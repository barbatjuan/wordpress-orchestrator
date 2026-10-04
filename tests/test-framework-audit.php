<?php
/**
 * Regression harness for framework-audit.php itself — the audit's own audit.
 *
 * Run: php tests/test-framework-audit.php     (exit 0 = green)
 *
 * Why a subprocess: framework-audit.php is a top-level script that reads $argv and calls exit()
 * directly. Including it would kill this test process on its first fixture, and the exit code is
 * half of what this file needs to prove. Every fixture is a synthetic tree written to a fresh
 * temp directory — never the real repo — so a broken fixture can never mark real skills.
 */

$root_dir = dirname( __DIR__ );
$audit    = $root_dir . '/skills/framework-audit/assets/framework-audit.php';

/* Ratchet, not an escape hatch: every entry needs a written reason right in this comment, and the
   ceiling below (ok() near the bottom of this file) keeps the list from growing to fit reality
   instead of reality getting a fixture. Empty at B0 — every row type ROW_TYPES declares gets a
   real fixture that emits it; none is waived. */
const COVERAGE_EXEMPT = array();

$pass = 0;
$fail = 0;
/* Accumulates every row-type ID any fixture below actually caused the audit to print (via
   --row-types, injected by fx_run_ok() for every scenario). This is the "observed" half of the
   coverage assertion near the end of this file — see fx_track_ids(). */
$GLOBALS['fx_observed_ids'] = array();
$GLOBALS['fx_diagnostics']  = array();
/* Every root fx_tmp_root() creates is swept here too, so a fatal error or interrupt mid-scenario
   never orphans a temp directory — teardown no longer depends on linear fallthrough alone. */
$GLOBALS['fx_roots'] = array();
register_shutdown_function(
	function () {
		foreach ( $GLOBALS['fx_roots'] as $dir ) {
			fx_rrmdir( $dir );
		}
	}
);

function ok( $cond, $label, $actual = null ) {
	global $pass, $fail;
	if ( $cond ) {
		$pass++;
		echo "  OK   $label\n";
	} else {
		$fail++;
		$suffix = ( null !== $actual ) ? ' -- actual: ' . fx_repr( $actual ) : '';
		echo "  FAIL $label$suffix\n";
	}
}
/* Stringifies an assertion's actual value for a FAIL line, so "exit code 0 on a conforming
   fixture" failing says what the exit code actually WAS, not just that it wasn't 0. */
function fx_repr( $v ) {
	if ( null === $v ) {
		return 'null';
	}
	if ( is_bool( $v ) ) {
		return $v ? 'true' : 'false';
	}
	if ( is_array( $v ) ) {
		return json_encode( $v );
	}
	$s = (string) $v;
	return ( strlen( $s ) > 300 ) ? substr( $s, 0, 300 ) . '…' : $s;
}
function has( $haystack, $needle ) {
	return false !== strpos( $haystack, $needle );
}
/* Every printed line containing every one of $needles -- lets an assertion pin itself to ONE
   bullet among several that share the same fixture's combined output, instead of asking whether
   an ID appears ANYWHERE in it (which a different bullet could satisfy by accident). */
function fx_lines_with( $out, array $needles ) {
	return array_values(
		array_filter(
			explode( "\n", $out ),
			function ( $l ) use ( $needles ) {
				foreach ( $needles as $n ) {
					if ( false === strpos( $l, $n ) ) {
						return false;
					}
				}
				return true;
			}
		)
	);
}
/* Every row-type ID this run of the audit EMITTED, regardless of which scenario caused it.
 *
 * Anchored to the ID COLUMN — line start, then whitespace — and not to the line anywhere. That
 * anchor is the whole correctness of the coverage assertion, and it was learned the hard way: an
 * unanchored scan credited an ID as observed when it merely appeared inside another row's MESSAGE,
 * and RT_ROWTYPE_UNDOCUMENTED's message names the ID it is complaining about. So one fixture that
 * forgot a row-type bullet marked the entire registry covered, and a row type could be deleted
 * outright with the suite still green — the coverage ratchet reporting success over work nothing
 * inspected, which is the exact disease it exists to cure. A message can carry an ID-shaped token
 * (a broken-reference row echoes an audited path verbatim, and a path may be named anything);
 * only the emitter can put one at line start. */
function fx_track_ids( $out ) {
	if ( preg_match_all( '/^(RT_[A-Z0-9_]+)\s/m', $out, $m ) ) {
		foreach ( $m[1] as $id ) {
			$GLOBALS['fx_observed_ids'][ $id ] = true;
		}
	}
}
/* Every PHP diagnostic any fixture's run printed, accumulated the same way IDs are. A scenario
   asserts on the substrings it cares about and ok() prints the output only when it FAILS, so a
   warning riding along in EVERY run is invisible by construction -- the marker grammar's
   affirmative form emitted "Undefined array key 1" 44 times across a suite reporting 0 FAIL. The
   audit writes to STDOUT and the gate reads STDOUT: a diagnostic there is corrupt output. */
function fx_track_diagnostics( $out ) {
	if ( preg_match_all( '/^(?:PHP )?(?:Warning|Notice|Deprecated|Fatal error|Parse error):.*/m', $out, $m ) ) {
		foreach ( $m[0] as $d ) {
			$GLOBALS['fx_diagnostics'][ preg_replace( '/ in .*$/', '', trim( $d ) ) ] = true;
		}
	}
}
/* The LEVEL of the single row a set of needles pins, from the --row-types layout's second column.
   Presence says a check FIRED; it says nothing about whether it still BLOCKS. The audit exits 1 on
   FAIL alone, so one 'FAIL' -> 'WARN' token turns a gate into a comment, and seven of this slice's
   ten blocking checks were asserted by presence only. A level is behaviour. Assert it. */
function fx_row_level( $out, array $needles ) {
	$lines = fx_lines_with( $out, $needles );
	if ( 1 !== count( $lines ) ) {
		return '<' . count( $lines ) . ' rows matched, expected exactly 1>';
	}
	return preg_match( '/^RT_[A-Z0-9_]+\s+(FAIL|WARN|JUDGE)\s/', $lines[0], $m ) ? $m[1] : '<no level column>';
}

/* -------------------------------------------------------------- fixture plumbing */

/* A temp root with a space in its name — proves the subprocess argument quoting survives the
   one Windows/escapeshellarg edge case that silently breaks naive `exec()` calls. */
function fx_tmp_root() {
	$dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/nm audit fx ' . uniqid( '', true );
	if ( ! mkdir( $dir, 0777, true ) && ! is_dir( $dir ) ) {
		fwrite( STDERR, "fx_tmp_root(): mkdir() failed for \"$dir\" -- cannot create a fixture root, aborting.\n" );
		exit( 1 );
	}
	$GLOBALS['fx_roots'][] = $dir;
	return $dir;
}
function fx( $root, $relpath, $content ) {
	$path = $root . '/' . $relpath;
	$dir  = dirname( $path );
	if ( ! is_dir( $dir ) ) {
		mkdir( $dir, 0777, true );
	}
	file_put_contents( $path, $content );
}
function fx_rrmdir( $dir ) {
	/* is_link() BEFORE is_dir(): both is_dir() and scandir() follow symlinks, so recursing on
	   is_dir() alone would walk through a symlink into a directory this fixture never created —
	   a predictable-name-under-sys_get_temp_dir() TOCTOU shape. A symlink is unlinked, never
	   traversed. */
	if ( is_link( $dir ) ) {
		unlink( $dir );
		return;
	}
	if ( ! is_dir( $dir ) ) {
		return;
	}
	foreach ( scandir( $dir ) as $f ) {
		if ( '.' === $f || '..' === $f ) {
			continue;
		}
		$p = $dir . '/' . $f;
		if ( is_link( $p ) ) {
			unlink( $p );
		} elseif ( is_dir( $p ) ) {
			fx_rrmdir( $p );
		} else {
			unlink( $p );
		}
	}
	rmdir( $dir );
}

/* Every declared row-type ID, one bullet per line — read by fx_base() (and any fixture that
   writes its own CONTRIBUTING.md) so a fixture's CONTRIBUTING.md always documents every row type
   the real one does. Pulled from the audit's OWN --emit-row-types output ($declared_row_types,
   fetched once before any fixture runs), never hand-copied, so this list cannot drift from
   ROW_TYPES the way two independent implementations of one rule always eventually do. */
function fx_row_type_doc() {
	global $declared_row_types;
	$lines = '';
	foreach ( $declared_row_types as $rt_id ) {
		$lines .= "- $rt_id\n";
	}
	return "## Row types\n" . $lines;
}

/* A minimal write-capable SKILL.md: frontmatter + a passing build gate + the given "## Hard
   Rules" body, plus an optional $extra section (e.g. a custom "## Execution Steps"). $skill MUST
   be one of framework-audit.php's own hardcoded $WRITE_CAPABLE names -- that list is not
   fixture-configurable -- or none of the marker-grammar rows below this comment ever fire. */
function fx_wc_skill( $root, $skill, $hard_rules_body, $extra = '' ) {
	fx(
		$root,
		"skills/$skill/SKILL.md",
		"---\nname: $skill\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\n"
		. "Build gate: requires explicit **yes** before writing.\n\n"
		. "## Hard Rules\n" . $hard_rules_body . "\n" . $extra
	);
}

/**
 * One Plantilla MAQUETA, reduced to what the re-pointed RT_MOCKUP_* rows read: a `:root` block, the
 * stylesheet around it and a heading. Everything a real maqueta also has — fourteen pages, the nav
 * script, the placeholder recipe — is deliberately absent, so a fixture that fails fails on the one
 * thing its scenario names and on nothing else.
 *
 * $fonts drives RT_MOCKUP_FONT_NOT_EMBEDDED and DEFAULTS TO EMPTY — no `--font-primary`, no
 * `font-family`, no `@font-face` — so every other scenario stays exactly as green as it was. That
 * default is load-bearing rather than lazy: the row fires on a family ASKED FOR and not served, so a
 * fixture that asks for nothing can never emit it.
 *
 *   'stack'  — the `--font-primary` value written into `:root`
 *   'rule'   — a `font-family` written into a `@media` block, OUTSIDE `:root`, which is how the
 *              whole-file half of the scan gets a fixture; a `:root`-only reader misses it
 *   'faces'  — family => src, emitted as `@font-face` ahead of `:root`, exactly as production does
 *
 * $band drives RT_MOCKUP_BLEED_FIXED_BAND and RT_MOCKUP_BLEED_NOT_MEDIA and DEFAULTS TO NEITHER HALF:
 * no `--content-width` at all, and no `full-end` anywhere. The first row needs BOTH arms to fire and
 * each key writes one of them, so a scenario can pin the band WITHOUT a bleed (correct: a centred
 * layout does exactly that, and the row must stay silent) or bleed with a fluid band (also correct).
 *
 *   'width'   — the `--content-width` written into `:root`
 *   'bleed'   — emit one rule that sends `sel` to `full-end`
 *   'sel'     — the selector that bleeds; DEFAULTS TO `.hero .media`, which is media and therefore silent
 *   'comment' — put prose that names `full-end` (and carries two commas) immediately above the rule,
 *               which must NOT count: stripping comments AFTER splitting the selector list on `,`
 *               reported two fragments of English as offending selectors
 */
function fx_mockup( array $fonts = array(), array $band = array() ) {
	/* The faces go ahead of `:root`, where production puts them. `@font-face{…}` carries braces but
	   no `:root`, so the content-width scan's `/:root\s*\{(.*?)\}/s` still lands on the real block. */
	$face_block = '';
	foreach ( ( isset( $fonts['faces'] ) ? $fonts['faces'] : array() ) as $face_fam => $face_src ) {
		$face_block .= "  @font-face{font-family:'" . $face_fam . "';font-style:normal;font-weight:400 700;"
			. 'src:url(' . $face_src . ") format('woff2')}\n";
	}
	$font_decl = isset( $fonts['stack'] ) ? '    --font-primary: ' . $fonts['stack'] . ";\n" : '';
	$font_rule = isset( $fonts['rule'] )
		? "  @media(min-width:768px){ .hero h1{font-family:" . $fonts['rule'] . "} }\n"
		: '';
	$band_decl = isset( $band['width'] ) ? '    --content-width: ' . $band['width'] . ";\n" : '';
	$bleed_sel = isset( $band['sel'] ) ? $band['sel'] : '.hero .media';
	/* THE COMMENT HAS TO SIT IMMEDIATELY ABOVE THE BLEEDING RULE OR IT TESTS NOTHING: the extraction
	   regex requires `full-start|full-end` INSIDE the braces. Caught by mutating the strip out of
	   framework-audit.php and watching the suite stay green. */
	$bleed_note = empty( $band['comment'] ) ? ''
		: "  /* the one bleed per section, always the same edge, all the way down to full-end */\n";
	$band_rule = empty( $band['bleed'] ) ? '' : $bleed_note . "  {$bleed_sel}{grid-column:c 8/full-end}\n";
	return "<!-- maqueta fixture -->\n<style>\n" . $face_block . "  :root{\n    --c-bg:#F2F5F8; --c-bg-alt:#E8EDF3;\n" . $font_decl . $band_decl . "  }\n"
		. $font_rule . $band_rule
		. "</style>\n<h1>Maqueta</h1>\n";
}

/* The ONE Plantilla fixture the re-pointed maqueta rules read: `plantillas/fx/maqueta/index.html`
   and, beside its folder, `manifiesto-imagenes.md`. Same shape as a real Plantilla, minus everything
   those rules never open. */
const FX_MAQUETA  = 'skills/web-templates/references/plantillas/fx/maqueta/index.html';
const FX_MANIFEST = 'skills/web-templates/references/plantillas/fx/manifiesto-imagenes.md';
$FX_MAQ_IMGS      = array( 'hero-taller', 'card-veta' );

/** Writes the maqueta, and its manifest when one is given (null writes none). */
function fx_maqueta( $root, $html, $manifest = null ) {
	fx( $root, FX_MAQUETA, $html );
	if ( null !== $manifest ) {
		fx( $root, FX_MANIFEST, $manifest );
	}
}

/** A maqueta that renders each slug the way a real one does: `src="../img/<slug>.webp"`. */
function fx_maqueta_html( array $imgs ) {
	$out = fx_mockup();
	foreach ( $imgs as $slug ) {
		$out .= '<img src="../img/' . $slug . '.webp" alt="fixture">' . "\n";
	}
	return $out;
}

/**
 * One `manifiesto-imagenes.md`, reduced to what RT_GALLERY_NO_MANIFEST reads.
 *
 * $rows maps slug => licence cell; the empty-string KEY writes a row with an empty Slug cell, and an
 * empty VALUE writes a row with an empty Licencia cell. $licence_col false drops the column entirely,
 * which is a different finding and gets its own sentence in the message. The table above the set
 * carries `Slugs` in its header on purpose: the check selects a table by a header cell reading
 * exactly `Slug`, so this is what proves the plural does not match.
 */
function fx_manifest( array $rows, $licence_col = true ) {
	$out  = "# Manifiesto de imagenes fixture\n\n| Registro | Slugs | Que dice |\n|---|---|---|\n";
	$out .= '| Taller | ' . implode( ' ', array_keys( $rows ) ) . " | fixture |\n\n";
	$head = $licence_col ? array( 'Slug', 'Rol', 'Licencia', '`alt`' ) : array( 'Slug', 'Rol', '`alt`' );
	$out .= '| ' . implode( ' | ', $head ) . " |\n|" . str_repeat( '---|', count( $head ) ) . "\n";
	foreach ( $rows as $slug => $lic ) {
		$cells = $licence_col
			? array( ( '' === $slug ) ? '' : '`' . $slug . '`', 'foto 4:3', $lic, 'alt fixture' )
			: array( ( '' === $slug ) ? '' : '`' . $slug . '`', 'foto 4:3', 'alt fixture' );
		/* An EMPTY cell is written as a single space: the empty-Slug scenario pins the reported line
		   by searching the fixture for `| | foto 4:3`. */
		$out .= '|' . implode(
			'|',
			array_map(
				function ( $c ) {
					return ( '' === $c ) ? ' ' : ' ' . $c . ' ';
				},
				$cells
			)
		) . "|\n";
	}
	return $out;
}

/**
 * One BUILDER asset — the shape RT_BUILDER_NO_TOKENS / RT_BUILDER_HARDCODED_TOKEN read — reduced
 * to the three things the region boundary depends on: an es_tokens() block, a visual helper below
 * it, and the "end of the visual layer" marker with save-pipeline code underneath.
 *
 * The token block is deliberately STUFFED with literals — hex in both lengths, an `rgba()`, a
 * `cubic-bezier()` and a font family. Every one of them is correct THERE: it is the declaration
 * site. That is the whole point of the fixture. A check anchored on the OPENING of es_tokens()
 * instead of its closing brace reports all seven against a perfectly correct file and is useless
 * — es-builder.php really does carry 21 of them — and the conforming scenario below is what turns
 * red the moment someone moves that anchor.
 *
 * $region and $below_end inject raw PHP lines on either side of the END marker, which is how the
 * "a literal here FAILS / the same literal there does not" pair gets written from one template.
 * $tokens / $end_marker / $end_first omit or misplace the boundary pieces.
 */
function fx_builder( array $opts = array() ) {
	$o = array_merge(
		array(
			'tokens'     => true,
			'end_marker' => true,
			'end_first'  => false,
			'region'     => '',
			'below_end'  => '',
			/* RT_FONT_NO_SERVING_PATH's three pieces, each omittable on its own so a scenario can
			   fail exactly one cause and read exactly one message. 'font_family' is the token-block
			   value, so a web-safe face can be declared and proven NOT to be a finding. */
			'font_family' => 'Manrope',
			'font_decl'   => true,
			'font_call'   => true,
		),
		$opts
	);
	$token_block = "/* ------------------------------------------------------ design tokens */\n"
		. "function es_tokens( array \$override = array() ) {\n"
		. "\treturn array_merge(\n"
		. "\t\tarray(\n"
		. "\t\t\t'bg'        => '#FFFFFF',\n"
		. "\t\t\t'text'      => '#15181A',\n"
		. "\t\t\t'accent'    => '#0FA968',\n"
		. "\t\t\t'on_accent' => '#fff',\n"
		. "\t\t\t'glow'      => 'rgba(15,169,104,0.55)',\n"
		. "\t\t\t'ease'      => 'cubic-bezier(.22,1,.36,1)',\n"
		. "\t\t\t'font_body' => '" . $o['font_family'] . "',\n"
		. "\t\t),\n"
		. "\t\t\$override\n"
		. "\t);\n"
		. "}\n";
	$end_block = "/* ------------------------------------------ end of the visual layer\n"
		. "   Everything below is the save pipeline. No styling value belongs here. */\n";

	$src = "<?php\n/* fixture builder asset */\n";
	if ( $o['end_first'] && $o['end_marker'] ) {
		$src .= $end_block;
	}
	if ( $o['tokens'] ) {
		$src .= $token_block;
	}
	$src .= "function es_t( \$key ) {\n\t\$t = es_tokens();\n\treturn isset( \$t[ \$key ] ) ? \$t[ \$key ] : '';\n}\n"
		. "function es_fixture_card() {\n"
		. "\t\$s = array(\n"
		. "\t\t'typography_font_family' => es_t( 'font_body' ),\n"
		. "\t\t'border_color'           => es_t( 'text' ),\n"
		. "\t);\n"
		. $o['region']
		. "\treturn \$s;\n}\n";
	if ( $o['end_marker'] && ! $o['end_first'] ) {
		$src .= $end_block;
	}
	/* The save-pipeline half. Note how the REAL file builds a post-id warning: '#' concatenated
	   with a variable, never a literal — so $below_end is what writes the literal form. */
	$src .= "function es_fixture_slug( \$id ) {\n"
		. "\t\$msg = 'la pagina #' . \$id . ' no existe';\n"
		. $o['below_end']
		. "\treturn \$msg;\n}\n";
	/* BELOW the end marker on purpose: the serving check is save-pipeline code, not visual code, and
	   the real file puts it there too. Declared inside the region it would be scanned for literals
	   by a row that has nothing to do with it. */
	if ( $o['font_decl'] ) {
		$src .= "function es_font_serving_check() {\n\treturn 'sin-wordpress';\n}\n";
	}
	$src .= "function es_fixture_build() {\n\tes_fixture_card();\n\tes_fixture_slug( 1 );\n"
		. ( $o['font_call'] ? "\tes_font_serving_check();\n" : '' )
		. "}\n";
	return $src;
}
/**
 * One SIBLING builder asset — SHAPE B, the second honest way to have a token layer.
 *
 * es-theme-parts.example.php, es-product-single.example.php and es-shop-template.example.php
 * cannot declare es_tokens(): they require es-builder.php, which already declares it, and PHP
 * fatals on a duplicate function. A second copy of the block would be the drift the whole layer
 * exists to end. So they DEPEND on it and mark their region with an explicit
 * "start of the visual layer" comment instead — and they need one, because in those files the
 * save pipeline sits ABOVE the visual code, so a region anchored on the top of the file would
 * scan it and a hex regex cannot tell a post id from a colour.
 *
 * The conforming region carries an es_rgba( es_t(...), '0.42' ) call on purpose. That is the ONE
 * shape a derived veil can take, the three real files make thirteen such calls, and a bare
 * `rgba|cubic-bezier` alternation matches inside the helper's own NAME — so without a lookbehind
 * this check reports the correct shape as the literal it replaced.
 *
 * Written into elementor-core/assets/es-builder.php like fx_builder(), because the check reads
 * CONTENT and not filenames, and that path already carries the SKILL.md scaffolding a
 * write-capable skill needs.
 */
function fx_builder_hermano( array $opts = array() ) {
	$o = array_merge(
		array(
			'depende'    => true,
			'inicio'     => true,
			'end_marker' => true,
			'prosa_fin'  => false,
			'region'     => '',
		),
		$opts
	);
	$src = "<?php\n/* fixture sibling asset */\n";
	if ( $o['depende'] ) {
		/* The dependency is named the way the real files name it: inside an array in a guard
		   loop, NOT on the require line, so a check that pattern-matches `require .* es-builder`
		   never sees it. */
		$src .= "foreach ( array( 'es-builder.php' ) as \$dep ) {\n"
			. "\trequire_once WP_CONTENT_DIR . '/novamira-sandbox/' . \$dep;\n}\n";
	}
	if ( $o['inicio'] ) {
		$src .= "/* ------------------------------------------ start of the visual layer\n";
		if ( $o['prosa_fin'] ) {
			$src .= "   Everything from here to the \"end of the visual layer\" marker reads es_t().\n";
		}
		$src .= "   Nothing below this line types a colour. */\n";
	}
	$src .= "function es_fixture_card() {\n"
		. "\t\$s = array(\n"
		. "\t\t'typography_font_family' => es_t( 'font_body' ),\n"
		. "\t\t'border_color'           => es_t( 'text' ),\n"
		. "\t\t'veil'                   => es_rgba( es_t( 'on_inverse' ), '0.42' ),\n"
		. "\t);\n"
		. $o['region']
		. "\treturn \$s;\n}\n";
	if ( $o['end_marker'] ) {
		$src .= "/* ------------------------------------------ end of the visual layer\n"
			. "   Everything below is the save pipeline. No styling value belongs here. */\n";
	}
	$src .= "function es_fixture_slug( \$id ) {\n"
		. "\t\$msg = 'la pagina #' . \$id . ' no existe';\n"
		. "\treturn \$msg;\n}\n"
		. "function es_fixture_build() {\n\tes_fixture_card();\n\tes_fixture_slug( 1 );\n}\n";
	return $src;
}

/* The 1-based line a needle first appears on, so a "names the line" assertion pins the REAL line
   number instead of one hand-counted from a template that will be edited again. */
function fx_line_of( $content, $needle ) {
	foreach ( explode( "\n", $content ) as $i => $l ) {
		if ( false !== strpos( $l, $needle ) ) {
			return $i + 1;
		}
	}
	return -1;
}
/* elementor-core is in framework-audit.php's own $WRITE_CAPABLE, so it needs the build gate and
   the Hard Rules a write-capable skill needs, or every builder scenario carries two FAILs that
   have nothing to do with the token layer. The $extra names the asset and its unrouted entry
   point so the scenario is not read through a fog of RT_ORPHAN_FILE / RT_HELPER_UNROUTABLE. */
/* $documenta writes the third piece RT_FONT_NO_SERVING_PATH asks for: a .md in the tree that names
   the serving check, so an operator who sees its warning has somewhere to go. It is a parameter and
   not always-on because "the check exists, is wired, and is documented nowhere" is its own cause
   with its own message, and a fixture that cannot express it cannot prove that arm fires. */
function fx_builder_skill( $root, $content, $documenta = true ) {
	fx_wc_skill(
		$root,
		'elementor-core',
		"- Every colour, family and shadow lives in es_tokens(); nothing below it types one.\n",
		"\nThe token layer and the demo build live in `assets/es-builder.php` — see `es_fixture_build()`.\n"
			. ( $documenta ? "Serving the families is a human's job: see es_font_serving_check().\n" : '' )
	);
	fx( $root, 'skills/elementor-core/assets/es-builder.php', $content );
}

/* A conforming house-rules.md, and it is a helper rather than a literal because RT_QA_NO_AXIS_CHECK
   raised the bar for "conforming": the gate must NAME every axis declaration axis_declarations()
   lists plus a composition blueprint, so a bare "No rows." file — what fx_base() used to write —
   now carries a FAIL that has nothing to do with whatever a scenario is testing. $extra_rows is
   appended verbatim so a scenario can add the one malformed row it actually cares about without
   also tripping the axis row and having to read two failures as one. */
function fx_house_rules( $extra_rows = '' ) {
	return "# House Rules\n\n"
		. '| 1 | The build stands at the approved tokens | Compare the approved maqueta `:root` against `es_tokens()`.'
		. " World: W1 or W3 | **auto** |\n"
		. $extra_rows;
}

/* A conforming skeleton every fixture starts from: one skill (qa-review, which the audit checks
   by a HARDCODED path regardless of whether the skill exists) plus its house-rules file, an
   offline test file, and CONTRIBUTING.md documenting every row type. Without this every fixture
   would carry FAILs that have nothing to do with what it actually tests.
   The two catalog-era skills the audit no longer checks (ux-design-system, web-templates,
   html-mockup) stay as bare SKILL.md files: scenarios write references/ and assets/ files under
   those names, and a references/*.md alone creates a skills/<name>/ directory the skill loop then
   walks and flags RT_NO_SKILL_MD for, since that loop is glob($root.'/skills/*') filtered by
   is_dir(), not by "has a SKILL.md" — any file dropped under a new skill name silently enrols
   that name as a skill. */
function fx_base( $root ) {
	fx( $root, 'CONTRIBUTING.md', "# Contributing\nFixture root for framework-audit tests.\n\n" . fx_row_type_doc() );
	fx( $root, 'tests/dummy.php', "<?php\n// fixture placeholder, never executed by the audit\n" );
	fx(
		$root,
		'skills/qa-review/SKILL.md',
		"---\n" .
		"name: qa-review\n" .
		"description: \"Trigger: fixture skill.\"\n" .
		"license: MIT\n" .
		"metadata:\n" .
		"  author: fixture\n" .
		"  version: \"1.0\"\n" .
		"---\n\n" .
		"# QA Review Fixture\n\nSee `references/house-rules.md`.\n"
	);
	fx( $root, 'skills/qa-review/references/house-rules.md', fx_house_rules() );
	foreach ( array( 'ux-design-system', 'web-templates', 'html-mockup' ) as $fx_bare_skill ) {
		fx(
			$root,
			'skills/' . $fx_bare_skill . '/SKILL.md',
			"---\n" .
			'name: ' . $fx_bare_skill . "\n" .
			"description: \"Trigger: fixture skill.\"\n" .
			"license: MIT\n" .
			"metadata:\n" .
			"  author: fixture\n" .
			"  version: \"1.0\"\n" .
			"---\n\n" .
			"Fixture skill body.\n"
		);
	}
}

/* Runs the real audit as a subprocess against $root. Returns [exit_code, combined_output,
   launched]. $launched distinguishes two different bugs that used to collapse into the same
   fx_counts() sentinel: a SITE defect (the audit ran and printed something unparseable) versus a
   TOOLING gap (the audit never ran at all — no CLI PHP binary under a non-CLI SAPI, exec()
   disabled, or the shell failed to launch the command). $php_binary is injectable so this
   distinction is itself testable — see the launch-failure scenario below. */
function fx_run( $audit, $root, array $extra_args = array(), $php_binary = null ) {
	$php_binary = ( null === $php_binary ) ? PHP_BINARY : $php_binary;
	if ( '' === $php_binary ) {
		return array( null, 'launch failure: PHP_BINARY is empty -- no CLI PHP binary is available (non-CLI SAPI?)', false );
	}
	if ( ! function_exists( 'exec' ) ) {
		return array( null, 'launch failure: exec() is unavailable -- disabled via disable_functions', false );
	}
	$parts = array( escapeshellarg( $php_binary ), escapeshellarg( $audit ), '--root=' . escapeshellarg( $root ) );
	foreach ( $extra_args as $arg ) {
		$parts[] = escapeshellarg( $arg );
	}
	$last = exec( implode( ' ', $parts ) . ' 2>&1', $out, $code );
	if ( false === $last && array() === $out ) {
		return array( null, 'launch failure: exec() returned false -- the shell never ran the command', false );
	}
	return array( $code, implode( "\n", $out ), true );
}
/* Every scenario except the launch-failure one below calls this instead of fx_run() directly, so
   a silent tooling gap can never be misread as "the audit ran and disagreed". Every call also
   requests --row-types (unless a caller already did), and every launch feeds fx_track_ids() — so
   EVERY existing and new scenario's run counts toward the coverage assertion for free, with no
   per-scenario opt-in to forget. The human-readable message text is untouched either way; the ID
   column is additive, so every has($out, '...') assertion written before this slice keeps working
   unmodified — proven by the full suite staying green after this change landed. */
function fx_run_ok( $audit, $root, array $extra_args = array() ) {
	if ( ! in_array( '--row-types', $extra_args, true ) ) {
		$extra_args[] = '--row-types';
	}
	list( $code, $out, $launched ) = fx_run( $audit, $root, $extra_args );
	ok( $launched, 'the audit subprocess actually launched', $launched ? 'true' : $out );
	if ( $launched ) {
		fx_track_ids( $out );
		fx_track_diagnostics( $out );
	}
	return array( $code, $out );
}
/* Parses the summary line printed by the printf() in framework-audit.php's report section (see
   the matching comment there) -- the two sides of this contract live in different files and must
   be kept in sync by hand. Callers only reach this after fx_run_ok()/fx_run() has already
   confirmed $launched === true, so the (-1,-1,-1) sentinel here means only "ran, but printed
   something this regex cannot parse" -- never "never ran" (see fx_run() above). */
function fx_counts( $out ) {
	if ( preg_match( '/(\d+) FAIL \/ (\d+) WARN \/ (\d+) JUDGE/', $out, $m ) ) {
		return array( (int) $m[1], (int) $m[2], (int) $m[3] );
	}
	return array( -1, -1, -1 );
}

/* --emit-row-types is static introspection of the script — it needs no --root and prints exit 0
   before the tree-walk even starts, so it is fetched once here rather than per-scenario. This IS
   the "declared" half of the coverage assertion: the registry the audit itself carries, not a
   second hand-typed copy in this file that could silently drift from it. */
list( , $declared_out ) = fx_run( $audit, '', array( '--emit-row-types' ) );
preg_match_all( '/\bRT_[A-Z0-9_]+\b/', $declared_out, $declared_m );
$declared_row_types = array_values( array_unique( $declared_m[0] ) );
sort( $declared_row_types );

/* -------------------------------------------------------------- scenarios */

echo "--- --row-types and the unflagged default report the identical summary line ---\n";
/* The per-row format may grow an ID column under the flag; the count line every other assertion
   in this file (and every human running the audit unflagged) reads must never move because of it. */
$r0 = fx_tmp_root();
fx_base( $r0 );
list( , $out0_flagged )   = fx_run_ok( $audit, $r0, array( '--row-types' ) );
list( , $out0_unflagged ) = fx_run( $audit, $r0, array() );
preg_match( '/\d+ FAIL \/ \d+ WARN \/ \d+ JUDGE across \d+ skills \+ \d+ agent\(s\)/', $out0_flagged, $sm_flagged );
preg_match( '/\d+ FAIL \/ \d+ WARN \/ \d+ JUDGE across \d+ skills \+ \d+ agent\(s\)/', $out0_unflagged, $sm_unflagged );
ok(
	isset( $sm_flagged[0] ) && isset( $sm_unflagged[0] ) && $sm_flagged[0] === $sm_unflagged[0],
	'--row-types and the unflagged run produce the identical counts line',
	array( 'flagged' => isset( $sm_flagged[0] ) ? $sm_flagged[0] : null, 'unflagged' => isset( $sm_unflagged[0] ) ? $sm_unflagged[0] : null )
);
fx_rrmdir( $r0 );

echo "--- positive control: a fully conforming fixture is 0 FAIL ---\n";
$r1 = fx_tmp_root();
ok( false !== strpos( $r1, ' ' ), 'the fixture temp path itself contains a space', $r1 );
fx_base( $r1 );
list( $code1, $out1 ) = fx_run_ok( $audit, $r1 );
list( $f1, , ) = fx_counts( $out1 );
ok( 0 === $code1, 'exit code 0 on a conforming fixture', $code1 );
ok( 0 === $f1, 'zero FAIL rows reported', $f1 );
fx_rrmdir( $r1 );

echo "--- routing to a missing skill FAILs (was a silent tautology) ---\n";
$r2 = fx_tmp_root();
fx_base( $r2 );
fx(
	$r2,
	'agents/orchestrator.md',
	/* The House rule bullet is not decoration: an agent stating no House rules is now its own FAIL,
	   and this scenario asserts on the FAIL COUNT, so the fixture has to conform on every axis but
	   the one it tests. */
	"---\nname: orchestrator\n---\n\n## House rules\n- Routes to `phantom-skill` for commerce.\n  (no verifier: a fixture rule, here only so the agent states something.)\n"
);
list( $code2, $out2 ) = fx_run_ok( $audit, $r2 );
list( $f2, , ) = fx_counts( $out2 );
ok( 1 === $code2, 'exit code 1 when a routed skill is missing', $code2 );
ok( 1 === $f2, 'exactly one FAIL row reported', $f2 );
ok( has( $out2, 'routes to skill "phantom-skill" which is missing' ), 'the FAIL names the missing skill', $out2 );
/* Only an agent carrying a ROUTING MAP owes every skill a mention. This fixture has none, so the
   "unroutable" rows must stay silent: the loop runs over every agents/*.md, and while there was
   exactly one agent it silently equated "an agent" with "the router". A second agent that routes
   to nothing by design (a copywriter) collected one WARN per skill saying it was unroutable
   through the orchestrator, which it is not supposed to be. */
ok( ! has( $out2, 'unroutable through this agent' ), 'an agent with NO routing map is never told it fails to mention a skill', $out2 );
fx_rrmdir( $r2 );

echo "--- write-capability is detected from assets/*.php content, not SKILL.md prose ---\n";
$r3 = fx_tmp_root();
fx_base( $r3 );
fx(
	$r3,
	'skills/sample-writer/SKILL.md',
	"---\nname: sample-writer\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\n" .
	"Build gate: requires explicit **yes** before writing (fixture claims a gate it should not have).\n"
);
fx(
	$r3,
	'skills/sample-writer/assets/tool.php',
	"<?php\nfunction do_write( \$id ) {\n\tupdate_post_meta( \$id, 'x', 'y' );\n}\n"
);
fx(
	$r3,
	'skills/sample-mentioner/SKILL.md',
	"---\nname: sample-mentioner\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\nFixture skill, no writes.\n"
);
fx(
	$r3,
	'skills/sample-mentioner/assets/note.php',
	"<?php\n/**\n * Historically this asset called update_post_meta( \$id, 'x', 'y' ) directly; now it delegates.\n */\nfunction noop() { return true; }\n"
);
list( $code3, $out3 ) = fx_run_ok( $audit, $r3 );
ok( has( $out3, 'sample-writer' ) && has( $out3, 'writes to WordPress (tool.php:3)' ), 'unlisted skill with a real assets/*.php write call is flagged, with file:line' );
ok( has( $out3, 'not in the write-capable list' ), 'the FAIL explains the skill is missing from $WRITE_CAPABLE' );
$mentioner_lines = array_filter(
	explode( "\n", $out3 ),
	function ( $l ) {
		return false !== strpos( $l, 'sample-mentioner' );
	}
);
$mentioner_flagged = false;
foreach ( $mentioner_lines as $l ) {
	if ( false !== strpos( $l, 'writes to WordPress' ) ) {
		$mentioner_flagged = true;
	}
}
ok( ! $mentioner_flagged, 'a comment-only mention of the same token produces no write-capability row' );
fx_rrmdir( $r3 );

echo "--- the audit does not self-flag through its own detection-token literal ---\n";
/* Pairs with the positive case above: together they pin the call-shape rule (\s*\() that stands
   between this file's own detection-regex literal and self-flagging when the assets glob reaches
   skills/framework-audit/assets/framework-audit.php in a real repo scan. */
$r4 = fx_tmp_root();
fx_base( $r4 );
fx(
	$r4,
	'skills/sample-selfcheck/SKILL.md',
	"---\nname: sample-selfcheck\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\nFixture skill, no writes.\n"
);
fx(
	$r4,
	'skills/sample-selfcheck/assets/detector.php',
	<<<EOT
<?php
/* Same token list as framework-audit.php's own literal: "|"-separated, never followed by "(". */
\$pattern = '/\b(update_post_meta|wp_insert_post|wp_update_post|update_option|es_save_page|es_save_theme_part)\s*\(/';
function noop() { return \$pattern; }
EOT
);
list( $code4, $out4 ) = fx_run_ok( $audit, $r4 );
$selfcheck_rows = array_filter(
	explode( "\n", $out4 ),
	function ( $l ) {
		return has( $l, 'sample-selfcheck' ) && has( $l, 'writes to WordPress' );
	}
);
ok( 0 === count( $selfcheck_rows ), 'a token list written the same way the audit writes its own regex literal produces no write-capability row', $out4 );
fx_rrmdir( $r4 );

echo "--- write-capability from SKILL.md PROSE alone is never flagged ---\n";
/* The comment-only-mention assertion above pins the call-shape rule INSIDE assets/*.php; it never
   tested this other half of the same claim. A regression that resurrects a
   strpos($src, 'update_post_meta') check on SKILL.md text -- the exact bug this slice fixed --
   would pass every existing assertion undetected. */
$r5 = fx_tmp_root();
fx_base( $r5 );
fx(
	$r5,
	'skills/sample-prose-writer/SKILL.md',
	"---\nname: sample-prose-writer\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\n" .
	"Discusses update_post_meta() and wp_insert_post() at length; nothing under assets/ calls either.\n"
);
fx( $r5, 'skills/sample-prose-writer/assets/reader.php', "<?php\nfunction read_only() { return true; }\n" );
list( $code5, $out5 ) = fx_run_ok( $audit, $r5 );
$prose_rows = array_filter(
	explode( "\n", $out5 ),
	function ( $l ) {
		return has( $l, 'sample-prose-writer' ) && has( $l, 'writes to WordPress' );
	}
);
ok( 0 === count( $prose_rows ), 'a SKILL.md whose PROSE names write tokens, with no calling assets, produces no write-capability row', $out5 );
fx_rrmdir( $r5 );

echo "--- a \$WRITE_CAPABLE skill with no PHP assets still needs a build gate ---\n";
/* divi-core, wordpress-seo and wordpress-performance ship no assets/*.php at all, so the
   content-based detector above can never catch them missing a gate -- this in_array($WRITE_CAPABLE)
   branch is the ONLY enforcement path for those three. */
$r6 = fx_tmp_root();
fx_base( $r6 );
fx(
	$r6,
	'skills/wordpress-seo/SKILL.md',
	"---\nname: wordpress-seo\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\nFixture write-capable skill: no PHP assets, no build gate.\n"
);
list( $code6, $out6 ) = fx_run_ok( $audit, $r6 );
$gate_missing_rows = array_filter(
	explode( "\n", $out6 ),
	function ( $l ) {
		return has( $l, 'wordpress-seo' ) && has( $l, 'WRITE-CAPABLE SKILL WITH NO BLOCKING BUILD GATE' );
	}
);
ok( count( $gate_missing_rows ) > 0, 'a $WRITE_CAPABLE skill with no assets/*.php and no gate text still FAILs', $out6 );
fx_rrmdir( $r6 );

echo "--- but an agent that DOES carry a routing map still owes every skill a mention ---\n";
$r2b = fx_tmp_root();
fx_base( $r2b );
fx(
	$r2b,
	'agents/orchestrator.md',
	"---\nname: orchestrator\n---\n\n## Routing map\n| Need | Skill |\n|------|-------|\n| Build | `elementor-core` |\n\n"
	. "## House rules\n- Routes commerce work.\n  (no verifier: a fixture rule, here only so the agent states something.)\n"
);
list( $code2b, $out2b ) = fx_run_ok( $audit, $r2b );
ok( has( $out2b, 'unroutable through this agent' ), 'a routing-map agent that omits a skill still WARNs', $out2b );
ok( ! has( $out2b, '"elementor-core" — unroutable' ), 'and says nothing about the one it does mention', $out2b );
fx_rrmdir( $r2b );

echo "--- an agent may name a sibling AGENT without it reading as a missing skill ---\n";
/* Delegation requires naming the delegate. Before this, backticking a second agent's name was a
   FAIL for routing to a skill that does not exist -- a FAIL for doing the thing delegation needs. */
$r2c = fx_tmp_root();
fx_base( $r2c );
fx( $r2c, 'agents/sidekick.md', "---\nname: sidekick\n---\n\n## House rules\n- Does one thing.\n  (no verifier: a fixture rule, here only so the agent states something.)\n" );
fx(
	$r2c,
	'agents/orchestrator.md',
	"---\nname: orchestrator\n---\n\n## House rules\n- Delegates writing to `sidekick`, and never to `ghost-agent`.\n  (no verifier: a fixture rule, here only so the agent states something.)\n"
);
list( $code2c, $out2c ) = fx_run_ok( $audit, $r2c );
ok( ! has( $out2c, 'routes to skill "sidekick"' ), 'naming a real sibling agent is not a missing route', $out2c );
ok( has( $out2c, 'routes to skill "ghost-agent" which is missing' ), 'but a name that is neither skill nor agent still FAILs', $out2c );
fx_rrmdir( $r2c );

echo "--- and the mirror: a skill that DECLARES a gate but is not on the list ---\n";
/* $WRITE_CAPABLE is what makes the gate requirement, the marker requirement and the write checks
   apply at all, so a name dropped from it disables all three at once -- and for a skill that writes
   through the connector rather than through PHP in this repo, the content detector cannot notice.
   This was found by mutating the list and watching nothing fail. */
$r6b = fx_tmp_root();
fx_base( $r6b );
fx(
	$r6b,
	'skills/html-mockup/SKILL.md',
	"---\nname: html-mockup\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\n"
	. "Fixture: a skill NOT in \$WRITE_CAPABLE that nevertheless declares a blocking gate.\n\n"
	. "**Build gate — blocking.** Do not run until the user has given an explicit **yes**.\n\n"
	. "## Hard Rules\n- Something.\n  (no verifier: fixture bullet, nothing checks this.)\n"
);
list( $code6b, $out6b ) = fx_run_ok( $audit, $r6b );
$unlisted_rows = array_filter(
	explode( "\n", $out6b ),
	function ( $l ) {
		return has( $l, 'html-mockup' ) && has( $l, 'missing from $WRITE_CAPABLE' );
	}
);
ok( count( $unlisted_rows ) > 0, 'a skill declaring a build gate while off $WRITE_CAPABLE FAILs', $out6b );
fx_rrmdir( $r6b );

$r7 = fx_tmp_root();
fx_base( $r7 );
fx(
	$r7,
	'skills/wordpress-seo/SKILL.md',
	"---\nname: wordpress-seo\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\nBuild gate: requires explicit **yes** before writing.\n"
);
list( $code7, $out7 ) = fx_run_ok( $audit, $r7 );
$gate_present_rows = array_filter(
	explode( "\n", $out7 ),
	function ( $l ) {
		return has( $l, 'wordpress-seo' ) && has( $l, 'NO BLOCKING BUILD GATE' );
	}
);
ok( 0 === count( $gate_present_rows ), 'the same skill WITH gate text produces no such row', $out7 );
fx_rrmdir( $r7 );

echo "--- missing CONTRIBUTING.md is refused as an install directory ---\n";
$r8 = fx_tmp_root();
mkdir( $r8 . '/skills', 0777, true );
list( $code8, $out8 ) = fx_run_ok( $audit, $r8 );
ok( 2 === $code8, 'exit code 2 when CONTRIBUTING.md is absent', $code8 );
ok( has( $out8, 'CONTRIBUTING.md' ), 'the message names CONTRIBUTING.md', $out8 );
fx_rrmdir( $r8 );

echo "--- --strict promotes a WARN-only run to exit 1 ---\n";
$r9 = fx_tmp_root();
fx_base( $r9 );
list( $code9n, $out9n ) = fx_run_ok( $audit, $r9 );
list( $code9s, ) = fx_run_ok( $audit, $r9, array( '--strict' ) );
list( $f9, $w9, ) = fx_counts( $out9n );
ok( 0 === $f9 && $w9 > 0, 'the base fixture carries a real WARN and no FAIL', json_encode( array( $f9, $w9 ) ) );
ok( 0 === $code9n, 'exit code 0 without --strict despite the WARN', $code9n );
ok( 1 === $code9s, '--strict turns that WARN into exit 1', $code9s );
fx_rrmdir( $r9 );

echo "--- fx_run() distinguishes a launch failure from a parse failure ---\n";
list( $codeL, $msgL, $launchedL ) = fx_run( $audit, '/does/not/matter', array(), '' );
ok( false === $launchedL, 'launched=false when no CLI PHP binary is configured', var_export( $launchedL, true ) );
ok( null === $codeL, 'a launch failure reports no exit code', $codeL );
ok( has( $msgL, 'PHP_BINARY' ), 'the message names PHP_BINARY as the reason', $msgL );
$parse_counts = fx_counts( 'summary line missing from this output' );
ok( array( -1, -1, -1 ) === $parse_counts, 'fx_counts() keeps a distinct sentinel for "ran, unparseable" vs a launch failure', json_encode( $parse_counts ) );

/* The gate self-registration check is the one verifier this change adds, and nothing here
   exercised it: every fixture wrote tests/dummy.php, which the `test-*.php` glob deliberately
   misses, so the loop body never ran once and deleting the whole block left this suite green at
   32 OK. A gate reporting success on work nothing inspected is the exact disease this change
   exists to cure — finding it inside the cure is why both branches are pinned here. */
echo "--- a tests/test-*.php absent from the gate line FAILs, and joining the line clears it ---\n";
$r10 = fx_tmp_root();
fx_base( $r10 );
fx( $r10, 'tests/test-example.php', "<?php\n// fixture test file, never executed by the audit\n" );

list( , $out10a ) = fx_run_ok( $audit, $r10 );
$unregistered = array_filter(
	explode( "\n", $out10a ),
	function ( $l ) {
		return has( $l, 'gate line never runs' ) && has( $l, 'test-example.php' );
	}
);
ok( 1 === count( $unregistered ), 'an unregistered tests/test-*.php raises exactly one gate-line FAIL', $out10a );

fx( $r10, 'CONTRIBUTING.md', "# Contributing\n\nRun `php tests/test-example.php` before every commit.\n\n" . fx_row_type_doc() );
list( , $out10b ) = fx_run_ok( $audit, $r10 );
$registered = array_filter(
	explode( "\n", $out10b ),
	function ( $l ) {
		return has( $l, 'gate line never runs' );
	}
);
ok( 0 === count( $registered ), 'naming it on the gate line clears the row', $out10b );
fx_rrmdir( $r10 );

/* -------------------------------------------------- row-type retrofit fixtures (B0)
 *
 * Every scenario above already exercises 7 of the 21 row types framework-audit.php could print
 * before this slice; these 13 cover the other 14 (one, r16, deliberately covers two: a skill body
 * past 600 words and a separate skill body past 300). Each is the smallest tree that makes ONE
 * check fire, so a future regression in any single check has exactly one fixture pointing at it. */

echo "--- a skill directory with no SKILL.md is RT_NO_SKILL_MD ---\n";
$r11 = fx_tmp_root();
fx_base( $r11 );
fx( $r11, 'skills/sample-empty/.keep', "placeholder, never read by the audit\n" );
list( , $out11 ) = fx_run_ok( $audit, $r11 );
ok( has( $out11, 'RT_NO_SKILL_MD' ), 'a skill directory with no SKILL.md is RT_NO_SKILL_MD', $out11 );
fx_rrmdir( $r11 );

echo "--- a SKILL.md with no YAML frontmatter block is RT_NO_FRONTMATTER ---\n";
$r12 = fx_tmp_root();
fx_base( $r12 );
fx( $r12, 'skills/sample-nofrontmatter/SKILL.md', "# No frontmatter here\n\nJust prose, no --- block.\n" );
list( , $out12 ) = fx_run_ok( $audit, $r12 );
ok( has( $out12, 'RT_NO_FRONTMATTER' ), 'a SKILL.md with no frontmatter block is RT_NO_FRONTMATTER', $out12 );
fx_rrmdir( $r12 );

echo "--- frontmatter missing a required key is RT_FRONTMATTER_MISSING_KEY ---\n";
$r13 = fx_tmp_root();
fx_base( $r13 );
fx(
	$r13,
	'skills/sample-missingkey/SKILL.md',
	"---\nname: sample-missingkey\ndescription: \"Trigger: fixture.\"\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\nFixture skill, license key deliberately omitted.\n"
);
list( , $out13 ) = fx_run_ok( $audit, $r13 );
ok( has( $out13, 'RT_FRONTMATTER_MISSING_KEY' ) && has( $out13, 'license' ), 'frontmatter missing "license" is RT_FRONTMATTER_MISSING_KEY', $out13 );
fx_rrmdir( $r13 );

echo "--- frontmatter name: not matching its directory is RT_NAME_MISMATCH ---\n";
$r14 = fx_tmp_root();
fx_base( $r14 );
fx(
	$r14,
	'skills/sample-namemismatch/SKILL.md',
	"---\nname: something-else\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\nFixture skill.\n"
);
list( , $out14 ) = fx_run_ok( $audit, $r14 );
ok( has( $out14, 'RT_NAME_MISMATCH' ), 'frontmatter name: not matching the directory is RT_NAME_MISMATCH', $out14 );
fx_rrmdir( $r14 );

echo "--- a description with no \"Trigger:\" words is RT_NO_TRIGGER ---\n";
$r15 = fx_tmp_root();
fx_base( $r15 );
fx(
	$r15,
	'skills/sample-notrigger/SKILL.md',
	"---\nname: sample-notrigger\ndescription: \"Fixture skill, no trigger words at all.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\nFixture skill.\n"
);
list( , $out15 ) = fx_run_ok( $audit, $r15 );
ok( has( $out15, 'RT_NO_TRIGGER' ), 'a description with no "Trigger:" words is RT_NO_TRIGGER', $out15 );
fx_rrmdir( $r15 );

echo "--- SKILL.md body word counts past 600 and past 500 are RT_BODY_OVER_600 / RT_BODY_OVER_500 ---\n";
$r16 = fx_tmp_root();
fx_base( $r16 );
fx(
	$r16,
	'skills/sample-bodyover600/SKILL.md',
	"---\nname: sample-bodyover600\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\n"
	. implode( ' ', array_fill( 0, 650, 'word' ) ) . "\n"
);
fx(
	$r16,
	'skills/sample-bodyover500/SKILL.md',
	"---\nname: sample-bodyover500\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\n"
	. implode( ' ', array_fill( 0, 550, 'word' ) ) . "\n"
);
fx(
	$r16,
	'skills/sample-bodyunder500/SKILL.md',
	"---\nname: sample-bodyunder500\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\n"
	. implode( ' ', array_fill( 0, 450, 'word' ) ) . "\n"
);
list( , $out16 ) = fx_run_ok( $audit, $r16 );
/* Row type AND skill name together, never the bare row type: three fixture skills are in this run
   and a substring hit on the ID alone would credit any of them. That is the same anchoring bug the
   coverage ratchet had. */
ok( array() !== fx_lines_with( $out16, array( 'RT_BODY_OVER_600', 'sample-bodyover600' ) ), 'a 650-word SKILL.md body is RT_BODY_OVER_600', $out16 );
ok( array() !== fx_lines_with( $out16, array( 'RT_BODY_OVER_500', 'sample-bodyover500' ) ), 'a 550-word SKILL.md body is RT_BODY_OVER_500', $out16 );
/* 450 sits between the old aim and the new one, so this is what makes moving the number a change
   something MEASURED: revert the threshold to 300 and this row appears. Scoped to the budget rows
   because a bare skeleton fixture legitimately raises other ones (no Hard Rules, and so on) — the
   first version asserted the name was absent from the whole report and failed for that reason. */
ok( array() === fx_lines_with( $out16, array( 'RT_BODY_OVER', 'sample-bodyunder500' ) ), 'a 450-word body is under the new aim and raises no budget row', $out16 );
fx_rrmdir( $r16 );

echo "--- a SKILL.md pointer to a nonexistent references/ path is RT_BROKEN_REFERENCE ---\n";
$r17 = fx_tmp_root();
fx_base( $r17 );
fx(
	$r17,
	'skills/sample-brokenref/SKILL.md',
	"---\nname: sample-brokenref\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\nSee `references/missing.md` for details.\n"
);
list( , $out17 ) = fx_run_ok( $audit, $r17 );
ok( has( $out17, 'RT_BROKEN_REFERENCE' ), 'a SKILL.md pointer to a nonexistent references/ path is RT_BROKEN_REFERENCE', $out17 );
fx_rrmdir( $r17 );

echo "--- a Hard Rule bullet with no verifier marker at all is RT_MARKER_ABSENT (D1', replaces RT_HARD_RULE_NO_VERIFIER) ---\n";
$r18 = fx_tmp_root();
fx_base( $r18 );
fx_wc_skill( $r18, 'elementor-core', "- Keep components small and focused on one job.\n" );
list( , $out18 ) = fx_run_ok( $audit, $r18 );
ok( has( $out18, 'RT_MARKER_ABSENT' ), 'a Hard Rule bullet with no verifier marker at all is RT_MARKER_ABSENT', $out18 );
fx_rrmdir( $r18 );

echo "--- error_log() with no paired stdout channel is RT_ERRORLOG_NO_STDOUT ---\n";
$r19 = fx_tmp_root();
fx_base( $r19 );
fx(
	$r19,
	'skills/sample-errorlog/SKILL.md',
	"---\nname: sample-errorlog\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\nSee `assets/broken.php`.\n"
);
fx(
	$r19,
	'skills/sample-errorlog/assets/broken.php',
	"<?php\nfunction silent_fail( \$e ) {\n\terror_log( \$e );\n\treturn null;\n}\n"
);
list( , $out19 ) = fx_run_ok( $audit, $r19 );
ok( has( $out19, 'RT_ERRORLOG_NO_STDOUT' ), 'error_log() with no paired stdout channel nearby is RT_ERRORLOG_NO_STDOUT', $out19 );
fx_rrmdir( $r19 );

echo "--- a .mjs asset that defaults --out is RT_CAPTURE_OUT_DEFAULTED ---\n";
/* A capture tool whose --out falls back to the working directory lets two runs started from the
   same place overwrite each other's frames in silence. It is a one-line defect and it was live:
   blind-judges/assets/capture.mjs did exactly this. A one-line fix with nothing watching it comes
   back, so the rule is what keeps it fixed. */
$r_out = fx_tmp_root();
fx_base( $r_out );
fx(
	$r_out,
	'skills/sample-capture/SKILL.md',
	"---\nname: sample-capture\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\nSee `assets/shoot.mjs`.\n"
);
fx(
	$r_out,
	'skills/sample-capture/assets/shoot.mjs',
	"const outDir = resolve( arg( '--out', process.cwd() ) );\n"
);
list( , $out_out ) = fx_run_ok( $audit, $r_out );
ok( has( $out_out, 'RT_CAPTURE_OUT_DEFAULTED' ), 'a .mjs asset that gives --out a default is RT_CAPTURE_OUT_DEFAULTED', $out_out );
fx_rrmdir( $r_out );

echo "--- a .mjs asset that REQUIRES --out is clean ---\n";
/* The other half, and the one that keeps the rule from being satisfied by deleting the flag:
   requiring --out must NOT fire it. Without this a rule that flags every .mjs would pass too. */
$r_out2 = fx_tmp_root();
fx_base( $r_out2 );
fx(
	$r_out2,
	'skills/sample-capture/SKILL.md',
	"---\nname: sample-capture\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\nSee `assets/shoot.mjs`.\n"
);
fx(
	$r_out2,
	'skills/sample-capture/assets/shoot.mjs',
	"const outDir = arg( '--out' ) ? resolve( arg( '--out' ) ) : null;\n"
);
list( , $out_out2 ) = fx_run_ok( $audit, $r_out2 );
ok( ! has( $out_out2, 'RT_CAPTURE_OUT_DEFAULTED' ), 'and requiring --out does not fire it', $out_out2 );
fx_rrmdir( $r_out2 );

echo "--- prose naming a row type that does not exist is RT_ROWTYPE_PHANTOM ---\n";
/* The class this closes, found live: es-builder.php's manifest docblock claimed
   `RT_REPLAY_NO_FINGERPRINT` FAILed while nothing recorded the fingerprint. That row did not exist
   and could not have — the audit cannot read a live WordPress option (CONTRIBUTING.md, "Testing a change") — so the
   sentence was a verifier promised and never built, in a file whose docblocks no check reads.
   RT_ROWTYPE_UNDOCUMENTED already catches a row declared and undocumented; this is the mirror,
   and between them the pair is closed in both directions. */
$r_ph = fx_tmp_root();
fx_base( $r_ph );
fx( $r_ph, 'skills/sample-phantom/SKILL.md', "---\nname: sample-phantom\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\nSee `assets/lib.php`.\n" );
fx( $r_ph, 'skills/sample-phantom/assets/lib.php', "<?php\n/* Guarded by `RT_INVENTED_BY_THIS_FIXTURE`, which nothing declares. */\n" );
list( , $out_ph ) = fx_run_ok( $audit, $r_ph );
ok( has( $out_ph, 'RT_ROWTYPE_PHANTOM' ), 'prose naming a row type ROW_TYPES does not declare is RT_ROWTYPE_PHANTOM', $out_ph );
ok( has( $out_ph, 'RT_INVENTED_BY_THIS_FIXTURE' ), 'and it names the id, which is the only thing the reader can search for', $out_ph );
fx_rrmdir( $r_ph );

echo "--- naming a row type that DOES exist is clean ---\n";
/* Without this half, a rule that flagged every backticked RT_* alike would pass the half above.
   Citing a real row is the whole point of the marker grammar; it must stay free. */
$r_ph2 = fx_tmp_root();
fx_base( $r_ph2 );
fx( $r_ph2, 'skills/sample-phantom/SKILL.md', "---\nname: sample-phantom\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\nSee `assets/lib.php`.\n" );
fx( $r_ph2, 'skills/sample-phantom/assets/lib.php', "<?php\n/* Guarded by `RT_ORPHAN_FILE`, which ROW_TYPES declares. */\n" );
list( , $out_ph2 ) = fx_run_ok( $audit, $r_ph2 );
ok( ! has( $out_ph2, 'RT_ROWTYPE_PHANTOM' ), 'and citing a row type that IS declared does not fire it', $out_ph2 );
fx_rrmdir( $r_ph2 );

echo "--- a house-rules row calling the library without naming a world is RT_HOUSERULES_NO_WORLD ---\n";
/* A row whose method is a library call cannot run everywhere: production runs no bridge of ours,
   so unless one PHP file can be evaluated there, that row has NO production arm at all. A row
   that does not say which world it runs in reads as checkable everywhere and is checkable
   nowhere — which is precisely how UNVERIFIED becomes PASS without anyone deciding to allow it.
   Scoped to library-calling rows on purpose: a pure-HTTP row runs in every world, and demanding
   it say so would be ceremony that teaches the reader to skip the annotation. */
$r_w = fx_tmp_root();
fx_base( $r_w );
fx( $r_w, 'skills/qa-review/references/house-rules.md', "| # | Rule | What to check | Server-side method | Verdict source |\n|---|---|---|---|---|\n| 1 | Fixture rule | something | Call `es_manifest_verify()` and require an empty drift list | **auto** |\n" );
list( , $out_w ) = fx_run_ok( $audit, $r_w );
ok( has( $out_w, 'RT_HOUSERULES_NO_WORLD' ), 'a library-calling row that names no world is RT_HOUSERULES_NO_WORLD', $out_w );
fx_rrmdir( $r_w );

echo "--- naming the world clears it ---\n";
$r_w2 = fx_tmp_root();
fx_base( $r_w2 );
fx( $r_w2, 'skills/qa-review/references/house-rules.md', "| # | Rule | What to check | Server-side method | Verdict source |\n|---|---|---|---|---|\n| 1 | Fixture rule | something | Call `es_manifest_verify()` and require an empty drift list. World: W1 or W3 | **auto** |\n" );
list( , $out_w2 ) = fx_run_ok( $audit, $r_w2 );
ok( ! has( $out_w2, 'RT_HOUSERULES_NO_WORLD' ), 'and the same row naming W1 or W3 does not fire it', $out_w2 );
fx_rrmdir( $r_w2 );

echo "--- a migration doc that never names the sandbox is RT_MIGRATION_NO_EXCLUDE ---\n";
/* The sandbox directory sits INSIDE wp-content, so a literal copy ships it to the client's
   server, executable and reachable by URL. It is the one exclusion whose absence is
   catastrophic, so its presence stops being something a writer remembers.
   What this proves and what it does not: that the document NAMES it, not that any archive
   actually excluded it. House-rules row 33 is what proves the archive. */
$r_mx = fx_tmp_root();
fx_base( $r_mx );
fx( $r_mx, 'skills/sample-mig/SKILL.md', "---\nname: sample-mig\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\nSee `references/migration.md`.\n" );
fx( $r_mx, 'skills/sample-mig/references/migration.md', "# Migration\n\nCopy wp-content and the database. Exclude the caches.\n" );
list( , $out_mx ) = fx_run_ok( $audit, $r_mx );
ok( has( $out_mx, 'RT_MIGRATION_NO_EXCLUDE' ), 'a migration doc that never names novamira-sandbox is RT_MIGRATION_NO_EXCLUDE', $out_mx );
fx_rrmdir( $r_mx );

echo "--- naming it clears that too ---\n";
$r_mx2 = fx_tmp_root();
fx_base( $r_mx2 );
fx( $r_mx2, 'skills/sample-mig/SKILL.md', "---\nname: sample-mig\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\nSee `references/migration.md`.\n" );
fx( $r_mx2, 'skills/sample-mig/references/migration.md', "# Migration\n\nExcluded: wp-content/novamira-sandbox/** — it must never travel.\n" );
list( , $out_mx2 ) = fx_run_ok( $audit, $r_mx2 );
ok( ! has( $out_mx2, 'RT_MIGRATION_NO_EXCLUDE' ), 'and one that names it does not fire it', $out_mx2 );
fx_rrmdir( $r_mx2 );

echo "--- a house-rules row citing a row number the table does not have is RT_HOUSERULES_ROW_PHANTOM ---\n";
/* Rows cross-reference each other constantly — "the same call as row 11", "row 22 empties the
   sandbox first" — and that is how the file stays one document instead of thirty-four. It also
   means renumbering or deleting a row leaves prose pointing at nothing.
   Not hypothetical: deleting three rows in one edit left four references behind in this very
   file, and nothing caught them. RT_ROWTYPE_PHANTOM does not: it reads backticked RT_* ids. The
   marker grammar does not either: it only resolves `house-rule row N` inside a (verifier: …)
   marker, and these citations live in the method column, which no check reads. */
$r_rp = fx_tmp_root();
fx_base( $r_rp );
fx( $r_rp, 'skills/qa-review/references/house-rules.md', "| # | Rule | What to check | Server-side method | Verdict source |\n|---|---|---|---|---|\n| 1 | Fixture rule | something | Same call as row 99, which already reports it. World: W1 | **auto** |\n" );
list( , $out_rp ) = fx_run_ok( $audit, $r_rp );
ok( has( $out_rp, 'RT_HOUSERULES_ROW_PHANTOM' ), 'prose citing a row the table does not contain is RT_HOUSERULES_ROW_PHANTOM', $out_rp );
ok( has( $out_rp, '99' ), 'and it names the number, which is the only thing the reader can search for', $out_rp );
fx_rrmdir( $r_rp );

echo "--- citing a row that DOES exist is clean ---\n";
/* Without this half, a rule that flagged every `row N` alike would pass the half above — and
   cross-referencing is the point of the file, not a smell. */
$r_rp2 = fx_tmp_root();
fx_base( $r_rp2 );
fx( $r_rp2, 'skills/qa-review/references/house-rules.md', "| # | Rule | What to check | Server-side method | Verdict source |\n|---|---|---|---|---|\n| 1 | Fixture rule | something | Same call as row 1, which already reports it. World: W1 | **auto** |\n" );
list( , $out_rp2 ) = fx_run_ok( $audit, $r_rp2 );
ok( ! has( $out_rp2, 'RT_HOUSERULES_ROW_PHANTOM' ), 'and citing a row that exists does not fire it', $out_rp2 );
fx_rrmdir( $r_rp2 );

echo "--- an agent markdown file with a code block is RT_AGENT_CODE_BLOCK ---\n";
$r20 = fx_tmp_root();
fx_base( $r20 );
fx( $r20, 'agents/orchestrator.md', "---\nname: orchestrator\n---\n\n## House rules\n\n```php\necho 'nope';\n```\n" );
list( , $out20 ) = fx_run_ok( $audit, $r20 );
ok( has( $out20, 'RT_AGENT_CODE_BLOCK' ), 'an agent markdown file with a code block is RT_AGENT_CODE_BLOCK', $out20 );
fx_rrmdir( $r20 );

echo "--- a house-rules.md row with no verdict source in its last column is RT_HOUSERULES_NO_VERDICT ---\n";
$r21 = fx_tmp_root();
fx_base( $r21 );
fx( $r21, 'skills/qa-review/references/house-rules.md', fx_house_rules( "| 2 | Some rule | plain text, no bold verdict source |\n" ) );
list( , $out21 ) = fx_run_ok( $audit, $r21 );
ok( has( $out21, 'RT_HOUSERULES_NO_VERDICT' ), 'a house-rules.md row with no bold verdict source is RT_HOUSERULES_NO_VERDICT', $out21 );
fx_rrmdir( $r21 );

echo "--- a missing qa-review/references/house-rules.md is RT_HOUSERULES_MISSING ---\n";
$r22 = fx_tmp_root();
fx_base( $r22 );
unlink( $r22 . '/skills/qa-review/references/house-rules.md' );
list( , $out22 ) = fx_run_ok( $audit, $r22 );
ok( has( $out22, 'RT_HOUSERULES_MISSING' ), 'a missing qa-review/references/house-rules.md is RT_HOUSERULES_MISSING', $out22 );
fx_rrmdir( $r22 );

echo "--- a tree with no tests/*.php at all is RT_NO_OFFLINE_TESTS ---\n";
$r23 = fx_tmp_root();
fx( $r23, 'CONTRIBUTING.md', "# Contributing\nFixture root, deliberately no tests/*.php anywhere.\n\n" . fx_row_type_doc() );
fx(
	$r23,
	'skills/qa-review/SKILL.md',
	"---\nname: qa-review\ndescription: \"Trigger: fixture skill.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\n# QA Review Fixture\n\nSee `references/house-rules.md`.\n"
);
fx( $r23, 'skills/qa-review/references/house-rules.md', fx_house_rules() );
list( , $out23 ) = fx_run_ok( $audit, $r23 );
ok( has( $out23, 'RT_NO_OFFLINE_TESTS' ), 'a tree with no tests/*.php at all is RT_NO_OFFLINE_TESTS', $out23 );
fx_rrmdir( $r23 );

/* Mirrors r10's gate-self-registration pair exactly (D1'.7 says this check is the same shape).
   Omitting one ID from CONTRIBUTING.md's row-type table must FAIL naming that exact ID; restoring
   it must clear the row. This is also the mutation the coverage assertion below depends on never
   going stale: RT_ROWTYPE_UNDOCUMENTED is itself declared in ROW_TYPES, so IT needs a fixture too
   — the same requirement it enforces on every other row type, applied to itself. */
echo "--- a ROW_TYPES ID undocumented in CONTRIBUTING.md is RT_ROWTYPE_UNDOCUMENTED, and documenting it clears the row ---\n";
$r24 = fx_tmp_root();
fx_base( $r24 );
/* The preamble text must NOT name the omitted ID itself — strpos() cannot tell "documented in the
   table" from "mentioned in prose describing this fixture", and the first draft of this fixture
   self-sabotaged exactly that way: writing the ID's name in the description satisfied the check
   even with its bullet removed. */
$omitted_doc = str_replace( "- RT_NO_SKILL_MD\n", '', fx_row_type_doc() );
ok( false === strpos( $omitted_doc, 'RT_NO_SKILL_MD' ), 'the omitted-ID fixture doc really omits RT_NO_SKILL_MD', $omitted_doc );
fx( $r24, 'CONTRIBUTING.md', "# Contributing\nFixture root, one row type deliberately left off the table below.\n\n" . $omitted_doc );
list( , $out24a ) = fx_run_ok( $audit, $r24 );
ok( has( $out24a, 'RT_ROWTYPE_UNDOCUMENTED' ) && has( $out24a, 'RT_NO_SKILL_MD' ), 'omitting one ID from the table raises RT_ROWTYPE_UNDOCUMENTED naming it', $out24a );

fx( $r24, 'CONTRIBUTING.md', "# Contributing\nFixture root, all IDs documented.\n\n" . fx_row_type_doc() );
list( , $out24b ) = fx_run_ok( $audit, $r24 );
ok( ! has( $out24b, 'RT_ROWTYPE_UNDOCUMENTED' ), 'documenting every ID clears the row', $out24b );
fx_rrmdir( $r24 );

/* The registry's OTHER guarantee: add() refuses an ID that ROW_TYPES does not declare, loudly,
 * rather than shipping a row nothing registered. The coverage assertion above can never reach this
 * one — the guard's whole purpose is to stop the process BEFORE anything is printed, so an
 * emitted-ID census is structurally blind to it. It needs its own fixture or it is another check
 * with no verifier, which is the shape that sank two prior slices of this change.
 *
 * The IDs are literals in the audit's source, so no synthetic tree can provoke this: the fixture
 * mutates a COPY of the audit instead, and the copy lives in a temp root that gets swept like any
 * other. */
echo "--- add() refuses an unregistered row-type ID, loudly ---\n";
$r25       = fx_tmp_root();
fx_base( $r25 );
$audit_src = (string) file_get_contents( $audit );
fx( $r25, 'mutant-audit.php', str_replace( "\$rows = array();", "\$rows = array();\nadd( 'RT_NOT_IN_THE_REGISTRY', 'FAIL', 'mutation', 'this ID was never declared' );", $audit_src ) );
ok( has( (string) file_get_contents( $r25 . '/mutant-audit.php' ), 'RT_NOT_IN_THE_REGISTRY' ), 'the mutant copy really carries the unregistered call', 'injection failed' );
list( $code25, $out25 ) = fx_run_ok( $r25 . '/mutant-audit.php', $r25 );
ok( 3 === $code25, 'an unregistered row-type ID exits 3, not 0 and not 1', $code25 );
ok( has( $out25, 'RT_NOT_IN_THE_REGISTRY' ), 'the message names the offending ID', $out25 );
ok( has( $out25, 'ROW_TYPES' ), 'the message says where to register it', $out25 );
fx_rrmdir( $r25 );

/* --------------------------------------------- design-tokens.md hardcoded-font fixture
 *
 * The style catalog, its axes and its ledger are gone, and so are the rows that guarded them. What
 * is left of this block is RT_TOKENS_HARDCODED_FONT: design-tokens.md must not name an example font
 * pairing, because one named example with no alternative is what made every build converge on the
 * same look. It proves the conforming shape produces NO row as well, since the check loops two
 * literals and an assertion that only ever fires cannot show the check discriminates. */

echo "--- ux-design-system: design-tokens.md hardcoding an example font pairing is RT_TOKENS_HARDCODED_FONT, and dropping it clears the row ---\n";
$r29 = fx_tmp_root();
fx_base( $r29 );
fx( $r29, 'skills/ux-design-system/references/design-tokens.md', "# Design tokens fixture\n\nHeadings use Space Grotesk; body text uses Manrope.\n" );
list( , $out29a ) = fx_run_ok( $audit, $r29 );
ok(
	has( $out29a, 'RT_TOKENS_HARDCODED_FONT' ) && has( $out29a, 'Space Grotesk' ) && has( $out29a, 'Manrope' ),
	'design-tokens.md hardcoding both example fonts raises RT_TOKENS_HARDCODED_FONT naming each',
	$out29a
);

fx( $r29, 'skills/ux-design-system/references/design-tokens.md', "# Design tokens fixture\n\nThe font pairing comes from the chosen personality; no example is hardcoded here.\n" );
list( , $out29b ) = fx_run_ok( $audit, $r29 );
ok( ! has( $out29b, 'RT_TOKENS_HARDCODED_FONT' ), 'a design-tokens.md with no hardcoded example font clears the row', $out29b );
fx_rrmdir( $r29 );

/* ------------------------------------------------- marker grammar fixtures (B1, design D1')
 *
 * Every scenario below is scoped to a $WRITE_CAPABLE skill name (elementor-core, divi-core,
 * woocommerce, wordpress-seo, wordpress-performance) because the marker-grammar verdict rows are
 * scoped that way in framework-audit.php itself -- see fx_wc_skill(). Two mandatory fixtures
 * (r33, r34) reproduce the exact mutation that defeated the rejected slice's greedy regex: a
 * marker line followed by trailing prose that itself ends in a parenthetical.
 */

echo "--- a bullet whose PROSE merely mentions the marker mid-sentence is RT_MARKER_ABSENT, never a false marker match ---\n";
/* The exact framework-audit/SKILL.md shape this slice exists to prevent forever: the token
   appears on the bullet's continuation line, but backtick-wrapped and mid-sentence, never as the
   first character of the line. */
$r32 = fx_tmp_root();
fx_base( $r32 );
fx_wc_skill(
	$r32,
	'elementor-core',
	"- Do the thing properly and double-check the work before it ships.\n"
	. "  See the `(no verifier: reason)` convention in CONTRIBUTING.md for the escape hatch shape.\n"
);
list( , $out32 ) = fx_run_ok( $audit, $r32 );
ok( has( $out32, 'RT_MARKER_ABSENT' ), 'a bullet whose prose merely mentions the marker mid-sentence is RT_MARKER_ABSENT, not a false marker match', $out32 );
fx_rrmdir( $r32 );

echo "--- CRITICAL fixture: (no verifier: -) + trailing prose ending in a parenthetical is RT_MARKER_TRAILING_TEXT, not zero rows ---\n";
/* Reproduces the proven exploit verbatim: under the rejected slice's greedy /s regex this fixture
   produced ZERO rows because the pattern matched from the marker's "(" all the way to the LAST
   ")" in the bullet, swallowing the whole trailing sentence into the payload. */
$r33 = fx_tmp_root();
fx_base( $r33 );
fx_wc_skill(
	$r33,
	'divi-core',
	"- A real constraint the rule describes.\n"
	. "  (no verifier: -)\n"
	. "  More prose after the marker line, and it happens to end in a parenthetical (like this one).\n"
);
list( $code33, $out33 ) = fx_run_ok( $audit, $r33 );
ok( has( $out33, 'RT_MARKER_TRAILING_TEXT' ), '(no verifier: -) followed by trailing prose ending in a parenthetical is RT_MARKER_TRAILING_TEXT', $out33 );
ok( 1 === $code33, 'RT_MARKER_TRAILING_TEXT is a FAIL, exit code 1', $code33 );
fx_rrmdir( $r33 );

echo "--- CRITICAL fixture: (verifier: dunno) + trailing prose naming an unrelated es_*() is RT_MARKER_TRAILING_TEXT, existence never resolves through the prose ---\n";
$r34 = fx_tmp_root();
fx_base( $r34 );
fx_wc_skill(
	$r34,
	'woocommerce',
	"- A real constraint the rule describes.\n"
	. "  (verifier: dunno)\n"
	. "  Unrelated trailing prose that happens to name es_container_audit() by accident.\n"
);
list( , $out34 ) = fx_run_ok( $audit, $r34 );
ok( has( $out34, 'RT_MARKER_TRAILING_TEXT' ), '(verifier: dunno) followed by trailing prose naming an unrelated es_*() is RT_MARKER_TRAILING_TEXT', $out34 );
ok( ! has( $out34, 'RT_MARKER_TARGET_MISSING' ) && ! has( $out34, 'es_container_audit' ), 'the existence check never resolves through the unrelated trailing prose', $out34 );
fx_rrmdir( $r34 );

echo "--- a payload wrapped across two lines, closing at the absolute end, is accepted ---\n";
$r35 = fx_tmp_root();
fx_base( $r35 );
fx( $r35, 'skills/wordpress-performance/assets/dummy.php', "<?php\nfunction es_multiline_target() {\n\treturn true;\n}\n" );
fx_wc_skill(
	$r35,
	'wordpress-performance',
	"- A bullet whose verifier payload wraps across two physical lines in the source.\n"
	. "  (verifier: this explanation deliberately wraps across two lines and finally\n"
	. "  cites es_multiline_target() to prove multi-line payload capture works)\n"
);
list( , $out35 ) = fx_run_ok( $audit, $r35 );
ok( array() === fx_lines_with( $out35, array( 'wordpress-performance', 'RT_MARKER' ) ), 'a multi-line wrapped payload that closes at the absolute end is accepted -- no marker row', $out35 );
fx_rrmdir( $r35 );

echo "--- a payload containing the verifier's OWN call parentheses is captured whole, the balancer does not stop at the first \")\" ---\n";
$r36 = fx_tmp_root();
fx_base( $r36 );
fx( $r36, 'skills/elementor-core/assets/dummy.php', "<?php\nfunction es_test_target() {\n\treturn true;\n}\n" );
fx_wc_skill( $r36, 'elementor-core', "- A bullet citing a helper whose own call parentheses must not confuse the balancer.\n  (verifier: es_test_target())\n" );
list( , $out36 ) = fx_run_ok( $audit, $r36 );
ok( array() === fx_lines_with( $out36, array( 'elementor-core', 'RT_MARKER' ) ), '(verifier: es_test_target()) -- the balancer finds the marker\'s OWN closing paren, not the first one, so this resolves with no row', $out36 );
fx_rrmdir( $r36 );

echo "--- two markers on one bullet is RT_MARKER_MULTIPLE ---\n";
$r37 = fx_tmp_root();
fx_base( $r37 );
fx_wc_skill(
	$r37,
	'divi-core',
	"- A confused bullet carrying two markers.\n"
	. "  (verifier: first explanation of the check goes here)\n"
	. "  (verifier: second explanation should never coexist with the first)\n"
);
list( , $out37 ) = fx_run_ok( $audit, $r37 );
ok( 'FAIL' === fx_row_level( $out37, array( 'RT_MARKER_MULTIPLE' ) ), 'a bullet with two verifier markers is RT_MARKER_MULTIPLE, at FAIL level', fx_row_level( $out37, array( 'RT_MARKER_MULTIPLE' ) ) );
fx_rrmdir( $r37 );

echo "--- an unclosed marker is RT_MARKER_UNCLOSED ---\n";
$r38 = fx_tmp_root();
fx_base( $r38 );
fx_wc_skill( $r38, 'woocommerce', "- A bullet whose marker forgets to close its parenthesis.\n  (verifier: this reason never gets its closing paren\n" );
list( , $out38 ) = fx_run_ok( $audit, $r38 );
ok( 'FAIL' === fx_row_level( $out38, array( 'RT_MARKER_UNCLOSED' ) ), 'a marker whose opening paren is never closed is RT_MARKER_UNCLOSED, at FAIL level', fx_row_level( $out38, array( 'RT_MARKER_UNCLOSED' ) ) );
fx_rrmdir( $r38 );

echo "--- an empty reason on BOTH polarities is RT_MARKER_EMPTY, never RT_MARKER_UNCLOSED ---\n";
$r39 = fx_tmp_root();
fx_base( $r39 );
fx_wc_skill(
	$r39,
	'wordpress-seo',
	"- First bullet with an empty affirmative marker.\n  (verifier:)\n"
	. "- Second bullet with an empty negated marker.\n  (no verifier:)\n"
);
list( , $out39 ) = fx_run_ok( $audit, $r39 );
ok( 2 === substr_count( $out39, 'RT_MARKER_EMPTY' ), '(verifier:) and (no verifier:) both raise RT_MARKER_EMPTY -- the closing paren IS there, this is not RT_MARKER_UNCLOSED', $out39 );
ok( ! has( $out39, 'RT_MARKER_UNCLOSED' ), 'an empty-but-closed marker is never misreported as unclosed', $out39 );
ok( 'FAIL' === fx_row_level( $out39, array( 'RT_MARKER_EMPTY', 'First bullet' ) ), 'an empty marker payload is RT_MARKER_EMPTY, at FAIL level', fx_row_level( $out39, array( 'RT_MARKER_EMPTY', 'First bullet' ) ) );
fx_rrmdir( $r39 );

echo "--- wrong case ((no) Verifier:/VERIFIER:) is RT_MARKER_CASE, never silently accepted ---\n";
$r40 = fx_tmp_root();
fx_base( $r40 );
fx_wc_skill( $r40, 'elementor-core', "- A bullet using the wrong case for the marker token.\n  (NO VERIFIER: this should be flagged for case, not silently accepted)\n" );
list( , $out40 ) = fx_run_ok( $audit, $r40 );
ok( 'FAIL' === fx_row_level( $out40, array( 'RT_MARKER_CASE' ) ), 'a marker token that is not the exact lowercase literal is RT_MARKER_CASE, at FAIL level', fx_row_level( $out40, array( 'RT_MARKER_CASE' ) ) );
fx_rrmdir( $r40 );

echo "--- D1'.5: a name defined for real resolves; the same name in a docblock or a string literal does not (token_get_all, not a raw regex) ---\n";
$r41 = fx_tmp_root();
fx_base( $r41 );
fx(
	$r41,
	'skills/woocommerce/assets/tokens.php',
	"<?php\n"
	. "/**\n * Historically this called es_ghost() directly; now it delegates.\n */\n"
	. "function es_real() {\n\treturn true;\n}\n"
	. "\$s = 'function es_phantom() { return true; }';\n"
);
fx_wc_skill(
	$r41,
	'woocommerce',
	"- Shape A: cites a function really defined in this skill's assets.\n  (verifier: resolved through es_real() which is a genuine function)\n"
	. "- Shape B: cites a name that only ever appears inside a docblock comment.\n  (verifier: this claims es_ghost() checks it, but that name is only a comment)\n"
	. "- Shape C: cites a name that only ever appears inside a string literal.\n  (verifier: this claims es_phantom() checks it, but that name is only a string)\n"
);
list( , $out41 ) = fx_run_ok( $audit, $r41 );
ok( array() === fx_lines_with( $out41, array( 'Shape A', 'RT_MARKER' ) ), 'a function genuinely defined (T_FUNCTION + T_STRING) resolves, no marker row', $out41 );
ok( array() !== fx_lines_with( $out41, array( 'RT_MARKER_TARGET_MISSING', 'es_ghost()' ) ), 'a name that exists only inside a docblock comment is RT_MARKER_TARGET_MISSING, naming es_ghost()', $out41 );
ok( array() !== fx_lines_with( $out41, array( 'RT_MARKER_TARGET_MISSING', 'es_phantom()' ) ), 'a name that exists only inside a string literal is RT_MARKER_TARGET_MISSING, naming es_phantom()', $out41 );
fx_rrmdir( $r41 );

echo "--- the mislabel: (no verifier: ...) citing a step that DOES exist is RT_MARKER_MISLABEL, a JUDGE, never silently accepted ---\n";
$r42 = fx_tmp_root();
fx_base( $r42 );
fx_wc_skill(
	$r42,
	'divi-core',
	"- A bullet whose author wrongly believes nothing checks it.\n  (no verifier: covered already by step-1 in this very skill)\n",
	"## Execution Steps\n1. The first real, numbered step this marker can point at.\n2. A second step, unrelated.\n"
);
list( , $out42 ) = fx_run_ok( $audit, $r42 );
ok( array() !== fx_lines_with( $out42, array( 'RT_MARKER_MISLABEL', 'divi-core step 1' ) ), '(no verifier: ...) citing step-4-shaped text that resolves to a real step is RT_MARKER_MISLABEL, a JUDGE row', $out42 );
fx_rrmdir( $r42 );

echo "--- pivotal pair: (verifier: TODO) and (no verifier: TODO) raise the IDENTICAL FAIL row type and exit code (D1'.3 symmetry) ---\n";
/* This is the incentive fix: in the rejected slice, the affirmative marker degraded to a
   non-blocking JUDGE while the negated one FAILed for the identical placeholder payload, so the
   cheapest route past the gate was to ASSERT a verifier that does not exist. Both polarities must
   now cost the same. */
$r43 = fx_tmp_root();
fx_base( $r43 );
fx_wc_skill(
	$r43,
	'wordpress-performance',
	"- Affirmative marker with a placeholder payload.\n  (verifier: TODO)\n"
	. "- Negated marker with the identical placeholder payload.\n  (no verifier: TODO)\n"
);
list( $code43, $out43 ) = fx_run_ok( $audit, $r43 );
ok( 2 === substr_count( $out43, 'RT_MARKER_STOPWORD' ), '(verifier: TODO) and (no verifier: TODO) both raise RT_MARKER_STOPWORD -- the SAME row type', $out43 );
ok( 1 === $code43, 'both stopword placeholders FAIL -- exit code 1, never a free pass for either polarity', $code43 );
fx_rrmdir( $r43 );

echo "--- a payload under 12 characters is RT_MARKER_TOO_SHORT ---\n";
$r44 = fx_tmp_root();
fx_base( $r44 );
fx_wc_skill( $r44, 'elementor-core', "- Affirmative marker whose payload is real but too short to mean much.\n  (verifier: short)\n" );
list( , $out44 ) = fx_run_ok( $audit, $r44 );
ok( 'FAIL' === fx_row_level( $out44, array( 'RT_MARKER_TOO_SHORT' ) ), 'a payload under 12 characters is RT_MARKER_TOO_SHORT, at FAIL level', fx_row_level( $out44, array( 'RT_MARKER_TOO_SHORT' ) ) );
fx_rrmdir( $r44 );

echo "--- a payload over 40 words is RT_MARKER_OVERSIZE ---\n";
$r45 = fx_tmp_root();
fx_base( $r45 );
fx_wc_skill( $r45, 'woocommerce', "- Affirmative marker whose payload blows well past the 40-word cap.\n  (verifier: " . implode( ' ', array_fill( 0, 45, 'word' ) ) . ")\n" );
list( , $out45 ) = fx_run_ok( $audit, $r45 );
ok( 'FAIL' === fx_row_level( $out45, array( 'RT_MARKER_OVERSIZE' ) ), 'a payload over 40 words is RT_MARKER_OVERSIZE, at FAIL level', fx_row_level( $out45, array( 'RT_MARKER_OVERSIZE' ) ) );
fx_rrmdir( $r45 );

echo "--- an affirmative marker citing no locatable shape at all is RT_MARKER_PROSE_ONLY, a JUDGE ---\n";
$r46 = fx_tmp_root();
fx_base( $r46 );
fx_wc_skill( $r46, 'wordpress-seo', "- Affirmative marker whose reason is a real sentence but cites nothing checkable.\n  (verifier: the team eyeballs this manually before every release, no artifact yet)\n" );
list( , $out46 ) = fx_run_ok( $audit, $r46 );
ok( has( $out46, 'RT_MARKER_PROSE_ONLY' ), 'an affirmative marker citing no function, tests/ path, house-rule row or step is RT_MARKER_PROSE_ONLY', $out46 );
fx_rrmdir( $r46 );

echo "--- D1'.4: all four resolver shapes, when the cited target really exists, resolve with no row ---\n";
$r47 = fx_tmp_root();
fx_base( $r47 );
fx( $r47, 'skills/elementor-core/assets/shapes.php', "<?php\nfunction es_shape_ok() {\n\treturn true;\n}\n" );
fx( $r47, 'tests/shape-ok.php', "<?php\n// fixture, never executed by the audit\n" );
fx( $r47, 'skills/qa-review/references/house-rules.md', fx_house_rules( "| 5 | A real rule | Server-side check | **auto** |\n" ) );
fx_wc_skill(
	$r47,
	'elementor-core',
	"- Shape 1: cites a function that is really defined.\n  (verifier: es_shape_ok() proves this)\n"
	. "- Shape 2: cites a tests/ path that really exists.\n  (verifier: see `tests/shape-ok.php` for it)\n"
	. "- Shape 3: cites a house-rules row that really exists.\n  (verifier: `qa-review` house-rule row 5 covers it)\n"
	. "- Shape 4: cites an execution step that really exists.\n  (verifier: step 1 of this skill's own Execution Steps)\n",
	"## Execution Steps\n1. The first real numbered step this marker can point at.\n"
);
list( , $out47 ) = fx_run_ok( $audit, $r47 );
ok( array() === fx_lines_with( $out47, array( 'RT_MARKER_TARGET_MISSING', 'Shape 1' ) ), 'shape 1 (existing function) resolves, no target-missing row', $out47 );
ok( array() === fx_lines_with( $out47, array( 'RT_MARKER_TARGET_MISSING', 'Shape 2' ) ), 'shape 2 (existing tests/ path) resolves, no target-missing row', $out47 );
ok( array() === fx_lines_with( $out47, array( 'RT_MARKER_TARGET_MISSING', 'Shape 3' ) ), 'shape 3 (existing house-rules row) resolves, no target-missing row', $out47 );
ok( array() === fx_lines_with( $out47, array( 'RT_MARKER_TARGET_MISSING', 'Shape 4' ) ), 'shape 4 (existing execution step) resolves, no target-missing row', $out47 );
fx_rrmdir( $r47 );

echo "--- D1'.4: all four resolver shapes, when the cited target does NOT exist, are RT_MARKER_TARGET_MISSING ---\n";
$r48 = fx_tmp_root();
fx_base( $r48 );
fx_wc_skill(
	$r48,
	'woocommerce',
	"- Shape 1: cites a function that is never defined anywhere.\n  (verifier: es_shape_missing() proves this)\n"
	. "- Shape 2: cites a tests/ path that never exists.\n  (verifier: see `tests/shape-missing.php` for it)\n"
	. "- Shape 3: cites a house-rules row that never exists.\n  (verifier: `qa-review` house-rule row 999 covers it)\n"
	. "- Shape 4: cites an execution step that never exists.\n  (verifier: step 99 of this skill's own Execution Steps)\n",
	"## Execution Steps\n1. The only real numbered step -- 99 is never one of them.\n"
);
list( , $out48 ) = fx_run_ok( $audit, $r48 );
/* Level, not presence: this is the check that makes asserting a verifier that does not exist cost
   exactly what admitting the gap costs (D1'.3), and at WARN it costs nothing. */
foreach ( array( 1, 2, 3, 4 ) as $shape ) {
	$lvl = fx_row_level( $out48, array( 'RT_MARKER_TARGET_MISSING', "Shape $shape" ) );
	ok( 'FAIL' === $lvl, "shape $shape (missing target) is RT_MARKER_TARGET_MISSING, at FAIL level", $lvl );
}
fx_rrmdir( $r48 );

echo "--- D1'.1 test (i): a valid marker's payload words are excluded from the instruction-word count, both numbers printed ---\n";
/* Expected numbers are derived by running the SAME transformation framework-audit.php runs
   (strip the exact span text, then str_word_count), not hand-counted -- markdown asterisks,
   "## " headings and the boilerplate "Build gate:" line all add real words that a hand count
   silently forgets, which is exactly how this fixture's first draft mismatched by 12-23 words. */
$r49 = fx_tmp_root();
fx_base( $r49 );
$r49_lead    = 'Marks a real rule about something.';
$r49_payload = implode( ' ', array_fill( 0, 30, 'reason' ) );
$r49_span    = '(no verifier: ' . $r49_payload . ')';
$r49_bullet  = "- $r49_lead\n  $r49_span\n";
$r49_prose   = "Build gate: requires explicit **yes** before writing.\n\n"
	. "## Notes\n" . implode( ' ', array_fill( 0, 560, 'filler' ) ) . "\n\n"
	. "## Hard Rules\n" . $r49_bullet . $r49_bullet;
$r49_marker_words = str_word_count( $r49_span . ' ' . $r49_span );
$r49_instr_words   = str_word_count( strip_tags( str_replace( $r49_span, '', $r49_prose ) ) );
ok( str_word_count( strip_tags( $r49_prose ) ) > 600, 'the fixture is over 600 words BEFORE marker exclusion (proves exclusion is load-bearing)', $r49_prose );
ok( $r49_instr_words < 600, 'the fixture is under 600 instruction words AFTER marker exclusion', $r49_instr_words );
fx(
	$r49,
	'skills/wordpress-performance/SKILL.md',
	"---\nname: wordpress-performance\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\n" . $r49_prose
);
list( , $out49 ) = fx_run_ok( $audit, $r49 );
ok(
	has( $out49, $r49_instr_words . ' instruction words' ) && has( $out49, '+' . $r49_marker_words . ' marker' ),
	"a valid marker's words are excluded from the instruction count; both numbers are printed ($r49_instr_words + $r49_marker_words)",
	$out49
);
ok( ! has( $out49, 'RT_BODY_OVER_600' ), 'the fixture does not FAIL the 600 ceiling once marker words are excluded', $out49 );
fx_rrmdir( $r49 );

echo "--- D1'.1 test (ii): 601 instruction words with zero markers still FAILs -- the ceiling itself is unmoved ---\n";
$r50 = fx_tmp_root();
fx_base( $r50 );
fx(
	$r50,
	'skills/wordpress-performance/SKILL.md',
	"---\nname: wordpress-performance\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\n"
	. "Build gate: requires explicit **yes** before writing.\n\n"
	. "## Notes\n" . implode( ' ', array_fill( 0, 601, 'filler' ) ) . "\n\n"
	. "## Hard Rules\n- A normal rule with no marker at all.\n"
);
list( , $out50 ) = fx_run_ok( $audit, $r50 );
ok( has( $out50, 'RT_BODY_OVER_600' ), '601 instruction words with no marker to exclude still FAILs RT_BODY_OVER_600', $out50 );
fx_rrmdir( $r50 );

echo "--- D1'.1 test (iv): an UNCLOSED marker's words are counted, never stripped ---\n";
/* Same self-simulation technique as r49: the expected total is str_word_count() applied to the
   EXACT prose this fixture writes, not a hand sum -- an unclosed marker has no valid span to
   strip (marker_parse() never even reaches the point of building one when closed=false), so the
   fixture's own full-body count IS the expected printed number. */
$r51        = fx_tmp_root();
$r51_filler = 595;
$r51_prose  = "Build gate: requires explicit **yes** before writing.\n\n"
	. "## Notes\n" . implode( ' ', array_fill( 0, $r51_filler, 'filler' ) ) . "\n\n"
	. "## Hard Rules\n- A rule whose marker never closes its parenthesis.\n  (no verifier: " . implode( ' ', array_fill( 0, 9, 'unclosed' ) ) . "\n";
$r51_total  = str_word_count( strip_tags( $r51_prose ) );
fx_base( $r51 );
fx(
	$r51,
	'skills/elementor-core/SKILL.md',
	"---\nname: elementor-core\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\n" . $r51_prose
);
list( , $out51 ) = fx_run_ok( $audit, $r51 );
ok( $r51_total > 600, 'the fixture arithmetic really does cross the 600 ceiling', $r51_total );
ok( has( $out51, $r51_total . ' instruction words' ), "an unclosed marker's words are counted toward the total, never stripped ($r51_total expected)", $out51 );
ok( has( $out51, 'RT_BODY_OVER_600' ) && has( $out51, 'RT_MARKER_UNCLOSED' ), 'the unclosed marker both FAILs on its own and inflates the instruction-word count', $out51 );
fx_rrmdir( $r51 );

echo "--- --word-report prints skill<TAB>instruction_words<TAB>marker_words and exits before the FAIL/WARN/JUDGE summary ---\n";
$r52 = fx_tmp_root();
fx_base( $r52 );
fx_wc_skill( $r52, 'divi-core', "- A rule with a clean marker.\n  (no verifier: " . implode( ' ', array_fill( 0, 10, 'reason' ) ) . ")\n" );
list( $code52, $out52 ) = fx_run( $audit, $r52, array( '--word-report' ) );
ok( 0 === $code52, '--word-report exits 0', $code52 );
ok( 1 === preg_match( '/^divi-core\t\d+\t\d+$/m', $out52 ), '--word-report prints a tab-separated skill/instruction-words/marker-words line', $out52 );
ok( ! has( $out52, 'FAIL /' ), '--word-report does not also print the FAIL/WARN/JUDGE summary line', $out52 );
fx_rrmdir( $r52 );

echo "--- D1'.9: a write-capable skill with NO \"## Hard Rules\" section at all is RT_HARD_RULES_MISSING_WRITE, a FAIL, not a WARN ---\n";
$r53 = fx_tmp_root();
fx_base( $r53 );
fx(
	$r53,
	'skills/woocommerce/SKILL.md',
	"---\nname: woocommerce\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\n"
	. "Build gate: requires explicit **yes** before writing.\n\nFixture skill body with no Hard Rules section at all.\n"
);
list( $code53, $out53 ) = fx_run_ok( $audit, $r53 );
ok( has( $out53, 'RT_HARD_RULES_MISSING_WRITE' ), 'a write-capable skill with no "## Hard Rules" section is RT_HARD_RULES_MISSING_WRITE', $out53 );
ok( 1 === $code53, 'RT_HARD_RULES_MISSING_WRITE is a FAIL, exit code 1', $code53 );
fx_rrmdir( $r53 );

echo "--- D1'.9: a NON-write-capable skill with no Hard Rules stays RT_NO_HARD_RULES (WARN), the write-capable FAIL does not leak onto it ---\n";
$r54 = fx_tmp_root();
fx_base( $r54 );
list( $code54n, $out54 ) = fx_run_ok( $audit, $r54 );
ok( array() !== fx_lines_with( $out54, array( 'qa-review', 'RT_NO_HARD_RULES' ) ), 'a non-write-capable skill missing "## Hard Rules" stays RT_NO_HARD_RULES', $out54 );
ok( 0 === $code54n, 'RT_NO_HARD_RULES alone does not fail the build', $code54n );
fx_rrmdir( $r54 );

echo "--- D1'.1: a marker-shaped opener line OUTSIDE \"## Hard Rules\" is RT_MARKER_OUTSIDE_RULES (WARN), not silently free ---\n";
$r55 = fx_tmp_root();
fx_base( $r55 );
fx(
	$r55,
	'skills/elementor-core/SKILL.md',
	"---\nname: elementor-core\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\n"
	. "Build gate: requires explicit **yes** before writing.\n\n"
	. "A stray marker-shaped line sits here, outside any Hard Rules section:\n"
	. "(no verifier: this line looks like a marker but is not inside Hard Rules)\n\n"
	. "## Hard Rules\n- A normal rule with a real marker.\n  (no verifier: this one really is inside the rules section)\n"
);
list( , $out55 ) = fx_run_ok( $audit, $r55 );
ok( has( $out55, 'RT_MARKER_OUTSIDE_RULES' ), 'a marker-shaped opener line outside "## Hard Rules" is RT_MARKER_OUTSIDE_RULES', $out55 );
fx_rrmdir( $r55 );

echo "--- a shape-2 target that climbs OUT of the audited root never counts as existing ---\n";
/* The escaping target really exists on disk, one directory above the audited root, so this fails
   the moment containment is dropped rather than passing because nothing happened to be there. */
$r56       = fx_tmp_root();
$r56_inner = $r56 . '/inner';
fx_base( $r56_inner );
fx( $r56, 'outside/escaped.php', "<?php\n// really exists, but OUTSIDE the audited root\n" );
fx( $r56_inner, 'tests/in-root.php', "<?php\n// really exists, inside the audited root\n" );
fx_wc_skill( $r56_inner, 'woocommerce', "- Escaping bullet: its target climbs out of the audited tree.\n  (verifier: covered by `tests/../../outside/escaped.php` which really is there)\n- Neighbour bullet: the identical shape, staying inside the tree.\n  (verifier: covered by `tests/in-root.php` which really is there)\n" );
list( , $out56 ) = fx_run_ok( $audit, $r56_inner );
ok( 'FAIL' === fx_row_level( $out56, array( 'RT_MARKER_TARGET_MISSING', 'Escaping bullet' ) ), 'a shape-2 target that climbs out of the audited root is RT_MARKER_TARGET_MISSING at FAIL level -- existing on the host never counts as existing in the tree', fx_row_level( $out56, array( 'RT_MARKER_TARGET_MISSING', 'Escaping bullet' ) ) );
ok( array() === fx_lines_with( $out56, array( 'RT_MARKER_TARGET_MISSING', 'Neighbour bullet' ) ), 'the non-traversing twin still resolves, so containment did not break ordinary shape-2 resolution', $out56 );
fx_rrmdir( $r56 );

echo "--- the 40-word marker cap is enforced on EVERY skill, not only the write-capable ones ---\n";
/* The strip was uniform while the cap was not, so a knowledge skill got an unmetered region the
   budget subtracted and no check read. No RT_MARKER_OVERSIZE row is expected here -- the ROW is
   still write-capable-only -- the ceiling FAIL is what must appear. */
$r57 = fx_tmp_root();
fx_base( $r57 );
$r57_span = '(no verifier: ' . implode( ' ', array_fill( 0, 700, 'hidden' ) ) . ')';
fx( $r57, 'skills/web-templates/SKILL.md', "---\nname: web-templates\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\n## Hard Rules\n- A knowledge-skill rule whose marker tries to swallow the whole budget.\n  $r57_span\n" );
list( , $out57 ) = fx_run_ok( $audit, $r57 );
ok( array() !== fx_lines_with( $out57, array( 'RT_BODY_OVER_600', 'web-templates' ) ), 'an over-cap marker in a NON-write-capable skill is not excluded, so its words still FAIL the 600 ceiling', $out57 );
ok( array() === fx_lines_with( $out57, array( 'RT_MARKER_OVERSIZE', 'web-templates' ) ), 'the OVERSIZE row itself stays scoped to write-capable skills -- the cap is uniform, the row is not', $out57 );
fx_rrmdir( $r57 );

echo "--- a write-capable skill that states no Hard Rules FAILs however it says nothing ---\n";
/* Three ways to state no rules, all previously free: the heading alone at EOF, the heading with
   prose under it, and rules the "- " splitter cannot see. Each was cheaper than the escape this
   design priced, so each was the route a contributor under pressure would find. */
foreach ( array(
	'bare-heading' => '',
	'prose-only'   => 'We have no rules worth writing down here.',
	'star-bullets' => "* A rule the splitter cannot see.\n* A second one.",
) as $shape => $body ) {
	$r58 = fx_tmp_root();
	fx_base( $r58 );
	fx_wc_skill( $r58, 'divi-core', $body );
	list( $code58, $out58 ) = fx_run_ok( $audit, $r58 );
	ok( 'FAIL' === fx_row_level( $out58, array( 'RT_HARD_RULES_MISSING_WRITE', 'divi-core' ) ), "a write-capable skill whose Hard Rules section is $shape is RT_HARD_RULES_MISSING_WRITE, at FAIL level", fx_row_level( $out58, array( 'RT_HARD_RULES_MISSING_WRITE', 'divi-core' ) ) );
	ok( 1 === $code58, "stating no rules as $shape blocks the gate -- exit code 1", $code58 );
	fx_rrmdir( $r58 );
}

echo "--- the mislabel carve-out: a negated marker citing an EXISTING tests/ path is not a mislabel ---\n";
/* The exemption had no fixture, so deleting it left the suite green. The shape-4 twin in the same
   run proves the branch is still live, so the carve-out cannot widen unnoticed either. */
$r59 = fx_tmp_root();
fx_base( $r59 );
fx( $r59, 'tests/carve-out.php', "<?php\n// fixture, never executed\n" );
fx_wc_skill( $r59, 'elementor-core', "- Exempt bullet: names a real tests/ path only as context for the gap.\n  (no verifier: nothing runs this yet, closest is `tests/carve-out.php`)\n- Mislabelled bullet: names a step that really exists.\n  (no verifier: covered already by step-1 in this very skill)\n", "## Execution Steps\n1. The first real numbered step this marker can point at.\n" );
list( , $out59 ) = fx_run_ok( $audit, $r59 );
ok( array() === fx_lines_with( $out59, array( 'RT_MARKER_MISLABEL', 'Exempt bullet' ) ), 'a (no verifier: ...) citing an existing tests/ path is exempt from RT_MARKER_MISLABEL', $out59 );
ok( array() !== fx_lines_with( $out59, array( 'RT_MARKER_MISLABEL', 'Mislabelled bullet' ) ), 'the same run still reports the shape-4 mislabel, so the carve-out is narrow and the branch is live', $out59 );
fx_rrmdir( $r59 );

echo "--- an agent that states no House rules is RT_AGENT_NO_HOUSE_RULES, however it says nothing ---\n";
/* The orchestrator's House rules are the defaults every build inherits, so a violation there ships
   on every site rather than one. They were the last set of rules in the repo nothing read. */
foreach ( array(
	'no-section'   => "---\nname: orchestrator\n---\n\nRoutes to `woocommerce` for commerce.\n",
	'bare-heading' => "---\nname: orchestrator\n---\n\n## House rules\n",
	'prose-only'   => "---\nname: orchestrator\n---\n\n## House rules\nWe have no defaults; decide per build.\n",
) as $shape => $agent ) {
	$r60 = fx_tmp_root();
	fx_base( $r60 );
	fx( $r60, 'agents/orchestrator.md', $agent );
	list( $code60, $out60 ) = fx_run_ok( $audit, $r60 );
	ok( 'FAIL' === fx_row_level( $out60, array( 'RT_AGENT_NO_HOUSE_RULES', 'orchestrator' ) ), "an agent whose House rules are $shape is RT_AGENT_NO_HOUSE_RULES, at FAIL level", fx_row_level( $out60, array( 'RT_AGENT_NO_HOUSE_RULES', 'orchestrator' ) ) );
	ok( 1 === $code60, "an agent stating no House rules blocks the gate as $shape -- exit code 1", $code60 );
	fx_rrmdir( $r60 );
}

echo "--- the marker grammar really runs on an agent's House rules, not only on skills ---\n";
/* The heading match is loose after "House rules" on purpose: the real orchestrator's carries a
   parenthetical, and a rule that only fires on an exact string is one an author retitles their way
   out of by accident. This fixture proves the loose form is walked, not merely matched. */
$r61 = fx_tmp_root();
fx_base( $r61 );
fx(
	$r61,
	'agents/orchestrator.md',
	"---\nname: orchestrator\n---\n\n## House rules (defaults for every build)\n"
	. "- Marked bullet, routed to `woocommerce`.\n  (no verifier: a documented gap, stated so it cannot hide.)\n"
	. "- Unmarked bullet that names no verifier at all.\n"
);
list( , $out61 ) = fx_run_ok( $audit, $r61 );
ok( array() !== fx_lines_with( $out61, array( 'RT_MARKER_ABSENT', 'orchestrator', 'Unmarked bullet' ) ), 'an unmarked House rule in an agent is RT_MARKER_ABSENT, so the grammar reaches agents', $out61 );
ok( array() === fx_lines_with( $out61, array( 'RT_MARKER_ABSENT', 'Marked bullet' ) ), 'the marked House rule beside it raises nothing, so the walk reads each bullet separately', $out61 );
ok( array() === fx_lines_with( $out61, array( 'RT_AGENT_NO_HOUSE_RULES' ) ), 'a parenthesised "## House rules (…)" heading is found, not read as a missing section', $out61 );
fx_rrmdir( $r61 );

echo "--- D1'.4 shape 5: a marker may name one of this audit's own row types ---\n";
/* The House rule "a warning nobody reads is not a warning" is enforced by RT_ERRORLOG_NO_STDOUT
   and had no honest affirmative form until this shape existed -- the only alternatives were to
   name es_warn(), which IMPLEMENTS the rule rather than checking it, or to declare a gap that is
   not real. A row type the registry does not declare must cost the same as a missing function. */
$r62 = fx_tmp_root();
fx_base( $r62 );
fx_wc_skill(
	$r62,
	'woocommerce',
	"- Real row type: cites a check this audit genuinely declares.\n  (verifier: `RT_ERRORLOG_NO_STDOUT` FAILs an error_log call with no stdout channel beside it.)\n"
	. "- Phantom row type: cites one the registry never declared.\n  (verifier: `RT_NOT_A_DECLARED_ROW_TYPE` would catch it, if it existed at all.)\n"
	. "- Unquoted row type: names one only as prose, so it cites no shape at all.\n  (verifier: RT_ERRORLOG_NO_STDOUT is the sort of thing that would catch it one day.)\n"
);
list( $code62, $out62 ) = fx_run_ok( $audit, $r62 );
ok( array() === fx_lines_with( $out62, array( 'RT_MARKER', 'Real row type' ) ), 'a marker naming a declared row type resolves, no marker row', $out62 );
ok( 'FAIL' === fx_row_level( $out62, array( 'RT_MARKER_TARGET_MISSING', 'Phantom row type' ) ), 'a marker naming an undeclared row type is RT_MARKER_TARGET_MISSING, at FAIL level', fx_row_level( $out62, array( 'RT_MARKER_TARGET_MISSING', 'Phantom row type' ) ) );
ok( 1 === $code62, 'naming a row type that does not exist blocks the gate -- exit code 1', $code62 );
ok( array() !== fx_lines_with( $out62, array( 'RT_MARKER_PROSE_ONLY', 'Unquoted row type' ) ), 'an UNQUOTED row type cites no shape: the backticks are what make it a citation rather than prose', $out62 );
fx_rrmdir( $r62 );

echo "--- a row type MENTIONED in passing never steals resolution from the shape the marker means ---\n";
/* The row-type shape shipped first and unquoted, and that was a hole big enough to drive the gate
   through: first-match wins, an ID is legal prose, so a marker naming a verifier that does NOT
   exist resolved green the moment its sentence also mentioned any row type. The target-missing FAIL
   was never reached. Both halves are pinned here -- the steal, and the negated shape-2 carve-out
   inverting into a spurious mislabel -- because a fixture that only tests row-type-alone payloads
   is exactly what let this ship. */
$r63 = fx_tmp_root();
fx_base( $r63 );
fx( $r63, 'tests/neighbour.php', "<?php\n// fixture, never executed\n" );
fx_wc_skill(
	$r63,
	'woocommerce',
	"- Missing helper, with a real row type mentioned in passing.\n  (verifier: es_never_defined_at_all() enforces it, reported the way `RT_ORPHAN_FILE` reports strays.)\n"
	. "- Missing tests path, with a real row type mentioned in passing.\n  (verifier: see `tests/does-not-exist.php`, checked alongside `RT_ORPHAN_FILE` in the same run.)\n"
	. "- Declared gap citing a neighbour file and a row type.\n  (no verifier: nothing runs this yet, closest is `tests/neighbour.php`, unlike `RT_ORPHAN_FILE`.)\n"
);
list( $code63, $out63 ) = fx_run_ok( $audit, $r63 );
ok( 'FAIL' === fx_row_level( $out63, array( 'RT_MARKER_TARGET_MISSING', 'Missing helper' ) ), 'a passing row-type mention does not rescue a marker whose named helper is missing', fx_row_level( $out63, array( 'RT_MARKER_TARGET_MISSING', 'Missing helper' ) ) );
ok( 'FAIL' === fx_row_level( $out63, array( 'RT_MARKER_TARGET_MISSING', 'Missing tests path' ) ), 'a passing row-type mention does not rescue a marker whose named tests/ path is missing', fx_row_level( $out63, array( 'RT_MARKER_TARGET_MISSING', 'Missing tests path' ) ) );
ok( array() === fx_lines_with( $out63, array( 'RT_MARKER_MISLABEL', 'Declared gap' ) ), 'the shape-2 carve-out survives a row type in the same payload -- no spurious mislabel', $out63 );
ok( 1 === $code63, 'the masked target-missing rows still block the gate -- exit code 1', $code63 );
fx_rrmdir( $r63 );

echo "--- reachability: a file nothing routes to is RT_ORPHAN_FILE, at ANY depth ---\n";
/* The old check globbed ONE level and skipped directories, so the deepest corner of the repo was
   the one corner nothing looked at. Recursion alone would have been wrong in the other direction:
   almost nothing here is cited by its full filename, so 21 real files would have been flagged.
   This tree pins the whole model in one run -- a direct pointer, a directory pointer that reaches
   only DIRECT children, a family-prefix citation, a transitive citation through a reachable index,
   and the case that makes it honest: an index nobody can reach vouches for nothing. */
$r64 = fx_tmp_root();
fx_base( $r64 );
fx(
	$r64,
	'skills/web-templates/SKILL.md',
	"---\nname: web-templates\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\n"
	/* "see `pages/_README.md`" is the real orchestrating shape, and it is what makes the ambiguity
	   rule load-bearing here: the skill holds TWO files named _README.md, so an unqualified mention
	   of that basename must credit NEITHER on its own. */
	/* The bare "references/" and the bare "hidden/" are deliberate: the first is the prose shape
	   that used to whitelist the whole depth-1 layer, the second is a directory-qualified mention
	   of an ambiguous basename, which credits nothing. */
	. "Read `references/atlas.md` first. Everything else lives under `references/`.\n"
	/* The BARE `_README.md` is what arms the ambiguity rule: with two files of that name, an
	   unqualified mention must credit NEITHER. Written directory-qualified, the basename handle
	   could never match anyway once citations became boundary-anchored, and the rule went
	   unverified — a guard that survives its own removal is not a guard. */
	. "Page archetypes live under `references/pages/`. Both indexes are named `_README.md`; the one that counts is the first.\n"
	. "The quick notes are in `references/atlas-q.md`.\n\n"
	. "## Hard Rules\n- A knowledge-skill rule.\n  (no verifier: a fixture rule, here only so the skill states something.)\n"
);
fx( $r64, 'skills/web-templates/references/atlas.md', "# Atlas\n\nThe deep archetype is TPL-DEEP-01.\n" );
fx( $r64, 'skills/web-templates/references/deep/TPL-DEEP-01-first.md', "# TPL-DEEP-01\n" );
fx( $r64, 'skills/web-templates/references/pages/_README.md', "# Pages\n\nThe page archetype is TPL-PAGE-01.\n" );
fx( $r64, 'skills/web-templates/references/pages/one/TPL-PAGE-01-first.md', "# TPL-PAGE-01\n" );
fx( $r64, 'skills/web-templates/references/pages/one/TPL-PAGE-99-lost.md', "# TPL-PAGE-99\n" );
fx( $r64, 'skills/web-templates/references/hidden/_README.md', "# Hidden\n\nThe hidden archetype is TPL-HIDE-01.\n" );
fx( $r64, 'skills/web-templates/references/hidden/TPL-HIDE-01-first.md', "# TPL-HIDE-01\n" );
fx( $r64, 'skills/web-templates/references/stray.md', "# Stray\n" );
fx( $r64, 'skills/web-templates/references/name.md', "# Name\n" );
fx( $r64, 'skills/web-templates/references/loose/one.md', "# One\n" );
fx( $r64, 'skills/web-templates/references/atlas-q.md', "# Atlas Q\n" );
fx( $r64, 'skills/web-templates/references/q.md', "# Q\n" );
list( , $out64 ) = fx_run_ok( $audit, $r64 );
$fx_orphan = function ( $path ) use ( $out64 ) {
	return array() !== fx_lines_with( $out64, array( 'RT_ORPHAN_FILE', $path ) );
};
ok( ! $fx_orphan( 'references/atlas.md' ), 'a file SKILL.md names directly is reachable', $out64 );
ok( ! $fx_orphan( 'references/deep/TPL-DEEP-01-first.md' ), 'a deep file cited by FAMILY prefix from a reachable file is reachable -- recursion alone would have flagged it', $out64 );
ok( ! $fx_orphan( 'references/pages/_README.md' ), 'a directory pointer reaches that directory\'s DIRECT children', $out64 );
ok( ! $fx_orphan( 'references/pages/one/TPL-PAGE-01-first.md' ), 'a file two levels below the pointer is reachable through the index that lists it', $out64 );
ok( 'WARN' === fx_row_level( $out64, array( 'RT_ORPHAN_FILE', 'references/pages/one/TPL-PAGE-99-lost.md' ) ), 'a deep file NO index lists is RT_ORPHAN_FILE, at WARN level -- the row the old one-level glob could not see', fx_row_level( $out64, array( 'RT_ORPHAN_FILE', 'references/pages/one/TPL-PAGE-99-lost.md' ) ) );
ok( $fx_orphan( 'references/hidden/_README.md' ), 'an index nobody points at is itself an orphan, even though SKILL.md names that basename for its SIBLING -- an ambiguous basename credits neither file unqualified', $out64 );
ok( $fx_orphan( 'references/hidden/TPL-HIDE-01-first.md' ), 'and it vouches for nothing: a file listed ONLY by an unreachable index stays an orphan', $out64 );
ok( $fx_orphan( 'references/stray.md' ), 'a depth-1 file nobody mentions is still an orphan -- the old check\'s one real job survives', $out64 );
/* Both of these were laundered in the first cut, and the fixture above could not see either: its
   one depth-1 file was named "stray", a word appearing in no reachable text, and both of its
   directory mentions continued into a path. Measured, the depth-1 detection rate had fallen from
   59 of 60 to 29 of 60 with the whole suite green. A guard that survives a 30-case regression is
   not a guard, so the two laundering shapes get a file each. */
ok( $fx_orphan( 'references/name.md' ), 'a common STEM is not a citation: "name" appears in every SKILL.md\'s own frontmatter, and the file is still an orphan', $out64 );
ok( $fx_orphan( 'references/loose/one.md' ), 'a bare "references/" in prose is not a pointer at the skill root, so a file under it is still an orphan', $out64 );
ok( ! $fx_orphan( 'references/atlas-q.md' ), 'the longer filename SKILL.md really cites is reachable', $out64 );
ok( $fx_orphan( 'references/q.md' ), 'a citation BEGINS where a filename begins: "atlas-q.md" does not credit "q.md"', $out64 );
fx_rrmdir( $r64 );

/* The coverage assertion is the anti-pattern mechanism itself (design D1'.6), closing the exact
   CRITICAL shape that sank both prior slices of this change: a check added with no fixture
   exercising it. "Observed" means the ID appeared SOMEWHERE in some fixture's --row-types output
   above — fx_track_ids() harvests every scenario's run, so a row type firing only incidentally
   (never behind its own dedicated assertion) still counts, deliberately: RT_ORPHAN_FILE is real
   coverage even though no scenario above asserts on it directly. */
/* RT_HELPER_UNROUTABLE was OBSERVED by other fixtures the moment it shipped — several of them drop
   a .php under assets/ with a function no markdown names — so the coverage ratchet went green
   without anyone pinning what the check actually decides. Incidental coverage proves a row can
   fire; it proves nothing about the three things that must NOT fire it, and those carve-outs are
   where a check like this rots into either noise or silence. */
echo "--- a helper nothing can invoke is RT_HELPER_UNROUTABLE, and three things are not ---\n";
$r70 = fx_tmp_root();
fx_base( $r70 );
fx_wc_skill( $r70, 'woocommerce', "- A rule.\n  (verifier: `tests/test-write-path.php` covers it.)\n", "\n## References\n- `assets/lib.php`\n- `assets/demo.example.php`\n- `references/api.md`\n" );
fx(
	$r70,
	'skills/woocommerce/assets/lib.php',
	"<?php\n"
	. "function fx_orphan_helper( \$a ) {\n\treturn \$a;\n}\n"
	. "function fx_called_helper( \$a ) {\n\treturn \$a;\n}\n"
	. "function fx_documented_helper( \$a ) {\n\treturn \$a;\n}\n"
	. "function fx_entry() {\n\treturn fx_called_helper( 1 );\n}\n"
	. "/* fx_orphan_helper() used to be called from here. */\n"
);
fx( $r70, 'skills/woocommerce/assets/demo.example.php', "<?php\nfunction fx_example_build() {\n\treturn 1;\n}\n" );
fx( $r70, 'skills/woocommerce/references/api.md', "# API\n\nCall `fx_documented_helper()` from the build. `fx_entry()` starts it.\n" );
list( , $out70 ) = fx_run_ok( $audit, $r70 );
$fx_unroutable   = function ( $fn ) use ( $out70 ) {
	return array() !== fx_lines_with( $out70, array( 'RT_HELPER_UNROUTABLE', $fn . '()' ) );
};
ok( 'WARN' === fx_row_level( $out70, array( 'RT_HELPER_UNROUTABLE', 'fx_orphan_helper()' ) ), 'a function no asset calls and no .md names is RT_HELPER_UNROUTABLE at WARN', fx_row_level( $out70, array( 'RT_HELPER_UNROUTABLE', 'fx_orphan_helper()' ) ) );
ok( ! $fx_unroutable( 'fx_called_helper' ), 'a function another asset CALLS is routable — it needs no pointer', $out70 );
ok( ! $fx_unroutable( 'fx_documented_helper' ), 'a function only a .md names is routable: naming it IS the wiring', $out70 );
ok( ! $fx_unroutable( 'fx_example_build' ), 'a *.example.php build function is exempt — an example is copied and rewritten, not called', $out70 );
/* The comment on the last line of lib.php names fx_orphan_helper() with parens. If a comment
   counted as a call the row above would vanish, and every forgotten helper with a docblock
   mentioning it would read as wired. */
ok( $fx_unroutable( 'fx_orphan_helper' ), 'a COMMENT naming the function is not a call — the row survives it', $out70 );
/* A checkout of another branch under .worktrees/ is not this tree. Crediting it would make the row
   go quiet exactly when a helper is being removed here and only the old copy still names it. */
fx( $r70, '.worktrees/otra/skills/woocommerce/references/api.md', "# API de otra rama\n\n`fx_orphan_helper()` sigue documentada aqui.\n" );
list( , $out71 ) = fx_run_ok( $audit, $r70 );
ok( array() !== fx_lines_with( $out71, array( 'RT_HELPER_UNROUTABLE', 'fx_orphan_helper()' ) ), 'markdown under a hidden directory (.worktrees) does not make a helper reachable', $out71 );
/* The same hole one directory over, and worse. `openspec/` is not dot-prefixed, so the filter
   above walks it: an in-flight proposal, design or tasks file naming a helper would credit it as
   routable. Planning prose describes helpers that do NOT exist yet or were never wired, so a
   change still deciding whether to keep a helper would silence the one row that says it is
   unreachable. Only DURABLE surfaces route: skills/, agents/, docs/, CONTRIBUTING.md, README.md. */
fx( $r70, 'openspec/changes/fx-cambio/proposal.md', "# Proposal\n\nWire `fx_orphan_helper()` into the build.\n" );
list( , $out72 ) = fx_run_ok( $audit, $r70 );
ok( array() !== fx_lines_with( $out72, array( 'RT_HELPER_UNROUTABLE', 'fx_orphan_helper()' ) ), 'markdown under openspec/ (in-flight planning prose) does not make a helper reachable', $out72 );
fx_rrmdir( $r70 );

/* ---------------------------------------------------------------------------
   RT_MOCKUP_BLEED_FIXED_BAND — a fixed content band is only a defect NEXT TO A BLEED.

   Two arms, so the three scenarios below are one FAIL and two controls. The obvious wrong
   implementation — "a fixed --content-width is bad" — turns every LP-CENTERED and LP-STRICT-GRID
   mockup in the framework red, and those are correct: they cap the band and centre it and never
   bleed. What makes a fixed band a defect is `full-end`, because the named-line grid must sum to
   the VIEWPORT, so capping the twelve columns leaves the outer `1fr` gutter as the only track that
   can absorb a wider screen and a `1fr` has no ceiling. Measured on the retired gallery at
   1140px: 150.1 / 390.1 / 710.1px of dead margin at 1440 / 1920 / 2560.
   --------------------------------------------------------------------------- */

echo "--- una maqueta que sangra a full-end con --content-width fijo FALLA ---\n";
$r101b = fx_tmp_root();
fx_base( $r101b );
fx( $r101b, FX_MAQUETA, fx_mockup( array(), array( 'width' => '1140px', 'bleed' => true ) ) );
list( , $out101b ) = fx_run_ok( $audit, $r101b );
ok( 'FAIL' === fx_row_level( $out101b, array( 'RT_MOCKUP_BLEED_FIXED_BAND' ) ), 'banda fija junto a un sangrado FALLA', fx_row_level( $out101b, array( 'RT_MOCKUP_BLEED_FIXED_BAND' ) ) );
ok( array() !== fx_lines_with( $out101b, array( 'RT_MOCKUP_BLEED_FIXED_BAND', '1140px' ) ), 'y cita el valor fijo que encontro', $out101b );
ok( array() !== fx_lines_with( $out101b, array( 'RT_MOCKUP_BLEED_FIXED_BAND', 'plantillas/fx/maqueta/index.html' ) ), 'y nombra el fichero', $out101b );
fx_rrmdir( $r101b );

echo "--- la MISMA banda fija SIN sangrado no produce fila: LP-CENTERED es correcto ---\n";
$r101c = fx_tmp_root();
fx_base( $r101c );
/* The control that stops this row becoming "fixed width is bad". Byte-identical to the scenario
   above except that nothing says `full-end`. If it ever goes red the row has stopped reading the
   bleed and started reading the token alone, and half the framework fails with it. */
fx( $r101c, FX_MAQUETA, fx_mockup( array(), array( 'width' => '1140px' ) ) );
list( $code101c, $out101c ) = fx_run_ok( $audit, $r101c );
ok( array() === fx_lines_with( $out101c, array( 'RT_MOCKUP_BLEED_FIXED_BAND' ) ), 'sin full-end una banda fija no produce fila', $out101c );
ok( 0 === $code101c, 'y el arbol sale con codigo 0', $code101c );
fx_rrmdir( $r101c );

echo "--- sangrado con banda FLUIDA tampoco produce fila ---\n";
$r101d = fx_tmp_root();
fx_base( $r101d );
/* The other control, and the one proving the row reads the VALUE rather than the presence of the
   token: same bleed as the failing scenario, same declaration, only the value moves. */
fx( $r101d, FX_MAQUETA, fx_mockup( array(), array( 'width' => 'clamp(1140px, calc(1140px + (100vw - 1280px) * 0.5), 1600px)', 'bleed' => true ) ) );
list( $code101d, $out101d ) = fx_run_ok( $audit, $r101d );
ok( array() === fx_lines_with( $out101d, array( 'RT_MOCKUP_BLEED_FIXED_BAND' ) ), 'una banda fluida junto a un sangrado no produce fila', $out101d );
ok( 0 === $code101d, 'y el arbol sale con codigo 0', $code101d );
fx_rrmdir( $r101d );

/* ---------------------------------------------------------------------------
   RT_MOCKUP_BLEED_NOT_MEDIA — WHAT reaches the glass, where FIXED_BAND above reads only the
   relationship between the band and a bleed and is blind to the element.

   The three defects this row exists for all shipped with FIXED_BAND green: card rows at
   `c 1 / full-end` whose last card was half bled (frame right 2000.0, body ink 1968.0), a copy
   block claiming a gutter its ink never entered, and — the one that made the page unusable —
   `.band .formwrap`, a FORM, whose submit button's right border sat on x=2560.0 with a 1453.3px
   name input beside it. `scrollWidth === clientWidth` throughout all three.

   The controls matter as much as the failure. "Anything at full-end is bad" turns every bleeding
   mockup in the framework red and deletes the blueprint; "only `.media` literally" fails the day
   someone writes `.hero .frame`. So: one non-media FAIL, two media controls, one unknown-subject
   FAIL proving the row fails CLOSED, one comment control, and one mixed selector list proving the
   message names the offender and not its innocent neighbour.
   --------------------------------------------------------------------------- */

echo "--- un FORMULARIO enviado a full-end FALLA, y la fila lo nombra ---\n";
$r101e = fx_tmp_root();
fx_base( $r101e );
fx( $r101e, FX_MAQUETA, fx_mockup( array(), array( 'bleed' => true, 'sel' => '.band .formwrap' ) ) );
list( , $out101e ) = fx_run_ok( $audit, $r101e );
ok( 'FAIL' === fx_row_level( $out101e, array( 'RT_MOCKUP_BLEED_NOT_MEDIA' ) ), 'un .formwrap contra el cristal FALLA', fx_row_level( $out101e, array( 'RT_MOCKUP_BLEED_NOT_MEDIA' ) ) );
ok( array() !== fx_lines_with( $out101e, array( 'RT_MOCKUP_BLEED_NOT_MEDIA', '.band .formwrap' ) ), 'y cita el selector culpable', $out101e );
ok( array() !== fx_lines_with( $out101e, array( 'RT_MOCKUP_BLEED_NOT_MEDIA', 'plantillas/fx/maqueta/index.html' ) ), 'y nombra el fichero', $out101e );
fx_rrmdir( $r101e );

echo "--- .hero .media contra el cristal NO produce fila: eso es el plano funcionando ---\n";
$r101f = fx_tmp_root();
fx_base( $r101f );
/* The control that stops this row deleting the blueprint. Byte-identical to the scenario above
   except for the subject of the declaration. */
fx( $r101f, FX_MAQUETA, fx_mockup( array(), array( 'bleed' => true ) ) );
list( $code101f, $out101f ) = fx_run_ok( $audit, $r101f );
ok( array() === fx_lines_with( $out101f, array( 'RT_MOCKUP_BLEED_NOT_MEDIA' ) ), 'una imagen sangrando no produce fila', $out101f );
ok( 0 === $code101f, 'y el arbol sale con codigo 0', $code101f );
fx_rrmdir( $r101f );

/* ---- Las tres formas de equivocarse con una lista desplegable ----
 *
 * La regla es una: construida con `<details>` y exactamente la PRIMERA fila abierta. Se escribio
 * DESPUES del drift, no antes: mockup-guide.md llevaba desde el principio pidiendo
 * `<details>/<summary>` para COMP-FAQ y habia CUATRO emisores con TRES comportamientos, uno de
 * ellos pintando `<div class="qa"><h3>` con todas las respuestas permanentemente en pantalla.
 * Cada causa es una edicion distinta, asi que cada una tiene su mensaje y su asercion.
 */
echo "--- una lista desplegable que no abre ninguna fila FALLA ---\n";
$r133 = fx_tmp_root();
fx_base( $r133 );
fx( $r133, FX_MAQUETA,
	str_replace( '</style>', "</style>\n<div class=\"faq\"><details><summary>A</summary><p>a</p></details>\n"
		. "<details><summary>B</summary><p>b</p></details></div>", fx_mockup() ) );
list( , $out133 ) = fx_run_ok( $audit, $r133 );
ok( 'FAIL' === fx_row_level( $out133, array( 'RT_MOCKUP_DISCLOSURE_STATE', 'open no row' ) ), 'una lista con todas las filas cerradas FALLA', fx_row_level( $out133, array( 'RT_MOCKUP_DISCLOSURE_STATE', 'open no row' ) ) );
fx_rrmdir( $r133 );

echo "--- y una que abre mas de una tambien ---\n";
$r134 = fx_tmp_root();
fx_base( $r134 );
fx( $r134, FX_MAQUETA,
	str_replace( '</style>', "</style>\n<div class=\"faq\"><details open><summary>A</summary><p>a</p></details>\n"
		. "<details open><summary>B</summary><p>b</p></details></div>", fx_mockup() ) );
list( , $out134 ) = fx_run_ok( $audit, $r134 );
ok( 'FAIL' === fx_row_level( $out134, array( 'RT_MOCKUP_DISCLOSURE_STATE', 'more than one row' ) ), 'una lista con dos filas abiertas FALLA: no es un acordeon, es una pagina larga disfrazada de corta', fx_row_level( $out134, array( 'RT_MOCKUP_DISCLOSURE_STATE', 'more than one row' ) ) );
fx_rrmdir( $r134 );

echo "--- y una seccion FAQ sin ningun <details> es la tercera causa ---\n";
$r135 = fx_tmp_root();
fx_base( $r135 );
fx( $r135, FX_MAQUETA,
	str_replace( '</style>', "</style>\n<section class=\"sec faq\"><div class=\"qa\"><h3>A</h3><p>a</p></div>"
		. "<div class=\"qa\"><h3>B</h3><p>b</p></div></section>", fx_mockup() ) );
list( , $out135 ) = fx_run_ok( $audit, $r135 );
ok( 'FAIL' === fx_row_level( $out135, array( 'RT_MOCKUP_DISCLOSURE_STATE', 'no <details> at all' ) ), 'una seccion FAQ que no es un desplegable en absoluto FALLA', fx_row_level( $out135, array( 'RT_MOCKUP_DISCLOSURE_STATE', 'no <details> at all' ) ) );
fx_rrmdir( $r135 );

/* DEUDA CONOCIDA, con nombre y apellidos y no una excepcion generica: `corte` se sello antes de que
   esta fila mirase las maquetas, y su FAQ lleva las tres respuestas a la vista. Arreglarla cambia
   los bytes sellados y exige volver a juzgarla, asi que la fila avisa en WARN -- visible en cada
   pasada -- solo para ESE fichero. Cualquier otra Plantilla sigue en FAIL (arriba), y la entrada
   se borra del auditor el dia que corte se rehaga. */
echo "--- la FAQ plana de la Plantilla `corte`, deuda conocida, avisa en WARN y no bloquea ---\n";
$r135b = fx_tmp_root();
fx_base( $r135b );
fx( $r135b, 'skills/web-templates/references/plantillas/corte/maqueta/index.html',
	str_replace( '</style>', "</style>\n<section class=\"band-alt bloque-faq\"><div class=\"faq-q\">A</div>"
		. "<div class=\"faq-q\">B</div></section>", fx_mockup() ) );
list( $code135b, $out135b ) = fx_run_ok( $audit, $r135b );
ok( 'WARN' === fx_row_level( $out135b, array( 'RT_MOCKUP_DISCLOSURE_STATE', 'plantillas/corte/maqueta/index.html', 'no <details> at all' ) ), 'la FAQ plana de corte avisa en WARN, no en FAIL', fx_row_level( $out135b, array( 'RT_MOCKUP_DISCLOSURE_STATE', 'plantillas/corte/maqueta/index.html', 'no <details> at all' ) ) );
ok( array() !== fx_lines_with( $out135b, array( 'RT_MOCKUP_DISCLOSURE_STATE', 'known debt' ) ), 'y el aviso dice que es deuda conocida y por que no se arregla aqui', $out135b );
ok( 0 === $code135b, 'y no rompe el gate', $code135b );
fx_rrmdir( $r135b );

/* Y el control negativo, que es el que impide que la fila se vuelva ruido: UN solo `<details>` no
   es una lista y no se juzga. Es la forma de `.handoff`, el bloque de spec, que empieza cerrado a
   proposito porque nada de lo que hay dentro merece la primera mirada del lector. */
echo "--- pero un <details> suelto no es una lista y no se juzga ---\n";
$r136 = fx_tmp_root();
fx_base( $r136 );
fx( $r136, FX_MAQUETA,
	str_replace( '</style>', "</style>\n<details class=\"handoff\"><summary>Spec</summary><p>x</p></details>", fx_mockup() ) );
list( , $out136 ) = fx_run_ok( $audit, $r136 );
ok( array() === fx_lines_with( $out136, array( 'RT_MOCKUP_DISCLOSURE_STATE' ) ), 'un desplegable unico y cerrado no produce fila', $out136 );
fx_rrmdir( $r136 );

/* ---- `auto-fill` reserva una columna para lo que no existe ----
 *
 * Las dos grafías se leen igual en una hoja de estilos y no lo son: `auto-fill` crea todas las
 * columnas que CABEN, `auto-fit` colapsa las vacías. Tres retratos en un canvas donde caben cuatro
 * se rinden, bajo `auto-fill`, como tres tarjetas apretadas a la izquierda y un cuarto de sección
 * vacío — que es lo que el lector rodeó en rojo. No es una prohibición sino una exigencia de
 * justificación, porque hay sitios donde la pista vacía ES el objetivo.
 */
echo "--- una rejilla con auto-fill sin justificar FALLA ---\n";
$r137 = fx_tmp_root();
fx_base( $r137 );
fx( $r137, FX_MAQUETA,
	str_replace( '</style>', ".team{display:grid;grid-template-columns:repeat(auto-fill,minmax(15rem,1fr))}\n</style>", fx_mockup() ) );
list( , $out137 ) = fx_run_ok( $audit, $r137 );
ok( 'FAIL' === fx_row_level( $out137, array( 'RT_MOCKUP_GRID_AUTOFILL', 'plantillas/fx/maqueta/index.html' ) ), 'auto-fill sin justificacion al lado FALLA', fx_row_level( $out137, array( 'RT_MOCKUP_GRID_AUTOFILL', 'plantillas/fx/maqueta/index.html' ) ) );
fx_rrmdir( $r137 );

/* Los dos controles negativos, que son los que impiden que la fila se vuelva un tic: la grafia
   correcta no dispara, y la incorrecta CON su razon escrita tampoco. */
echo "--- pero auto-fit no, y auto-fill justificado tampoco ---\n";
$r138 = fx_tmp_root();
fx_base( $r138 );
fx( $r138, FX_MAQUETA,
	str_replace( '</style>', ".team{display:grid;grid-template-columns:repeat(auto-fit,minmax(15rem,1fr))}\n</style>", fx_mockup() ) );
list( , $out138 ) = fx_run_ok( $audit, $r138 );
ok( array() === fx_lines_with( $out138, array( 'RT_MOCKUP_GRID_AUTOFILL' ) ), 'auto-fit no produce fila', $out138 );
fx_rrmdir( $r138 );

$r139 = fx_tmp_root();
fx_base( $r139 );
fx( $r139, FX_MAQUETA,
	str_replace( '</style>', "/* auto-fill: el hueco es el objetivo, esto es un calendario */\n"
		. ".cal{display:grid;grid-template-columns:repeat(auto-fill,minmax(3rem,1fr))}\n</style>", fx_mockup() ) );
list( , $out139 ) = fx_run_ok( $audit, $r139 );
ok( array() === fx_lines_with( $out139, array( 'RT_MOCKUP_GRID_AUTOFILL' ) ), 'auto-fill CON su razon escrita al lado no produce fila: la fila pide una justificacion, no prohibe', $out139 );
fx_rrmdir( $r139 );

echo "--- .hero .frame tampoco: la lista blanca es de MEDIA, no de un literal ---\n";
$r101g = fx_tmp_root();
fx_base( $r101g );
fx( $r101g, FX_MAQUETA, fx_mockup( array(), array( 'bleed' => true, 'sel' => '.hero .frame' ) ) );
list( $code101g, $out101g ) = fx_run_ok( $audit, $r101g );
ok( array() === fx_lines_with( $out101g, array( 'RT_MOCKUP_BLEED_NOT_MEDIA' ) ), 'otro nombre de media tampoco produce fila', $out101g );
ok( 0 === $code101g, 'y el arbol sale con codigo 0', $code101g );
fx_rrmdir( $r101g );

echo "--- un sujeto DESCONOCIDO FALLA: la fila cierra en rojo, no en silencio ---\n";
$r101h = fx_tmp_root();
fx_base( $r101h );
/* The arm that decides whether this row is worth having. A whitelist that skips what it has not
   seen is the same blind spot as FIXED_BAND one level up: the next wrapper nobody thought of goes
   to the glass and the audit stays green. `.mystery-box` is not media and is not known, and both
   facts must produce the same verdict. */
fx( $r101h, FX_MAQUETA, fx_mockup( array(), array( 'bleed' => true, 'sel' => '.hero .mystery-box' ) ) );
list( , $out101h ) = fx_run_ok( $audit, $r101h );
ok( 'FAIL' === fx_row_level( $out101h, array( 'RT_MOCKUP_BLEED_NOT_MEDIA' ) ), 'un sujeto que la lista no reconoce FALLA', fx_row_level( $out101h, array( 'RT_MOCKUP_BLEED_NOT_MEDIA' ) ) );
ok( array() !== fx_lines_with( $out101h, array( 'RT_MOCKUP_BLEED_NOT_MEDIA', '.mystery-box' ) ), 'y lo nombra en vez de callarselo', $out101h );
fx_rrmdir( $r101h );

echo "--- `full-end` dentro de un COMENTARIO no es un sangrado ---\n";
$r101i = fx_tmp_root();
fx_base( $r101i );
/* The regression the first implementation of this row shipped: comments stripped AFTER splitting
   the selector list on `,`, so the prose above a rule — which contains commas — was reported as
   two offending selectors. The message was unreadable, and a check nobody can act on is a check
   nobody acts on. The comment here carries both `full-end` and two commas. */
fx( $r101i, FX_MAQUETA, fx_mockup( array(), array( 'bleed' => true, 'comment' => true ) ) );
list( $code101i, $out101i ) = fx_run_ok( $audit, $r101i );
ok( array() === fx_lines_with( $out101i, array( 'RT_MOCKUP_BLEED_NOT_MEDIA' ) ), 'prosa citando full-end no produce fila', $out101i );
ok( 0 === $code101i, 'y el arbol sale con codigo 0', $code101i );
fx_rrmdir( $r101i );

echo "--- en una lista de selectores, la fila nombra al culpable y no a su vecino ---\n";
$r101j = fx_tmp_root();
fx_base( $r101j );
fx( $r101j, FX_MAQUETA, fx_mockup( array(), array( 'bleed' => true, 'sel' => '.hero .media,' . "\n" . '  .cases .items' ) ) );
list( , $out101j ) = fx_run_ok( $audit, $r101j );
ok( 'FAIL' === fx_row_level( $out101j, array( 'RT_MOCKUP_BLEED_NOT_MEDIA' ) ), 'una lista con un no-media FALLA', fx_row_level( $out101j, array( 'RT_MOCKUP_BLEED_NOT_MEDIA' ) ) );
ok( array() !== fx_lines_with( $out101j, array( 'RT_MOCKUP_BLEED_NOT_MEDIA', '.cases .items' ) ), 'y nombra la tarjeta', $out101j );
ok( array() === fx_lines_with( $out101j, array( 'RT_MOCKUP_BLEED_NOT_MEDIA', '.hero .media`' ) ), 'y NO nombra la imagen inocente de la misma regla', $out101j );
fx_rrmdir( $r101j );

/* ---------------------------------------------------------------------------
   RT_MOCKUP_FONT_NOT_EMBEDDED — a declared typeface nobody serves.

   THE BUG THIS BAND EXISTS FOR SHIPPED, and every row above was green while it did. All four
   production mockups named real families — Fraunces, Inter Tight, Archivo Expanded, Instrument
   Serif, DM Sans, Source Sans 3 — and embedded none of them, and none of the six is installed on
   an ordinary machine. So `'Fraunces', Georgia, serif` rendered GEORGIA. The axis rows could not
   see it, because a token chain resolves perfectly into a face the machine does not have: the
   file declares five axes correctly and still renders a typeface nobody chose.

   The scenarios below kill four distinct mutants, which is why there are six of them rather than
   one pair:
     - dropping the `data:` arm      (r107: a URL satisfies "is there a face?" and is CSP-blocked)
     - reading only the first family (r108: quoted FALLBACKS must never be accused)
     - reading only `:root`          (r109: a `font-family` in a media query is a real request)
     - accusing generic keywords     (r110: `system-ui, sans-serif` names no file and never can)
   ------------------------------------------------------------------------ */

echo "--- una maqueta que NOMBRA una familia y no la incrusta FALLA ---\n";
$r106 = fx_tmp_root();
fx_base( $r106 );
fx(
	$r106,
	FX_MAQUETA,
	fx_mockup( array( 'stack' => "'Fraunces', Georgia, 'Times New Roman', serif" ) )
);
list( , $out106 ) = fx_run_ok( $audit, $r106 );
ok( 'FAIL' === fx_row_level( $out106, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED' ) ), 'nombrar una familia sin @font-face FALLA', fx_row_level( $out106, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED' ) ) );
ok( array() !== fx_lines_with( $out106, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED', 'Fraunces' ) ), 'y nombra la familia que no se sirve', $out106 );
/* Naming the FALLBACK would send the reader to delete Georgia, which is the one part that is
   doing its job. */
ok( array() === fx_lines_with( $out106, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED', 'Georgia' ) ), 'y no acusa al fallback declarado', $out106 );
fx_rrmdir( $r106 );

echo "--- incrustada como data: URI NO produce fila ---\n";
$r106b = fx_tmp_root();
fx_base( $r106b );
fx(
	$r106b,
	FX_MAQUETA,
	fx_mockup(
		array(
			'stack' => "'Fraunces', Georgia, serif",
			'faces' => array( 'Fraunces' => 'data:font/woff2;base64,d09GMgABAAAAAA' ),
		)
	)
);
list( $code106b, $out106b ) = fx_run_ok( $audit, $r106b );
ok( array() === fx_lines_with( $out106b, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED' ) ), 'una familia incrustada no produce fila', $out106b );
ok( 0 === $code106b, 'y el arbol conforme sale con codigo 0', $code106b );
fx_rrmdir( $r106b );

echo "--- @font-face que apunta a una URL FALLA: el CSP del Artifact la bloquea ---\n";
$r107 = fx_tmp_root();
fx_base( $r107 );
/* THE MUTANT THIS KILLS: drop the `data:` half of the check and this fixture passes. A face
   served from `https://fonts.gstatic.com/…` answers "does this family have an @font-face?" with
   yes, and then never arrives, because the Artifact CSP blocks the request — leaving the reader
   looking at the same Georgia they would have seen with no @font-face at all. This is the exact
   shape the old head comments in the four mockups warned about, and they were right about it;
   their mistake was concluding that @font-face itself had to go. */
fx(
	$r107,
	FX_MAQUETA,
	fx_mockup(
		array(
			'stack' => "'Fraunces', Georgia, serif",
			'faces' => array( 'Fraunces' => 'https://fonts.gstatic.com/s/fraunces/v38/x.woff2' ),
		)
	)
);
list( , $out107 ) = fx_run_ok( $audit, $r107 );
ok( 'FAIL' === fx_row_level( $out107, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED' ) ), 'una @font-face con URL FALLA', fx_row_level( $out107, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED' ) ) );
ok( array() !== fx_lines_with( $out107, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED', 'data: URI' ) ), 'y dice que el CSP bloquea la peticion', $out107 );
fx_rrmdir( $r107 );

echo "--- los fallbacks ENTRECOMILLADOS no se acusan: manda la POSICION, no las comillas ---\n";
$r108 = fx_tmp_root();
fx_base( $r108 );
/* THE MUTANT THIS KILLS: check every family in the stack instead of the first. `'Segoe UI'`,
   `'Helvetica Neue'` and `'Arial Black'` are quoted exactly like `'Source Sans 3'`, so quoting
   cannot separate a request from a fallback — position does, and it is the stack's own semantics:
   `font-family: A, B, C` means "A, else B". Under the mutant this fixture produces three extra
   rows demanding that Segoe UI be embedded, and the row becomes noise nobody reads. */
fx(
	$r108,
	FX_MAQUETA,
	fx_mockup(
		array(
			'stack' => "'Source Sans 3',system-ui,-apple-system,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif",
			'faces' => array( 'Source Sans 3' => 'data:font/woff2;base64,d09GMgABAAAAAA' ),
		)
	)
);
list( $code108, $out108 ) = fx_run_ok( $audit, $r108 );
ok( array() === fx_lines_with( $out108, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED' ) ), 'un stack con fallbacks entrecomillados no produce fila', $out108 );
ok( array() === fx_lines_with( $out108, array( 'Segoe UI' ) ), 'y en ninguna fila aparece Segoe UI', $out108 );
ok( 0 === $code108, 'y el arbol sale con codigo 0', $code108 );
fx_rrmdir( $r108 );

echo "--- una font-family FUERA de :root tambien cuenta: la maqueta FALLA ---\n";
$r109 = fx_tmp_root();
fx_base( $r109 );
/* THE MUTANT THIS KILLS: scope the font scan to `:root` the way the axis scan is scoped. The two
   are opposite shapes and this suite has to say so. An axis token is DECLARED once and USED
   everywhere, so reading the whole file would count a use as a declaration — that is why
   RT_MOCKUP_NO_AXES reads `:root` only. A `font-family` inside a media query is not a use of
   anything: it is a fresh request the browser will really try to serve, and a `:root`-only reader
   would call this file clean while it renders Arial Black at every width over 768px. */
fx(
	$r109,
	FX_MAQUETA,
	fx_mockup(
		array(
			'stack' => "'Source Sans 3', system-ui, sans-serif",
			'rule'  => "'Archivo Expanded', 'Arial Black', sans-serif",
			'faces' => array( 'Source Sans 3' => 'data:font/woff2;base64,d09GMgABAAAAAA' ),
		)
	)
);
list( , $out109 ) = fx_run_ok( $audit, $r109 );
ok( 'FAIL' === fx_row_level( $out109, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED' ) ), 'una familia pedida en un @media sin incrustar FALLA', fx_row_level( $out109, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED' ) ) );
ok( array() !== fx_lines_with( $out109, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED', 'Archivo Expanded' ) ), 'y nombra la familia del @media', $out109 );
/* La que SI esta incrustada no puede aparecer, o no se distingue que falta de que sobra. */
ok( array() === fx_lines_with( $out109, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED', 'Source Sans 3' ) ), 'y no acusa a la que si esta incrustada', $out109 );
fx_rrmdir( $r109 );

echo "--- un stack SOLO generico no produce fila: system-ui no nombra ningun archivo ---\n";
$r110 = fx_tmp_root();
fx_base( $r110 );
/* THE MUTANT THIS KILLS: treat the first entry as a family without asking whether it is a generic
   keyword. `system-ui`, `sans-serif` and `serif` name no file and can never be embedded, so
   demanding an @font-face for them is a row nobody can ever satisfy — and references/mockup-guide.md
   ships exactly this stack in its base shell, so the mutant would fail the guide's own example. */
fx(
	$r110,
	FX_MAQUETA,
	fx_mockup( array( 'stack' => 'system-ui, sans-serif' ) )
);
list( $code110, $out110 ) = fx_run_ok( $audit, $r110 );
ok( array() === fx_lines_with( $out110, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED' ) ), 'un stack generico no produce fila', $out110 );
ok( 0 === $code110, 'y el arbol sale con codigo 0', $code110 );
fx_rrmdir( $r110 );

echo "--- una @font-face que nadie usa no es una PETICION: no produce fila ---\n";
$r112 = fx_tmp_root();
fx_base( $r112 );
/* THE MUTANT THIS KILLS: stop stripping `@font-face` blocks before reading the font stacks. A
   face declares `font-family:` too, so leaving the blocks in makes every embedded family look
   like a request FOR ITSELF — the row starts answering its own question. It survives on the
   fixtures above, because there every declared face is also asked for, so the tautology is
   invisible. This is the file where the two readings part: `Ghost` has a face and nothing uses
   it, and a browser never fetches a face nothing uses, so there is no blocked request and no
   fallback and nothing to report. Under the mutant `Ghost` becomes an asked-for family served
   from a URL and the row fires on a file that is correct.

   Found by mutating, not by review: this was the one survivor of ten. */
fx(
	$r112,
	FX_MAQUETA,
	fx_mockup(
		array(
			'stack' => "'Fraunces', Georgia, serif",
			'faces' => array(
				'Fraunces' => 'data:font/woff2;base64,d09GMgABAAAAAA',
				'Ghost'    => 'https://fonts.gstatic.com/s/ghost/v1/x.woff2',
			),
		)
	)
);
list( $code112, $out112 ) = fx_run_ok( $audit, $r112 );
ok( array() === fx_lines_with( $out112, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED' ) ), 'una cara declarada que nadie pide no produce fila', $out112 );
ok( array() === fx_lines_with( $out112, array( 'Ghost' ) ), 'y Ghost no aparece en ninguna fila', $out112 );
ok( 0 === $code112, 'y el arbol sale con codigo 0', $code112 );
fx_rrmdir( $r112 );

/* ---------------------------------------------------------------------------
   style-catalog PR 4a (tasks.md 4a.1) — the font-budget constraint the whole catalog is locked
   to: `skills/html-mockup/assets/fonts/_fonts.php` embedded exactly 7 faces at the time (Fraunces,
   Instrument Serif, Inter Tight, DM Sans, Source Sans 3, Archivo, Archivo Expanded), which is WHY
   the catalog ships 8 entries in v1 instead of the 12 first proposed. `RT_MOCKUP_FONT_NOT_EMBEDDED`
   above is the mechanism that would catch a `STY-*.md` naming a family outside that list once it
   is rendered into a mockup, and it needs no new code: it already reads any mockup's font stack
   against its own `@font-face` declarations, generically, regardless of which catalog format named
   the family. THIS IS A "VALUE, NOT NOVELTY" RED, same discipline as 3b.1/3b.3: the mechanism is
   pre-existing (r106 above already proves the general shape with `Fraunces`), so this scenario is
   already green before any 4a code lands — its purpose is to lock the SPECIFIC example the
   style-catalog spec names (`Canela Deck`, `specs/style-catalog/spec.md` Scenario "Unembedded
   family named") into a real assertion, so a future PR cannot silently widen the font-embedded
   check without this concrete catalog-budget case noticing. */

echo "--- style-catalog PR 4a: una familia fuera del presupuesto de 7 caras (Canela Deck) FALLA ---\n";
$r112b = fx_tmp_root();
fx_base( $r112b );
fx(
	$r112b,
	FX_MAQUETA,
	fx_mockup( array( 'stack' => "'Canela Deck', Georgia, serif" ) )
);
list( , $out112b ) = fx_run_ok( $audit, $r112b );
ok( 'FAIL' === fx_row_level( $out112b, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED' ) ), 'nombrar una familia ausente de nm_font_registry() FALLA', fx_row_level( $out112b, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED' ) ) );
ok( array() !== fx_lines_with( $out112b, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED', 'Canela Deck' ) ), 'y nombra la familia que ningun STY-*.md puede pedir', $out112b );
fx_rrmdir( $r112b );

echo "--- style-catalog PR 4a: reincrustar una familia embebida a otro peso/stretch NO FALLA (Archivo / Archivo Expanded) ---\n";
$r112c = fx_tmp_root();
fx_base( $r112c );
fx(
	$r112c,
	FX_MAQUETA,
	fx_mockup(
		array(
			'stack' => "'Archivo', system-ui, sans-serif",
			'rule'  => "'Archivo Expanded', system-ui, sans-serif",
			'faces' => array(
				'Archivo'          => 'data:font/woff2;base64,d09GMgABAAAAAA',
				'Archivo Expanded' => 'data:font/woff2;base64,d09GMgABAAAAAA',
			),
		)
	)
);
list( $code112c, $out112c ) = fx_run_ok( $audit, $r112c );
ok( array() === fx_lines_with( $out112c, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED' ) ), 'una familia embebida reutilizada a otro peso/stretch no produce fila', $out112c );
ok( 0 === $code112c, 'y el arbol conforme sale con codigo 0', $code112c );
fx_rrmdir( $r112c );

/* ---------------------------------------------------------------------------
   style-catalog PR 4b (tasks.md 4b.1) — r112b/r112c above lock the mechanism against ONE reuse pair
   (Archivo/Archivo Expanded) and ONE absent family (Canela Deck), both invented for the spec's own
   worked examples. This is the same "value, not novelty" RED: `RT_MOCKUP_FONT_NOT_EMBEDDED` needs no
   new code for PR 4b either, so the value here is locking PR 4b's OWN concrete font decisions — real
   choices this PR actually ships, not a second copy of 4a's examples — into a real assertion.
   `STY-TECH-SAAS` reuses `Inter Tight` as a PRIMARY for the first time in the catalog (every prior
   use was secondary, `STY-EDITORIAL`/`STY-VITRINE`); `STY-NEO-BRUTALIST` reuses `Archivo` (not
   `Archivo Expanded`) as a primary for the first time (its only prior use was `STY-DIRECT`'s
   secondary). Both are real `--font-primary`/`--font-secondary` pairs from this PR's own `.md`
   files, not synthetic stand-ins. */

echo "--- style-catalog PR 4b: STY-TECH-SAAS reincrusta Inter Tight como primary (antes solo secondary) NO FALLA ---\n";
$r112d = fx_tmp_root();
fx_base( $r112d );
fx(
	$r112d,
	FX_MAQUETA,
	fx_mockup(
		array(
			'stack' => "'Inter Tight', system-ui, sans-serif",
			'rule'  => "'Source Sans 3', system-ui, sans-serif",
			'faces' => array(
				'Inter Tight'   => 'data:font/woff2;base64,d09GMgABAAAAAA',
				'Source Sans 3' => 'data:font/woff2;base64,d09GMgABAAAAAA',
			),
		)
	)
);
list( $code112d, $out112d ) = fx_run_ok( $audit, $r112d );
ok( array() === fx_lines_with( $out112d, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED' ) ), 'Inter Tight como primary + Source Sans 3 como secondary, ambos incrustados, no producen fila', $out112d );
ok( 0 === $code112d, 'y el arbol conforme sale con codigo 0', $code112d );
fx_rrmdir( $r112d );

echo "--- style-catalog PR 4b: STY-NEO-BRUTALIST reincrusta Archivo (no Expanded) como primary NO FALLA ---\n";
$r112e = fx_tmp_root();
fx_base( $r112e );
fx(
	$r112e,
	FX_MAQUETA,
	fx_mockup(
		array(
			'stack' => "'Archivo', system-ui, sans-serif",
			'rule'  => "'DM Sans', system-ui, sans-serif",
			'faces' => array(
				'Archivo' => 'data:font/woff2;base64,d09GMgABAAAAAA',
				'DM Sans' => 'data:font/woff2;base64,d09GMgABAAAAAA',
			),
		)
	)
);
list( $code112e, $out112e ) = fx_run_ok( $audit, $r112e );
ok( array() === fx_lines_with( $out112e, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED' ) ), 'Archivo (no Expanded) como primary + DM Sans como secondary, ambos incrustados, no producen fila', $out112e );
ok( 0 === $code112e, 'y el arbol conforme sale con codigo 0', $code112e );
fx_rrmdir( $r112e );

echo "--- style-catalog PR 4b: una segunda familia fuera del presupuesto, distinta de Canela Deck, FALLA igual ---\n";
$r112f = fx_tmp_root();
fx_base( $r112f );
fx(
	$r112f,
	FX_MAQUETA,
	fx_mockup( array( 'stack' => "'GT Walsheim', system-ui, sans-serif" ) )
);
list( , $out112f ) = fx_run_ok( $audit, $r112f );
ok( 'FAIL' === fx_row_level( $out112f, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED' ) ), 'nombrar una familia ausente distinta de la ya fijada en 4a tambien FALLA', fx_row_level( $out112f, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED' ) ) );
ok( array() !== fx_lines_with( $out112f, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED', 'GT Walsheim' ) ), 'y nombra la familia -- el mecanismo no esta atado a un solo nombre fijado por 4a', $out112f );
fx_rrmdir( $r112f );

echo "--- imagenes sin manifiesto al lado FALLAN, contandolas ---\n";
$r211 = fx_tmp_root();
fx_base( $r211 );
fx_maqueta( $r211, fx_maqueta_html( $FX_MAQ_IMGS ) );
list( , $out211 ) = fx_run_ok( $audit, $r211 );
ok( 'FAIL' === fx_row_level( $out211, array( 'RT_GALLERY_NO_MANIFEST' ) ), 'sin manifiesto-imagenes.md al lado, FALLA', fx_row_level( $out211, array( 'RT_GALLERY_NO_MANIFEST' ) ) );
ok( array() !== fx_lines_with( $out211, array( 'RT_GALLERY_NO_MANIFEST', 'renders 2 image slug(s) and no manifiesto-imagenes.md' ) ), 'y cuenta las imagenes que quedan sin contrato', $out211 );
/* El POR QUE tiene que viajar en el mensaje: es_photo() resuelve un slug de adjunto, no una URL. */
ok( array() !== fx_lines_with( $out211, array( 'RT_GALLERY_NO_MANIFEST', 'ATTACHMENT SLUG' ) ), 'y dice por que importa: es_photo resuelve un slug', $out211 );
fx_rrmdir( $r211 );

echo "--- un manifiesto conforme no produce fila ---\n";
$r211b = fx_tmp_root();
fx_base( $r211b );
fx_maqueta( $r211b, fx_maqueta_html( $FX_MAQ_IMGS ), fx_manifest( array( 'hero-taller' => 'Freepik AI', 'card-veta' => 'Freepik AI' ) ) );
list( $code211b, $out211b ) = fx_run_ok( $audit, $r211b );
ok( array() === fx_lines_with( $out211b, array( 'RT_GALLERY_NO_MANIFEST' ) ), 'cada imagen con su fila y su licencia no produce fila', $out211b );
ok( 0 === $code211b, 'y el arbol conforme sale con codigo 0', $code211b );
fx_rrmdir( $r211b );

echo "--- un manifiesto SIN tabla de slugs FALLA: `Slugs` en plural no es la columna `Slug` ---\n";
$r212 = fx_tmp_root();
fx_base( $r212 );
/* La tabla se elige por una celda de cabecera que diga exactamente `Slug`. Un manifiesto puede llevar
   otra tabla cuya cabecera diga `Slugs`, y un parser por subcadena — o por posicion de columna —
   leeria tres celdas de prosa como filas de imagen. */
fx_maqueta(
	$r212,
	fx_maqueta_html( $FX_MAQ_IMGS ),
	"# Manifiesto sin tabla de imagenes\n\n| Registro | Slugs | Que dice |\n|---|---|---|\n| Taller | hero-taller card-veta | fixture |\n"
);
list( , $out212 ) = fx_run_ok( $audit, $r212 );
ok( 'FAIL' === fx_row_level( $out212, array( 'RT_GALLERY_NO_MANIFEST' ) ), 'un manifiesto sin columna Slug FALLA', fx_row_level( $out212, array( 'RT_GALLERY_NO_MANIFEST' ) ) );
ok( array() !== fx_lines_with( $out212, array( 'RT_GALLERY_NO_MANIFEST', 'carries no table with a `Slug` column' ) ), 'y dice que lo que falta es la tabla, no una fila', $out212 );
fx_rrmdir( $r212 );

echo "--- una imagen sin fila en el manifiesto FALLA, nombrandola y sin acusar a las que si estan ---\n";
$r213 = fx_tmp_root();
fx_base( $r213 );
fx_maqueta( $r213, fx_maqueta_html( $FX_MAQ_IMGS ), fx_manifest( array( 'hero-taller' => 'Freepik AI' ) ) );
list( , $out213 ) = fx_run_ok( $audit, $r213 );
ok( 'FAIL' === fx_row_level( $out213, array( 'RT_GALLERY_NO_MANIFEST' ) ), 'un slug sin fila FALLA', fx_row_level( $out213, array( 'RT_GALLERY_NO_MANIFEST' ) ) );
ok( array() !== fx_lines_with( $out213, array( 'RT_GALLERY_NO_MANIFEST', 'renders `card-veta` with no row' ) ), 'y nombra el slug que falta', $out213 );
ok( array() === fx_lines_with( $out213, array( 'RT_GALLERY_NO_MANIFEST', '`hero-taller`' ) ), 'y no acusa al que si tiene fila', $out213 );
fx_rrmdir( $r213 );

echo "--- una fila con la celda Slug vacia FALLA, nombrando su linea ---\n";
$r214 = fx_tmp_root();
fx_base( $r214 );
/* "El nombre del fichero ES el slug": una fila sin slug documenta una imagen que el build no tiene
   forma de pedir, y que el operador no sabe con que nombre subir. */
$fx_man214 = fx_manifest(
	array(
		'hero-taller' => 'Freepik AI',
		'card-veta'   => 'Freepik AI',
		''            => 'Freepik AI',
	)
);
fx_maqueta( $r214, fx_maqueta_html( $FX_MAQ_IMGS ), $fx_man214 );
list( , $out214 ) = fx_run_ok( $audit, $r214 );
ok( 'FAIL' === fx_row_level( $out214, array( 'RT_GALLERY_NO_MANIFEST', 'empty Slug cell' ) ), 'una fila sin slug FALLA', fx_row_level( $out214, array( 'RT_GALLERY_NO_MANIFEST', 'empty Slug cell' ) ) );
ok(
	array() !== fx_lines_with( $out214, array( 'RT_GALLERY_NO_MANIFEST', 'line ' . fx_line_of( $fx_man214, '| | foto 4:3' ) ) ),
	'y nombra la LINEA de la fila, no solo que hay una',
	$out214
);
fx_rrmdir( $r214 );

echo "--- una fila sin licencia FALLA, nombrando el slug y su linea ---\n";
$r215 = fx_tmp_root();
fx_base( $r215 );
/* La licencia va POR FILA y no en un parrafo: un "todas estas son de licencia libre" es cierto
   hasta la decimoquinta imagen, y deja de serlo en silencio porque la fila nueva no se distingue
   de las viejas. */
$fx_man215 = fx_manifest( array( 'hero-taller' => 'Freepik AI', 'card-veta' => '' ) );
fx_maqueta( $r215, fx_maqueta_html( $FX_MAQ_IMGS ), $fx_man215 );
list( , $out215 ) = fx_run_ok( $audit, $r215 );
ok( 'FAIL' === fx_row_level( $out215, array( 'RT_GALLERY_NO_MANIFEST' ) ), 'una fila sin licencia FALLA', fx_row_level( $out215, array( 'RT_GALLERY_NO_MANIFEST' ) ) );
ok(
	array() !== fx_lines_with( $out215, array( 'RT_GALLERY_NO_MANIFEST', '1 row(s) with no licence — `card-veta` (line ' . fx_line_of( $fx_man215, '| `card-veta` |' ) . ')' ) ),
	'y nombra el slug y la linea, y cuenta UNA sola',
	$out215
);
ok( array() === fx_lines_with( $out215, array( 'RT_GALLERY_NO_MANIFEST', 'no Licence column at all' ) ), 'y no dice que falte la columna, porque esta', $out215 );
fx_rrmdir( $r215 );

echo "--- un manifiesto sin columna de licencia FALLA una vez, diciendo que la columna entera falta ---\n";
$r216 = fx_tmp_root();
fx_base( $r216 );
/* Dos causas distintas, dos mensajes distintos: una celda vacia se arregla escribiendo en ella, una
   columna ausente se arregla cambiando la tabla. Sin la frase final el lector busca una celda que
   no existe. */
fx_maqueta(
	$r216,
	fx_maqueta_html( $FX_MAQ_IMGS ),
	fx_manifest( array( 'hero-taller' => 'Freepik AI', 'card-veta' => 'Freepik AI' ), false )
);
list( , $out216 ) = fx_run_ok( $audit, $r216 );
ok( 'FAIL' === fx_row_level( $out216, array( 'RT_GALLERY_NO_MANIFEST' ) ), 'sin columna de licencia, FALLA', fx_row_level( $out216, array( 'RT_GALLERY_NO_MANIFEST' ) ) );
ok( array() !== fx_lines_with( $out216, array( 'RT_GALLERY_NO_MANIFEST', '2 row(s) with no licence' ) ), 'y las cuenta todas', $out216 );
ok( array() !== fx_lines_with( $out216, array( 'RT_GALLERY_NO_MANIFEST', 'carries no Licence column at all' ) ), 'y dice que la causa es la columna, no la celda', $out216 );
fx_rrmdir( $r216 );

/* ---------------------------------------------------------------------------
   RT_BUILDER_NO_TOKENS / RT_BUILDER_HARDCODED_TOKEN — the same question RT_MOCKUP_NO_AXES asks of
   the file a project is COPIED FROM, asked one hop later of the file that WRITES the site.
   elementor-core/SKILL.md told operators to "swap its palette/type constants" while es-builder.php
   contained no constants of any kind: 51 colour literals typed where they were used. Every site
   shipped the same green on the same white and every row in this audit stayed green.

   The whole difficulty is the region's START boundary, and the scenarios below are ordered so the
   trap comes first. See fx_builder()'s docblock.
   --------------------------------------------------------------------------- */

echo "--- un builder con es_tokens() y sin literales en la region no produce fila ---\n";
$r106 = fx_tmp_root();
fx_base( $r106 );
/* THE BOUNDARY SCENARIO. This asset's token block declares seven visual literals, and it is
   CORRECT: the declaration site is the one place they belong. The region starts at the CLOSING
   brace of es_tokens(). Move that anchor to the function's opening line — the mistake that is one
   character of intent away — and this scenario reports seven findings against a conforming file,
   which is a check nobody keeps switched on. */
fx_builder_skill( $r106, fx_builder() );
list( $code106, $out106 ) = fx_run_ok( $audit, $r106 );
ok( array() === fx_lines_with( $out106, array( 'RT_BUILDER_' ) ), 'un builder conforme no produce ninguna fila de builder', $out106 );
ok( 0 === $code106, 'y el arbol conforme sale con codigo 0', $code106 );
fx_rrmdir( $r106 );

echo "--- cada forma de literal en la region FALLA, nombrando fichero, linea y valor ---\n";
$r107   = fx_tmp_root();
fx_base( $r107 );
/* All four detected shapes in one asset, each on its own line with its own unique anchor, so
   dropping ANY ONE arm of the detector kills a specific named assertion instead of quietly
   halving the check's reach while the row still fires on the other three. */
$b107 = fx_builder(
	array(
		'region' => "\t\$s['background_color']  = '#0FA968';\n"
			. "\t\$s['box_shadow_color']  = 'rgba(15,169,104,0.55)';\n"
			. "\t\$s['transition_curve']  = 'cubic-bezier(.22,1,.36,1)';\n"
			. "\t\$s2 = array( 'typography_font_family' => 'Manrope' );\n",
	)
);
fx_builder_skill( $r107, $b107 );
list( , $out107 ) = fx_run_ok( $audit, $r107 );
ok( 'FAIL' === fx_row_level( $out107, array( 'RT_BUILDER_HARDCODED_TOKEN' ) ), 'un literal en la region FALLA', fx_row_level( $out107, array( 'RT_BUILDER_HARDCODED_TOKEN' ) ) );
ok(
	array() !== fx_lines_with( $out107, array( 'RT_BUILDER_HARDCODED_TOKEN', 'es-builder.php:' . fx_line_of( $b107, 'background_color' ) . ' → #0FA968' ) ),
	'y nombra fichero, LINEA y valor del hex',
	$out107
);
ok(
	array() !== fx_lines_with( $out107, array( 'RT_BUILDER_HARDCODED_TOKEN', 'es-builder.php:' . fx_line_of( $b107, 'box_shadow_color' ) . ' → rgba(15,169,104,0.55)' ) ),
	'y tambien el rgba() entero, no solo "rgba("',
	$out107
);
ok(
	array() !== fx_lines_with( $out107, array( 'RT_BUILDER_HARDCODED_TOKEN', 'es-builder.php:' . fx_line_of( $b107, 'transition_curve' ) . ' → cubic-bezier(.22,1,.36,1)' ) ),
	'y la curva de easing',
	$out107
);
ok(
	array() !== fx_lines_with( $out107, array( 'RT_BUILDER_HARDCODED_TOKEN', 'es-builder.php:' . fx_line_of( $b107, "'typography_font_family' => 'Manrope'" ) . " → typography_font_family 'Manrope'" ) ),
	'y una familia escrita como cadena en una clave de tipografia',
	$out107
);
/* The es_t() call sites in the same function are the shape this row WANTS. If they were reported
   the message would stop being a list of what to fix. */
ok( array() === fx_lines_with( $out107, array( 'RT_BUILDER_HARDCODED_TOKEN', "es_t( 'font_body' )" ) ), 'y no acusa a las llamadas es_t() que estan bien', $out107 );
fx_rrmdir( $r107 );

echo "--- un builder SIN es_tokens() FALLA con RT_BUILDER_NO_TOKENS ---\n";
$r108 = fx_tmp_root();
fx_base( $r108 );
fx_builder_skill( $r108, fx_builder( array( 'tokens' => false ) ) );
list( , $out108 ) = fx_run_ok( $audit, $r108 );
ok( 'FAIL' === fx_row_level( $out108, array( 'RT_BUILDER_NO_TOKENS' ) ), 'sin es_tokens() FALLA', fx_row_level( $out108, array( 'RT_BUILDER_NO_TOKENS' ) ) );
ok( array() !== fx_lines_with( $out108, array( 'RT_BUILDER_NO_TOKENS', 'assets/es-builder.php' ) ), 'y nombra el fichero', $out108 );
/* Without a token block there is no region, so the literal row must stay silent rather than
   reporting the whole file as hardcoded — two rows for one cause is one row too many. */
ok( array() === fx_lines_with( $out108, array( 'RT_BUILDER_HARDCODED_TOKEN' ) ), 'y no dispara ademas la fila de literales', $out108 );
fx_rrmdir( $r108 );

echo "--- LA TRAMPA: \"#732\" bajo el marcador de fin NO es un color ---\n";
$r109 = fx_tmp_root();
fx_base( $r109 );
/* THE SCOPING SCENARIO, and the one that proves the END boundary is load-bearing rather than
   decorative. `#732` is a fake post id inside a Spanish warning, and a hex regex cannot tell it
   from a colour: three hex digits behind a `#`. Below the END marker it is inert — it never
   reaches the emitted data — so the region excludes it. Mutate the scan to read the whole file
   and this scenario goes red, which is the only thing that stops the region from being deleted as
   ceremony by the next reader.

   Honest note, because the plan this fixture comes from is wrong about it: es-builder.php does
   NOT type `#732` in a live string. It builds every such warning as `'#' . $post_id`, and the
   literal `#732` survives only in two explanatory COMMENTS. So this fixture is a RESERVATION in
   the same sense `_shared-copy.html` is above — the shape one refactor away, not a description of
   today's tree. The boundary still earns its place on today's file at the START end: without it,
   the token declarations are 21 findings. */
fx_builder_skill( $r109, fx_builder( array( 'below_end' => "\t\$otro = 'pagina #732 no existe';\n" ) ) );
list( $code109, $out109 ) = fx_run_ok( $audit, $r109 );
ok( array() === fx_lines_with( $out109, array( 'RT_BUILDER_' ) ), 'un "#732" bajo el marcador de fin no produce fila', $out109 );
ok( 0 === $code109, 'y el arbol sigue saliendo con codigo 0', $code109 );
fx_rrmdir( $r109 );

echo "--- un builder SIN marcador de fin FALLA: una region sin limite no se puede escanear ---\n";
$r110 = fx_tmp_root();
fx_base( $r110 );
fx_builder_skill( $r110, fx_builder( array( 'end_marker' => false ) ) );
list( , $out110 ) = fx_run_ok( $audit, $r110 );
ok( 'FAIL' === fx_row_level( $out110, array( 'RT_BUILDER_NO_TOKENS' ) ), 'sin marcador de fin FALLA', fx_row_level( $out110, array( 'RT_BUILDER_NO_TOKENS' ) ) );
/* This row covers three causes; the message has to say WHICH, or the reader is left diffing the
   asset against es-builder.php by eye to find out what is missing. */
ok( array() !== fx_lines_with( $out110, array( 'RT_BUILDER_NO_TOKENS', 'carries no "end of the visual layer" marker' ) ), 'y dice cual de las tres causas es', $out110 );
fx_rrmdir( $r110 );

echo "--- el marcador de fin POR ENCIMA de es_tokens() deja la region vacia y FALLA ---\n";
$r111 = fx_tmp_root();
fx_base( $r111 );
/* The shape a copy-paste produces when the markers are added to a second asset — and without this
   branch the region loop simply never runs and the file passes with nothing scanned, which is the
   silent-hole version of the same bug the END marker exists to prevent. */
fx_builder_skill( $r111, fx_builder( array( 'end_first' => true ) ) );
list( , $out111 ) = fx_run_ok( $audit, $r111 );
ok( 'FAIL' === fx_row_level( $out111, array( 'RT_BUILDER_NO_TOKENS' ) ), 'un marcador de fin mal colocado FALLA', fx_row_level( $out111, array( 'RT_BUILDER_NO_TOKENS' ) ) );
ok( array() !== fx_lines_with( $out111, array( 'RT_BUILDER_NO_TOKENS', 'sits ABOVE where the region starts' ) ), 'y dice que el marcador esta por encima', $out111 );
fx_rrmdir( $r111 );

echo "--- un hex en un COMENTARIO no es un literal; uno tras una URL en la misma linea SI ---\n";
$r112 = fx_tmp_root();
fx_base( $r112 );
/* Two claims in one fixture, and they only hold together.
   1. A hex inside a PHP comment does not fire. It cannot reach the emitted data, and es-builder.php
      really does explain a token choice with the words "both are #FFFFFF today" — a check that
      charged the file for that would be charging it for documenting itself.
   2. The stripping is done by PHP's own lexer, not by a regex. The second line here puts a URL
      inside a string BEFORE a real literal. A hand-rolled stripper that treats `//` as a comment
      opener blanks the rest of that line and the literal vanishes — a silent escape hatch anyone
      could reach by accident. This assertion is what forbids that implementation. */
$b112 = fx_builder(
	array(
		'region' => "\t/* era #0FA968 antes de que el token existiera; ver rgba(0,0,0,0) */\n"
			. "\t\$url = 'https://ejemplo.test/a'; \$s['tras_url'] = '#0FA968';\n",
	)
);
fx_builder_skill( $r112, $b112 );
list( , $out112 ) = fx_run_ok( $audit, $r112 );
ok( 'FAIL' === fx_row_level( $out112, array( 'RT_BUILDER_HARDCODED_TOKEN' ) ), 'el literal tras la URL FALLA', fx_row_level( $out112, array( 'RT_BUILDER_HARDCODED_TOKEN' ) ) );
ok(
	array() !== fx_lines_with( $out112, array( 'RT_BUILDER_HARDCODED_TOKEN', 'es-builder.php:' . fx_line_of( $b112, 'tras_url' ) . ' → #0FA968' ) ),
	'y lo nombra con su linea, sin que la URL de la misma linea lo tape',
	$out112
);
ok(
	array() === fx_lines_with( $out112, array( 'RT_BUILDER_HARDCODED_TOKEN', 'es-builder.php:' . fx_line_of( $b112, 'antes de que el token' ) . ' →' ) ),
	'y el hex del comentario no aparece como hallazgo',
	$out112
);
ok( 1 === count( fx_lines_with( $out112, array( 'RT_BUILDER_HARDCODED_TOKEN' ) ) ), 'y es UN hallazgo, no dos ni tres', $out112 );
fx_rrmdir( $r112 );

/* ---------------------------------------------------------------------------
   THE DETECTOR'S REACH, which is a separate question from the region's boundary.

   Measured before it was widened: the row caught hex, `rgba(`, `cubic-bezier(` and a quoted font
   family, and NOTHING else. A brand-new visual helper carrying `rebeccapurple`, `rgb(15,169,104)`,
   `hsl(151,84%,36%)` and a bare `ease` was dropped inside a scanned region and passed all four
   suites AND the audit at exit 0 — `rgb(` missed because the `a` was mandatory in the pattern.
   Every arm below gets its own named assertion so dropping one kills a specific line instead of
   halving the reach while the row still fires on the others.
   --------------------------------------------------------------------------- */

echo "--- toda sintaxis de color, no solo hex y rgba(), es un literal ---\n";
$r116 = fx_tmp_root();
fx_base( $r116 );
$b116 = fx_builder(
	array(
		'region' => "\t\$s['a_rgb']    = 'background:rgb(15,169,104);';\n"
			. "\t\$s['a_hsl']    = 'background:hsl(151,84%,36%);';\n"
			. "\t\$s['a_hsla']   = 'background:hsla(151,84%,36%,.5);';\n"
			. "\t\$s['a_oklch']  = 'background:oklch(70% 0.1 150);';\n"
			. "\t\$s['a_lab']    = 'background:lab(50% 40 59);';\n"
			. "\t\$s['a_mix']    = 'outline:color-mix(in srgb,white 22%,transparent);';\n"
			. "\t\$s['a_nombre'] = 'background:rebeccapurple;';\n"
			. "\t\$s['a_medio']  = 'border:1px solid black;';\n",
	)
);
fx_builder_skill( $r116, $b116 );
list( , $out116 ) = fx_run_ok( $audit, $r116 );
foreach ( array(
	'a_rgb'    => 'rgb(15,169,104)',
	'a_hsl'    => 'hsl(151,84%,36%)',
	'a_hsla'   => 'hsla(151,84%,36%,.5)',
	'a_oklch'  => 'oklch(70% 0.1 150)',
	'a_lab'    => 'lab(50% 40 59)',
	'a_mix'    => 'color-mix(in srgb,white 22%,transparent)',
	'a_nombre' => 'rebeccapurple',
	'a_medio'  => 'black',
) as $clave116 => $valor116 ) {
	ok(
		array() !== fx_lines_with( $out116, array( 'RT_BUILDER_HARDCODED_TOKEN', 'es-builder.php:' . fx_line_of( $b116, $clave116 ) . ' → ' . $valor116 ) ),
		'"' . $valor116 . '" es un literal, con su linea y su valor',
		$out116
	);
}
fx_rrmdir( $r116 );

echo "--- una curva de easing escrita como palabra suelta es un literal; es_t('ease') no ---\n";
$r117 = fx_tmp_root();
fx_base( $r117 );
/* THE LIVE FIXTURE. These three lines are the seven violations that existed in the tree when this
   was written, copied verbatim: the header's nav underline, the shop archive's pagination and the
   product page's add-to-cart — the last one carrying a bare `ease` in the SAME declaration as
   es_t('ease'), so `transform` eased on the house curve while `background-color` fell back to the
   browser default. Two of the three sibling assets reached the motion axis at exactly 0%.
   The fourth line is the corrected shape and must stay silent, because `ease` is also a TOKEN NAME:
   without the blanking pass, es_t('ease') reads as the keyword it replaced and the only way to
   satisfy the row would be to stop naming the token. */
$b117 = fx_builder(
	array(
		'region' => "\t\$s['t_nav']   = 'transition:opacity .28s ease,transform .28s ease;';\n"
			. "\t\$s['t_pag']   = 'transition:color .25s ease,background-color .25s ease;';\n"
			. "\t\$s['t_carro'] = 'transition:box-shadow .35s ease,transform .35s ' . es_t( 'ease' ) . ';';\n"
			. "\t\$s['t_func']  = 'transition-timing-function:linear;';\n"
			. "\t\$s['t_pasos'] = 'animation:x 2s steps(4);';\n"
			. "\t\$s['t_bien']  = 'transition:opacity .28s ' . es_t( 'ease' ) . ';';\n"
			. "\t\$s['t_fondo'] = 'background:' . es_t( 'transparent' ) . ';';\n",
	)
);
fx_builder_skill( $r117, $b117 );
list( , $out117 ) = fx_run_ok( $audit, $r117 );
ok( array() !== fx_lines_with( $out117, array( 'RT_BUILDER_HARDCODED_TOKEN', 'es-builder.php:' . fx_line_of( $b117, 't_nav' ) . ' → .28s ease' ) ), 'un `ease` suelto tras una duracion es un literal', $out117 );
ok( array() !== fx_lines_with( $out117, array( 'RT_BUILDER_HARDCODED_TOKEN', 'es-builder.php:' . fx_line_of( $b117, 't_pag' ) . ' → .25s ease' ) ), 'y en la paginacion tambien', $out117 );
ok( array() !== fx_lines_with( $out117, array( 'RT_BUILDER_HARDCODED_TOKEN', 'es-builder.php:' . fx_line_of( $b117, 't_carro' ) . ' → .35s ease' ) ), 'y el que comparte declaracion con es_t(ease), que es el peor de los tres', $out117 );
ok( array() !== fx_lines_with( $out117, array( 'RT_BUILDER_HARDCODED_TOKEN', 'es-builder.php:' . fx_line_of( $b117, 't_func' ) . ' → timing-function:linear' ) ), 'y `linear` en timing-function', $out117 );
ok( array() !== fx_lines_with( $out117, array( 'RT_BUILDER_HARDCODED_TOKEN', 'es-builder.php:' . fx_line_of( $b117, 't_pasos' ) . ' → steps(4)' ) ), 'y steps(), que es una curva como cualquier otra', $out117 );
ok( array() === fx_lines_with( $out117, array( 'RT_BUILDER_HARDCODED_TOKEN', 'es-builder.php:' . fx_line_of( $b117, 't_bien' ) . ' →' ) ), 'y la forma correcta —— es_t(ease) —— NO se acusa aunque el token se llame igual que la palabra clave', $out117 );
/* Y el token cuyo NOMBRE es un color CSS, que es el unico caso donde el blanqueo
   de es_t() carga peso de verdad. `ease` se salva por el ancla de duracion —— no
   hay ningun `<num>s ease` en la forma correcta —— pero `transparent` esta en la
   lista de 148 nombres y va pegado a comillas, asi que sin blanquear la lectura
   del token, `es_t('transparent')` se acusa a si mismo. es-theme-parts.example.php
   lo usa de verdad (linea 444), asi que sin esta linea el mutante que apaga el
   blanqueo pasaba la suite entera y solo caia sobre el arbol real. */
ok( array() === fx_lines_with( $out117, array( 'RT_BUILDER_HARDCODED_TOKEN', 'es-builder.php:' . fx_line_of( $b117, 't_fondo' ) . ' →' ) ), 'y es_t(transparent) tampoco: leer un token no es escribir su valor, aunque el token se llame como un color CSS', $out117 );
fx_rrmdir( $r117 );

echo "--- un hex partido por una concatenacion es un hex, se parta donde se parta ---\n";
$r118 = fx_tmp_root();
fx_base( $r118 );
/* `'#0FA' . '968'` was already caught by accident — `#0FA` is a valid three-digit hex — but
   `'#0F' . 'A968'` and `'#' . '0FA968'` were not. A rule whose reach depends on WHERE somebody put
   the quote is not a rule, and the split is one search-and-replace away from happening by itself. */
$b118 = fx_builder(
	array(
		'region' => "\t\$s['p_tres'] = '#0FA' . '968';\n"
			. "\t\$s['p_dos']  = '#0F' . 'A968';\n"
			. "\t\$s['p_cero'] = '#' . '0FA968';\n",
	)
);
fx_builder_skill( $r118, $b118 );
list( , $out118 ) = fx_run_ok( $audit, $r118 );
foreach ( array( 'p_tres', 'p_dos', 'p_cero' ) as $clave118 ) {
	ok(
		array() !== fx_lines_with( $out118, array( 'RT_BUILDER_HARDCODED_TOKEN', 'es-builder.php:' . fx_line_of( $b118, $clave118 ) ) ),
		'el hex partido en ' . $clave118 . ' se caza igual',
		$out118
	);
}
fx_rrmdir( $r118 );

echo "--- una clave *_color con una cadena entre comillas es un color, escriba lo que escriba ---\n";
$r119 = fx_tmp_root();
fx_base( $r119 );
/* The format-blind backstop. Every other arm hunts a SHAPE, and a shape list is only as complete as
   the CSS spec was the day it was written. `ButtonText` is a system colour that matches no pattern
   in this file and never will; the KEY is what gives it away. And `custom` on the same kind of key
   must stay silent, because it is Elementor's control mode rather than a colour —— es-theme-parts
   really uses it. */
$b119 = fx_builder(
	array(
		'region' => "\t\$s2 = array( 'k_sistema' => 1, 'toggle_color' => 'ButtonText' );\n"
			. "\t\$s3 = array( 'k_modo' => 1, 'icon_color' => 'custom' );\n",
	)
);
fx_builder_skill( $r119, $b119 );
list( , $out119 ) = fx_run_ok( $audit, $r119 );
ok(
	array() !== fx_lines_with( $out119, array( 'RT_BUILDER_HARDCODED_TOKEN', 'es-builder.php:' . fx_line_of( $b119, 'k_sistema' ) . " → toggle_color 'ButtonText'" ) ),
	'un color de sistema en una clave de color se caza por la CLAVE, no por la forma',
	$out119
);
ok(
	array() === fx_lines_with( $out119, array( 'RT_BUILDER_HARDCODED_TOKEN', 'es-builder.php:' . fx_line_of( $b119, 'k_modo' ) . ' →' ) ),
	'y el modo de control "custom" de Elementor no es un color: es una decision, y no se acusa',
	$out119
);
fx_rrmdir( $r119 );

echo "--- una palabra castellana que TAMBIEN es un color CSS no dispara la fila ---\n";
$r120 = fx_tmp_root();
fx_base( $r120 );
/* The price of taking the FULL 148-keyword list instead of a curated three. `tan`, `peru`, `snow`,
   `plum` and `gold` are ordinary words and these files carry Spanish copy, so the match is anchored
   where a colour ENDS a CSS value —— followed by `;`, `,`, `!`, `}`, `)`, a quote or end of line.
   A Spanish word is followed by another word. Without this assertion the cheap way out of a false
   FAIL is to shrink the list, which is exactly the move that lets the next `rebeccapurple` through. */
$b120 = fx_builder(
	array(
		'region' => "\t\$s['prosa'] = 'el menu se pinta tan pronto como exista, no antes';\n"
			. "\t\$s['color'] = 'background:tan;';\n",
	)
);
fx_builder_skill( $r120, $b120 );
list( , $out120 ) = fx_run_ok( $audit, $r120 );
ok( array() === fx_lines_with( $out120, array( 'RT_BUILDER_HARDCODED_TOKEN', 'es-builder.php:' . fx_line_of( $b120, 'prosa' ) . ' →' ) ), '"tan pronto" en una frase no es el color `tan`', $out120 );
ok( array() !== fx_lines_with( $out120, array( 'RT_BUILDER_HARDCODED_TOKEN', 'es-builder.php:' . fx_line_of( $b120, "\$s['color']" ) . ' → tan' ) ), 'pero `background:tan;` si lo es, asi que la lista sigue entera', $out120 );
fx_rrmdir( $r120 );

/* ---------------------------------------------------------------------------
   SHAPE B — the sibling assets, which INHERIT es_tokens() instead of declaring it.
   The glob used to stop at elementor-core/assets/ because the other three files had no token
   layer at all and would have put this gate permanently red. They have one now, so the glob
   covers elementor-theme-parts and woocommerce too, and shape B is what it has to understand.
   --------------------------------------------------------------------------- */

echo "--- un hermano que HEREDA es_tokens() y marca su region no produce fila ---\n";
$r113 = fx_tmp_root();
fx_base( $r113 );
fx_builder_skill( $r113, fx_builder_hermano() );
list( $code113, $out113 ) = fx_run_ok( $audit, $r113 );
ok( array() === fx_lines_with( $out113, array( 'RT_BUILDER_' ) ), 'un hermano conforme no produce ninguna fila de builder', $out113 );
/* The assertion the lookbehind exists for. es_rgba( es_t('on_inverse'), '0.42' ) is the shape
   this row WANTS: the source colour comes from a token, only the alpha is local. Matched as a
   literal, the only way to satisfy the check would be to invent a named token per alpha — eleven
   of them, named after their appearance, which is the opposite of what a token is for. */
ok( array() === fx_lines_with( $out113, array( 'RT_BUILDER_HARDCODED_TOKEN', 'rgba(' ) ), 'y no acusa a es_rgba( es_t(...) ), que es la forma correcta de un velo derivado', $out113 );
ok( 0 === $code113, 'y el arbol conforme sale con codigo 0', $code113 );
fx_rrmdir( $r113 );

echo "--- pero un rgba() escrito a mano en la region de un hermano SIGUE fallando ---\n";
$r114 = fx_tmp_root();
fx_base( $r114 );
/* The other half of the pair, and the one that keeps the narrowing honest: if the lookbehind had
   been written loosely enough to let `es_rgba(` through by disabling the rgba branch, this
   scenario goes green and the check has been quietly turned off. */
$b114 = fx_builder_hermano( array( 'region' => "\t\$s['halo'] = 'rgba(15,169,104,0.55)';\n" ) );
fx_builder_skill( $r114, $b114 );
list( , $out114 ) = fx_run_ok( $audit, $r114 );
ok( 'FAIL' === fx_row_level( $out114, array( 'RT_BUILDER_HARDCODED_TOKEN' ) ), 'un rgba() a mano en un hermano FALLA', fx_row_level( $out114, array( 'RT_BUILDER_HARDCODED_TOKEN' ) ) );
ok(
	array() !== fx_lines_with( $out114, array( 'RT_BUILDER_HARDCODED_TOKEN', 'es-builder.php:' . fx_line_of( $b114, "\$s['halo']" ) . ' → rgba(15,169,104,0.55)' ) ),
	'y lo nombra con su linea y su valor',
	$out114
);
fx_rrmdir( $r114 );

echo "--- sin es_tokens() Y sin depender de es-builder.php es RT_BUILDER_NO_TOKENS ---\n";
$r115 = fx_tmp_root();
fx_base( $r115 );
/* Shape B must not become a way to opt out. A file with no block of its own and no dependency on
   the file that has one really does type every colour where it is used. */
fx_builder_skill( $r115, fx_builder_hermano( array( 'depende' => false ) ) );
list( , $out115 ) = fx_run_ok( $audit, $r115 );
ok( 'FAIL' === fx_row_level( $out115, array( 'RT_BUILDER_NO_TOKENS' ) ), 'sin bloque y sin dependencia FALLA', fx_row_level( $out115, array( 'RT_BUILDER_NO_TOKENS' ) ) );
ok( array() !== fx_lines_with( $out115, array( 'RT_BUILDER_NO_TOKENS', 'does not require es-builder.php' ) ), 'y dice que la causa es la dependencia que falta', $out115 );
fx_rrmdir( $r115 );

echo "--- un hermano que depende pero NO marca el inicio deja la region sin techo y FALLA ---\n";
$r116 = fx_tmp_root();
fx_base( $r116 );
/* Without the start marker there is no upper boundary, and the save pipeline these files carry
   ABOVE their visual code would be scanned as if it were design. */
fx_builder_skill( $r116, fx_builder_hermano( array( 'inicio' => false ) ) );
list( , $out116 ) = fx_run_ok( $audit, $r116 );
ok( 'FAIL' === fx_row_level( $out116, array( 'RT_BUILDER_NO_TOKENS' ) ), 'sin marcador de inicio FALLA', fx_row_level( $out116, array( 'RT_BUILDER_NO_TOKENS' ) ) );
ok( array() !== fx_lines_with( $out116, array( 'RT_BUILDER_NO_TOKENS', 'carries no "start of the visual layer" marker' ) ), 'y dice cual de las causas es', $out116 );
fx_rrmdir( $r116 );

echo "--- el marcador de inicio puede NOMBRAR al de fin sin encoger la region a nada ---\n";
$r117 = fx_tmp_root();
fx_base( $r117 );
/* THE VACUOUS-REGION SCENARIO, and the reason the END marker takes the LAST match instead of the
   first. Every start marker naturally wants to say "…down to the end of the visual layer marker",
   and all three real sibling files were written that way. On a first match that prose IS the end
   boundary: the region collapses to the four lines of its own comment, every literal below it goes
   unscanned, and the check reports a clean file because it looked at almost nothing. A check that
   passes by scanning nothing is worse than no check, because it is reported as a pass.
   The literal here sits where the collapsed region would not reach it. */
$b117 = fx_builder_hermano(
	array(
		'prosa_fin' => true,
		'region'    => "\t\$s['lejos'] = '#0FA968';\n",
	)
);
fx_builder_skill( $r117, $b117 );
list( , $out117 ) = fx_run_ok( $audit, $r117 );
ok( 'FAIL' === fx_row_level( $out117, array( 'RT_BUILDER_HARDCODED_TOKEN' ) ), 'la prosa del marcador de inicio no encoge la region: el literal de mas abajo se sigue viendo', fx_row_level( $out117, array( 'RT_BUILDER_HARDCODED_TOKEN' ) ) );
ok(
	array() !== fx_lines_with( $out117, array( 'RT_BUILDER_HARDCODED_TOKEN', 'es-builder.php:' . fx_line_of( $b117, "\$s['lejos']" ) . ' → #0FA968' ) ),
	'y lo nombra con la linea real, no con una de la region encogida',
	$out117
);
fx_rrmdir( $r117 );

echo "--- el glob llega a los OTROS dos skills, no solo a elementor-core ---\n";
$r118 = fx_tmp_root();
fx_base( $r118 );
/* THE SCENARIO THAT PROTECTS THE WIDENING ITSELF, and it was written because its absence was
   MEASURED, not guessed: with the glob narrowed back to elementor-core alone and a hardcoded
   colour put back into the real es-theme-parts.example.php, the audit exited 0 and this suite
   still reported every assertion green. Every other builder fixture writes into
   elementor-core/assets/, so not one of them can tell a widened glob from a narrow one — and a
   glob nothing pins is a glob the next person narrows for a quiet afternoon, taking the header,
   the footer, the shop and the product page out of the check with it.
   Two assets in the two OTHER directories, so narrowing EITHER one turns this red. */
$b118 = fx_builder_hermano( array( 'region' => "\t\$s['fuga'] = '#0FA968';\n" ) );
fx_wc_skill( $r118, 'woocommerce', "- Every colour, family and shadow lives in es_tokens().\n", "\nThe demo build lives in `assets/es-shop.example.php` — see `es_fixture_build()`.\n" );
fx( $r118, 'skills/woocommerce/assets/es-shop.example.php', $b118 );
fx_wc_skill( $r118, 'elementor-theme-parts', "- Every colour, family and shadow lives in es_tokens().\n", "\nThe demo build lives in `assets/es-parts.example.php` — see `es_fixture_build()`.\n" );
fx( $r118, 'skills/elementor-theme-parts/assets/es-parts.example.php', $b118 );
list( , $out118 ) = fx_run_ok( $audit, $r118 );
/* The skill name is in the needle set on purpose: narrowing the glob for ONE of the two
   directories has to turn exactly one of these red, and a needle that only named the file would
   still match the other skill's row. */
ok(
	array() !== fx_lines_with( $out118, array( 'RT_BUILDER_HARDCODED_TOKEN', 'woocommerce', 'es-shop.example.php:' . fx_line_of( $b118, "\$s['fuga']" ) . ' → #0FA968' ) ),
	'un literal en woocommerce/assets se ve, con su fichero y su linea',
	$out118
);
ok(
	array() !== fx_lines_with( $out118, array( 'RT_BUILDER_HARDCODED_TOKEN', 'elementor-theme-parts', 'es-parts.example.php:' . fx_line_of( $b118, "\$s['fuga']" ) . ' → #0FA968' ) ),
	'y uno en elementor-theme-parts/assets tambien',
	$out118
);
fx_rrmdir( $r118 );

/* ---------------------------------------------------------------------------
   RT_FONT_NO_SERVING_PATH — the family is WRITTEN and nothing makes it exist.

   es_tokens() puts `font_head => 'Space Grotesk'` into every heading this framework emits as
   `typography_font_family`, and the framework carried no `@font-face`, no enqueue and no font
   registration anywhere: the scale axis moved every SIZE correctly while the typeface may never
   have arrived, with every row in this audit green.

   The row is scoped to what a REPO can honestly assert. The font files live on a WordPress site,
   not here, so nothing in this tree can know whether they are served — what it can require is that
   the build carries a check that ASKS the site, that something calls it, and that its warning has a
   documented procedure behind it. Three causes, one row, each named: the same shape
   RT_BUILDER_NO_TOKENS uses for its three.
   --------------------------------------------------------------------------- */

echo "--- un builder que declara familia, la comprueba y la documenta no produce fila ---\n";
$r121 = fx_tmp_root();
fx_base( $r121 );
fx_builder_skill( $r121, fx_builder() );
list( $code121, $out121 ) = fx_run_ok( $audit, $r121 );
ok( array() === fx_lines_with( $out121, array( 'RT_FONT_NO_SERVING_PATH' ) ), 'las tres piezas puestas: no hay fila', $out121 );
ok( 0 === $code121, 'y el arbol conforme sale con codigo 0', $code121 );
fx_rrmdir( $r121 );

echo "--- sin comprobacion de servicio, la familia declarada FALLA nombrandose ---\n";
$r122 = fx_tmp_root();
fx_base( $r122 );
fx_builder_skill( $r122, fx_builder( array( 'font_decl' => false, 'font_call' => false ) ), false );
list( , $out122 ) = fx_run_ok( $audit, $r122 );
ok( 'FAIL' === fx_row_level( $out122, array( 'RT_FONT_NO_SERVING_PATH' ) ), 'sin nada que sirva la familia, FALLA', fx_row_level( $out122, array( 'RT_FONT_NO_SERVING_PATH' ) ) );
ok( array() !== fx_lines_with( $out122, array( 'RT_FONT_NO_SERVING_PATH', 'names Manrope' ) ), 'y nombra la familia, que es lo unico accionable', $out122 );
/* El limite, dicho en el propio mensaje. Una fila que se leyera como "los ficheros no estan
   servidos" estaria afirmando algo que este repositorio no puede saber. */
ok( array() !== fx_lines_with( $out122, array( 'RT_FONT_NO_SERVING_PATH', 'CANNOT know whether those font files are served' ) ), 'y dice lo que NO puede afirmar: los ficheros viven en el sitio, no aqui', $out122 );
fx_rrmdir( $r122 );

echo "--- una comprobacion declarada que NADIE llama sigue siendo FALLA ---\n";
$r123 = fx_tmp_root();
fx_base( $r123 );
/* El fallo que esta rama borra una y otra vez: la funcion escrita, probada y documentada mientras
   NADA la invoca. Con solo el arm de "existe", este escenario pasaria en verde. */
fx_builder_skill( $r123, fx_builder( array( 'font_call' => false ) ) );
list( , $out123 ) = fx_run_ok( $audit, $r123 );
ok( 'FAIL' === fx_row_level( $out123, array( 'RT_FONT_NO_SERVING_PATH' ) ), 'declarada y sin llamar FALLA', fx_row_level( $out123, array( 'RT_FONT_NO_SERVING_PATH' ) ) );
ok( array() !== fx_lines_with( $out123, array( 'RT_FONT_NO_SERVING_PATH', 'nothing in it CALLS' ) ), 'y dice que la causa es que nadie la llama', $out123 );
ok( array() === fx_lines_with( $out123, array( 'RT_FONT_NO_SERVING_PATH', 'declares no es_font_serving_check' ) ), 'y NO acusa de que falte, porque esta: dos causas distintas son dos mensajes distintos', $out123 );
fx_rrmdir( $r123 );

echo "--- cableada pero sin procedimiento escrito en ningun .md, tambien FALLA ---\n";
$r124 = fx_tmp_root();
fx_base( $r124 );
/* Un aviso sin procedimiento detras es una linea mas por la que pasar de largo: el operador lee
   "no puedo confirmar que se sirva Manrope" y no tiene donde ir. */
fx_builder_skill( $r124, fx_builder(), false );
list( , $out124 ) = fx_run_ok( $audit, $r124 );
ok( 'FAIL' === fx_row_level( $out124, array( 'RT_FONT_NO_SERVING_PATH' ) ), 'sin .md que la nombre, FALLA', fx_row_level( $out124, array( 'RT_FONT_NO_SERVING_PATH' ) ) );
ok( array() !== fx_lines_with( $out124, array( 'RT_FONT_NO_SERVING_PATH', 'nowhere to go' ) ), 'y dice que el operador no tiene donde ir', $out124 );
ok( array() === fx_lines_with( $out124, array( 'RT_FONT_NO_SERVING_PATH', 'nothing in it CALLS' ) ), 'y no acumula las otras dos causas, que si estan cubiertas', $out124 );
fx_rrmdir( $r124 );

echo "--- una llamada a una comprobacion que NO existe tambien FALLA ---\n";
$r126 = fx_tmp_root();
fx_base( $r126 );
/* La tercera causa por separado, y es la que ningun otro escenario aisla: el escenario 122 se queda
   sin declaracion Y sin llamada, asi que borrar este arm dejaria la fila disparandose igual por la
   otra causa y nadie se enteraria. Un renombrado de la comprobacion produce exactamente esta forma:
   quedan las llamadas, desaparece la funcion. */
fx_builder_skill( $r126, fx_builder( array( 'font_decl' => false ) ) );
list( , $out126 ) = fx_run_ok( $audit, $r126 );
ok( 'FAIL' === fx_row_level( $out126, array( 'RT_FONT_NO_SERVING_PATH' ) ), 'llamar a una comprobacion que no se declara FALLA', fx_row_level( $out126, array( 'RT_FONT_NO_SERVING_PATH' ) ) );
ok( array() !== fx_lines_with( $out126, array( 'RT_FONT_NO_SERVING_PATH', 'declares no es_font_serving_check' ) ), 'y dice que lo que falta es la declaracion', $out126 );
ok( array() === fx_lines_with( $out126, array( 'RT_FONT_NO_SERVING_PATH', 'nothing in it CALLS' ) ), 'y no acusa de que nadie la llame, porque si la llaman', $out126 );
fx_rrmdir( $r126 );

echo "--- una cara web-safe NO necesita servirse y NO es un hallazgo ---\n";
$r125 = fx_tmp_root();
fx_base( $r125 );
/* Sin esta exclusion la fila acusaria a un proyecto que eligio Georgia a proposito, y una fila que
   acusa a lo correcto es una fila que se apaga. Las tres piezas se quitan a la vez: si la exclusion
   se rompiera, este arbol FALLARIA por las mismas tres causas del escenario 122. */
fx_builder_skill( $r125, fx_builder( array( 'font_family' => 'Georgia, serif', 'font_decl' => false, 'font_call' => false ) ), false );
list( , $out125 ) = fx_run_ok( $audit, $r125 );
ok( array() === fx_lines_with( $out125, array( 'RT_FONT_NO_SERVING_PATH' ) ), 'Georgia no produce fila: el navegador ya la tiene', $out125 );
fx_rrmdir( $r125 );

echo "--- fixture coverage: declared - observed - exempt must be empty ---\n";
$fx_observed = array_keys( $GLOBALS['fx_observed_ids'] );
sort( $fx_observed );
$fx_missing = array_diff( $declared_row_types, $fx_observed, COVERAGE_EXEMPT );
ok(
	array() === $fx_missing,
	'every row type ROW_TYPES declares is produced by at least one fixture above',
	array( 'declared' => count( $declared_row_types ), 'observed' => count( $fx_observed ), 'missing' => array_values( $fx_missing ) )
);
ok( count( COVERAGE_EXEMPT ) <= 3, 'the exempt list has not grown past its ratchet ceiling', COVERAGE_EXEMPT );

echo "--- no run of the audit above printed a PHP diagnostic on its own output ---\n";
ok( array() === $GLOBALS['fx_diagnostics'], 'not one fixture run emitted a PHP warning/notice/deprecation into the audit output the gate reads', array_keys( $GLOBALS['fx_diagnostics'] ) );

/* -------------------------------------------------------------- report */

printf( "\n%d OK / %d FAIL\n", $pass, $fail );
exit( $fail ? 1 : 0 );
