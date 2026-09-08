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

/* Mirrors framework-audit.php's own (retired, style-catalog PR 4c) $PERS_IDS / current
   $PERS_FIELDS (never imported — the audit is a subprocess, see the file header) so a fixture
   catalog is built to the exact shape the parser expects: one "### `ID`" heading per entry
   (still `PERS-*`, unchanged — see fx_sty_catalog()'s own comment for why), each followed by
   every required "**Field:**" bullet. $FX_PERS_IDS itself still names the same 5 fixture ids;
   only the ROW MEANING behind $PERS_IDS changed (no required-membership list exists in
   framework-audit.php any more), not this array's shape. */
$FX_PERS_IDS    = array(
	'PERS-EDITORIAL', 'PERS-MATTER', 'PERS-DIRECT', 'PERS-INSTITUTIONAL', 'PERS-VITRINE',
);
$FX_PERS_FIELDS = array( 'Axes', 'Fits', 'Typography', 'Motion intensity', 'Imagery', 'Card recipe' );
/* One well-separated axis position per fixture ID, mirroring the real catalog's own anchors (see
   references/style-catalog/) so a "conforming" fixture catalog is actually conforming under
   RT_STYLE_TOO_SIMILAR too -- a fixture catalog that shared axes wholesale would fail every
   fx_base()-rooted scenario on a check that scenario has nothing to do with. */
/* Trailing 3 (accent, chassis, ornament -- style-catalog PR 2b) transcribed verbatim from the real
   anchor table (design-personalities.md when this comment was written; references/style-catalog/
   since style-catalog PR 4c), same discipline as the original five. */
$FX_PERS_AXES = array(
	'PERS-EDITORIAL'     => array( 'editorial', 'paper', 'generous', 'asymmetric', 'none', 'none', 'rule-divided', 'rule' ),
	'PERS-MATTER'        => array( 'classic', 'warm', 'standard', 'strict-grid', 'hairline', 'tinted-field', 'bordered', 'texture' ),
	'PERS-DIRECT'        => array( 'monumental', 'ink', 'compact', 'broken-grid', 'accent-glow', 'gradient', 'bare', 'none' ),
	'PERS-INSTITUTIONAL' => array( 'contained', 'cool', 'standard', 'centered', 'soft-shadow', 'reserved', 'carded', 'illustration' ),
	/* One axis with each of the other four and never two -- scale with EDITORIAL, composition with
	   MATTER, ground with DIRECT, elevation with INSTITUTIONAL. Transcribed from the real block, not
	   invented here: a fixture anchor placed by hand is how a fixture catalog starts failing
	   RT_STYLE_TOO_SIMILAR in every scenario that has nothing to do with it. */
	'PERS-VITRINE'       => array( 'editorial', 'ink', 'monumental', 'strict-grid', 'soft-shadow', 'metallic', 'soft-carded', 'none' ),
);

/* Mirrors framework-audit.php's own $PERS_AXES (never imported -- the audit is a subprocess, see
   the file header), so fx_base()'s design-system.md can be built to carry a value for every
   position every axis defines. Task 2's RT_AXIS_VALUE_MISSING check runs at TOP LEVEL, unconditional
   on whether the style catalog exists, so it also runs unconditionally over every one of this
   suite's fx_base()-rooted fixtures -- the same shape as RT_PERS_CATALOG_MISSING before it, and the
   same trap: without a conforming design-system.md in the skeleton, every fixture in this entire
   file would carry 20 fresh FAILs that have nothing to do with what it actually tests. */
/* accent/chassis/ornament (style-catalog PR 2b): fx_ds_conforming() below has no $real entry for
   them, same as composition -- they fall through to the neutral literal `1`, which satisfies
   RT_AXIS_VALUE_MISSING without asserting a value RT_MOCKUP_AXES_MISMATCH never token-checks
   anyway (axis_token_props() returns none for a marker axis). */
$FX_AXIS_POSITIONS = array(
	'scale'       => array( 'contained', 'classic', 'editorial', 'monumental' ),
	/* Nine, not four (style-catalog PR 3a widened design-system.md's own Ground table; PR 4c
	   widened framework-audit.php's nm_axes() to match -- RT_AXIS_VALUE_MISSING runs unconditional
	   over nm_axes()'s OWN list, in the real subprocess, so this mirror has to carry all nine or
	   every fx_base()-rooted fixture in this file fails a check that has nothing to do with what
	   it tests). */
	'ground'      => array( 'paper', 'warm', 'cool', 'cream', 'earth', 'saturated', 'ink', 'ink-warm', 'ink-cool' ),
	'density'     => array( 'compact', 'standard', 'generous', 'monumental' ),
	'composition' => array( 'centered', 'asymmetric', 'strict-grid', 'broken-grid' ),
	'elevation'   => array( 'none', 'hairline', 'soft-shadow', 'accent-glow' ),
	'accent'      => array( 'none', 'reserved', 'tinted-field', 'duotone', 'gradient', 'metallic', 'polychrome' ),
	'chassis'     => array( 'bare', 'carded', 'soft-carded', 'bordered', 'rule-divided', 'hard-shadow', 'strict-grid', 'layered' ),
	'ornament'    => array( 'none', 'rule', 'texture', 'pattern', 'illustration' ),
);

/* A conforming design-system.md: every position $FX_AXIS_POSITIONS names gets its OWN table row,
   backticked, once (`monumental` covers both scale and density, so it is de-duplicated rather than
   repeated -- same first-axis-wins order as before; no fixture in this suite value-checks density
   at `monumental`), with a token-shaped value beside it.
   REAL numbers, transcribed verbatim from design-system.md's own tables, not the placeholder `1`
   this fixture used before style-catalog PR 1e: RT_MOCKUP_AXES_MISMATCH's new value check
   (axis_token_values()) reads THIS file exactly the way it reads the real one, so a `:root` token
   fx_mockup()/fx_proof() declare has to agree with a REAL row here or every scenario using them
   would carry a false FAIL that has nothing to do with what it tests -- the same trap this
   fixture was already written once to avoid, for RT_AXIS_VALUE_MISSING, one check earlier. Column
   SHAPE matches axis_token_props(): scale/ground need three cells, density/elevation one; a
   position outside $real (composition has no token to check) keeps the neutral `1`. */
function fx_ds_conforming() {
	global $FX_AXIS_POSITIONS;
	$real = array(
		'scale'     => array(
			'contained'  => '1.200 | 1.25 | 48',
			'classic'    => '1.333 | 1.10 | 64',
			'editorial'  => '1.500 | 0.95 | 88',
			'monumental' => '1.618 | 0.82 | 120',
		),
		'ground'    => array(
			'paper'     => '#FFFFFF | #F6F7F8 | #15181A',
			'warm'      => '#FFF3E3 | #F7E8D4 | #241C14',
			'cool'      => '#F2F5F8 | #E8EDF3 | #141C24',
			'cream'     => '#FBF4DD | #F3E7C4 | #221D0E',
			'earth'     => '#F3E4C8 | #EEDBB8 | #2B1608',
			'saturated' => '#F6D8DC | #F3D0D5 | #2B1015',
			'ink'       => '#0E1113 | #171B1E | #F4F6F7',
			'ink-warm'  => '#171008 | #221808 | #F7EFE2',
			'ink-cool'  => '#0B0F1C | #141B2E | #EAF0FF',
		),
		'density'   => array(
			'compact'    => '0.8',
			'standard'   => '1.0',
			'generous'   => '1.35',
			'monumental' => '1.7',
		),
		'elevation' => array(
			'none'        => 'none',
			'hairline'    => '`0 0 0 1px var(--c-border)`',
			'soft-shadow' => '`0 1px 2px rgba(0,0,0,.04)`',
			'accent-glow' => '`0 0 0 1px color-mix(in srgb,var(--c-accent) 22%,transparent)`',
		),
	);
	$seen = array();
	$out  = "# Tokens fixture\n\n| position | value |\n|---|---|\n";
	foreach ( $FX_AXIS_POSITIONS as $axis => $positions ) {
		foreach ( $positions as $pos ) {
			if ( isset( $seen[ $pos ] ) ) {
				continue;
			}
			$seen[ $pos ] = true;
			$val           = isset( $real[ $axis ][ $pos ] ) ? $real[ $axis ][ $pos ] : '1';
			$out          .= '| `' . $pos . '` | ' . $val . ' |' . "\n";
		}
	}
	return $out;
}

/* Builds a design-personalities.md catalog: every $FX_PERS_IDS block, every $FX_PERS_FIELDS
   bullet filled in. $skip_id omits one whole personality block (for RT_PERS_ID_MISSING);
   $skip_field_pid + $skip_field omit one bullet from one block (for RT_PERS_MISSING_FIELD) — a
   caller sets one pair or the other, never both, so each scenario proves exactly one failure
   mode. */
function fx_pers_catalog( $skip_id = null, $skip_field_pid = null, $skip_field = null ) {
	global $FX_PERS_IDS, $FX_PERS_FIELDS, $FX_PERS_AXES;
	$out = "# Design personalities fixture\n\n";
	foreach ( $FX_PERS_IDS as $pid ) {
		if ( $pid === $skip_id ) {
			continue;
		}
		$out .= "### `$pid`\n";
		foreach ( $FX_PERS_FIELDS as $field ) {
			if ( $pid === $skip_field_pid && $field === $skip_field ) {
				continue;
			}
			if ( 'Axes' === $field ) {
				list( $scale, $ground, $density, $composition, $elevation, $accent, $chassis, $ornament ) = $FX_PERS_AXES[ $pid ];
				$out .= '**Axes:** scale `' . $scale . '` · ground `' . $ground . '` · density `' . $density
					. '` · composition `' . $composition . '` · elevation `' . $elevation
					. '` · accent `' . $accent . '` · chassis `' . $chassis . '` · ornament `' . $ornament . "`\n";
			} else {
				$out .= '**' . $field . ':** fixture value.' . "\n";
			}
		}
		$out .= "\n";
	}
	return $out;
}

/* style-catalog PR 4c: the real catalog moved from one design-personalities.md file (multiple
   `### `ID`` blocks in one file) to one file per entry under references/style-catalog/, glob-
   matched on `STY-*.md`. framework-audit.php's ID-extraction regex does NOT hardcode a `PERS-`/
   `STY-` prefix (catalog MEMBERSHIP is the glob's job, not the heading's), so the fixture ids
   below stay the historical `PERS-*` vocabulary unchanged -- none of the ~80 existing assertions
   naming them had to move. Only the FILENAME needs the real `STY-` prefix, to satisfy the glob
   framework-audit.php itself hardcodes; `STY-` is prepended to the id for the filename only, never
   written into the file's own heading, so `has($out, 'PERS-EDITORIAL')` keeps matching the row
   text exactly as before. Splits the SAME concatenated-block string fx_pers_catalog()/fx_pers()
   already built for the old single-file shape, so every existing caller keeps building the exact
   block text it always did; this function is the only thing that needed to change to follow the
   real repoint. */
function fx_sty_catalog( $root, $blocks ) {
	foreach ( preg_split( '/(?=^### `[A-Z][A-Z-]*`)/m', $blocks, -1, PREG_SPLIT_NO_EMPTY ) as $fx_sty_block ) {
		if ( ! preg_match( '/^### `([A-Z][A-Z-]*)`/m', $fx_sty_block, $fx_sty_m ) ) {
			continue;
		}
		fx( $root, 'skills/ux-design-system/references/style-catalog/STY-' . $fx_sty_m[1] . '.md', $fx_sty_block );
	}
}
/* The r26 "no catalog at all" scenario needs the inverse: every STY-*.md file gone, so the
   directory has zero entries -- RT_PERS_CATALOG_MISSING's real trigger post-PR-4c, since a single
   `unlink()` no longer means anything once the catalog is N files, not one. */
function fx_sty_catalog_clear( $root ) {
	foreach ( glob( $root . '/skills/ux-design-system/references/style-catalog/STY-*.md' ) as $fx_sty_f ) {
		unlink( $fx_sty_f );
	}
}
/* style-catalog PR 5b/5c/6: writes skills/ux-design-system/references/shipped-log.md, the real
   ledger's exact column order (Date | Client | Style | Ground | Accent | Scale | Chassis |
   Toggles | Route). $rows is a list of 7-, 8- or 9-cell arrays, appended in the order given --
   the 8th (Toggles, PR 5c) and 9th (Route, PR 6) are both optional per row, exactly like the real
   file's own optional cells; a shorter row simply reads back with the missing columns empty
   (ledger_table_rows()'s own isset() fallback -- unchanged by widening the header, since it maps
   by NAME, not by cell count). The real file is append-only, newest last, so a scenario's own row
   order IS its delivery order. */
function fx_ledger( $root, array $rows ) {
	$out = "# Shipped log fixture\n\n"
		. "| Date | Client | Style | Ground | Accent | Scale | Chassis | Toggles | Route |\n"
		. "|---|---|---|---|---|---|---|---|---|\n";
	foreach ( $rows as $fx_lr ) {
		$out .= '| ' . implode( ' | ', $fx_lr ) . " |\n";
	}
	fx( $root, 'skills/ux-design-system/references/shipped-log.md', $out );
}

/* style-catalog PR 5c: one NEW STY-*.md fixture entry (not one of fx_base()'s 5 defaults) carrying
   a real "## Toggle precharge" table -- RT_STYLE_PRECHARGE_UNSHIPPED's own real input, since
   fx_pers()'s plain block (every other scenario in this suite) declares no precharge at all. Axes
   are reused VERBATIM from an existing $FX_PERS_AXES entry ($axes_from) so every position is one
   the registry already knows -- RT_PERS_BAD_AXIS is not this scenario's concern, and neither is
   RT_STYLE_TOO_SIMILAR (a fixture-only id, disclosed here rather than hidden, since diversifying
   the axes would add lines this rule's own test has no use for). $tgl_rows is a list of
   [id, value] pairs, written in the exact backticked shape the real STY-*.md catalog uses. */
function fx_sty_precharge( $root, $id, $axes_from, array $tgl_rows ) {
	global $FX_PERS_AXES;
	$block = call_user_func_array( 'fx_pers', array_merge( array( $id ), $FX_PERS_AXES[ $axes_from ] ) );
	$table = "## Toggle precharge\n\n| Toggle | Precharge | Why |\n|---|---|---|\n";
	foreach ( $tgl_rows as $tr ) {
		$table .= '| `' . $tr[0] . '` | `' . $tr[1] . "` | fixture |\n";
	}
	fx( $root, 'skills/ux-design-system/references/style-catalog/' . $id . '.md', $block . $table );
}

/* style-catalog PR 6: a ROUTE-BESPOKE intake declaration -- shares fx_pers()'s exact axes-line
   shape (parsed by the same pers_axes() RT_PERS_BAD_AXIS uses) plus a wireframe block in
   fx_tpl()'s exact shape (parsed by the same tpl_wireframe_comps() RT_TPL_NO_WIREFRAME uses).
   $ornament === null (fx_pers()'s own omission convention) writes an incomplete manifest --
   RT_BESPOKE_UNDECLARED's own RED case; $comps === null omits the wireframe heading entirely. */
function fx_bsp( $root, $id, $scale, $ground, $density, $composition, $elevation, $accent = null, $chassis = null, $ornament = null, $comps = array( 'HERO' ) ) {
	$block = fx_pers( $id, $scale, $ground, $density, $composition, $elevation, $accent, $chassis, $ornament );
	if ( null !== $comps ) {
		$block .= "## 2. Wireframe\n\n```\n";
		foreach ( $comps as $c ) {
			$block .= 'COMP-' . $c . "\n";
		}
		$block .= "```\n";
	}
	fx( $root, 'skills/ux-design-system/references/style-catalog/BSP-' . $id . '.md', $block );
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

/** One anchor block in the exact shape the audit parses. style-catalog PR 2b: $accent, $chassis,
    $ornament default to null, which OMITS that axis from the **Axes:** line entirely -- the shape
    an undefined-axis RED fixture needs (RT_PERS_BAD_AXIS), same null-omits convention fx_proof()
    already uses. */
function fx_pers( $id, $scale, $ground, $density, $composition, $elevation, $accent = null, $chassis = null, $ornament = null ) {
	$axes_line = "scale `$scale` · ground `$ground` · density `$density` · composition `$composition` · elevation `$elevation`";
	foreach ( array( 'accent' => $accent, 'chassis' => $chassis, 'ornament' => $ornament ) as $nm_axis => $nm_val ) {
		if ( null !== $nm_val ) {
			$axes_line .= " · $nm_axis `$nm_val`";
		}
	}
	return "### `$id` — Fixture\n\n"
		. "**Axes:** $axes_line\n\n"
		. "**Fits:** fixture.\n\n**Typography:** fixture.\n\n**Motion intensity:** fixture.\n\n"
		. "**Imagery:** fixture.\n\n**Card recipe:** fixture.\n\n";
}

/**
 * One home archetype, reduced to what RT_TPL_TOO_SIMILAR reads: the fenced block under
 * "## 2. Wireframe" and the COMP-* ids inside it.
 *
 * $comps is the wireframe inventory as bare suffixes ('HEADER', 'HERO', …). $decoy is a run of
 * COMP-* ids planted in § 3, OUTSIDE the block — the parser must ignore them, because § 3 names
 * components a section is merely compared against and § 4 restates a subset, and counting either
 * would let a doc's commentary decide its architecture.
 *
 * $wire === null writes the heading with no fenced block; $wire === 'prose' writes a block whose
 * rows are ALL prose; $wire === 'mixed' writes the ids plus ONE prose row, which is the dangerous
 * shape — the block still parses and the archetype still enters the comparison, one section
 * lighter than it really is. Three causes, one row id: RT_TPL_NO_WIREFRAME.
 */
function fx_tpl( $id, array $comps, array $decoy = array(), $wire = 'fenced' ) {
	$out = "# $id — Fixture\n\n## 1. Identidad\n\nFixture archetype.\n\n## 2. Wireframe (top → bottom)\n\n";
	if ( 'fenced' === $wire || 'mixed' === $wire ) {
		$out .= "```\n";
		foreach ( $comps as $c ) {
			$out .= 'COMP-' . $c . "        [fijo]\n";
		}
		if ( 'mixed' === $wire ) {
			$out .= "Editorial / Lookbook     [fijo]\n";
		}
		$out .= "```\n\n";
	} elseif ( 'prose' === $wire ) {
		$out .= "```\nCabecera        [fijo]\nBanda editorial [fijo]\nPie de pagina   [fijo]\n```\n\n";
	}
	$out .= "## 3. Secciones\n\n";
	foreach ( $decoy as $d ) {
		$out .= "### COMP-$d `[fijo]`\nObjetivo: fixture prose that names an id the wireframe does not carry.\n\n";
	}
	$out .= "## 4. Toggles admitidos\n\n**Fijos:** fixture.\n\n## 5. SEO / semántica\n\nFixture.\n";

	return $out;
}

/**
 * One proof mockup, reduced to the only thing RT_PROOF_NOT_DISTINCT reads: a `:root` block
 * carrying the five axis signatures. Everything a real proof file also has — the copy, the
 * layout, the placeholder recipe — is deliberately absent, so a fixture that fails fails on the
 * axis VALUES and on nothing else.
 *
 * $axes is array( scale, ground, density, elevation, composition, accent, chassis, ornament ) --
 * the trailing three are style-catalog PR 2b's marker axes, same shape composition already used.
 * Passing null for one omits that declaration entirely, which is how the "neither declares it"
 * branch gets a fixture.
 * The trailing `--c-bg-alt` is not decoration: it sits beside `--c-bg` exactly as it does in the
 * real files, so the fixture exercises the (?![\w-]) boundary that stops the ground axis from
 * reading its neighbour.
 *
 * $copy is the human-visible strings the file renders, in order, for RT_PROOF_COPY_DIFFERS —
 * first entry as the h1, the rest as paragraphs. Repeat an entry to render it twice; the row
 * compares MULTISETS, so a repeat is a real difference and the fixtures have to be able to make
 * one. Default is the single string every axis scenario above was already written against.
 *
 * $noise is everything the copy comparison must IGNORE, moved by one fixture and not the other:
 * the HTML comment, two attribute values, and — when it is set — a newline plus indentation
 * injected into the middle of the first text run. A copy check that compared raw source, or kept
 * attribute values, or compared whitespace, would fail the "same copy" scenario on this alone.
 */
function fx_proof( array $axes, $copy = null, $noise = '' ) {
	list( $scale, $ground, $density, $elevation, $composition, $accent, $chassis, $ornament ) = $axes + array_fill( 0, 8, null );
	$decl = '';
	if ( null !== $scale ) {
		$decl .= "  --type-ratio: $scale; --display-lh: 0.95; --fs-h1-max: 88;\n";
	}
	if ( null !== $ground ) {
		$decl .= "  --c-bg: $ground; --c-bg-alt: #F6F7F8; --c-text: #15181A;\n";
	}
	if ( null !== $density ) {
		$decl .= "  --sp-scale: $density;\n";
	}
	if ( null !== $elevation ) {
		$decl .= "  --elev-rest: $elevation; --elev-hover: none;\n";
	}
	if ( null !== $composition ) {
		$decl .= "  /* composition: $composition */\n";
	}
	foreach ( array( 'accent' => $accent, 'chassis' => $chassis, 'ornament' => $ornament ) as $nm_axis => $nm_val ) {
		if ( null !== $nm_val ) {
			$decl .= "  /* $nm_axis: $nm_val */\n";
		}
	}

	$copy = ( null === $copy ) ? array( 'Fixture' ) : $copy;
	$body = '';
	foreach ( $copy as $i => $line ) {
		$tag = ( 0 === $i ) ? 'h1' : 'p';
		/* The reflow only happens on the noisy side, so one fixture writes a run on one source
		   line and its twin writes the SAME run across two. */
		$text  = ( '' === $noise ) ? $line : preg_replace( '/ /', "\n      ", $line, 1 );
		$body .= '<' . $tag . ' class="row' . $i . ' ' . $noise . '" aria-label="' . $noise . '">' . $text . '</' . $tag . ">\n";
	}

	/* The comment carries ANGLE BRACKETS on purpose — the real proof headers quote the framework
	   breakpoints exactly like this. Without them a comment is stripped either by the comment rule
	   or, incidentally, by the tag rule, and deleting the comment rule changes nothing; with them
	   the tag rule stops at the first `>` inside and leaves the tail of the comment behind as
	   "copy". $noise sits AFTER the brackets so that leaked tail differs between the two files. */
	return '<!-- proof mockup fixture; breakpoints <768 / 768-1024 / >1024 · noise: ' . $noise . " -->\n<style>\n:root{\n" . $decl . "}\n"
		. "  .card{box-shadow:var(--elev-rest)}\n"     /* a USE of the property, outside :root */
		. "</style>\n" . $body;
}

/**
 * One PRODUCTION mockup asset — the kind a real project is copied from — reduced to the only
 * thing RT_MOCKUP_NO_AXES reads: a `:root` block declaring the perceptual axes, plus the
 * composition marker that is the one axis a token cannot carry.
 *
 * $omit names the declarations to leave OUT of `:root`, which is how each "missing" scenario gets
 * a fixture. Everything a real production mockup also has — six or seven pages, the nav script,
 * the placeholder recipe — is deliberately absent, so a fixture that fails fails on the axis
 * DECLARATIONS and on nothing else.
 *
 * $outside names declarations to emit OUTSIDE `:root` while still omitting them from it, and it is
 * what holds the check's `:root` SCOPING in place. The audit reads `$mockup_root`, not the file,
 * and the comment there says a whole-file scan "would match a USE and call it a declaration". The
 * `.card{box-shadow:var(--elev-rest)}` line below is that use — but it is NOT what defends the
 * scoping, and this docblock claimed it was. The check's regex is `--elev-rest(?![\w-])\s*:`: it
 * demands a COLON after the property name, and `var(--elev-rest)}` has a `)` there, so a use can
 * never be mistaken for a declaration and swapping `$mockup_root` for the whole file left every
 * assertion in this suite green. A real DECLARATION outside `:root` is what the scope actually
 * excludes, and both shapes are real in the production files: a `:root[data-theme="dark"]` block
 * (both mockups carry one) and any ordinary rule setting a custom property. `$outside` writes
 * both, so the theme block is what a whole-file scan would read as the light palette's
 * declaration — which is exactly the file that must FAIL: a mockup declaring an axis only under
 * `prefers-color-scheme: dark` cannot express that axis at all in the light default every project
 * ships. The `.card` line stays because a real file has one; it is scenery, and it is labelled
 * scenery now.
 *
 * $fonts drives RT_MOCKUP_FONT_NOT_EMBEDDED and DEFAULTS TO EMPTY — no `--font-primary`, no
 * `font-family`, no `@font-face` — so every scenario above stays exactly as green as it was. That
 * default is load-bearing rather than lazy: the row fires on a family ASKED FOR and not served, so
 * a fixture that asks for nothing can never emit it, and the axis scenarios keep testing axes.
 *
 *   'stack'  — the `--font-primary` value written into `:root`
 *   'rule'   — a `font-family` written into a `@media` block, OUTSIDE `:root`, which is how the
 *              whole-file half of the scan gets a fixture; a `:root`-only reader misses it
 *   'faces'  — family => src, emitted as `@font-face` ahead of `:root`, exactly as production does
 */
function fx_mockup( array $omit = array(), array $outside = array(), array $fonts = array(), array $band = array(), $anchor = null, $fs_h1_max = null ) {
	/* array( 'PERS-X', 'ground-position' ) — the anchor line plus the one label that has no token of
	   its own to ride on. Default is the conforming pair; a scenario passes something else to make
	   RT_MOCKUP_AXES_MISMATCH fire. */
	$anchor_pid    = ( null === $anchor ) ? 'PERS-INSTITUTIONAL' : $anchor[0];
	$anchor_ground = ( null === $anchor ) ? 'cool' : $anchor[1];
	/* style-catalog PR 1e: $fs_h1_max hand-types a WRONG number beside a label that still reads
	   `contained` — the LABEL stays correct while the VALUE drifts, which is the one shape $anchor
	   above cannot produce (it corrupts every label at once). Default null keeps the conforming 48,
	   byte-identical to every scenario written before this parameter existed. */
	$fs_h1_max = ( null === $fs_h1_max ) ? '48' : $fs_h1_max;
	/* One source for every declaration so `:root` and $outside emit byte-identical text — a
	   fixture whose two copies differed could pass the scoping test for the wrong reason. */
	/* PERS-INSTITUTIONAL throughout — the same anchor the real corporate-mockup.html stands at, and
	   the values are its own. The anchor marker and the four position labels ride along because
	   RT_MOCKUP_ANCHOR_UNDECLARED requires the first and RT_MOCKUP_AXES_MISMATCH reads the rest, and a
	   base fixture that failed either would charge every scenario in this suite for a row it has
	   nothing to do with. They are NOT part of $omit: $omit exists to drop a TOKEN and prove
	   RT_MOCKUP_NO_AXES sees it, and a dropped token with its label still in place is precisely the
	   shape the two rows separate — one asks whether the axis is declared, the other whether the
	   declaration is coherent. $anchor overrides the pair for the scenarios that need a mismatch. */
	$axis_decl = array(
		'--type-ratio' => '--type-ratio: 1.200;  /* scale: contained */',
		'--display-lh' => '--display-lh: 1.25;',
		'--fs-h1-max'  => '--fs-h1-max: ' . $fs_h1_max . ';',
		'--sp-scale'   => '--sp-scale: 1.0;  /* density: standard */',
		'--elev-rest'  => '--elev-rest: 0 1px 2px rgba(0,0,0,.04); --elev-hover: none;  /* elevation: soft-shadow */',
		'composition'  => '/* composition: LP-CENTERED */',
		/* style-catalog PR 2b: PERS-INSTITUTIONAL's own three new positions, same reasoning as
		   composition above -- a base fixture missing them would MISMATCH every default-anchor
		   scenario in this whole suite against $axes_of['PERS-INSTITUTIONAL']. */
		'accent'       => '/* accent: reserved */',
		'chassis'      => '/* chassis: carded */',
		'ornament'     => '/* ornament: illustration */',
	);
	$decl = '    /* Anchor: ' . $anchor_pid . " */\n"
		. '    --c-bg:#F2F5F8; --c-bg-alt:#E8EDF3;  /* ground: ' . $anchor_ground . " */\n";
	foreach ( $axis_decl as $axis_key => $axis_line ) {
		if ( ! in_array( $axis_key, $omit, true ) ) {
			$decl .= '    ' . $axis_line . "\n";
		}
	}
	/* Custom properties go in a `:root[data-theme="dark"]` block AND in an ordinary rule; the
	   composition marker is a comment, so it just sits loose in the stylesheet. `:root[` is not
	   `:root{`, so the audit's `/:root\s*\{(.*?)\}/s` cannot reach either of them — which is the
	   whole point of the scenario. */
	$theme_decl = '';
	$rule_decl  = '';
	$loose      = '';
	foreach ( $outside as $axis_key ) {
		if ( ! isset( $axis_decl[ $axis_key ] ) ) {
			continue;
		}
		if ( 'composition' === $axis_key ) {
			$loose .= '  ' . $axis_decl[ $axis_key ] . "\n";
			continue;
		}
		$theme_decl .= '    ' . $axis_decl[ $axis_key ] . "\n";
		$rule_decl  .= '  .panel{' . $axis_decl[ $axis_key ] . "}\n";
	}
	$outside_block = ( '' === $theme_decl ) ? '' : "  :root[data-theme=\"dark\"]{\n" . $theme_decl . "  }\n";

	/* The faces go ahead of `:root`, where production puts them. `@font-face{…}` carries braces but
	   no `:root`, so the axis scan's `/:root\s*\{(.*?)\}/s` still lands on the real block. */
	$face_block = '';
	foreach ( ( isset( $fonts['faces'] ) ? $fonts['faces'] : array() ) as $face_fam => $face_src ) {
		$face_block .= "  @font-face{font-family:'" . $face_fam . "';font-style:normal;font-weight:400 700;"
			. 'src:url(' . $face_src . ") format('woff2')}\n";
	}
	$font_decl = isset( $fonts['stack'] ) ? '    --font-primary: ' . $fonts['stack'] . ";\n" : '';
	$font_rule = isset( $fonts['rule'] )
		? "  @media(min-width:768px){ .hero h1{font-family:" . $fonts['rule'] . "} }\n"
		: '';

	/* $band drives RT_MOCKUP_BLEED_FIXED_BAND and DEFAULTS TO NEITHER HALF, so every scenario above
	   stays exactly as green as it was: no `--content-width` at all, and no `full-end` anywhere. The
	   row needs BOTH arms to fire and each key writes one of them, so a scenario can pin the band
	   WITHOUT a bleed (correct — LP-CENTERED does exactly that, and the row must stay silent) or
	   bleed with a fluid band (also correct). Those are the two mis-implementations that would turn
	   this into a row failing every centred mockup in the framework. */
	$band_decl = isset( $band['width'] ) ? '    --content-width: ' . $band['width'] . ";\n" : '';
	/* $band['sel'] drives RT_MOCKUP_BLEED_NOT_MEDIA and DEFAULTS TO `.hero .media`, which is media
	   and therefore silent — so every scenario that only wanted a bleed for the FIXED_BAND row
	   above keeps exactly the verdict it had. A scenario that wants to bleed something else names
	   it. `full-end` in a comment is `$band['comment']`, which must NOT count: the first version of
	   the row stripped comments after splitting the selector list on `,` and reported two fragments
	   of English prose as offending selectors. */
	$bleed_sel = isset( $band['sel'] ) ? $band['sel'] : '.hero .media';
	/* THE COMMENT HAS TO SIT IMMEDIATELY ABOVE THE BLEEDING RULE OR IT TESTS NOTHING. The first
	   version of this fixture put it above a rule that did NOT bleed, so the extraction regex —
	   which requires `full-start|full-end` INSIDE the braces — never matched, and the scenario
	   passed whether comments were stripped or not. It was caught by mutating the strip out of
	   framework-audit.php and watching the suite stay green. Here the prose carries two commas and
	   the word `full-end`, so an implementation that splits the selector list before stripping
	   comments reports two fragments of English as offending selectors. */
	$bleed_note = empty( $band['comment'] ) ? ''
		: "  /* the one bleed per section, always the same edge, all the way down to full-end */\n";
	$band_rule = empty( $band['bleed'] ) ? '' : $bleed_note . "  {$bleed_sel}{grid-column:c 8/full-end}\n";
	return "<!-- production mockup fixture -->\n<style>\n" . $face_block . "  :root{\n" . $decl . $font_decl . $band_decl . "  }\n"
		. $outside_block . $rule_decl . $loose . $font_rule . $band_rule
		. "  .card{box-shadow:var(--elev-rest)}\n"
		. "</style>\n<h1>Maqueta</h1>\n";
}

/**
 * One GALLERY asset — the shape RT_GALLERY_NOT_DISTINCT and RT_GALLERY_NO_MANIFEST read.
 *
 * A gallery is many `TPL-* x PERS-*` cards in ONE document, so its axis tokens cannot live in
 * `:root` (one `:root` cannot hold four values of --c-bg): each anchor gets a `[data-anchor="key"]`
 * block, and `:root` still carries a real default, exactly as the production file does. The `:root`
 * half is what RT_MOCKUP_NO_AXES reads and $root_omit is how a scenario takes one axis out of it.
 *
 * $strips is the card list, in document order: each entry sets 'tpl' and/or 'pers', and OMITTING a
 * key is how the "declares no data-tpl" scenario is written. $anchors maps a pers key to its five
 * axis values, or to null to emit no block at all for it.
 *
 * Three pieces are scenery that must NOT be read, and each one kills a plausible mis-implementation:
 *   · a `<section class="sec">` inside every strip — a check counting every <section> would report
 *     it as a strip with no data-tpl, so the conforming scenario turns red;
 *   · a `[data-anchor="key"] .card{…}` descendant rule after every block — the selector matches but
 *     the brace does not follow the bracket, and a looser regex would read a card recipe as an
 *     anchor's axis declarations;
 *   · $decoy, a CSS COMMENT quoting each anchor's selector followed by `{…}`. That is the trap this
 *     repo has already paid for once on `:root`: the ellipsis is three UTF-8 bytes, so a check that
 *     took only the FIRST matching block would read a three-byte body, find no axes in it, and
 *     report two anchors as identical. With every block concatenated the comment contributes
 *     nothing. The comment quotes EVERY anchor on purpose — quoting one would leave that anchor
 *     empty against a real one, which still reads as five axes apart and would let the mutant live.
 */
function fx_gallery( array $strips, array $anchors, array $opts = array() ) {
	$o = array_merge(
		array(
			'root_omit' => array(),
			'images'    => array(),
			'decoy'     => true,
		),
		$opts
	);
	$root_decl = array(
		'--type-ratio' => '--type-ratio: 1.200;',
		'--display-lh' => '--display-lh: 1.25;',
		'--fs-h1-max'  => '--fs-h1-max: 48;',
		'--sp-scale'   => '--sp-scale: 1.0;',
		'--elev-rest'  => '--elev-rest: 0 1px 2px rgba(0,0,0,.04); --elev-hover: none;',
		'composition'  => '/* composition: LP-CENTERED */',
	);
	$root = '';
	foreach ( $root_decl as $root_key => $root_line ) {
		if ( ! in_array( $root_key, $o['root_omit'], true ) ) {
			$root .= '    ' . $root_line . "\n";
		}
	}

	$css = '';
	if ( $o['decoy'] ) {
		$css .= "  /* Editing note: an anchor's tokens are scoped rather than declared in :root —\n";
		foreach ( array_keys( $anchors ) as $decoy_key ) {
			$css .= '     [data-anchor="' . $decoy_key . '"]{…} carries its own ground, ' . "\n";
		}
		$css .= "     because one :root cannot hold several values of --c-bg. */\n";
	}
	foreach ( $anchors as $anchor_key => $axes ) {
		if ( null === $axes ) {
			continue;
		}
		/* style-catalog PR 2b: trailing 3 (accent, chassis, ornament) default null via the +
		   array-union fill, same convention fx_proof() uses, so a caller that still passes a
		   5-tuple gets those three axes simply undeclared rather than a PHP notice. */
		list( $scale, $ground, $density, $elevation, $composition, $accent, $chassis, $ornament ) = $axes + array_fill( 0, 8, null );
		/* --c-bg-alt sits beside --c-bg exactly as it does in the real file, so every gallery
		   scenario also exercises the (?![\w-]) boundary that stops the ground axis reading its
		   longer neighbour. */
		$css .= '  [data-anchor="' . $anchor_key . '"]{' . "\n"
			. "    --type-ratio: $scale; --display-lh: 1.10; --fs-h1-max: 64;\n"
			. "    --c-bg: $ground; --c-bg-alt: #F6F7F8; --c-text: #15181A;\n"
			. "    --sp-scale: $density;\n"
			. "    --elev-rest: $elevation; --elev-hover: none;\n"
			. "    /* composition: $composition */\n";
		foreach ( array( 'accent' => $accent, 'chassis' => $chassis, 'ornament' => $ornament ) as $nm_axis => $nm_val ) {
			if ( null !== $nm_val ) {
				$css .= "    /* $nm_axis: $nm_val */\n";
			}
		}
		$css .= "  }\n"
			. '  [data-anchor="' . $anchor_key . '"] .card{border-radius:0;box-shadow:var(--elev-rest)}' . "\n";
	}

	$body = '';
	foreach ( $strips as $strip_ix => $st ) {
		$attrs = '';
		if ( isset( $st['tpl'] ) ) {
			$attrs .= ' data-tpl="' . $st['tpl'] . '"';
		}
		if ( isset( $st['pers'] ) ) {
			$attrs .= ' data-pers="' . $st['pers'] . '"';
		}
		$body .= '<section class="strip" id="s' . $strip_ix . '"' . $attrs . '>' . "\n"
			. '  <div class="sample" data-anchor="' . ( isset( $st['pers'] ) ? $st['pers'] : '' ) . '">' . "\n"
			. "    <section class=\"sec\"><h2>Seccion</h2></section>\n"
			. "  </div>\n</section>\n";
	}
	foreach ( $o['images'] as $img_slug ) {
		$body .= '<figure class="frame"><img data-img="' . $img_slug . '" alt="fixture"></figure>' . "\n";
	}

	return "<!-- gallery fixture -->\n<style>\n  :root{\n" . $root . "  }\n" . $css . "</style>\n" . $body;
}

/**
 * One `_gallery-images.md`, reduced to what RT_GALLERY_NO_MANIFEST reads.
 *
 * $rows maps slug => licence cell; the empty-string KEY writes a row with an empty Slug cell, and an
 * empty VALUE writes a row with an empty Licence cell. $licence_col false drops the column entirely,
 * which is a different finding and gets its own sentence in the message.
 *
 * The "Registers" table above the set is not decoration: its header carries `Slugs`, and the real
 * manifest has one. The check selects a table by a header cell reading exactly `Slug`, so this
 * fixture is what proves the plural does not match — a parser that counted columns from the left, or
 * matched a substring, would read three prose cells as an image row.
 */
function fx_gallery_manifest( array $rows, $licence_col = true, array $opts = array() ) {
	$o = array_merge(
		array(
			'registers'   => 4,
			'shoot_col'   => true,
			'freepik_col' => true,
			'freepik'     => array(),
			'shoot'       => array(),
		),
		$opts
	);

	/* Default ids are one per row and 1,000 apart in the BUCKET, so a default fixture has as many
	   shoots as rows and never trips the concentration bound by accident. A single shared id here
	   — the literal `1` this fixture used before the Shoot column existed — would have put every
	   row in `fp-0` and turned the conforming control red, which is how this default got chosen. */
	$out = "# Gallery image manifest fixture\n\n";
	if ( 0 < $o['registers'] ) {
		$out .= "## Registers\n\n| Register | Slugs | What it says |\n|---|---|---|\n"
			. '| Workshop | ' . implode( ' ', array_keys( $rows ) ) . " | fixture |\n";
		for ( $i = 1; $i < $o['registers']; $i++ ) {
			$out .= '| Register ' . $i . " | | fixture |\n";
		}
		$out .= "\n";
	}
	$out .= "## The set\n\n";

	$head = array( 'Slug', 'Role', 'Freepik', 'Shoot', 'Licence', '`alt`' );
	if ( ! $o['shoot_col'] ) {
		unset( $head[3] );
	}
	if ( ! $o['freepik_col'] ) {
		unset( $head[2] );
	}
	if ( ! $licence_col ) {
		unset( $head[4] );
	}
	$head  = array_values( $head );
	$out  .= '| ' . implode( ' | ', $head ) . " |\n|" . str_repeat( '---|', count( $head ) ) . "\n";

	$n = 0;
	foreach ( $rows as $slug => $lic ) {
		$id    = isset( $o['freepik'][ $slug ] ) ? (string) $o['freepik'][ $slug ] : (string) ( ( 11000 + $n ) * 1000 + 77 );
		$shoot = isset( $o['shoot'][ $slug ] ) ? (string) $o['shoot'][ $slug ] : 'fp-' . (string) intdiv( (int) $id, 1000 );
		++$n;
		$cells = array( ( '' === $slug ) ? '' : '`' . $slug . '`', 'card 4:3', $id, $shoot, $lic, 'alt fixture' );
		if ( ! $o['shoot_col'] ) {
			unset( $cells[3] );
		}
		if ( ! $o['freepik_col'] ) {
			unset( $cells[2] );
		}
		if ( ! $licence_col ) {
			unset( $cells[4] );
		}
		/* An EMPTY cell is written as a single space, exactly as this fixture wrote it before the
		   Shoot column arrived: the empty-Slug scenario pins the reported line number by searching
		   the fixture for `| | card 4:3`, and padding the empty cell to two spaces would move that
		   needle without moving the audit's behaviour — a red test reporting a green defect. */
		$out .= '|' . implode(
			'|',
			array_map(
				function ( $c ) {
					return ( '' === $c ) ? ' ' : ' ' . $c . ' ';
				},
				array_values( $cells )
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
		. '| 1 | The build stands at the approved axis positions | Compare the approved mockup `:root` against `es_tokens()`:'
		. ' `--type-ratio`, `--display-lh`, `--fs-h1-max`, `--sp-scale`, `--elev-rest`,'
		. " and the `LP-CENTERED` composition blueprint the user confirms by eye. World: W1 or W3 | **auto (declared) + eyes (composition)** |\n"
		. $extra_rows;
}

/* A conforming skeleton every fixture starts from: one skill (qa-review, which the audit checks
   by a HARDCODED path regardless of whether the skill exists) plus its house-rules file, an
   offline test file, a conforming ux-design-system skill carrying a conforming style catalog
   (RT_PERS_CATALOG_MISSING is checked the same unconditional way as RT_HOUSERULES_MISSING — see
   framework-audit.php — so it needs the same treatment here), a
   conforming web-templates skill carrying a conforming design-system.md (RT_AXIS_VALUE_MISSING is
   checked the same unconditional way, at TOP LEVEL, for the same reason), and CONTRIBUTING.md
   documenting every row type. Without this every fixture would carry FAILs that have nothing to
   do with what it actually tests.
   Each catalog skill's SKILL.md itself is required too, not just the file underneath it: writing
   a references/*.md alone creates a skills/<name>/ directory the skill-loop then walks and flags
   RT_NO_SKILL_MD for, since that loop is glob($root.'/skills/*') filtered by is_dir(), not by "has
   a SKILL.md" — any references/ or assets/ file dropped under a new skill name silently enrolls
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
	fx(
		$root,
		'skills/ux-design-system/SKILL.md',
		"---\n" .
		"name: ux-design-system\n" .
		"description: \"Trigger: fixture skill.\"\n" .
		"license: MIT\n" .
		"metadata:\n" .
		"  author: fixture\n" .
		"  version: \"1.0\"\n" .
		"---\n\n" .
		"## Execution Steps\n1. Resolves every perceptual axis via a dialogue step; see references/style-catalog/ for the style catalog.\n"
	);
	fx_sty_catalog( $root, fx_pers_catalog() );
	fx(
		$root,
		'skills/web-templates/SKILL.md',
		"---\n" .
		"name: web-templates\n" .
		"description: \"Trigger: fixture skill.\"\n" .
		"license: MIT\n" .
		"metadata:\n" .
		"  author: fixture\n" .
		"  version: \"1.0\"\n" .
		"---\n\n" .
		"Token names and values live in design-system.md.\n"
	);
	fx( $root, 'skills/web-templates/references/design-system.md', fx_ds_conforming() );
	/* RT_PROOF_NOT_DISTINCT is checked at TOP LEVEL against hardcoded paths, exactly like
	   RT_HOUSERULES_MISSING and RT_PERS_CATALOG_MISSING, so a fixture without these two files
	   would carry a FAIL that has nothing to do with what it tests. The SKILL.md is required for
	   the same reason the other catalog skills' are: writing an assets/ file alone enrols
	   html-mockup as a skill for the skill loop to flag RT_NO_SKILL_MD and RT_ORPHAN_FILE on.
	   These two differ on all five axes — the conforming case. */
	fx(
		$root,
		'skills/html-mockup/SKILL.md',
		"---\n" .
		"name: html-mockup\n" .
		"description: \"Trigger: fixture skill.\"\n" .
		"license: MIT\n" .
		"metadata:\n" .
		"  author: fixture\n" .
		"  version: \"1.0\"\n" .
		"---\n\n" .
		"The axis proof lives in `assets/proof-editorial-mockup.html` and `assets/proof-direct-mockup.html`.\n"
	);
	fx( $root, 'skills/html-mockup/assets/proof-editorial-mockup.html', fx_proof( array( '1.500', '#FFFFFF', '1.35', 'none', 'LP-ASYMMETRIC', 'none', 'rule-divided', 'rule' ) ) );
	fx( $root, 'skills/html-mockup/assets/proof-direct-mockup.html', fx_proof( array( '1.618', '#0E1113', '0.8', '0 0 0 1px rgba(255,106,26,.22)', 'LP-BROKEN-GRID', 'gradient', 'bare', 'none' ) ) );
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
   and could not have — the audit cannot read a live WordPress option (CONTRIBUTING.md:209) — so the
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

/* The contract has two ends and one list. RT_MOCKUP_NO_AXES asks the mockup to DECLARE each axis;
   this asks the gate that verifies the finished site to NAME each one. Dropping a single
   declaration is the mutation that matters: an assertion that only asked "does the row appear"
   would stay green with the list emptied down to one property, and the check would then be
   guarding a single axis while reading as if it guarded five. */
echo "--- house-rules.md missing ONE axis declaration is RT_QA_NO_AXIS_CHECK, naming exactly that one ---\n";
$r22b = fx_tmp_root();
fx_base( $r22b );
/* Everything the conforming helper writes EXCEPT --display-lh. Hand-built rather than a
   str_replace over fx_house_rules(), so this fixture cannot be quietly emptied by an edit there. */
fx(
	$r22b,
	'skills/qa-review/references/house-rules.md',
	"# House Rules\n\n"
	. '| 1 | Four fifths of the scale | `--type-ratio`, `--fs-h1-max`, `--sp-scale`, `--elev-rest`,'
	. " and the `LP-CENTERED` blueprint | **auto** |\n"
);
list( , $out22b ) = fx_run_ok( $audit, $r22b );
ok( 'FAIL' === fx_row_level( $out22b, array( 'RT_QA_NO_AXIS_CHECK' ) ), 'a house-rules.md missing one axis declaration is RT_QA_NO_AXIS_CHECK, at FAIL level', fx_row_level( $out22b, array( 'RT_QA_NO_AXIS_CHECK' ) ) );
ok( array() !== fx_lines_with( $out22b, array( 'RT_QA_NO_AXIS_CHECK', '--display-lh' ) ), 'the message names the declaration that is actually missing', $out22b );
/* The other half of "names exactly that one": a message listing every property regardless of what
   the file contains is a message that tells the reader nothing about where to look. */
foreach ( array( '--type-ratio', '--fs-h1-max', '--sp-scale', '--elev-rest', 'LP-' ) as $present ) {
	ok( array() === fx_lines_with( $out22b, array( 'RT_QA_NO_AXIS_CHECK', $present ) ), "and does not name $present, which the file does carry", $out22b );
}
fx_rrmdir( $r22b );

/* Composition is the arm with no custom property behind it — the axis whose position is a layout
   rule — so it is matched by its blueprint marker and needs its own fixture. Without this one the
   whole `LP-` clause could be deleted with the suite still green, which would silently drop the
   ONE axis qa-review cannot automate: exactly the axis most able to go missing unnoticed. */
echo "--- house-rules.md naming every property but no composition blueprint is still RT_QA_NO_AXIS_CHECK ---\n";
$r22c = fx_tmp_root();
fx_base( $r22c );
fx(
	$r22c,
	'skills/qa-review/references/house-rules.md',
	"# House Rules\n\n"
	. '| 1 | Every number, no blueprint | `--type-ratio`, `--display-lh`, `--fs-h1-max`, `--sp-scale`,'
	. " `--elev-rest`, and nothing about how the page is arranged | **auto** |\n"
);
list( , $out22c ) = fx_run_ok( $audit, $r22c );
ok( array() !== fx_lines_with( $out22c, array( 'RT_QA_NO_AXIS_CHECK', 'composition' ) ), 'a gate naming all five properties but no LP-* blueprint still fires, naming composition', $out22c );
/* And the row clears the moment the blueprint is named — a check that fires on every input is not
   a check. fx_base()'s own conforming house-rules is the positive control at r1; this is the
   narrower one, differing from the fixture above by a single blueprint id. */
fx(
	$r22c,
	'skills/qa-review/references/house-rules.md',
	"# House Rules\n\n"
	. '| 1 | Every number, and the blueprint | `--type-ratio`, `--display-lh`, `--fs-h1-max`, `--sp-scale`,'
	. " `--elev-rest`, and the `LP-BROKEN-GRID` the user confirms by eye | **auto** |\n"
);
list( , $out22d ) = fx_run_ok( $audit, $r22c );
ok( array() === fx_lines_with( $out22d, array( 'RT_QA_NO_AXIS_CHECK' ) ), 'naming the blueprint clears the row', $out22d );
fx_rrmdir( $r22c );

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

/* --------------------------------------------- ux-design-system style catalog fixtures
 *
 * Style-catalog PR 4c retired `$PERS_IDS`/`RT_PERS_ID_MISSING` along with design-personalities.md
 * itself: the style-catalog spec ("Exactly 8 `STY-*` Entries Ship In V1", scenario "No automated
 * size floor/ceiling") states there is deliberately no required-membership-list verifier any more
 * — count is trivially satisfied without being true, `RT_STYLE_TOO_SIMILAR` is the real floor. The
 * two fixtures that used to prove RT_PERS_ID_MISSING (r28, missing PERS-DIRECT; r28b, missing the
 * fifth anchor PERS-VITRINE — the exact anchor whose real omission from `$PERS_IDS` once returned
 * the audit to 0 FAIL) are removed rather than left asserting a row that no longer exists.
 *
 * The remaining checks below all follow the row-type-registry shape above; each gets the smallest
 * tree that makes ONE check fire, matching the retrofit fixtures. catalog-missing and
 * hardcoded-font also prove the conforming shape produces NO row, because both call sites sit next
 * to a sibling check (RT_PERS_MISSING_FIELD; RT_TOKENS_HARDCODED_FONT loops two literals) where an
 * assertion that only ever fires can never prove the check discriminates rather than always
 * firing. */

echo "--- ux-design-system: an empty style catalog is RT_PERS_CATALOG_MISSING, and a conforming catalog clears it ---\n";
$r26 = fx_tmp_root();
fx_base( $r26 );
fx_sty_catalog_clear( $r26 );
list( , $out26a ) = fx_run_ok( $audit, $r26 );
ok( has( $out26a, 'RT_PERS_CATALOG_MISSING' ), 'a style catalog with no STY-*.md files is RT_PERS_CATALOG_MISSING', $out26a );

fx_sty_catalog( $r26, fx_pers_catalog() );
list( , $out26b ) = fx_run_ok( $audit, $r26 );
ok( ! has( $out26b, 'RT_PERS_CATALOG_MISSING' ), 'restoring a conforming catalog clears the row', $out26b );
fx_rrmdir( $r26 );

echo "--- ux-design-system: a personality block missing a required field is RT_PERS_MISSING_FIELD ---\n";
$r27 = fx_tmp_root();
fx_base( $r27 );
fx_sty_catalog( $r27, fx_pers_catalog( null, 'PERS-EDITORIAL', 'Motion intensity' ) );
list( , $out27 ) = fx_run_ok( $audit, $r27 );
ok(
	has( $out27, 'RT_PERS_MISSING_FIELD' ) && has( $out27, 'PERS-EDITORIAL' ) && has( $out27, 'Motion intensity' ),
	'PERS-EDITORIAL missing "Motion intensity" is RT_PERS_MISSING_FIELD naming both the personality and the field',
	$out27
);
fx_rrmdir( $r27 );

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

echo "--- ux-design-system: SKILL.md never mentioning style-catalog/ is RT_CATALOG_UNMENTIONED ---\n";
$r30 = fx_tmp_root();
fx_base( $r30 );
fx(
	$r30,
	'skills/ux-design-system/SKILL.md',
	"---\nname: ux-design-system\ndescription: \"Trigger: fixture skill.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\n"
	. "## Execution Steps\n1. Resolve every axis with the client before anything else.\n\nFixture skill body. Its Execution Steps DO resolve the axes, so this scenario isolates the catalog-pointer check alone; it simply never points at the catalog itself.\n"
);
list( , $out30 ) = fx_run_ok( $audit, $r30 );
ok( has( $out30, 'RT_CATALOG_UNMENTIONED' ), 'a SKILL.md never mentioning style-catalog/ is RT_CATALOG_UNMENTIONED', $out30 );
fx_rrmdir( $r30 );

echo "--- ux-design-system: SKILL.md with no axis-resolving step at all is RT_UXDS_NO_AXIS_STEP ---\n";
$r31 = fx_tmp_root();
fx_base( $r31 );
fx(
	$r31,
	'skills/ux-design-system/SKILL.md',
	"---\nname: ux-design-system\ndescription: \"Trigger: fixture skill.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\n"
	. "Fixture skill body. Points at references/style-catalog/, but carries no \"## Execution Steps\" section at all.\n"
);
list( , $out31 ) = fx_run_ok( $audit, $r31 );
ok( has( $out31, 'RT_UXDS_NO_AXIS_STEP' ), 'a SKILL.md with no Execution Steps section is RT_UXDS_NO_AXIS_STEP', $out31 );
fx_rrmdir( $r31 );

echo "--- una skill de diseno que no nombra los ejes no puede resolverlos ---\n";
$r86 = fx_tmp_root();
fx_base( $r86 );
fx( $r86, 'skills/ux-design-system/SKILL.md',
	"---\nname: ux-design-system\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\n"
	. "## Execution Steps\n1. Pick something.\n\n## References\n- `references/style-catalog/`\n" );
list( , $out86 ) = fx_run_ok( $audit, $r86 );
ok( 'FAIL' === fx_row_level( $out86, array( 'RT_UXDS_NO_AXIS_STEP' ) ), 'una SKILL.md sin los ejes FALLA', fx_row_level( $out86, array( 'RT_UXDS_NO_AXIS_STEP' ) ) );
fx_rrmdir( $r86 );

/* Mutant (a): "axis" appears ONLY outside "## Execution Steps" -- in the frontmatter description
   (CSS's own "main axis" / "cross axis" / "x-axis" vocabulary) and in "## Hard Rules" (a hit
   inside the unrelated word "praxis"). The check is scoped to the Execution Steps section alone,
   so none of these accidental hits may satisfy it -- the row must still FAIL. */
echo "--- 'axis' fuera de Execution Steps (main/cross/x-axis, praxis) NO salva el check ---\n";
$r87 = fx_tmp_root();
fx_base( $r87 );
fx( $r87, 'skills/ux-design-system/SKILL.md',
	"---\nname: ux-design-system\ndescription: \"Trigger: fixture. Flexbox has a main axis and a cross axis; CSS also calls it the x-axis.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\n"
	. "## Hard Rules\n- Praxis over theory: ship something real. See `references/style-catalog/`.\n\n"
	. "## Execution Steps\n1. Pick something and hand it off.\n" );
list( , $out87 ) = fx_run_ok( $audit, $r87 );
ok( 'FAIL' === fx_row_level( $out87, array( 'RT_UXDS_NO_AXIS_STEP' ) ), '"axis" solo fuera de Execution Steps sigue FALLANDO', fx_row_level( $out87, array( 'RT_UXDS_NO_AXIS_STEP' ) ) );
fx_rrmdir( $r87 );

/* Mutant (b): "## Execution Steps" itself uses only the natural plural "axes" (never the
   singular "axis"). The check must match case-insensitively for BOTH forms, so this is a
   conforming skill and the row must NOT fire. */
echo "--- Execution Steps con solo el plural 'axes' SI resuelve el check ---\n";
$r88 = fx_tmp_root();
fx_base( $r88 );
fx( $r88, 'skills/ux-design-system/SKILL.md',
	"---\nname: ux-design-system\ndescription: \"Trigger: fixture.\"\nlicense: MIT\nmetadata:\n  author: fixture\n  version: \"1.0\"\n---\n\n"
	. "## Execution Steps\n1. Resolve the eight axes with the client, then land on an anchor from `references/style-catalog/`.\n" );
list( , $out88 ) = fx_run_ok( $audit, $r88 );
ok( ! has( $out88, 'RT_UXDS_NO_AXIS_STEP' ), 'Execution Steps con solo "axes" (plural) limpia el check', $out88 );
fx_rrmdir( $r88 );

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

echo "--- las anclas de personalidad no pueden parecerse entre si ---\n";
$r80 = fx_tmp_root();
fx_base( $r80 );
/* Un ancla que comparte CUATRO ejes con otra: comparten 4 > 1, tiene que FALLAR. */
fx_sty_catalog(
	$r80,
	"# Personalities\n\n"
	. fx_pers( 'PERS-EDITORIAL', 'editorial', 'paper', 'generous', 'asymmetric', 'none', 'none', 'rule-divided', 'rule' )
	. fx_pers( 'PERS-MATTER', 'classic', 'paper', 'generous', 'asymmetric', 'none', 'tinted-field', 'bordered', 'texture' )
	. fx_pers( 'PERS-DIRECT', 'monumental', 'ink', 'compact', 'broken-grid', 'accent-glow', 'gradient', 'bare', 'none' )
	. fx_pers( 'PERS-INSTITUTIONAL', 'contained', 'cool', 'standard', 'centered', 'soft-shadow', 'reserved', 'carded', 'illustration' )
);
list( , $out80 ) = fx_run_ok( $audit, $r80 );
ok( 'FAIL' === fx_row_level( $out80, array( 'RT_STYLE_TOO_SIMILAR', 'PERS-MATTER' ) ), 'dos anclas que comparten mas de un eje FALLAN', fx_row_level( $out80, array( 'RT_STYLE_TOO_SIMILAR', 'PERS-MATTER' ) ) );
ok( array() !== fx_lines_with( $out80, array( 'RT_STYLE_TOO_SIMILAR', '4' ) ), 'y la fila dice CUANTOS ejes comparten, que es lo accionable', $out80 );
fx_rrmdir( $r80 );

echo "--- una posicion de eje inventada no pasa en silencio ---\n";
$r81 = fx_tmp_root();
fx_base( $r81 );
fx_sty_catalog(
	$r81,
	"# Personalities\n\n"
	. fx_pers( 'PERS-EDITORIAL', 'editorial', 'paper', 'generous', 'asymmetric', 'none', 'none', 'rule-divided', 'rule' )
	. fx_pers( 'PERS-MATTER', 'classic', 'warm', 'standard', 'strict-grid', 'hairline', 'tinted-field', 'bordered', 'texture' )
	. fx_pers( 'PERS-DIRECT', 'monumental', 'ink', 'compact', 'broken-grid', 'accent-glow', 'gradient', 'bare', 'none' )
	/* "medium" no existe en el eje de densidad: un typo crea una quinta posicion en silencio.
	   ground y composition copian a PERS-MATTER a proposito -- si el guard `if ( $ok )` que
	   excluye anclas invalidas de la comparacion se cayera, esta ancla SI compartiria mas de un
	   eje con PERS-MATTER y RT_STYLE_TOO_SIMILAR dispararia donde no debe. Sin esas dos posiciones
	   repetidas, un typo en un solo eje nunca reproduce una colision real y el guard queda sin
	   cobertura de mutacion. */
	. fx_pers( 'PERS-INSTITUTIONAL', 'contained', 'warm', 'medium', 'strict-grid', 'soft-shadow', 'reserved', 'carded', 'illustration' )
);
list( , $out81 ) = fx_run_ok( $audit, $r81 );
ok( 'FAIL' === fx_row_level( $out81, array( 'RT_PERS_BAD_AXIS', 'medium' ) ), 'una posicion que ningun eje define FALLA, nombrandola', fx_row_level( $out81, array( 'RT_PERS_BAD_AXIS', 'medium' ) ) );
ok( array() === fx_lines_with( $out81, array( 'RT_STYLE_TOO_SIMILAR' ) ), 'y no se cuenta como parecido: una posicion invalida no es una coincidencia', $out81 );
fx_rrmdir( $r81 );

echo "--- cuatro anclas bien separadas no levantan nada ---\n";
$r82 = fx_tmp_root();
fx_base( $r82 );
fx_sty_catalog(
	$r82,
	"# Personalities\n\n"
	. fx_pers( 'PERS-EDITORIAL', 'editorial', 'paper', 'generous', 'asymmetric', 'none', 'none', 'rule-divided', 'rule' )
	. fx_pers( 'PERS-MATTER', 'classic', 'warm', 'standard', 'strict-grid', 'hairline', 'tinted-field', 'bordered', 'texture' )
	. fx_pers( 'PERS-DIRECT', 'monumental', 'ink', 'compact', 'broken-grid', 'accent-glow', 'gradient', 'bare', 'none' )
	. fx_pers( 'PERS-INSTITUTIONAL', 'contained', 'cool', 'standard', 'centered', 'soft-shadow', 'reserved', 'carded', 'illustration' )
);
list( , $out82 ) = fx_run_ok( $audit, $r82 );
ok( array() === fx_lines_with( $out82, array( 'RT_STYLE_TOO_SIMILAR' ) ), 'compartir UN eje (densidad) es legal: el limite es mas de uno', $out82 );
ok( array() === fx_lines_with( $out82, array( 'RT_PERS_BAD_AXIS' ) ), 'y todas las posiciones son validas', $out82 );
fx_rrmdir( $r82 );

echo "--- una posicion de eje sin valor de token no sirve para nada ---\n";
$r83 = fx_tmp_root();
fx_base( $r83 );
fx_sty_catalog( $r83,
	"# P\n\n"
	. fx_pers( 'PERS-EDITORIAL', 'editorial', 'paper', 'generous', 'asymmetric', 'none', 'none', 'rule-divided', 'rule' )
	. fx_pers( 'PERS-MATTER', 'classic', 'warm', 'standard', 'strict-grid', 'hairline', 'tinted-field', 'bordered', 'texture' )
	. fx_pers( 'PERS-DIRECT', 'monumental', 'ink', 'compact', 'broken-grid', 'accent-glow', 'gradient', 'bare', 'none' )
	. fx_pers( 'PERS-INSTITUTIONAL', 'contained', 'cool', 'standard', 'centered', 'soft-shadow', 'reserved', 'carded', 'illustration' ) );
/* NEGATIVE CONTROL. design-system.md aqui define TODAS las posiciones -- las 41 unicas (style-
   catalog PR 2b widened this from 19 to 36; style-catalog PR 4c's nm_axes() ground widening from
   4 to 9 pushes it from 36 to 41), contando que `monumental` cubre a la vez el eje de escala y
   el de densidad, `none` cubre elevation/accent/ornament y `strict-grid` cubre composition/chassis,
   por eso cada una aparece una sola vez -- y cada una con un valor token-shaped en su propia fila.
   Nada tiene que sonar: esta es la mitad silenciosa del par, y prueba que el check no dispara sobre
   un archivo correcto. La fila que SI dispara es $r84, diez lineas mas abajo. */
fx( $r83, 'skills/web-templates/references/design-system.md',
	"# Tokens\n\n| position | value |\n|---|---|\n"
	. "| `contained` | 1 |\n| `classic` | 1 |\n| `editorial` | 1 |\n| `monumental` | 1 |\n"
	. "| `paper` | 1 |\n| `warm` | 1 |\n| `cool` | 1 |\n| `cream` | 1 |\n| `earth` | 1 |\n"
	. "| `saturated` | 1 |\n| `ink` | 1 |\n| `ink-warm` | 1 |\n| `ink-cool` | 1 |\n"
	. "| `compact` | 1 |\n| `standard` | 1 |\n| `generous` | 1 |\n"
	. "| `centered` | 1 |\n| `asymmetric` | 1 |\n| `strict-grid` | 1 |\n| `broken-grid` | 1 |\n"
	. "| `none` | 1 |\n| `hairline` | 1 |\n| `soft-shadow` | 1 |\n| `accent-glow` | 1 |\n"
	. "| `reserved` | 1 |\n| `tinted-field` | 1 |\n| `duotone` | 1 |\n| `gradient` | 1 |\n| `metallic` | 1 |\n| `polychrome` | 1 |\n"
	. "| `bare` | 1 |\n| `carded` | 1 |\n| `soft-carded` | 1 |\n| `bordered` | 1 |\n| `rule-divided` | 1 |\n| `hard-shadow` | 1 |\n| `layered` | 1 |\n"
	. "| `rule` | 1 |\n| `texture` | 1 |\n| `pattern` | 1 |\n| `illustration` | 1 |\n" );
list( , $out83 ) = fx_run_ok( $audit, $r83 );
ok( array() === fx_lines_with( $out83, array( 'RT_AXIS_VALUE_MISSING' ) ), 'una posicion con valor en su propia fila no levanta nada', $out83 );
fx_rrmdir( $r83 );

/* La mitad ruidosa del par: design-system.md solo define `contained`, asi que las otras 18
   posiciones se quedan sin fila y RT_AXIS_VALUE_MISSING tiene que nombrarlas. */
$r84 = fx_tmp_root();
fx_base( $r84 );
fx_sty_catalog( $r84,
	"# P\n\n"
	. fx_pers( 'PERS-EDITORIAL', 'editorial', 'paper', 'generous', 'asymmetric', 'none', 'none', 'rule-divided', 'rule' )
	. fx_pers( 'PERS-MATTER', 'classic', 'warm', 'standard', 'strict-grid', 'hairline', 'tinted-field', 'bordered', 'texture' )
	. fx_pers( 'PERS-DIRECT', 'monumental', 'ink', 'compact', 'broken-grid', 'accent-glow', 'gradient', 'bare', 'none' )
	. fx_pers( 'PERS-INSTITUTIONAL', 'contained', 'cool', 'standard', 'centered', 'soft-shadow', 'reserved', 'carded', 'illustration' ) );
fx( $r84, 'skills/web-templates/references/design-system.md', "# Tokens\n\n| `contained` | 1.200 |\n" );
list( , $out84 ) = fx_run_ok( $audit, $r84 );
ok( 'FAIL' === fx_row_level( $out84, array( 'RT_AXIS_VALUE_MISSING', 'broken-grid' ) ), 'una posicion sin valor en design-system.md FALLA, nombrandola', fx_row_level( $out84, array( 'RT_AXIS_VALUE_MISSING', 'broken-grid' ) ) );
fx_rrmdir( $r84 );

/* A prose MENTION is not a row. `broken-grid` is named in a sentence -- once bare, once even
   backticked -- but owns no table row of its own, so it still has no value. The backtick-anchored
   substring scan this replaced was satisfied by the backticked mention alone; a row-anchored check
   is not, and that difference is what this fixture pins.
   `broken-grid` (not `monumental`) on purpose: it belongs to exactly one axis (composition), so
   exactly one RT_AXIS_VALUE_MISSING row can name it -- fx_row_level() requires exactly one match,
   and `monumental` names TWO axes (scale and density), which would make the assertion fail even
   against a correct implementation. */
$r85 = fx_tmp_root();
fx_base( $r85 );
fx_sty_catalog( $r85,
	"# P\n\n"
	. fx_pers( 'PERS-EDITORIAL', 'editorial', 'paper', 'generous', 'asymmetric', 'none', 'none', 'rule-divided', 'rule' )
	. fx_pers( 'PERS-MATTER', 'classic', 'warm', 'standard', 'strict-grid', 'hairline', 'tinted-field', 'bordered', 'texture' )
	. fx_pers( 'PERS-DIRECT', 'monumental', 'ink', 'compact', 'broken-grid', 'accent-glow', 'gradient', 'bare', 'none' )
	. fx_pers( 'PERS-INSTITUTIONAL', 'contained', 'cool', 'standard', 'centered', 'soft-shadow', 'reserved', 'carded', 'illustration' ) );
fx( $r85, 'skills/web-templates/references/design-system.md',
	"# Tokens\n\nThe broken-grid composition scatters elements across the grid on purpose; we write it `broken-grid` in prose here, and give it no row of its own anywhere.\n\n"
	. "| position | value |\n|---|---|\n"
	. "| `contained` | 1 |\n| `classic` | 1 |\n| `editorial` | 1 |\n| `monumental` | 1 |\n"
	. "| `paper` | 1 |\n| `warm` | 1 |\n| `cool` | 1 |\n| `ink` | 1 |\n"
	. "| `compact` | 1 |\n| `standard` | 1 |\n| `generous` | 1 |\n"
	. "| `centered` | 1 |\n| `asymmetric` | 1 |\n| `strict-grid` | 1 |\n"
	. "| `none` | 1 |\n| `hairline` | 1 |\n| `soft-shadow` | 1 |\n| `accent-glow` | 1 |\n" );
list( , $out85 ) = fx_run_ok( $audit, $r85 );
ok( 'FAIL' === fx_row_level( $out85, array( 'RT_AXIS_VALUE_MISSING', 'broken-grid' ) ), 'una posicion nombrada en prosa (incluso entre backticks) pero sin fila propia sigue SIN valor', fx_row_level( $out85, array( 'RT_AXIS_VALUE_MISSING', 'broken-grid' ) ) );
fx_rrmdir( $r85 );

/* ---- C1: la fila de la posicion tiene que llevar un VALOR, no repetir el nombre ----
 *
 * El incidente, reproducido en el checkout real antes de arreglarlo: RT_AXIS_VALUE_MISSING solo
 * preguntaba si el NOMBRE de la posicion aparecia entre backticks en algun sitio de
 * design-system.md. Sustituir toda la seccion de valores por una tabla pelada de nombres con las
 * celdas de valor VACIAS dejaba la puerta en `0 FAIL`, y con ese check ground `cool`, ground `ink`
 * y las cuatro posiciones de composicion llegaron a produccion sin ningun valor. */
echo "--- una tabla de NOMBRES con la celda de valor vacia no es un valor ---\n";
$r89 = fx_tmp_root();
fx_base( $r89 );
fx( $r89, 'skills/web-templates/references/design-system.md',
	"# Tokens\n\n| Position | Value |\n|---|---|\n"
	. "| `contained` |  |\n| `classic` |  |\n| `editorial` |  |\n| `monumental` |  |\n"
	. "| `paper` |  |\n| `warm` |  |\n| `cool` |  |\n| `ink` |  |\n"
	. "| `compact` |  |\n| `standard` |  |\n| `generous` |  |\n"
	. "| `centered` |  |\n| `asymmetric` |  |\n| `strict-grid` |  |\n| `broken-grid` |  |\n"
	. "| `none` |  |\n| `hairline` |  |\n| `soft-shadow` |  |\n| `accent-glow` |  |\n" );
list( , $out89 ) = fx_run_ok( $audit, $r89 );
ok( 'FAIL' === fx_row_level( $out89, array( 'RT_AXIS_VALUE_MISSING', 'broken-grid' ) ), 'la celda de valor vacia FALLA: el nombre entre backticks no es el valor', fx_row_level( $out89, array( 'RT_AXIS_VALUE_MISSING', 'broken-grid' ) ) );
/* 45 = 8 ejes x (4,9,4,4,4,7,8,5) posiciones sumadas (style-catalog PR 2b widened this from 20 to
   40; style-catalog PR 4c's nm_axes() ground widening from 4 to 9 pushes it from 40 to 45).
   `monumental` aparece dos veces a proposito: es posicion del eje de escala Y del de densidad, y
   cada eje reclama su valor por separado -- accent/chassis/ornament's 3 new positions (and ground's
   5 new positions) this table never mentions FAIL the SAME way, as "no table row of its own
   anywhere", so the count is unaffected by this fixture not listing them explicitly. */
ok( 45 === count( fx_lines_with( $out89, array( 'RT_AXIS_VALUE_MISSING' ) ) ), 'y FALLA para las 45 parejas eje/posicion, no solo para una', count( fx_lines_with( $out89, array( 'RT_AXIS_VALUE_MISSING' ) ) ) );
fx_rrmdir( $r89 );

/* Una frase en la celda tampoco es un valor. Es EXACTAMENTE lo que shippeaba la tabla de
   composicion ("at least one element per section crossing the grid...") y lo que ground `cool`
   shippeaba como "very light blue-grey": prosa donde tiene que ir algo que un builder pueda
   aplicar. La celda se juzga por su PRIMER token, asi que un hex citado a mitad de frase no la
   salva -- es un ejemplo, no el valor de la celda. */
echo "--- una frase en la celda de valor sigue sin ser un valor ---\n";
$r90 = fx_tmp_root();
fx_base( $r90 );
fx( $r90, 'skills/web-templates/references/design-system.md',
	"# Tokens\n\n| Position | Value |\n|---|---|\n"
	. "| `contained` | 1.200 |\n| `classic` | 1.333 |\n| `editorial` | 1.500 |\n| `monumental` | 1.618 |\n"
	. "| `paper` | `#FFFFFF` |\n| `warm` | cream/ivory, e.g. `#FFF3E3` |\n| `cool` | very light blue-grey |\n| `ink` | `#0E1113` |\n"
	. "| `compact` | 0.8 |\n| `standard` | 1.0 |\n| `generous` | 1.35 |\n"
	. "| `centered` | 1 |\n| `asymmetric` | 1 |\n| `strict-grid` | 1 |\n"
	. "| `broken-grid` | at least one element per section crossing the grid |\n"
	. "| `none` | none |\n| `hairline` | `0 0 0 1px var(--c-border)` |\n| `soft-shadow` | `0 1px 2px rgba(0,0,0,.04)` |\n| `accent-glow` | `color-mix(in srgb,var(--c-accent) 22%,transparent)` |\n" );
list( , $out90 ) = fx_run_ok( $audit, $r90 );
ok( 'FAIL' === fx_row_level( $out90, array( 'RT_AXIS_VALUE_MISSING', 'broken-grid' ) ), 'una frase en la celda de valor FALLA', fx_row_level( $out90, array( 'RT_AXIS_VALUE_MISSING', 'broken-grid' ) ) );
ok( 'FAIL' === fx_row_level( $out90, array( 'RT_AXIS_VALUE_MISSING', '"cool"' ) ), 'y un adjetivo ("very light blue-grey") tambien', fx_row_level( $out90, array( 'RT_AXIS_VALUE_MISSING', '"cool"' ) ) );
ok( 'FAIL' === fx_row_level( $out90, array( 'RT_AXIS_VALUE_MISSING', '"warm"' ) ), 'y una celda que ABRE en prosa no se salva por citar un hex a mitad de frase', fx_row_level( $out90, array( 'RT_AXIS_VALUE_MISSING', '"warm"' ) ) );
/* El control positivo del mismo fixture: hex, numero, `none`, una funcion CSS entre backticks y
   una lista de longitudes son TODOS valores, y ninguno levanta fila. Sin esto, un check que
   rechazara todo pasaria las tres aserciones de arriba. */
ok( array() === fx_lines_with( $out90, array( 'RT_AXIS_VALUE_MISSING', '"paper"' ) ), 'un hex SI es valor', $out90 );
ok( array() === fx_lines_with( $out90, array( 'RT_AXIS_VALUE_MISSING', '"hairline"' ) ), 'una lista de longitudes con var() SI es valor', $out90 );
ok( array() === fx_lines_with( $out90, array( 'RT_AXIS_VALUE_MISSING', '"accent-glow"' ) ), 'una funcion CSS (color-mix) SI es valor', $out90 );
ok( array() === fx_lines_with( $out90, array( 'RT_AXIS_VALUE_MISSING', '"none"' ) ), 'la palabra none SI es valor: "sin sombra" es una decision, no un hueco', $out90 );
fx_rrmdir( $r90 );

/* La composicion es el unico eje cuyo valor es una regla de layout y no un numero, asi que su
   celda nombra un blueprint. Un blueprint que nadie escribio es un adjetivo con numero de serie:
   el nombre tiene que resolver a un heading real en layout-patterns.md. */
echo "--- un blueprint que layout-patterns.md no define no vale como valor ---\n";
$r91 = fx_tmp_root();
fx_base( $r91 );
$fx_ds_bp = function ( $bp ) {
	return "# Tokens\n\n| Position | Value |\n|---|---|\n"
		. "| `contained` | 1 |\n| `classic` | 1 |\n| `editorial` | 1 |\n| `monumental` | 1 |\n"
		. "| `paper` | 1 |\n| `warm` | 1 |\n| `cool` | 1 |\n| `ink` | 1 |\n"
		. "| `compact` | 1 |\n| `standard` | 1 |\n| `generous` | 1 |\n"
		. "| `centered` | 1 |\n| `asymmetric` | 1 |\n| `strict-grid` | 1 |\n"
		. '| `broken-grid` | `' . $bp . "` |\n"
		. "| `none` | 1 |\n| `hairline` | 1 |\n| `soft-shadow` | 1 |\n| `accent-glow` | 1 |\n";
};
fx( $r91, 'skills/web-templates/references/design-system.md', $fx_ds_bp( 'LP-BROKEN-GRID' ) );
fx( $r91, 'skills/ux-design-system/references/layout-patterns.md', "# Layout patterns fixture\n\n### `LP-CENTERED`\n- Nothing bleeds.\n" );
list( , $out91a ) = fx_run_ok( $audit, $r91 );
ok( 'FAIL' === fx_row_level( $out91a, array( 'RT_AXIS_BLUEPRINT_MISSING', 'LP-BROKEN-GRID' ) ), 'un blueprint sin heading en layout-patterns.md FALLA, nombrandolo', fx_row_level( $out91a, array( 'RT_AXIS_BLUEPRINT_MISSING', 'LP-BROKEN-GRID' ) ) );
ok( array() === fx_lines_with( $out91a, array( 'RT_AXIS_VALUE_MISSING', 'broken-grid' ) ), 'y NO se reporta como "sin valor": el problema es a donde apunta, no que apunte', $out91a );
/* Escrito el blueprint, la fila se calla. Sin esta mitad, un check que fallara siempre pasaria. */
fx( $r91, 'skills/ux-design-system/references/layout-patterns.md', "# Layout patterns fixture\n\n### `LP-CENTERED`\n- Nothing bleeds.\n\n### `LP-BROKEN-GRID`\n- One element per section crosses a column line.\n" );
list( , $out91b ) = fx_run_ok( $audit, $r91 );
ok( array() === fx_lines_with( $out91b, array( 'RT_AXIS_BLUEPRINT_MISSING' ) ), 'escrito el blueprint, la fila se calla', $out91b );
/* Las backticks son lo unico que distingue un identificador de blueprint de una palabra suelta en
   mayusculas. Sin ellas la celda es prosa, y prosa no es valor -- si no, "TODO" o "TBD" pasarian
   como valor de un eje. */
fx( $r91, 'skills/web-templates/references/design-system.md', str_replace( '`LP-BROKEN-GRID`', 'LP-BROKEN-GRID', $fx_ds_bp( 'LP-BROKEN-GRID' ) ) );
list( , $out91c ) = fx_run_ok( $audit, $r91 );
ok( 'FAIL' === fx_row_level( $out91c, array( 'RT_AXIS_VALUE_MISSING', 'broken-grid' ) ), 'un id de blueprint SIN backticks no es valor: es una palabra en mayusculas', fx_row_level( $out91c, array( 'RT_AXIS_VALUE_MISSING', 'broken-grid' ) ) );
fx_rrmdir( $r91 );

/* `monumental` es posicion del eje de escala Y del de densidad, asi que le tocan DOS filas, una
   por tabla. Cada una tiene que llevar su propio valor: si bastara con que UNA cualquiera lo
   llevara, vaciar la tabla de densidad quedaria tapado por la fila de la tabla de escala -- el
   mismo agujero de "existe algo con pinta de valor en otro sitio del archivo" un nivel mas abajo,
   y justo sobre la posicion de densidad que esta rama existe para poder expresar. */
echo "--- una posicion con dos filas necesita valor en LAS DOS ---\n";
$r93 = fx_tmp_root();
fx_base( $r93 );
fx( $r93, 'skills/web-templates/references/design-system.md',
	"# Tokens\n\n### Scale\n| Position | `--type-ratio` |\n|---|---|\n"
	. "| `contained` | 1.200 |\n| `classic` | 1.333 |\n| `editorial` | 1.500 |\n| `monumental` | 1.618 |\n\n"
	. "### Density\n| Position | `--sp-scale` |\n|---|---|\n"
	. "| `compact` | 0.8 |\n| `standard` | 1.0 |\n| `generous` | 1.35 |\n| `monumental` |  |\n\n"
	. "### Rest\n| Position | Value |\n|---|---|\n"
	. "| `paper` | 1 |\n| `warm` | 1 |\n| `cool` | 1 |\n| `cream` | 1 |\n| `earth` | 1 |\n"
	. "| `saturated` | 1 |\n| `ink` | 1 |\n| `ink-warm` | 1 |\n| `ink-cool` | 1 |\n"
	. "| `centered` | 1 |\n| `asymmetric` | 1 |\n| `strict-grid` | 1 |\n| `broken-grid` | 1 |\n"
	. "| `none` | 1 |\n| `hairline` | 1 |\n| `soft-shadow` | 1 |\n| `accent-glow` | 1 |\n"
	. "| `reserved` | 1 |\n| `tinted-field` | 1 |\n| `duotone` | 1 |\n| `gradient` | 1 |\n| `metallic` | 1 |\n| `polychrome` | 1 |\n"
	. "| `bare` | 1 |\n| `carded` | 1 |\n| `soft-carded` | 1 |\n| `bordered` | 1 |\n| `rule-divided` | 1 |\n| `hard-shadow` | 1 |\n| `layered` | 1 |\n"
	. "| `rule` | 1 |\n| `texture` | 1 |\n| `pattern` | 1 |\n| `illustration` | 1 |\n" );
list( , $out93 ) = fx_run_ok( $audit, $r93 );
ok( 'FAIL' === fx_row_level( $out93, array( 'RT_AXIS_VALUE_MISSING', '"density"' ) ), 'la fila vacia de la tabla de densidad FALLA aunque la de escala tenga valor', fx_row_level( $out93, array( 'RT_AXIS_VALUE_MISSING', '"density"' ) ) );
/* El check indexa por POSICION, no por tabla: no sabe bajo que heading cae cada fila, asi que
   atribuye la fila vacia a los DOS ejes que reclaman `monumental`. Ruidoso a proposito -- elegir
   uno en silencio seria adivinar. Lo que importa es que la fila con valor NO tapa a la vacia. */
ok( 2 === count( fx_lines_with( $out93, array( 'RT_AXIS_VALUE_MISSING', 'monumental' ) ) ), 'y se reporta bajo los dos ejes que reclaman esa posicion, sin elegir uno a dedo', count( fx_lines_with( $out93, array( 'RT_AXIS_VALUE_MISSING', 'monumental' ) ) ) );
ok( 2 === count( fx_lines_with( $out93, array( 'RT_AXIS_VALUE_MISSING' ) ) ), 'y ninguna otra posicion se contagia: el resto de la tabla esta bien', $out93 );
fx_rrmdir( $r93 );

/* ---- I1: un heading de ancla duplicado apagaba el check estrella ----
 *
 * Reproducido en el checkout real: $found y $axes_of van indexados por ID y la ultima escritura
 * ganaba, asi que un SEGUNDO bloque `### `PERS-EDITORIAL`` con un juego de ejes lejano borraba el
 * ancla de verdad antes de comparar, y RT_STYLE_TOO_SIMILAR dejaba de disparar (1 FAIL -> 0 FAIL).
 * Aqui PERS-EDITORIAL comparte CUATRO ejes con PERS-MATTER, y el duplicado es deliberadamente
 * lejano: si el ancla que entra a la comparacion fuera la copia, la segunda asercion se cae. */
echo "--- un ID de personalidad declarado dos veces no puede tapar al primero ---\n";
$r92 = fx_tmp_root();
fx_base( $r92 );
fx_sty_catalog( $r92,
	"# P\n\n"
	. fx_pers( 'PERS-EDITORIAL', 'editorial', 'paper', 'generous', 'asymmetric', 'none', 'none', 'rule-divided', 'rule' )
	. fx_pers( 'PERS-MATTER', 'classic', 'paper', 'generous', 'asymmetric', 'none', 'tinted-field', 'bordered', 'texture' )
	. fx_pers( 'PERS-DIRECT', 'monumental', 'ink', 'compact', 'broken-grid', 'accent-glow', 'gradient', 'bare', 'none' )
	. fx_pers( 'PERS-INSTITUTIONAL', 'contained', 'cool', 'standard', 'centered', 'soft-shadow', 'reserved', 'carded', 'illustration' )
	/* Presente porque un ancla construida sigue siendo obligatoria, y con sus posiciones reales: un
	   eje con EDITORIAL (scale), uno con DIRECT (ground), uno con INSTITUTIONAL (elevation), cero
	   con MATTER. Cero ruido sobre el par EDITORIAL/MATTER que este escenario existe para provocar. */
	. fx_pers( 'PERS-VITRINE', 'editorial', 'ink', 'monumental', 'strict-grid', 'soft-shadow', 'metallic', 'soft-carded', 'none' )
);
/* style-catalog PR 4c: "duplicate ID" now means two DIFFERENT FILES whose headings both claim the
   same id, not a second `### `PERS-X`` block inside one file (design-personalities.md is gone).
   Written directly, not through fx_sty_catalog(): that helper derives its filename FROM the id, so
   two calls with the same id would collide at the FILESYSTEM level before the audit ever ran,
   proving nothing. `STY-ZZ-DUPLICATE-EDITORIAL.md` is named to sort AFTER `STY-PERS-EDITORIAL.md`
   (fx_sty_catalog()'s own naming, `STY-` + id) alphabetically, so the glob's sort keeps the REAL
   block first and this copy second -- the exact ordering the second assertion below depends on. */
fx( $r92, 'skills/ux-design-system/references/style-catalog/STY-ZZ-DUPLICATE-EDITORIAL.md', fx_pers( 'PERS-EDITORIAL', 'editorial', 'warm', 'standard', 'strict-grid', 'hairline', 'none', 'rule-divided', 'rule' ) );
list( , $out92 ) = fx_run_ok( $audit, $r92 );
ok( 'FAIL' === fx_row_level( $out92, array( 'RT_PERS_DUPLICATE_ID', 'PERS-EDITORIAL' ) ), 'un ID declarado dos veces FALLA, nombrandolo', fx_row_level( $out92, array( 'RT_PERS_DUPLICATE_ID', 'PERS-EDITORIAL' ) ) );
ok( 'FAIL' === fx_row_level( $out92, array( 'RT_STYLE_TOO_SIMILAR', 'PERS-MATTER' ) ), 'y el duplicado no apaga RT_STYLE_TOO_SIMILAR: manda el PRIMER bloque (el fichero cuyo nombre ordena primero)', fx_row_level( $out92, array( 'RT_STYLE_TOO_SIMILAR', 'PERS-MATTER' ) ) );
fx_rrmdir( $r92 );

/* style-catalog PR 2b RED test (tasks.md 2b.1): fx_pers() called with ornament UNDEFINED --
   confirmed genuinely red against the pre-widening registry, where $PERS_AXES only had five
   entries and could not have named "ornament" at all; GREEN once nm_axes() carries all 8. */
echo "--- style-catalog PR 2b: un eje sin definir (ornament) FALLA con RT_PERS_BAD_AXIS ---\n";
$r92b = fx_tmp_root();
fx_base( $r92b );
fx_sty_catalog(
	$r92b,
	"# P\n\n"
	. fx_pers( 'PERS-EDITORIAL', 'editorial', 'paper', 'generous', 'asymmetric', 'none', 'none', 'rule-divided', 'rule' )
	. fx_pers( 'PERS-MATTER', 'classic', 'warm', 'standard', 'strict-grid', 'hairline', 'tinted-field', 'bordered' )
);
list( , $out92b ) = fx_run_ok( $audit, $r92b );
ok( 'FAIL' === fx_row_level( $out92b, array( 'RT_PERS_BAD_AXIS', 'PERS-MATTER', 'ornament' ) ), 'un eje sin definir en el bloque FALLA, nombrando el eje', fx_row_level( $out92b, array( 'RT_PERS_BAD_AXIS', 'PERS-MATTER', 'ornament' ) ) );
ok( array() === fx_lines_with( $out92b, array( 'RT_STYLE_TOO_SIMILAR' ) ), 'y el ancla invalida queda fuera de la comparacion', $out92b );
fx_rrmdir( $r92b );

/* style-catalog PR 2b's own boundary, stated in design.md's testing strategy: 2/8 shared passes,
   3/8 shared FAILs (the >2 threshold PR 2b.2 wrote at :1146). */
echo "--- style-catalog PR 2b: el limite ancho -- 2 de 8 pasa, 3 de 8 FALLA ---\n";
$r92c = fx_tmp_root();
fx_base( $r92c );
fx_sty_catalog(
	$r92c,
	"# P\n\n"
	. fx_pers( 'PERS-EDITORIAL', 'editorial', 'paper', 'generous', 'asymmetric', 'none', 'none', 'rule-divided', 'rule' )
	/* Comparte EXACTAMENTE 2 de 8 con EDITORIAL (ground, accent). */
	. fx_pers( 'PERS-MATTER', 'classic', 'paper', 'standard', 'strict-grid', 'hairline', 'none', 'bordered', 'texture' )
	/* Comparte EXACTAMENTE 3 de 8 con EDITORIAL (ground, composition, ornament). */
	. fx_pers( 'PERS-DIRECT', 'monumental', 'paper', 'compact', 'asymmetric', 'accent-glow', 'gradient', 'bare', 'rule' )
);
list( , $out92c ) = fx_run_ok( $audit, $r92c );
ok( array() === fx_lines_with( $out92c, array( 'RT_STYLE_TOO_SIMILAR', 'PERS-MATTER' ) ), '2 de 8 ejes compartidos pasa: el limite es mas de dos', $out92c );
ok( 'FAIL' === fx_row_level( $out92c, array( 'RT_STYLE_TOO_SIMILAR', 'PERS-EDITORIAL', 'PERS-DIRECT' ) ), '3 de 8 ejes compartidos FALLA', fx_row_level( $out92c, array( 'RT_STYLE_TOO_SIMILAR', 'PERS-EDITORIAL', 'PERS-DIRECT' ) ) );
ok( array() !== fx_lines_with( $out92c, array( 'RT_STYLE_TOO_SIMILAR', 'PERS-EDITORIAL', 'PERS-DIRECT', 'share 3' ) ), 'y la fila dice CUANTOS ejes comparten', $out92c );
fx_rrmdir( $r92c );

/* ---------------------------------------------------------------------------
   style-catalog PR 4b (tasks.md 4b.2) — RT_STYLE_TOO_SIMILAR AT CATALOG SCALE. r92c above proves
   the comparator's boundary (2/8 passes, 3/8 FAILs) over THREE anchors; the v1 catalog ships EIGHT,
   which is C(8,2) = 28 pairs, not 3 — an O(n^2) loop that is correct at n=3 is not automatically
   correct at n=8 (an off-by-one in the `array_slice($ids,$i+1)` walk, for instance, would only ever
   miss a pair involving the LAST id). This is "value, not novelty" RED, same discipline as
   3b.1/3b.3/4a.1: the comparator itself is untouched by PR 4b, so the mechanism is already correct
   before this fixture runs — its purpose is locking the specific claim design.md's own testing
   strategy names ("4: RT_STYLE_TOO_SIMILAR over all 8") into a real assertion at the real scale.

   THIS FIXTURE STAYS SYNTHETIC, NOT THE REAL CATALOG, though the reason it was written synthetic
   no longer applies: at the time this comment was first written, `nm_axes()` still listed only 4
   ground positions and `RT_STYLE_TOO_SIMILAR` still parsed design-personalities.md, so a fixture
   using `ink-cool`/`ink-warm`/`saturated` as a GROUND value would have tripped `RT_PERS_BAD_AXIS`
   and been silently excluded from the comparison. style-catalog PR 4c closed both halves of that
   gap (`nm_axes()` widened to 9 ground positions BEFORE the repoint landed, `RT_STYLE_TOO_SIMILAR`
   now reads `STY-*.md`) — this fixture is kept synthetic anyway, on purpose, so the mechanism check
   stays independent of whatever the real catalog's 8 entries happen to be at any given moment; the
   REAL 8 `STY-*.md` files' own 28-pair table is the authoritative claim for the real catalog, and
   lives in `_README.md`, re-verified by hand every time a new entry lands. Entries 1-5 below are
   the five ported anchors' real axis positions; entries 6-8 are placeholder anchors standing in for
   the shape of a full 8-entry catalog. Every one of the 28 pairs below was computed and
   re-verified by hand before being written down. */

echo "--- style-catalog PR 4b: RT_STYLE_TOO_SIMILAR sobre 8 anclas -- la entrada #8 comparte 4 de 8 con la #3 FALLA ---\n";
$r92d = fx_tmp_root();
fx_base( $r92d );
fx_sty_catalog(
	$r92d,
	"# P\n\n"
	. fx_pers( 'PERS-EDITORIAL', 'editorial', 'paper', 'generous', 'asymmetric', 'none', 'none', 'rule-divided', 'rule' )
	. fx_pers( 'PERS-DIRECT', 'monumental', 'ink', 'compact', 'broken-grid', 'accent-glow', 'gradient', 'bare', 'none' )
	. fx_pers( 'PERS-MATTER', 'classic', 'warm', 'standard', 'strict-grid', 'hairline', 'tinted-field', 'bordered', 'texture' )
	. fx_pers( 'PERS-VITRINE', 'editorial', 'ink', 'monumental', 'strict-grid', 'soft-shadow', 'metallic', 'soft-carded', 'none' )
	. fx_pers( 'PERS-INSTITUTIONAL', 'contained', 'cool', 'standard', 'centered', 'soft-shadow', 'reserved', 'carded', 'illustration' )
	/* Placeholder #6/#7: well-separated from 1-5 and from each other, max 2/8 every pair -- filler
	   for "a catalog of eight", not a stand-in for any one real PR 4b entry. */
	. fx_pers( 'PERS-FILLER-ONE', 'contained', 'cool', 'compact', 'broken-grid', 'hairline', 'duotone', 'layered', 'pattern' )
	. fx_pers( 'PERS-FILLER-TWO', 'monumental', 'paper', 'generous', 'centered', 'accent-glow', 'polychrome', 'bordered', 'illustration' )
	/* Placeholder #8, DELIBERATELY corrupted: scale/ground/density/composition all copy PERS-MATTER
	   (classic/warm/standard/strict-grid), elevation/accent/chassis/ornament all differ from it --
	   exactly the spec's own "entry #8 shares 4 of 8 positions with entry #3" scenario, at the
	   position in the id list (last) an off-by-one in the pairwise walk is most likely to miss.
	   Named with a word, not a digit -- the catalog parser's own id regex is `[A-Z][A-Z-]*` (style-
	   catalog PR 4c widened this from the old `PERS-[A-Z-]+`, still letters/hyphens only, still no
	   digits), so `PERS-FILLER-8` would silently fail to parse as a block at all and vanish from
	   the comparison -- the exact bug this fixture was renamed to avoid reproducing. */
	. fx_pers( 'PERS-FILLER-THREE', 'classic', 'warm', 'standard', 'strict-grid', 'accent-glow', 'duotone', 'layered', 'rule' )
);
list( , $out92d ) = fx_run_ok( $audit, $r92d );
ok( 'FAIL' === fx_row_level( $out92d, array( 'RT_STYLE_TOO_SIMILAR', 'PERS-MATTER', 'PERS-FILLER-THREE' ) ), 'la entrada #8 (ultima) contra la #3 (media) FALLA en un catalogo de ocho', fx_row_level( $out92d, array( 'RT_STYLE_TOO_SIMILAR', 'PERS-MATTER', 'PERS-FILLER-THREE' ) ) );
ok( array() !== fx_lines_with( $out92d, array( 'RT_STYLE_TOO_SIMILAR', 'PERS-MATTER', 'PERS-FILLER-THREE', 'share 4' ) ), 'y la fila dice que comparten 4', $out92d );
/* Las otras 27 parejas se quedan calladas -- confirmado por conteo, no solo por la pareja que
   interesa: un comparador roto que disparara de mas tambien "encontraria" la pareja #8/#3. */
ok( 1 === count( fx_lines_with( $out92d, array( 'RT_STYLE_TOO_SIMILAR' ) ) ), 'y ninguna otra de las 28 parejas dispara la fila', count( fx_lines_with( $out92d, array( 'RT_STYLE_TOO_SIMILAR' ) ) ) );
fx_rrmdir( $r92d );

/* GREEN companion (tasks.md 4b.4's own "full catalog passes" scenario, at the same n=8 scale):
   entries 1-7 UNCHANGED from $r92d above; only placeholder #8 is corrected -- scale/ground stay
   classic/warm (each alone is legal, 1 of 8), but density and composition move off PERS-MATTER's
   own standard/strict-grid, dropping the shared count from 4 to 2. Proves the SAME mechanism clears
   a genuinely distinct 8-entry catalog with zero FAILs, not only that it can be made to fire. */
echo "--- style-catalog PR 4b: un catalogo de 8 bien separado no dispara RT_STYLE_TOO_SIMILAR en ninguna de las 28 parejas ---\n";
$r92e = fx_tmp_root();
fx_base( $r92e );
fx_sty_catalog(
	$r92e,
	"# P\n\n"
	. fx_pers( 'PERS-EDITORIAL', 'editorial', 'paper', 'generous', 'asymmetric', 'none', 'none', 'rule-divided', 'rule' )
	. fx_pers( 'PERS-DIRECT', 'monumental', 'ink', 'compact', 'broken-grid', 'accent-glow', 'gradient', 'bare', 'none' )
	. fx_pers( 'PERS-MATTER', 'classic', 'warm', 'standard', 'strict-grid', 'hairline', 'tinted-field', 'bordered', 'texture' )
	. fx_pers( 'PERS-VITRINE', 'editorial', 'ink', 'monumental', 'strict-grid', 'soft-shadow', 'metallic', 'soft-carded', 'none' )
	. fx_pers( 'PERS-INSTITUTIONAL', 'contained', 'cool', 'standard', 'centered', 'soft-shadow', 'reserved', 'carded', 'illustration' )
	. fx_pers( 'PERS-FILLER-ONE', 'contained', 'cool', 'compact', 'broken-grid', 'hairline', 'duotone', 'layered', 'pattern' )
	. fx_pers( 'PERS-FILLER-TWO', 'monumental', 'paper', 'generous', 'centered', 'accent-glow', 'polychrome', 'bordered', 'illustration' )
	. fx_pers( 'PERS-FILLER-THREE', 'classic', 'ink', 'standard', 'centered', 'accent-glow', 'duotone', 'layered', 'rule' )
);
list( $code92e, $out92e ) = fx_run_ok( $audit, $r92e );
ok( array() === fx_lines_with( $out92e, array( 'RT_STYLE_TOO_SIMILAR' ) ), 'las 28 parejas quedan en silencio: ninguna comparte mas de 2 de 8', $out92e );
ok( 0 === $code92e, 'y el catalogo de 8 conforme sale con codigo 0', $code92e );
fx_rrmdir( $r92e );

/* ---------------------------------------------------------------------------
   RT_PROOF_NOT_DISTINCT — the axis proof is a gate, not a claim.
   fx_base() already writes a conforming pair (all five axes apart); each scenario below
   overwrites one or both files, so the only variable is the axis values.
   --------------------------------------------------------------------------- */

echo "--- dos proof mockups que solo se separan en TRES ejes FALLAN, nombrando los que coinciden ---\n";
$r93 = fx_tmp_root();
fx_base( $r93 );
/* scale, ground and composition differ; density and elevation are identical in both files. */
/* accent `none` on BOTH sides is deliberate: density+elevation already share (2 of the old 5), and
   the widened >2-of-8 threshold needs a third match to still FAIL post-widening -- chassis and
   ornament keep differing so the count is exactly 3, not the whole trio. */
fx( $r93, 'skills/html-mockup/assets/proof-editorial-mockup.html', fx_proof( array( '1.500', '#FFFFFF', '1.0', 'none', 'LP-ASYMMETRIC', 'none', 'rule-divided', 'rule' ) ) );
fx( $r93, 'skills/html-mockup/assets/proof-direct-mockup.html', fx_proof( array( '1.618', '#0E1113', '1.0', 'none', 'LP-BROKEN-GRID', 'none', 'bare', 'none' ) ) );
list( , $out93 ) = fx_run_ok( $audit, $r93 );
ok( 'FAIL' === fx_row_level( $out93, array( 'RT_PROOF_NOT_DISTINCT' ) ), 'tres ejes comparten (density, elevation, accent) de ocho FALLA', fx_row_level( $out93, array( 'RT_PROOF_NOT_DISTINCT' ) ) );
ok( array() !== fx_lines_with( $out93, array( 'RT_PROOF_NOT_DISTINCT', 'only 5 of 8' ) ), 'y dice CUANTOS ejes se separan', $out93 );
/* The two assertions that make the message actionable. Without them "not different enough" would
   send the reader to diff two 300-line files by eye, which is what the row exists to replace. */
ok( array() !== fx_lines_with( $out93, array( 'RT_PROOF_NOT_DISTINCT', 'density (--sp-scale: both `1.0`)' ) ), 'y nombra el eje density con su valor compartido', $out93 );
ok( array() !== fx_lines_with( $out93, array( 'RT_PROOF_NOT_DISTINCT', 'elevation (--elev-rest: both `none`)' ) ), 'y nombra tambien el eje elevation', $out93 );
fx_rrmdir( $r93 );

echo "--- cuatro ejes separados YA basta: no hay fila ---\n";
$r94 = fx_tmp_root();
fx_base( $r94 );
/* scale, ground, density and composition differ; only elevation matches. Four is the threshold,
   and this scenario is also what proves the `--c-bg` match does not swallow the `--c-bg-alt`
   declaration sitting beside it: fx_proof() writes the SAME --c-bg-alt into both files, so a
   greedy ground match would read both grounds as #F6F7F8, drop the count to three, and fail here. */
fx( $r94, 'skills/html-mockup/assets/proof-editorial-mockup.html', fx_proof( array( '1.500', '#FFFFFF', '1.35', 'none', 'LP-ASYMMETRIC', 'none', 'rule-divided', 'rule' ) ) );
fx( $r94, 'skills/html-mockup/assets/proof-direct-mockup.html', fx_proof( array( '1.618', '#0E1113', '0.8', 'none', 'LP-BROKEN-GRID', 'gradient', 'bare', 'none' ) ) );
list( $code94, $out94 ) = fx_run_ok( $audit, $r94 );
ok( array() === fx_lines_with( $out94, array( 'RT_PROOF_NOT_DISTINCT' ) ), 'cuatro ejes distintos no producen fila', $out94 );
ok( 0 === $code94, 'y el arbol conforme sale con codigo 0', $code94 );
fx_rrmdir( $r94 );

echo "--- un proof mockup ausente FALLA: la prueba no puede volverse opcional ---\n";
$r95 = fx_tmp_root();
fx_base( $r95 );
unlink( $r95 . '/skills/html-mockup/assets/proof-direct-mockup.html' );
list( , $out95 ) = fx_run_ok( $audit, $r95 );
ok( 'FAIL' === fx_row_level( $out95, array( 'RT_PROOF_NOT_DISTINCT', 'proof-direct-mockup.html' ) ), 'borrar un proof mockup FALLA, nombrando el fichero', fx_row_level( $out95, array( 'RT_PROOF_NOT_DISTINCT', 'proof-direct-mockup.html' ) ) );
ok( array() !== fx_lines_with( $out95, array( 'RT_PROOF_NOT_DISTINCT', 'STY-DIRECT' ) ), 'y dice de que ancla era la mitad que falta', $out95 );
fx_rrmdir( $r95 );

echo "--- un eje que NINGUNO declara cuenta como coincidencia, no como diferencia ---\n";
$r96 = fx_tmp_root();
fx_base( $r96 );
/* Neither file declares --sp-scale and both share elevation: three axes apart, so this FAILs.
   The trap it closes is treating "absent" as "different" — under that reading, deleting the
   density token from both files would have made the proof look MORE distinct, not less. */
fx( $r96, 'skills/html-mockup/assets/proof-editorial-mockup.html', fx_proof( array( '1.500', '#FFFFFF', null, 'none', 'LP-ASYMMETRIC', 'none', 'rule-divided', 'rule' ) ) );
fx( $r96, 'skills/html-mockup/assets/proof-direct-mockup.html', fx_proof( array( '1.618', '#0E1113', null, 'none', 'LP-BROKEN-GRID', 'none', 'bare', 'none' ) ) );
list( , $out96 ) = fx_run_ok( $audit, $r96 );
ok( 'FAIL' === fx_row_level( $out96, array( 'RT_PROOF_NOT_DISTINCT' ) ), 'un eje ausente en ambos no cuenta como separacion', fx_row_level( $out96, array( 'RT_PROOF_NOT_DISTINCT' ) ) );
ok( array() !== fx_lines_with( $out96, array( 'RT_PROOF_NOT_DISTINCT', 'density (--sp-scale: neither declares it)' ) ), 'y lo reporta como "neither declares it", no como un valor', $out96 );
fx_rrmdir( $r96 );

echo "--- cambiar la CAJA de un hex no es un eje ---\n";
$r97 = fx_tmp_root();
fx_base( $r97 );
/* Same ground written two ways. If case counted, a find-and-replace would pass for a redesign. */
fx( $r97, 'skills/html-mockup/assets/proof-editorial-mockup.html', fx_proof( array( '1.500', '#FFFFFF', '1.35', 'none', 'LP-ASYMMETRIC', 'none', 'rule-divided', 'rule' ) ) );
fx( $r97, 'skills/html-mockup/assets/proof-direct-mockup.html', fx_proof( array( '1.618', '#ffffff', '0.8', 'none', 'LP-BROKEN-GRID', 'none', 'bare', 'none' ) ) );
list( , $out97 ) = fx_run_ok( $audit, $r97 );
ok( 'FAIL' === fx_row_level( $out97, array( 'RT_PROOF_NOT_DISTINCT' ) ), '#FFFFFF y #ffffff son el mismo ground', fx_row_level( $out97, array( 'RT_PROOF_NOT_DISTINCT' ) ) );
ok( array() !== fx_lines_with( $out97, array( 'RT_PROOF_NOT_DISTINCT', 'ground (--c-bg: both `#ffffff`)' ) ), 'y lo nombra normalizado en minusculas', $out97 );
fx_rrmdir( $r97 );

/* ---------------------------------------------------------------------------
   RT_PROOF_COPY_DIFFERS — the OTHER half of the criterion. RT_PROOF_NOT_DISTINCT above gates
   "unmistakably different"; this gates "same content". Until it existed, editing one headline in
   one proof file contaminated the experiment with every row still green.
   --------------------------------------------------------------------------- */

echo "--- la MISMA copia no produce fila, aunque difieran CSS, atributos, comentarios y saltos de linea ---\n";
$r98    = fx_tmp_root();
fx_base( $r98 );
/* Four strings, one of them REPEATED — the repeat is what makes the next two scenarios able to
   tell a multiset from a set. The direct half also carries $noise: a different HTML comment,
   different attribute values on every element, and the first text run reflowed across two source
   lines. None of that is copy, and none of it may produce a row. */
$fx_copy98 = array( 'Cortamos piedra que dura', 'Pedir presupuesto', 'Marta Iribarren', 'Pedir presupuesto' );
fx( $r98, 'skills/html-mockup/assets/proof-editorial-mockup.html', fx_proof( array( '1.500', '#FFFFFF', '1.35', 'none', 'LP-ASYMMETRIC', 'none', 'rule-divided', 'rule' ), $fx_copy98 ) );
fx( $r98, 'skills/html-mockup/assets/proof-direct-mockup.html', fx_proof( array( '1.618', '#0E1113', '0.8', '0 0 0 1px rgba(255,106,26,.22)', 'LP-BROKEN-GRID', 'gradient', 'bare', 'none' ), $fx_copy98, 'ruido-solo-en-b' ) );
list( $code98, $out98 ) = fx_run_ok( $audit, $r98 );
ok( array() === fx_lines_with( $out98, array( 'RT_PROOF_COPY_DIFFERS' ) ), 'copia identica no produce fila', $out98 );
ok( 0 === $code98, 'y el arbol conforme sale con codigo 0', $code98 );
fx_rrmdir( $r98 );

echo "--- editar UNA cadena en un solo fichero FALLA, nombrandola y contando AMBAS direcciones ---\n";
$r99 = fx_tmp_root();
fx_base( $r99 );
/* One headline edited: the old string vanishes from A and a new one appears in B. Two differences
   from one edit, and the count is the assertion that kills a check written in one direction
   only — such a check still fires here, but reports 1. */
fx( $r99, 'skills/html-mockup/assets/proof-editorial-mockup.html', fx_proof( array( '1.500', '#FFFFFF', '1.35', 'none', 'LP-ASYMMETRIC', 'none', 'rule-divided', 'rule' ), array( 'Cortamos piedra que dura', 'Marta Iribarren' ) ) );
fx( $r99, 'skills/html-mockup/assets/proof-direct-mockup.html', fx_proof( array( '1.618', '#0E1113', '0.8', '0 0 0 1px rgba(255,106,26,.22)', 'LP-BROKEN-GRID', 'gradient', 'bare', 'none' ), array( 'Cortamos piedra que DURA', 'Marta Iribarren' ) ) );
list( , $out99 ) = fx_run_ok( $audit, $r99 );
ok( 'FAIL' === fx_row_level( $out99, array( 'RT_PROOF_COPY_DIFFERS' ) ), 'una cadena editada FALLA', fx_row_level( $out99, array( 'RT_PROOF_COPY_DIFFERS' ) ) );
ok( array() !== fx_lines_with( $out99, array( 'RT_PROOF_COPY_DIFFERS', '2 visible string(s) differ' ) ), 'y cuenta las DOS diferencias que produce un solo cambio', $out99 );
ok( array() !== fx_lines_with( $out99, array( 'RT_PROOF_COPY_DIFFERS', '`Cortamos piedra que dura` is rendered 1x by proof-editorial-mockup.html and 0x by proof-direct-mockup.html' ) ), 'y nombra la cadena y el fichero que ya no la tiene', $out99 );
fx_rrmdir( $r99 );

echo "--- anadir una cadena que YA existe tambien FALLA: cuenta el numero de veces, no la presencia ---\n";
$r100 = fx_tmp_root();
fx_base( $r100 );
/* The added string is a DUPLICATE of one already on both pages, so it is present on both sides and
   a set comparison would see nothing. On a real page that is a whole extra CTA. */
fx( $r100, 'skills/html-mockup/assets/proof-editorial-mockup.html', fx_proof( array( '1.500', '#FFFFFF', '1.35', 'none', 'LP-ASYMMETRIC', 'none', 'rule-divided', 'rule' ), array( 'Cortamos piedra que dura', 'Pedir presupuesto' ) ) );
fx( $r100, 'skills/html-mockup/assets/proof-direct-mockup.html', fx_proof( array( '1.618', '#0E1113', '0.8', '0 0 0 1px rgba(255,106,26,.22)', 'LP-BROKEN-GRID', 'gradient', 'bare', 'none' ), array( 'Cortamos piedra que dura', 'Pedir presupuesto', 'Pedir presupuesto' ) ) );
list( , $out100 ) = fx_run_ok( $audit, $r100 );
ok( 'FAIL' === fx_row_level( $out100, array( 'RT_PROOF_COPY_DIFFERS' ) ), 'una cadena de mas FALLA aunque exista en los dos', fx_row_level( $out100, array( 'RT_PROOF_COPY_DIFFERS' ) ) );
ok( array() !== fx_lines_with( $out100, array( 'RT_PROOF_COPY_DIFFERS', '1 visible string(s) differ' ) ), 'y es UNA diferencia, no dos', $out100 );
ok( array() !== fx_lines_with( $out100, array( 'RT_PROOF_COPY_DIFFERS', '`Pedir presupuesto` is rendered 1x by proof-editorial-mockup.html and 2x by proof-direct-mockup.html' ) ), 'y dice cuantas veces la pinta cada fichero', $out100 );
fx_rrmdir( $r100 );

/* ---------------------------------------------------------------------------
   RT_MOCKUP_NO_AXES — the two PROOF files above carry the whole axis system; this row is about
   the files a real project is actually copied from. Measured on a532df1, corporate-mockup.html
   and ecommerce-mockup.html contained ZERO occurrences of --type-ratio, --sp-scale or
   --elev-rest, so the axis system stopped before the first client site and every project
   reverted to one look with every other row still green. The point is not that a mockup is
   pretty; it is that a mockup which cannot EXPRESS an axis silently flattens every project that
   starts from it.
   --------------------------------------------------------------------------- */

echo "--- una maqueta de produccion con los seis ejes declarados no produce fila ---\n";
$r101 = fx_tmp_root();
fx_base( $r101 );
fx( $r101, 'skills/html-mockup/assets/corporate-mockup.html', fx_mockup() );
list( $code101, $out101 ) = fx_run_ok( $audit, $r101 );
ok( array() === fx_lines_with( $out101, array( 'RT_MOCKUP_NO_AXES' ) ), 'una maqueta completa no produce fila', $out101 );
ok( 0 === $code101, 'y el arbol conforme sale con codigo 0', $code101 );
fx_rrmdir( $r101 );

/* ---------------------------------------------------------------------------
   RT_MOCKUP_BLEED_FIXED_BAND — a fixed content band is only a defect NEXT TO A BLEED.

   Two arms, so the three scenarios below are one FAIL and two controls. The obvious wrong
   implementation — "a fixed --content-width is bad" — turns every LP-CENTERED and LP-STRICT-GRID
   mockup in the framework red, and those are correct: they cap the band and centre it and never
   bleed. What makes a fixed band a defect is `full-end`, because the named-line grid must sum to
   the VIEWPORT, so capping the twelve columns leaves the outer `1fr` gutter as the only track that
   can absorb a wider screen and a `1fr` has no ceiling. Measured on assets/gallery/index.html at
   1140px: 150.1 / 390.1 / 710.1px of dead margin at 1440 / 1920 / 2560.
   --------------------------------------------------------------------------- */

echo "--- una maqueta que sangra a full-end con --content-width fijo FALLA ---\n";
$r101b = fx_tmp_root();
fx_base( $r101b );
fx( $r101b, 'skills/html-mockup/assets/corporate-mockup.html', fx_mockup( array(), array(), array(), array( 'width' => '1140px', 'bleed' => true ) ) );
list( , $out101b ) = fx_run_ok( $audit, $r101b );
ok( 'FAIL' === fx_row_level( $out101b, array( 'RT_MOCKUP_BLEED_FIXED_BAND' ) ), 'banda fija junto a un sangrado FALLA', fx_row_level( $out101b, array( 'RT_MOCKUP_BLEED_FIXED_BAND' ) ) );
ok( array() !== fx_lines_with( $out101b, array( 'RT_MOCKUP_BLEED_FIXED_BAND', '1140px' ) ), 'y cita el valor fijo que encontro', $out101b );
ok( array() !== fx_lines_with( $out101b, array( 'RT_MOCKUP_BLEED_FIXED_BAND', 'corporate-mockup.html' ) ), 'y nombra el fichero', $out101b );
fx_rrmdir( $r101b );

echo "--- la MISMA banda fija SIN sangrado no produce fila: LP-CENTERED es correcto ---\n";
$r101c = fx_tmp_root();
fx_base( $r101c );
/* The control that stops this row becoming "fixed width is bad". Byte-identical to the scenario
   above except that nothing says `full-end`. If it ever goes red the row has stopped reading the
   bleed and started reading the token alone, and half the framework fails with it. */
fx( $r101c, 'skills/html-mockup/assets/corporate-mockup.html', fx_mockup( array(), array(), array(), array( 'width' => '1140px' ) ) );
list( $code101c, $out101c ) = fx_run_ok( $audit, $r101c );
ok( array() === fx_lines_with( $out101c, array( 'RT_MOCKUP_BLEED_FIXED_BAND' ) ), 'sin full-end una banda fija no produce fila', $out101c );
ok( 0 === $code101c, 'y el arbol sale con codigo 0', $code101c );
fx_rrmdir( $r101c );

echo "--- sangrado con banda FLUIDA tampoco produce fila ---\n";
$r101d = fx_tmp_root();
fx_base( $r101d );
/* The other control, and the one proving the row reads the VALUE rather than the presence of the
   token: same bleed as the failing scenario, same declaration, only the value moves. */
fx( $r101d, 'skills/html-mockup/assets/corporate-mockup.html', fx_mockup( array(), array(), array(), array( 'width' => 'clamp(1140px, calc(1140px + (100vw - 1280px) * 0.5), 1600px)', 'bleed' => true ) ) );
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
fx( $r101e, 'skills/html-mockup/assets/corporate-mockup.html', fx_mockup( array(), array(), array(), array( 'bleed' => true, 'sel' => '.band .formwrap' ) ) );
list( , $out101e ) = fx_run_ok( $audit, $r101e );
ok( 'FAIL' === fx_row_level( $out101e, array( 'RT_MOCKUP_BLEED_NOT_MEDIA' ) ), 'un .formwrap contra el cristal FALLA', fx_row_level( $out101e, array( 'RT_MOCKUP_BLEED_NOT_MEDIA' ) ) );
ok( array() !== fx_lines_with( $out101e, array( 'RT_MOCKUP_BLEED_NOT_MEDIA', '.band .formwrap' ) ), 'y cita el selector culpable', $out101e );
ok( array() !== fx_lines_with( $out101e, array( 'RT_MOCKUP_BLEED_NOT_MEDIA', 'corporate-mockup.html' ) ), 'y nombra el fichero', $out101e );
fx_rrmdir( $r101e );

echo "--- .hero .media contra el cristal NO produce fila: eso es el plano funcionando ---\n";
$r101f = fx_tmp_root();
fx_base( $r101f );
/* The control that stops this row deleting the blueprint. Byte-identical to the scenario above
   except for the subject of the declaration. */
fx( $r101f, 'skills/html-mockup/assets/corporate-mockup.html', fx_mockup( array(), array(), array(), array( 'bleed' => true ) ) );
list( $code101f, $out101f ) = fx_run_ok( $audit, $r101f );
ok( array() === fx_lines_with( $out101f, array( 'RT_MOCKUP_BLEED_NOT_MEDIA' ) ), 'una imagen sangrando no produce fila', $out101f );
ok( 0 === $code101f, 'y el arbol sale con codigo 0', $code101f );
fx_rrmdir( $r101f );

/* ---- El re-apuntado a medias: la forma que TODAS las filas anteriores dejaban pasar ----
 *
 * Apuntar un proyecto a otra ancla es editar seis cosas —cinco lineas de token y el marcador de
 * composition— y hasta esta fila el audit quedaba verde con cinco de seis. La sexta se entrega como
 * un sitio que no es ninguna de las dos anclas. Aqui el fichero DICE PERS-VITRINE y lleva las cinco
 * posiciones de PERS-INSTITUTIONAL, que es el caso extremo del mismo defecto.
 */
echo "--- un mockup cuyas etiquetas de eje no son las de su ancla FALLA ---\n";
$r131 = fx_tmp_root();
fx_base( $r131 );
fx( $r131, 'skills/html-mockup/assets/corporate-mockup.html', fx_mockup( array(), array(), array(), array(), array( 'PERS-VITRINE', 'cool' ) ) );
list( , $out131 ) = fx_run_ok( $audit, $r131 );
ok( 'FAIL' === fx_row_level( $out131, array( 'RT_MOCKUP_AXES_MISMATCH', 'corporate-mockup.html' ) ), 'un fichero que dice ser un ancla y lleva las posiciones de otra FALLA', fx_row_level( $out131, array( 'RT_MOCKUP_AXES_MISMATCH', 'corporate-mockup.html' ) ) );
/* Las dos aserciones que hacen accionable el mensaje. Sin ellas "no coincide con su ancla" manda al
   lector a comparar un :root contra un catalogo a ojo, que es justamente el diff que nadie hace. */
/* style-catalog PR 2b: the fixture's :root still carries PERS-INSTITUTIONAL's own accent/chassis/
   ornament tokens (fx_mockup()'s defaults), so those three ALSO mismatch against VITRINE's real
   ones -- 4 old + 3 new = 7 of 8, elevation the only survivor. */
ok( array() !== fx_lines_with( $out131, array( 'RT_MOCKUP_AXES_MISMATCH', '7 of 8' ) ), 'y dice CUANTOS ejes discrepan: siete, porque VITRINE e INSTITUTIONAL comparten elevation', $out131 );
ok( array() !== fx_lines_with( $out131, array( 'RT_MOCKUP_AXES_MISMATCH', 'composition', 'strict-grid' ) ), 'y nombra cada eje con las DOS posiciones, la que trae y la que deberia', $out131 );
/* El eje compartido NO se reporta: una coincidencia no es un defecto, y listarla ahogaria los
   cuatro que si lo son. INSTITUTIONAL y VITRINE estan ambas en `soft-shadow`. */
ok( array() === fx_lines_with( $out131, array( 'RT_MOCKUP_AXES_MISMATCH', 'elevation `soft-shadow`' ) ), 'y el eje en el que SI coinciden no se reporta', $out131 );
fx_rrmdir( $r131 );

/* ---- Y el marcador no puede desaparecer para apagar la fila de arriba ----
 *
 * Sin esta fila, borrar la linea `Anchor:` dejaba RT_MOCKUP_AXES_MISMATCH sin sujeto y el audit en
 * verde: la misma forma exacta por la que PERS-VITRINE se entrego fuera de $PERS_IDS. Por eso los
 * dos assets de arranque estan por NOMBRE y no por glob.
 */
echo "--- y un asset de arranque sin marcador de ancla FALLA ---\n";
$r132 = fx_tmp_root();
fx_base( $r132 );
/* style-catalog PR 1f: `corporate-mockup.html` (the hand-maintained original) is deleted and
   pruned out of $anchored_required, so this scenario moved to `chassis/corporate.html` — one of
   the two remaining required starting assets — to keep testing the same thing: a required asset
   without an anchor marker FAILs. */
fx( $r132, 'skills/html-mockup/assets/chassis/corporate.html', str_replace( '/* Anchor: PERS-INSTITUTIONAL */', '', fx_mockup() ) );
list( , $out132 ) = fx_run_ok( $audit, $r132 );
ok( 'FAIL' === fx_row_level( $out132, array( 'RT_MOCKUP_ANCHOR_UNDECLARED', 'chassis/corporate.html' ) ), 'un asset de arranque que no dice a que ancla apunta FALLA', fx_row_level( $out132, array( 'RT_MOCKUP_ANCHOR_UNDECLARED', 'chassis/corporate.html' ) ) );
/* Y no se le acusa ADEMAS de incoherencia: sin ancla declarada no hay contra que comparar, y dos
   filas por un hecho mandan al lector a arreglar dos cosas donde solo hay una. */
ok( array() === fx_lines_with( $out132, array( 'RT_MOCKUP_AXES_MISMATCH', 'chassis/corporate.html' ) ), 'y no se le acusa tambien de incoherencia: sin ancla no hay contra que comparar', $out132 );
fx_rrmdir( $r132 );

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
fx( $r133, 'skills/html-mockup/assets/corporate-mockup.html',
	str_replace( '</style>', "</style>\n<div class=\"faq\"><details><summary>A</summary><p>a</p></details>\n"
		. "<details><summary>B</summary><p>b</p></details></div>", fx_mockup() ) );
list( , $out133 ) = fx_run_ok( $audit, $r133 );
ok( 'FAIL' === fx_row_level( $out133, array( 'RT_MOCKUP_DISCLOSURE_STATE', 'open no row' ) ), 'una lista con todas las filas cerradas FALLA', fx_row_level( $out133, array( 'RT_MOCKUP_DISCLOSURE_STATE', 'open no row' ) ) );
fx_rrmdir( $r133 );

echo "--- y una que abre mas de una tambien ---\n";
$r134 = fx_tmp_root();
fx_base( $r134 );
fx( $r134, 'skills/html-mockup/assets/corporate-mockup.html',
	str_replace( '</style>', "</style>\n<div class=\"faq\"><details open><summary>A</summary><p>a</p></details>\n"
		. "<details open><summary>B</summary><p>b</p></details></div>", fx_mockup() ) );
list( , $out134 ) = fx_run_ok( $audit, $r134 );
ok( 'FAIL' === fx_row_level( $out134, array( 'RT_MOCKUP_DISCLOSURE_STATE', 'more than one row' ) ), 'una lista con dos filas abiertas FALLA: no es un acordeon, es una pagina larga disfrazada de corta', fx_row_level( $out134, array( 'RT_MOCKUP_DISCLOSURE_STATE', 'more than one row' ) ) );
fx_rrmdir( $r134 );

echo "--- y una seccion FAQ sin ningun <details> es la tercera causa ---\n";
$r135 = fx_tmp_root();
fx_base( $r135 );
fx( $r135, 'skills/html-mockup/assets/corporate-mockup.html',
	str_replace( '</style>', "</style>\n<section class=\"sec faq\"><div class=\"qa\"><h3>A</h3><p>a</p></div>"
		. "<div class=\"qa\"><h3>B</h3><p>b</p></div></section>", fx_mockup() ) );
list( , $out135 ) = fx_run_ok( $audit, $r135 );
ok( 'FAIL' === fx_row_level( $out135, array( 'RT_MOCKUP_DISCLOSURE_STATE', 'no <details> at all' ) ), 'una seccion FAQ que no es un desplegable en absoluto FALLA', fx_row_level( $out135, array( 'RT_MOCKUP_DISCLOSURE_STATE', 'no <details> at all' ) ) );
fx_rrmdir( $r135 );

/* Y el control negativo, que es el que impide que la fila se vuelva ruido: UN solo `<details>` no
   es una lista y no se juzga. Es la forma de `.handoff`, el bloque de spec, que empieza cerrado a
   proposito porque nada de lo que hay dentro merece la primera mirada del lector. */
echo "--- pero un <details> suelto no es una lista y no se juzga ---\n";
$r136 = fx_tmp_root();
fx_base( $r136 );
fx( $r136, 'skills/html-mockup/assets/corporate-mockup.html',
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
fx( $r137, 'skills/html-mockup/assets/corporate-mockup.html',
	str_replace( '</style>', ".team{display:grid;grid-template-columns:repeat(auto-fill,minmax(15rem,1fr))}\n</style>", fx_mockup() ) );
list( , $out137 ) = fx_run_ok( $audit, $r137 );
ok( 'FAIL' === fx_row_level( $out137, array( 'RT_MOCKUP_GRID_AUTOFILL', 'corporate-mockup.html' ) ), 'auto-fill sin justificacion al lado FALLA', fx_row_level( $out137, array( 'RT_MOCKUP_GRID_AUTOFILL', 'corporate-mockup.html' ) ) );
fx_rrmdir( $r137 );

/* Los dos controles negativos, que son los que impiden que la fila se vuelva un tic: la grafia
   correcta no dispara, y la incorrecta CON su razon escrita tampoco. */
echo "--- pero auto-fit no, y auto-fill justificado tampoco ---\n";
$r138 = fx_tmp_root();
fx_base( $r138 );
fx( $r138, 'skills/html-mockup/assets/corporate-mockup.html',
	str_replace( '</style>', ".team{display:grid;grid-template-columns:repeat(auto-fit,minmax(15rem,1fr))}\n</style>", fx_mockup() ) );
list( , $out138 ) = fx_run_ok( $audit, $r138 );
ok( array() === fx_lines_with( $out138, array( 'RT_MOCKUP_GRID_AUTOFILL' ) ), 'auto-fit no produce fila', $out138 );
fx_rrmdir( $r138 );

$r139 = fx_tmp_root();
fx_base( $r139 );
fx( $r139, 'skills/html-mockup/assets/corporate-mockup.html',
	str_replace( '</style>', "/* auto-fill: el hueco es el objetivo, esto es un calendario */\n"
		. ".cal{display:grid;grid-template-columns:repeat(auto-fill,minmax(3rem,1fr))}\n</style>", fx_mockup() ) );
list( , $out139 ) = fx_run_ok( $audit, $r139 );
ok( array() === fx_lines_with( $out139, array( 'RT_MOCKUP_GRID_AUTOFILL' ) ), 'auto-fill CON su razon escrita al lado no produce fila: la fila pide una justificacion, no prohibe', $out139 );
fx_rrmdir( $r139 );

echo "--- .hero .frame tampoco: la lista blanca es de MEDIA, no de un literal ---\n";
$r101g = fx_tmp_root();
fx_base( $r101g );
fx( $r101g, 'skills/html-mockup/assets/corporate-mockup.html', fx_mockup( array(), array(), array(), array( 'bleed' => true, 'sel' => '.hero .frame' ) ) );
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
fx( $r101h, 'skills/html-mockup/assets/corporate-mockup.html', fx_mockup( array(), array(), array(), array( 'bleed' => true, 'sel' => '.hero .mystery-box' ) ) );
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
fx( $r101i, 'skills/html-mockup/assets/corporate-mockup.html', fx_mockup( array(), array(), array(), array( 'bleed' => true, 'comment' => true ) ) );
list( $code101i, $out101i ) = fx_run_ok( $audit, $r101i );
ok( array() === fx_lines_with( $out101i, array( 'RT_MOCKUP_BLEED_NOT_MEDIA' ) ), 'prosa citando full-end no produce fila', $out101i );
ok( 0 === $code101i, 'y el arbol sale con codigo 0', $code101i );
fx_rrmdir( $r101i );

echo "--- en una lista de selectores, la fila nombra al culpable y no a su vecino ---\n";
$r101j = fx_tmp_root();
fx_base( $r101j );
fx( $r101j, 'skills/html-mockup/assets/corporate-mockup.html', fx_mockup( array(), array(), array(), array( 'bleed' => true, 'sel' => '.hero .media,' . "\n" . '  .cases .items' ) ) );
list( , $out101j ) = fx_run_ok( $audit, $r101j );
ok( 'FAIL' === fx_row_level( $out101j, array( 'RT_MOCKUP_BLEED_NOT_MEDIA' ) ), 'una lista con un no-media FALLA', fx_row_level( $out101j, array( 'RT_MOCKUP_BLEED_NOT_MEDIA' ) ) );
ok( array() !== fx_lines_with( $out101j, array( 'RT_MOCKUP_BLEED_NOT_MEDIA', '.cases .items' ) ), 'y nombra la tarjeta', $out101j );
ok( array() === fx_lines_with( $out101j, array( 'RT_MOCKUP_BLEED_NOT_MEDIA', '.hero .media`' ) ), 'y NO nombra la imagen inocente de la misma regla', $out101j );
fx_rrmdir( $r101j );

echo "--- una maqueta SIN --sp-scale FALLA, nombrando ese token ---\n";
$r102 = fx_tmp_root();
fx_base( $r102 );
/* Only the density axis is missing. The assertion names the token because "no axis tokens" would
   send a reader to diff an 880-line file by eye against a reference — which is the work this row
   exists to replace. */
fx( $r102, 'skills/html-mockup/assets/corporate-mockup.html', fx_mockup( array( '--sp-scale' ) ) );
list( , $out102 ) = fx_run_ok( $audit, $r102 );
ok( 'FAIL' === fx_row_level( $out102, array( 'RT_MOCKUP_NO_AXES' ) ), 'falta un token de eje y FALLA', fx_row_level( $out102, array( 'RT_MOCKUP_NO_AXES' ) ) );
ok( array() !== fx_lines_with( $out102, array( 'RT_MOCKUP_NO_AXES', '--sp-scale' ) ), 'y nombra el token que falta', $out102 );
ok( array() !== fx_lines_with( $out102, array( 'RT_MOCKUP_NO_AXES', 'corporate-mockup.html' ) ), 'y nombra el fichero', $out102 );
/* The five it DOES declare must not be reported as missing, or the message stops being a list of
   what to fix and becomes noise. */
ok( array() === fx_lines_with( $out102, array( 'RT_MOCKUP_NO_AXES', '--type-ratio' ) ), 'y no acusa a los tokens que si estan', $out102 );
fx_rrmdir( $r102 );

echo "--- una maqueta SIN el comentario de composicion FALLA, nombrandolo ---\n";
$r103 = fx_tmp_root();
fx_base( $r103 );
/* Composition is the one axis whose value is a layout rule rather than a number, so it has no
   token to look for: the marker IS the declaration. A check that only counted custom properties
   would pass a file that had quietly dropped a whole axis. */
fx( $r103, 'skills/html-mockup/assets/ecommerce-mockup.html', fx_mockup( array( 'composition' ) ) );
list( , $out103 ) = fx_run_ok( $audit, $r103 );
ok( 'FAIL' === fx_row_level( $out103, array( 'RT_MOCKUP_NO_AXES' ) ), 'falta el marcador de composicion y FALLA', fx_row_level( $out103, array( 'RT_MOCKUP_NO_AXES' ) ) );
ok( array() !== fx_lines_with( $out103, array( 'RT_MOCKUP_NO_AXES', 'composition: LP-' ) ), 'y nombra el marcador que falta', $out103 );
ok( array() !== fx_lines_with( $out103, array( 'RT_MOCKUP_NO_AXES', 'ecommerce-mockup.html' ) ), 'y nombra el fichero', $out103 );
fx_rrmdir( $r103 );

echo "--- un asset con prefijo _ es CONTENIDO, no maqueta: no se le exigen ejes ---\n";
$r104 = fx_tmp_root();
fx_base( $r104 );
/* The `_` skip is a RESERVATION, not a response to a file that exists: no `_`-prefixed `.html`
   partial is in the tree today. `_axis-proof-content.md` sits in this same directory but is a
   `.md`, and the audit's glob is `*.html`, so it was never in scope with or without the skip —
   naming it here made the skip look load-bearing where it did nothing. `_shared-copy.html` is the
   shape the reservation is FOR: an `.html` file with no axis declarations at all. Without the
   skip this row would demand axes from it; with a skip written too wide it would demand them from
   nothing at all, which is why the scenario above has to keep failing while this one passes. */
fx( $r104, 'skills/html-mockup/assets/_shared-copy.html', fx_mockup( array( '--type-ratio', '--display-lh', '--fs-h1-max', '--sp-scale', '--elev-rest', 'composition' ) ) );
list( $code104, $out104 ) = fx_run_ok( $audit, $r104 );
ok( array() === fx_lines_with( $out104, array( 'RT_MOCKUP_NO_AXES' ) ), 'un fichero _-prefijado no produce fila', $out104 );
ok( 0 === $code104, 'y el arbol sigue saliendo con codigo 0', $code104 );
fx_rrmdir( $r104 );

echo "--- declarar un eje FUERA de :root no cuenta: la maqueta sigue FALLANDO ---\n";
$r105 = fx_tmp_root();
fx_base( $r105 );
/* THE SCOPING SCENARIO. framework-audit.php reads only the first `:root{...}` block and says so:
   "a whole-file scan would match a USE and call it a declaration". Nothing in this suite tested
   that until now — mutate `$mockup_root` to the whole file and all fifteen assertions above stay
   green, because the check's regex demands a colon after the property name and the `.card` line
   every fixture carries is `var(--elev-rest)}`, a use with a `)` there.

   This fixture is the one that fails the mutant. `--elev-rest` and the composition marker are
   omitted from `:root` and written OUTSIDE it instead — in a `:root[data-theme="dark"]` block, in
   an ordinary `.panel` rule, and (for the comment) loose in the stylesheet. Both mockups really do
   carry a `[data-theme]` block, so this is the shape a production file drifts into, not an
   invented one. It must FAIL: an axis declared only under a dark theme is an axis the light
   default every project ships cannot express, which is the exact regression this row exists for.
   Under the mutant it PASSES, and these assertions go red. */
fx(
	$r105,
	'skills/html-mockup/assets/corporate-mockup.html',
	fx_mockup( array( '--elev-rest', 'composition' ), array( '--elev-rest', 'composition' ) )
);
list( , $out105 ) = fx_run_ok( $audit, $r105 );
ok( 'FAIL' === fx_row_level( $out105, array( 'RT_MOCKUP_NO_AXES' ) ), 'un eje declarado solo fuera de :root FALLA', fx_row_level( $out105, array( 'RT_MOCKUP_NO_AXES' ) ) );
ok( array() !== fx_lines_with( $out105, array( 'RT_MOCKUP_NO_AXES', '--elev-rest' ) ), 'y nombra el token que :root no declara', $out105 );
ok( array() !== fx_lines_with( $out105, array( 'RT_MOCKUP_NO_AXES', 'composition: LP-' ) ), 'y tambien el marcador de composicion suelto', $out105 );
/* The four it DOES declare in `:root` must stay unaccused, or a reader cannot tell the scoping
   failure from a file with no axes at all. */
ok( array() === fx_lines_with( $out105, array( 'RT_MOCKUP_NO_AXES', '--sp-scale' ) ), 'y no acusa a los que si estan en :root', $out105 );
fx_rrmdir( $r105 );

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
	'skills/html-mockup/assets/corporate-mockup.html',
	fx_mockup( array(), array(), array( 'stack' => "'Fraunces', Georgia, 'Times New Roman', serif" ) )
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
	'skills/html-mockup/assets/corporate-mockup.html',
	fx_mockup(
		array(),
		array(),
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
	'skills/html-mockup/assets/corporate-mockup.html',
	fx_mockup(
		array(),
		array(),
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
	'skills/html-mockup/assets/corporate-mockup.html',
	fx_mockup(
		array(),
		array(),
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
	'skills/html-mockup/assets/corporate-mockup.html',
	fx_mockup(
		array(),
		array(),
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
	'skills/html-mockup/assets/corporate-mockup.html',
	fx_mockup( array(), array(), array( 'stack' => 'system-ui, sans-serif' ) )
);
list( $code110, $out110 ) = fx_run_ok( $audit, $r110 );
ok( array() === fx_lines_with( $out110, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED' ) ), 'un stack generico no produce fila', $out110 );
ok( 0 === $code110, 'y el arbol sale con codigo 0', $code110 );
fx_rrmdir( $r110 );

echo "--- la galeria GENERADA esta sujeta a la misma fila, a cualquier profundidad ---\n";
$r111 = fx_tmp_root();
fx_base( $r111 );
/* The same recursion argument RT_MOCKUP_NO_AXES needed. `assets/gallery/index.html` renders all
   four anchors, so it is the one file that names EVERY family the framework has — and a glob that
   does not descend would leave the worst offender unchecked. */
fx(
	$r111,
	'skills/html-mockup/assets/gallery/index.html',
	fx_mockup( array(), array(), array( 'stack' => "'Inter Tight', system-ui, sans-serif" ) )
);
list( , $out111 ) = fx_run_ok( $audit, $r111 );
ok( array() !== fx_lines_with( $out111, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED', 'gallery/index.html' ) ), 'la galeria en un subdirectorio tambien produce la fila', $out111 );
ok( array() !== fx_lines_with( $out111, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED', 'Inter Tight' ) ), 'y nombra su familia sin incrustar', $out111 );
fx_rrmdir( $r111 );

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
	'skills/html-mockup/assets/corporate-mockup.html',
	fx_mockup(
		array(),
		array(),
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
   to: `skills/html-mockup/assets/fonts/_fonts.php:63-69` embeds exactly 7 faces (Fraunces,
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
	'skills/html-mockup/assets/corporate-mockup.html',
	fx_mockup( array(), array(), array( 'stack' => "'Canela Deck', Georgia, serif" ) )
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
	'skills/html-mockup/assets/corporate-mockup.html',
	fx_mockup(
		array(),
		array(),
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
	'skills/html-mockup/assets/corporate-mockup.html',
	fx_mockup(
		array(),
		array(),
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
	'skills/html-mockup/assets/corporate-mockup.html',
	fx_mockup(
		array(),
		array(),
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
	'skills/html-mockup/assets/corporate-mockup.html',
	fx_mockup( array(), array(), array( 'stack' => "'GT Walsheim', system-ui, sans-serif" ) )
);
list( , $out112f ) = fx_run_ok( $audit, $r112f );
ok( 'FAIL' === fx_row_level( $out112f, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED' ) ), 'nombrar una familia ausente distinta de la ya fijada en 4a tambien FALLA', fx_row_level( $out112f, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED' ) ) );
ok( array() !== fx_lines_with( $out112f, array( 'RT_MOCKUP_FONT_NOT_EMBEDDED', 'GT Walsheim' ) ), 'y nombra la familia -- el mecanismo no esta atado a un solo nombre fijado por 4a', $out112f );
fx_rrmdir( $r112f );

/* ---------------------------------------------------------------------------
   style-catalog PR 1b (tasks.md 1b.1) — el chasis del cliente vive en `assets/chassis/`, NUNCA
   en `assets/gallery/chassis/`. design.md D1's rejected path collided with RT_GALLERY_NOT_DISTINCT
   (:2683 — any `.html` under a `gallery/` segment renders zero `<section class="strip">` and FAILs
   as a catalog-page candidate), which PR 1a caught and corrected during apply but never locked into
   this suite: every assertion for it so far only ran against the real repo, once, by hand. These
   four scenarios are that missing lock, using the SAME PERS-INSTITUTIONAL/cool default `fx_mockup()`
   already stands at, because that is the anchor `corporate-mockup.html` — and now the generated
   corporate chassis — is actually pointed at.
   --------------------------------------------------------------------------- */

echo "--- el chasis EN LA RUTA RECHAZADA (assets/gallery/chassis/) SI cae bajo RT_GALLERY_NOT_DISTINCT ---\n";
$r140 = fx_tmp_root();
fx_base( $r140 );
fx( $r140, 'skills/html-mockup/assets/gallery/chassis/corporate.html', fx_mockup() );
list( , $out140 ) = fx_run_ok( $audit, $r140 );
ok( 'FAIL' === fx_row_level( $out140, array( 'RT_GALLERY_NOT_DISTINCT', 'gallery/chassis/corporate.html' ) ), 'un chasis de un solo sitio bajo gallery/ FALLA: no puede rendir ninguna .strip', fx_row_level( $out140, array( 'RT_GALLERY_NOT_DISTINCT', 'gallery/chassis/corporate.html' ) ) );
fx_rrmdir( $r140 );

echo "--- el mismo chasis en la ruta REAL (assets/chassis/) no cae bajo esa fila ---\n";
$r141 = fx_tmp_root();
fx_base( $r141 );
fx( $r141, 'skills/html-mockup/assets/chassis/corporate.html', fx_mockup() );
list( , $out141 ) = fx_run_ok( $audit, $r141 );
ok( array() === fx_lines_with( $out141, array( 'RT_GALLERY_NOT_DISTINCT' ) ), 'assets/chassis/ es hermano de assets/gallery/, no un hijo: la fila nunca lo evalua', $out141 );
/* La afirmacion literal de 1b.1: RT_MOCKUP_NO_AXES calla sobre el chasis generado. Se cumple ya
   desde PR 1a — el `:root` que hereda de `$css` declara los cinco ejes desde el shell — así que esto
   fija la garantía a la ruta nueva en vez de reproducir un rojo que ya no existe; ver el informe de
   esta fase para la verificación en vivo que descartó ese rojo literal. */
ok( array() === fx_lines_with( $out141, array( 'RT_MOCKUP_NO_AXES' ) ), 'y tampoco produce fila de ejes: hereda el :root completo de $css', $out141 );
ok( array() === fx_lines_with( $out141, array( 'RT_MOCKUP_ANCHOR_UNDECLARED' ) ), 'declara su ancla (la fila lo revisa por glob, no solo los dos ficheros de arranque)', $out141 );
ok( array() === fx_lines_with( $out141, array( 'RT_MOCKUP_AXES_MISMATCH' ) ), 'y sus posiciones concuerdan con la que declara', $out141 );
fx_rrmdir( $r141 );

/* La forma EXACTA que faq_block_html()/disclosure_list_html() (:13155/:13169) emiten para las
   páginas del chasis: dos `<details>`, solo la primera abierta. Aquí se rompe esa forma a propósito
   para probar que la fila realmente vigila el chasis en su ruta nueva, y no solo los dos ficheros de
   arranque de siempre. */
echo "--- y una lista desplegable rota EN EL CHASIS (no en los dos ficheros de siempre) tambien FALLA ---\n";
$r142 = fx_tmp_root();
fx_base( $r142 );
fx(
	$r142,
	'skills/html-mockup/assets/chassis/corporate.html',
	str_replace(
		'</style>',
		"</style>\n<div class=\"faqlist\"><details><summary>A</summary><p>a</p></details>"
			. '<details><summary>B</summary><p>b</p></details></div>',
		fx_mockup()
	)
);
list( , $out142 ) = fx_run_ok( $audit, $r142 );
ok( 'FAIL' === fx_row_level( $out142, array( 'RT_MOCKUP_DISCLOSURE_STATE', 'open no row' ) ), 'un chasis cuyo FAQ no abre ninguna fila FALLA igual que cualquier otro mockup', fx_row_level( $out142, array( 'RT_MOCKUP_DISCLOSURE_STATE', 'open no row' ) ) );
fx_rrmdir( $r142 );

echo "--- y la forma correcta (solo la primera fila abierta) no produce fila ---\n";
$r143 = fx_tmp_root();
fx_base( $r143 );
fx(
	$r143,
	'skills/html-mockup/assets/chassis/corporate.html',
	str_replace(
		'</style>',
		"</style>\n<div class=\"faqlist\"><details open><summary>A</summary><p>a</p></details>"
			. '<details><summary>B</summary><p>b</p></details></div>',
		fx_mockup()
	)
);
list( , $out143 ) = fx_run_ok( $audit, $r143 );
ok( array() === fx_lines_with( $out143, array( 'RT_MOCKUP_DISCLOSURE_STATE' ) ), 'la forma que realmente genera este PR (primera fila abierta, solo una) no produce fila', $out143 );
fx_rrmdir( $r143 );

/* ---------------------------------------------------------------------------
   style-catalog PR 1c (tasks.md 1c.1) — el chasis de ecommerce, `assets/chassis/ecommerce.html`.

   VERIFICADO EN VIVO ANTES DE ESCRIBIR ESTE BLOQUE (misma disciplina que 1b.1, ver el informe de
   esta fase): `html_assets_deep()` (:1609) es un glob recursivo por EXTENSION, ciego al nombre de
   archivo, y `RT_GALLERY_NOT_DISTINCT`/`RT_MOCKUP_NO_AXES`/`RT_MOCKUP_ANCHOR_UNDECLARED`/
   `RT_MOCKUP_AXES_MISMATCH` no leen el nombre del fichero en ningun punto de su logica — el
   mecanismo que r140/r141 fijaron para `corporate.html` ya cubre `ecommerce.html` por construccion,
   asi que repetir ese par aqui no fijaria ningun comportamiento nuevo del auditor. Y la afirmacion
   literal de 1c.1 (RT_MOCKUP_NO_AXES en rojo antes de escribir el cuerpo) tampoco se sostiene por la
   misma razon que en 1b.1: el `:root` del chasis hereda de `$css` desde el shell de PR 1a, axis-
   completo independientemente de si el `<body>` tiene contenido — confirmado corriendo
   framework-audit.php contra el repo real en el estado shell-only de este PR (0 FAIL / 4 WARN, el
   placeholder `<!-- chassis body markup (site type: ecommerce): not yet generated -->` no produce
   ninguna fila RT_MOCKUP_*).

   LO QUE SI ESTA GENUINAMENTE SIN CUBRIR: `assets/chassis/ecommerce.html` no tiene NINGUNA fixture
   propia en esta suite — cero regresion sintetica en esa ruta especifica — y la forma EXACTA que
   1c.2 emite para el acordeon de PDP es DISTINTA de la de 1b (tres `<details>` bajo `class="qas"`,
   no dos bajo `class="faqlist"` — `disclosure_list_html()` (:13155) trata ambos wrappers igual, pero
   ningun test hasta ahora ejercito tres filas ni la ruta `ecommerce.html`). Estas cuatro
   comprobaciones fijan ambas cosas: silencio en la ruta correcta para el fichero hermano, y un par
   RED→GREEN genuino contra la forma real de tres filas. */

echo "--- el chasis de ecommerce en la ruta correcta tambien calla en las cuatro filas RT_MOCKUP_*/RT_GALLERY_NOT_DISTINCT ---\n";
$r144 = fx_tmp_root();
fx_base( $r144 );
fx( $r144, 'skills/html-mockup/assets/chassis/ecommerce.html', fx_mockup() );
list( , $out144 ) = fx_run_ok( $audit, $r144 );
ok( array() === fx_lines_with( $out144, array( 'RT_GALLERY_NOT_DISTINCT' ) ), 'ecommerce.html es el mismo hermano de assets/gallery/ que corporate.html: tampoco lo evalua esa fila', $out144 );
ok( array() === fx_lines_with( $out144, array( 'RT_MOCKUP_NO_AXES' ) ), 'y hereda el :root completo de $css igual que el chasis corporativo', $out144 );
ok( array() === fx_lines_with( $out144, array( 'RT_MOCKUP_ANCHOR_UNDECLARED' ) ), 'declara su ancla', $out144 );
ok( array() === fx_lines_with( $out144, array( 'RT_MOCKUP_AXES_MISMATCH' ) ), 'y sus posiciones concuerdan con la que declara', $out144 );
fx_rrmdir( $r144 );

/* La forma EXACTA que `chassis_ecom_page_pdp()` emite via `disclosure_list_html($P['acc'],'qas')`
   (mockup-guide.md § COMP-ACCORDION): tres `<details>`, solo la primera abierta — no dos, como en
   el FAQ corporativo. Se rompe a proposito para probar que la fila vigila esta forma tambien, no
   solo la de dos filas que 1b ya fijo. */
echo "--- y un acordeon PDP roto (tres <details>, ninguna abierta) EN EL CHASIS DE ECOMMERCE tambien FALLA ---\n";
$r145 = fx_tmp_root();
fx_base( $r145 );
fx(
	$r145,
	'skills/html-mockup/assets/chassis/ecommerce.html',
	str_replace(
		'</style>',
		"</style>\n<div class=\"qas\"><details><summary>Descripción</summary><p>a</p></details>"
			. '<details><summary>Envíos</summary><p>b</p></details>'
			. '<details><summary>Devoluciones</summary><p>c</p></details></div>',
		fx_mockup()
	)
);
list( , $out145 ) = fx_run_ok( $audit, $r145 );
ok( 'FAIL' === fx_row_level( $out145, array( 'RT_MOCKUP_DISCLOSURE_STATE', 'open no row' ) ), 'un acordeon PDP de tres filas que no abre ninguna FALLA igual que el FAQ corporativo de dos', fx_row_level( $out145, array( 'RT_MOCKUP_DISCLOSURE_STATE', 'open no row' ) ) );
fx_rrmdir( $r145 );

echo "--- y la forma correcta (tres <details>, solo la primera abierta) no produce fila ---\n";
$r146 = fx_tmp_root();
fx_base( $r146 );
fx(
	$r146,
	'skills/html-mockup/assets/chassis/ecommerce.html',
	str_replace(
		'</style>',
		"</style>\n<div class=\"qas\"><details open><summary>Descripción</summary><p>a</p></details>"
			. '<details><summary>Envíos</summary><p>b</p></details>'
			. '<details><summary>Devoluciones</summary><p>c</p></details></div>',
		fx_mockup()
	)
);
list( , $out146 ) = fx_run_ok( $audit, $r146 );
ok( array() === fx_lines_with( $out146, array( 'RT_MOCKUP_DISCLOSURE_STATE' ) ), 'la forma que realmente genera chassis_ecom_page_pdp() (primera de tres abierta) no produce fila', $out146 );
fx_rrmdir( $r146 );

/* ---------------------------------------------------------------------------
   RT_GALLERY_NOT_DISTINCT / RT_GALLERY_NO_MANIFEST, and the RECURSIVE glob the first of them
   needed. A gallery is many TPL-* x PERS-* cards in ONE document: RT_MOCKUP_NO_AXES asks each FILE
   whether it can express an axis and RT_PROOF_NOT_DISTINCT compares exactly two hardcoded files,
   so neither learns anything from a thirtieth card. The failure mode of a catalog is not a missing
   token — it is forty entries that turn out to be one entry with a different accent colour.

   EVERY FIXTURE HERE LIVES IN assets/gallery/, WHICH IS THE POINT. The glob RT_MOCKUP_NO_AXES used
   was `assets/*.html`, which does not descend, so a gallery could have shipped with no axis
   declaration at all and the row would have stayed green. This repo has already "confirmed green" a
   widened glob with fixtures that all sat in the one directory the narrow glob covered, which is a
   suite that cannot tell widened from narrow. Scenario r202 below is the one that can: narrow the
   walk back to a flat glob and it goes red on a named assertion.
   --------------------------------------------------------------------------- */

/* Two anchors eight axes apart (style-catalog PR 2b widened this from five) — the conforming
   case, and the same distance the real catalog holds. */
$FX_GAL_FAR = array(
	'editorial' => array( '1.500', '#FFFFFF', '1.35', 'none', 'LP-ASYMMETRIC', 'none', 'rule-divided', 'rule' ),
	'direct'    => array( '1.618', '#0E1113', '0.8', '0 0 0 1px rgba(255,106,26,.22)', 'LP-BROKEN-GRID', 'gradient', 'bare', 'none' ),
);
/* Two archetypes x two anchors: four cards, no repeated pair. */
$FX_GAL_STRIPS = array(
	array( 'tpl' => 'TPL-C-01', 'pers' => 'editorial' ),
	array( 'tpl' => 'TPL-C-01', 'pers' => 'direct' ),
	array( 'tpl' => 'TPL-E-02', 'pers' => 'editorial' ),
	array( 'tpl' => 'TPL-E-02', 'pers' => 'direct' ),
);
$FX_GAL_IMGS = array( 'hero-taller', 'card-veta' );
$FX_GAL_MAN  = fx_gallery_manifest( array( 'hero-taller' => 'Freepik free', 'card-veta' => 'Freepik free' ) );

/** Writes a gallery where the real one lives: a SUBDIRECTORY of assets/, which the old glob missed. */
function fx_gal( $root, $html, $manifest = null ) {
	fx( $root, 'skills/html-mockup/assets/gallery/index.html', $html );
	if ( null !== $manifest ) {
		fx( $root, 'skills/html-mockup/assets/gallery/_gallery-images.md', $manifest );
	}
}

/** Writes only the gallery generator stub -- presence is the whole signal, contents are never
    read. Mirrors fx_gal(), which writes only the output side of the same pair. */
function fx_gal_generator( $root ) {
	fx( $root, 'skills/html-mockup/assets/gallery/_build-gallery.php', "<?php\n// fixture stub: presence is the whole signal; contents are never read.\n" );
}

echo "--- una galeria conforme en un SUBDIRECTORIO no produce ninguna fila de galeria ---\n";
$r200 = fx_tmp_root();
fx_base( $r200 );
/* The positive control, and it carries every piece of scenery at once: the decoy CSS comment that
   quotes both anchor selectors followed by `{…}`, the `[data-anchor] .card` descendant rules, the
   inner `<section class="sec">` in every strip, and a Registers table whose header says `Slugs`.
   Each of those is a plausible mis-read; if any of them were read, this scenario turns red. */
fx_gal( $r200, fx_gallery( $FX_GAL_STRIPS, $FX_GAL_FAR, array( 'images' => $FX_GAL_IMGS ) ), $FX_GAL_MAN );
list( $code200, $out200 ) = fx_run_ok( $audit, $r200 );
ok( array() === fx_lines_with( $out200, array( 'RT_GALLERY_' ) ), 'una galeria conforme no produce fila de galeria', $out200 );
ok( array() === fx_lines_with( $out200, array( 'RT_MOCKUP_NO_AXES' ) ), 'ni fila de ejes: su :root los declara', $out200 );
ok( 0 === $code200, 'y el arbol conforme sale con codigo 0', $code200 );
fx_rrmdir( $r200 );

echo "--- EL GLOB RECURSA: una galeria sin --sp-scale en :root FALLA aunque viva en assets/gallery/ ---\n";
$r202 = fx_tmp_root();
fx_base( $r202 );
/* THE WIDENING SCENARIO, and the only one in this file that can tell a recursive walk from a flat
   glob. Everything else about this gallery conforms; the single defect is one axis missing from
   `:root`, one directory down. Under `glob(assets/*.html)` the file is never opened, no row is
   emitted, and all three assertions below go red — which is what "confirmed green" failed to do
   the last time a glob in this repo was widened. */
fx_gal(
	$r202,
	fx_gallery( $FX_GAL_STRIPS, $FX_GAL_FAR, array( 'images' => $FX_GAL_IMGS, 'root_omit' => array( '--sp-scale' ) ) ),
	$FX_GAL_MAN
);
list( , $out202 ) = fx_run_ok( $audit, $r202 );
ok( 'FAIL' === fx_row_level( $out202, array( 'RT_MOCKUP_NO_AXES' ) ), 'un asset en un subdirectorio SI se audita', fx_row_level( $out202, array( 'RT_MOCKUP_NO_AXES' ) ) );
ok( array() !== fx_lines_with( $out202, array( 'RT_MOCKUP_NO_AXES', '--sp-scale' ) ), 'y nombra el token que falta', $out202 );
/* The path, not the basename: two files called index.html in two subdirectories are one message
   otherwise, and the reader cannot tell which one to open. */
ok( array() !== fx_lines_with( $out202, array( 'RT_MOCKUP_NO_AXES', 'assets/gallery/index.html' ) ), 'y nombra la RUTA con su subdirectorio, no solo el basename', $out202 );
fx_rrmdir( $r202 );

echo "--- repetir un par TPL x PERS FALLA, nombrando el par y las dos tiras ---\n";
$r203 = fx_tmp_root();
fx_base( $r203 );
/* "Two strips sharing an anchor must declare different archetypes" is the SAME event as a repeated
   pair — sharing both coordinates is what a duplicate is — so it is one rule with one message. */
fx_gal(
	$r203,
	fx_gallery(
		array(
			array( 'tpl' => 'TPL-C-01', 'pers' => 'editorial' ),
			array( 'tpl' => 'TPL-C-01', 'pers' => 'direct' ),
			array( 'tpl' => 'TPL-C-01', 'pers' => 'editorial' ),
		),
		$FX_GAL_FAR,
		array( 'images' => $FX_GAL_IMGS )
	),
	$FX_GAL_MAN
);
list( , $out203 ) = fx_run_ok( $audit, $r203 );
ok( 'FAIL' === fx_row_level( $out203, array( 'RT_GALLERY_NOT_DISTINCT' ) ), 'un par repetido FALLA', fx_row_level( $out203, array( 'RT_GALLERY_NOT_DISTINCT' ) ) );
ok( array() !== fx_lines_with( $out203, array( 'RT_GALLERY_NOT_DISTINCT', 'renders the pair TPL-C-01' ) ), 'y nombra el par repetido', $out203 );
ok( array() !== fx_lines_with( $out203, array( 'RT_GALLERY_NOT_DISTINCT', 'twice, at strips #1 and #3' ) ), 'y dice QUE DOS tiras son, no solo que hay una repeticion', $out203 );
fx_rrmdir( $r203 );

echo "--- dos tiras del MISMO arquetipo separadas por solo TRES ejes FALLAN, nombrando los que coinciden ---\n";
$r204 = fx_tmp_root();
fx_base( $r204 );
/* scale, ground and composition differ; density and elevation are identical, and accent is forced
   to match too (same trick as RT_PROOF_NOT_DISTINCT's r93) so 3-of-8 shared still clears the
   widened >2 threshold; chassis/ornament keep differing. Same shape RT_PROOF_NOT_DISTINCT reports,
   through the same axis_matches() list. */
$fx_gal_near = array(
	'editorial' => array( '1.500', '#FFFFFF', '1.0', 'none', 'LP-ASYMMETRIC', 'none', 'rule-divided', 'rule' ),
	'direct'    => array( '1.618', '#0E1113', '1.0', 'none', 'LP-BROKEN-GRID', 'none', 'bare', 'none' ),
);
fx_gal(
	$r204,
	fx_gallery(
		array(
			array( 'tpl' => 'TPL-C-01', 'pers' => 'editorial' ),
			array( 'tpl' => 'TPL-C-01', 'pers' => 'direct' ),
		),
		$fx_gal_near,
		array( 'images' => $FX_GAL_IMGS )
	),
	$FX_GAL_MAN
);
list( , $out204 ) = fx_run_ok( $audit, $r204 );
ok( 'FAIL' === fx_row_level( $out204, array( 'RT_GALLERY_NOT_DISTINCT' ) ), 'tres ejes comparten (density, elevation, accent) de ocho entre dos tiras del mismo arquetipo FALLA', fx_row_level( $out204, array( 'RT_GALLERY_NOT_DISTINCT' ) ) );
ok( array() !== fx_lines_with( $out204, array( 'RT_GALLERY_NOT_DISTINCT', 'only 5 of 8 axes' ) ), 'y dice CUANTOS ejes los separan', $out204 );
ok( array() !== fx_lines_with( $out204, array( 'RT_GALLERY_NOT_DISTINCT', 'density (--sp-scale: both `1.0`)' ) ), 'y nombra el eje density con su valor compartido', $out204 );
ok( array() !== fx_lines_with( $out204, array( 'RT_GALLERY_NOT_DISTINCT', 'elevation (--elev-rest: both `none`)' ) ), 'y nombra tambien el eje elevation', $out204 );
ok( array() !== fx_lines_with( $out204, array( 'RT_GALLERY_NOT_DISTINCT', 'share an archetype' ) ), 'y dice que lo que comparten es el arquetipo', $out204 );
fx_rrmdir( $r204 );

echo "--- cuatro ejes YA bastan tambien en la galeria: no hay fila ---\n";
$r205 = fx_tmp_root();
fx_base( $r205 );
/* Only elevation matches. Four is the bar, the same one RT_STYLE_TOO_SIMILAR holds, and the real
   catalog sits exactly on it: PERS-MATTER and PERS-INSTITUTIONAL both stand at density `standard`,
   so a threshold of five would fail the shipped gallery. Raise the bar and this scenario goes red;
   lower it and r204 above goes red. */
$fx_gal_four = array(
	'editorial' => array( '1.500', '#FFFFFF', '1.35', 'none', 'LP-ASYMMETRIC', 'none', 'rule-divided', 'rule' ),
	'direct'    => array( '1.618', '#0E1113', '0.8', 'none', 'LP-BROKEN-GRID', 'gradient', 'bare', 'none' ),
);
fx_gal(
	$r205,
	fx_gallery(
		array(
			array( 'tpl' => 'TPL-C-01', 'pers' => 'editorial' ),
			array( 'tpl' => 'TPL-C-01', 'pers' => 'direct' ),
		),
		$fx_gal_four,
		array( 'images' => $FX_GAL_IMGS )
	),
	$FX_GAL_MAN
);
list( $code205, $out205 ) = fx_run_ok( $audit, $r205 );
ok( array() === fx_lines_with( $out205, array( 'RT_GALLERY_NOT_DISTINCT' ) ), 'cuatro ejes distintos no producen fila', $out205 );
ok( 0 === $code205, 'y el arbol sale con codigo 0', $code205 );
fx_rrmdir( $r205 );

echo "--- dos ARQUETIPOS distintos no se comparan entre si, ni con anclas identicas ---\n";
$r206 = fx_tmp_root();
fx_base( $r206 );
/* The scoping half of the rule. Two cards of different archetypes are already separated by their
   section inventory, which is the one axis the five perceptual ones do not carry — so comparing
   them would demand a distance the taxonomy never promised. Both anchors here are IDENTICAL on all
   five axes: a check that compared every pair would report this, and it must not. */
$fx_gal_same = array(
	'editorial' => array( '1.500', '#FFFFFF', '1.35', 'none', 'LP-ASYMMETRIC', 'none', 'rule-divided', 'rule' ),
	'direct'    => array( '1.500', '#FFFFFF', '1.35', 'none', 'LP-ASYMMETRIC', 'none', 'rule-divided', 'rule' ),
);
fx_gal(
	$r206,
	fx_gallery(
		array(
			array( 'tpl' => 'TPL-C-01', 'pers' => 'editorial' ),
			array( 'tpl' => 'TPL-E-02', 'pers' => 'direct' ),
		),
		$fx_gal_same,
		array( 'images' => $FX_GAL_IMGS )
	),
	$FX_GAL_MAN
);
list( $code206, $out206 ) = fx_run_ok( $audit, $r206 );
ok( array() === fx_lines_with( $out206, array( 'RT_GALLERY_NOT_DISTINCT' ) ), 'arquetipos distintos no se comparan por ejes', $out206 );
ok( 0 === $code206, 'y el arbol sale con codigo 0', $code206 );
fx_rrmdir( $r206 );

echo "--- una tira sin data-pers FALLA, nombrando el atributo y el numero de tira ---\n";
$r207 = fx_tmp_root();
fx_base( $r207 );
/* Sin este arm, una tira a la que se le cae un atributo se queda FUERA de las comparaciones y el
   resto de reglas la dan por buena: el hueco silencioso, no el ruidoso. */
fx_gal(
	$r207,
	fx_gallery(
		array(
			array( 'tpl' => 'TPL-C-01' ),
			array( 'tpl' => 'TPL-C-01', 'pers' => 'direct' ),
		),
		$FX_GAL_FAR,
		array( 'images' => $FX_GAL_IMGS )
	),
	$FX_GAL_MAN
);
list( , $out207 ) = fx_run_ok( $audit, $r207 );
ok( 'FAIL' === fx_row_level( $out207, array( 'RT_GALLERY_NOT_DISTINCT' ) ), 'una tira sin data-pers FALLA', fx_row_level( $out207, array( 'RT_GALLERY_NOT_DISTINCT' ) ) );
ok( array() !== fx_lines_with( $out207, array( 'RT_GALLERY_NOT_DISTINCT', 'strip #1 declares no data-pers' ) ), 'y nombra el atributo y la tira', $out207 );
fx_rrmdir( $r207 );

echo "--- un ancla que solo aparece en un COMENTARIO no cuenta como declarada ---\n";
$r208 = fx_tmp_root();
fx_base( $r208 );
/* THE COMMENT TRAP, from the side that matters. The decoy comment fx_gallery() writes quotes
   [data-anchor="direct"]{…} in prose; the real block for `direct` is omitted here. Taking every
   matching block is what stops a three-byte comment body from SHADOWING a real declaration — and it
   is also what would let a deleted block pass as declared, because an empty signature against a
   real one reads as five axes apart. So an anchor whose block declares not one of the five axes is
   the same finding as an anchor with no block at all, and this is the fixture that says so. */
fx_gal(
	$r208,
	fx_gallery(
		array(
			array( 'tpl' => 'TPL-C-01', 'pers' => 'editorial' ),
			array( 'tpl' => 'TPL-C-01', 'pers' => 'direct' ),
		),
		array(
			'editorial' => $FX_GAL_FAR['editorial'],
			'direct'    => null,
		),
		array( 'images' => $FX_GAL_IMGS )
	),
	$FX_GAL_MAN
);
list( , $out208 ) = fx_run_ok( $audit, $r208 );
ok( 'FAIL' === fx_row_level( $out208, array( 'RT_GALLERY_NOT_DISTINCT' ) ), 'un ancla sin bloque real FALLA', fx_row_level( $out208, array( 'RT_GALLERY_NOT_DISTINCT' ) ) );
ok( array() !== fx_lines_with( $out208, array( 'RT_GALLERY_NOT_DISTINCT', 'no [data-anchor="direct"] block declares its axes' ) ), 'y nombra el ancla que el CSS nunca pinta', $out208 );
ok( array() === fx_lines_with( $out208, array( 'RT_GALLERY_NOT_DISTINCT', 'editorial"] block' ) ), 'y no acusa al ancla que si esta declarada', $out208 );
fx_rrmdir( $r208 );

echo "--- un asset de galeria SIN tiras FALLA: un gate no puede darse verde sobre un conjunto vacio ---\n";
$r209 = fx_tmp_root();
fx_base( $r209 );
/* Sin este arm la ruta entera es opcional: borra las tiras y las cuatro reglas de arriba recorren
   una lista vacia y no dicen nada. Es exactamente la forma de los siete checks que esta rama
   produjo pasando sin comprobar nada. */
fx_gal( $r209, fx_mockup() );
list( , $out209 ) = fx_run_ok( $audit, $r209 );
ok( 'FAIL' === fx_row_level( $out209, array( 'RT_GALLERY_NOT_DISTINCT' ) ), 'una galeria sin tiras FALLA', fx_row_level( $out209, array( 'RT_GALLERY_NOT_DISTINCT' ) ) );
ok( array() !== fx_lines_with( $out209, array( 'RT_GALLERY_NOT_DISTINCT', 'renders no <section class="strip">' ) ), 'y dice que lo que falta son las tiras', $out209 );
fx_rrmdir( $r209 );

echo "--- una galeria FUERA de gallery/ tambien se audita: la deteccion tiene dos brazos ---\n";
$r210 = fx_tmp_root();
fx_base( $r210 );
/* El brazo por CONTENIDO. Con deteccion solo por ruta, renombrar el directorio apaga las dos filas
   sin tocar una sola regla; con deteccion solo por contenido, borrar los dos data-* hace lo mismo.
   Los dos brazos existen para que ninguna de esas dos ediciones vuelva verde el gate quitandole el
   sujeto en vez de arreglando algo. */
fx(
	$r210,
	'skills/html-mockup/assets/catalog.html',
	fx_gallery(
		array(
			array( 'tpl' => 'TPL-C-01', 'pers' => 'editorial' ),
			array( 'tpl' => 'TPL-C-01', 'pers' => 'editorial' ),
		),
		$FX_GAL_FAR
	)
);
list( , $out210 ) = fx_run_ok( $audit, $r210 );
ok( 'FAIL' === fx_row_level( $out210, array( 'RT_GALLERY_NOT_DISTINCT' ) ), 'un fichero con tiras fuera de gallery/ tambien se audita', fx_row_level( $out210, array( 'RT_GALLERY_NOT_DISTINCT' ) ) );
ok( array() !== fx_lines_with( $out210, array( 'RT_GALLERY_NOT_DISTINCT', 'assets/catalog.html' ) ), 'y la fila nombra ese fichero', $out210 );
fx_rrmdir( $r210 );

echo "--- imagenes sin manifiesto al lado FALLAN, contandolas ---\n";
$r211 = fx_tmp_root();
fx_base( $r211 );
fx_gal( $r211, fx_gallery( $FX_GAL_STRIPS, $FX_GAL_FAR, array( 'images' => $FX_GAL_IMGS ) ) );
list( , $out211 ) = fx_run_ok( $audit, $r211 );
ok( 'FAIL' === fx_row_level( $out211, array( 'RT_GALLERY_NO_MANIFEST' ) ), 'sin _gallery-images.md al lado, FALLA', fx_row_level( $out211, array( 'RT_GALLERY_NO_MANIFEST' ) ) );
ok( array() !== fx_lines_with( $out211, array( 'RT_GALLERY_NO_MANIFEST', 'renders 2 image slug(s) and no _gallery-images.md' ) ), 'y cuenta las imagenes que quedan sin contrato', $out211 );
/* El POR QUE tiene que viajar en el mensaje: es_photo() resuelve un slug de adjunto, no una URL. */
ok( array() !== fx_lines_with( $out211, array( 'RT_GALLERY_NO_MANIFEST', 'ATTACHMENT SLUG' ) ), 'y dice por que importa: es_photo resuelve un slug', $out211 );
fx_rrmdir( $r211 );

echo "--- un manifiesto SIN tabla de slugs FALLA: `Slugs` en plural no es la columna `Slug` ---\n";
$r212 = fx_tmp_root();
fx_base( $r212 );
/* La tabla se elige por una celda de cabecera que diga exactamente `Slug`. El manifiesto real lleva
   una tabla "Registers" cuya cabecera dice `Slugs`, y un parser por subcadena — o por posicion de
   columna — leeria tres celdas de prosa como filas de imagen. */
fx_gal(
	$r212,
	fx_gallery( $FX_GAL_STRIPS, $FX_GAL_FAR, array( 'images' => $FX_GAL_IMGS ) ),
	"# Manifiesto sin tabla de imagenes\n\n| Register | Slugs | What it says |\n|---|---|---|\n| Workshop | hero-taller card-veta | fixture |\n"
);
list( , $out212 ) = fx_run_ok( $audit, $r212 );
ok( 'FAIL' === fx_row_level( $out212, array( 'RT_GALLERY_NO_MANIFEST' ) ), 'un manifiesto sin columna Slug FALLA', fx_row_level( $out212, array( 'RT_GALLERY_NO_MANIFEST' ) ) );
ok( array() !== fx_lines_with( $out212, array( 'RT_GALLERY_NO_MANIFEST', 'carries no table with a `Slug` column' ) ), 'y dice que lo que falta es la tabla, no una fila', $out212 );
fx_rrmdir( $r212 );

echo "--- una imagen sin fila en el manifiesto FALLA, nombrandola y sin acusar a las que si estan ---\n";
$r213 = fx_tmp_root();
fx_base( $r213 );
fx_gal(
	$r213,
	fx_gallery( $FX_GAL_STRIPS, $FX_GAL_FAR, array( 'images' => $FX_GAL_IMGS ) ),
	fx_gallery_manifest( array( 'hero-taller' => 'Freepik free' ) )
);
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
$fx_man214 = fx_gallery_manifest(
	array(
		'hero-taller' => 'Freepik free',
		'card-veta'   => 'Freepik free',
		''            => 'Freepik free',
	)
);
fx_gal( $r214, fx_gallery( $FX_GAL_STRIPS, $FX_GAL_FAR, array( 'images' => $FX_GAL_IMGS ) ), $fx_man214 );
list( , $out214 ) = fx_run_ok( $audit, $r214 );
ok( 'FAIL' === fx_row_level( $out214, array( 'RT_GALLERY_NO_MANIFEST', 'empty Slug cell' ) ), 'una fila sin slug FALLA', fx_row_level( $out214, array( 'RT_GALLERY_NO_MANIFEST', 'empty Slug cell' ) ) );
ok(
	array() !== fx_lines_with( $out214, array( 'RT_GALLERY_NO_MANIFEST', 'line ' . fx_line_of( $fx_man214, '| | card 4:3' ) ) ),
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
$fx_man215 = fx_gallery_manifest( array( 'hero-taller' => 'Freepik free', 'card-veta' => '' ) );
fx_gal( $r215, fx_gallery( $FX_GAL_STRIPS, $FX_GAL_FAR, array( 'images' => $FX_GAL_IMGS ) ), $fx_man215 );
list( , $out215 ) = fx_run_ok( $audit, $r215 );
ok( 'FAIL' === fx_row_level( $out215, array( 'RT_GALLERY_NO_MANIFEST' ) ), 'una fila sin licencia FALLA', fx_row_level( $out215, array( 'RT_GALLERY_NO_MANIFEST' ) ) );
ok(
	/* The needle is the BACKTICKED cell: `card-veta` also appears bare in the Registers table above,
	   and pinning on the plain slug would report that line instead. */
	array() !== fx_lines_with( $out215, array( 'RT_GALLERY_NO_MANIFEST', '1 row(s) with no licence — `card-veta` (line ' . fx_line_of( $fx_man215, '| `card-veta` |' ) . ')' ) ),
	'y nombra el slug y la linea, y cuenta UNA sola',
	$out215
);
ok( array() === fx_lines_with( $out215, array( 'RT_GALLERY_NO_MANIFEST', 'no Licence column at all' ) ), 'y no dice que falte la columna, porque esta', $out215 );
fx_rrmdir( $r215 );

echo "--- un manifiesto sin columna Licence FALLA una vez, diciendo que la columna entera falta ---\n";
$r216 = fx_tmp_root();
fx_base( $r216 );
/* Dos causas distintas, dos mensajes distintos: una celda vacia se arregla escribiendo en ella, una
   columna ausente se arregla cambiando la tabla. Sin la frase final el lector busca una celda que
   no existe. */
fx_gal(
	$r216,
	fx_gallery( $FX_GAL_STRIPS, $FX_GAL_FAR, array( 'images' => $FX_GAL_IMGS ) ),
	fx_gallery_manifest( array( 'hero-taller' => 'Freepik free', 'card-veta' => 'Freepik free' ), false )
);
list( , $out216 ) = fx_run_ok( $audit, $r216 );
ok( 'FAIL' === fx_row_level( $out216, array( 'RT_GALLERY_NO_MANIFEST' ) ), 'sin columna Licence, FALLA', fx_row_level( $out216, array( 'RT_GALLERY_NO_MANIFEST' ) ) );
ok( array() !== fx_lines_with( $out216, array( 'RT_GALLERY_NO_MANIFEST', '2 row(s) with no licence' ) ), 'y las cuenta todas', $out216 );
ok( array() !== fx_lines_with( $out216, array( 'RT_GALLERY_NO_MANIFEST', 'carries no Licence column at all' ) ), 'y dice que la causa es la columna, no la celda', $out216 );
fx_rrmdir( $r216 );

/* ---------------------------------------------------------------------------
   RT_GALLERY_ONE_SHOOT — the register table says what the set is ABOUT; this says how many times a
   shutter was pressed. The gallery shipped seven of fourteen images from one session and every
   register read as healthy, which is the gap these scenarios pin.
   --------------------------------------------------------------------------- */

echo "--- dos filas de UNA sola sesion pasan del tope y FALLAN, con la aritmetica en el mensaje ---\n";
$r217 = fx_tmp_root();
fx_base( $r217 );
/* Los dos ids caen en el mismo cubo de 1.000 (50621286 y 50621783 son exactamente el rango que el
   lote real ocupaba), asi que las dos filas son una sola sesion: 2 de 2 contra un tope de
   ceil(2/4) = 1. */
$fx_man217 = fx_gallery_manifest(
	array( 'hero-taller' => 'Freepik free', 'card-veta' => 'Freepik free' ),
	true,
	array( 'freepik' => array( 'hero-taller' => 50621286, 'card-veta' => 50621783 ) )
);
fx_gal( $r217, fx_gallery( $FX_GAL_STRIPS, $FX_GAL_FAR, array( 'images' => $FX_GAL_IMGS ) ), $fx_man217 );
list( , $out217 ) = fx_run_ok( $audit, $r217 );
ok( 'FAIL' === fx_row_level( $out217, array( 'RT_GALLERY_ONE_SHOOT' ) ), 'una sola sesion por encima del tope FALLA', fx_row_level( $out217, array( 'RT_GALLERY_ONE_SHOOT' ) ) );
ok( array() !== fx_lines_with( $out217, array( 'RT_GALLERY_ONE_SHOOT', 'draws 2 of its 2 images from the one shoot `fp-50621`' ) ), 'y nombra la sesion y cuenta cuantas son', $out217 );
ok( array() !== fx_lines_with( $out217, array( 'RT_GALLERY_ONE_SHOOT', '`hero-taller`, `card-veta`' ) ), 'y nombra CUALES, no solo cuantas', $out217 );
/* El tope tiene que viajar con su cuenta: un mensaje que solo diga "demasiadas" deja al lector sin
   saber cuantas sobran ni de donde sale el numero. */
ok( array() !== fx_lines_with( $out217, array( 'RT_GALLERY_ONE_SHOOT', 'declares 4 registers, so the cap is ceil(2/4) = 1' ) ), 'y muestra la aritmetica y su divisor', $out217 );
/* Y tiene que decir lo que NO ve, o la fila se lee como una garantia de variedad que no es. */
ok( array() !== fx_lines_with( $out217, array( 'RT_GALLERY_ONE_SHOOT', 'WHAT THIS ROW CANNOT SEE' ) ), 'y declara su propio punto ciego', $out217 );
fx_rrmdir( $r217 );

echo "--- el MISMO manifiesto con un solo registro NO produce fila: el divisor se lee, no se fija ---\n";
$r218 = fx_tmp_root();
fx_base( $r218 );
/* Este es el escenario que mata la version hardcodeada del tope. Las filas y sus ids son identicos
   al 217; lo unico que cambia es cuantos registros declara la tabla, y ceil(2/1) = 2 ya admite las
   dos. Si alguien sustituye gallery_register_count() por un 4 literal, 217 sigue verde y este se
   pone rojo. */
$fx_man218 = fx_gallery_manifest(
	array( 'hero-taller' => 'Freepik free', 'card-veta' => 'Freepik free' ),
	true,
	array( 'registers' => 1, 'freepik' => array( 'hero-taller' => 50621286, 'card-veta' => 50621783 ) )
);
fx_gal( $r218, fx_gallery( $FX_GAL_STRIPS, $FX_GAL_FAR, array( 'images' => $FX_GAL_IMGS ) ), $fx_man218 );
list( , $out218 ) = fx_run_ok( $audit, $r218 );
ok( array() === fx_lines_with( $out218, array( 'RT_GALLERY_ONE_SHOOT' ) ), 'mas registros declarados aflojan el tope y no hay fila', $out218 );
fx_rrmdir( $r218 );

echo "--- sin tabla de Registers el tope no tiene divisor y FALLA ---\n";
$r219 = fx_tmp_root();
fx_base( $r219 );
/* Un conjunto que no declara estructura de registros no ha afirmado nada sobre cuantas miradas
   distintas lleva, y una cota medida contra ninguna afirmacion es una fila pasando por encima de
   su propio sujeto ausente — el mismo argumento que RT_GALLERY_NOT_DISTINCT hace con las tiras. */
fx_gal(
	$r219,
	fx_gallery( $FX_GAL_STRIPS, $FX_GAL_FAR, array( 'images' => $FX_GAL_IMGS ) ),
	fx_gallery_manifest( array( 'hero-taller' => 'Freepik free', 'card-veta' => 'Freepik free' ), true, array( 'registers' => 0 ) )
);
list( , $out219 ) = fx_run_ok( $audit, $r219 );
ok( 'FAIL' === fx_row_level( $out219, array( 'RT_GALLERY_ONE_SHOOT' ) ), 'sin tabla de registros, FALLA', fx_row_level( $out219, array( 'RT_GALLERY_ONE_SHOOT' ) ) );
ok( array() !== fx_lines_with( $out219, array( 'RT_GALLERY_ONE_SHOOT', 'declares no Registers table with rows' ) ), 'y dice que lo que falta es el divisor', $out219 );
fx_rrmdir( $r219 );

echo "--- sin columna Shoot FALLA: nada registra que imagenes vienen de una misma sesion ---\n";
$r220 = fx_tmp_root();
fx_base( $r220 );
fx_gal(
	$r220,
	fx_gallery( $FX_GAL_STRIPS, $FX_GAL_FAR, array( 'images' => $FX_GAL_IMGS ) ),
	fx_gallery_manifest( array( 'hero-taller' => 'Freepik free', 'card-veta' => 'Freepik free' ), true, array( 'shoot_col' => false ) )
);
list( , $out220 ) = fx_run_ok( $audit, $r220 );
ok( 'FAIL' === fx_row_level( $out220, array( 'RT_GALLERY_ONE_SHOOT' ) ), 'sin columna Shoot, FALLA', fx_row_level( $out220, array( 'RT_GALLERY_ONE_SHOOT' ) ) );
ok( array() !== fx_lines_with( $out220, array( 'RT_GALLERY_ONE_SHOOT', 'carries no Shoot column' ) ), 'y dice que la causa es la columna', $out220 );
ok( array() === fx_lines_with( $out220, array( 'RT_GALLERY_ONE_SHOOT', 'no Freepik column' ) ), 'y no acumula la otra causa de columna', $out220 );
fx_rrmdir( $r220 );

echo "--- con Shoot y sin Freepik FALLA: una celda que nadie puede contrastar es una etiqueta ---\n";
$r221 = fx_tmp_root();
fx_base( $r221 );
/* Dos causas distintas, dos mensajes distintos, igual que la pareja celda-vacia / columna-ausente
   de RT_GALLERY_NO_MANIFEST: sin Freepik la celda Shoot no se deriva de nada y vuelve a ser prosa. */
fx_gal(
	$r221,
	fx_gallery( $FX_GAL_STRIPS, $FX_GAL_FAR, array( 'images' => $FX_GAL_IMGS ) ),
	fx_gallery_manifest( array( 'hero-taller' => 'Freepik free', 'card-veta' => 'Freepik free' ), true, array( 'freepik_col' => false ) )
);
list( , $out221 ) = fx_run_ok( $audit, $r221 );
ok( 'FAIL' === fx_row_level( $out221, array( 'RT_GALLERY_ONE_SHOOT' ) ), 'con Shoot y sin Freepik, FALLA', fx_row_level( $out221, array( 'RT_GALLERY_ONE_SHOOT' ) ) );
ok( array() !== fx_lines_with( $out221, array( 'RT_GALLERY_ONE_SHOOT', 'no Freepik column to derive it from' ) ), 'y dice que lo que falta es la fuente de la derivacion', $out221 );
ok( array() === fx_lines_with( $out221, array( 'RT_GALLERY_ONE_SHOOT', 'carries no Shoot column' ) ), 'y no acusa de que falte Shoot, porque esta', $out221 );
fx_rrmdir( $r221 );

echo "--- una celda Shoot que no cuadra con su propio id FALLA, y no acusa a la que si cuadra ---\n";
$r222 = fx_tmp_root();
fx_base( $r222 );
/* Este es el arm que impide arreglar la fila anterior renombrando: si el tope se midiera sobre
   etiquetas escritas a mano, bastaria teclear otra para poner el gate verde. La celda se RE-DERIVA
   del id, y el id no se puede retocar sin falsear tambien la fuente que la celda Licence sostiene. */
$fx_man222 = fx_gallery_manifest(
	array( 'hero-taller' => 'Freepik free', 'card-veta' => 'Freepik free' ),
	true,
	array( 'freepik' => array( 'hero-taller' => 50621286 ), 'shoot' => array( 'hero-taller' => 'fp-99999' ) )
);
fx_gal( $r222, fx_gallery( $FX_GAL_STRIPS, $FX_GAL_FAR, array( 'images' => $FX_GAL_IMGS ) ), $fx_man222 );
list( , $out222 ) = fx_run_ok( $audit, $r222 );
ok( 'FAIL' === fx_row_level( $out222, array( 'RT_GALLERY_ONE_SHOOT' ) ), 'una celda Shoot que miente FALLA', fx_row_level( $out222, array( 'RT_GALLERY_ONE_SHOOT' ) ) );
ok(
	array() !== fx_lines_with( $out222, array( 'RT_GALLERY_ONE_SHOOT', '`hero-taller` says `fp-99999` for Freepik `50621286` which derives `fp-50621` (line ' . fx_line_of( $fx_man222, '| `hero-taller` |' ) . ')' ) ),
	'y nombra la fila, lo que dice, lo que deriva y su linea',
	$out222
);
ok( array() === fx_lines_with( $out222, array( 'RT_GALLERY_ONE_SHOOT', '`card-veta` says' ) ), 'y no acusa a la fila cuya celda si deriva', $out222 );
fx_rrmdir( $r222 );
echo "--- N es el conjunto ENTERO, no el tamano de la sesion acusada ---\n";
$r223 = fx_tmp_root();
fx_base( $r223 );
/* Tres filas de una sesion y una cuarta de otra: N = 4, R = 4, tope = 1, y solo la primera sesion
   se pasa. El escenario existe por un mutante que sobrevivio a los otros seis: cambiar `$gal_n +=`
   por `$gal_n =` deja N valiendo el tamano de la ULTIMA sesion contada, y en cualquier fixture
   donde todas las sesiones valgan 1 — o donde solo haya una — las dos versiones dan el mismo
   numero. Aqui no: el mutante informa "3 of its 1 images" y un tope de ceil(1/4), que es un
   mensaje sin sentido y una cota equivocada. */
$fx_man223 = fx_gallery_manifest(
	array(
		'hero-taller'  => 'Freepik free',
		'card-veta'    => 'Freepik free',
		'card-mueble'  => 'Freepik free',
		'card-detalle' => 'Freepik free',
	),
	true,
	array(
		'freepik' => array(
			'hero-taller'  => 50621286,
			'card-veta'    => 50621483,
			'card-mueble'  => 50621783,
			'card-detalle' => 41000077,
		),
	)
);
fx_gal( $r223, fx_gallery( $FX_GAL_STRIPS, $FX_GAL_FAR, array( 'images' => $FX_GAL_IMGS ) ), $fx_man223 );
list( , $out223 ) = fx_run_ok( $audit, $r223 );
ok( 'FAIL' === fx_row_level( $out223, array( 'RT_GALLERY_ONE_SHOOT' ) ), 'tres de cuatro en una sesion FALLA', fx_row_level( $out223, array( 'RT_GALLERY_ONE_SHOOT' ) ) );
ok( array() !== fx_lines_with( $out223, array( 'RT_GALLERY_ONE_SHOOT', 'draws 3 of its 4 images from the one shoot `fp-50621`' ) ), 'y el denominador es el conjunto entero, no la sesion', $out223 );
ok( array() !== fx_lines_with( $out223, array( 'RT_GALLERY_ONE_SHOOT', 'the cap is ceil(4/4) = 1' ) ), 'y el tope se calcula sobre ese mismo N', $out223 );
ok( array() === fx_lines_with( $out223, array( 'RT_GALLERY_ONE_SHOOT', '`fp-41000`' ) ), 'y la sesion que no se pasa no se nombra', $out223 );
fx_rrmdir( $r223 );

/* ---------------------------------------------------------------------------
   RT_GALLERY_NOT_BUILT -- the gallery output (skills/html-mockup/assets/gallery/index.html) is
   generated and, after this change, untracked: a fresh clone has none until the generator runs.
   Discovery is by glob (html_assets_deep()), so a MISSING file emits nothing on its own -- the
   same structural gap $PROOF_MOCKUPS already closed with hardcoded paths. Gated on the generator's
   own presence so it never fires inside fx_gal()'s fixtures, which write index.html but never
   _build-gallery.php.
   --------------------------------------------------------------------------- */

echo "--- the generator present without a built index.html FAILs, naming the exact fix command ---\n";
$r224 = fx_tmp_root();
fx_base( $r224 );
fx_gal_generator( $r224 );
list( $code224, $out224 ) = fx_run_ok( $audit, $r224 );
ok( 'FAIL' === fx_row_level( $out224, array( 'RT_GALLERY_NOT_BUILT' ) ), 'generator present, no index.html built -> FAIL', fx_row_level( $out224, array( 'RT_GALLERY_NOT_BUILT' ) ) );
ok( array() !== fx_lines_with( $out224, array( 'RT_GALLERY_NOT_BUILT', 'php skills/html-mockup/assets/gallery/_build-gallery.php' ) ), 'and the message names the exact fix command', $out224 );
ok( 1 === $code224, 'and the tree exits code 1', $code224 );
fx_rrmdir( $r224 );

echo "--- the generator present WITH a built index.html stays silent -- a built tree is not accused ---\n";
$r225 = fx_tmp_root();
fx_base( $r225 );
fx_gal_generator( $r225 );
fx_gal( $r225, fx_gallery( $FX_GAL_STRIPS, $FX_GAL_FAR, array( 'images' => $FX_GAL_IMGS ) ), $FX_GAL_MAN );
list( $code225, $out225 ) = fx_run_ok( $audit, $r225 );
ok( array() === fx_lines_with( $out225, array( 'RT_GALLERY_NOT_BUILT' ) ), 'generator + built index.html -> no RT_GALLERY_NOT_BUILT row', $out225 );
fx_rrmdir( $r225 );

echo "--- no generator at all never produces RT_GALLERY_NOT_BUILT -- the gate never fires in a bare fixture root ---\n";
$r226 = fx_tmp_root();
fx_base( $r226 );
list( $code226, $out226 ) = fx_run_ok( $audit, $r226 );
ok( array() === fx_lines_with( $out226, array( 'RT_GALLERY_NOT_BUILT' ) ), 'no generator, no index.html -> still no row: the gate is conditioned on the generator', $out226 );
fx_rrmdir( $r226 );

/* ---------------------------------------------------------------------------
   RT_GALLERY_STALE -- the half RT_GALLERY_NOT_BUILT cannot see.

   Its sibling above answers "was the gallery ever built?". Nothing answered "was it built from
   THESE inputs?", and since index.html became untracked that question has no other asker: a
   generated file nobody can diff against its own history is invisible to every other gate here.
   Edit a TPL-*.md or the image manifest, never regenerate, and the whole chain stays green over
   output that no longer matches what produced it.

   The mechanism is an input fingerprint the generator EMBEDS in its own output and the audit
   RECOMPUTES from disk. Both sides read it out of ONE file --
   skills/html-mockup/assets/gallery/_gallery-fingerprint.php -- because a fingerprint written
   twice is two fingerprints, and framework-audit/SKILL.md already names which of the two loses.
   The fixtures below copy that same file rather than restating its rules, for the same reason.

   Gated on that file's PRESENCE IN THE AUDITED ROOT, exactly as its sibling gates on the
   generator's. No fixture except the four below writes it, so the row cannot fire inside a temp
   root that never opted in -- asserted, not assumed, by the last scenario.
   --------------------------------------------------------------------------- */

$fx_gal_fp_lib = $root_dir . '/skills/html-mockup/assets/gallery/_gallery-fingerprint.php';

echo "--- the input fingerprint has ONE definition, and both the generator and the audit read it there ---\n";
ok( is_file( $fx_gal_fp_lib ), 'the shared fingerprint definition exists at the single path both sides require', $fx_gal_fp_lib );
if ( is_file( $fx_gal_fp_lib ) ) {
	require_once $fx_gal_fp_lib;
}
ok( function_exists( 'nm_gallery_input_digest' ), 'and it exposes the digest the two sides compare', 'nm_gallery_input_digest() is not defined' );
ok( function_exists( 'nm_gallery_fingerprint_line' ), 'and the exact line the generator stamps into its output', 'nm_gallery_fingerprint_line() is not defined' );

/** Copies the REAL fingerprint definition into a fixture root -- never a re-typed copy of its
    rules, which is the duplicate this whole row exists to forbid. Returns false when the
    definition does not exist yet, so a red run reports a missing row rather than a fatal. */
function fx_gal_fingerprint( $root ) {
	global $fx_gal_fp_lib;
	if ( ! is_file( $fx_gal_fp_lib ) ) {
		return false;
	}
	fx( $root, 'skills/html-mockup/assets/gallery/_gallery-fingerprint.php', file_get_contents( $fx_gal_fp_lib ) );
	return true;
}

echo "--- output carrying NO fingerprint at all FAILs: nothing can vouch for it ---\n";
$r227 = fx_tmp_root();
fx_base( $r227 );
fx_gal_generator( $r227 );
fx_gal_fingerprint( $r227 );
fx_gal( $r227, fx_gallery( $FX_GAL_STRIPS, $FX_GAL_FAR, array( 'images' => $FX_GAL_IMGS ) ), $FX_GAL_MAN );
list( $code227, $out227 ) = fx_run_ok( $audit, $r227 );
ok( 'FAIL' === fx_row_level( $out227, array( 'RT_GALLERY_STALE' ) ), 'index.html with no fingerprint line -> FAIL', fx_row_level( $out227, array( 'RT_GALLERY_STALE' ) ) );
ok( array() !== fx_lines_with( $out227, array( 'RT_GALLERY_STALE', 'records no input fingerprint' ) ), 'and the row says WHICH of the two causes it is: no fingerprint, not a mismatched one', $out227 );
ok( array() !== fx_lines_with( $out227, array( 'RT_GALLERY_STALE', 'php skills/html-mockup/assets/gallery/_build-gallery.php' ) ), 'and it names the exact remedy -- a FAIL that does not is noise', $out227 );
ok( 1 === $code227, 'and the tree exits code 1', $code227 );
fx_rrmdir( $r227 );

echo "--- output whose fingerprint does not match the inputs on disk FAILs ---\n";
$r228 = fx_tmp_root();
fx_base( $r228 );
fx_gal_generator( $r228 );
fx_gal_fingerprint( $r228 );
/* A syntactically perfect fingerprint of a DIFFERENT input set -- the shape a real stale gallery
   has, and the one a "does the line exist?" check would wave through. */
$fx_wrong_fp = str_repeat( 'a', 64 );
fx_gal( $r228, '<!-- gallery-input-fingerprint: sha256:' . $fx_wrong_fp . " -->\n" . fx_gallery( $FX_GAL_STRIPS, $FX_GAL_FAR, array( 'images' => $FX_GAL_IMGS ) ), $FX_GAL_MAN );
list( $code228, $out228 ) = fx_run_ok( $audit, $r228 );
ok( 'FAIL' === fx_row_level( $out228, array( 'RT_GALLERY_STALE' ) ), 'a well-formed fingerprint of the wrong inputs -> FAIL', fx_row_level( $out228, array( 'RT_GALLERY_STALE' ) ) );
ok( array() !== fx_lines_with( $out228, array( 'RT_GALLERY_STALE', 'was generated from a different set of inputs' ) ), 'and this cause reads differently from the missing-fingerprint one', $out228 );
ok( array() !== fx_lines_with( $out228, array( 'RT_GALLERY_STALE', 'php skills/html-mockup/assets/gallery/_build-gallery.php' ) ), 'and it too names the exact remedy', $out228 );
ok( array() !== fx_lines_with( $out228, array( 'RT_GALLERY_STALE', substr( $fx_wrong_fp, 0, 12 ) ) ), 'and it quotes the fingerprint the output actually records, so the reader can tell them apart', $out228 );
fx_rrmdir( $r228 );

echo "--- output whose fingerprint MATCHES the inputs on disk is silent -- a current gallery is not accused ---\n";
if ( function_exists( 'nm_gallery_input_digest' ) && function_exists( 'nm_gallery_fingerprint_line' ) ) {
	$r229 = fx_tmp_root();
	fx_base( $r229 );
	fx_gal_generator( $r229 );
	fx_gal_fingerprint( $r229 );
	fx_gal( $r229, '', $FX_GAL_MAN );
	/* Stamped AFTER every input is on disk, which is the order the generator itself runs in.
	   index.html is not one of its own inputs, so rewriting it here cannot move the digest --
	   and if it ever could, this scenario is where that shows up. */
	$fp229 = nm_gallery_input_digest( $r229 . '/skills/html-mockup/assets/gallery' );
	fx_gal( $r229, nm_gallery_fingerprint_line( $fp229 ) . "\n" . fx_gallery( $FX_GAL_STRIPS, $FX_GAL_FAR, array( 'images' => $FX_GAL_IMGS ) ) );
	/* style-catalog PR 1d: RT_CHASSIS_NOT_BUILT is gated on the same generator presence as
	   RT_GALLERY_NOT_BUILT, so this "exits code 0" positive control needs both chassis files too,
	   same reason r232 below needs them for its own clean-exit claim. */
	fx( $r229, 'skills/html-mockup/assets/chassis/corporate.html', fx_mockup() );
	fx( $r229, 'skills/html-mockup/assets/chassis/ecommerce.html', fx_mockup() );
	list( $code229, $out229 ) = fx_run_ok( $audit, $r229 );
	ok( array() === fx_lines_with( $out229, array( 'RT_GALLERY_STALE' ) ), 'matching fingerprint -> no staleness row', $out229 );
	ok( array() === fx_lines_with( $out229, array( 'RT_GALLERY_' ) ), 'and no other gallery row either: the stamp is inert scenery to every rule but this one', $out229 );
	ok( 0 === $code229, 'and the tree exits code 0', $code229 );
	/* The mutation that proves the digest is not a constant: move ONE input byte and re-run. */
	fx( $r229, 'skills/html-mockup/assets/gallery/_gallery-images.md', $FX_GAL_MAN . "\n<!-- one more byte -->\n" );
	list( , $out229b ) = fx_run_ok( $audit, $r229, array( '--row-types' ) );
	ok( 'FAIL' === fx_row_level( $out229b, array( 'RT_GALLERY_STALE' ) ), 'and touching a single input file turns that same tree red', fx_row_level( $out229b, array( 'RT_GALLERY_STALE' ) ) );
	fx_rrmdir( $r229 );
}

echo "--- no fingerprint definition in the audited root never produces RT_GALLERY_STALE ---\n";
$r230 = fx_tmp_root();
fx_base( $r230 );
fx_gal_generator( $r230 );
fx_gal( $r230, fx_gallery( $FX_GAL_STRIPS, $FX_GAL_FAR, array( 'images' => $FX_GAL_IMGS ) ), $FX_GAL_MAN );
list( , $out230 ) = fx_run_ok( $audit, $r230 );
ok( array() === fx_lines_with( $out230, array( 'RT_GALLERY_STALE' ) ), 'generator + index.html but no fingerprint definition -> still no row: the gate is conditioned on the definition', $out230 );
fx_rrmdir( $r230 );

/* ---------------------------------------------------------------------------
   style-catalog PR 1d (tasks.md 1d.1/1d.2) — RT_CHASSIS_NOT_BUILT, RT_GALLERY_NOT_BUILT's sibling
   for the SECOND artifact the same generator run writes (design.md D1). No row of this shape
   existed before this PR — verified against the real repo before writing anything here (see the
   apply-progress report for the live run): with the row absent, a chassis-less tree audited clean.

   Writes the chassis .html directly with fx() rather than through the generator, same as
   fx_gal_generator()/fx_gal() split its own sibling: presence is the whole signal here too, and
   content is never read by this row. Four scenarios, not three, because this row checks TWO paths
   from ONE generator -- the interrupted-build case (one site type written, the other not) has no
   analogue in RT_GALLERY_NOT_BUILT's single-artifact test and is the one genuinely new shape here.
   --------------------------------------------------------------------------- */

echo "--- the generator present with NEITHER chassis built FAILs twice, naming each site type ---\n";
$r231 = fx_tmp_root();
fx_base( $r231 );
fx_gal_generator( $r231 );
list( $code231, $out231 ) = fx_run_ok( $audit, $r231 );
ok( 'FAIL' === fx_row_level( $out231, array( 'RT_CHASSIS_NOT_BUILT', 'chassis/corporate.html' ) ), 'generator present, no corporate chassis built -> FAIL naming corporate', fx_row_level( $out231, array( 'RT_CHASSIS_NOT_BUILT', 'chassis/corporate.html' ) ) );
ok( 'FAIL' === fx_row_level( $out231, array( 'RT_CHASSIS_NOT_BUILT', 'chassis/ecommerce.html' ) ), 'and no ecommerce chassis built -> FAIL naming ecommerce, same row twice', fx_row_level( $out231, array( 'RT_CHASSIS_NOT_BUILT', 'chassis/ecommerce.html' ) ) );
ok( array() !== fx_lines_with( $out231, array( 'RT_CHASSIS_NOT_BUILT', 'php skills/html-mockup/assets/gallery/_build-gallery.php' ) ), 'and the message names the exact fix command', $out231 );
ok( 1 === $code231, 'and the tree exits code 1', $code231 );
fx_rrmdir( $r231 );

echo "--- the generator present with BOTH chassis built stays silent -- a built pair is not accused ---\n";
$r232 = fx_tmp_root();
fx_base( $r232 );
/* index.html joins the fixture too, same as RT_GALLERY_NOT_BUILT's own positive control (r225):
   omitting it would make RT_GALLERY_NOT_BUILT itself FAIL and confound this row's own isolation. */
fx_gal_generator( $r232 );
fx_gal( $r232, fx_gallery( $FX_GAL_STRIPS, $FX_GAL_FAR, array( 'images' => $FX_GAL_IMGS ) ), $FX_GAL_MAN );
fx( $r232, 'skills/html-mockup/assets/chassis/corporate.html', fx_mockup() );
fx( $r232, 'skills/html-mockup/assets/chassis/ecommerce.html', fx_mockup() );
list( , $out232 ) = fx_run_ok( $audit, $r232 );
ok( array() === fx_lines_with( $out232, array( 'RT_CHASSIS_NOT_BUILT' ) ), 'generator + both chassis built -> no RT_CHASSIS_NOT_BUILT row', $out232 );
fx_rrmdir( $r232 );

echo "--- an INTERRUPTED build -- one chassis written, the other not -- FAILs only for the missing one ---\n";
$r233 = fx_tmp_root();
fx_base( $r233 );
fx_gal_generator( $r233 );
fx( $r233, 'skills/html-mockup/assets/chassis/corporate.html', fx_mockup() );
list( , $out233 ) = fx_run_ok( $audit, $r233 );
ok( 'FAIL' === fx_row_level( $out233, array( 'RT_CHASSIS_NOT_BUILT', 'chassis/ecommerce.html' ) ), 'corporate written, ecommerce missing -> FAIL naming only ecommerce', fx_row_level( $out233, array( 'RT_CHASSIS_NOT_BUILT', 'chassis/ecommerce.html' ) ) );
ok( array() === fx_lines_with( $out233, array( 'RT_CHASSIS_NOT_BUILT', 'chassis/corporate.html' ) ), 'and the site type that WAS written is not named -- the row is per file, not per generator', $out233 );
fx_rrmdir( $r233 );

echo "--- no generator at all never produces RT_CHASSIS_NOT_BUILT -- the gate never fires in a bare fixture root ---\n";
$r234 = fx_tmp_root();
fx_base( $r234 );
list( , $out234 ) = fx_run_ok( $audit, $r234 );
ok( array() === fx_lines_with( $out234, array( 'RT_CHASSIS_NOT_BUILT' ) ), 'no generator, no chassis -> still no row: the gate is conditioned on the generator, same as its sibling', $out234 );
fx_rrmdir( $r234 );

/* ---------------------------------------------------------------------------
   style-catalog PR 1e (tasks.md 1e.1/1e.2/1e.3) — RT_MOCKUP_AXES_MISMATCH becomes a VALUE check,
   not only a LABEL check. mockup-guide.md:455-458 admitted the gap in prose: a `scale: contained`
   marker beside a hand-typed `--fs-h1-max: 53` used to pass, because the label agreed with the
   anchor and nothing read the number. r235 is that exact fixture — genuinely RED against the
   unmodified audit (see apply-progress for the stash/un-stash proof), GREEN once
   axis_token_values()/root_token_value() compare the two. r236 is the explicit positive control:
   label AND value agree, stays silent.
   --------------------------------------------------------------------------- */

echo "--- label agrees with the anchor, value does not: the gap the row used to have ---\n";
$r235 = fx_tmp_root();
fx_base( $r235 );
fx( $r235, 'skills/html-mockup/assets/corporate-mockup.html', fx_mockup( array(), array(), array(), array(), null, '53' ) );
list( , $out235 ) = fx_run_ok( $audit, $r235 );
ok( 'FAIL' === fx_row_level( $out235, array( 'RT_MOCKUP_AXES_MISMATCH', 'corporate-mockup.html' ) ), 'scale `contained` beside --fs-h1-max:53 FAILs even though the label matches PERS-INSTITUTIONAL', fx_row_level( $out235, array( 'RT_MOCKUP_AXES_MISMATCH', 'corporate-mockup.html' ) ) );
/* Naming the disagreeing token is the requirement (client-chassis-generation spec, "Label agrees,
   value does not"): both the actual 53 and the table's own 48 must appear, or the message sends
   the reader back to diff design-system.md by eye — exactly what this row exists to avoid. */
ok( array() !== fx_lines_with( $out235, array( 'RT_MOCKUP_AXES_MISMATCH', '--fs-h1-max', '`53`' ) ), 'and names the actual wrong value', $out235 );
ok( array() !== fx_lines_with( $out235, array( 'RT_MOCKUP_AXES_MISMATCH', '`48`' ) ), 'and the value contained actually holds, per design-system.md', $out235 );
/* The OTHER four axes still agree in this fixture, so they must not be swept into the count: a
   message over-reporting "5 of 5" here would misdirect the reader to re-check axes that are fine. */
ok( array() === fx_lines_with( $out235, array( 'RT_MOCKUP_AXES_MISMATCH', 'ground `cool`' ) ), 'and an axis whose label AND value both agree is not reported', $out235 );
fx_rrmdir( $r235 );

echo "--- label and value both agree: the row stays silent ---\n";
$r236 = fx_tmp_root();
fx_base( $r236 );
fx( $r236, 'skills/html-mockup/assets/corporate-mockup.html', fx_mockup() );
list( $code236, $out236 ) = fx_run_ok( $audit, $r236 );
ok( array() === fx_lines_with( $out236, array( 'RT_MOCKUP_AXES_MISMATCH' ) ), 'a conforming mockup does not FAIL the value check', $out236 );
ok( 0 === $code236, 'and the tree exits code 0', $code236 );
fx_rrmdir( $r236 );

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

echo "--- dos arquetipos de una familia no pueden ser el mismo esqueleto ---\n";
$r127 = fx_tmp_root();
fx_base( $r127 );
$fx_tpl_dir = 'skills/web-templates/references/templates/';
/* E-01 y E-02 comparten 6 de 10: mas de la mitad, tiene que FALLAR. */
fx( $r127, $fx_tpl_dir . 'ecommerce/TPL-E-01-fixture.md', fx_tpl( 'TPL-E-01', array( 'HEADER', 'HERO', 'CATEGORY-CARD', 'PRODUCT-CAROUSEL', 'GALLERY', 'BENEFITS', 'NEWSLETTER', 'FOOTER' ) ) );
fx( $r127, $fx_tpl_dir . 'ecommerce/TPL-E-02-fixture.md', fx_tpl( 'TPL-E-02', array( 'HEADER', 'HERO', 'CATEGORY-CARD', 'PRODUCT-CAROUSEL', 'GALLERY', 'BENEFITS', 'PRODUCT-GRID', 'FAQ' ) ) );
/* E-03 comparte SOLO header+hero en su wireframe, pero su § 3 nombra en prosa los seis ids que le
   faltarian para colisionar con E-01 (8 de 12 -> FALLA). Si el parser leyera mas alla del bloque
   cercado, este arbol traeria una fila de mas -- que es la mutacion que convierte "arquitectura
   declarada" en "cualquier COMP que el documento mencione de paso". */
fx(
	$r127,
	$fx_tpl_dir . 'ecommerce/TPL-E-03-fixture.md',
	fx_tpl(
		'TPL-E-03',
		array( 'HEADER', 'HERO', 'ABOUT', 'VALUES', 'TESTIMONIAL', 'CTA' ),
		array( 'CATEGORY-CARD', 'PRODUCT-CAROUSEL', 'GALLERY', 'BENEFITS', 'NEWSLETTER', 'FOOTER' )
	)
);
/* C-01 y C-02 comparten EXACTAMENTE la mitad -- 6 de 12 -- que es donde se para el par mas
   parecido de la familia corporate real (TPL-C-01/TPL-C-03 y TPL-C-02/TPL-C-03). Es legal, y esta
   aqui porque es el borde: un `>` que se convierta en `>=` deja verde este escenario en la fila de
   arriba y pone en rojo la familia corporate del repo, que no cambio nada. */
fx( $r127, $fx_tpl_dir . 'corporate/TPL-C-01-fixture.md', fx_tpl( 'TPL-C-01', array( 'HEADER', 'HERO', 'SERVICES', 'CTA', 'FOOTER', 'LOGOS', 'PROCESS', 'CASES', 'TESTIMONIAL', 'LEAD-FORM' ) ) );
fx( $r127, $fx_tpl_dir . 'corporate/TPL-C-02-fixture.md', fx_tpl( 'TPL-C-02', array( 'HEADER', 'HERO', 'SERVICES', 'CTA', 'FOOTER', 'LOGOS', 'PORTFOLIO-GRID', 'ABOUT' ) ) );
/* C-03 es IDENTICO a E-01, seccion por seccion, y no puede producir fila: el recomendador bifurca
   por tipo de sitio antes de poner un arquetipo sobre la mesa, asi que un parecido entre familias
   no le cuesta una eleccion a nadie. Quitar el bucle por familia y comparar todo contra todo hace
   aparecer exactamente esta fila. */
fx( $r127, $fx_tpl_dir . 'corporate/TPL-C-03-fixture.md', fx_tpl( 'TPL-C-03', array( 'HEADER', 'HERO', 'CATEGORY-CARD', 'PRODUCT-CAROUSEL', 'GALLERY', 'BENEFITS', 'NEWSLETTER', 'FOOTER' ) ) );
list( , $out127 ) = fx_run_ok( $audit, $r127 );
ok( 'FAIL' === fx_row_level( $out127, array( 'RT_TPL_TOO_SIMILAR', 'TPL-E-01 and TPL-E-02' ) ), 'dos arquetipos que comparten mas de la mitad de su inventario FALLAN', fx_row_level( $out127, array( 'RT_TPL_TOO_SIMILAR', 'TPL-E-01 and TPL-E-02' ) ) );
ok( array() !== fx_lines_with( $out127, array( 'RT_TPL_TOO_SIMILAR', 'share 6 of 10 sections' ) ), 'y la fila dice CUANTAS comparten sobre CUANTAS hay, que es lo accionable', $out127 );
ok( array() !== fx_lines_with( $out127, array( 'RT_TPL_TOO_SIMILAR', 'COMP-GALLERY' ) ), 'y nombra las secciones compartidas, no solo el conteo', $out127 );
ok( array() === fx_lines_with( $out127, array( 'RT_TPL_TOO_SIMILAR', 'TPL-E-03' ) ), 'el § 3 en prosa NO cuenta: la arquitectura es el bloque cercado del § 2 y nada mas', $out127 );
ok( array() === fx_lines_with( $out127, array( 'RT_TPL_TOO_SIMILAR', 'TPL-C-01 and TPL-C-02' ) ), 'compartir EXACTAMENTE la mitad es legal: el limite es mas de la mitad', $out127 );
ok( array() === fx_lines_with( $out127, array( 'RT_TPL_TOO_SIMILAR', 'TPL-C-03' ) ), 'y un arquetipo corporate identico a uno ecommerce no produce fila: se compara dentro de la familia', $out127 );
fx_rrmdir( $r127 );

echo "--- un arquetipo terminado al que nadie puede llegar ---\n";
/* RT_TPL_TOO_SIMILAR prueba que dos arquetipos son DISTINTOS. No dice nada de si alguien puede
   llegar a ofrecer uno, y ese fue el hueco que costo trabajo de verdad: TPL-C-06..12 se
   entregaron como siete arquetipos terminados -- con su bloque de marca, sus tipografias
   incrustadas y su tira en la galeria -- mientras recommender.md no nombraba ninguno, ni una vez.
   El arbol estuvo en 0 FAIL todo el tiempo. */
$r129 = fx_tmp_root();
fx_base( $r129 );
$fx_route_dir = 'skills/web-templates/references/templates/';
/* El recomendador nombra E-01 y NO nombra E-02. Nombra el id pelado, no el fichero: por eso el
   fichero se llama TPL-E-01-alcanzable.md -- si la regla comparase nombres de fichero, renombrar
   un arquetipo la apagaria en silencio. */
fx( $r129, 'skills/web-templates/references/recommender.md', "# Recomendador fixture\n\n| Perfil | Recomienda |\n|---|---|\n| Marca visual | **TPL-E-01** |\n" );
fx( $r129, $fx_route_dir . 'ecommerce/_README.md', "# Ecommerce\n\nTPL-E-01 y TPL-E-02 son los dos arquetipos de la familia.\n" );
fx( $r129, $fx_route_dir . 'ecommerce/TPL-E-01-alcanzable.md', fx_tpl( 'TPL-E-01', array( 'HEADER', 'HERO', 'CATEGORY-CARD', 'NEWSLETTER', 'FOOTER' ) ) );
fx( $r129, $fx_route_dir . 'ecommerce/TPL-E-02-huerfano.md', fx_tpl( 'TPL-E-02', array( 'HEADER', 'PRODUCT-GRID', 'FAQ', 'CTA', 'BENEFITS' ) ) );
/* corporate no tiene _README.md: una sola fila contra el DIRECTORIO, no una por plantilla. Un
   arbol con doce arquetipos y sin indice tiene un problema, no doce. */
fx( $r129, $fx_route_dir . 'corporate/TPL-C-01-fixture.md', fx_tpl( 'TPL-C-01', array( 'HEADER', 'HERO', 'SERVICES', 'LEAD-FORM', 'FOOTER' ) ) );
list( , $out129 ) = fx_run_ok( $audit, $r129 );
ok( 'FAIL' === fx_row_level( $out129, array( 'RT_TPL_UNROUTABLE', 'TPL-E-02', 'recommender.md' ) ), 'un arquetipo que recommender.md no nombra FALLA: esta construido y nadie puede ser enviado a el', fx_row_level( $out129, array( 'RT_TPL_UNROUTABLE', 'TPL-E-02', 'recommender.md' ) ) );
ok( array() === fx_lines_with( $out129, array( 'RT_TPL_UNROUTABLE', 'TPL-E-01' ) ), 'el que SI esta nombrado no se acusa de nada, y se le reconoce por el id pelado aunque el fichero se llame distinto', $out129 );
ok( array() !== fx_lines_with( $out129, array( 'RT_TPL_UNROUTABLE', 'corporate/ holds' ) ), 'una familia sin _README.md produce su propia fila: el indice que un humano lee antes de elegir no existe', $out129 );
ok( 1 === count( fx_lines_with( $out129, array( 'RT_TPL_UNROUTABLE', 'no _README.md' ) ) ), 'y esa fila es UNA contra el directorio, no una por cada plantilla que el indice ausente deja de listar', $out129 );
/* Y las dos causas se cuentan por separado: corporate trae DOS filas -- el indice que falta y
   la plantilla que el recomendador no nombra -- porque son dos arreglos distintos. Fundirlas en
   una haria que arreglar el _README pareciera arreglar tambien la ruta, que es la mentira que
   esta regla existe para no contar. */
ok( array() !== fx_lines_with( $out129, array( 'RT_TPL_UNROUTABLE', 'TPL-C-01', 'recommender.md' ) ), 'y la plantilla corporate sin ruta trae ADEMAS su propia fila: indice ausente y ruta ausente son dos arreglos', $out129 );
fx_rrmdir( $r129 );

/* Control negativo del gate: el mismo arbol SIN recommender.md no dice nada. Un arbol sin
   recomendador no es un catalogo con la ruta rota, es que no es un catalogo -- y el fichero que
   falta ya lo acusa RT_BROKEN_REFERENCE una capa mas arriba, porque SKILL.md apunta a el.
   Sin este gate la regla gritaria en cada fixture que lleve un TPL-*.md suelto. */
$r130 = fx_tmp_root();
fx_base( $r130 );
fx( $r130, $fx_route_dir . 'ecommerce/TPL-E-01-suelto.md', fx_tpl( 'TPL-E-01', array( 'HEADER', 'HERO', 'FOOTER' ) ) );
list( , $out130 ) = fx_run_ok( $audit, $r130 );
ok( array() === fx_lines_with( $out130, array( 'RT_TPL_UNROUTABLE' ) ), 'sin recommender.md la regla calla: no hay catalogo que enrutar y el fichero ausente es de otra fila', $out130 );
fx_rrmdir( $r130 );
echo "--- un arquetipo que el parser no puede leer no se cae de la comparacion en silencio ---\n";
$r128 = fx_tmp_root();
fx_base( $r128 );
/* Las dos caras del mismo agujero. Sin esta fila, cualquiera de las dos saca al arquetipo de todos
   los pares a los que pertenece y RT_TPL_TOO_SIMILAR se calla justo donde la documentacion empeoro
   -- la forma que RT_PERS_DUPLICATE_ID cierra un nivel mas arriba. */
fx( $r128, $fx_tpl_dir . 'ecommerce/TPL-E-01-fixture.md', fx_tpl( 'TPL-E-01', array( 'HEADER', 'HERO', 'FOOTER' ), array(), null ) );
fx( $r128, $fx_tpl_dir . 'ecommerce/TPL-E-02-fixture.md', fx_tpl( 'TPL-E-02', array(), array(), 'prose' ) );
fx( $r128, $fx_tpl_dir . 'ecommerce/TPL-E-03-fixture.md', fx_tpl( 'TPL-E-03', array( 'HEADER', 'HERO', 'ABOUT', 'VALUES', 'TESTIMONIAL', 'CTA', 'FOOTER' ) ) );
/* La tercera causa, y la unica que NO rompe el parseo: el bloque tiene ids y ademas UNA fila en
   prosa. Sin este arm, la fila solo dispara cuando TODAS las filas son prosa, y la version
   fila-a-fila del mismo defecto pasa de largo -- comprobado por mutacion sobre el arbol real:
   devolver `COMP-GALLERY (lookbook)` de TPL-E-01 a la prosa que era dejaba el arbol en 0 FAIL. */
fx( $r128, $fx_tpl_dir . 'ecommerce/TPL-E-04-fixture.md', fx_tpl( 'TPL-E-04', array( 'HEADER', 'HERO', 'CATEGORY-GRID', 'PRODUCT-TABS', 'FOOTER' ), array(), 'mixed' ) );
list( , $out128 ) = fx_run_ok( $audit, $r128 );
ok( 'FAIL' === fx_row_level( $out128, array( 'RT_TPL_NO_WIREFRAME', 'TPL-E-01-fixture.md' ) ), 'un TPL sin bloque cercado bajo "## 2. Wireframe" FALLA, nombrando el archivo', fx_row_level( $out128, array( 'RT_TPL_NO_WIREFRAME', 'TPL-E-01-fixture.md' ) ) );
ok( 'FAIL' === fx_row_level( $out128, array( 'RT_TPL_NO_WIREFRAME', 'TPL-E-02-fixture.md' ) ), 'y un bloque cuyas filas son prosa sin ningun COMP-* tambien FALLA', fx_row_level( $out128, array( 'RT_TPL_NO_WIREFRAME', 'TPL-E-02-fixture.md' ) ) );
ok( array() !== fx_lines_with( $out128, array( 'RT_TPL_NO_WIREFRAME', 'TPL-E-02-fixture.md', 'no COMP-* section at all' ) ), 'y las causas no dan el mismo mensaje: una es el bloque ausente, la otra el bloque sin ningun id', $out128 );
ok( 'FAIL' === fx_row_level( $out128, array( 'RT_TPL_NO_WIREFRAME', 'TPL-E-04-fixture.md' ) ), 'UNA sola fila en prosa dentro de un bloque que por lo demas parsea tambien FALLA', fx_row_level( $out128, array( 'RT_TPL_NO_WIREFRAME', 'TPL-E-04-fixture.md' ) ) );
ok( array() !== fx_lines_with( $out128, array( 'RT_TPL_NO_WIREFRAME', 'Editorial / Lookbook' ) ), 'y la fila cita el texto exacto de la fila sin id, que es lo que hay que arreglar', $out128 );
ok( array() === fx_lines_with( $out128, array( 'RT_TPL_NO_WIREFRAME', 'TPL-E-03-fixture.md' ) ), 'el arquetipo legible no se acusa de nada', $out128 );
fx_rrmdir( $r128 );

echo "--- la comparacion tambien mide las paginas internas, y por ROL de pagina ---\n";
/* LA FAMILIA ES EL SET DEL QUE SALE UNA ELECCION, y bajo pages/ ese set es un rol de pagina.
   La regla nacio apuntando solo a las dos familias de home, asi que todo lo de pages/ crecio fuera
   del unico control que mantiene separadas dos arquitecturas: medidos por primera vez, TPL-PDP-01 y
   TPL-PDP-02 compartian SIETE de sus ocho secciones y TPL-SHOP-01/TPL-SHOP-02 seis de siete. Este
   escenario existe para que eso no pueda volver en silencio: quitar el bloque de pages/ de
   $tpl_families deja verde el arbol entero y pone en rojo estas afirmaciones.

   El fixture de about es IDENTICO seccion por seccion al de product, y NO puede producir fila:
   nadie elige entre una pagina de Nosotros y una ficha de producto -- un sitio lleva las dos --,
   asi que comparar entre roles mediria una distancia que nadie puede gastar. Comparar la carpeta
   pages/ entera de golpe hace aparecer exactamente esa fila de mas. */
$r140 = fx_tmp_root();
fx_base( $r140 );
fx( $r140, $fx_tpl_dir . 'pages/product/TPL-PDP-01-fixture.md', fx_tpl( 'TPL-PDP-01', array( 'HEADER', 'BREADCRUMB', 'GALLERY', 'PRODUCT-INFO', 'ACCORDION', 'FOOTER' ) ) );
fx( $r140, $fx_tpl_dir . 'pages/product/TPL-PDP-02-fixture.md', fx_tpl( 'TPL-PDP-02', array( 'HEADER', 'BREADCRUMB', 'GALLERY', 'PRODUCT-INFO', 'ACCORDION', 'PRODUCT-CAROUSEL' ) ) );
fx( $r140, $fx_tpl_dir . 'pages/about/TPL-ABOUT-01-fixture.md', fx_tpl( 'TPL-ABOUT-01', array( 'HEADER', 'BREADCRUMB', 'GALLERY', 'PRODUCT-INFO', 'ACCORDION', 'FOOTER' ) ) );
/* Una sola fila en prosa dentro de una pagina interna: la misma forma peligrosa que el escenario
   128 prueba en una home, aqui para demostrar que la comprobacion fila a fila llega tan lejos como
   la de conteo. Sin ella, media docena de paginas internas podian quedarse en prosa sin acusarlas. */
fx( $r140, $fx_tpl_dir . 'pages/contact/TPL-CONTACT-01-fixture.md', fx_tpl( 'TPL-CONTACT-01', array( 'HEADER', 'CONTACT-FORM', 'FOOTER' ), array(), 'mixed' ) );
list( , $out140 ) = fx_run_ok( $audit, $r140 );
ok( 'FAIL' === fx_row_level( $out140, array( 'RT_TPL_TOO_SIMILAR', 'TPL-PDP-01 and TPL-PDP-02' ) ), 'dos fichas de producto que comparten mas de la mitad FALLAN: la regla llega a pages/', fx_row_level( $out140, array( 'RT_TPL_TOO_SIMILAR', 'TPL-PDP-01 and TPL-PDP-02' ) ) );
ok( array() !== fx_lines_with( $out140, array( 'RT_TPL_TOO_SIMILAR', 'share 5 of 7 sections' ) ), 'y la fila cuenta lo mismo aqui que en una home: cuantas comparten sobre cuantas hay', $out140 );
ok( array() === fx_lines_with( $out140, array( 'RT_TPL_TOO_SIMILAR', 'TPL-ABOUT-01' ) ), 'una pagina de Nosotros identica a una ficha NO produce fila: la familia es el ROL, no la carpeta pages/', $out140 );
ok( 'FAIL' === fx_row_level( $out140, array( 'RT_TPL_NO_WIREFRAME', 'TPL-CONTACT-01-fixture.md' ) ), 'y una fila en prosa dentro de una pagina interna tambien FALLA', fx_row_level( $out140, array( 'RT_TPL_NO_WIREFRAME', 'TPL-CONTACT-01-fixture.md' ) ) );
ok( array() !== fx_lines_with( $out140, array( 'RT_TPL_NO_WIREFRAME', 'pages/contact/TPL-CONTACT-01-fixture.md' ) ), 'nombrando el ROL dentro de la ruta, para que el lector sepa que carpeta abrir', $out140 );
fx_rrmdir( $r140 );

echo "--- style-catalog PR 5b: shipped-log.md is the ledger RT_STYLE_REPEATS_RECENT reads ---\n";
/* design.md D5's exact algorithm: take the last 5 data rows; WARN if the NEWEST row's Style also
   appears among the 4 before it. A fresh pick that matches nothing in the window stays silent --
   this scenario's real value lands once 5b.4 implements the rule (before that, "silent" is also
   true because the rule does not exist), same "value, not novelty" discipline PR 4a/4b used. */
$r150 = fx_tmp_root();
fx_base( $r150 );
fx_ledger(
	$r150,
	array(
		array( '2026-08-01', 'alpha', 'STY-EDITORIAL', 'paper', 'warm', 'editorial', 'corporate' ),
		array( '2026-08-05', 'beta', 'STY-MATTER', 'warm', 'olive', 'classic', 'ecommerce' ),
		array( '2026-08-09', 'gamma', 'STY-DIRECT', 'ink', 'orange', 'monumental', 'corporate' ),
		array( '2026-08-13', 'delta', 'STY-VITRINE', 'ink', 'metallic', 'editorial', 'ecommerce' ),
		array( '2026-08-17', 'epsilon', 'STY-QUARRY', 'saturated', 'magenta', 'compact', 'corporate' ),
	)
);
list( , $out150 ) = fx_run_ok( $audit, $r150 );
ok( array() === fx_lines_with( $out150, array( 'RT_STYLE_REPEATS_RECENT' ) ), 'a fresh pick matching none of the 4 deliveries before it stays silent', $out150 );
ok( array() === fx_lines_with( $out150, array( 'RT_STYLE_UNRESOLVED_DEFAULT' ) ), 'every row here names both a chassis and a resolved style, so the unresolved-default gate has nothing to say either', $out150 );
fx_rrmdir( $r150 );

echo "--- a repeat WITHIN the 5-row window WARNs, and the audit still exits 0 unstrict ---\n";
$r151 = fx_tmp_root();
fx_base( $r151 );
fx_ledger(
	$r151,
	array(
		array( '2026-08-01', 'alpha', 'STY-EDITORIAL', 'paper', 'warm', 'editorial', 'corporate' ),
		array( '2026-08-05', 'beta', 'STY-MATTER', 'warm', 'olive', 'classic', 'ecommerce' ),
		array( '2026-08-09', 'gamma', 'STY-QUARRY', 'saturated', 'magenta', 'compact', 'corporate' ),
		array( '2026-08-13', 'delta', 'STY-VITRINE', 'ink', 'metallic', 'editorial', 'ecommerce' ),
		array( '2026-08-17', 'epsilon', 'STY-QUARRY', 'saturated', 'magenta', 'compact', 'corporate' ),
	)
);
list( $code151, $out151 ) = fx_run_ok( $audit, $r151 );
ok( 'WARN' === fx_row_level( $out151, array( 'RT_STYLE_REPEATS_RECENT', 'STY-QUARRY' ) ), 'STY-QUARRY repeating at row 3 of the last 5 WARNs, naming the style', fx_row_level( $out151, array( 'RT_STYLE_REPEATS_RECENT', 'STY-QUARRY' ) ) );
ok( 0 === $code151, 'a WARN alone never blocks the unstrict audit -- repetition is a judgment call, not a defect (house-rules.md:31)', $code151 );
fx_rrmdir( $r151 );

echo "--- the same style, one delivery further back than row 6, stays silent -- the window is exact ---\n";
$r152 = fx_tmp_root();
fx_base( $r152 );
fx_ledger(
	$r152,
	array(
		array( '2026-07-01', 'zero', 'STY-QUARRY', 'saturated', 'magenta', 'compact', 'corporate' ),
		array( '2026-08-01', 'alpha', 'STY-EDITORIAL', 'paper', 'warm', 'editorial', 'corporate' ),
		array( '2026-08-05', 'beta', 'STY-MATTER', 'warm', 'olive', 'classic', 'ecommerce' ),
		array( '2026-08-09', 'gamma', 'STY-DIRECT', 'ink', 'orange', 'monumental', 'corporate' ),
		array( '2026-08-13', 'delta', 'STY-VITRINE', 'ink', 'metallic', 'editorial', 'ecommerce' ),
		array( '2026-08-17', 'epsilon', 'STY-QUARRY', 'saturated', 'magenta', 'compact', 'corporate' ),
	)
);
list( , $out152 ) = fx_run_ok( $audit, $r152 );
ok( array() === fx_lines_with( $out152, array( 'RT_STYLE_REPEATS_RECENT' ) ), 'the earlier STY-QUARRY sits at row 1 of 6, outside the last-5 window -- not "roughly recent", exactly the last 5', $out152 );
fx_rrmdir( $r152 );

echo "--- style-catalog PR 5b: RT_STYLE_UNRESOLVED_DEFAULT -- a delivery that names its chassis but no resolved style FAILs ---\n";
/* This closes the real defect mockup-guide.md:436-447 recorded: every corporate site shipped
   PERS-INSTITUTIONAL "not because anyone chose them but because nobody was asked to". A row with
   Chassis filled and Style blank is the offline-visible shadow of exactly that -- the audit cannot
   read es_manifest_read() (a live WP option), so this is the one place it CAN see the same fact. */
$r153 = fx_tmp_root();
fx_base( $r153 );
fx_ledger( $r153, array( array( '2026-08-20', 'zeta', '', '', '', '', 'corporate' ) ) );
list( $code153, $out153 ) = fx_run_ok( $audit, $r153 );
ok( 'FAIL' === fx_row_level( $out153, array( 'RT_STYLE_UNRESOLVED_DEFAULT', 'zeta' ) ), 'a row naming its chassis with no Style FAILs, naming the client', fx_row_level( $out153, array( 'RT_STYLE_UNRESOLVED_DEFAULT', 'zeta' ) ) );
ok( 1 === $code153, 'unlike a repeat, an unresolved default blocks the audit -- this is a completeness check, not a judgment call', $code153 );
fx_rrmdir( $r153 );

echo "--- a delivery with BOTH a chassis and a resolved style never trips the same gate ---\n";
$r154 = fx_tmp_root();
fx_base( $r154 );
fx_ledger( $r154, array( array( '2026-08-21', 'eta', 'STY-INSTITUTIONAL', 'cool', 'blue', 'contained', 'corporate' ) ) );
list( , $out154 ) = fx_run_ok( $audit, $r154 );
ok( array() === fx_lines_with( $out154, array( 'RT_STYLE_UNRESOLVED_DEFAULT' ) ), 'a resolved style beside its chassis never FAILs -- naming a chassis is not itself the defect, an unanswered one is', $out154 );
fx_rrmdir( $r154 );

echo "--- style-catalog PR 5c: RT_STYLE_PRECHARGE_UNSHIPPED -- a style declares 6 toggles, the ledger shows only 5 shipped at the declared value ---\n";
/* Root cause 6 (proposal): the demo gallery varied structure and look but moved exactly one
   toggle off default across 67 strips -- "configuration never did". This is the offline check
   that a REAL delivery ships what ITS OWN resolved style declares, read from the same ledger
   RT_STYLE_UNRESOLVED_DEFAULT above already reads. */
$r155 = fx_tmp_root();
fx_base( $r155 );
fx_sty_precharge(
	$r155,
	'STY-FIX-SIX',
	'PERS-EDITORIAL',
	array(
		array( 'TGL-A', 'on' ),
		array( 'TGL-B', 'off' ),
		array( 'TGL-C', 'slider' ),
		array( 'TGL-D', 'fija' ),
		array( 'TGL-E', 'medio' ),
		array( 'TGL-F', 'grande' ),
	)
);
fx_ledger(
	$r155,
	array(
		array( '2026-08-22', 'theta', 'STY-FIX-SIX', 'paper', 'warm', 'editorial', 'corporate', 'TGL-A=on; TGL-B=off; TGL-C=slider; TGL-D=fija; TGL-E=medio' ),
	)
);
list( $code155, $out155 ) = fx_run_ok( $audit, $r155 );
ok( 'FAIL' === fx_row_level( $out155, array( 'RT_STYLE_PRECHARGE_UNSHIPPED', 'TGL-F', 'STY-FIX-SIX' ) ), 'a style declaring 6 toggles FAILs when the ledger shows only 5 shipped at the declared value, naming the missing toggle and the style', fx_row_level( $out155, array( 'RT_STYLE_PRECHARGE_UNSHIPPED', 'TGL-F', 'STY-FIX-SIX' ) ) );
ok( 1 === $code155, 'unlike a repeat, an unshipped precharge blocks the audit -- this is a completeness check, not a judgment call', $code155 );
fx_rrmdir( $r155 );

echo "--- there is no universal floor: style A declares 2, style B declares 6, a project on A shipping exactly 2 never FAILs ---\n";
/* The locked decision this scenario exists to prove: the gate is never "did it ship at least N",
   only "did it ship what ITS OWN style declared". STY-FIX-SIX-B (6 toggles) sits in the same
   catalog and is never delivered against in this fixture -- its larger count must not leak into
   STY-FIX-TWO's own, smaller, fully-satisfied check. */
$r156 = fx_tmp_root();
fx_base( $r156 );
fx_sty_precharge( $r156, 'STY-FIX-TWO', 'PERS-MATTER', array( array( 'TGL-X', 'on' ), array( 'TGL-Y', 'off' ) ) );
fx_sty_precharge(
	$r156,
	'STY-FIX-SIX-B',
	'PERS-DIRECT',
	array(
		array( 'TGL-A', 'on' ),
		array( 'TGL-B', 'off' ),
		array( 'TGL-C', 'slider' ),
		array( 'TGL-D', 'fija' ),
		array( 'TGL-E', 'medio' ),
		array( 'TGL-F', 'grande' ),
	)
);
fx_ledger(
	$r156,
	array(
		array( '2026-08-23', 'iota', 'STY-FIX-TWO', 'warm', 'olive', 'classic', 'ecommerce', 'TGL-X=on; TGL-Y=off' ),
	)
);
list( , $out156 ) = fx_run_ok( $audit, $r156 );
ok( array() === fx_lines_with( $out156, array( 'RT_STYLE_PRECHARGE_UNSHIPPED' ) ), 'a style declaring only 2 toggles, fully shipped, is never FAILed for "too few" against a fixed count or a bigger catalog sibling -- the gate is per-style, never universal', $out156 );
fx_rrmdir( $r156 );

/* Un toggle precargado que el cliente SOBRESCRIBE sigue siendo un toggle decidido. La precarga
   es una sugerencia que toggles.md le ofrece al cliente para confirmar o cambiar, asi que una
   regla que exigiera el valor exacto reprobaria al cliente por ejercer justo esa eleccion, y
   convertiria un gate contra la monotonia en un gate que impone uniformidad. La primera version
   de esta fila comparaba por igualdad y lo habria hecho. */
echo "--- un toggle precargado y SOBRESCRITO por el cliente no es un toggle sin decidir ---
";
$r157 = fx_tmp_root();
fx_base( $r157 );
fx_sty_precharge( $r157, 'STY-FIX-OVR', 'PERS-MATTER', array( array( 'TGL-X', 'on' ), array( 'TGL-Y', 'off' ) ) );
fx_ledger(
	$r157,
	array(
		array( '2026-08-24', 'kappa', 'STY-FIX-OVR', 'warm', 'olive', 'classic', 'ecommerce', 'TGL-X=off; TGL-Y=off' ),
	)
);
list( , $out157 ) = fx_run_ok( $audit, $r157 );
ok( array() === fx_lines_with( $out157, array( 'RT_STYLE_PRECHARGE_UNSHIPPED' ) ), 'TGL-X precargado en `on` y entregado en `off` no dispara la fila: una sobrescritura es una decision', $out157 );
fx_rrmdir( $r157 );

echo "--- pero un toggle precargado y dejado VACIO si la dispara ---
";
$r158 = fx_tmp_root();
fx_base( $r158 );
fx_sty_precharge( $r158, 'STY-FIX-BLK', 'PERS-MATTER', array( array( 'TGL-X', 'on' ), array( 'TGL-Y', 'off' ) ) );
fx_ledger(
	$r158,
	array(
		array( '2026-08-25', 'lambda', 'STY-FIX-BLK', 'warm', 'olive', 'classic', 'ecommerce', 'TGL-Y=off' ),
	)
);
list( , $out158 ) = fx_run_ok( $audit, $r158 );
ok( 'FAIL' === fx_row_level( $out158, array( 'RT_STYLE_PRECHARGE_UNSHIPPED', 'TGL-X' ) ), 'TGL-X precargado y ausente del registro si dispara: un blanco no es una decision', fx_row_level( $out158, array( 'RT_STYLE_PRECHARGE_UNSHIPPED', 'TGL-X' ) ) );
fx_rrmdir( $r158 );

echo "--- style-catalog PR 6: RT_BESPOKE_UNDECLARED -- a BSP-*.md missing the ornament answer FAILs, naming it ---\n";
/* Zero precharge is ROUTE-BESPOKE's own first requirement: every one of the 8 axes gets an
   explicit answer before builder-core, none inherited. $ornament omitted (fx_pers()'s own
   omission convention, the same one RT_PERS_BAD_AXIS's r92b uses for STY-*.md) is the RED case --
   no rule reading BSP-*.md existed before this PR. */
$r159 = fx_tmp_root();
fx_base( $r159 );
fx_bsp( $r159, 'client-alpha', 'contained', 'paper', 'standard', 'centered', 'none', 'none', 'bare' );
list( $code159, $out159 ) = fx_run_ok( $audit, $r159 );
ok( 'FAIL' === fx_row_level( $out159, array( 'RT_BESPOKE_UNDECLARED', 'ornament' ) ), 'a bespoke manifest missing the ornament answer FAILs, naming the missing axis', fx_row_level( $out159, array( 'RT_BESPOKE_UNDECLARED', 'ornament' ) ) );
ok( 1 === $code159, 'an undeclared axis blocks the audit -- zero precharge means every axis is answered, not most', $code159 );
fx_rrmdir( $r159 );

echo "--- a complete BSP-*.md -- all 8 axes plus a declared wireframe -- never trips the same row ---\n";
$r160 = fx_tmp_root();
fx_base( $r160 );
fx_bsp( $r160, 'client-beta', 'contained', 'paper', 'standard', 'centered', 'none', 'none', 'bare', 'none', array( 'HERO', 'FOOTER' ) );
list( , $out160 ) = fx_run_ok( $audit, $r160 );
ok( array() === fx_lines_with( $out160, array( 'RT_BESPOKE_UNDECLARED' ) ), 'a complete manifest -- 8 axes and a declared wireframe -- never FAILs', $out160 );
fx_rrmdir( $r160 );

echo "--- promotion grants no exemption: a promoted STY-* sharing 3 of 8 axes with an existing entry FAILs RT_STYLE_TOO_SIMILAR, unmodified ---\n";
/* No new mechanism: promotion means writing the bespoke build's 8-axis answers as an ORDINARY
   STY-*.md, so the existing, unmodified RT_STYLE_TOO_SIMILAR (the PR 2b/4c gate above) already
   applies to it exactly as it applies to any of the 8 real entries -- proving that is this
   scenario's whole job. Shares scale/ground/composition (3) with the fixture catalog's
   PERS-EDITORIAL entry, hand-verified to share at most 1 with each of the other four. */
$r161 = fx_tmp_root();
fx_base( $r161 );
fx( $r161, 'skills/ux-design-system/references/style-catalog/STY-PROMOTED.md', fx_pers( 'STY-PROMOTED', 'editorial', 'paper', 'standard', 'asymmetric', 'accent-glow', 'duotone', 'layered', 'pattern' ) );
list( $code161, $out161 ) = fx_run_ok( $audit, $r161 );
ok( 'FAIL' === fx_row_level( $out161, array( 'RT_STYLE_TOO_SIMILAR', 'STY-PROMOTED' ) ), 'a promoted entry sharing 3 of 8 axes with an existing catalog entry FAILs -- promotion buys no exemption from distinctness', fx_row_level( $out161, array( 'RT_STYLE_TOO_SIMILAR', 'STY-PROMOTED' ) ) );
ok( 1 === $code161, 'the collision blocks the audit, the same as any other catalog pair', $code161 );
fx_rrmdir( $r161 );

echo "--- necessary, disclosed: a ROUTE-BESPOKE delivery's blank Style never trips RT_STYLE_UNRESOLVED_DEFAULT -- Route tells the two apart ---\n";
/* Without this exception, EVERY ROUTE-BESPOKE delivery -- Style legitimately blank by its own
   Zero Precharge requirement -- would FAIL the ledger forever, since a blank Style beside a
   filled Chassis is exactly r153's own trigger above. That would make the very route this PR
   implements undeliverable, discovered while wiring "Ledger Registration Is Mandatory" and fixed
   here rather than shipped broken. */
$r162 = fx_tmp_root();
fx_base( $r162 );
fx_ledger( $r162, array( array( '2026-08-26', 'mu', '', 'saturated', 'magenta', 'monumental', 'ecommerce', '', 'bespoke' ) ) );
list( , $out162 ) = fx_run_ok( $audit, $r162 );
ok( array() === fx_lines_with( $out162, array( 'RT_STYLE_UNRESOLVED_DEFAULT' ) ), 'Route=bespoke with a blank Style is a deliberate zero-precharge build, not the untended default the gate exists to catch', $out162 );
fx_rrmdir( $r162 );

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
