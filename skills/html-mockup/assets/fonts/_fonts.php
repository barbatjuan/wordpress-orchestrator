<?php
/**
 * _fonts.php — the typefaces this skill NAMES, as bytes a browser can actually serve.
 *
 * WHY THIS FILE EXISTS. Every mockup here named real families and shipped none of them. On a
 * machine without them installed — which is every machine that has not deliberately installed
 * six Google families, including the one this was found on — `'Fraunces', Georgia, serif`
 * renders Georgia. So the EDITORIAL anchor was judged as Georgia, DIRECT as Arial Black, and
 * LUXE and INSTITUTIONAL as whatever `system-ui` resolves to. Every visual verdict anyone had
 * reached about this framework was a verdict on the fallback stack. No craft layer rescues the
 * wrong typeface, and no amount of token discipline shows through a substituted face.
 *
 * WHY A `data:` URI IS NOT THE THING THE OLD COMMENT REFUSED. The four mockups used to say the
 * families were "NAMED with honest system fallbacks and never @font-face'd at a URL: the
 * Artifact CSP blocks external requests, and a blocked font is worse than a declared fallback."
 * The premise was right and the conclusion did not follow. A `data:` URI issues no request, so
 * there is nothing for a CSP to block; the reasoning ruled out `url(https://…)` and was read as
 * ruling out `@font-face` itself. `specs/2026-08-14-perceptual-axes-design.md` already
 * prescribes exactly this embedding for fonts. Those comments now say what is true.
 *
 * WHY THE LATIN SUBSET IS ENOUGH. Google serves one woff2 per unicode-range. The `latin` file
 * (`U+0000-00FF` plus punctuation and currency) carries the whole of Spanish — á é í ó ú ñ ü ¿ ¡
 * all live under U+00FF — which is the language every one of these mockups is written in. The
 * `latin-ext`, `vietnamese` and `cyrillic` subsets are not fetched and not embedded.
 *
 * WHY ARCHIVO IS TWO FILES AND NOT ONE. `Archivo Expanded` is not a separate family: it is
 * Archivo at `wdth` 125. The obvious embedding — one full-range `62..125` file under two
 * `@font-face` names — costs 90,104 bytes AND has to be base64'd twice in any file that uses
 * both widths, because a data URI is inline. Google will serve a PINNED width instance instead:
 * `wdth,wght@100,400..700` is 34,928 bytes and `wdth,wght@125,400..700` is 34,708. Two pinned
 * instances are 69,636 bytes — smaller than one shared full-range file, and each is named once.
 * The `font-stretch` descriptor is what makes the expanded one render expanded: an element
 * asking for the default `normal` (100%) gets clamped into the face's declared range, so a face
 * declaring `125%` renders at 125% without every rule having to say so.
 *
 * LICENCE. Every family registered below is SIL Open Font License 1.1, verified per family rather than
 * assumed — see `_fonts.md`, which carries the evidence, the source URL, the sha256 and the
 * copyright line for each. OFL permits redistribution only with the licence text accompanying
 * the fonts, so each family's `OFL.txt` is committed beside its woff2 in this directory. This
 * repository is public under Apache-2.0 and its LICENSE hands every reader the right to
 * redistribute what it contains; that right can only be granted over what we actually hold.
 *
 * The files are Google's own subsets, byte-for-byte as served, and are NOT re-subset here. That
 * is deliberate and it is what keeps Source Sans 3's Reserved Font Name clause satisfied: OFL §3
 * restricts the RFN in MODIFIED versions, and an unmodified redistribution under the original
 * name is what OFL §2 permits outright.
 *
 * Consumers: `../gallery/_build-gallery.php` (the gallery) and `_embed-fonts.php` (the four
 * static mockups). `framework-audit.php`'s RT_MOCKUP_FONT_NOT_EMBEDDED checks the result.
 */

/**
 * The registry. Key is the CSS family name a mockup writes in a `font-family` stack, so the
 * lookup below is the same string the design asks for, never a slug someone has to keep in sync.
 *
 * `weight` is the face's TRUE weight range, not a convenient one. Declaring a range wider than
 * the font holds would silently suppress the browser's synthetic bold and hide the fact that a
 * mockup is asking for a weight nobody drew — which is exactly what Instrument Serif, a
 * single-weight display serif, is here to make visible.
 *
 * ONE FAMILY, SEVERAL FACES. An entry is either one face (`file`, `weight`, `stretch`) or a
 * `faces` list of them, each with its own `style`. The plural form exists because the four shop
 * Plantillas set Bodoni Moda and Newsreader in italic as well as roman, and IBM Plex Mono arrives
 * as two static files. Registering only the roman would have the browser synthesise the italic by
 * slanting it — a different drawing from the one designed — and would leave the second Plex
 * weight with nowhere to go, since the key is the CSS family name and cannot repeat.
 */
function nm_font_registry() {
	return array(
		'Fraunces'          => array( 'file' => 'fraunces-latin.woff2',          'weight' => '400 700', 'stretch' => null ),
		'Instrument Serif'  => array( 'file' => 'instrument-serif-latin.woff2',  'weight' => '400',     'stretch' => null ),
		'Inter Tight'       => array( 'file' => 'inter-tight-latin.woff2',       'weight' => '400 700', 'stretch' => null ),
		'DM Sans'           => array( 'file' => 'dm-sans-latin.woff2',           'weight' => '400 700', 'stretch' => null ),
		'Source Sans 3'     => array( 'file' => 'source-sans-3-latin.woff2',     'weight' => '400 700', 'stretch' => null ),
		'Archivo'           => array( 'file' => 'archivo-latin.woff2',           'weight' => '400 700', 'stretch' => '100%' ),
		'Archivo Expanded'  => array( 'file' => 'archivo-expanded-latin.woff2',  'weight' => '400 700', 'stretch' => '125%' ),
		'Jost'              => array( 'file' => 'jost-latin.woff2',              'weight' => '300 500', 'stretch' => null ),
		'Schibsted Grotesk' => array( 'file' => 'schibsted-grotesk-latin.woff2', 'weight' => '400 500', 'stretch' => null ),
		'Instrument Sans'   => array( 'file' => 'instrument-sans-latin.woff2',   'weight' => '400 600', 'stretch' => null ),
		'Martian Mono'      => array( 'file' => 'martian-mono-latin.woff2',      'weight' => '400 500', 'stretch' => null ),
		'Bodoni Moda'       => array( 'faces' => array(
			array( 'file' => 'bodoni-moda-latin.woff2',        'weight' => '400', 'style' => 'normal', 'stretch' => null ),
			array( 'file' => 'bodoni-moda-italic-latin.woff2', 'weight' => '400', 'style' => 'italic', 'stretch' => null ),
		) ),
		'Newsreader'        => array( 'faces' => array(
			array( 'file' => 'newsreader-latin.woff2',        'weight' => '200 500', 'style' => 'normal', 'stretch' => null ),
			array( 'file' => 'newsreader-italic-latin.woff2', 'weight' => '200 500', 'style' => 'italic', 'stretch' => null ),
		) ),
		'IBM Plex Mono'     => array( 'faces' => array(
			array( 'file' => 'ibm-plex-mono-400-latin.woff2', 'weight' => '400', 'style' => 'normal', 'stretch' => null ),
			array( 'file' => 'ibm-plex-mono-500-latin.woff2', 'weight' => '500', 'style' => 'normal', 'stretch' => null ),
		) ),
	);
}

/**
 * A registry entry as the list of faces it stands for, whichever of the two shapes it was written
 * in. A single-face entry becomes a one-item list with `style` normal, so every caller iterates one
 * shape and the seven original entries did not have to be rewritten.
 */
function nm_font_entry_faces( array $entry ) {
	if ( isset( $entry['faces'] ) ) {
		return $entry['faces'];
	}
	return array(
		array(
			'file'    => $entry['file'],
			'weight'  => $entry['weight'],
			'style'   => 'normal',
			'stretch' => $entry['stretch'],
		),
	);
}

/**
 * The `@font-face` block for exactly the families asked for, in the order asked for.
 *
 * Refuses an unknown family rather than emitting nothing for it: a silent skip here produces a
 * page that names a typeface and serves the fallback, which is the entire defect this file
 * exists to close.
 *
 * Returns '' for an empty list so a caller with no real families adds no empty `<style>` noise.
 */
function nm_font_faces( array $families ) {
	$reg = nm_font_registry();
	$out = array();
	foreach ( $families as $fam ) {
		if ( ! isset( $reg[ $fam ] ) ) {
			fwrite( STDERR, "_fonts.php: FAIL — no font registered for `$fam`\n" );
			exit( 1 );
		}
		foreach ( nm_font_entry_faces( $reg[ $fam ] ) as $f ) {
			$path = __DIR__ . '/' . $f['file'];
			if ( ! is_file( $path ) ) {
				fwrite( STDERR, "_fonts.php: FAIL — `$fam` names {$f['file']}, which is not in " . __DIR__ . "\n" );
				exit( 1 );
			}
			$bytes = file_get_contents( $path );
			if ( "wOF2" !== substr( $bytes, 0, 4 ) ) {
				fwrite( STDERR, "_fonts.php: FAIL — {$f['file']} is not a woff2 (bad magic) — a TTF renames itself to nothing\n" );
				exit( 1 );
			}
			$out[] = "@font-face{font-family:'" . $fam . "';font-style:" . $f['style'] . ';font-weight:' . $f['weight'] . ';'
				. ( null === $f['stretch'] ? '' : 'font-stretch:' . $f['stretch'] . ';' )
				. 'font-display:swap;src:url(data:font/woff2;base64,'
				. base64_encode( $bytes ) . ") format('woff2')}";
		}
	}
	return array() === $out ? '' : implode( "\n", $out );
}

/**
 * The raw and base64 cost of a family list, so a caller's receipt can report what it added
 * instead of asserting that it is small.
 */
function nm_font_bytes( array $families ) {
	$reg = nm_font_registry();
	$raw = 0;
	foreach ( $families as $fam ) {
		if ( isset( $reg[ $fam ] ) ) {
			foreach ( nm_font_entry_faces( $reg[ $fam ] ) as $f ) {
				$raw += filesize( __DIR__ . '/' . $f['file'] );
			}
		}
	}
	return array( 'raw' => $raw, 'b64' => (int) ( ceil( $raw / 3 ) * 4 ) );
}

/**
 * The families a stylesheet actually ASKS FOR, as opposed to the ones it falls back to.
 *
 * THE RULE IS FIRST-IN-STACK, and it is the stack's own semantics rather than a list somebody
 * maintains. `font-family: A, B, C` means "A, and if you cannot serve A, B". A is the design; B
 * and C are the safety net. So A is what has to exist, and an allowlist of "system fonts" — which
 * would have to know that `'Segoe UI'`, `'Times New Roman'`, `'Helvetica Neue'` and `'Arial
 * Black'` are quoted fallbacks and not requests — is never needed.
 *
 * Two sources, because the mockups write the stack once into a token and then reference it:
 * custom properties whose name starts `--font`, and literal `font-family` declarations. A stack
 * that begins with `var(…)` is skipped: it is an alias for a token this same scan already read.
 * Generic keywords (`serif`, `sans-serif`, `system-ui`, …) in first position are skipped too —
 * they name no file and can never be embedded.
 */
function nm_font_families_asked_for( $css ) {
	$generic = array(
		'serif', 'sans-serif', 'monospace', 'cursive', 'fantasy', 'system-ui', 'ui-serif',
		'ui-sans-serif', 'ui-monospace', 'ui-rounded', 'inherit', 'initial', 'unset', 'revert',
		'math', 'emoji', 'fangsong',
	);
	$stacks = array();
	if ( preg_match_all( '/--font[\w-]*\s*:\s*([^;}]+)/i', $css, $m ) ) {
		$stacks = array_merge( $stacks, $m[1] );
	}
	if ( preg_match_all( '/(?<![\w-])font-family\s*:\s*([^;}]+)/i', $css, $m ) ) {
		$stacks = array_merge( $stacks, $m[1] );
	}
	$out = array();
	foreach ( $stacks as $stack ) {
		$first = trim( explode( ',', trim( $stack ) )[0] );
		if ( '' === $first || 0 === stripos( $first, 'var(' ) ) {
			continue;
		}
		$first = trim( $first, "\"'" );
		if ( '' === $first || in_array( strtolower( $first ), $generic, true ) ) {
			continue;
		}
		$out[ $first ] = true;
	}
	$out = array_keys( $out );
	sort( $out );
	return $out;
}
