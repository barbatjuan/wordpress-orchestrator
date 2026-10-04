<?php
/**
 * WordPress Orchestrator framework self-audit — the checks that need no judgement.
 *
 * Run:  php skills/framework-audit/assets/framework-audit.php           (exit 0 = no FAILs)
 *         …/framework-audit.php --strict                                (WARNs also fail)
 *         …/framework-audit.php --root=/path/to/checkout                (audit another tree)
 *
 * Lives in the skill's assets/ rather than a repo-level tools/ for a boring but load-bearing
 * reason: install.ps1 copies only skills/ and agents/, so a script anywhere else does not travel
 * with the skill that tells you to run it.
 *
 * It audits a CHECKOUT, never an install. Pointing it at ~/.claude was tried and cut: an install
 * holds every skill from every source, so it judged 34 unrelated skills against this repo's
 * CONTRIBUTING and produced 690 warnings about files nobody here owns. An audit that fires on
 * things you cannot fix is one people learn to ignore. Verifying an install still matters — that
 * is a diff against the repo, which is a different tool.
 *
 * Everything this framework verifies is about a BUILT SITE. Nothing verified the framework
 * itself, which is how "fewest containers" stayed violated through a whole build cycle and how
 * "one H1 per page" sat in wordpress-seo for versions with no checker anywhere. This closes that
 * loop for the mechanical half.
 *
 * Deliberately split by what a script can actually decide:
 *   FAIL  — objectively wrong. Broken reference, missing build gate, absent frontmatter field.
 *   WARN  — smells wrong, a human may have a reason. Over the word budget, orphaned file.
 *   JUDGE — a heuristic fired and a MODEL has to read it. Reported, never counted as a pass.
 *
 * A script that guesses at JUDGE rows and prints PASS is worse than no script, because it
 * launders an unknown into a green tick. skills/framework-audit/SKILL.md owns that half.
 */

$strict           = in_array( '--strict', $argv, true );
$show_row_types   = in_array( '--row-types', $argv, true );
$show_word_report = in_array( '--word-report', $argv, true );

/* ---------------------------------------------------------- row-type registry (D1'.6)
 *
 * Every add() call site below is named here. Two guarantees this buys, both proven by mutation
 * in tests/test-framework-audit.php rather than asserted in prose:
 *   1. add() rejects any ID absent from this list — exit(3), loud, not a silent skip. A call site
 *      cannot drift from the registry by typo or omission.
 *   2. The regression harness accumulates every ID it OBSERVES (via --row-types, below) across all
 *      its fixtures and diffs that set against this registry. A check with no fixture, or a fixture
 *      whose emitting branch gets deleted, turns the harness red — the exact CRITICAL that sank two
 *      prior slices of this change, closed by mechanism instead of discipline.
 */
const ROW_TYPES = array(
	'RT_NO_SKILL_MD'             => 'FAIL  — a skill directory has no SKILL.md',
	'RT_NO_FRONTMATTER'          => 'FAIL  — SKILL.md has no YAML frontmatter block',
	'RT_FRONTMATTER_MISSING_KEY' => 'FAIL  — frontmatter is missing a required key',
	'RT_NAME_MISMATCH'           => 'FAIL  — frontmatter name: does not match its directory',
	'RT_NO_TRIGGER'              => 'FAIL  — description carries no "Trigger:" words',
	'RT_BODY_OVER_600'           => 'FAIL  — SKILL.md body is past the ~600-word ceiling',
	'RT_BODY_OVER_500'           => 'WARN  — SKILL.md body is past the ~500-word aim',
	'RT_NO_BUILD_GATE'           => 'FAIL  — a write-capable skill has no blocking build gate',
	'RT_GATE_NOT_LISTED'         => 'FAIL  — a skill declares a blocking build gate but is not in $WRITE_CAPABLE',
	'RT_BROKEN_REFERENCE'        => 'FAIL  — SKILL.md points at a references/assets path that does not exist',
	'RT_ORPHAN_FILE'             => 'WARN  — a references/ or assets/ file, at any depth, is reachable from nothing',
	'RT_CAPTURE_OUT_DEFAULTED'   => 'FAIL  — a .mjs asset gives --out a default instead of requiring it',
	'RT_ROWTYPE_PHANTOM'         => 'FAIL  — prose cites a row type ROW_TYPES does not declare',
	'RT_HOUSERULES_NO_WORLD'     => 'FAIL  — a house-rules row calls the library but names no world',
	'RT_MIGRATION_NO_EXCLUDE'    => 'FAIL  — a references/migration.md never names novamira-sandbox',
	'RT_HOUSERULES_ROW_PHANTOM'  => 'FAIL  — house-rules prose cites a row number the table does not contain',
	'RT_NO_HARD_RULES'           => 'WARN  — SKILL.md states no Hard Rules: section absent, or present with no "- " bullets',
	'RT_HARD_RULES_MISSING_WRITE' => 'FAIL  — a write-capable skill states no Hard Rules: section absent, or bullet-less',
	'RT_AGENT_NO_HOUSE_RULES'    => 'FAIL  — an agent states no House rules: section absent, or present with no "- " bullets',
	/* D1' verifier-marker grammar (B1) — replaces the old vocabulary-substring
	   RT_HARD_RULE_NO_VERIFIER, which prose could satisfy by accident. See marker_parse(). */
	'RT_MARKER_ABSENT'           => 'JUDGE — a Hard Rule bullet names no verifier marker',
	'RT_MARKER_MULTIPLE'         => 'FAIL  — a Hard Rule bullet carries two or more verifier markers',
	'RT_MARKER_CASE'             => 'FAIL  — a verifier marker token is not the exact lowercase literal',
	'RT_MARKER_UNCLOSED'         => 'FAIL  — a verifier marker\'s opening paren is never closed',
	'RT_MARKER_EMPTY'            => 'FAIL  — a verifier marker\'s payload is empty',
	'RT_MARKER_STOPWORD'         => 'FAIL  — a verifier marker\'s payload is a stop-word placeholder',
	'RT_MARKER_TOO_SHORT'        => 'FAIL  — a verifier marker\'s payload is under 12 characters',
	'RT_MARKER_OVERSIZE'         => 'FAIL  — a verifier marker\'s payload is over the 40-word cap',
	'RT_MARKER_TARGET_MISSING'   => 'FAIL  — a "(verifier: …)" marker names a target that does not exist',
	'RT_MARKER_MISLABEL'         => 'JUDGE — a "(no verifier: …)" marker names a target that DOES exist',
	'RT_MARKER_PROSE_ONLY'       => 'JUDGE — a "(verifier: …)" marker names no locatable target',
	'RT_ERRORLOG_NO_STDOUT'      => 'FAIL  — an error_log call has no paired stdout channel',
	'RT_HELPER_UNROUTABLE'       => 'WARN  — an asset function no asset calls is named by no markdown either',
	'RT_WRITE_NOT_LISTED'        => 'FAIL  — code writes to WordPress but the skill is missing from $WRITE_CAPABLE',
	'RT_AGENT_CODE_BLOCK'        => 'FAIL  — an agent markdown file contains a code block',
	'RT_AGENT_ROUTE_MISSING'     => 'FAIL  — an agent routes to a skill that does not exist',
	'RT_AGENT_SKILL_UNMENTIONED' => 'WARN  — an agent never mentions an existing skill',
	'RT_HOUSERULES_NO_VERDICT'   => 'FAIL  — a house-rules.md row has no verdict source',
	'RT_HOUSERULES_MISSING'      => 'FAIL  — qa-review/references/house-rules.md is missing',
	'RT_NO_OFFLINE_TESTS'        => 'FAIL  — no offline test suite under tests/',
	'RT_GATE_LINE_UNREGISTERED'  => 'FAIL  — a tests/test-*.php file is absent from the CONTRIBUTING.md gate line',
	'RT_ROWTYPE_UNDOCUMENTED'    => 'FAIL  — a ROW_TYPES ID is not listed in CONTRIBUTING.md',
	'RT_TOKENS_HARDCODED_FONT'   => 'FAIL  — design-tokens.md still hardcodes an example font pairing',
	'RT_MOCKUP_DISCLOSURE_STATE' => 'FAIL  — a Plantilla maqueta disclosure block is not built from <details>, or does not open exactly its first row (WARN for a flat FAQ listed as known debt)',
	'RT_MOCKUP_GRID_AUTOFILL'    => 'FAIL  — a Plantilla maqueta grid uses repeat(auto-fill) with no justification beside it, so it reserves columns for elements that do not exist',
	'RT_MOCKUP_FONT_NOT_EMBEDDED' => 'FAIL  — a Plantilla maqueta names a font family it does not embed',
	'RT_MOCKUP_BLEED_FIXED_BAND' => 'FAIL  — a Plantilla maqueta bleeds to `full-end` but pins --content-width at a fixed length, leaving the gutter beside the bleed unbounded',
	'RT_MOCKUP_BLEED_NOT_MEDIA'  => 'FAIL  — a Plantilla maqueta sends something other than media to the viewport glass: a card, a run of copy or, worst, a form control',
	'RT_GALLERY_NO_MANIFEST'     => 'FAIL  — a Plantilla maqueta renders an image its manifiesto-imagenes.md carries no slug and licence for',
	'RT_BUILDER_NO_TOKENS'       => 'FAIL  — a builder asset has no es_tokens() block a scan can be bounded by',
	'RT_BUILDER_HARDCODED_TOKEN' => 'FAIL  — a builder asset types a visual literal outside its token block',
	'RT_FONT_NO_SERVING_PATH'    => 'FAIL  — a builder asset names a font family and nothing warns or documents how it gets served',
);

/* --emit-row-types is static introspection of the script, not of an audited tree: it needs no
   --root and no CONTRIBUTING.md guard, so it is handled before either. */
if ( in_array( '--emit-row-types', $argv, true ) ) {
	foreach ( ROW_TYPES as $rt_id => $rt_desc ) {
		echo $rt_id . "\t" . $rt_desc . "\n";
	}
	exit( 0 );
}

/* Root = the tree that holds skills/ and agents/. Walk up rather than hardcoding a depth, so the
   same file works from the repo checkout and from an install. --root wins when given. */
$root = '';
foreach ( $argv as $a ) {
	if ( 0 === strpos( $a, '--root=' ) ) {
		$root = rtrim( substr( $a, 7 ), '/\\' );
	}
}
if ( '' === $root ) {
	$probe = __DIR__;
	for ( $i = 0; $i < 5; $i++ ) {
		if ( is_dir( $probe . '/skills' ) && is_dir( $probe . '/agents' ) ) {
			$root = $probe;
			break;
		}
		$probe = dirname( $probe );
	}
}
if ( '' === $root || ! is_dir( $root . '/skills' ) ) {
	fwrite( STDERR, "framework-audit: no skills/ + agents/ tree found. Pass --root=<checkout>.\n" );
	exit( 2 );
}
/* CONTRIBUTING.md is what distinguishes a checkout from an install directory. Refuse the latter
   rather than judging unrelated skills by this repo's rules — see the header. */
if ( ! file_exists( $root . '/CONTRIBUTING.md' ) ) {
	fwrite(
		STDERR,
		"framework-audit: \"$root\" has no CONTRIBUTING.md, so it is an install directory, not a\n"
		. "framework checkout. This audits the repo. To check an install, diff it against the repo.\n"
	);
	exit( 2 );
}
echo 'framework-audit: ' . $root . "\n\n";

/* Skills that write to a live WordPress site. The canonical list is the orchestrator's
   "the build gate is also enforced skill-side" paragraph; it is repeated here so the script
   can check it, and cross-checked below so a NEW write-capable skill cannot slip past. */
$WRITE_CAPABLE = array( 'elementor-core', 'divi-core', 'woocommerce', 'wordpress-seo', 'wordpress-performance', 'wordpress-security', 'wordpress-forms', 'wordpress-legal', 'elementor-theme-parts' );

$rows = array();
/* $id is a row-type ID from ROW_TYPES — see the block above. An ID this registry does not
   recognise is not a row, not a warning: it is a bug in the audit itself, so it stops the audit,
   loudly, rather than shipping a row nothing declared. */
function add( $id, $level, $where, $msg ) {
	global $rows;
	if ( ! array_key_exists( $id, ROW_TYPES ) ) {
		fwrite( STDERR, "framework-audit: add() called with unregistered row-type ID \"$id\" — register it in ROW_TYPES first.\n" );
		exit( 3 );
	}
	$rows[] = array( $id, $level, $where, $msg );
}
function slurp( $path ) {
	return str_replace( "\r\n", "\n", (string) file_get_contents( $path ) );
}

/* ---------------------------------------------------- verifier-marker grammar (D1', slice B1)
 *
 * Marker shape: own-line, case-sensitive — `(verifier: …)` or `(no verifier: …)`. The
 * token must OPEN the line and its closing `)` must exist. Parsed from
 * a line array plus a paren-depth balance, never a regex over the whole bullet: a greedy
 * dot-matches-newline regex anchored at end-of-bullet accepts ANY bullet whose LAST character is
 * ")" and absorbs all intervening prose into the payload — proven twice by mutation on the
 * rejected first attempt at this slice (ending a sentence in a parenthetical is a constant habit
 * in these files). Depth counting finds the marker's OWN closing paren; anything after it is
 * trailing prose, not payload.
 */
function marker_parse( $rule ) {
	$lines   = explode( "\n", rtrim( $rule ) );
	$open    = array();
	$ci_only = 0;
	foreach ( $lines as $i => $l ) {
		if ( preg_match( '/^[ \t]*\((no[ \t]+)?verifier:/', $l, $m ) ) {
			/* isset(), not a bare cast: the "no " group is optional AND last, so on the AFFIRMATIVE
			   marker PHP drops it from $m entirely instead of filling it with "". The cast got the
			   right answer and raised "Undefined array key 1" on STDOUT doing it, once per marker,
			   into the same channel the gate and --word-report print. */
			$open[] = array( $i, isset( $m[1] ) && '' !== trim( $m[1] ) );
		} elseif ( preg_match( '/^[ \t]*\((no[ \t]+)?verifier:/i', $l ) ) {
			++$ci_only;
		}
	}
	if ( 0 === count( $open ) && $ci_only > 0 ) {
		return array( 'n' => 0, 'case_mismatch' => true );
	}
	if ( 1 !== count( $open ) ) {
		return array( 'n' => count( $open ) );
	}
	list( $li, $negated ) = $open[0];
	$tail  = implode( "\n", array_slice( $lines, $li ) );
	$start = strpos( $tail, '(' );
	$depth = 0;
	$close = -1;
	for ( $p = $start, $len = strlen( $tail ); $p < $len; $p++ ) {
		if ( '(' === $tail[ $p ] ) {
			++$depth;
		} elseif ( ')' === $tail[ $p ] && 0 === --$depth ) {
			$close = $p;
			break;
		}
	}
	$span = ( -1 === $close ) ? '' : substr( $tail, $start, $close - $start + 1 );
	preg_match( '/^\((?:no[ \t]+)?verifier:[ \t]*(.*)\)$/s', $span, $pm );
	return array(
		'n'        => 1,
		'negated'  => $negated,
		'span'     => $span,
		'closed'   => ( -1 !== $close ),
		'payload'  => isset( $pm[1] ) ? trim( $pm[1] ) : '',
	);
}

/* A short, single-line excerpt of a bullet for a row message — mirrors the truncation the old
   vocabulary check used, so messages stay scannable in a terminal. */
function marker_short( $rule ) {
	return preg_replace( '/\s+/', ' ', mb_substr( ltrim( $rule, '- ' ), 0, 84 ) );
}

/* D1'.5: function names via token_get_all(), never a regex over the raw file text. A name that
   appears only in a comment (T_COMMENT/T_DOC_COMMENT) or a string literal
   (T_CONSTANT_ENCAPSED_STRING) can never be T_FUNCTION's next T_STRING, so it is structurally
   unreachable here — the exact hole the rejected slice's regex collector had. */
function collect_function_names( $root ) {
	$names = array();
	foreach ( glob( $root . '/skills/*/assets/*.php' ) as $php ) {
		$expect = false;
		foreach ( token_get_all( slurp( $php ) ) as $tok ) {
			if ( ! is_array( $tok ) ) {
				if ( $expect && '&' !== $tok ) {
					$expect = false;
				}
				continue;
			}
			list( $id, $text ) = $tok;
			if ( T_FUNCTION === $id ) {
				$expect = true;
				continue;
			}
			if ( ! $expect ) {
				continue;
			}
			if ( T_WHITESPACE === $id || T_COMMENT === $id || T_DOC_COMMENT === $id ) {
				continue;
			}
			if ( T_STRING === $id ) {
				$names[ $text ] = true;
			}
			$expect = false;
		}
	}
	return $names;
}

/* D1'.4 shape 3: reuses the exact house-rules row shape the walk further below already carries
   ("row N" in the leading table column), so there is exactly one place that decides what a
   "house-rule row" looks like. */
function collect_house_rule_rows( $root ) {
	$rows = array();
	$file = $root . '/skills/qa-review/references/house-rules.md';
	if ( file_exists( $file ) ) {
		foreach ( explode( "\n", slurp( $file ) ) as $line ) {
			if ( preg_match( '/^\|\s*(\d+)\s*\|/', $line, $m ) ) {
				$rows[ (int) $m[1] ] = true;
			}
		}
	}
	return $rows;
}

/* D1'.4 shape 4: the numbered list under a skill's OWN "## Execution Steps" heading. [^\n]*
   tolerates a decorative tail — divi-core writes "## Execution Steps (validate each)". */
function collect_skill_steps( array $skill_dirs ) {
	$steps = array();
	foreach ( $skill_dirs as $dir ) {
		$name = basename( $dir );
		$file = $dir . '/SKILL.md';
		if ( ! file_exists( $file ) ) {
			continue;
		}
		if ( preg_match( '/^## Execution Steps\b[^\n]*\n(.*?)(?=\n## |\z)/ms', slurp( $file ), $m ) ) {
			preg_match_all( '/^(\d+)\.\s/m', $m[1], $sm );
			$steps[ $name ] = array_map( 'intval', $sm[1] );
		}
	}
	return $steps;
}

/* D1'.4: the resolver shapes, tried in order. Returns null when the payload cites none of them —
   verdict RT_MARKER_PROSE_ONLY for an affirmative marker, silently accepted for a negated one (a
   gap explanation is prose by nature). Resolution itself is polarity-blind; the caller decides
   what a resolved/unresolved/absent result means for each polarity. */
function marker_resolve( $payload, $root, array $fn_names, array $house_rows, array $skill_steps, $own_skill ) {
	if ( preg_match( '/\bes_[a-z_]+\(/', $payload, $m ) ) {
		$fn = rtrim( $m[0], '(' );
		return array( 'shape' => 1, 'exists' => isset( $fn_names[ $fn ] ), 'target' => $fn . '()' );
	}
	if ( preg_match( '#`(tests/[\w\-./]+)`#', $payload, $m ) ) {
		/* The capture admits "." and "/", so it admits "..". Concatenated onto $root unchecked, a
		   climbing path probes the HOST filesystem: the FAIL is then satisfied by a file this
		   repository does not contain, and the same commit passes on one machine and fails on
		   another. A target that leaves the audited tree never counts as existing. */
		$inside = false === strpos( '/' . $m[1] . '/', '/../' );
		return array( 'shape' => 2, 'exists' => $inside && file_exists( $root . '/' . $m[1] ), 'target' => $m[1] );
	}
	if ( preg_match( '/(?:`qa-review`|house-rule)[^\d]{0,24}row\s+(\d+)/i', $payload, $m ) ) {
		return array( 'shape' => 3, 'exists' => isset( $house_rows[ (int) $m[1] ] ), 'target' => 'house-rules row ' . $m[1] );
	}
	if ( preg_match( '/(?:`([a-z0-9\-]+)`\s*)?\bstep-?\s*(\d+)\b/i', $payload, $m ) ) {
		$skill = ( isset( $m[1] ) && '' !== $m[1] ) ? $m[1] : $own_skill;
		$list  = isset( $skill_steps[ $skill ] ) ? $skill_steps[ $skill ] : array();
		return array( 'shape' => 4, 'exists' => in_array( (int) $m[2], $list, true ), 'target' => "$skill step " . $m[2] );
	}
	/* A row type THIS audit declares is a verifier like any other, and it was the one kind of
	 * checker the grammar could not name: the House rule "a warning nobody reads is not a warning"
	 * is enforced by RT_ERRORLOG_NO_STDOUT and had no honest affirmative form.
	 *
	 * LAST, and backticked. It was written first and unquoted, and that was a hole big enough to
	 * drive the whole gate through: an ID is legal prose, so a marker naming a verifier that does
	 * NOT exist resolved green the moment its sentence also mentioned any row type in passing —
	 * the target-missing FAIL was never reached, and the negated shape-2 carve-out inverted into a
	 * spurious mislabel. First-match wins, so the broadest pattern must be tried last, and the
	 * backticks make citing a row type an act rather than an accident. */
	if ( preg_match( '/`(RT_[A-Z0-9_]+)`/', $payload, $m ) ) {
		return array( 'shape' => 5, 'exists' => array_key_exists( $m[1], ROW_TYPES ), 'target' => 'row type ' . $m[1] );
	}
	return null;
}

/* Every file under references/ and assets/, at ANY depth, relative to the skill directory.
 *
 * The check this feeds used to glob one level and `continue` on directories, so 21 files under a
 * web-templates reference folder were not audited at all — the deepest and least-visited
 * corner of the repo was the one corner nothing looked at. Sorted, because a filesystem iterator's
 * order is not guaranteed and a row order that changes between runs is a diff nobody can read. */
function skill_files( $dir ) {
	$out = array();
	foreach ( array( 'references', 'assets' ) as $sub ) {
		$base = $dir . '/' . $sub;
		if ( ! is_dir( $base ) ) {
			continue;
		}
		$walk = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $walk as $f ) {
			if ( $f->isFile() ) {
				$out[] = str_replace( '\\', '/', substr( $f->getPathname(), strlen( $dir ) + 1 ) );
			}
		}
	}
	sort( $out );
	return $out;
}

/* Every token by which a file may legitimately be named. Recursion ALONE is wrong here and would
 * have added 21 false rows: almost nothing in this repo is cited by its full filename. A file with
 * a family id in its name is cited by that family (`TPL-DEEP-01`, never the whole name), a directory is cited as a directory,
 * and ten page archetypes are cited only by the README that indexes them. A pointer at a directory
 * reaches that directory's DIRECT children and no deeper — "look in pages/" tells you to open
 * pages/, where the README tells you the rest; that is exactly the hop the check must require. */
function file_handles( $rel, array $ambiguous = array() ) {
	$base = basename( $rel );
	$q    = function ( $s ) {
		return preg_quote( $s, '#' );
	};
	/* Boundary-anchored patterns, never bare substrings. A citation BEGINS where a filename
	   begins: unanchored, "gotchas.md" credited "a.md" and "TPL-PAGE-11" credited "TPL-PAGE-1", so a
	   file nobody had ever written about was reachable because of a longer name that happened to
	   end the same way. There is deliberately NO stem handle either — a name without its
	   extension is an ordinary English word, and matching one against whole files credited
	   "name.md", "version.md" and "license.md" from every SKILL.md's own frontmatter keys.
	   Measured on planted depth-1 orphans: 60 of 60 caught by the check this replaces, 20 of 60
	   by the unanchored first cut, 60 of 60 here. */
	$h = array( '#(?<![\w.\-/])' . $q( $rel ) . '(?![\w\-])#' );
	/* A bare basename is only a handle when it names ONE file in the skill. Two READMEs in two
	   directories are the ordinary case here, and crediting both because one is pointed at would
	   let the unpointed subtree ride along on its sibling's name. An ambiguous name must be cited
	   by its full path from the skill root; a directory-qualified suffix is NOT a handle. */
	if ( ! isset( $ambiguous[ $base ] ) ) {
		$h[] = '#(?<![\w.\-/])' . $q( $base ) . '(?![\w\-])#';
	}
	/* The family prefix keeps a trailing-digit guard only: "TPL-DEEP-01" is legitimately followed by
	   "-first.md" in a filename and by ".." in a range, but never by another digit. */
	if ( preg_match( '/^([A-Z]{2,}(?:-[A-Z]+)*-\d+)/', $base, $m ) ) {
		$h[] = '#(?<![\w.\-])' . $q( $m[1] ) . '(?!\d)#';
	}
	return $h;
}

/* Basenames that occur more than once under one skill, so file_handles() can refuse to credit them
   unqualified. */
function ambiguous_basenames( array $files ) {
	$seen = array();
	foreach ( $files as $rel ) {
		$b          = basename( $rel );
		$seen[ $b ] = isset( $seen[ $b ] ) ? $seen[ $b ] + 1 : 1;
	}
	return array_filter( $seen, function ( $n ) {
		return $n > 1;
	} );
}

/* Is $text a deliberate pointer at $dir_rel, rather than a longer path that merely begins with it?
 *
 * The distinction is the whole value of the directory handle. "references/" is a prefix of every
 * path under references/, so a plain substring test made a single mention of any file credit EVERY
 * depth-1 file in the skill — the check's one original job, silently vacuous. A pointer ends where
 * the directory ends: the next character must not continue the path. */
function points_at_dir( $text, $dir_rel ) {
	/* The skill's own roots are never a pointer. "references/" and "assets/" are ordinary words in
	   this repo's prose — one write-capable SKILL.md ends a sentence with "… under `assets/`." —
	   and treating either as a deliberate pointer credited EVERY file directly under it, killing
	   the whole depth-1 layer of this check for that skill. A pointer has to name a place, and the
	   root is where the walk starts, not a place someone routed you to. */
	if ( in_array( $dir_rel, array( 'references', 'assets' ), true ) ) {
		return false;
	}
	return 1 === preg_match( '#' . preg_quote( $dir_rel . '/', '#' ) . '(?![\w.\-])#', $text );
}

/* Which of those files anything can actually route to, seeded ONLY by SKILL.md and closed
 * transitively through files that are already reachable.
 *
 * An index counts as a pointer — that is how the page archetypes are found — but an index nobody
 * can reach makes nothing reachable, which is the whole point: a subtree that only cites itself is
 * still dead weight. Seeding from every file instead of from SKILL.md would make the check
 * vacuous, since any two orphans that mention each other would vouch for one another. */
function reachable_files( $dir, $src, array $files ) {
	$reach     = array();
	$ambiguous = ambiguous_basenames( $files );
	$front     = array( $src );
	while ( array() !== $front ) {
		$text = array_pop( $front );
		foreach ( $files as $rel ) {
			if ( isset( $reach[ $rel ] ) ) {
				continue;
			}
			$hit = points_at_dir( $text, dirname( $rel ) );
			foreach ( file_handles( $rel, $ambiguous ) as $handle ) {
				$hit = $hit || 1 === preg_match( $handle, $text );
			}
			if ( $hit ) {
				$reach[ $rel ] = true;
				$front[]       = slurp( $dir . '/' . $rel );
			}
		}
	}
	return $reach;
}

/* The "- " bullets under a rules heading. Always an array, never null: a file with no such
 * section and a heading with nothing under it BOTH state no rules, and both cost the same
 * verdict, so distinguishing them would be a distinction no caller acts on.
 *
 * $heading_re must capture the block. Two callers, two headings: skills say "## Hard Rules", the
 * orchestrator says "## House rules (defaults for every build — …)". */
function rules_bullets( $body, $heading_re ) {
	if ( ! preg_match( $heading_re, $body, $m ) ) {
		return array();
	}
	$out = array();
	foreach ( preg_split( '/\n(?=- )/', trim( $m[1] ) ) as $rule ) {
		$rule = trim( $rule );
		if ( '' !== $rule && '-' === $rule[0] ) {
			$out[] = $rule;
		}
	}
	return $out;
}

/* The marker grammar over one file's rule bullets. Returns the structurally valid spans, which
 * the SKILL.md caller subtracts from its word budget and the agent caller ignores (agents carry
 * no budget).
 *
 * Factored out rather than copied because the orchestrator's House rules govern EVERY build and
 * were, until this slice, the one set of rules in the repo the grammar never read. Two
 * implementations of one rule drift, and the repo says so in its own house-rules table; the
 * duplicate is the one that would have been wrong.
 *
 * $report is the scope gate: verdict rows fire only where a violation SHIPS — the five
 * write-capable skills and the orchestrator. The word-budget strip is not gated (see the cap
 * below), because a marker is provenance wherever it legitimately appears. */
function marker_walk( $where, array $rules, $report, $noun, $own_skill ) {
	global $root, $FN_NAMES, $HOUSE_ROWS, $SKILL_STEPS;
	$valid_spans = array();
	foreach ( $rules as $rule ) {
		$mp = marker_parse( $rule );
		if ( ! empty( $mp['case_mismatch'] ) ) {
			if ( $report ) {
				add( 'RT_MARKER_CASE', 'FAIL', $where, marker_short( $rule ) . '… — marker token is not the exact lowercase "(verifier:"/"(no verifier:" literal' );
			}
			continue;
		}
		if ( 0 === $mp['n'] ) {
			if ( $report ) {
				add( 'RT_MARKER_ABSENT', 'JUDGE', $where, $noun . ' names no verifier marker: "' . marker_short( $rule ) . '…"' );
			}
			continue;
		}
		if ( $mp['n'] >= 2 ) {
			if ( $report ) {
				add( 'RT_MARKER_MULTIPLE', 'FAIL', $where, marker_short( $rule ) . '… — carries ' . $mp['n'] . ' verifier markers, exactly one is allowed' );
			}
			continue;
		}
		if ( ! $mp['closed'] ) {
			if ( $report ) {
				add( 'RT_MARKER_UNCLOSED', 'FAIL', $where, marker_short( $rule ) . '… — verifier marker is never closed' );
			}
			continue;
		}
		/* Structurally valid (closed) regardless of what its payload says below — free
		   from the word budget either way (D1'.1), but only WITHIN the 40-word cap, which is
		   evaluated here, ABOVE the scope gate. The strip is what makes a marker free, so a cap
		   running only where rows are reported is not a cap: it left the unreported files an
		   unmetered region the budget subtracts and no check reads. The ROW is scoped; the CAP is
		   not. */
		$payload   = $mp['payload'];
		$too_large = str_word_count( $payload ) > 40;
		if ( ! $too_large ) {
			$valid_spans[] = $mp['span'];
		}
		if ( ! $report ) {
			continue;
		}
		if ( '' === $payload ) {
			add( 'RT_MARKER_EMPTY', 'FAIL', $where, marker_short( $rule ) . '… — verifier marker payload is empty' );
			continue;
		}
		if ( in_array( mb_strtolower( $payload ), array( 'n/a', 'none', 'todo', 'tbd', 'pendiente', '-', 'x', '?', 'dunno' ), true ) ) {
			add( 'RT_MARKER_STOPWORD', 'FAIL', $where, marker_short( $rule ) . '… — verifier marker payload "' . $payload . '" is a placeholder, not a reason' );
			continue;
		}
		if ( mb_strlen( $payload ) < 12 ) {
			add( 'RT_MARKER_TOO_SHORT', 'FAIL', $where, marker_short( $rule ) . '… — verifier marker payload is under 12 characters' );
			continue;
		}
		if ( $too_large ) {
			add( 'RT_MARKER_OVERSIZE', 'FAIL', $where, marker_short( $rule ) . '… — verifier marker payload is ' . str_word_count( $payload ) . ' words, past the 40-word cap' );
			continue;
		}
		$resolved = marker_resolve( $payload, $root, $FN_NAMES, $HOUSE_ROWS, $SKILL_STEPS, $own_skill );
		if ( $mp['negated'] ) {
			/* Shape 2 is exempt on purpose: "(no verifier: nothing runs this yet, closest is
			   `tests/test-foo.php`)" cites a NEIGHBOURING file as context for the gap — it does
			   not claim that file checks the rule. The other shapes name the checker itself, so
			   if the checker exists the marker is simply mislabelled. */
			if ( $resolved && $resolved['exists'] && 2 !== $resolved['shape'] ) {
				add( 'RT_MARKER_MISLABEL', 'JUDGE', $where, marker_short( $rule ) . '… — "(no verifier: …)" names "' . $resolved['target'] . '", which DOES exist; use "(verifier: …)" instead' );
			}
		} elseif ( ! $resolved ) {
			add( 'RT_MARKER_PROSE_ONLY', 'JUDGE', $where, marker_short( $rule ) . '… — verifier marker names no locatable row type, function, tests/ path, house-rule row or step' );
		} elseif ( ! $resolved['exists'] ) {
			add( 'RT_MARKER_TARGET_MISSING', 'FAIL', $where, marker_short( $rule ) . '… — verifier marker names "' . $resolved['target'] . '", which does not exist' );
		}
	}
	return $valid_spans;
}

/* ---------------------------------------------------------------- skills */

$skill_dirs = array_filter( glob( $root . '/skills/*' ), 'is_dir' );
sort( $skill_dirs );

/* D1'.4/.5 collectors: walked once, handed to every skill's marker_resolve() call below — the
   tree does not change mid-audit, so there is no reason to rescan it per bullet. */
$FN_NAMES    = collect_function_names( $root );
$HOUSE_ROWS  = collect_house_rule_rows( $root );
$SKILL_STEPS = collect_skill_steps( $skill_dirs );
$word_report = array();

foreach ( $skill_dirs as $dir ) {
	$name = basename( $dir );
	$file = $dir . '/SKILL.md';
	if ( ! file_exists( $file ) ) {
		add( 'RT_NO_SKILL_MD', 'FAIL', $name, 'no SKILL.md' );
		continue;
	}
	$src = slurp( $file );

	/* --- frontmatter (CONTRIBUTING §2) --- */
	if ( ! preg_match( '/\A---\n(.*?)\n---\n(.*)\z/s', $src, $m ) ) {
		add( 'RT_NO_FRONTMATTER', 'FAIL', $name, 'SKILL.md has no YAML frontmatter block' );
		continue;
	}
	list( , $fm, $body ) = $m;

	foreach ( array( 'name:', 'description:', 'license:', '  author:', '  version:' ) as $key ) {
		if ( false === strpos( $fm, $key ) ) {
			add( 'RT_FRONTMATTER_MISSING_KEY', 'FAIL', $name, 'frontmatter missing "' . trim( $key ) . '"' );
		}
	}
	if ( preg_match( '/^name:\s*(\S+)/m', $fm, $nm ) && $nm[1] !== $name ) {
		add( 'RT_NAME_MISMATCH', 'FAIL', $name, 'frontmatter name "' . $nm[1] . '" does not match its directory' );
	}
	if ( false === strpos( $fm, 'Trigger:' ) ) {
		add( 'RT_NO_TRIGGER', 'FAIL', $name, 'description carries no "Trigger:" words — the skill will not auto-activate' );
	}

	/* --- Hard Rules markers (D1'.1-.5, .9): parsed BEFORE the body word budget, because a
	 * structurally valid marker span is excluded from the word count below.
	 *
	 * The grammar verdict rows stay scoped to WRITE-CAPABLE skills, exactly like the vocabulary
	 * check this replaces: a knowledge skill's rule is executed by the model reading it in
	 * context, there is no artifact to check afterward, and demanding a verifier there would
	 * report rows nobody can act on. A rule in a skill that WRITES to a client's live site is
	 * different: a violation ships. Word-budget stripping of a structurally valid span, however,
	 * applies to every skill uniformly — a marker is provenance, not an instruction, wherever it
	 * legitimately appears.
	 */
	$is_write_capable = in_array( $name, $WRITE_CAPABLE, true );
	/* The verdict is driven by the BULLETS actually parsed, never by the heading: the lazy capture
	   matches the empty string happily, so testing the block for null let a write-capable skill
	   past the FAIL by typing "## Hard Rules" and stopping — free, where the escape this design
	   priced costs a written reason. Rules the splitter cannot see (a "* " bullet) count as absent
	   for the same reason: fail closed, and say so. */
	$rules = rules_bullets( $body, '/^## Hard Rules\n(.*?)(?=\n## |\z)/ms' );
	if ( array() === $rules ) {
		$how = 'no "## Hard Rules" section, or one with no "- " bullets under it';
		if ( $is_write_capable ) {
			add( 'RT_HARD_RULES_MISSING_WRITE', 'FAIL', $name, 'WRITE-CAPABLE skill states no Hard Rules — ' . $how );
		} else {
			add( 'RT_NO_HARD_RULES', 'WARN', $name, 'no Hard Rules — ' . $how );
		}
	}

	$valid_spans = marker_walk( $name, $rules, $is_write_capable, 'Hard Rule', $name );

	/* --- body budget (CONTRIBUTING §2: aim ~500, hard ceiling ~600) ---
	   Structurally valid marker spans are excluded (D1'.1): a marker documents what CHECKS a
	   rule, it is provenance for the audit and the reviewer, not an instruction the model
	   executes, so excluding it makes the measurement more accurate, not more lenient. */
	$budget_body = $body;
	foreach ( $valid_spans as $span ) {
		$budget_body = str_replace( $span, '', $budget_body );
	}
	$marker_words         = str_word_count( implode( ' ', $valid_spans ) );
	$words                = str_word_count( strip_tags( $budget_body ) );
	$left                 = 600 - $words;
	$word_report[ $name ] = array( $words, $marker_words );
	if ( $words > 600 ) {
		add( 'RT_BODY_OVER_600', 'FAIL', $name, "SKILL.md body is $words instruction words (+$marker_words marker), past the ~600 ceiling — move detail into references/" );
	} elseif ( $words > 500 ) {
		add( 'RT_BODY_OVER_500', 'WARN', $name, "SKILL.md body is $words instruction words (+$marker_words marker), past the ~500 aim — $left from the 600 ceiling" );
	}

	/* --- build gate: the single highest-stakes property in the repo --- */
	$gate = ( false !== strpos( $src, 'Build gate' ) && false !== strpos( $src, 'explicit **yes**' ) );
	if ( in_array( $name, $WRITE_CAPABLE, true ) ) {
		if ( ! $gate ) {
			add( 'RT_NO_BUILD_GATE', 'FAIL', $name, 'WRITE-CAPABLE SKILL WITH NO BLOCKING BUILD GATE — it can be reached by its own triggers and write unasked' );
		}
	} elseif ( $gate ) {
		/* The mirror image, and the one that stayed open longest. $WRITE_CAPABLE is what makes the
		   gate requirement, the Hard-Rules marker requirement and the write checks apply AT ALL, so
		   a name silently dropped from it disables every one of them at once — and the content
		   detector below cannot catch a skill whose writing happens through the connector rather
		   than through PHP in this repo. A skill that declares a blocking build gate has declared
		   it writes; if it is not on the list, the list is wrong. Found by mutating the list and
		   watching nothing notice. */
		add( 'RT_GATE_NOT_LISTED', 'FAIL', $name, 'declares a blocking build gate but is missing from $WRITE_CAPABLE — the list is what makes the gate, marker and write checks apply, so being off it silently disables all three' );
	}

	/* --- every path it points at must exist --- */
	preg_match_all( '#`([a-z0-9\-]+/)?(references|assets)/[\w\-./]+\.(md|php|html|mjs|js|json)`#i', $src, $refs, PREG_SET_ORDER );
	$seen = array();
	foreach ( $refs as $r ) {
		$raw = trim( $r[0], '`' );
		if ( isset( $seen[ $raw ] ) ) {
			continue;
		}
		$seen[ $raw ] = true;
		$target = $r[1] ? $root . '/skills/' . $raw : $dir . '/' . $raw;
		if ( ! file_exists( $target ) ) {
			add( 'RT_BROKEN_REFERENCE', 'FAIL', $name, 'points at "' . $raw . '", which does not exist' );
		}
	}

	/* --- files nobody can route to --- */
	$skill_files = skill_files( $dir );
	$reach       = reachable_files( $dir, $src, $skill_files );
	foreach ( $skill_files as $rel ) {
		if ( ! isset( $reach[ $rel ] ) ) {
			add( 'RT_ORPHAN_FILE', 'WARN', $name, $rel . ' is reachable from nothing — dead weight, or a missing pointer' );
		}
	}

	/* --- warnings that reach nobody (CONTRIBUTING §3) + write-capability from real code --- */
	$write_capable_hit = '';
	foreach ( glob( $dir . '/assets/*.php' ) as $php ) {
		$code  = slurp( $php );
		$lines = explode( "\n", $code );
		foreach ( $lines as $i => $line ) {
			/* A comment that only NAMES a token is not a call — shared by both checks below, so a
			 * note explaining what a function used to do never fires either one. This docblock is
			 * itself proof: every continuation line opens with " * " precisely so a comment about
			 * detection logic can never be mistaken for the code it describes. */
			$is_comment = preg_match( '#^\s*(\*|//|/\*|\#)#', $line );

			if ( ! $is_comment && preg_match( '/(?<![\w>])error_log\s*\(/', $line ) ) {
				/* Paired with a stdout channel within a few lines either way? */
				$window = implode( "\n", array_slice( $lines, max( 0, $i - 4 ), 9 ) );
				if ( ! preg_match( '/\becho\b|es_warn\s*\(/', $window ) ) {
					add(
						'RT_ERRORLOG_NO_STDOUT',
						'FAIL',
						$name,
						basename( $php ) . ':' . ( $i + 1 ) . ' error_log() with no stdout channel — the sandbox returns STDOUT, the PHP log is never fetched. Use es_warn()'
					);
				}
			}

			/* Write-capability, from what the code actually DOES, not what SKILL.md says about
			 * it. `\s*\(` is the load-bearing part: this file's own detection-regex literal below
			 * contains each token followed by "|" or ")", never "(", so it cannot self-flag. */
			if ( ! $is_comment && ! $write_capable_hit
				&& preg_match( '/\b(update_post_meta|wp_insert_post|wp_update_post|update_option|es_save_page|es_save_theme_part)\s*\(/', $line )
			) {
				$write_capable_hit = basename( $php ) . ':' . ( $i + 1 );
			}
		}
	}
	/* --- a .mjs tool that defaults its output directory (CONTRIBUTING §3) ---
	 *
	 * An --out that falls back to the working directory is not a convenience, it is a collision:
	 * two runs started from the same place, with the same label, overwrite each other's files and
	 * neither says so. It was live in blind-judges/assets/capture.mjs, where it meant one capture
	 * could quietly photograph another capture's page. The fix is one line, which is exactly why
	 * it needs a row: a one-line fix with nothing watching it comes back.
	 *
	 * Matches `arg( '--out', <anything> )` — a second argument to the flag reader IS the default.
	 * Requiring the flag reads as `arg( '--out' )` with no comma and does not match, which the
	 * paired fixture in tests/test-framework-audit.php pins so the rule cannot be satisfied by a
	 * check that flags every .mjs alike. */
	foreach ( glob( $dir . '/assets/*.mjs' ) as $mjs ) {
		$mjs_lines = explode( "
", slurp( $mjs ) );
		foreach ( $mjs_lines as $mi => $mline ) {
			if ( preg_match( '#^\s*(\*|//|/\*)#', $mline ) ) {
				continue;
			}
			if ( preg_match( '/\barg\(\s*.--out.\s*,/', $mline ) ) {
				add(
					'RT_CAPTURE_OUT_DEFAULTED',
					'FAIL',
					$name,
					basename( $mjs ) . ':' . ( $mi + 1 ) . ' gives --out a default — two runs from one directory then overwrite each other. Require it and exit(2) without it'
				);
			}
		}
	}

	if ( $write_capable_hit && ! in_array( $name, $WRITE_CAPABLE, true ) ) {
		add( 'RT_WRITE_NOT_LISTED', 'FAIL', $name, 'writes to WordPress (' . $write_capable_hit . ') but is not in the write-capable list' );
	}
}

/* --word-report is B1's deliverable to B2: B2's acceptance test is that these two columns are
   byte-identical before and after its migration (markers are excluded, and the migration is a
   pure line addition, so the number is invariant by construction). Exits before the normal
   FAIL/WARN/JUDGE report and the agent/qa-review/tests walk below -- this is introspection of the
   skill tree already walked above, not another kind of audit run. */
if ( $show_word_report ) {
	foreach ( $word_report as $rep_name => $rep ) {
		printf( "%s\t%d\t%d\n", $rep_name, $rep[0], $rep[1] );
	}
	exit( 0 );
}

/* ------------------------------------------------------------- the agent */

foreach ( glob( $root . '/agents/*.md' ) as $agent ) {
	$name = basename( $agent, '.md' );
	$src  = slurp( $agent );

	/* "The orchestrator never gains CSS/HTML/PHP" (CONTRIBUTING §2). */
	if ( preg_match( '/```(php|css|html|js)|<\?php/i', $src ) ) {
		add( 'RT_AGENT_CODE_BLOCK', 'FAIL', $name, 'contains a code block — the agent thinks, the skills execute; move it into a skill asset' );
	}
	/* Every skill it routes to must exist. Was a tautology: it only FAILed when $maybe was ALSO
	   in the set of dirs that exist, which the `is_dir()` check right above already guarantees
	   false for. Inverted so a missing routing target is actually reported. */
	preg_match_all( '/`([a-z][a-z0-9\-]{2,})`/', $src, $m );
	foreach ( array_unique( $m[1] ) as $maybe ) {
		if ( is_dir( $root . '/skills/' . $maybe ) ) {
			continue;
		}
		/* An agent may name a SIBLING AGENT, and the orchestrator has to in order to delegate to
		   one. Without this, backticking a second agent's name reads as routing to a skill that
		   does not exist — a FAIL for doing the thing delegation requires. Only real files count:
		   this widens what an agent may name, never what may be missing. */
		if ( file_exists( $root . '/agents/' . $maybe . '.md' ) ) {
			continue;
		}
		add( 'RT_AGENT_ROUTE_MISSING', 'FAIL', $name, 'routes to skill "' . $maybe . '" which is missing' );
	}
	/* Only a ROUTER owes every skill a mention, and what makes an agent a router is carrying a
	   routing map — not being the only agent in the directory, which is what this loop silently
	   assumed while there was exactly one. The second agent (a copywriter, which routes to nothing
	   by design) produced eight WARN rows saying it was "unroutable through the orchestrator",
	   which it is not supposed to be. Rows nobody can act on are how a report gets ignored. */
	$is_router = (bool) preg_match( '/^## Routing map\b/mi', $src );
	if ( $is_router ) {
		foreach ( array_map( 'basename', $skill_dirs ) as $sk ) {
			if ( false === strpos( $src, '`' . $sk . '`' ) ) {
				add( 'RT_AGENT_SKILL_UNMENTIONED', 'WARN', $name, 'never mentions skill "' . $sk . '" — unroutable through this agent, which carries a routing map' );
			}
		}
	}

	/* The orchestrator's House rules get the SAME grammar as a write-capable skill's Hard Rules,
	 * and until this slice they were the one set of rules in the repo nothing read. They are not
	 * softer than a skill's: they are the defaults every build inherits, so a violation ships on
	 * every site, not one. The heading match is deliberately loose after "House rules" — the
	 * orchestrator's carries a parenthetical, and a rule that only fires on an exact string is a
	 * rule an author retitles their way out of by accident. */
	$house = rules_bullets( $src, '/^## House rules\b[^\n]*\n(.*?)(?=\n## |\z)/mis' );
	if ( array() === $house ) {
		/* "this agent", not "the orchestrator": the row fires for every agents/*.md, and with a
		   second agent in the directory the old wording named the wrong file to whoever read it. */
		add( 'RT_AGENT_NO_HOUSE_RULES', 'FAIL', $name, 'this agent states no House rules — no "## House rules" section, or one with no "- " bullets under it' );
	} else {
		marker_walk( $name, $house, true, 'House rule', $name );
	}
}

/* ------------------------------------------- helpers nothing can invoke */

/**
 * A function no asset calls is an ENTRY POINT: the only thing left that can invoke it is an
 * instruction file telling a model to. So one that no markdown in the repo names at all is
 * unreachable — dead weight, or a helper somebody wrote, tested and forgot to wire. That second
 * case is this repo's own recurring bug one level down: `es_set_front_page()` was written,
 * measured against a live site, documented in a house rule, and called from nothing, so a build
 * could finish green with the client's front page untouched.
 *
 * Derived, never listed. A hardcoded roster of "the helpers that matter" would go stale the first
 * time somebody added one, and going stale unnoticed is the failure being checked.
 *
 * WARN, matching RT_ORPHAN_FILE: this is the same finding one level finer, and the honest reading
 * is "dead weight, or a missing pointer" — which of the two is a judgement no grep can make.
 *
 * `*.example.php` defines nothing here on purpose. An example is a file you COPY and rewrite, so
 * its top-level build function is meant to be REPLACED rather than called; demanding a pointer to
 * it would be demanding a pointer to scaffolding.
 */
$asset_defs  = array();
$asset_calls = array();
foreach ( $skill_dirs as $sdir ) {
	foreach ( glob( $sdir . '/assets/*.php' ) as $php ) {
		$lines      = explode( "\n", slurp( $php ) );
		$is_example = false !== strpos( basename( $php ), '.example.' );
		foreach ( $lines as $line ) {
			/* One `continue` for definitions, not two guards: a definition line is not a call in
			   ANY file, and counting it would make every function its own caller — the check would
			   then report nothing, ever, while looking exactly as healthy as it does now. Written
			   as two conditions first, and mutation proved the second unreachable for library
			   files and untested for examples. Whether the name is RECORDED is the only thing the
			   example carve-out decides. */
			if ( preg_match( '/^function\s+([a-z_][a-z0-9_]*)\s*\(/i', $line, $m ) ) {
				if ( ! $is_example ) {
					$asset_defs[ $m[1] ] = basename( $sdir );
				}
				continue;
			}
			/* Same rule as the two checks above: a comment that only NAMES a token is not a call,
			   so a docblock explaining what a helper used to do cannot mark it as still wired. */
			if ( preg_match( '#^\s*(\*|//|/\*|\#)#', $line ) ) {
				continue;
			}
			if ( preg_match_all( '/(?<![\w>$])([a-z_][a-z0-9_]*)\s*\(/i', $line, $mm ) ) {
				foreach ( $mm[1] as $fn ) {
					$asset_calls[ $fn ] = true;
				}
			}
		}
	}
}

/* Only markdown that can actually ROUTE — an ALLOWLIST of durable surfaces, not the whole tree
   minus whatever looked disposable at the time. The first version walked everything `SKIP_DOTS`
   left, which is `.` and `..` and nothing else, so it read `.git` and — the one that mattered —
   `.worktrees/`, a full checkout of another branch: a helper deleted here but still named by that
   copy's SKILL.md read as reachable, and the check went quiet exactly when a helper was being
   removed. Excluding hidden directories by name closed that hole and left a wider one open.
   `openspec/` is not hidden, is not a dependency tree, and is not durable: it holds IN-FLIGHT
   planning prose — proposal.md, design.md, tasks.md, specs/<capability>/spec.md — describing helpers that do
   not exist yet or were never wired. A change still deciding whether to keep a helper would name
   it there and silence the one row saying nothing can reach it. So the question is not "is this
   directory disposable" but "would a model ever be told to read this file", and only these five
   surfaces answer yes. A new durable surface is added here deliberately; a new scratch directory
   costs nothing and is credited by nobody. */
$prose = '';
foreach ( array( 'CONTRIBUTING.md', 'README.md' ) as $rel ) {
	if ( is_file( $root . '/' . $rel ) ) {
		$prose .= "\n" . slurp( $root . '/' . $rel );
	}
}
foreach ( array( 'skills', 'agents', 'docs' ) as $rel ) {
	$base = $root . '/' . $rel;
	if ( ! is_dir( $base ) ) {
		continue;
	}
	/* Hidden directories and dependency trees are still skipped by name INSIDE a durable surface:
	   a `node_modules/` or a stray checkout under `skills/` is no more routable there than at the
	   root, and the allowlist above says nothing about depth. */
	$dirs = new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS );
	$walk = new RecursiveIteratorIterator(
		new RecursiveCallbackFilterIterator(
			$dirs,
			function ( $cur ) {
				$name = $cur->getFilename();
				if ( $cur->isDir() ) {
					return '.' !== substr( $name, 0, 1 ) && ! in_array( $name, array( 'node_modules', 'vendor' ), true );
				}
				return true;
			}
		),
		RecursiveIteratorIterator::SELF_FIRST
	);
	foreach ( $walk as $f ) {
		if ( $f->isFile() && 'md' === strtolower( $f->getExtension() ) ) {
			$prose .= "\n" . slurp( $f->getPathname() );
		}
	}
}

foreach ( $asset_defs as $fn => $owner ) {
	if ( isset( $asset_calls[ $fn ] ) ) {
		continue;
	}
	if ( preg_match( '/(?<![\w])' . preg_quote( $fn, '/' ) . '(?![\w])/', $prose ) ) {
		continue;
	}
	add(
		'RT_HELPER_UNROUTABLE',
		'WARN',
		$owner,
		$fn . '() is called by no asset and named by no .md — nothing can reach it: dead weight, or a helper that was never wired'
	);
}

/* ------------------------------------------------- qa-review house rules */

$hr_file = $root . '/skills/qa-review/references/house-rules.md';
if ( file_exists( $hr_file ) ) {
	foreach ( explode( "\n", slurp( $hr_file ) ) as $i => $line ) {
		if ( ! preg_match( '/^\|\s*\d+\s*\|/', $line ) ) {
			continue;
		}
		if ( ! preg_match( '/\|\s*\*\*[^|]*(auto|eyes|measured|manual)[^|]*\*\*\s*\|\s*$/i', rtrim( $line ) ) ) {
			add( 'RT_HOUSERULES_NO_VERDICT', 'FAIL', 'qa-review', 'house-rules.md:' . ( $i + 1 ) . ' row has no verdict source in its last column' );
		}

		/* Which world a row can be proven in.
		 *
		 * Production runs no bridge plugin of ours. A row whose method is a LIBRARY call therefore
		 * has no production arm at all unless one PHP file can be evaluated there — and a row that
		 * does not say so reads as checkable everywhere while being checkable nowhere, which is
		 * exactly how UNVERIFIED turns into PASS without anybody deciding to allow it.
		 *
		 * Scoped to library-calling rows on purpose. A pure-HTTP row runs in every world, so
		 * demanding it declare one would be ceremony — and ceremony is what teaches a reader to
		 * skim the annotation that matters. Structural twin of RT_HOUSERULES_NO_VERDICT above:
		 * naming, not automating, is what is being asked. */
		if ( preg_match( '/\bes_[a-z_]+\s*\(/', $line ) && ! preg_match( '/\bW[123]\b/', $line ) ) {
			add( 'RT_HOUSERULES_NO_WORLD', 'FAIL', 'qa-review', 'house-rules.md:' . ( $i + 1 ) . ' row calls the library but never says which world it can be proven in — W1 local, W2 production over HTTP, W3 production by ephemeral PHP' );
		}
	}

	/* Prose citing a row number the table does not contain.
	 *
	 * Rows cross-reference each other constantly — "the same call as row 11", "it must run BEFORE
	 * row 22 empties the sandbox" — and that is what keeps this file one document rather than
	 * thirty-four unrelated checks. It also means deleting or renumbering a row leaves prose
	 * pointing at nothing, and the reader who follows the pointer learns to stop following them.
	 *
	 * Not hypothetical: removing three rows in one edit left four such references behind in this
	 * very file, and nothing caught them. RT_ROWTYPE_PHANTOM does not — it reads backticked RT_*
	 * ids. The marker grammar does not either — it resolves `house-rule row N` only inside a
	 * (verifier: …) marker, and these citations live in the method column, which no check reads. */
	$hr_rows = array();
	foreach ( explode( "\n", slurp( $hr_file ) ) as $hr_line ) {
		if ( preg_match( '/^\|\s*(\d+)\s*\|/', $hr_line, $hr_m ) ) {
			$hr_rows[ (int) $hr_m[1] ] = true;
		}
	}
	foreach ( explode( "\n", slurp( $hr_file ) ) as $i => $line ) {
		/* "rows 13-15", "rows 11, 16, 22, 23, 24 and 28", "row 22's reason" all have to parse, so
		   the number list is captured whole and every integer in it is checked. An en dash is a
		   range in this file's prose, and both its endpoints exist when the range is honest. */
		if ( ! preg_match_all( '/\brows?\s+((?:\d+)(?:\s*(?:,|and|-|\x{2013}|\x{2014})\s*\d+)*)/u', $line, $cm ) ) {
			continue;
		}
		$said = array();
		foreach ( $cm[1] as $group ) {
			preg_match_all( '/\d+/', $group, $nums );
			foreach ( $nums[0] as $n ) {
				if ( isset( $hr_rows[ (int) $n ] ) || isset( $said[ $n ] ) ) {
					continue;
				}
				$said[ $n ] = true;
				add( 'RT_HOUSERULES_ROW_PHANTOM', 'FAIL', 'qa-review', 'house-rules.md:' . ( $i + 1 ) . ' cites row ' . $n . ', which this table does not contain — a pointer left behind by a deletion or a renumber' );
			}
		}
	}
} else {
	add( 'RT_HOUSERULES_MISSING', 'FAIL', 'qa-review', 'references/house-rules.md is missing — the house rules have no gate' );
}

/**
 * The typefaces a stylesheet ASKS FOR, as opposed to the ones it settles for.
 *
 * THE RULE IS FIRST-IN-STACK, and it is the stack's own semantics rather than a list somebody has
 * to maintain. `font-family: A, B, C` means "A, and if you cannot serve A, then B". A is the
 * design; B and C are the safety net. So A is the one that has to exist.
 *
 * The alternative — an allowlist of "fonts the system already has" — was rejected because it has
 * to know that `'Segoe UI'`, `'Times New Roman'`, `'Helvetica Neue'` and `'Arial Black'` are
 * quoted FALLBACKS in these files and not requests, while `'DM Sans'` and `'Inter Tight'` are
 * requests. Quoting does not separate them; position does. `RT_FONT_NO_SERVING_PATH` maintains
 * such a list ($builder_system_faces) because it scans PHP that emits one family at a time with no
 * stack to read the position from — the two rows look at different shapes, so they do not share.
 *
 * Two sources, because these files write the stack once into a token and reference it everywhere:
 * custom properties whose name starts `--font`, and literal `font-family` declarations. A stack
 * beginning with `var(…)` is skipped — it aliases a token this same scan already read, and
 * following it would report the same family twice or, worse, report `var` as a family.
 *
 * DELIBERATELY WHOLE-FILE, unlike the `:root`-only axis scan above. An axis token is DECLARED once
 * and USED everywhere, so a whole-file scan there would count a use as a declaration. A font stack
 * is the opposite: `font-family:` in a media query or a `[data-anchor]` block is a real request
 * that a browser will really try to serve, and reading only `:root` would miss every one of them.
 */
function mockup_fonts_asked_for( $css ) {
	$generic = array(
		'serif', 'sans-serif', 'monospace', 'cursive', 'fantasy', 'system-ui', 'ui-serif',
		'ui-sans-serif', 'ui-monospace', 'ui-rounded', 'inherit', 'initial', 'unset', 'revert',
		'revert-layer', 'math', 'emoji', 'fangsong', 'none',
	);
	/* @font-face blocks are the ANSWER, never the question. Leaving them in would make every
	   embedded family look like a request for itself — harmless today, and it would silently turn
	   the row into a tautology the moment a face were declared for a family nothing asks for. */
	$css    = preg_replace( '/@font-face\s*\{[^}]*\}/i', '', $css );
	$stacks = array();
	if ( preg_match_all( '/--font[\w-]*\s*:\s*([^;}]+)/i', $css, $m ) ) {
		$stacks = array_merge( $stacks, $m[1] );
	}
	if ( preg_match_all( '/(?<![\w-])font-family\s*:\s*([^;}]+)/i', $css, $m ) ) {
		$stacks = array_merge( $stacks, $m[1] );
	}
	$out = array();
	foreach ( $stacks as $stack ) {
		$parts = explode( ',', trim( $stack ) );
		$first = trim( $parts[0] );
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

/**
 * The families a stylesheet actually SERVES: every `@font-face` family name mapped to its `src`.
 *
 * The `src` comes back because declaring the face is only half of serving it. An `@font-face`
 * pointing at `url(https://fonts.gstatic.com/…)` satisfies "is there a face for this family?"
 * while being the exact thing the Artifact CSP blocks, so the caller checks the scheme too.
 */
function mockup_font_faces_served( $css ) {
	$out = array();
	if ( ! preg_match_all( '/@font-face\s*\{([^}]*)\}/i', $css, $blocks ) ) {
		return $out;
	}
	foreach ( $blocks[1] as $block ) {
		if ( ! preg_match( '/(?<![\w-])font-family\s*:\s*([^;]+)/i', $block, $fm ) ) {
			continue;
		}
		$fam = trim( trim( $fm[1] ), "\"'" );
		$src = preg_match( '/(?<![\w-])src\s*:\s*([^;]+)/i', $block, $sm ) ? trim( $sm[1] ) : '';
		if ( '' !== $fam ) {
			$out[ $fam ] = $src;
		}
	}
	return $out;
}

/** One row of a markdown table, cells trimmed, outer pipes dropped. */
function manifest_cells( $line ) {
	$line = preg_replace( '/^\|/', '', trim( $line ) );
	$line = preg_replace( '/\|$/', '', $line );
	return array_map( 'trim', explode( '|', $line ) );
}

/** `|---|:--|` and friends: a row that is only alignment, never data. */
function manifest_is_separator( array $cells ) {
	if ( array() === $cells ) {
		return false;
	}
	foreach ( $cells as $c ) {
		if ( ! preg_match( '/^:?-{3,}:?$/', $c ) ) {
			return false;
		}
	}
	return true;
}

/**
 * The image table out of a Plantilla's `manifiesto-imagenes.md`: its rows, and which column carries
 * the licence. Columns are read from the HEADER, never counted from the left, and the table
 * selected is the first whose header carries a cell reading exactly `Slug`. Returns null when NO
 * table carries a Slug column — a different finding from a table whose rows are wrong.
 */
function manifest_table( $md ) {
	$blocks = array();
	$cur    = array();
	foreach ( explode( "\n", $md ) as $i => $line ) {
		$t = trim( $line );
		if ( '' !== $t && '|' === $t[0] ) {
			$cur[] = array( $i + 1, $t );
			continue;
		}
		if ( array() !== $cur ) {
			$blocks[] = $cur;
			$cur      = array();
		}
	}
	if ( array() !== $cur ) {
		$blocks[] = $cur;
	}
	foreach ( $blocks as $block ) {
		$slug_col = null;
		$lic_col  = null;
		foreach ( manifest_cells( $block[0][1] ) as $ci => $cell ) {
			$name = strtolower( trim( $cell, " \t`*" ) );
			if ( 'slug' === $name ) {
				$slug_col = $ci;
			}
			if ( 'licencia' === $name || 'licence' === $name || 'license' === $name ) {
				$lic_col = $ci;
			}
		}
		if ( null === $slug_col ) {
			continue;
		}
		$rows = array();
		foreach ( array_slice( $block, 1 ) as $r ) {
			$cells = manifest_cells( $r[1] );
			if ( manifest_is_separator( $cells ) ) {
				continue;
			}
			$rows[] = array(
				'line'    => $r[0],
				'slug'    => isset( $cells[ $slug_col ] ) ? trim( $cells[ $slug_col ], " \t`" ) : '',
				'licence' => ( null !== $lic_col && isset( $cells[ $lic_col ] ) ) ? trim( $cells[ $lic_col ], " \t`" ) : '',
			);
		}
		return array( 'rows' => $rows, 'licence_col' => $lic_col );
	}
	return null;
}

/** Every image slug a maqueta renders from its Plantilla's `img/` folder, sorted and de-duplicated. */
function manifest_used_slugs( $src ) {
	$out = array();
	if ( preg_match_all( '#(?:\bsrc\s*=\s*["\']|url\(\s*["\']?)(?:\.\./)?img/([A-Za-z0-9._-]+?)\.(?:webp|png|jpe?g|avif|gif)\b#i', $src, $m ) ) {
		foreach ( $m[1] as $s ) {
			$out[ trim( $s ) ] = true;
		}
	}
	ksort( $out, SORT_STRING );
	return array_keys( $out );
}

/* ------------------------------------------------------ the Plantilla maquetas
 *
 * A Plantilla is a folder of committed bytes, and its `maqueta/index.html` is the one file a client
 * is shown before the native build. Every rule below reads THAT file and nothing generated: there
 * is no generator, no chassis and no gallery to build first, so a fresh clone is as green as a
 * worked one.
 *
 * These five rules were written against the legacy gallery and chassis and carried over unchanged
 * in what they decide; only the subject moved. Each one exists because a defect shipped through
 * every other gate while the audit was green:
 *   RT_MOCKUP_GRID_AUTOFILL   — a grid reserved a column for every element that FITS, not that EXISTS
 *   RT_MOCKUP_DISCLOSURE_STATE — four emitters, three behaviours for one accordion
 *   RT_MOCKUP_BLEED_FIXED_BAND / _NOT_MEDIA — dead margin and a form control on the viewport glass
 *   RT_MOCKUP_FONT_NOT_EMBEDDED — every review judged the fallback stack, not the chosen typeface
 * The axis, anchor, proof and gallery rows that used to share this walk guarded generated files that
 * no longer exist and are retired with them.
 */
$plantilla_root = $root . '/skills/web-templates/references/plantillas';
$maquetas       = glob( $plantilla_root . '/*/maqueta/index.html' );
if ( ! is_array( $maquetas ) ) {
	$maquetas = array();
}
/* Sorted: a row's order must not depend on the filesystem's directory-entry order. */
sort( $maquetas );

/* KNOWN DEBT, named one file at a time and never a pattern. These rules were written for generated
   files and only started reading the Plantilla maquetas when the generator was retired. One sealed
   Plantilla already breaks one of them: `corte`'s FAQ shows all three answers at once. Fixing it
   changes the sealed bytes and sends the Plantilla back to the judges, so for that file the row
   reports at WARN, every run, with the reason attached. Every other file is judged at full
   strength, and the entry is deleted the day `corte` is rebuilt. */
$maqueta_known_debt = array(
	'plantillas/corte/maqueta/index.html' => 'sealed before this row read maquetas; fixing it changes the sealed bytes and needs a new judge pass',
);

foreach ( $maquetas as $mockup_path ) {
	$mockup_name = 'plantillas/' . basename( dirname( dirname( $mockup_path ) ) ) . '/maqueta/index.html';
	$mockup_src  = slurp( $mockup_path );
	/* Only the first `:root` block is read for the content-width token: the property is DECLARED
	   once and USED everywhere, so a whole-file scan would count a use as a declaration. */
	$mockup_root = '';
	if ( preg_match( '/:root\s*\{(.*?)\}/s', $mockup_src, $mrm ) ) {
		$mockup_root = $mrm[1];
	}

	/* ---- RT_MOCKUP_GRID_AUTOFILL ----
	 *
	 * `auto-fill` AND `auto-fit` LOOK IDENTICAL IN A STYLESHEET AND ARE NOT. `auto-fill` creates
	 * every column that fits the container whether or not there is an element for it; `auto-fit`
	 * collapses the empty ones so the elements that exist share the width. Three team portraits in
	 * a canvas that fits four render, under `auto-fill`, as three cards squeezed left with a quarter
	 * of the section empty — and an empty reserved column is indistinguishable from a missing card.
	 *
	 * NOT A BAN, A JUSTIFICATION REQUIREMENT: `auto-fill` is right for a calendar month or a seat
	 * map, where the empty track IS the point. So the row asks for the marker `auto-fill:` in a
	 * comment within 400 characters before the declaration, and the reason travels with the code.
	 */
	if ( preg_match_all( '/repeat\(\s*auto-fill/', $mockup_src, $af_m, PREG_OFFSET_CAPTURE ) ) {
		$af_bad = 0;
		foreach ( $af_m[0] as $af_hit ) {
			$af_from = max( 0, $af_hit[1] - 400 );
			$af_ctx  = substr( $mockup_src, $af_from, $af_hit[1] - $af_from );
			if ( false === strpos( $af_ctx, 'auto-fill:' ) ) {
				++$af_bad;
			}
		}
		if ( $af_bad > 0 ) {
			add( 'RT_MOCKUP_GRID_AUTOFILL', 'FAIL', 'web-templates', $mockup_name . ': ' . $af_bad . ' grid(s) use repeat(auto-fill) with no `auto-fill:` justification beside them — that reserves a column for every element that FITS rather than for every element that EXISTS, and a reserved empty column reads as a missing card. `auto-fit` collapses them; if the empty track is the point, say so in a comment within 400 chars' );
		}
	}

	/* ---- RT_MOCKUP_DISCLOSURE_STATE ----
	 *
	 * ONE RULE FOR EVERY DISCLOSURE LIST: built from `<details>`, and exactly the FIRST row open.
	 * All closed is a wall of headings the reader will not open; all open is a long page pretending
	 * to be a short one. Measured when somebody finally looked: FOUR emitters, THREE behaviours,
	 * one of them a `<div class="qa"><h3>` with every answer permanently on screen.
	 *
	 * A RUN OF SIBLINGS, NOT A LIST OF WRAPPER CLASSES: two or more `<details>` separated by nothing
	 * but whitespace are one list, whatever wraps them. A SINGLE `<details>` is not a list and is
	 * not judged. THREE CAUSES, three messages, because each is a different edit.
	 */
	if ( preg_match_all( '#<details[^>]*>.*?</details>#s', $mockup_src, $disc_m, PREG_OFFSET_CAPTURE ) ) {
		/* Group into runs: a hit that starts where the previous one ended, give or take whitespace,
		   is the same list. */
		$disc_runs = array();
		$disc_cur  = array();
		$disc_prev = -1;
		foreach ( $disc_m[0] as $disc_hit ) {
			$disc_at = $disc_hit[1];
			if ( $disc_prev >= 0 && '' === trim( substr( $mockup_src, $disc_prev, $disc_at - $disc_prev ) ) ) {
				$disc_cur[] = $disc_hit[0];
			} else {
				if ( count( $disc_cur ) > 0 ) {
					$disc_runs[] = $disc_cur;
				}
				$disc_cur = array( $disc_hit[0] );
			}
			$disc_prev = $disc_at + strlen( $disc_hit[0] );
		}
		if ( count( $disc_cur ) > 0 ) {
			$disc_runs[] = $disc_cur;
		}
		$disc_none_open = 0;
		$disc_many_open = 0;
		foreach ( $disc_runs as $disc_run ) {
			if ( count( $disc_run ) < 2 ) {
				continue;
			}
			$disc_open = 0;
			foreach ( $disc_run as $disc_row ) {
				if ( preg_match( '/^<details[^>]*\sopen[\s>]/', $disc_row ) ) {
					++$disc_open;
				}
			}
			if ( 0 === $disc_open ) {
				++$disc_none_open;
			} elseif ( $disc_open > 1 ) {
				++$disc_many_open;
			}
		}
		if ( $disc_none_open > 0 ) {
			add( 'RT_MOCKUP_DISCLOSURE_STATE', 'FAIL', 'web-templates', $mockup_name . ': ' . $disc_none_open . ' disclosure list(s) open no row — all closed reads as a wall of headings and the reader cannot tell whether anything inside is worth opening. The FIRST row carries `open`, and no other' );
		}
		if ( $disc_many_open > 0 ) {
			add( 'RT_MOCKUP_DISCLOSURE_STATE', 'FAIL', 'web-templates', $mockup_name . ': ' . $disc_many_open . ' disclosure list(s) open more than one row — all open is not an accordion, it is a long page pretending to be a short one. Exactly the first, and no other' );
		}
	}
	/* A section that calls itself a FAQ and holds no `<details>` at all is the fourth emitter's
	   defect, and the run check above cannot see it, because there is nothing to run over. */
	if ( preg_match_all( '#<section[^>]*class="[^"]*\bfaq\b[^"]*"[^>]*>(.*?)</section>#s', $mockup_src, $disc_fq, PREG_SET_ORDER ) ) {
		$disc_flat = 0;
		foreach ( $disc_fq as $disc_one ) {
			if ( false === strpos( $disc_one[1], '<details' ) ) {
				++$disc_flat;
			}
		}
		if ( $disc_flat > 0 ) {
			/* The one named exception, and it is a WARN rather than a silence: see $maqueta_known_debt. */
			$disc_debt = isset( $maqueta_known_debt[ $mockup_name ] );
			add( 'RT_MOCKUP_DISCLOSURE_STATE', $disc_debt ? 'WARN' : 'FAIL', 'web-templates', $mockup_name . ': ' . $disc_flat . ' FAQ section(s) hold no <details> at all — every answer is permanently on screen, which is not a disclosure list, it is a long page pretending to be a short one' . ( $disc_debt ? ' [known debt: ' . $maqueta_known_debt[ $mockup_name ] . ']' : '' ) );
		}
	}

	/* ---- RT_MOCKUP_BLEED_FIXED_BAND ----
	 *
	 * A FIXED `--content-width` IS ONLY A DEFECT NEXT TO A VIEWPORT-EDGE BLEED, so the row has two
	 * arms and fires on neither alone. A centred layout caps its content at a fixed band, correctly.
	 * A named-line grid that declares `full-end` has to sum to the SCREEN, so a fixed band leaves
	 * the outer `1fr` gutter as the only track that can absorb a wider screen and nothing bounds it
	 * (measured: 150px of dead margin at 1440, 710px at 2560). A text check decides whether the
	 * token is a bare literal; whether a fluid value tracks the viewport WELL is house-rules row 32.
	 */
	if ( false !== strpos( $mockup_src, 'full-end' )
		&& preg_match( '/--content-width\s*:\s*([^;}]+)/', $mockup_root, $cwm ) ) {
		$cw_value = trim( $cwm[1] );
		if ( ! preg_match( '/clamp\(|min\(|max\(|\d\s*vw|\d\s*vmin|\d\s*%/i', $cw_value ) ) {
			add(
				'RT_MOCKUP_BLEED_FIXED_BAND',
				'FAIL',
				'web-templates',
				$mockup_name . ' bleeds to `full-end` but pins --content-width at `' . $cw_value
					. '` — the named-line grid must sum to the viewport, so a fixed band leaves the outer 1fr gutter'
					. ' as the only track that can absorb a wider screen and nothing bounds it (measured 710px of dead'
					. ' margin at 2560 on a 1140px band). Use a fluid value (clamp/min/max/vw)'
			);
		}
	}

	/* ---- RT_MOCKUP_BLEED_NOT_MEDIA ----
	 *
	 * WHAT REACHES THE GLASS, not how wide the band beside it is. An element may resolve to
	 * `full-start` / `full-end` only if it is MEDIA — a figure, an image, a picture, a video, or a
	 * container whose whole job is to hold one. Anything else at the glass is a defect whose
	 * severity only goes up as the element gets more interactive: copy is amputated, a card is
	 * sliced, a control is broken (a submit button's right border sat on x=2560.0 with a 1453px name
	 * field beside it, while scrollWidth === clientWidth throughout).
	 *
	 * A WHITELIST, FAIL-CLOSED ON THE UNKNOWN: a subject this row does not recognise is reported,
	 * not skipped. It reads the LAST simple selector — the element the rule actually styles.
	 */
	$bleed_media_ok = array( 'media', 'frame', 'figure', 'img', 'picture', 'video',
		'slides', 'slide', 'hero-slides', 'shot', 'ph', 'slab' );
	/* `bleedband` is a SECTION that spans the glass, not an item inside a row, so no copy and no
	   control ever reaches the glass — only the colour does. The claim is checked and not trusted:
	   the readback below FAILs if the class is ever found on anything but a `<section>`. */
	$bleed_media_ok[] = 'bleedband';
	$bleed_offences   = array();
	/* COMMENTS COME OUT FIRST: stripping them after splitting the selector list on `,` reported the
	   prose above a rule as separate offending selectors. */
	$bleed_css = preg_replace( '#/\*.*?\*/#s', '', $mockup_src );
	if ( preg_match_all( '/([^{}]+)\{[^{}]*grid-column\s*:[^;}]*full-(?:start|end)[^;}]*[;}]/i', $bleed_css, $bm, PREG_SET_ORDER ) ) {
		foreach ( $bm as $bleed_rule ) {
			foreach ( explode( ',', $bleed_rule[1] ) as $bleed_sel ) {
				$bleed_sel = trim( $bleed_sel );
				if ( '' === $bleed_sel ) {
					continue;
				}
				/* The subject is the last compound selector; strip its attribute/pseudo tail and
				   take the element name or the last class on it. */
				$bleed_parts   = preg_split( '/\s+|>/', $bleed_sel, -1, PREG_SPLIT_NO_EMPTY );
				$bleed_subject = (string) end( $bleed_parts );
				$bleed_subject = preg_replace( '/:{1,2}[\w-]+(\([^)]*\))?/', '', $bleed_subject );
				$bleed_subject = preg_replace( '/\[[^\]]*\]/', '', $bleed_subject );
				$bleed_names   = array();
				if ( preg_match_all( '/\.([\w-]+)/', $bleed_subject, $bcm ) ) {
					$bleed_names = $bcm[1];
				} elseif ( preg_match( '/^([a-z][\w-]*)/i', $bleed_subject, $btm ) ) {
					$bleed_names = array( strtolower( $btm[1] ) );
				}
				if ( array() === $bleed_names ) {
					continue;
				}
				foreach ( $bleed_names as $bleed_name ) {
					if ( in_array( $bleed_name, $bleed_media_ok, true ) ) {
						continue 2;
					}
				}
				$bleed_offences[] = '`' . $bleed_sel . '`';
			}
		}
	}
	if ( array() !== $bleed_offences ) {
		add(
			'RT_MOCKUP_BLEED_NOT_MEDIA',
			'FAIL',
			'web-templates',
			$mockup_name . ' sends ' . implode( ', ', array_unique( $bleed_offences ) )
				. ' to `full-start`/`full-end`, which IS the layout viewport edge, and none of them is media.'
				. ' A photograph at the glass is a bleed; a card is sliced, a paragraph is amputated and a form'
				. ' control is unusable. End the row at the band (`c 13` / `wide-end`) and let only `.media` reach `full-end`'
		);
	}
	if ( preg_match_all( '/<([a-z][\w-]*)\b[^>]*\bclass\s*=\s*"[^"]*\bbleedband\b[^"]*"/i', $mockup_src, $bb_m, PREG_SET_ORDER ) ) {
		$bb_bad = array();
		foreach ( $bb_m as $bb_one ) {
			if ( 'section' !== strtolower( $bb_one[1] ) ) {
				$bb_bad[] = '`<' . strtolower( $bb_one[1] ) . '>`';
			}
		}
		if ( array() !== $bb_bad ) {
			add(
				'RT_MOCKUP_BLEED_NOT_MEDIA',
				'FAIL',
				'web-templates',
				$mockup_name . ' puts `bleedband` on ' . implode( ', ', array_unique( $bb_bad ) )
					. ' and that class is admitted to the viewport glass ONLY as a section-level band.'
					. ' On anything smaller it is an item inside a row claiming a gutter its row-mates do not,'
					. ' which is the exact defect this row was built from. Move the class to the `<section>`'
			);
		}
	}

	/* ---- RT_MOCKUP_FONT_NOT_EMBEDDED ----
	 *
	 * A declared typeface nobody serves. A mockup that named real families and embedded none of
	 * them rendered the FALLBACK STACK, so every visual judgement of it was a judgement of Georgia.
	 * The mockup-side twin of es_font_serving_check(), not a duplicate: that one asks about the
	 * WordPress build, this one whether the static HTML a client is shown carries the bytes.
	 *
	 * TWO ARMS, because either alone is trivially satisfiable: (1) a family asked for with no
	 * `@font-face` at all; (2) an `@font-face` whose `src` is a URL rather than a `data:` URI, which
	 * satisfies arm 1 while being exactly what the Artifact CSP blocks.
	 */
	$mockup_asked = mockup_fonts_asked_for( $mockup_src );
	$mockup_serve = mockup_font_faces_served( $mockup_src );

	$mockup_bare = array();
	$mockup_url  = array();
	foreach ( $mockup_asked as $mockup_fam ) {
		if ( ! isset( $mockup_serve[ $mockup_fam ] ) ) {
			$mockup_bare[] = '`' . $mockup_fam . '`';
			continue;
		}
		if ( false === stripos( $mockup_serve[ $mockup_fam ], 'data:' ) ) {
			$mockup_url[] = '`' . $mockup_fam . '`';
		}
	}
	if ( array() !== $mockup_bare ) {
		add(
			'RT_MOCKUP_FONT_NOT_EMBEDDED',
			'FAIL',
			'web-templates',
			$mockup_name . ' names ' . implode( ', ', $mockup_bare )
				. ' first in a font stack and declares no @font-face for it — the file renders its FALLBACK,'
				. ' so everyone who reviews it reviews a typeface nobody chose. Embed the woff2 as a data: URI:'
				. ' html-mockup/assets/fonts/_fonts.php (nm_font_faces) does it, _fonts.md says under what licence'
		);
	}
	if ( array() !== $mockup_url ) {
		add(
			'RT_MOCKUP_FONT_NOT_EMBEDDED',
			'FAIL',
			'web-templates',
			$mockup_name . ' serves ' . implode( ', ', $mockup_url )
				. ' from a URL rather than a data: URI — the Artifact CSP blocks the request, so the face never'
				. ' arrives and the file renders the same fallback it would with no @font-face at all'
		);
	}

	/* ---- RT_GALLERY_NO_MANIFEST ----
	 *
	 * An image the maqueta renders must have a row in the Plantilla's `manifiesto-imagenes.md`
	 * carrying a slug and a licence. The file name IS the WordPress attachment slug, so an image the
	 * manifest does not carry is one the operator cannot upload and es_photo() cannot resolve; and
	 * this repository is public under Apache-2.0, so an image whose terms are recorded nowhere is a
	 * right nobody checked we had to give. (The id keeps its legacy name; its subject is the
	 * Plantilla's own manifest.)
	 *
	 * COLUMNS ARE READ FROM THE HEADER, never counted from the left: the table selected is the
	 * first whose header carries a cell reading exactly `Slug`.
	 */
	$man_used = manifest_used_slugs( $mockup_src );
	$man_file = dirname( dirname( $mockup_path ) ) . '/manifiesto-imagenes.md';
	if ( ! is_file( $man_file ) ) {
		if ( array() !== $man_used ) {
			add(
				'RT_GALLERY_NO_MANIFEST',
				'FAIL',
				'web-templates',
				$mockup_name . ' renders ' . count( $man_used ) . ' image slug(s) and no manifiesto-imagenes.md sits beside its folder'
					. ' — es_photo() resolves a WordPress ATTACHMENT SLUG, not a URL and not a data: URI, so an image with no row is a promise'
					. ' the native build cannot keep and the client gets the grey box the mockup told them they would not get'
			);
		}
		continue;
	}
	$man_where = substr( $man_file, strlen( $plantilla_root ) + 1 );
	$man_table = manifest_table( slurp( $man_file ) );
	if ( null === $man_table ) {
		add(
			'RT_GALLERY_NO_MANIFEST',
			'FAIL',
			'web-templates',
			'plantillas/' . $man_where . ' carries no table with a `Slug` column, so none of the ' . count( $man_used )
				. ' image slug(s) ' . $mockup_name . ' renders can be matched to anything — the manifest is prose, not a contract'
		);
		continue;
	}
	$man_by_slug = array();
	foreach ( $man_table['rows'] as $man_r ) {
		if ( '' !== $man_r['slug'] ) {
			$man_by_slug[ $man_r['slug'] ] = $man_r;
		}
	}
	$man_unlisted = array();
	foreach ( $man_used as $man_s ) {
		if ( ! isset( $man_by_slug[ $man_s ] ) ) {
			$man_unlisted[] = '`' . $man_s . '`';
		}
	}
	if ( array() !== $man_unlisted ) {
		add(
			'RT_GALLERY_NO_MANIFEST',
			'FAIL',
			'web-templates',
			$mockup_name . ' renders ' . implode( ', ', $man_unlisted ) . ' with no row in ' . basename( $man_file )
				. ' — the file name IS the attachment slug, so an image the manifest does not carry is one the operator cannot upload and es_photo() cannot resolve'
		);
	}
	$man_no_slug = array();
	$man_no_lic  = array();
	foreach ( $man_table['rows'] as $man_r ) {
		if ( '' === $man_r['slug'] ) {
			$man_no_slug[] = 'line ' . $man_r['line'];
			continue;
		}
		if ( '' === $man_r['licence'] ) {
			$man_no_lic[] = '`' . $man_r['slug'] . '` (line ' . $man_r['line'] . ')';
		}
	}
	if ( array() !== $man_no_slug ) {
		add(
			'RT_GALLERY_NO_MANIFEST',
			'FAIL',
			'web-templates',
			'plantillas/' . $man_where . ' has ' . count( $man_no_slug ) . ' row(s) with an empty Slug cell — ' . implode( ', ', $man_no_slug )
				. '. A row without a slug names no attachment, so it documents an image the build has no way to ask for'
		);
	}
	if ( array() !== $man_no_lic ) {
		add(
			'RT_GALLERY_NO_MANIFEST',
			'FAIL',
			'web-templates',
			'plantillas/' . $man_where . ' has ' . count( $man_no_lic ) . ' row(s) with no licence — ' . implode( ', ', $man_no_lic )
				. ( null === $man_table['licence_col'] ? '. The table carries no Licence column at all' : '' )
				. '. This repository is public under Apache-2.0 and its LICENSE hands every reader the right to redistribute what it contains;'
				. ' an image whose terms are recorded nowhere is a right nobody checked we had to give'
		);
	}
}

/* --------------------------------------------------- the builder's token layer
 *
 * The retired mockup axis rows asked whether the file a project is COPIED FROM could express an axis.
 * These two ask the same question one hop later, of the file that actually writes the site.
 * `elementor-core/SKILL.md` step 2 USED to tell the operator to "swap its palette/type constants",
 * and there were no constants of any kind in es-builder.php to swap — 51 colour literals, 9 font
 * strings and 5 shadows typed inline between the helpers instead. So every WordPress Orchestrator site shipped
 * the same green on the same white whatever the axis dialogue resolved, with every other row in
 * this audit green. That step now says "override es_tokens() — the one edit point" (67dcb45),
 * which is a real mechanism; the sentence above is history, quoted as history, because a comment
 * that quotes deleted text as current text sends the next reader looking for a string that is not
 * there. What these two rows guarantee is the half a SKILL.md sentence cannot: that the one edit
 * point stays the ONLY one.
 *
 * THE REGION, and why getting its START wrong makes the whole row useless. The scan runs from the
 * closing brace of es_tokens() to the end-of-visual-layer marker. Anchoring on the OPENING of
 * es_tokens() instead puts the token DECLARATIONS inside the scanned region, and every one of
 * those is a hex literal by definition — the check then reports ~21 findings against a perfectly
 * correct file, which is a check nobody keeps. Anchoring on some neighbouring helper's brace
 * instead (es_t(), es_fs(), es_sp()) breaks the first time a function is added next to it. So the
 * boundary depends on exactly one name — es_tokens — and that is the one name this row already
 * requires to exist, so it cannot be an incidental coupling. Everything else in the file, es_t()
 * included, is INSIDE the region and held to the rule.
 *
 * COMMENTS ARE NOT SCANNED, and that is a decision, not an oversight. A hex in a comment cannot
 * reach the emitted data — it is inert for the same reason the region below the END marker is
 * inert. Scanning them would FAIL es-builder.php today on a rationale comment that reads "…was
 * byte-identical only because both are #FFFFFF today", i.e. it would charge a file for explaining
 * itself, in a repo whose whole style is explaining itself. Stripping is done with PHP's OWN
 * lexer (php_code_lines()) rather than a regex: a hand-rolled stripper that treats `//` as a
 * comment opener blanks the rest of any line carrying a URL inside a string, and a real literal
 * after one would vanish — that is a genuine escape hatch, and tests/test-framework-audit.php
 * carries the fixture for it.
 *
 * SCOPE. It covers the assets/ of every skill that emits Elementor data — elementor-core,
 * elementor-theme-parts and woocommerce — which is all four builder assets. It used to cover
 * elementor-core alone, deliberately, because the other three carried 131 literals and no token
 * block, and a gate that is red for known, scheduled, un-started work is a gate people learn to
 * scroll past. They were migrated in Task 4 of
 * docs/superpowers/plans/2026-08-15-axes-reach-the-build.md, whose Step 2 makes widening this the
 * same task's job and not a follow-up, so the reservation is spent and the glob is open.
 *
 * Still a GLOB per skill and not four hardcoded filenames, for the same reason the
 * mockup walk is a glob: a SECOND asset dropped into any of those three assets/ directories without a token layer
 * is exactly the regression this row exists for, and a hardcoded name would not see it. The skill
 * LIST is explicit rather than one wildcard across every skill's assets, because that wider glob
 * also picks up framework-audit.php — this file — whose literal regexes and examples are not
 * colours a build emits, and which has no visual region to bound. divi-core has no assets/ and no PHP at
 * all; when it grows a di_* library, its directory joins this list.
 *
 * TWO SHAPES OF TOKEN LAYER, because there are two honest ways to have one:
 *   A. The file DECLARES es_tokens() — es-builder.php. Region starts at that function's closing
 *      brace, never at its opening line, or the 27 declarations inside it read as 27 literals.
 *   B. The file INHERITS it — the three siblings require es-builder.php and must not redeclare
 *      es_tokens() (PHP would fatal on the duplicate, and a second copy of the block is the drift
 *      this whole layer exists to end). Those files carry an explicit "start of the visual layer"
 *      marker instead, because their save pipeline sits ABOVE the visual code rather than below
 *      it and a region anchored on the top of the file would scan it.
 * A file with neither is a file where every colour is typed where it is used: RT_BUILDER_NO_TOKENS.
 */

/* Every line of $src with its PHP comments blanked out and the line NUMBERING preserved, so a
   finding's reported line still points at the real line of the real file. Uses token_get_all()
   — PHP's own lexer — so "is this a comment" is answered by the thing that decides it at runtime,
   never by a regex that cannot tell `'https://x'` from the start of one. A file that is not PHP
   at all lexes to one T_INLINE_HTML token and nothing is stripped, which errs toward scanning
   MORE, never less. */
function php_code_lines( $src ) {
	$out = '';
	foreach ( token_get_all( $src ) as $tk ) {
		if ( ! is_array( $tk ) ) {
			$out .= $tk;
			continue;
		}
		if ( T_COMMENT === $tk[0] || T_DOC_COMMENT === $tk[0] ) {
			$out .= str_repeat( "\n", substr_count( $tk[1], "\n" ) );
			continue;
		}
		$out .= $tk[1];
	}
	return explode( "\n", $out );
}

/* A token READ is not a literal, by definition — and two token NAMES are spelled exactly like the
   CSS values this row exists to catch. `es_t( 'ease' )` is the correct shape and `ease` is the
   keyword the widened rules below hunt; `es_t( 'transparent' )` is the correct shape and
   `transparent` is a named colour. Blanked to spaces of the SAME length before any pattern runs
   (so a real literal later on the line still reports the right line), which is why widening the
   keyword set cannot charge a file for reading its own tokens. Only `es_t( 'identifier' )` is
   blanked: a `#`, a bracket or an expression inside the parens is not a token name and stays
   visible to the scan. */
function es_blank_token_reads( $line ) {
	return preg_replace_callback(
		'/es_t\(\s*([\'"])[A-Za-z0-9_]+\1\s*\)/',
		function ( $m ) {
			return str_repeat( ' ', strlen( $m[0] ) );
		},
		$line
	);
}

$builder_end_marker   = 'end of the visual layer';
$builder_start_marker = 'start of the visual layer';
/* 3-to-8 hex covers every CSS form (#fff, #ffff, #ffffff, #ffffffff), not just the two the plan
   named — an alpha hex is as much a hardcoded colour as a plain one. The trailing boundary stops
   `#facade-panel` (a CSS id selector) from reading as a colour, and stops the six-digit form from
   also reporting its own first three digits as a second finding. rgba()/cubic-bezier() report
   their whole call when it closes on the same line, because "rgba(" alone is not a value a reader
   can go and look for. */
/* The lookBEHIND is what lets `es_rgba( es_t( 'accent' ), '0.07' )` through while
   `'rgba(15,169,104,0.07)'` still fails. Without it the bare `rgba|cubic-bezier` alternation
   matches inside the helper's own NAME, so the one shape this row wants to see — a veil derived
   from a token — reads as the literal it replaced, and the only way to satisfy the check would be
   to invent a named token per alpha. es-builder.php never hit this because its single es_rgba()
   call sits INSIDE es_tokens(), above the region; the three siblings make 13 such calls in-region.
   This narrows only by an identifier character before the name: `:rgba(`, `,rgba(`, ` rgba(` and
   `'rgba(` all still match, and a hand-typed colour is never preceded by [A-Za-z0-9_]. */
/* THE FUNCTION SET IS EVERY COLOUR AND TIMING FUNCTION CSS HAS, not the two the file happened to
   contain. `rgba` alone missed `rgb(` outright — the `a` was mandatory — and with it `hsl()`,
   `hsla()`, `hwb()`, `lab()`, `lch()`, `oklab()`, `oklch()` and `color-mix()`, which is the syntax
   design-system.md's own `accent-glow` elevation position is written in. `steps()` joins
   `cubic-bezier()` for the same reason on the motion side. `linear-gradient()` is deliberately NOT
   here: a gradient built out of token colours is the correct shape, and its colours are caught on
   their own.
   THE SPLIT HEX. `'#0FA' . '968'` was already caught (`#0FA` is a valid three-digit hex and the
   quote is not a hex character), but `'#0F' . 'A968'` was not, and neither was `'#' . '0FA968'` —
   the split point decided whether the rule saw anything, which is not a rule. A `#` with 0-2 hex
   digits sitting at the end of a string that is being concatenated is a colour torn in half; there
   is no other reason to write one. The `'(#' . $id . ')'` warnings that shape resembles all live
   BELOW the END marker, which is what that marker is for. */
$builder_literal_re = '/#[0-9A-Fa-f]{3,8}(?![0-9A-Za-z_-])|#[0-9A-Fa-f]{0,2}[\'"]\s*\.|(?<![0-9A-Za-z_])(?:rgba?|hsla?|hwb|lab|lch|oklab|oklch|color-mix|cubic-bezier|steps)\((?:[^)\n]{0,48}\))?/';

/* NAMED CSS COLOURS: the FULL 148-keyword list plus `transparent`, not a curated shortlist.
 *
 * The curated option — `white`, `black`, `transparent`, the three anybody actually types — is the
 * one that makes this row green for the least work, and it is wrong for the reason the row exists:
 * the literal that survives is always the one nobody thought of. `rebeccapurple` is not a colour a
 * careful author reaches for, which is exactly why a shortlist would never have carried it, and a
 * live probe put `rebeccapurple` inside a scanned region and watched all four suites and the audit
 * pass. A list that only contains the colours you predicted is a list that only catches the
 * mistakes you predicted. The full list costs one long string and never needs maintaining: CSS has
 * not added a colour keyword since `rebeccapurple` in 2014, and if it ever does, the two rules
 * below still catch it by shape and by key.
 *
 * THE PRICE, and how it is paid. `tan`, `peru`, `snow`, `linen`, `plum`, `gold` and `red` are also
 * ordinary words, and these files carry Spanish UI copy. So the match is anchored where a colour
 * ENDS a CSS value: the keyword must be followed (after optional space) by `;`, `,`, `!`, `}`, `)`,
 * a quote, or end of line. `background:rebeccapurple;`, `border:1px solid black;` and
 * `'title_color' => 'white'` all satisfy it; `tan pronto como` does not, because a Spanish word is
 * followed by another word.
 *
 * RESIDUAL, stated rather than discovered: a named colour in the MIDDLE of a shorthand it does not
 * end — `background:white url(x)` — is not caught, and a Spanish clause that happens to end on a
 * colour word before a comma would be a false FAIL. Both are the loud kind: the first is a miss the
 * `*_color` key rule below usually catches anyway, and the second fails visibly with the file and
 * line in the message, which beats a rule that quietly scans nothing. */
$builder_colour_names = 'aliceblue|antiquewhite|aquamarine|aqua|azure|beige|bisque|blanchedalmond|blueviolet|blue|brown|burlywood|cadetblue|chartreuse|chocolate|coral|cornflowerblue|cornsilk|crimson|cyan|darkblue|darkcyan|darkgoldenrod|darkgray|darkgreen|darkgrey|darkkhaki|darkmagenta|darkolivegreen|darkorange|darkorchid|darkred|darksalmon|darkseagreen|darkslateblue|darkslategray|darkslategrey|darkturquoise|darkviolet|deeppink|deepskyblue|dimgray|dimgrey|dodgerblue|firebrick|floralwhite|forestgreen|fuchsia|gainsboro|ghostwhite|goldenrod|gold|gray|greenyellow|green|grey|honeydew|hotpink|indianred|indigo|ivory|khaki|lavenderblush|lavender|lawngreen|lemonchiffon|lightblue|lightcoral|lightcyan|lightgoldenrodyellow|lightgray|lightgreen|lightgrey|lightpink|lightsalmon|lightseagreen|lightskyblue|lightslategray|lightslategrey|lightsteelblue|lightyellow|limegreen|lime|linen|magenta|maroon|mediumaquamarine|mediumblue|mediumorchid|mediumpurple|mediumseagreen|mediumslateblue|mediumspringgreen|mediumturquoise|mediumvioletred|midnightblue|mintcream|mistyrose|moccasin|navajowhite|navy|oldlace|olivedrab|olive|orangered|orange|orchid|palegoldenrod|palegreen|paleturquoise|palevioletred|papayawhip|peachpuff|peru|pink|plum|powderblue|purple|rebeccapurple|red|rosybrown|royalblue|saddlebrown|salmon|sandybrown|seagreen|seashell|sienna|silver|skyblue|slateblue|slategray|slategrey|snow|springgreen|steelblue|tan|teal|thistle|tomato|transparent|turquoise|violet|wheat|whitesmoke|white|yellowgreen|yellow|black';
$builder_named_re     = '/(?<![\w-])(?:' . $builder_colour_names . ')(?![\w-])(?=\s*(?:[;,!}\')"]|$))/i';

/* BARE TIMING KEYWORDS, anchored on the duration that always precedes one.
 *
 * This is the rule with seven live violations at the moment it was written: `transition:opacity
 * .28s ease,transform .28s ease` in the header, three more in the shop archive's pagination, and
 * two in the product page's add-to-cart — in the SAME declaration as `es_t('ease')`, so `transform`
 * eased on the house curve while `background-color` and `box-shadow` fell back to the browser
 * default. Two of the three sibling assets reached the motion axis at exactly 0%.
 *
 * Anchored on `<number>s`/`<number>ms` (or an explicit `timing-function:`) rather than matched as a
 * bare word, because `ease` and `linear` are too common to hunt loose — and because `es_t( 'ease' )`
 * is blanked before this runs, the correct shape can never trip it either way. */
$builder_timing_re = '/(?:[0-9]*\.?[0-9]+\s*m?s\s+|timing-function\s*:\s*)(?:ease-in-out|ease-in|ease-out|ease|linear|step-start|step-end)(?![\w-])/i';
/* A family typed as a STRING on a typography key. `=> es_t( 'font_body' )` has no quote after the
   arrow and is the shape this row wants; `=> 'Manrope'` is the shape it exists to stop. \w* so a
   `_tablet`/`_mobile` responsive variant cannot slip past the same rule. */
$builder_family_re = '/typography_font_family\w*[\'"]?\s*=>\s*([\'"])(.*?)\1/';

/* THE FORMAT-BLIND BACKSTOP: a settings key whose name ends in `color` may not be fed a quoted
   string at all. Every rule above hunts a SHAPE, and a shape list is only ever as complete as the
   CSS spec was on the day it was written — `oklch()` did not exist when `rgba()` was the whole of
   this check. This one asks the other question: not "does this look like a colour" but "is this
   the slot a colour goes in". `'title_color' => <anything quoted>` is a hardcoded colour whatever
   syntax it is written in, including syntaxes nobody has invented yet.
   The exception list is four CSS-wide keywords and one Elementor control mode, and every one of
   them is a DECISION rather than a colour: `custom` is how Elementor is told a sibling key carries
   the value (es-theme-parts.example.php:579 uses it), and `initial`/`inherit`/`unset`/`revert`
   defer to the cascade. Nothing that names a colour is on it. */
$builder_colour_key_re    = '/[\'"]?(\w*colou?r)[\'"]?\s*=>\s*([\'"])(.*?)\2/i';
$builder_colour_key_allow = array( 'custom', 'initial', 'inherit', 'unset', 'revert', '' );

/* The runtime half of RT_FONT_NO_SERVING_PATH, named once. A rename of the builder's check makes
   this row fire, which is the correct outcome: a renamed check is an unwired one until its call
   sites and its documentation follow it. */
$font_check_fn         = 'es_font_serving_check';
$font_check_documented = (bool) preg_match( '/(?<![\w])' . preg_quote( $font_check_fn, '/' ) . '(?![\w])/', $prose );
/* Same list the builder's own es_font_system_faces() carries. Two copies is one too many and it is
   named as debt rather than hidden: this script may not require a builder asset (it audits trees it
   must not execute), so the honest options were a duplicate list or no check at all. */
$builder_system_faces = array(
	'',
	'inherit',
	'initial',
	'serif',
	'sans-serif',
	'monospace',
	'cursive',
	'fantasy',
	'system-ui',
	'-apple-system',
	'blinkmacsystemfont',
	'arial',
	'helvetica',
	'helvetica neue',
	'georgia',
	'times',
	'times new roman',
	'courier',
	'courier new',
	'verdana',
	'tahoma',
	'trebuchet ms',
	'palatino',
	'garamond',
	'impact',
	'arial black',
	'lucida sans',
	'segoe ui',
);

$builder_assets = array();
foreach ( array( 'elementor-core', 'elementor-theme-parts', 'woocommerce' ) as $builder_dir ) {
	$builder_found = glob( $root . '/skills/' . $builder_dir . '/assets/*.php' );
	if ( is_array( $builder_found ) ) {
		$builder_assets = array_merge( $builder_assets, $builder_found );
	}
}
sort( $builder_assets );
foreach ( $builder_assets as $builder_path ) {
	$builder_name  = basename( $builder_path );
	$builder_skill = basename( dirname( dirname( $builder_path ) ) );
	$builder_src   = slurp( $builder_path );
	$builder_raw   = explode( "\n", $builder_src );
	$builder_code  = php_code_lines( $builder_src );

	$tokens_open = -1;
	foreach ( $builder_code as $bi => $bl ) {
		if ( preg_match( '/^\s*function\s+es_tokens\s*\(/', $bl ) ) {
			$tokens_open = $bi;
			break;
		}
	}
	$region_start = -1;
	if ( -1 !== $tokens_open ) {
		/* SHAPE A — the file declares the block. The top-level closing brace, found by column: a
		   `}` alone on a line ends the function, an indented one closes an inner block. */
		for ( $bi = $tokens_open + 1, $bn = count( $builder_code ); $bi < $bn; $bi++ ) {
			if ( '}' === rtrim( $builder_code[ $bi ] ) ) {
				$region_start = $bi;
				break;
			}
		}
		$builder_why_start = 'es_tokens() never closes on a line of its own, so where the declarations stop cannot be read';
	} else {
		/* SHAPE B — the file inherits the block. It must actually depend on the file that holds
		   it: the dependency is named in CODE, not in a comment, and the three siblings name it
		   inside a `foreach ( array( 'es-builder.php' ) ... )` guard rather than on the require
		   line itself, so this asks whether the code mentions it at all rather than pattern-
		   matching a require that is not there. */
		if ( false === strpos( implode( "\n", $builder_code ), 'es-builder.php' ) ) {
			add(
				'RT_BUILDER_NO_TOKENS',
				'FAIL',
				$builder_skill,
				'assets/' . $builder_name . ' declares no es_tokens() and does not require es-builder.php, which holds the only one'
					. ' — every colour, family and shadow in it is typed where it is used, so the site it builds cannot be re-skinned'
					. ' from one edit point and ships the framework default'
			);
			continue;
		}
		foreach ( $builder_raw as $bi => $bl ) {
			if ( false !== strpos( $bl, $builder_start_marker ) ) {
				$region_start = $bi;
				break;
			}
		}
		$builder_why_start = 'it inherits es_tokens() from es-builder.php but carries no "' . $builder_start_marker
			. '" marker, so where its visual region BEGINS cannot be read — and in these files the save pipeline sits above the visual code, so "the top" is the wrong answer';
	}
	/* Both markers are located in the RAW lines: they are comments, and php_code_lines() has just
	   blanked them out of $builder_code.
	   The END marker takes the LAST match, not the first, and that is load-bearing. Every start
	   marker naturally wants to say "…down to the end of the visual layer marker", and on a FIRST
	   match that prose collapses the region to the four lines of its own comment — a check that
	   passes because it scanned almost nothing, which is the worst failure a check has. Taking the
	   last match makes the same mistake widen the region instead, and a region that is too wide
	   fails LOUDLY on the first inert `#` it meets. Loud beats silently vacuous. */
	$region_end = -1;
	foreach ( $builder_raw as $bi => $bl ) {
		if ( false !== strpos( $bl, $builder_end_marker ) ) {
			$region_end = $bi;
		}
	}
	if ( -1 === $region_start || -1 === $region_end || $region_end <= $region_start ) {
		if ( -1 === $region_start ) {
			$why = $builder_why_start;
		} elseif ( -1 === $region_end ) {
			$why = 'it carries no "' . $builder_end_marker . '" marker';
		} else {
			$why = 'its "' . $builder_end_marker . '" marker sits ABOVE where the region starts, leaving nothing between them';
		}
		add(
			'RT_BUILDER_NO_TOKENS',
			'FAIL',
			$builder_skill,
			'assets/' . $builder_name . ' has an es_tokens() block no scan can be bounded by: ' . $why
				. ' — an unbounded region is an unscannable one, and a region nothing scans is a rule nothing enforces'
		);
		continue;
	}

	$builder_hits = array();
	for ( $bi = $region_start + 1; $bi < $region_end; $bi++ ) {
		/* Token reads blanked FIRST and once, so every pattern below sees the same line and none of
		   them can charge `es_t( 'ease' )` or `es_t( 'transparent' )` for spelling a CSS keyword. */
		$bline = es_blank_token_reads( $builder_code[ $bi ] );
		foreach ( array( $builder_literal_re, $builder_named_re, $builder_timing_re ) as $bre ) {
			if ( preg_match_all( $bre, $bline, $bm ) ) {
				foreach ( $bm[0] as $bhit ) {
					$builder_hits[] = $builder_name . ':' . ( $bi + 1 ) . ' → ' . trim( $bhit );
				}
			}
		}
		if ( preg_match( $builder_family_re, $bline, $bfm ) ) {
			$builder_hits[] = $builder_name . ':' . ( $bi + 1 ) . ' → typography_font_family ' . $bfm[1] . $bfm[2] . $bfm[1];
		}
		if ( preg_match_all( $builder_colour_key_re, $bline, $bkm, PREG_SET_ORDER ) ) {
			foreach ( $bkm as $bk ) {
				if ( ! in_array( strtolower( $bk[3] ), $builder_colour_key_allow, true ) ) {
					$builder_hits[] = $builder_name . ':' . ( $bi + 1 ) . ' → ' . $bk[1] . ' ' . $bk[2] . $bk[3] . $bk[2];
				}
			}
		}
	}
	/* The same literal can satisfy two rules — `'title_color' => '#0FA968'` is both a hex and a
	   colour key — and reporting it twice makes the count read as twice the debt. */
	$builder_hits = array_values( array_unique( $builder_hits ) );
	if ( array() !== $builder_hits ) {
		/* Every hit is named with its own line and its own value. "hay un literal" sends a reader
		   to eye-scan 500 lines; "es-builder.php:388 → #CBD0CB" is one keystroke away from fixed.
		   Nothing is truncated: a long row is the honest size of the debt. */
		add(
			'RT_BUILDER_HARDCODED_TOKEN',
			'FAIL',
			$builder_skill,
			'assets/' . $builder_name . ' types ' . count( $builder_hits ) . ' visual literal(s) between es_tokens() and the "'
				. $builder_end_marker . '" marker: ' . implode( ', ', $builder_hits )
				. ' — each one is a value the axis dialogue can no longer move, so the site reverts to the framework default wherever it is read'
		);
	}

	/* ---------------------------------------------- RT_FONT_NO_SERVING_PATH
	 *
	 * The token block writes `font_head => 'Space Grotesk'` into every heading this framework emits,
	 * as `typography_font_family`. Nothing in the framework ever made that family EXIST on the site:
	 * no `@font-face`, no enqueue, no registration. The scale axis moved every size correctly while
	 * the typeface may never have arrived, and every row in this audit stayed green.
	 *
	 * WHAT THIS ROW CAN HONESTLY ASSERT, and the limit is stated in its own message rather than
	 * discovered later: the font FILES live on a WordPress site, not in this repository, so nothing
	 * here can know whether they are served. What a repo-time check CAN know is whether the
	 * framework is equipped to find out and to tell someone — that the build carries a serving check,
	 * that the check is actually called, and that a human who sees its warning has a documented
	 * procedure to follow. Those three are the wiring, and the wiring is what rots silently.
	 *
	 * Scoped to the DECLARATION site: the region scanned here is the token block itself, which is
	 * exactly the region RT_BUILDER_HARDCODED_TOKEN deliberately does not scan, so the two rows read
	 * disjoint ground and one literal can never produce two rows. A sibling asset that inherits
	 * es_tokens() declares no family of its own and is not asked. */
	if ( -1 !== $tokens_open ) {
		$builder_families = array();
		for ( $bi = $tokens_open; $bi <= $region_start; $bi++ ) {
			if ( preg_match_all( '/[\'"]font_\w+[\'"]\s*=>\s*[\'"]([^\'"]+)[\'"]/', $builder_code[ $bi ], $bfam ) ) {
				foreach ( $bfam[1] as $bfamily ) {
					$bface = strtolower( trim( explode( ',', $bfamily )[0], " \t'\"" ) );
					/* Generic stacks and the faces a browser already has need no serving path, and a
					   row about `Georgia` is a row people learn to scroll past. Same list the runtime
					   check skips, and it has to stay the same list or the two disagree about what a
					   font even is. */
					if ( ! in_array( $bface, $builder_system_faces, true ) ) {
						$builder_families[ $bface ] = trim( explode( ',', $bfamily )[0], " \t'\"" );
					}
				}
			}
		}
		if ( $builder_families ) {
			$builder_declares_check = false;
			$builder_calls_check    = false;
			foreach ( $builder_code as $bl ) {
				if ( preg_match( '/^\s*function\s+' . preg_quote( $font_check_fn, '/' ) . '\s*\(/', $bl ) ) {
					$builder_declares_check = true;
					continue;
				}
				if ( false !== strpos( $bl, $font_check_fn . '(' ) ) {
					$builder_calls_check = true;
				}
			}
			$builder_font_why = array();
			if ( ! $builder_declares_check ) {
				$builder_font_why[] = 'it declares no ' . $font_check_fn . '()';
			}
			if ( ! $builder_calls_check ) {
				$builder_font_why[] = 'nothing in it CALLS ' . $font_check_fn . '(), so the check is a function no build reaches';
			}
			if ( ! $font_check_documented ) {
				$builder_font_why[] = 'no .md in this tree names ' . $font_check_fn
					. ', so an operator who sees the warning has nowhere to go — a warning with no procedure behind it is one more line to scroll past';
			}
			if ( $builder_font_why ) {
				add(
					'RT_FONT_NO_SERVING_PATH',
					'FAIL',
					$builder_skill,
					'assets/' . $builder_name . ' names ' . implode( ' and ', $builder_families )
						. ' in its token block, and ' . implode( '; ', $builder_font_why )
						. ' — this repo CANNOT know whether those font files are served, because they live on a WordPress site and not here;'
						. ' what it can require is that the build ASKS the site and that the answer has a documented fix'
				);
			}
		}
	}
}

/* Regression guard: the exact drift that made every build converge on the same look — a single
   named font example with no alternative anywhere in the skill. */
$dt_file = $root . '/skills/ux-design-system/references/design-tokens.md';
if ( file_exists( $dt_file ) ) {
	$dt_src = slurp( $dt_file );
	foreach ( array( 'Space Grotesk', 'Manrope' ) as $hardcoded ) {
		if ( false !== strpos( $dt_src, $hardcoded ) ) {
			add( 'RT_TOKENS_HARDCODED_FONT', 'FAIL', 'ux-design-system', 'design-tokens.md still hardcodes "' . $hardcoded . '" as an example font — move concrete pairings into the Plantilla that owns them (references/plantillas/<slug>/ficha.md)' );
		}
	}
}

/* ------------------------------------------------------------- offline suite */

if ( ! glob( $root . '/tests/*.php' ) ) {
	add( 'RT_NO_OFFLINE_TESTS', 'FAIL', 'tests', 'no offline test suite — the code that enforces the rules has nothing enforcing it' );
}

/* --------------------------------------------------- phantom verifiers */

/* Prose that names a row type this audit does not declare.
 *
 * RT_ROWTYPE_UNDOCUMENTED catches a row DECLARED and never written down. This is its mirror: a
 * row WRITTEN DOWN and never declared — a verifier promised in prose and never built. Between
 * them the pair is closed in both directions, which one alone never was.
 *
 * Found live, and that is why it exists: es-builder.php's manifest docblock claimed
 * `RT_REPLAY_NO_FINGERPRINT` FAILed for as long as nothing recorded the build fingerprint. That
 * row did not exist, and could not have — this audit cannot read a live WordPress option
 * (CONTRIBUTING.md says so in as many words). The sentence read as a guarantee and was a wish, in
 * a PHP docblock, which is a surface no other check in this file looks at.
 *
 * SCOPE, deliberately narrow: skills/ and agents/ only, the files that instruct the model. docs/
 * is history — a plan naming a row that was proposed and never built is an accurate record, not a
 * false claim, and flagging it would be noise that teaches the reader to skip this row.
 *
 * Two files are exempt BY EXACT PATH, never by pattern: a pattern exemption is silenceable by
 * moving text into a matching filename. This file declares every id, and the audit's own test
 * suite invents ids on purpose.
 *
 * One row per id per file: a helper cited on ten lines is one missing verifier, not ten. */
$phantom_slash  = chr( 92 );
$phantom_root   = str_replace( $phantom_slash, '/', $root );
$phantom_exempt = array(
	$phantom_root . '/skills/framework-audit/assets/framework-audit.php',
	$phantom_root . '/tests/test-framework-audit.php',
);
foreach ( array( $root . '/skills', $root . '/agents' ) as $proot ) {
	if ( ! is_dir( $proot ) ) {
		continue;
	}
	$pwalk = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $proot, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $pwalk as $pf ) {
		if ( ! $pf->isFile() || ! in_array( strtolower( $pf->getExtension() ), array( 'php', 'md' ), true ) ) {
			continue;
		}
		$ppath = str_replace( $phantom_slash, '/', $pf->getPathname() );
		if ( in_array( $ppath, $phantom_exempt, true ) ) {
			continue;
		}
		$pseen = array();
		foreach ( explode( "\n", slurp( $ppath ) ) as $pi => $pline ) {
			if ( ! preg_match_all( '/`(RT_[A-Z0-9_]+)`/', $pline, $pm ) ) {
				continue;
			}
			foreach ( $pm[1] as $pid ) {
				if ( array_key_exists( $pid, ROW_TYPES ) || isset( $pseen[ $pid ] ) ) {
					continue;
				}
				$pseen[ $pid ] = true;
				add(
					'RT_ROWTYPE_PHANTOM',
					'FAIL',
					substr( $ppath, strlen( $phantom_root ) + 1 ),
					'line ' . ( $pi + 1 ) . ' cites ' . $pid . ', which ROW_TYPES does not declare — a verifier named in prose and never built. Build the row, or say what actually checks this'
				);
			}
		}
	}
}


/* ------------------------------------------------- migration exclusions */

/* A migration document that never names the sandbox directory.
 *
 * es_sandbox_dir() is WP_CONTENT_DIR . '/novamira-sandbox' — INSIDE wp-content. So a literal
 * "copy wp-content to production" ships it: es-builder.php, and anything pasted in to debug
 * something once, land on the client's server executable and reachable by URL. It is the one
 * exclusion whose absence is catastrophic rather than untidy, so its presence stops being
 * something a writer has to remember.
 *
 * WHAT THIS PROVES, and what it does not: that the document NAMES the directory. It cannot know
 * whether any archive actually excluded it — that is qa-review house-rules row 33, which reads the
 * archive's own file listing. A rule that claimed more than it reads would be the exact defect
 * RT_ROWTYPE_PHANTOM exists to catch. */
foreach ( glob( $root . '/skills/*/references/migration.md' ) as $mig ) {
	if ( false === strpos( slurp( $mig ), 'novamira-sandbox' ) ) {
		$mig_skill = basename( dirname( dirname( $mig ) ) );
		add( 'RT_MIGRATION_NO_EXCLUDE', 'FAIL', $mig_skill, 'references/migration.md never names novamira-sandbox — the sandbox lives inside wp-content, so a copy that does not exclude it puts executable files on the client site' );
	}
}

/* ------------------------------------------------- gate self-registration */

/* CONTRIBUTING.md's testing gate is a static, hand-typed && chain, not a glob — the exact hole
 * that lets a brand-new tests/test-*.php file silently never run. This turns that discipline
 * into a verifier: every tests/test-*.php must be named on the gate line, or it FAILs. */
$contributing_file = $root . '/CONTRIBUTING.md';
if ( file_exists( $contributing_file ) ) {
	$contrib_src = slurp( $contributing_file );
	foreach ( glob( $root . '/tests/test-*.php' ) as $t ) {
		$base = basename( $t );
		if ( false === strpos( $contrib_src, 'tests/' . $base ) ) {
			add( 'RT_GATE_LINE_UNREGISTERED', 'FAIL', 'CONTRIBUTING.md', 'gate line never runs "' . $base . '" — a new test file must join the && chain or it silently never runs' );
		}
	}

	/* ------------------------------------------------ row-type doc-sync (D1'.7)
	 *
	 * Mirrors the gate-self-registration check right above: a new FAIL/WARN/JUDGE mode cannot ship
	 * without a line in CONTRIBUTING.md naming it. Reuses the same ROW_TYPES registry the coverage
	 * assertion in tests/test-framework-audit.php reads via --emit-row-types, so there is exactly
	 * one place a row type is declared, never two that can drift apart. */
	foreach ( ROW_TYPES as $rt_id => $rt_desc ) {
		if ( false === strpos( $contrib_src, $rt_id ) ) {
			add( 'RT_ROWTYPE_UNDOCUMENTED', 'FAIL', 'CONTRIBUTING.md', 'row type "' . $rt_id . '" is declared in ROW_TYPES but never documented in CONTRIBUTING.md' );
		}
	}
}

/* -------------------------------------------------------------- report */

/* $r is now [ id, level, where, msg ] — id threaded through for --row-types, ignored otherwise. */
$order = array( 'FAIL' => 0, 'WARN' => 1, 'JUDGE' => 2 );
usort(
	$rows,
	function ( $a, $b ) use ( $order ) {
		return $order[ $a[1] ] <=> $order[ $b[1] ] ?: strcmp( $a[2], $b[2] );
	}
);

$n = array( 'FAIL' => 0, 'WARN' => 0, 'JUDGE' => 0 );
foreach ( $rows as $r ) {
	list( $rt_id, $level, $where, $msg ) = $r;
	$n[ $level ]++;
	if ( $show_row_types ) {
		printf( "%-28s  %-5s  %-22s  %s\n", $rt_id, $level, $where, $msg );
	} else {
		printf( "%-5s  %-22s  %s\n", $level, $where, $msg );
	}
}
if ( ! $rows ) {
	echo "nothing to report\n";
}

/* Stdout contract, and it has a reader: `fx_counts()` in tests/test-framework-audit.php parses
   this exact line with /(\d+) FAIL \/ (\d+) WARN \/ (\d+) JUDGE/. Reword the format without
   updating that regex and every assertion keyed off these counts stops testing anything —
   quietly, which is the failure this whole file exists to catch. */
printf(
	"\n%d FAIL / %d WARN / %d JUDGE across %d skills + %d agent(s)\n",
	$n['FAIL'],
	$n['WARN'],
	$n['JUDGE'],
	count( $skill_dirs ),
	count( glob( $root . '/agents/*.md' ) )
);
if ( $n['JUDGE'] ) {
	echo "JUDGE rows are NOT passes. A model has to read each one and decide whether the rule\n"
		. "really has no verifier or the heuristic simply missed it — see skills/framework-audit/SKILL.md.\n";
}

exit( ( $n['FAIL'] || ( $strict && $n['WARN'] ) ) ? 1 : 0 );
