/**
 * barrido.mjs — the four-measurement sweep across every page × width of a hash-routed maqueta.
 *
 * Run:  node barrido.mjs <url-of-a-maqueta> [--json] [--anchos 430,768,1280]
 *
 * The maqueta is ONE file with hash-routed pages: every page is `<div class="page ..." id="slug">`
 * and a router shows one at a time when `location.hash` changes. Pages are discovered from the DOM
 * itself (`.page` elements, in document order) — the tool needs no page list from the caller. For
 * each width the page is loaded ONCE (a real `Page.navigate`, same trick as `medir-geometria.mjs`:
 * bounce through `about:blank` first, because a same-document navigation never fires
 * `Page.loadEventFired`), then every page is visited by setting `location.hash` and waiting for the
 * router's own `hashchange` handler to run — never a re-navigation per page.
 *
 * FOUR MEASUREMENTS, precisely, and narrow enough to be true:
 *
 *   1. OVERFLOW. `document.documentElement.scrollWidth > document.documentElement.clientWidth`,
 *      read straight off the DOM already laid out. Nothing else counts: a table that scrolls inside
 *      its own `overflow-x:auto` container never touches `documentElement.scrollWidth`, because that
 *      container's own scroll box absorbs the extra width. Checked at 430, 768 and 1280.
 *
 *   2. MULTI-TRACK GRIDS AT DESKTOP (1280 only — `n/a` at 430 and 768). For every element whose
 *      computed `display` is `grid` or `inline-grid`, with at least two visible element children:
 *      compare what the STYLESHEET authored for `grid-template-columns` on that element (walked from
 *      `document.styleSheets`, honouring `@media`/`@supports` wrappers that are currently true, and
 *      taking the last matching declaration in source order as the winner — a plain cascade
 *      approximation, not a full cascade engine, and deliberately blind to inline `style=""`, which
 *      is the whole point: an inline override is exactly the bug this measurement exists to catch)
 *      against what is RENDERED (`getComputedStyle(el).gridTemplateColumns`, split into tracks). If
 *      the authored declaration names more than one track (a bare list like `1fr 1fr 1fr`, or
 *      `repeat(N, …)` with a literal N>1 — `repeat(auto-fill|auto-fit, …)` is deliberately excluded
 *      here, because an auto-repeat grid does not "declare" a track count; its collapses belong to
 *      measurement 3) but only one track renders, that is the failure. A grid authored with one
 *      column is a list, not a failure — it is skipped, not flagged.
 *
 *   3. PHANTOM TRACKS AND ORPHANS (430, 768 and 1280 — no width is exempt).
 *      - Phantom tracks: a grid (computed `display: grid`/`inline-grid`) whose rendered track count
 *        is ≥2, grouped into visual rows by rounded `getBoundingClientRect().top`, where the fullest
 *        row still has fewer visible children than there are rendered tracks. That gap is empty
 *        columns — the classic shape of `repeat(auto-fill, minmax(...))` outrunning its content.
 *      - Orphans: in a grid OR a `flex-wrap: wrap` container with at least 3 visible children that
 *        are HOMOGENEOUS — same tagName, and the same first class token when they carry a class at
 *        all (a classless child is compared by tagName alone) — grouped into rows the same way: the
 *        last row holds exactly one child while the row before it holds three or more. Heterogeneous
 *        rows are skipped before grouping even runs: a caption next to a control next to a button is
 *        not a repeated-card grid dropping its last card, and this check exists for the latter (REFINED
 *        after bajura's `.franja-cp-row` — title, input, button, status text, note — wrapped its note
 *        to a second line and read as an "orphan" that was really just a caption under a control row).
 *      Both checks read the CHILDREN'S rendered boxes, not the CSS, so they need no cascade walk.
 *
 *   4. ONE MENU TOGGLE AT 430 (430 only — `n/a` at 768 and 1280). Every `<header>` in the document is
 *      a candidate "site header" (a maqueta may keep one persistent header outside every `.page`, or
 *      — as this tool's own control fixture does, to keep each broken page isolated — one header per
 *      page); whichever `<header>` is actually VISIBLE at the moment of measurement is THE site header
 *      for that page. Inside it, count distinct visible elements that are a DISCLOSURE control: they
 *      carry `aria-controls` or `aria-expanded`, or have a class containing `burger`, `menu-toggle` or
 *      `hamburguesa` (an element with both `aria-expanded` and a `burger` class is one element, not
 *      two). A bare `<button>` with none of those signals is an ACTION, not a toggle, and is no longer
 *      a candidate on its own (REFINED after bajura's header also held a postal-code "Comprobar"
 *      button with no aria-expanded/aria-controls, which the old "any button counts" rule mistook for
 *      a second toggle). Exactly one disclosure control must be visible; zero or more than one is the
 *      failure.
 *
 * "Visible" everywhere above means: not `[hidden]`, no ancestor (up to `<body>`) with
 * `display:none`/`visibility:hidden|collapse`, and a rendered box with width>0 and height>0 — which
 * is also why an element inside an inactive `.page` (the router sets `display:none` on it) is simply
 * invisible and drops out of every measurement without special-casing the router.
 *
 * Exit contract, shared with the rest of the toolbox: `0` every applicable measurement passed on
 * every page and width, `1` a measured failure (including any `no-disponible` cell — the contract
 * this tool answers to, `veredicto-formato.md`, is explicit that a skipped cell is never a pass),
 * `2` usage or environment (bad flags, Chrome not found, the url would not load at all).
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

/* Runs INSIDE the page. One copy of the measurement, kept as one string like medir-geometria's PROBE.
   No backticks inside: this whole thing is itself a template literal at the Node level. */
const PROBE_PAGES = `(() => Array.from(document.querySelectorAll('.page')).map(el => el.id))()`;

const PROBE_MEASURE = `(() => {
  const W = document.documentElement.clientWidth;

  function isVisible(el) {
    if (!el || el.nodeType !== 1) return false;
    if (el.hidden) return false;
    let node = el;
    while (node && node !== document.body) {
      const cs = getComputedStyle(node);
      if (cs.display === 'none' || cs.visibility === 'hidden' || cs.visibility === 'collapse') return false;
      node = node.parentElement;
    }
    const r = el.getBoundingClientRect();
    return r.width > 0 && r.height > 0;
  }

  function describe(el) {
    let s = el.tagName.toLowerCase();
    if (el.id) s += '#' + el.id;
    if (typeof el.className === 'string' && el.className.trim()) {
      s += '.' + el.className.trim().split(/\\s+/).slice(0, 3).join('.');
    }
    return s;
  }

  /* Split a grid-template-columns string (authored or computed) into top-level tracks: strip
     bracketed line names, then split on whitespace that is not inside parentheses (so a
     "repeat(3, 1fr)" call stays one token, not three). */
  function trackTokens(str) {
    if (!str) return [];
    const stripped = str.replace(/\\[[^\\]]*\\]/g, ' ').trim();
    if (!stripped) return [];
    const tokens = [];
    let depth = 0, cur = '';
    for (const ch of stripped) {
      if (ch === '(') depth++;
      if (ch === ')') depth--;
      if (/\\s/.test(ch) && depth === 0) {
        if (cur) tokens.push(cur);
        cur = '';
      } else {
        cur += ch;
      }
    }
    if (cur) tokens.push(cur);
    return tokens;
  }

  /* The winning grid-template-columns declaration for el among document.styleSheets rules, or
     null. Walks @media / @supports and keeps only currently-true branches; among matching rules
     (tested with el.matches(selectorText)) the LAST one in source order wins — a plain
     specificity-blind cascade approximation. Deliberately ignores el.style (inline): an inline
     override winning at runtime while the stylesheet still says otherwise is exactly the mismatch
     measurement 2 exists to catch, not a case to paper over. */
  function authoredGridColumns(el) {
    let authored = null;
    function walk(rules) {
      for (const rule of rules) {
        if (rule.type === CSSRule.MEDIA_RULE) {
          let ok = false;
          try { ok = window.matchMedia(rule.media.mediaText).matches; } catch (e) {}
          if (ok) walk(rule.cssRules);
          continue;
        }
        if (typeof CSSRule.SUPPORTS_RULE !== 'undefined' && rule.type === CSSRule.SUPPORTS_RULE) {
          let ok = false;
          try { ok = CSS.supports(rule.conditionText); } catch (e) {}
          if (ok) walk(rule.cssRules);
          continue;
        }
        if (rule.type !== CSSRule.STYLE_RULE) continue;
        let matches = false;
        try { matches = el.matches(rule.selectorText); } catch (e) {}
        if (!matches) continue;
        const v = rule.style.getPropertyValue('grid-template-columns');
        if (v) authored = v;
      }
    }
    for (const ss of document.styleSheets) {
      let rules;
      try { rules = ss.cssRules; } catch (e) { continue; }
      walk(rules);
    }
    return authored;
  }

  /* Track count an authored declaration names, and whether it used an auto-fill/auto-fit repeat
     (excluded from the count on purpose — see measurement 2's definition above). */
  function authoredTrackCount(authored) {
    const tokens = trackTokens(authored);
    let count = 0, hasAutoRepeat = false;
    for (const t of tokens) {
      const rm = /^repeat\\(\\s*([0-9]+)\\s*,([\\s\\S]*)\\)$/i.exec(t);
      if (rm) {
        const n = parseInt(rm[1], 10);
        const inner = trackTokens(rm[2]).length || 1;
        count += n * inner;
        continue;
      }
      if (/^repeat\\(\\s*(auto-fill|auto-fit)\\s*,/i.test(t)) { hasAutoRepeat = true; continue; }
      count += 1;
    }
    return { count, hasAutoRepeat };
  }

  function renderedTrackCount(el) {
    return trackTokens(getComputedStyle(el).gridTemplateColumns).length;
  }

  /* A child CSS Grid/flexbox actually lays out — excludes position:absolute/fixed, which the spec
     removes from grid/flex placement entirely (it is not "a grid item"/"a flex item"). Counting it
     anyway is exactly how a 3-column header (logo, nav, absolutely-positioned decorative badge) or
     an overlay hero (background layer + content, one of them unpositioned) used to read as a grid
     with the wrong child count. */
  /* Not display:none/[hidden]/visibility:hidden anywhere up the tree — unlike isVisible(), this
     does NOT require a non-zero rendered box. A deliberately empty grid cell (an alignment
     placeholder — a header span with no text, sitting above a real icon-button column that the
     row below fills in) still occupies its track; it simply has nothing to paint, and align-items
     other than the grid default (stretch) can legitimately collapse it to zero height. Filtering
     it out by box size made a genuinely full N-item grid read as short by exactly that one column. */
  function isRendered(el) {
    if (!el || el.nodeType !== 1) return false;
    if (el.hidden) return false;
    let node = el;
    while (node && node !== document.body) {
      const cs = getComputedStyle(node);
      if (cs.display === 'none' || cs.visibility === 'hidden' || cs.visibility === 'collapse') return false;
      node = node.parentElement;
    }
    return true;
  }

  function isLayoutItem(el) {
    if (!isRendered(el)) return false;
    const pos = getComputedStyle(el).position;
    return pos !== 'absolute' && pos !== 'fixed';
  }

  /* GRID rows, by DOCUMENT ORDER and the rendered track count — item i sits at row floor(i/tracks),
     exactly how default grid-auto-flow:row places items. Deliberately NOT geometry: a header bar
     with align-items:center, or a hero with align-items:end, puts same-row siblings of different
     heights at different getBoundingClientRect().top values, which used to read as several rows of
     one child each — a false phantom/orphan on every ordinary vertically-centered row. This does not
     handle grid-auto-flow:column or explicit grid-row/grid-column placement (rare in these
     maquetas); that is a known, narrow limitation, not a silent one. */
  function rowsByTrackCount(children, trackCount) {
    const rows = [];
    for (let i = 0; i < children.length; i++) {
      const r = Math.floor(i / trackCount);
      ( rows[ r ] || ( rows[ r ] = [] ) ).push( children[ i ] );
    }
    return rows;
  }

  /* FLEX-WRAP rows, by vertical OVERLAP rather than equal top — flex-wrap has no track count to
     reason from, so this stays geometry-based, but two children only need to share SOME vertical
     span to count as the same row, which survives the same alignment-driven top differences. */
  function rowsByOverlap(children) {
    const items = children
      .map((el) => { const r = el.getBoundingClientRect(); return { el, top: r.top, bottom: r.bottom }; })
      .sort((a, b) => a.top - b.top);
    const rows = [];
    for (const item of items) {
      let row = rows.find((r) => item.top < r.bottom - 1 && item.bottom > r.top + 1);
      if (!row) { row = { top: item.top, bottom: item.bottom, items: [] }; rows.push(row); }
      else { row.top = Math.min(row.top, item.top); row.bottom = Math.max(row.bottom, item.bottom); }
      row.items.push(item.el);
    }
    rows.sort((a, b) => a.top - b.top);
    return rows.map((r) => r.items);
  }

  const overflow = {
    fail: document.documentElement.scrollWidth > document.documentElement.clientWidth,
    scrollWidth: document.documentElement.scrollWidth,
    clientWidth: document.documentElement.clientWidth,
  };

  const gridEls = Array.from(document.querySelectorAll('*')).filter((el) => {
    if (!isVisible(el)) return false;
    const d = getComputedStyle(el).display;
    return d === 'grid' || d === 'inline-grid';
  });

  const gridFails = [];
  for (const el of gridEls) {
    const kids = Array.from(el.children).filter(isLayoutItem);
    if (kids.length < 2) continue;
    const authored = authoredGridColumns(el);
    const { count: authoredCount, hasAutoRepeat } = authoredTrackCount(authored);
    if (hasAutoRepeat || authoredCount <= 1) continue;
    const rendered = renderedTrackCount(el);
    if (rendered === 1) {
      gridFails.push({ selector: describe(el), authored, authoredCount, rendered });
    }
  }

  /* REFINED (bajura contradiction, found while investigating findings by hand, not part of the
     coordinator's two named rules but the same kind of bug): phantom tracks used to count CHILDREN
     per row against rendered track count, which is right only when every child occupies exactly
     one track. bajura's .row-3 "Como viaja" block has two children — one plain, one authored
     grid-column:span 2 — that together fill all three tracks; counting children (2) against tracks
     (3) read that as a phantom column that does not exist. getComputedStyle here resolves an
     authored span to a literal "span N" string (verified against this exact element, not assumed),
     so summing each child's declared span per row is a direct, reliable fix — not a geometry
     estimate that could drift on non-uniform track widths. */
  function declaredSpan(el) {
    const cs = getComputedStyle(el);
    const m = /^span\\s+([0-9]+)/i.exec(cs.gridColumnEnd) || /^span\\s+([0-9]+)/i.exec(cs.gridColumnStart);
    return m ? parseInt(m[1], 10) : 1;
  }

  const phantomFails = [];
  for (const el of gridEls) {
    const kids = Array.from(el.children).filter(isLayoutItem);
    if (kids.length < 1) continue;
    const rendered = renderedTrackCount(el);
    if (rendered < 2) continue;
    const rows = rowsByTrackCount(kids, rendered);
    const maxRow = rows.length ? Math.max(...rows.map((r) => r.reduce((sum, k) => sum + declaredSpan(k), 0))) : 0;
    if (maxRow > 0 && maxRow < rendered) {
      phantomFails.push({ selector: describe(el), rendered, maxRow });
    }
  }

  /* REFINED (bajura contradiction): the orphan rule exists for a repeated-card grid dropping its
     last card alone — "6 tarjetas en 2 pistas: 3 pistas y una huérfana" — not for any wrapping row
     whatsoever. bajura's .franja-cp-row is a flex-wrap of five DIFFERENT things (a label, an
     input, a button, a status line, a note): the note wraps to its own line, and that is a
     caption under a control row, not an orphaned repeat. Restrict the check to containers whose
     counted children are HOMOGENEOUS — same tagName, and the same first class token when they
     carry a class at all (a bare div with no class is only compared by tagName, which is what
     keeps the plain-div control fixtures below working unchanged). Heterogeneous rows — a
     caption row, a toolbar, this postal-code strip — are skipped outright, before rows are even
     grouped; lumiere's four .pie-col footer columns are same-tag-same-class and still flag. */
  function childSignature(el) {
    const cls = typeof el.className === 'string' && el.className.trim() ? el.className.trim().split(/\s+/)[0] : '';
    return el.tagName + '|' + cls;
  }
  function isHomogeneous(kids) {
    if (kids.length === 0) return true;
    const sig = childSignature(kids[0]);
    return kids.every((k) => childSignature(k) === sig);
  }

  const orphanFails = [];
  const wrapContainers = Array.from(document.querySelectorAll('*')).filter((el) => {
    if (!isVisible(el)) return false;
    const cs = getComputedStyle(el);
    if (cs.display === 'grid' || cs.display === 'inline-grid') return true;
    return (cs.display === 'flex' || cs.display === 'inline-flex') && cs.flexWrap && cs.flexWrap.indexOf('wrap') === 0;
  });
  for (const el of wrapContainers) {
    const kids = Array.from(el.children).filter(isLayoutItem);
    if (kids.length < 3) continue;
    if (!isHomogeneous(kids)) continue;
    const cs = getComputedStyle(el);
    const isGrid = cs.display === 'grid' || cs.display === 'inline-grid';
    const rows = isGrid ? rowsByTrackCount(kids, Math.max(1, renderedTrackCount(el))) : rowsByOverlap(kids);
    if (rows.length < 2) continue;
    const last = rows[rows.length - 1];
    const prev = rows[rows.length - 2];
    if (last.length === 1 && prev.length >= 3) {
      orphanFails.push({ selector: describe(el), lastRow: last.length, prevRow: prev.length });
    }
  }

  /* REFINED (bajura contradiction): "a button... inside the site header" was too broad — bajura's
     header also holds button.btn "Comprobar", a postal-code lookup action with no aria-expanded
     and no aria-controls, and a bare button counted it as a second menu toggle. A menu toggle is a
     DISCLOSURE control: it always exposes its state or its target through aria-expanded or
     aria-controls, or is named as one by class (burger / menu-toggle / hamburguesa). An action
     button that opens nothing accessibly is not a disclosure and is no longer a candidate at all —
     dropping the bare button selector, not just filtering by text or position, is what keeps this
     narrow: any REAL toggle still matches on its own aria/class signal. */
  const headers = Array.from(document.querySelectorAll('header'));
  const header = headers.find(isVisible) || null;
  let toggleEls = [];
  if (header) {
    const seen = new Set();
    header.querySelectorAll('[aria-controls], [aria-expanded], [class*="burger"], [class*="menu-toggle"], [class*="hamburguesa"]')
      .forEach((el) => { if (el.getAttribute('aria-hidden') !== 'true') seen.add(el); });
    /* A decorative icon (the three-line glyph) inside the real toggle button matches the same
       class/attribute selector as its parent button and used to count as a second control. Keep
       only the outermost element of each nested cluster: the actual control, not its artwork. */
    const visible = Array.from(seen).filter(isVisible);
    toggleEls = visible.filter((el) => !visible.some((other) => other !== el && other.contains(el)));
  }

  return {
    width: W,
    overflow,
    gridFails,
    phantomFails,
    orphanFails,
    menuToggle: { headerFound: !!header, count: toggleEls.length, elements: toggleEls.map(describe) },
  };
})()`;

function gotoHashExpr( id ) {
	const target = '#' + id;
	return `(function(){
    return new Promise((resolve) => {
      var target = ${ JSON.stringify( target ) };
      if (location.hash === target) { resolve('same'); return; }
      var done = false;
      function onChange(){ if (done) return; done = true; window.removeEventListener('hashchange', onChange); resolve('changed'); }
      window.addEventListener('hashchange', onChange);
      location.hash = target;
      setTimeout(function(){ if (!done) { done = true; window.removeEventListener('hashchange', onChange); resolve('timeout'); } }, 1500);
    });
  })()`;
}

async function loadAtWidth( client, sessionId, url, width ) {
	await client.send(
		'Emulation.setDeviceMetricsOverride',
		{ width, height: 900, deviceScaleFactor: 1, mobile: false },
		sessionId
	);
	/* Same-document navigation never fires Page.loadEventFired — see medir-geometria.mjs. Bounce
	   through about:blank so every width gets one real, full load. */
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
}

async function measurePage( client, sessionId, id ) {
	await client.send( 'Runtime.evaluate', { expression: gotoHashExpr( id ), awaitPromise: true }, sessionId );
	await sleep( 250 );
	const { result } = await client.send(
		'Runtime.evaluate',
		{ expression: PROBE_MEASURE, returnByValue: true },
		sessionId
	);
	return result.value;
}

/* Turn one page's raw per-width measurement into the four-measurement verdict the veredicto needs. */
function interpret( data, width ) {
	if ( ! data ) {
		return { status: 'no-disponible', reason: 'sin datos', measures: null };
	}
	const measures = {
		overflow: {
			status: data.overflow.fail ? 'fail' : 'pass',
			evidence: data.overflow.fail
				? [ { expected: `scrollWidth <= ${ data.overflow.clientWidth }`, observed: `scrollWidth ${ data.overflow.scrollWidth }` } ]
				: [],
		},
		grid: {
			status: width !== 1280 ? 'n/a' : data.gridFails.length ? 'fail' : 'pass',
			evidence: width !== 1280 ? [] : data.gridFails.map( ( f ) => ( {
				selector: f.selector,
				expected: `${ f.authoredCount } pistas (autoría: ${ f.authored })`,
				observed: `${ f.rendered } pista`,
			} ) ),
		},
		phantomOrphan: {
			status: ( data.phantomFails.length || data.orphanFails.length ) ? 'fail' : 'pass',
			evidence: [
				...data.phantomFails.map( ( f ) => ( {
					selector: f.selector,
					kind: 'fantasma',
					expected: `${ f.rendered } pistas ocupadas`,
					observed: `${ f.maxRow } elementos en la fila más llena`,
				} ) ),
				...data.orphanFails.map( ( f ) => ( {
					selector: f.selector,
					kind: 'huerfano',
					expected: 'ninguna fila de 1 tras una fila de 3 o más',
					observed: `fila anterior ${ f.prevRow }, última fila ${ f.lastRow }`,
				} ) ),
			],
		},
		menuToggle: {
			status: width !== 430 ? 'n/a' : data.menuToggle.count === 1 ? 'pass' : 'fail',
			evidence: width !== 430 || data.menuToggle.count === 1
				? []
				: [ {
					expected: '1 control de menú',
					observed: data.menuToggle.headerFound
						? `${ data.menuToggle.count } controles (${ data.menuToggle.elements.join( ', ' ) || 'ninguno' })`
						: 'no se encontró <header> visible',
				} ],
		},
	};
	const status = Object.values( measures ).some( ( m ) => m.status === 'fail' ) ? 'fail' : 'pass';
	return { status, measures };
}

async function main() {
	const url = process.argv[ 2 ];
	if ( ! url || url.startsWith( '--' ) ) {
		console.error( 'usage: node barrido.mjs <url-de-una-maqueta> [--json] [--anchos 430,768,1280]' );
		process.exit( 2 );
	}
	const widths = ( arg( '--anchos', '430,768,1280' ) ).split( ',' ).map( ( n ) => Number( n.trim() ) );
	const asJson = process.argv.includes( '--json' );

	const profile = join( tmpdir(), 'nm-barrido-' + Date.now() );
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

	let usageError = null;
	const paginas = [];
	/** @type {Record<string, Record<number, ReturnType<typeof interpret>>>} */
	const resultados = {};

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

		for ( const width of widths ) {
			try {
				await loadAtWidth( client, sessionId, url, width );
			} catch ( e ) {
				if ( paginas.length === 0 ) {
					/* First width could not even load the url at all: that is environment, not a
					   per-page measurement gap. */
					throw new Error( `no se pudo cargar ${ url }: ${ e.message }` );
				}
				for ( const id of paginas ) {
					resultados[ id ] = resultados[ id ] || {};
					resultados[ id ][ width ] = { status: 'no-disponible', reason: e.message, measures: null };
				}
				continue;
			}
			if ( paginas.length === 0 ) {
				const { result } = await client.send( 'Runtime.evaluate', { expression: PROBE_PAGES, returnByValue: true }, sessionId );
				const found = result.value || [];
				if ( found.length === 0 ) {
					throw new Error( `${ url } no tiene ningún elemento .page` );
				}
				paginas.push( ...found );
			}
			for ( const id of paginas ) {
				resultados[ id ] = resultados[ id ] || {};
				try {
					const data = await measurePage( client, sessionId, id );
					resultados[ id ][ width ] = interpret( data, width );
				} catch ( e ) {
					resultados[ id ][ width ] = { status: 'no-disponible', reason: e.message, measures: null };
				}
			}
		}
		ws.close();
	} catch ( e ) {
		usageError = e;
	} finally {
		chrome.kill();
	}

	if ( usageError ) {
		console.error( 'barrido: ' + usageError.message );
		process.exit( 2 );
	}

	let anyFail = false;
	for ( const id of paginas ) {
		for ( const width of widths ) {
			const cell = resultados[ id ][ width ];
			if ( ! cell || cell.status !== 'pass' ) {
				anyFail = true;
			}
		}
	}

	if ( asJson ) {
		console.log( JSON.stringify( { url, anchos: widths, paginas, resultados }, null, 2 ) );
	} else {
		console.log( `\nbarrido · ${ url }` );
		console.log( `  páginas: ${ paginas.join( ', ' ) }` );
		for ( const id of paginas ) {
			const cells = widths.map( ( w ) => {
				const cell = resultados[ id ][ w ];
				if ( ! cell ) return `${ w }=?`;
				if ( cell.status === 'no-disponible' ) return `${ w }=no-disponible(${ cell.reason })`;
				if ( cell.status === 'pass' ) return `${ w }=✓`;
				const fails = Object.entries( cell.measures )
					.filter( ( [ , m ] ) => m.status === 'fail' )
					.map( ( [ name, m ] ) => `${ name }:${ m.evidence.map( ( e ) => `${ e.selector ? e.selector + ' ' : '' }esperado ${ e.expected } observado ${ e.observed }` ).join( ' | ' ) }` );
				return `${ w }=FAIL[${ fails.join( '; ' ) }]`;
			} );
			console.log( `  ${ id.padEnd( 24 ) } ${ cells.join( '   ' ) }` );
		}
	}

	process.exit( anyFail ? 1 : 0 );
}

main().catch( ( e ) => {
	console.error( 'barrido: ' + e.message );
	process.exit( 2 );
} );
