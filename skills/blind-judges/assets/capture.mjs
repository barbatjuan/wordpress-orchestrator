#!/usr/bin/env node
/**
 * Deterministic viewport capture for the blind judges — the half that must NOT be improvised.
 *
 * Run:  node capture.mjs <file-or-url> --label <name> [--out <dir>] [--frames 4]
 *
 * Why this file exists at all. The first calibration run had the judge drive its own browser, and
 * it worked — but it cost 71 tool calls and thirteen minutes, and every run would have picked its
 * own viewport, its own scroll offsets and its own image quality. Two descriptions captured under
 * two geometries are not comparable, and comparing them across deliveries is the entire point of
 * the corpus. So the geometry is frozen HERE, once, and the judges never touch a browser.
 *
 * The second reason is blindness, and it is the load-bearing one. A judge that can drive a browser
 * can read the DOM, and a WordPress Orchestrator mockup declares its own axis positions in `:root` comments —
 * which is how proof_axis_signature() reads them. A judge that opens the stylesheet is reading the
 * answer. Separating capture from judging is what lets the judge agents run with Read as their
 * ONLY tool: they receive image paths and have no mechanism to reach the source.
 *
 * No dependencies, by repo convention — there is no package.json here. This drives Chrome over the
 * DevTools Protocol using Node built-ins and the global WebSocket only.
 *
 * The in-app browser pane does not composite a frame for a subagent and the Chrome extension
 * cannot reach file:// — both measured. Headless Chrome is not a fallback here, it is the path.
 */

import { spawn } from 'node:child_process';
import { copyFileSync, existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';
import { pathToFileURL } from 'node:url';

/* Frozen geometry. Changing any of these invalidates every stored corpus shot for comparison
   purposes, because a description written against a 1280 viewport does not describe the same page
   at 1440. If one ever has to change, the corpus is recaptured, not mixed. */
const VIEWPORT = { width: 1280, height: 860 };
const QUALITY = 72;

/* Frame count follows the page, and the ceiling is what stops a long page from costing a fortune.
   A fixed four was the first version and both calibration judges reported the same hole in it:
   the middle of the page unseen, and no footer in frame at all. Four evenly spaced frames over a
   nine-screen page skip five screens — and the footer, which is one of the things a recognition
   judge is most likely to find a tell in. One frame per screen, capped, samples the document
   instead of sampling four points in it. */
const MAX_FRAMES = 9;

/* THREE corpus shots, not two. The hero is always frame 0 — sameness in this framework announces
   itself above the fold. The band comes from a fraction of the way down rather than a fixed pixel
   offset, so it lands on real content whether the page is four screens or twelve. The tail is the
   last frame, and it exists because the first calibration stored hero and band only: the
   recognition judge reported it had no footer in frame at all, while naming footer construction as
   one of the tells it most wanted. A tell the corpus cannot show is a tell nobody checks. */
const BAND_FRACTION = 0.45;

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
	const hit = candidates.find( ( p ) => existsSync( p ) );
	if ( ! hit ) {
		throw new Error(
			'Chrome not found. Set CHROME_PATH to the binary. Tried: ' + candidates.join( ', ' )
		);
	}
	return hit;
}

const sleep = ( ms ) => new Promise( ( r ) => setTimeout( r, ms ) );

/* Chrome writes the chosen port to stderr when asked for port 0, but it also writes a great deal
   else, and on Windows the line can arrive split. It ALSO writes it to `DevToolsActivePort` in the
   profile directory, first line, which is a file and therefore pollable rather than raced — that is
   what waitForPort() below reads. Polling the HTTP endpoint after it is slower by a few hundred
   milliseconds and never races either. */
/* Which port Chrome actually took. It writes it to `DevToolsActivePort` in the profile directory
   as soon as the debugger is listening: first line the port, second the browser target path. The
   file appears late enough that reading it once races, so it is polled — and a profile directory
   is per-process, so nothing else can be writing this one. */
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
	throw new Error( `Chrome did not report a debugger port in ${ portFile } within ${ timeoutMs } ms` );
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
	throw new Error( `Chrome did not open a debugger on port ${ port } within ${ timeoutMs } ms` );
}

/* A minimal CDP client. One socket, one id counter, one pending map. Session-scoped messages carry
   sessionId; browser-scoped ones do not. */
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
				const timer = setTimeout(
					() => fail( new Error( 'timed out waiting for a CDP event' ) ),
					timeoutMs
				);
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

async function main() {
	const target = process.argv[ 2 ];
	const label = arg( '--label' );
	const outDir = arg( '--out' ) ? resolve( arg( '--out' ) ) : null;
	const framesOverride = arg( '--frames' );

	/* --out is REQUIRED, and used to default to process.cwd(). Two captures run from the same
	   directory with the same --label then overwrite each other's frames in silence, and at the
	   volume this is now built for that is not a corner case. A caller that has to name the
	   directory cannot collide with one by accident. */
	if ( ! target || ! label || ! outDir ) {
		console.error( 'usage: node capture.mjs <file-or-url> --label <name> --out <dir> [--frames 4]' );
		process.exit( 2 );
	}

	const url = /^https?:\/\//i.test( target ) ? target : pathToFileURL( resolve( target ) ).href;
	mkdirSync( outDir, { recursive: true } );

	/* The port used to be `9222 + pid % 500`, which narrows collisions rather than removing them:
	   over 500 buckets, fifteen concurrent captures collide about a fifth of the time, and a
	   collision means one capture attaches to ANOTHER capture's browser and photographs its page.
	   Port 0 lets the OS pick a free one; Chrome reports which. */
	const profile = join( tmpdir(), `blind-judges-${ process.pid }` );
	const chrome = spawn(
		chromeBinary(),
		[
			'--headless=new',
			'--disable-gpu',
			'--no-sandbox',
			'--hide-scrollbars',
			'--force-device-scale-factor=1',
			/* Hinting off, so the same page renders the same on any machine. Type weight is one of
			   the eight attributes a judge reports; letting the host decide it would put noise
			   straight into the comparison. */
			'--font-render-hinting=none',
			'--disable-lcd-text',
			'--remote-debugging-port=0',
			`--user-data-dir=${ profile }`,
			'about:blank',
		],
		{ stdio: [ 'ignore', 'ignore', 'ignore' ] }
	);

	const written = [];
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
		await client.send(
			'Emulation.setDeviceMetricsOverride',
			{ ...VIEWPORT, deviceScaleFactor: 1, mobile: false },
			sessionId
		);

		const loaded = client.once( ( m ) => m.method === 'Page.loadEventFired' && m.sessionId === sessionId );
		await client.send( 'Page.navigate', { url }, sessionId );
		await loaded;

		/* Embedded woff2 decode and first paint both land after load. Without this the hero shot
		   catches the fallback face, and "headline voice" — one of the eight attributes — is then
		   a description of Arial. */
		await client.send(
			'Runtime.evaluate',
			{ expression: 'document.fonts ? document.fonts.ready.then(() => 1) : 1', awaitPromise: true },
			sessionId
		);
		await sleep( 400 );

		const { result: heightResult } = await client.send(
			'Runtime.evaluate',
			{ expression: 'Math.max(document.body.scrollHeight, document.documentElement.scrollHeight)' },
			sessionId
		);
		const pageHeight = Math.max( VIEWPORT.height, heightResult.value || VIEWPORT.height );
		const span = Math.max( 0, pageHeight - VIEWPORT.height );
		const frames = framesOverride
			? Math.max( 1, parseInt( framesOverride, 10 ) )
			: Math.min( MAX_FRAMES, Math.max( 2, Math.ceil( pageHeight / VIEWPORT.height ) ) );

		for ( let i = 0; i < frames; i++ ) {
			/* Evenly spaced across the scrollable span rather than a fixed step, so a short page
			   and a long one both yield frames that sample the whole document. */
			const y = frames === 1 ? 0 : Math.round( ( span * i ) / ( frames - 1 ) );
			await client.send( 'Runtime.evaluate', { expression: `window.scrollTo(0, ${ y })` }, sessionId );
			await sleep( 250 );
			const shot = await client.send(
				'Page.captureScreenshot',
				{ format: 'jpeg', quality: QUALITY, captureBeyondViewport: false },
				sessionId
			);
			const file = join( outDir, `${ label }-${ String( i + 1 ).padStart( 2, '0' ) }.jpg` );
			writeFileSync( file, Buffer.from( shot.data, 'base64' ) );
			written.push( file );
		}

		/* The two corpus shots are copies of frames already taken, never extra captures: one more
		   navigation is one more chance for the two to disagree about what the page looked like. */
		const bandIndex = Math.max( 1, Math.round( ( written.length - 1 ) * BAND_FRACTION ) );
		for ( const [ suffix, source ] of [
			[ 'hero', written[ 0 ] ],
			[ 'band', written[ Math.min( bandIndex, written.length - 1 ) ] ],
			[ 'tail', written[ written.length - 1 ] ],
		] ) {
			const dest = join( outDir, `${ label }-${ suffix }.jpg` );
			copyFileSync( source, dest );
			written.push( dest );
		}

		ws.close();
	} finally {
		chrome.kill();
	}

	/* Paths on stdout, one per line — the caller hands exactly these to a judge, and nothing else.
	   Anything this script prints beside them would be something the judge could be told. */
	written.forEach( ( f ) => console.log( f ) );
}

main().catch( ( err ) => {
	console.error( 'capture failed: ' + err.message );
	process.exit( 1 );
} );
