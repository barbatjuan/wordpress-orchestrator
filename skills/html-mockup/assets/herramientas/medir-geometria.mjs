/**
 * medir-geometria.mjs — la validación que faltaba.
 *
 * Run:  node medir-geometria.mjs <url> [--ancho 1440] [--json]
 *
 * Contraste y desbordamiento ya tenían herramienta. La GEOMETRÍA no: cuánto margen deja la página,
 * a qué ancho se detiene su contenido, y si ese margen se mantiene o crece con la pantalla. Eso se
 * revisaba a ojo, y a ojo es exactamente como se cuela — un lector dijo "no hay casi márgenes"
 * sobre una página cuyas tres comprobaciones automáticas estaban en verde.
 *
 * Qué mide, sobre el DOM ya maquetado y no sobre el CSS:
 *
 *   1. LA MEDIDA. El borde izquierdo y derecho de cada bloque de TEXTO de la página, agrupados.
 *      Una página con criterio tiene pocos valores distintos: un raíl. Una página sin criterio
 *      tiene veinte. Se informan los cinco más frecuentes y cuántos elementos caen en cada uno.
 *
 *   2. EL MARGEN, como FRACCIÓN y no como umbral. `izquierda / ancho`. Un margen que crece de
 *      forma monótona con el ancho es el defecto, aunque cada lectura suelta esté por debajo de
 *      cualquier barra que se elija. Por eso se mide en varios anchos y se compara la curva.
 *
 *   3. LA COLUMNA DE LECTURA. Caracteres por línea de cada párrafo largo, estimados por la anchura
 *      real del texto renderizado. Por debajo de 45 o por encima de 85 se nombra.
 *
 *   4. LO QUE TOCA EL CRISTAL. Todo elemento cuyo borde llega al del lienzo, con qué contiene. Una
 *      fotografía a sangre es la composición funcionando; un párrafo o un control a sangre es una
 *      amputación. Misma regla que `qa-review` fila 32, aplicada aquí antes de que exista la web.
 *
 *   5. EL DESBORDE. `scrollWidth` contra `clientWidth`, por si acaso.
 *
 * Sale 1 si algo toca el cristal que no sea media, o si hay desborde. Todo lo demás se informa:
 * un margen estrecho puede ser una decisión, y esta herramienta no la toma por nadie.
 */
import { spawn } from 'node:child_process';
import { existsSync, readFileSync, mkdirSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const sleep = ( ms ) => new Promise( ( r ) => setTimeout( r, ms ) );

function arg( flag, fallback = null ) {
	const i = process.argv.indexOf( flag );
	return i === -1 || i === process.argv.length - 1 ? fallback : process.argv[ i + 1 ];
}

function chromeBinary() {
	const candidates = [
		process.env.CHROME_PATH,
		'C:/Program Files/Google/Chrome/Application/chrome.exe',
		'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
		'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
		'/usr/bin/google-chrome',
		'/usr/bin/chromium',
	].filter( Boolean );
	for ( const c of candidates ) {
		if ( existsSync( c ) ) {
			return c;
		}
	}
	throw new Error( 'Chrome not found. Set CHROME_PATH. Tried: ' + candidates.join( ', ' ) );
}

async function waitForPort( profile, timeoutMs = 20000 ) {
	const portFile = join( profile, 'DevToolsActivePort' );
	const deadline = Date.now() + timeoutMs;
	while ( Date.now() < deadline ) {
		if ( existsSync( portFile ) ) {
			const first = /^[0-9]+/.exec( readFileSync( portFile, 'utf8' ) );
			if ( first ) {
				return Number( first[ 0 ] );
			}
		}
		await sleep( 100 );
	}
	throw new Error( 'Chrome did not report a debugger port' );
}

async function waitForDebugger( port, timeoutMs = 20000 ) {
	const deadline = Date.now() + timeoutMs;
	while ( Date.now() < deadline ) {
		try {
			const res = await fetch( `http://127.0.0.1:${ port }/json/version` );
			if ( res.ok ) {
				return ( await res.json() ).webSocketDebuggerUrl;
			}
		} catch {
			/* not up yet */
		}
		await sleep( 150 );
	}
	throw new Error( 'Chrome did not open a debugger' );
}

function cdp( ws ) {
	let id = 0;
	const pending = new Map();
	const listeners = [];
	ws.addEventListener( 'message', ( ev ) => {
		const msg = JSON.parse( ev.data );
		if ( msg.id !== undefined && pending.has( msg.id ) ) {
			const { ok, fail } = pending.get( msg.id );
			pending.delete( msg.id );
			msg.error ? fail( new Error( msg.error.message ) ) : ok( msg.result );
			return;
		}
		listeners.forEach( ( fn ) => fn( msg ) );
	} );
	return {
		send( method, params = {}, sessionId ) {
			const payload = { id: ++id, method, params };
			if ( sessionId ) {
				payload.sessionId = sessionId;
			}
			return new Promise( ( ok, fail ) => {
				pending.set( payload.id, { ok, fail } );
				ws.send( JSON.stringify( payload ) );
			} );
		},
		once( predicate, timeoutMs = 30000 ) {
			return new Promise( ( ok, fail ) => {
				const timer = setTimeout( () => fail( new Error( 'CDP event timeout' ) ), timeoutMs );
				const fn = ( msg ) => {
					if ( ! predicate( msg ) ) {
						return;
					}
					clearTimeout( timer );
					listeners.splice( listeners.indexOf( fn ), 1 );
					ok( msg );
				};
				listeners.push( fn );
			} );
		},
	};
}

/* Runs INSIDE the page. Kept as one string so there is one copy of the measurement, not two. */
const PROBE = `(() => {
  const W = document.documentElement.clientWidth;
  const MEDIA = new Set(['IMG','PICTURE','VIDEO','SVG','CANVAS','FIGURE']);
  const out = { width: W, scrollWidth: document.documentElement.scrollWidth, rails: {}, railText: {}, edge: [], measure: [], blocks: 0 };

  const isText = (el) => {
    if (!el.firstChild) return false;
    for (const n of el.childNodes) if (n.nodeType === 3 && n.textContent.trim().length > 2) return true;
    return false;
  };

  for (const el of document.querySelectorAll('p,h1,h2,h3,h4,li,dt,dd,span,a,button,label,td,th,blockquote')) {
    const r = el.getBoundingClientRect();
    if (r.width < 8 || r.height < 4) continue;
    if (!isText(el)) continue;
    /* Misma exencion que en la prueba del cristal: una caja que no solapa el viewport esta
       escondida, no desalineada. Un enlace de salto en left:-9999px abria un rail fantasma
       a -9999 y se llevaba el informe entero. */
    if (r.right <= 0 || r.left >= W) continue;
    out.blocks++;
    /* EL RAIL ES LA TINTA, NO LA CAJA. Un <p> a ancho completo con 108px de relleno tiene la caja
       en 0 y el texto en 108: contarlo por la caja inventa un rail a cero en cada rotulo de
       seccion correctamente sangrado. Es el mismo error que ya se pago en la prueba del cristal,
       cometido dos veces en el mismo fichero por no compartir la medida. */
    let tinta = null;
    for (const n of el.childNodes) {
      if (n.nodeType !== 3 || !n.textContent.trim()) continue;
      const rg = document.createRange(); rg.selectNodeContents(n);
      for (const q of rg.getClientRects()) {
        if (q.width < 2) continue;
        tinta = tinta ? { l: Math.min(tinta.l, q.left), r: Math.max(tinta.r, q.right) }
                      : { l: q.left, r: q.right };
      }
    }
    const L = Math.round(tinta ? tinta.l : r.left), R = Math.round(tinta ? tinta.r : r.right);
    out.rails[L] = (out.rails[L] || 0) + 1;
    if (!out.railText[L]) out.railText[L] = (el.textContent || '').trim().slice(0, 46);
    if (L <= 1 || R >= W - 1) {
      out.edge.push({ tag: el.tagName, left: L, right: R, text: (el.textContent || '').trim().slice(0, 46) });
    }
    /* READING MEASURE, FROM THE LONGEST RENDERED LINE, NOT FROM THE BOX.
       Dividing the border box by half the font size calls a hard-broken block a violation: a
       footer of four <br>-separated lines, longest line 218px in a 272px box, was reported at 44
       characters while the identical footer one file over passed, and the only difference between
       them was 7 characters of total length. A block that never wraps has no measure to judge. */
    if (el.tagName === 'P' && (el.textContent || '').trim().length > 120) {
      const fs = parseFloat(getComputedStyle(el).fontSize) || 16;
      let widest = 0, lines = 0;
      for (const n of el.childNodes) {
        if (n.nodeType !== 3 || !n.textContent.trim()) continue;
        const rg = document.createRange(); rg.selectNodeContents(n);
        for (const q of rg.getClientRects()) {
          if (q.width < 2) continue;
          lines++; widest = Math.max(widest, q.width);
        }
      }
      /* WRAPPED PROSE OR HARD-BROKEN LINES. Only wrapped text has a measure to judge. In prose
         every line but the last runs close to the container; in a <br>-separated stack the lines
         are short and ragged and none of them fills the box. So the discriminator is fill: if the
         longest rendered line does not reach 85% of the container, nothing is wrapping and the
         block is a list of lines wearing a <p>. Footer address and returns blocks are exactly
         that, and flagging them at 35 characters is noise that trains a reader to skip the row. */
      const fill = r.width > 0 ? widest / r.width : 0;
      if (lines >= 3 && fill >= 0.85) {
        out.measure.push({ ch: Math.round(widest / (fs * 0.5)), px: Math.round(widest), fs: Math.round(fs), lines });
      }
    }
  }

  /* Anything at all whose box reaches the glass, and what kind of thing it is. */
  for (const el of document.querySelectorAll('*')) {
    const r = el.getBoundingClientRect();
    if (r.width < 24 || r.height < 12) continue;
    if (r.left > 1 && r.right < W - 1) continue;
    /* FUERA DEL LIENZO NO ES AL CRISTAL. Un enlace de salto se aparca con
       position:absolute con left:-9999px y vuelve con :focus{left:0}: es el patrón de accesibilidad
       de manual, no una amputación. Verificado en la maqueta de delao (la clase .skip, líneas 178-180)
       antes de eximirlo, porque el defecto que esta prueba caza es tinta que TOCA el cristal
       estando a la vista. Una caja que no solapa el viewport está escondida, no cortada. */
    if (r.right <= 0 || r.left >= W) continue;
    const media = MEDIA.has(el.tagName) || !!el.querySelector('img,picture,video,svg,canvas');
    if (media) continue;
    /* THE INK, NOT THE BOX. A <p> with horizontal padding has a full-width border box and text
       inset by that padding; reporting the box calls a correctly inset paragraph an amputation.
       qa-review row 32 arm (c) settles this: walk the text nodes and take a Range per node. */
    let ink = null;
    for (const n of el.childNodes) {
      if (n.nodeType !== 3 || !n.textContent.trim()) continue;
      const rg = document.createRange(); rg.selectNodeContents(n);
      for (const q of rg.getClientRects()) {
        if (q.width < 2) continue;
        ink = ink ? { l: Math.min(ink.l, q.left), r: Math.max(ink.r, q.right) } : { l: q.left, r: q.right };
      }
    }
    const controls = [...el.querySelectorAll('input,select,textarea,button')]
      .map(c => c.getBoundingClientRect()).filter(q => q.width > 8 && (q.left <= 1 || q.right >= W - 1));
    const inkAtGlass = ink && (ink.l <= 1 || ink.r >= W - 1);
    if (inkAtGlass || controls.length) {
      const txt = (el.textContent || '').trim();
      out.edge.push({ tag: el.tagName, left: Math.round(ink ? ink.l : r.left), right: Math.round(ink ? ink.r : r.right), text: txt.slice(0, 46) || (controls.length + ' controles'), kind: 'no-media' });
    }
  }
  return out;
})()`;

async function measure( client, sessionId, url, width ) {
	await client.send(
		'Emulation.setDeviceMetricsOverride',
		{ width, height: 900, deviceScaleFactor: 1, mobile: false },
		sessionId
	);
	/* UNA NAVEGACIÓN SAME-DOCUMENT SE COME EL EVENTO DE CARGA. measure() corre tres veces sobre la
	   misma página, y un segundo Page.navigate a una url que sólo difiere en su #fragmento —o ni
	   siquiera en eso— es una navegación dentro del mismo documento: Chrome mueve el scroll y no
	   emite nada. Esperar Page.loadEventFired se cuelga treinta segundos y la corrida muere con
	   «CDP event timeout». Medido sobre la maqueta de delao, cinco páginas enrutadas por hash,
	   tres anchos, cero mediciones. Pasar por about:blank fuerza una carga real cada vez. */
	const blank = client.once( ( m ) => m.method === 'Page.loadEventFired' && m.sessionId === sessionId );
	await client.send( 'Page.navigate', { url: 'about:blank' }, sessionId );
	await blank;
	const loaded = client.once( ( m ) => m.method === 'Page.loadEventFired' && m.sessionId === sessionId );
	await client.send( 'Page.navigate', { url }, sessionId );
	await loaded;
	await client.send(
		'Runtime.evaluate',
		{ expression: 'document.fonts ? document.fonts.ready.then(()=>1) : 1', awaitPromise: true },
		sessionId
	);
	await sleep( 350 );
	const { result } = await client.send(
		'Runtime.evaluate',
		{ expression: PROBE, returnByValue: true },
		sessionId
	);
	return result.value;
}

async function main() {
	const url = process.argv[ 2 ];
	if ( ! url || url.startsWith( '--' ) ) {
		console.error( 'usage: node medir-geometria.mjs <url> [--ancho 1440] [--json]' );
		process.exit( 2 );
	}
	const base = Number( arg( '--ancho', '1440' ) );
	const widths = [ base, Math.round( base * 1.33 ), Math.round( base * 1.78 ) ];

	const profile = join( tmpdir(), 'nm-geo-' + Date.now() );
	mkdirSync( profile, { recursive: true } );
	const chrome = spawn(
		chromeBinary(),
		[
			'--headless=new',
			'--disable-gpu',
			'--hide-scrollbars',
			'--no-first-run',
			'--no-default-browser-check',
			'--remote-debugging-port=0',
			'--user-data-dir=' + profile,
			'about:blank',
		],
		{ stdio: 'ignore' }
	);

	let failed = false;
	try {
		const wsUrl = await waitForDebugger( await waitForPort( profile ) );
		const ws = new WebSocket( wsUrl );
		await new Promise( ( ok, fail ) => {
			ws.addEventListener( 'open', ok, { once: true } );
			ws.addEventListener( 'error', () => fail( new Error( 'CDP socket failed' ) ), { once: true } );
		} );
		const client = cdp( ws );
		const { targetId } = await client.send( 'Target.createTarget', { url: 'about:blank' } );
		const { sessionId } = await client.send( 'Target.attachToTarget', { targetId, flatten: true } );
		await client.send( 'Page.enable', {}, sessionId );
		await client.send( 'Runtime.enable', {}, sessionId );

		const runs = [];
		for ( const w of widths ) {
			runs.push( { w, data: await measure( client, sessionId, url, w ) } );
		}

		const first = runs[ 0 ].data;
		const rails = Object.entries( first.rails )
			.map( ( [ x, n ] ) => [ Number( x ), n ] )
			.sort( ( a, b ) => b[ 1 ] - a[ 1 ] )
			.slice( 0, 5 );

		if ( arg( '--json' ) !== null && process.argv.includes( '--json' ) ) {
			console.log( JSON.stringify( { url, runs }, null, 2 ) );
		} else {
			console.log( `\ngeometría · ${ url }` );
			console.log( `  bloques de texto medidos: ${ first.blocks }` );
			console.log( `  raíles de la izquierda (los 5 más usados):` );
			for ( const [ x, n ] of rails ) {
				console.log( `    x=${ String( x ).padStart( 5 ) }  ${ String( n ).padStart( 3 ) } elementos   margen ${ ( ( x / first.width ) * 100 ).toFixed( 1 ) }%` );
			}
			const spread = rails.length ? rails[ 0 ][ 0 ] : 0;
			console.log( `  margen dominante: ${ spread }px sobre ${ first.width } = ${ ( ( spread / first.width ) * 100 ).toFixed( 1 ) }%` );

			/* TODO RAÍL POR DENTRO DEL MARGEN, NO SÓLO LOS CINCO MÁS USADOS. Una rejilla a sangre
			   con relleno de celda de 28px puso seis elementos a 28px del borde mientras el resto
			   de la lámina iba a 108. Entró en el listado por suerte: tenía elementos de sobra para
			   colarse en el top cinco. Con dos habría pasado desapercibida, y un texto a 28px del
			   cristal en una página de margen 108 es el defecto que este medidor existe para ver.
			   El umbral es el margen dominante, no un número: lo que se busca es texto que rompe el
			   margen que la propia página se dio. */
			const dentro = Object.entries( first.rails )
				.map( ( [ x, n ] ) => [ Number( x ), n ] )
				.filter( ( [ x ] ) => x < spread - 2 )
				.sort( ( a, b ) => a[ 0 ] - b[ 0 ] );
			if ( dentro.length ) {
				const total = dentro.reduce( ( s, [ , n ] ) => s + n, 0 );
				console.log( `  raíles POR DENTRO del margen dominante: ${ dentro.length } (${ total } elementos)` );
				for ( const [ x, n ] of dentro ) {
					const t = first.railText[ x ] ? ` «${ first.railText[ x ] }»` : '';
					console.log( `    x=${ String( x ).padStart( 5 ) }  ${ String( n ).padStart( 3 ) } elementos   margen ${ ( ( x / first.width ) * 100 ).toFixed( 1 ) }%${ t }` );
				}
			} else {
				console.log( `  raíles por dentro del margen dominante: ninguno` );
			}
			console.log( `  curva del margen dominante por ancho:` );
			for ( const r of runs ) {
				const top = Object.entries( r.data.rails ).map( ( [ x, n ] ) => [ Number( x ), n ] ).sort( ( a, b ) => b[ 1 ] - a[ 1 ] )[ 0 ];
				const f = top ? ( top[ 0 ] / r.data.width ) * 100 : 0;
				console.log( `    ${ String( r.w ).padStart( 5 ) }px → ${ String( top ? top[ 0 ] : 0 ).padStart( 4 ) }px  ${ f.toFixed( 1 ) }%` );
			}
			const ms = first.measure;
			if ( ms.length ) {
				const bad = ms.filter( ( m ) => m.ch < 45 || m.ch > 85 );
				const avg = Math.round( ms.reduce( ( s, m ) => s + m.ch, 0 ) / ms.length );
				console.log( `  columna de lectura: ${ ms.length } párrafos, media ${ avg } caracteres, ${ bad.length } fuera de 45–85` );
				for ( const m of bad.slice( 0, 6 ) ) {
					console.log( `    ${ m.ch } car  (${ m.px }px a ${ m.fs }px)` );
				}
			}
			const edge = first.edge.filter( ( e ) => e.kind === 'no-media' );
			console.log( `  al cristal sin ser media: ${ edge.length }` );
			for ( const e of edge.slice( 0, 8 ) ) {
				console.log( `    ${ e.tag } ${ e.left }→${ e.right }  «${ e.text }»` );
			}
			for ( const r of runs ) {
				if ( r.data.scrollWidth > r.data.width + 1 ) {
					console.log( `  DESBORDE a ${ r.w }px: scrollWidth ${ r.data.scrollWidth } > ${ r.data.width }` );
					failed = true;
				}
			}
			if ( edge.length ) {
				failed = true;
			}
		}
		ws.close();
	} finally {
		chrome.kill();
	}
	process.exit( failed ? 1 : 0 );
}

main().catch( ( e ) => {
	console.error( 'medir-geometria: ' + e.message );
	process.exit( 2 );
} );
