#!/usr/bin/env node
/**
 * Lighthouse gate for a WordPress Orchestrator build — the METHOD behind the rules that had none.
 *
 * Run:  node lighthouse-audit.mjs <url> [more urls…] [--desktop] [--json=out.json]
 *
 * `wordpress-performance` said "Measure" six times without naming a tool, `qa-review`'s
 * accessibility pass was five items with no method, and "best practices" appeared nowhere. A step
 * with no method is a step nobody runs. This is the method.
 *
 * Mobile by default, because that is where a WordPress/Elementor build actually hurts and where
 * Google grades you.
 *
 * Bands are Lighthouse's own (0-49 red / 50-89 orange / 90-100 green), NOT numbers invented here:
 *   FAIL  < 50
 *   WARN  50-89
 *   PASS  >= 90
 * with ONE deliberate exception. Mobile performance on a real Elementor site rarely reaches 90,
 * so holding it to 90 would print FAIL forever and teach everyone to skip the report — the exact
 * failure this repo keeps fixing. Performance is therefore reported, banded, and never the sole
 * reason to block; what matters is the before/after delta `wordpress-performance` owns.
 * Accessibility, best-practices and SEO have no such excuse: those are ours to get right.
 *
 * MEASURED, not assumed: five runs against the SAME url on the same machine scored 95, 74, 71, 69
 * and 97 — a 28-point swing from lab noise alone, with LCP ranging 2.5-5.0 s. That is the second
 * reason performance does not gate: a blocking threshold on a number that unstable would fail
 * builds at random. The other three categories are near-deterministic because they inspect the
 * DOM rather than time it, which is exactly why those DO block. Comparing performance across runs
 * only means something on the same machine, same network, ideally best-of-three.
 *
 * Requires Chrome and network reach to the URL. The NovaMira sandbox domain is often
 * policy-blocked from the in-app browser; whether headless Chrome reaches it is UNVERIFIED until
 * the first real run — if it cannot, say so, do not report a score of zero as a finding.
 */

import { execSync } from 'node:child_process';
import { readFileSync, rmSync, unlinkSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const argv = process.argv.slice( 2 );
const urls = argv.filter( ( a ) => ! a.startsWith( '--' ) );
const desktop = argv.includes( '--desktop' );
const jsonOut = ( argv.find( ( a ) => a.startsWith( '--json=' ) ) || '' ).slice( 7 );

if ( ! urls.length ) {
	console.error( 'usage: node lighthouse-audit.mjs <url> [more urls…] [--desktop] [--json=out.json]' );
	process.exit( 2 );
}

/**
 * The lighthouse CLI ships as npx.cmd on Windows, and since the CVE-2024-27980 fix Node refuses
 * to spawn a .cmd without a shell. Going through a shell means a URL carrying `&`, `|` or a
 * backtick would be command injection, so the URL is validated instead of escaped: parse it, demand
 * http(s), and reject shell metacharacters outright. Rejecting beats quoting — quoting rules differ
 * between cmd.exe and sh, and a rule that is right on one platform and wrong on the other is worse
 * than no rule.
 */
function safeUrl( u ) {
	let parsed;
	try {
		parsed = new URL( u );
	} catch {
		throw new RefusedUrl( `not a URL: ${ u }` );
	}
	if ( ! [ 'http:', 'https:' ].includes( parsed.protocol ) ) {
		throw new RefusedUrl( `only http/https: ${ u }` );
	}
	if ( /["'`$&|<>^%\\\s]/.test( u ) ) {
		/* Known limit: this also rejects a legitimate two-parameter query (`?a=1&b=2`). WordPress Orchestrator
		   page URLs are clean paths and the cache-bust is a single `?v=`, so it has not bitten;
		   if it ever does, the fix is to resolve lighthouse's entry file and spawn it with
		   process.execPath so no shell is involved at all — not to loosen this test. */
		throw new RefusedUrl( `URL contains a shell metacharacter, refusing to run it: ${ u }` );
	}
	return u;
}

/** Thrown by safeUrl(): we declined to run, which is not the same as the site being down. */
class RefusedUrl extends Error {}

/* The audits worth naming. A category score tells you there is a problem; these tell you which. */
const WATCH = {
	accessibility: [
		'color-contrast', 'image-alt', 'link-name', 'button-name', 'heading-order',
		'html-has-lang', 'meta-viewport', 'target-size', 'label', 'aria-required-attr',
	],
	'best-practices': [ 'is-on-https', 'errors-in-console', 'image-aspect-ratio', 'image-size-responsive' ],
	seo: [ 'document-title', 'meta-description', 'link-text', 'is-crawlable', 'crawlable-anchors', 'http-status-code' ],
	performance: [
		'largest-contentful-paint', 'cumulative-layout-shift', 'total-blocking-time',
		'render-blocking-resources', 'unused-css-rules', 'modern-image-formats',
		'uses-responsive-images', 'offscreen-images',
	],
};

const band = ( s ) => ( s >= 0.9 ? 'PASS' : s >= 0.5 ? 'WARN' : 'FAIL' );
const pct = ( s ) => Math.round( s * 100 );

const results = [];
let blocking = 0;

for ( const [ i, url ] of urls.entries() ) {
	const tmp = join( tmpdir(), 'lh-' + Buffer.from( url ).toString( 'hex' ).slice( 0, 16 ) + '.json' );
	let report;

	try {
		const safe = safeUrl( url );

		/**
		 * Two Windows-specific defensives, both earned by watching this fail:
		 *
		 *  - A unique --user-data-dir per attempt. Lighthouse otherwise makes its own temp Chrome
		 *    profile and intermittently dies with "EPERM, Permission denied:
		 *    …\Temp\lighthouse.<n>" when a previous profile has not been released yet.
		 *  - Three attempts with a backoff. Measured on the dev machine: the same URL alternates
		 *    between a clean run and EPERM with no change in between, and two consecutive
		 *    single-retry runs both failed. It is a race (antivirus or a lingering handle on the
		 *    profile dir), not a verdict about the site — and reporting it as UNREACHABLE would
		 *    be a fabricated finding, the exact thing this file exists to avoid. Retries reduce
		 *    it; they do not eliminate it. If you see UNREACHABLE, re-run before believing it.
		 */
		let lastErr;
		for ( let attempt = 0; attempt < 3; attempt++ ) {
			if ( attempt ) {
				/* Synchronous sleep — the whole script is sequential by design. */
				Atomics.wait( new Int32Array( new SharedArrayBuffer( 4 ) ), 0, 0, 1500 );
			}
			const profile = join( tmpdir(), `lh-profile-${ process.pid }-${ i }-${ attempt }` );
			const cmd = [
				'npx --yes lighthouse',
				safe,
				'--output=json',
				`--output-path="${ tmp }"`,
				'--quiet',
				`--chrome-flags="--headless=new --no-sandbox --disable-gpu --user-data-dir=${ profile }"`,
				desktop ? '--preset=desktop' : '',
			].filter( Boolean ).join( ' ' );
			try {
				execSync( cmd, { stdio: [ 'ignore', 'ignore', 'pipe' ], timeout: 240000 } );
				lastErr = null;
				break;
			} catch ( err ) {
				lastErr = err;
			} finally {
				/* Always, not only on failure: a Chrome profile is tens of MB and a successful run
				   would otherwise leave one behind in %TEMP% every time this gate is used. */
				rmSync( profile, { recursive: true, force: true } );
			}
		}
		if ( lastErr ) {
			throw lastErr;
		}

		report = JSON.parse( readFileSync( tmp, 'utf8' ) );
		unlinkSync( tmp );
	} catch ( e ) {
		/* Neither of these is a score of zero. Reporting one as a finding would be a fabrication. */
		const refused = e instanceof RefusedUrl;
		/* Take the MESSAGE, not the tail. Slicing the last lines of a Node error yields stack
		   frames and hides the one sentence that says what went wrong — which is its own small
		   version of "the warning went somewhere nobody reads". */
		const why = String( e.stderr || e.message )
			.split( '\n' )
			.map( ( l ) => l.trim() )
			.filter( ( l ) => l && ! l.startsWith( 'at ' ) && ! /^Command failed/.test( l ) )
			.slice( 0, 3 )
			.join( '\n  ' );
		console.log( `\n${ refused ? 'REFUSED' : 'UNREACHABLE' }  ${ url }` );
		console.log( '  ' + ( why || 'no message on stderr' ) );
		console.log(
			refused
				? '  Nothing was run. Fix the URL and re-run; do not report this as an audit result.'
				: '  Lighthouse never ran — check the URL is reachable from THIS machine. A sandbox host'
					+ '\n  that headless Chrome cannot reach is a tooling gap, not a site defect.'
		);
		results.push( { url, [ refused ? 'refused' : 'unreachable' ]: true } );
		blocking++;
		continue;
	}

	if ( report.runtimeError ) {
		console.log( `\nUNREACHABLE  ${ url }\n  ${ report.runtimeError.message }` );
		results.push( { url, unreachable: true } );
		blocking++;
		continue;
	}

	const scores = {};
	console.log( `\n${ url }   [${ desktop ? 'desktop' : 'mobile' }]` );
	console.log( '─'.repeat( Math.min( 78, url.length + 14 ) ) );

	for ( const key of [ 'performance', 'accessibility', 'best-practices', 'seo' ] ) {
		const cat = report.categories[ key ];
		if ( ! cat || cat.score === null ) {
			continue;
		}
		const b = band( cat.score );
		scores[ key ] = pct( cat.score );
		/* Performance never blocks on its own — see the header. */
		const shown = key === 'performance' && b === 'FAIL' ? 'WARN' : b;
		if ( shown === 'FAIL' ) {
			blocking++;
		}
		console.log( `  ${ shown.padEnd( 5 ) } ${ key.padEnd( 15 ) } ${ String( pct( cat.score ) ).padStart( 3 ) }`
			+ ( key === 'performance' && b === 'FAIL' ? '   (banded down: mobile Elementor rarely hits 90 — track the delta, not the absolute)' : '' ) );

		const failed = WATCH[ key ]
			.map( ( id ) => report.audits[ id ] )
			.filter( ( a ) => a && a.score !== null && a.score < 0.9 )
			.map( ( a ) => '      · ' + a.title + ( a.displayValue ? ` — ${ a.displayValue }` : '' ) );
		if ( failed.length ) {
			console.log( failed.join( '\n' ) );
		}
	}
	results.push( { url, formFactor: desktop ? 'desktop' : 'mobile', scores } );
}

if ( jsonOut ) {
	writeFileSync( jsonOut, JSON.stringify( results, null, 2 ) );
	console.log( `\njson → ${ jsonOut }` );
}

console.log(
	`\nVEREDICTO ${ blocking ? 'A CORREGIR' : 'LIMPIO' }: ${ urls.length } url(s), ${ blocking } bloqueante(s).`
);
console.log(
	'Accessibility / best-practices / SEO under 50 block. Performance is reported, never the sole\n'
	+ 'blocker. A score is not a visual sign-off — the user still has to look at the page.'
);
process.exit( blocking ? 1 : 0 );
