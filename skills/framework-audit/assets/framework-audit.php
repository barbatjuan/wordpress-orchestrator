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
	'RT_MARKER_TRAILING_TEXT'    => 'FAIL  — text follows a verifier marker\'s closing paren',
	'RT_MARKER_EMPTY'            => 'FAIL  — a verifier marker\'s payload is empty',
	'RT_MARKER_STOPWORD'         => 'FAIL  — a verifier marker\'s payload is a stop-word placeholder',
	'RT_MARKER_TOO_SHORT'        => 'FAIL  — a verifier marker\'s payload is under 12 characters',
	'RT_MARKER_OVERSIZE'         => 'FAIL  — a verifier marker\'s payload is over the 40-word cap',
	'RT_MARKER_TARGET_MISSING'   => 'FAIL  — a "(verifier: …)" marker names a target that does not exist',
	'RT_MARKER_MISLABEL'         => 'JUDGE — a "(no verifier: …)" marker names a target that DOES exist',
	'RT_MARKER_PROSE_ONLY'       => 'JUDGE — a "(verifier: …)" marker names no locatable target',
	'RT_MARKER_OUTSIDE_RULES'    => 'WARN  — a verifier-marker-shaped line sits outside "## Hard Rules"',
	'RT_ERRORLOG_NO_STDOUT'      => 'FAIL  — an error_log call has no paired stdout channel',
	'RT_HELPER_UNROUTABLE'       => 'WARN  — an asset function no asset calls is named by no markdown either',
	'RT_WRITE_NOT_LISTED'        => 'FAIL  — code writes to WordPress but the skill is missing from $WRITE_CAPABLE',
	'RT_AGENT_CODE_BLOCK'        => 'FAIL  — an agent markdown file contains a code block',
	'RT_AGENT_ROUTE_MISSING'     => 'FAIL  — an agent routes to a skill that does not exist',
	'RT_AGENT_SKILL_UNMENTIONED' => 'WARN  — an agent never mentions an existing skill',
	'RT_HOUSERULES_NO_VERDICT'   => 'FAIL  — a house-rules.md row has no verdict source',
	'RT_HOUSERULES_MISSING'      => 'FAIL  — qa-review/references/house-rules.md is missing',
	'RT_QA_NO_AXIS_CHECK'        => 'FAIL  — the house rules never name an axis declaration the mockup gate demands',
	'RT_NO_OFFLINE_TESTS'        => 'FAIL  — no offline test suite under tests/',
	'RT_GATE_LINE_UNREGISTERED'  => 'FAIL  — a tests/test-*.php file is absent from the CONTRIBUTING.md gate line',
	'RT_ROWTYPE_UNDOCUMENTED'    => 'FAIL  — a ROW_TYPES ID is not listed in CONTRIBUTING.md',
	'RT_PERS_CATALOG_MISSING'    => 'FAIL  — ux-design-system/references/style-catalog/ has no STY-*.md entries',
	'RT_PERS_MISSING_FIELD'      => 'FAIL  — a STY-*.md entry is missing a required field',
	'RT_PERS_DUPLICATE_ID'       => 'FAIL  — two style-catalog files (STY-*.md or BSP-*.md) declare the same ID',
	'RT_STYLE_TOO_SIMILAR'       => 'FAIL  — two catalog entries share more than two axis positions',
	'RT_PERS_BAD_AXIS'           => 'FAIL  — a style or a ROUTE-BESPOKE declaration names an axis position no axis defines',
	'RT_TPL_TOO_SIMILAR'         => 'FAIL  — two archetypes of one family share more than half of their combined section inventory',
	'RT_TPL_NO_WIREFRAME'        => 'FAIL  — a TPL-*.md wireframe is not fully readable: no fenced block, no COMP-* id in it, or a row carrying no id',
	'RT_TPL_UNROUTABLE'          => 'FAIL  — a TPL-*.md exists that recommender.md or its folder _README.md never names, so nothing can route a client to it',
	'RT_TOKENS_HARDCODED_FONT'   => 'FAIL  — design-tokens.md still hardcodes an example font pairing',
	'RT_CATALOG_UNMENTIONED'     => 'FAIL  — ux-design-system/SKILL.md never mentions the style-catalog/ directory',
	'RT_UXDS_NO_AXIS_STEP'       => 'FAIL  — ux-design-system/SKILL.md has no axis-resolving dialogue step',
	'RT_AXIS_VALUE_MISSING'      => 'FAIL  — an axis position\'s own table row in design-system.md carries no token-shaped value',
	'RT_AXIS_BLUEPRINT_MISSING'  => 'FAIL  — an axis position names a blueprint layout-patterns.md never defines',
	'RT_PROOF_NOT_DISTINCT'      => 'FAIL  — the two proof mockups do not differ on enough axes',
	'RT_PROOF_COPY_DIFFERS'      => 'FAIL  — the two proof mockups do not render the same copy',
	'RT_MOCKUP_NO_AXES'          => 'FAIL  — an html-mockup asset declares no perceptual-axis tokens',
	'RT_MOCKUP_ANCHOR_UNDECLARED' => 'FAIL  — a starting or proof mockup does not say which anchor it is pointed at',
	'RT_MOCKUP_AXES_MISMATCH'    => 'FAIL  — a mockup\'s axis labels are not the ones its declared anchor holds',
	'RT_MOCKUP_DISCLOSURE_STATE' => 'FAIL  — a disclosure block is not built from <details>, or does not open exactly its first row',
	'RT_MOCKUP_GRID_AUTOFILL'    => 'FAIL  — a mockup grid uses repeat(auto-fill) with no justification beside it, so it reserves columns for elements that do not exist',
	'RT_MOCKUP_FONT_NOT_EMBEDDED' => 'FAIL  — an html-mockup asset names a font family it does not embed',
	'RT_MOCKUP_BLEED_FIXED_BAND' => 'FAIL  — a mockup bleeds to `full-end` but pins --content-width at a fixed length, leaving the gutter beside the bleed unbounded',
	'RT_MOCKUP_BLEED_NOT_MEDIA'  => 'FAIL  — a mockup sends something other than media to the viewport glass: a card, a run of copy or, worst, a form control',
	'RT_GALLERY_NOT_DISTINCT'    => 'FAIL  — two gallery strips are not two cards: a repeated pair, or one archetype under two anchors that barely differ',
	'RT_GALLERY_NO_MANIFEST'     => 'FAIL  — a gallery asset renders an image no manifest row carries a slug and a licence for',
	'RT_GALLERY_ONE_SHOOT'       => 'FAIL  — one photo shoot supplies more of the image set than the manifest\'s own register table claims distinct looks',
	'RT_GALLERY_NOT_BUILT'       => 'FAIL  — the gallery generator is present but its index.html output is not: the tree was never built',
	'RT_CHASSIS_NOT_BUILT'       => 'FAIL  — the gallery generator is present but a client chassis output is not: the tree was never built',
	'RT_GALLERY_STALE'           => 'FAIL  — the gallery output records no input fingerprint, or one that no longer matches the inputs on disk',
	'RT_BUILDER_NO_TOKENS'       => 'FAIL  — a builder asset has no es_tokens() block a scan can be bounded by',
	'RT_BUILDER_HARDCODED_TOKEN' => 'FAIL  — a builder asset types a visual literal outside its token block',
	'RT_FONT_NO_SERVING_PATH'    => 'FAIL  — a builder asset names a font family and nothing warns or documents how it gets served',
	'RT_STYLE_REPEATS_RECENT'    => 'WARN  — shipped-log.md: the newest delivery\'s style also appears among the 4 before it, within the last 5',
	'RT_STYLE_UNRESOLVED_DEFAULT' => 'FAIL  — shipped-log.md: a delivery names its chassis default but no resolved style — nobody was asked, or the answer never reached the ledger',
	'RT_STYLE_PRECHARGE_UNSHIPPED' => 'FAIL  — shipped-log.md: a delivery\'s resolved style declares a toggle precharge the ledger does not show shipped at that value',
	'RT_BESPOKE_UNDECLARED'      => 'FAIL  — a BSP-*.md ROUTE-BESPOKE declaration names no position for one of the 8 axes, or declares no wireframe',
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
$WRITE_CAPABLE = array( 'elementor-core', 'divi-core', 'woocommerce', 'wordpress-seo', 'wordpress-performance', 'wordpress-forms', 'wordpress-legal', 'elementor-theme-parts' );

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
 * Marker shape: own-line, terminal, case-sensitive — `(verifier: …)` or `(no verifier: …)`. The
 * token must OPEN the line; its closing `)` must be the LAST character of the bullet. Parsed from
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
		'terminal' => ( $close === strlen( $tail ) - 1 ),
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
 * The check this feeds used to glob one level and `continue` on directories, so 21 files under
 * web-templates/references/templates/ were not audited at all — the deepest and least-visited
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
 * have added 21 false rows: almost nothing in this repo is cited by its full filename. An archetype
 * is cited by its family (`TPL-C-01`, never the whole slug), a directory is cited as a directory,
 * and ten page archetypes are cited only by the README that indexes them. A pointer at a directory
 * reaches that directory's DIRECT children and no deeper — "look in pages/" tells you to open
 * pages/, where the README tells you the rest; that is exactly the hop the check must require. */
function file_handles( $rel, array $ambiguous = array() ) {
	$base = basename( $rel );
	$q    = function ( $s ) {
		return preg_quote( $s, '#' );
	};
	/* Boundary-anchored patterns, never bare substrings. A citation BEGINS where a filename
	   begins: unanchored, "gotchas.md" credited "a.md" and "TPL-P-11" credited "TPL-P-1", so a
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
	/* The family prefix keeps a trailing-digit guard only: "TPL-C-01" is legitimately followed by
	   "-services-leadgen.md" in a filename and by ".." in a range, but never by another digit. */
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
		if ( ! $mp['terminal'] ) {
			if ( $report ) {
				add( 'RT_MARKER_TRAILING_TEXT', 'FAIL', $where, marker_short( $rule ) . '… — text follows the closing ")": the marker must be the last thing in the rule' );
			}
			continue;
		}
		/* Structurally valid (closed && terminal) regardless of what its payload says below — free
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
	$hard_rules_block = null;
	if ( preg_match( '/^## Hard Rules\n(.*?)(?=\n## |\z)/ms', $body, $hr ) ) {
		$hard_rules_block = $hr[1];
	}
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

	/* A marker-shaped OPENER line outside "## Hard Rules" is not free: markers are provenance for
	   the rule they document, not a general licence, so a line shaped like one anywhere else in
	   the body is WARN'd and stays counted toward the budget below. */
	$rest_of_body = ( null !== $hard_rules_block ) ? str_replace( $hard_rules_block, '', $body ) : $body;
	foreach ( explode( "\n", $rest_of_body ) as $ln ) {
		if ( preg_match( '/^[ \t]*\((no[ \t]+)?verifier:/', $ln ) ) {
			add( 'RT_MARKER_OUTSIDE_RULES', 'WARN', $name, 'verifier-marker-shaped line outside "## Hard Rules": "' . trim( $ln ) . '"' );
		}
	}

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

/**
 * The custom properties that carry a perceptual-axis POSITION as a value.
 *
 * ONE list, two consumers, and that is the whole reason it is a function rather than two literals.
 * RT_MOCKUP_NO_AXES asks whether the file a project is copied FROM declares them; RT_QA_NO_AXIS_CHECK
 * asks whether the gate that verifies the FINISHED site names them. A sixth axis property added here
 * is demanded of both ends at once — and the two ends drifting apart is exactly the defect this
 * pair exists to close, since `html-mockup/SKILL.md` spent a whole branch telling the operator its
 * approved output was "the visual contract qa-review checks the build against" while qa-review
 * contained zero mentions of the mockup, the contract or any axis.
 *
 * Composition is deliberately NOT here: its position is a layout rule rather than a number, so it
 * has no custom property and both consumers match its `LP-*` blueprint marker separately.
 */
function axis_declarations() {
	return array( '--type-ratio', '--display-lh', '--fs-h1-max', '--sp-scale', '--elev-rest' );
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

	/* Naming, not automating, is what this asks — and the distinction is the point. An axis
	   qa-review can only hand to the user's eyes still has to be NAMED as unverifiable, because the
	   failure being closed is a SILENT omission: composition was not declared out of scope, it was
	   simply never mentioned, and a reader who trusts the promise stops looking. A row that says
	   "composition: eyes" is an honest gate; a row that says nothing is a gap wearing a green tick.
	   This is a documentation-coherence check of the same strength as RT_CATALOG_UNMENTIONED — it
	   proves the gate names each axis, never that the method behind it works. */
	$hr_text    = slurp( $hr_file );
	$hr_missing = array();
	foreach ( axis_declarations() as $hr_prop ) {
		if ( false === strpos( $hr_text, $hr_prop ) ) {
			$hr_missing[] = $hr_prop;
		}
	}
	if ( ! preg_match( '/\bLP-[A-Z0-9-]+/', $hr_text ) ) {
		$hr_missing[] = 'the composition blueprint (LP-*)';
	}
	if ( array() !== $hr_missing ) {
		add(
			'RT_QA_NO_AXIS_CHECK',
			'FAIL',
			'qa-review',
			'house-rules.md never names ' . implode( ', ', $hr_missing )
				. ' — html-mockup/SKILL.md promises its approved output is the visual contract qa-review checks the build against, and an axis the gate does not name is a promise with nothing behind it'
		);
	}
} else {
	add( 'RT_HOUSERULES_MISSING', 'FAIL', 'qa-review', 'references/house-rules.md is missing — the house rules have no gate' );
}

/* --------------------------------------- ux-design-system style catalog */

/* style-catalog PR 4c retired `design-personalities.md` (the single multi-block file this section
   used to split on `### `PERS-*`` headings) in favour of one `STY-*.md` file per entry under
   `references/style-catalog/`, matching what the real catalog has looked like since PR 4a. There
   is NO required-membership list here, unlike the old $PERS_IDS before it: the style-catalog spec
   ("Exactly 8 `STY-*` Entries Ship In V1", scenario "No automated size floor/ceiling") states
   plainly that catalog SIZE has no verifier by design -- "count is trivially satisfied without
   being true" -- so the load-bearing gate stays RT_STYLE_TOO_SIMILAR below, never a count of ids.
   Rule ids below keep their `RT_PERS_*` names: renaming them is not this PR's job, and
   RT_STYLE_TOO_SIMILAR (style-catalog PR 2b) already proves a rule can outlive the noun in its own
   name once a decision is made deliberately rather than by drift. */
/* Color mood and "Radius & shadow" are gone as prose fields: ground and elevation are AXES now,
   and a field that repeats an axis in adjectives is how the old catalog drifted from its own
   values. Motion stays prose on purpose — the distance rule below is about what a still frame
   shows, and motion.md already pins the curve and the ranges. */
$PERS_FIELDS = array( 'Axes', 'Fits', 'Typography', 'Motion intensity', 'Imagery', 'Card recipe' );

/**
 * The single source of truth for what an axis IS: which positions it may hold, which custom
 * property carries its value inside a `:root` block (or `null` when it has none), and whether it
 * is a MARKER axis -- declared through a CSS comment carrying `axis: position` rather than a
 * custom property, the way composition already is.
 *
 * style-catalog PR 2a's whole job: before this function existed, the same axis list was hand-kept
 * in FOUR places that had to be edited together and were never checked for agreement -- $PERS_AXES
 * (now derived below), axis_signature_of_block()'s $props, axis_matches()'s $labels, and the
 * RT_MOCKUP_AXES_MISMATCH marker regex's alternation. "ONE list, two consumers" is
 * axis_matches()'s own phrase for itself; this is the same discipline applied to all four.
 *
 * 8 axes, widened from 5 in style-catalog PR 2b, in ONE atomic commit alongside every anchor's
 * `**Axes:**` line and the >1 -> >2 similarity threshold -- widening the registry alone, without
 * those companion edits landing in the same commit, FAILs every anchor instantly via
 * RT_PERS_BAD_AXIS (a style would then name a position no axis defines). `accent`, `chassis` and
 * `ornament` are the three new axes, all MARKER axes (`prop => null`) like `composition` already
 * was -- no custom property carries them, a `/* axis: position *\/` comment does.
 */
function nm_axes() {
	return array(
		'scale'       => array(
			'positions' => array( 'contained', 'classic', 'editorial', 'monumental' ),
			'prop'      => '--type-ratio',
			'marker'    => false,
		),
		/* Nine positions, not four (style-catalog PR 3a widened design-system.md's own Ground table
		   at :304-333 the same way; this registry was never widened alongside it -- style-catalog
		   PR 4b found the gap and disclosed it rather than fixing it, since nothing gated STY-*.md
		   against nm_axes() yet. PR 4c repoints RT_STYLE_TOO_SIMILAR to STY-*.md, and three shipped
		   styles (`ink-cool`, `ink-warm`, `saturated`) would FAIL RT_PERS_BAD_AXIS the moment that
		   repoint landed with this list still at four -- so the widening has to land FIRST, same
		   ordering discipline design.md's D2 established for the original 5->8 axis widening. */
		'ground'      => array(
			'positions' => array( 'paper', 'warm', 'cool', 'cream', 'earth', 'saturated', 'ink', 'ink-warm', 'ink-cool' ),
			'prop'      => '--c-bg',
			'marker'    => false,
		),
		'density'     => array(
			'positions' => array( 'compact', 'standard', 'generous', 'monumental' ),
			'prop'      => '--sp-scale',
			'marker'    => false,
		),
		'composition' => array(
			'positions' => array( 'centered', 'asymmetric', 'strict-grid', 'broken-grid' ),
			'prop'      => null,
			'marker'    => true,
		),
		'elevation'   => array(
			'positions' => array( 'none', 'hairline', 'soft-shadow', 'accent-glow' ),
			'prop'      => '--elev-rest',
			'marker'    => false,
		),
		/* Named `accent`, not `accent-policy`: pers_axes() below parses the **Axes:** line's axis
		   token with `[a-z]+` (no hyphen), so a hyphenated axis key would parse as its tail word
		   only ("policy"). Every other axis key in this registry is one word for the same reason. */
		'accent'      => array(
			'positions' => array( 'none', 'reserved', 'tinted-field', 'duotone', 'gradient', 'metallic', 'polychrome' ),
			'prop'      => null,
			'marker'    => true,
		),
		'chassis'     => array(
			'positions' => array( 'bare', 'carded', 'soft-carded', 'bordered', 'rule-divided', 'hard-shadow', 'strict-grid', 'layered' ),
			'prop'      => null,
			'marker'    => true,
		),
		'ornament'    => array(
			'positions' => array( 'none', 'rule', 'texture', 'pattern', 'illustration' ),
			'prop'      => null,
			'marker'    => true,
		),
	);
}

$PERS_AXES = array();
foreach ( nm_axes() as $nm_axis => $nm_def ) {
	$PERS_AXES[ $nm_axis ] = $nm_def['positions'];
}

/**
 * The axis positions one anchor block declares.
 *
 * Parsed from a single `**Axes:**` PARAGRAPH -- not a single LINE, since style-catalog PR 4c's
 * STY-*.md files wrap it across two physical lines for human readability (8 axes no longer fit
 * design-personalities.md's old one-liner width) while design-personalities.md's own blocks never
 * did. `(.+)$` under `/m` alone (no `/s`) stops at the first `\n`, so it silently read only the
 * FIRST four axes of every wrapped block -- caught by running the suite against the real 8-entry
 * catalog for the first time this PR (all 8 FAILed "names no position for axis elevation/accent/
 * chassis/ornament", not a fixture, the real files), the same "run it, don't assume it parses"
 * discipline style-catalog PR 4b's own r92 fixture bug was caught by. Bounded to the paragraph
 * (up to the next blank line, or end of file) rather than opened to `/s` unbounded, so a stray
 * `**Axes:**`-shaped string later in the same file cannot bleed into this capture. Returns
 * `axis => position`, omitting anything the paragraph does not name — a missing axis surfaces as
 * RT_PERS_MISSING_FIELD or as an unnamed axis, never as a silent default, because an axis nobody
 * sets is exactly how every project lands on the same value.
 */
function pers_axes( $block ) {
	if ( ! preg_match( '/^\*\*Axes:\*\*(.+?)(?:\n[ \t]*\n|\z)/ms', $block, $m ) ) {
		return array();
	}
	$out = array();
	if ( preg_match_all( '/([a-z]+)\s+`([a-z-]+)`/', $m[1], $mm, PREG_SET_ORDER ) ) {
		foreach ( $mm as $pair ) {
			$out[ $pair[1] ] = $pair[2];
		}
	}

	return $out;
}

$sty_dir   = $root . '/skills/ux-design-system/references/style-catalog';
$sty_files = glob( $sty_dir . '/STY-*.md' );
if ( ! is_array( $sty_files ) ) {
	$sty_files = array();
}
/* Sorted, same reason every other glob() consumer in this file sorts: the order a row reports a
   pair in must not depend on the filesystem's own directory-entry order. */
sort( $sty_files );
if ( array() === $sty_files ) {
	add( 'RT_PERS_CATALOG_MISSING', 'FAIL', 'ux-design-system', 'references/style-catalog/ has no STY-*.md entries — the style catalog has no files' );
} else {
	$found   = array();
	$axes_of = array();
	/* $axes_of answers two DIFFERENT questions, and only one of them is catalog-only: "what does
	   this anchor hold" (RT_MOCKUP_AXES_MISMATCH, which a `BSP-*.md` must also be able to answer --
	   see the ROUTE-BESPOKE block below) and "which entries owe each other distinctness"
	   (RT_STYLE_TOO_SIMILAR, which is `STY-*.md` and nothing else). $sty_ids is the second
	   question's own membership, captured HERE where the catalog files are the only thing in scope,
	   so the pairwise loop cannot widen by accident later: a bespoke entry merged into $axes_of is
	   invisible to it whatever order the two blocks end up in. Deriving that list from
	   array_keys($axes_of) instead would make the exclusion an ordering accident. */
	$sty_ids = array();
	foreach ( $sty_files as $sty_file ) {
		$block = slurp( $sty_file );
		/* The id pattern does NOT hardcode `STY-` the way the old `PERS-[A-Z-]+` split regex hardcoded
		   `PERS-`: catalog MEMBERSHIP is already decided by the glob above (only `STY-*.md` filenames
		   are ever read here), so the heading only needs to name SOME id, the same "matched on the
		   bare id, never the filename" independence `RT_TPL_UNROUTABLE` already relies on one level
		   up. Every real entry still reads `STY-*` because that is what its own heading says. */
		if ( ! preg_match( '/^#+\s*`([A-Z][A-Z-]*)`/m', $block, $hm ) ) {
			continue;
		}
		$pid = $hm[1];
		/* One file, one entry now -- there is no second `### `STY-X`` heading inside a single file
		   to shadow the first the way design-personalities.md's blocks could. The failure mode
		   this row still guards is the FILE-level equivalent: two DIFFERENT files whose headings
		   both claim the same id (a copy-pasted entry that never got its own heading updated),
		   which would silently drop one style out of the distinctness comparison below exactly the
		   way a duplicate `### `PERS-X`` heading used to. The FIRST file (sorted) stays
		   authoritative, same "report, never merge" discipline as before. */
		if ( isset( $found[ $pid ] ) ) {
			add( 'RT_PERS_DUPLICATE_ID', 'FAIL', 'ux-design-system', basename( $sty_file ) . ' declares "' . $pid . '", already claimed by ' . $found[ $pid ] . ' — the later file is excluded from RT_STYLE_TOO_SIMILAR rather than silently replacing the first' );
			continue;
		}
		$found[ $pid ] = basename( $sty_file );
		foreach ( $PERS_FIELDS as $field ) {
			if ( false === strpos( $block, '**' . $field . ':**' ) ) {
				add( 'RT_PERS_MISSING_FIELD', 'FAIL', 'ux-design-system', basename( $sty_file ) . ': ' . $pid . ' is missing required field "' . $field . '"' );
			}
		}
		$axes = pers_axes( $block );
		$ok   = true;
		foreach ( $PERS_AXES as $axis => $positions ) {
			if ( ! isset( $axes[ $axis ] ) ) {
				add( 'RT_PERS_BAD_AXIS', 'FAIL', 'ux-design-system', $pid . ' names no position for axis "' . $axis . '"' );
				$ok = false;
			} elseif ( ! in_array( $axes[ $axis ], $positions, true ) ) {
				add( 'RT_PERS_BAD_AXIS', 'FAIL', 'ux-design-system', $pid . ' places axis "' . $axis . '" at "' . $axes[ $axis ] . '", which that axis does not define' );
				$ok = false;
			}
		}
		/* Only fully valid entries enter the comparison. An invalid position is not a coincidence,
		   and counting it would report two entries as "too similar" over a typo. */
		if ( $ok ) {
			$axes_of[ $pid ] = $axes;
			$sty_ids[]       = $pid;
		}
	}
	$ids = $sty_ids;
	foreach ( $ids as $i => $a ) {
		foreach ( array_slice( $ids, $i + 1 ) as $b ) {
			$shared = array();
			foreach ( $PERS_AXES as $axis => $_ ) {
				if ( $axes_of[ $a ][ $axis ] === $axes_of[ $b ][ $axis ] ) {
					$shared[] = $axis . ' `' . $axes_of[ $a ][ $axis ] . '`';
				}
			}
			if ( count( $shared ) > 2 ) {
				add(
					'RT_STYLE_TOO_SIMILAR',
					'FAIL',
					'ux-design-system',
					$a . ' and ' . $b . ' share ' . count( $shared ) . ' axes (' . implode( ', ', $shared )
						. ') — two anchors may share at most two of the eight, or they ship as the same site with a different accent'
				);
			}
		}
	}
}

/* --------------------------------------- ROUTE-BESPOKE intake declarations (style-catalog PR 6)
 *
 * `ux-design-system/SKILL.md`'s own axis step precharges every axis from an industry or a gallery
 * card, on purpose -- that IS the fast path. ROUTE-BESPOKE is the escape hatch for when the
 * catalog cannot express a project, and its whole contract is ZERO precharge: every one of the 8
 * axes gets its own explicit answer before builder-core, none inherited (`recommender.md`'s
 * intake asks it). The orchestrator writes what was answered into a `BSP-*.md` file -- structural
 * metadata (axis labels, a wireframe section inventory), the same class of receipt
 * `shipped-log.md` already is, never the client's actual site (`shipped-log.md`: "client work
 * does not belong in this repository"). Filed beside the catalog it may feed
 * (references/style-catalog/, `BSP-*.md` -- a different glob prefix than `STY-*.md`, so it never
 * enters `RT_STYLE_TOO_SIMILAR`'s own comparison above), because "promotion feeds the catalog,
 * never bypasses it" is easiest to keep true when the escape hatch and the thing it escapes share
 * one directory. On the real repo this glob is empty until a first bespoke project ships --
 * silent, same as `RT_TPL_NO_WIREFRAME` before any `TPL-*.md` exists.
 *
 * Reuses `pers_axes()` (the exact `**Axes:**` parser `STY-*.md` uses above) and
 * `tpl_wireframe_comps()` (the exact "## 2. Wireframe" parser `RT_TPL_NO_WIREFRAME` uses below)
 * rather than a third pair of parsers for a third document shape -- a `BSP-*.md` is a
 * `STY-*.md`'s axis declaration plus a `TPL-*.md`'s wireframe declaration, nothing new to read.
 */
$bsp_files = glob( $sty_dir . '/BSP-*.md' );
if ( ! is_array( $bsp_files ) ) {
	$bsp_files = array();
}
sort( $bsp_files );
foreach ( $bsp_files as $bsp_file ) {
	$bsp_base  = basename( $bsp_file );
	$bsp_block = slurp( $bsp_file );
	$bsp_axes  = pers_axes( $bsp_block );
	$bsp_ok    = true;
	foreach ( $PERS_AXES as $bsp_axis => $bsp_positions ) {
		if ( ! isset( $bsp_axes[ $bsp_axis ] ) ) {
			add( 'RT_BESPOKE_UNDECLARED', 'FAIL', 'ux-design-system', $bsp_base . ' names no position for axis "' . $bsp_axis . '" -- ROUTE-BESPOKE answers every axis explicitly, none inherited' );
			$bsp_ok = false;
		} elseif ( ! in_array( $bsp_axes[ $bsp_axis ], $bsp_positions, true ) ) {
			/* The route "buys freedom to COMBINE any position on any axis without inheriting a
			   pre-bundled `STY-*`, not freedom to invent" one (`_bespoke-route.md`) -- a rule that
			   nothing read while this loop only asked whether each axis was ANSWERED. It has to be
			   read now that the answers become an anchor below: an unvalidated position would have
			   the mockup gate compare a real `:root` against a typo and call the pair coherent.
			   RT_PERS_BAD_AXIS rather than a row of its own, because a bespoke declaration's axes
			   line IS a catalog entry's (same `pers_axes()`), so this is the same fact about the
			   same vocabulary, and that row's message already states it in these exact words. */
			add( 'RT_PERS_BAD_AXIS', 'FAIL', 'ux-design-system', $bsp_base . ' places axis "' . $bsp_axis . '" at "' . $bsp_axes[ $bsp_axis ] . '", which that axis does not define' );
			$bsp_ok = false;
		}
	}
	$bsp_wire = tpl_wireframe_comps( $bsp_block );
	if ( null === $bsp_wire || array() === $bsp_wire[0] ) {
		add( 'RT_BESPOKE_UNDECLARED', 'FAIL', 'ux-design-system', $bsp_base . ' declares no wireframe under "## 2. Wireframe" -- ROUTE-BESPOKE still owes QA a declared inventory to build against' );
	}
	/* ---- and the declaration becomes an ANCHOR a mockup may be stamped with ----
	 *
	 * Without this, ROUTE-BESPOKE had no conforming way to mark its own mockup: `Anchor: BSP-X`
	 * FAILed RT_MOCKUP_AXES_MISMATCH as an id the catalog does not define, so the route's only
	 * moves were to name a `STY-*` it does not hold -- the exact defect that row exists to catch --
	 * or to omit the marker, which is RT_MOCKUP_ANCHOR_UNDECLARED on any asset that requires one.
	 * An escape hatch whose only conforming move is to break one of two rules is not one.
	 *
	 * The merge lands HERE and not in $sty_ids: the eight positions are the same eight, read by the
	 * same parser, so the mockup gate compares a bespoke anchor exactly as it compares a catalog
	 * one -- while RT_STYLE_TOO_SIMILAR keeps its own membership list and never sees this entry,
	 * which is `_bespoke-route.md`'s stated contract ("a different glob prefix, so it never enters
	 * RT_STYLE_TOO_SIMILAR's own comparison").
	 *
	 * $bsp_ok gates entry for the reason the catalog loop gates its own: an invalid position is not
	 * a coincidence, and an anchor built out of one validates a mockup against a typo. A file whose
	 * heading names no id registers nothing and says nothing -- membership is the glob's to decide
	 * (same reasoning as the catalog's own id regex above), and an unusable heading surfaces where
	 * it is actionable, as the mockup that tried to point at it.
	 */
	if ( ! $bsp_ok || ! isset( $axes_of ) ) {
		/* No catalog at all is RT_PERS_CATALOG_MISSING's row and $axes_of does not exist. Creating
		   it here would hand RT_MOCKUP_AXES_MISMATCH a map to miss against and charge every mockup
		   in the tree for one absent catalog -- the same double-report its own `isset` guard below
		   exists to avoid. */
		continue;
	}
	if ( ! preg_match( '/^#+\s*`([A-Z][A-Z-]*)`/m', $bsp_block, $bsp_hm ) ) {
		continue;
	}
	$bsp_pid = $bsp_hm[1];
	/* Two namespaces merging into one map is how one silently overwrites the other, and a
	   `BSP-*.md` headed with a catalog id would replace that entry in the lookup after the pairwise
	   comparison has already run over the real one -- nothing downstream would ever notice. This is
	   the file-level collision RT_PERS_DUPLICATE_ID already exists for one level up, so it is the
	   row reused and the FIRST claimant stays authoritative, the same "report, never merge". */
	if ( isset( $found[ $bsp_pid ] ) ) {
		add( 'RT_PERS_DUPLICATE_ID', 'FAIL', 'ux-design-system', $bsp_base . ' declares "' . $bsp_pid . '", already claimed by ' . $found[ $bsp_pid ] . ' — the bespoke declaration registers no anchor rather than silently replacing the first' );
		continue;
	}
	$found[ $bsp_pid ]   = $bsp_base;
	$axes_of[ $bsp_pid ] = $bsp_axes;
}

/**
 * The COMP-* section inventory one `TPL-*.md` declares, deduped, in first-appearance order.
 *
 * Read from the FENCED BLOCK under "## 2. Wireframe" and nowhere else. That narrowness is the
 * point: § 3 expands each section in prose and names components it is merely comparing against,
 * and § 4's "Fijos:" line restates a subset — counting either would let a doc's commentary decide
 * its architecture. The wireframe block is the archetype's declaration of what exists.
 *
 * A section the block names in PROSE rather than as a `COMP-*` id is invisible here, and that is
 * why naming every wireframe row is a rule rather than a nicety: two archetypes whose only
 * difference was an unnamed "Editorial / Lookbook" against an unnamed "Brand Story" measured as
 * 89% identical, and the difference the reader could see was the one the comparison could not.
 *
 * So the ROWS are checked one by one, not just the block as a whole. Proven by mutation: turning
 * TPL-E-01's `COMP-GALLERY (lookbook)` back into the prose row it used to be left the whole tree
 * at 0 FAIL, because the block still held nine other ids and the pair it pulled together landed
 * exactly ON the half rather than past it. A rule that only fires when EVERY row is prose is a
 * rule the one-row-at-a-time version of the defect walks straight through.
 *
 * Returns null when there is no wireframe block at all; otherwise array( comps, prose ) where
 * `prose` is every non-empty row that carries no id.
 */
function tpl_wireframe_comps( $src ) {
	/* `m` so `^` finds the heading on its own LINE, `s` so the capture can run past newlines. `m`
	   alone leaves the heading unfindable and every archetype reads as wireframe-less; `s` alone
	   anchors `^` to byte 0 and does the same. Both, or the row reports the wrong failure — which
	   is what the first run of this check did, on all ten files at once. */
	if ( ! preg_match( '/^##\s*2\.\s*Wireframe[^\n]*\n(.*)/ms', $src, $m ) ) {
		return null;
	}
	if ( ! preg_match( '/```[^\n]*\n(.*?)```/s', $m[1], $f ) ) {
		return null;
	}
	$comps = array();
	$prose = array();
	foreach ( explode( "\n", $f[1] ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		if ( ! preg_match_all( '/COMP-[A-Z0-9-]+/', $line, $cm ) ) {
			$prose[] = $line;
			continue;
		}
		foreach ( $cm[0] as $id ) {
			$comps[ $id ] = true;
		}
	}

	return array( array_keys( $comps ), $prose );
}

/* -------------------------------------------------- archetypes of one family must be distinct
 *
 * web-templates/SKILL.md promises "many architectures", and the personality catalog
 * already has to earn its equivalent claim: `RT_STYLE_TOO_SIMILAR` above lets two anchors share at
 * most two of eight axes, "or they ship as the same site with a different accent colour". The
 * archetypes made the same promise with nothing checking it, and the ecommerce family had drifted
 * to where TPL-E-01 and TPL-E-03 shared eight of their nine sections.
 *
 * THE BAR IS MEASURED, NOT CHOSEN. The corporate family is the control — five architectures nobody
 * disputes are different. Over its ten pairs the shared fraction runs from a half down to about a
 * quarter, and the FLOOR is exactly a half, hit twice: TPL-C-01/TPL-C-03 and TPL-C-02/TPL-C-03 each
 * share 6 of 12. So "at most half" is not a round number picked to be lenient; it is the closest two
 * genuinely different architectures in this repo have ever stood, with no pair to spare.
 *
 * Compared in INTEGERS — 2·|A∩B| > |A∪B| — so the bar cannot drift with float representation, and
 * so the message can name the two counts a reader has to act on rather than a ratio they must
 * reverse-engineer.
 *
 * WITHIN a family only. Ecommerce and corporate archetypes are never offered to the same client:
 * `recommender.md` § 0 bifurcates on site type before any archetype is on the table, so a
 * cross-family resemblance costs nobody a choice. A family directory that vanished entirely emits
 * nothing here, and is caught one level up: SKILL.md points at both directories, so
 * `RT_BROKEN_REFERENCE` fires on the missing path.
 */
/* A FAMILY IS THE SET ONE CHOICE PICKS FROM, and for the inner pages that set is a single PAGE
   ROLE, never the whole `pages/` directory. Nobody chooses between an about page and a product
   page — a site gets both — so comparing across roles would measure a distance no client can
   spend, and the cross-role pairs would outnumber the real ones nine to one. `pages/product/`
   against itself is the question that means something: two product archetypes ARE alternatives,
   and exactly one of them gets picked.
 *
 * THIS OMISSION IS WHAT THE INNER PAGES ACTUALLY COST. The rule shipped pointed at the two home
 * families and nothing else, so every page under `pages/` sat outside the only gate that keeps
 * architectures apart. Measured for the first time, `TPL-PDP-01` and `TPL-PDP-02` shared SEVEN of
 * their eight sections and `TPL-SHOP-01`/`TPL-SHOP-02` six of seven: not similar, the same page.
 * The homes had a gate and held their distance; the pages had none and converged — which is the
 * mechanical reason every site in this catalogue shipped the same product page with a different
 * photo size. Same class of finding as `RT_TPL_UNROUTABLE`: nothing failed, and the audit was
 * green the whole time it was true. */
$tpl_families = array(
	'ecommerce' => $root . '/skills/web-templates/references/templates/ecommerce',
	'corporate' => $root . '/skills/web-templates/references/templates/corporate',
);
$tpl_page_dirs = glob( $root . '/skills/web-templates/references/templates/pages/*', GLOB_ONLYDIR );
if ( ! is_array( $tpl_page_dirs ) ) {
	$tpl_page_dirs = array();
}
/* Sorted, for the same reason the file list below is: the pairs a run reports must not depend on
   the order the filesystem happens to hand back its directory entries. */
sort( $tpl_page_dirs );
foreach ( $tpl_page_dirs as $tpl_page_dir ) {
	$tpl_families[ 'pages/' . basename( $tpl_page_dir ) ] = $tpl_page_dir;
}

foreach ( $tpl_families as $tpl_family => $tpl_dir ) {
	if ( ! is_dir( $tpl_dir ) ) {
		continue;
	}
	$tpl_files = glob( $tpl_dir . '/TPL-*.md' );
	if ( ! is_array( $tpl_files ) ) {
		$tpl_files = array();
	}
	/* Sorted, so the pair a row names is the same pair on every filesystem. glob() order is not
	   guaranteed, and a row whose subject depends on the host is a row nobody can reproduce. */
	sort( $tpl_files );

	$tpl_inv = array();
	foreach ( $tpl_files as $tpl_file ) {
		$tpl_base = basename( $tpl_file );
		/* [A-Z0-9] and not [A-Z]: `TPL-404-01` carries digits in its family segment, and under the
		   narrower class it fell through to the bare filename — a label that still reads but no
		   longer matches the id every other rule speaks in. */
		$tpl_id   = preg_match( '/^(TPL-[A-Z0-9]+-\d+)/', $tpl_base, $im ) ? $im[1] : $tpl_base;
		/* Every name in this block carries the `tpl_` prefix, and that is a rule here rather than a
		   style: this file is 2,900 lines of top-level procedural code sharing one global scope,
		   and an unprefixed `$prose` in this loop silently overwrote the concatenated-markdown
		   blob assigned ~1,300 lines above and read ~1,500 lines below, killing the whole audit
		   with a fatal at a preg_match that had nothing to do with templates. */
		$tpl_parsed = tpl_wireframe_comps( slurp( $tpl_file ) );
		/* An archetype the parser cannot read is NOT skipped quietly. Dropping it would remove it
		   from every pair it belongs to, so a botched heading or an unclosed fence would be a
		   silent off switch for the check — the same shape `RT_PERS_DUPLICATE_ID` exists to close
		   one level up, where a duplicated heading switched RT_STYLE_TOO_SIMILAR off for the real
		   anchor. Report it, then leave it out: comparing an empty inventory would invent a
		   distance nobody wrote. */
		if ( null === $tpl_parsed ) {
			add( 'RT_TPL_NO_WIREFRAME', 'FAIL', 'web-templates', $tpl_family . '/' . $tpl_base . ' has no fenced block under "## 2. Wireframe" — its section inventory cannot be read, so it leaves the ' . $tpl_family . ' similarity comparison entirely' );
			continue;
		}
		list( $tpl_comps, $tpl_prose ) = $tpl_parsed;
		/* Three causes, three messages, one row id — the shape RT_GALLERY_NOT_DISTINCT already
		   uses. The causes are EXCLUSIVE and ordered widest-first: a block with no ids anywhere is
		   one finding about the file, not one finding per line plus a summary, which is what the
		   first version printed (four rows for a three-line block). */
		if ( array() === $tpl_comps ) {
			add( 'RT_TPL_NO_WIREFRAME', 'FAIL', 'web-templates', $tpl_family . '/' . $tpl_base . ' has a wireframe block that names no COMP-* section at all — there is no declared architecture here to compare' );
			continue;
		}
		/* The dangerous cause, and the one worth a row PER LINE: the block still parses and the
		   archetype still enters the comparison, carrying one section fewer than it really has.
		   Each row names the exact text to fix. */
		if ( array() !== $tpl_prose ) {
			foreach ( $tpl_prose as $tpl_prose_row ) {
				add( 'RT_TPL_NO_WIREFRAME', 'FAIL', 'web-templates', $tpl_family . '/' . $tpl_base . ' wireframe row "' . $tpl_prose_row . '" carries no COMP-* id — that section is invisible to the similarity comparison, which is exactly how two archetypes whose only difference was an unnamed section measured as near-identical' );
			}
			continue;
		}
		$tpl_inv[ $tpl_id ] = $tpl_comps;
	}

	$tpl_ids = array_keys( $tpl_inv );
	foreach ( $tpl_ids as $tpl_i => $tpl_a ) {
		foreach ( array_slice( $tpl_ids, $tpl_i + 1 ) as $tpl_b ) {
			$tpl_shared = array_values( array_intersect( $tpl_inv[ $tpl_a ], $tpl_inv[ $tpl_b ] ) );
			$tpl_union  = array_unique( array_merge( $tpl_inv[ $tpl_a ], $tpl_inv[ $tpl_b ] ) );
			sort( $tpl_shared );
			if ( 2 * count( $tpl_shared ) > count( $tpl_union ) ) {
				add(
					'RT_TPL_TOO_SIMILAR',
					'FAIL',
					'web-templates',
					$tpl_a . ' and ' . $tpl_b . ' share ' . count( $tpl_shared ) . ' of ' . count( $tpl_union )
						. ' sections (' . implode( ', ', $tpl_shared )
						. ') — two archetypes of one family may share at most half, the distance the corporate family already holds, or they ship as the same site with a different accent'
				);
			}
		}
	}
}

/* ------------------------------------------------ every archetype has to be reachable by a client
 *
 * `RT_TPL_TOO_SIMILAR` above proves two archetypes are DIFFERENT. It says nothing about whether
 * anybody can ever be offered one, and that turned out to be the gap that actually cost work:
 * TPL-C-06..12 shipped as seven finished archetypes — each with its own brand block, its own
 * embedded typefaces and its own strip in the gallery — while `recommender.md` named none of them,
 * not once. Every documented path into the catalog runs through that file, so seven templates were
 * built and none of them could be recommended. Nothing failed; the audit was green the whole time.
 *
 * This is the same class of finding as `RT_CATALOG_UNMENTIONED` one level up ("the style
 * catalog is unreachable from the skill"), applied to the layer below it, and it is a FAIL for the
 * same reason: an unreachable catalog entry is indistinguishable from one that does not exist,
 * except that somebody paid to build it.
 *
 * Two ways in are required, because they fail apart. `recommender.md` is the RUNTIME path — the
 * agent reads it to route a live client. The folder `_README.md` is the READING path — a human
 * comparing the family before touching anything. A template missing from the first cannot be
 * chosen; one missing from the second gets rebuilt from scratch by the next person, who had no way
 * to know it was there. A family directory with no `_README.md` at all reports once, against the
 * directory, rather than once per template it fails to list.
 *
 * Matched on the bare id (TPL-C-06), never the filename, so renaming
 * `TPL-C-06-table-menu.md` cannot silently switch the check off. Silent when recommender.md is
 * absent: see the note on the gate below.
 */
$route_rec_file = $root . '/skills/web-templates/references/recommender.md';
/* No recommender.md, no judgement. A tree without one is not a catalog with a broken route, it is
   not a catalog — and the missing file is already owned one level up: SKILL.md § References points
   at `references/recommender.md`, so `RT_BROKEN_REFERENCE` fires on the dead path. Claiming it here
   too would print the same fact twice and would make this rule shout at every fixture that happens
   to carry a TPL-*.md without a whole skill around it. */
$route_rec_src = file_exists( $route_rec_file ) ? slurp( $route_rec_file ) : null;

$route_groups = null === $route_rec_src ? array() : array(
	'ecommerce' => $root . '/skills/web-templates/references/templates/ecommerce',
	'corporate' => $root . '/skills/web-templates/references/templates/corporate',
	'pages'     => $root . '/skills/web-templates/references/templates/pages',
);

foreach ( $route_groups as $route_family => $route_dir ) {
	if ( ! is_dir( $route_dir ) ) {
		continue;
	}
	/* pages/ nests one level deeper (product/, legal/, error/…); the two home families are flat.
	   One glob pattern per shape rather than a recursive walk, so the set this rule judges is the
	   same set a reader sees in the directory listing. */
	$route_files = array_merge(
		is_array( $g1 = glob( $route_dir . '/TPL-*.md' ) ) ? $g1 : array(),
		is_array( $g2 = glob( $route_dir . '/*/TPL-*.md' ) ) ? $g2 : array()
	);
	sort( $route_files );
	if ( array() === $route_files ) {
		continue;
	}

	$route_readme = $route_dir . '/_README.md';
	if ( ! file_exists( $route_readme ) ) {
		add(
			'RT_TPL_UNROUTABLE',
			'FAIL',
			'web-templates',
			'templates/' . $route_family . '/ holds ' . count( $route_files ) . ' archetype(s) and no _README.md —'
				. ' the other families have one, so this is the family a human comparing the catalog cannot read'
		);
		$route_readme_src = null;
	} else {
		$route_readme_src = slurp( $route_readme );
	}

	foreach ( $route_files as $route_file ) {
		$route_base = basename( $route_file );
		if ( ! preg_match( '/^(TPL-[A-Z0-9]+-\d+)/', $route_base, $route_m ) ) {
			continue;
		}
		$route_id = $route_m[1];

		if ( null !== $route_rec_src && false === strpos( $route_rec_src, $route_id ) ) {
			add(
				'RT_TPL_UNROUTABLE',
				'FAIL',
				'web-templates',
				$route_id . ' (' . $route_family . '/' . $route_base . ') is never named in recommender.md —'
					. ' it is a finished archetype no client can ever be offered, because every documented route into the catalog reads that file'
			);
		}
		if ( null !== $route_readme_src && false === strpos( $route_readme_src, $route_id ) ) {
			add(
				'RT_TPL_UNROUTABLE',
				'FAIL',
				'web-templates',
				$route_id . ' is missing from templates/' . $route_family . '/_README.md —'
					. ' the comparison table a human reads before choosing does not list it, which is how an archetype gets rebuilt by somebody who never knew it existed'
			);
		}
	}
}

/**
 * Every markdown table row that DEFINES one axis position, as its list of candidate value cells.
 *
 * "Defines" is narrow on purpose: a row one of whose cells is EXACTLY `<position>` — backticked,
 * nothing else in the cell. Everything to the RIGHT of that cell is a candidate value; everything
 * to its left is the table's own labelling. A prose paragraph that merely names the position is
 * not a row and contributes nothing, and neither does a heading. A separator row (|---|:--:|) can
 * never match, because no cell of one is ever exactly a backticked position.
 *
 * Rows come back one by one, not merged, because one position can legitimately own two rows —
 * `monumental` is a position on BOTH the scale axis and the density axis — and each of those rows
 * has to carry its own value. Merging them would let a filled scale row cover for an emptied
 * density row, which is the same "some value-looking string exists elsewhere" hole one level down.
 */
function axis_rows_for( $src, $pos ) {
	$want = '`' . $pos . '`';
	$out  = array();
	foreach ( explode( "\n", $src ) as $line ) {
		$line = trim( $line );
		if ( '' === $line || '|' !== $line[0] ) {
			continue;
		}
		$cells = array_map( 'trim', explode( '|', trim( $line, '|' ) ) );
		$at    = array_search( $want, $cells, true );
		if ( false === $at ) {
			continue;
		}
		$out[] = array_slice( $cells, $at + 1 );
	}

	return $out;
}

/* Generic pipe-table parser for shipped-log.md (style-catalog PR 5b): the FIRST pipe-starting line
 * of $src becomes the header (column name => cell index) — true by construction, since the whole
 * file is exactly one table. Every following non-separator row becomes an assoc array keyed by
 * that header, so a column can be read by name (`Style`, `Chassis`, …) rather than by a hardcoded
 * position, the same resilience axis_rows_for() above buys by matching a header token instead of a
 * column count. A separator row ("|---|---|", or any row whose cells hold only "-", ":" or
 * whitespace) is recognised and skipped, never mistaken for a delivery.
 */
function ledger_table_rows( $src ) {
	$header = null;
	$out    = array();
	foreach ( explode( "\n", $src ) as $line ) {
		$line = trim( $line );
		if ( '' === $line || '|' !== $line[0] ) {
			continue;
		}
		$cells = array_map( 'trim', explode( '|', trim( $line, '|' ) ) );
		if ( null === $header ) {
			$header = $cells;
			continue;
		}
		$is_sep = true;
		foreach ( $cells as $cell ) {
			if ( '' !== trim( $cell, "-: \t" ) ) {
				$is_sep = false;
				break;
			}
		}
		if ( $is_sep ) {
			continue;
		}
		$row = array();
		foreach ( $header as $col_ix => $col_name ) {
			$row[ $col_name ] = isset( $cells[ $col_ix ] ) ? $cells[ $col_ix ] : '';
		}
		$out[] = $row;
	}
	return $out;
}

/* One STY-*.md's own "## Toggle precharge" (English) / "## Precarga de toggles" (Spanish) table,
 * keyed by toggle id => declared precharge value (style-catalog PR 5c). Reuses
 * ledger_table_rows()'s generic pipe-table parser rather than a third hand-rolled one; "Precharge"
 * and "Precarga" are read interchangeably because STY-VITRINE.md's own table header is Spanish —
 * the language contract already established elsewhere in this catalog, not a new exception. Both
 * the toggle id and its value are typed as inline code (`` `TGL-IMAGERY` ``, `` `foto` ``); the
 * surrounding backticks are stripped so a comparison against the ledger's own plain-text cell is
 * exact.
 */
function style_precharge_rows( $sty_src ) {
	if ( ! preg_match( '/^##\s+(?:Toggle precharge|Precarga de toggles)\b[^\n]*\n(.*?)(?=\n## |\z)/ms', $sty_src, $m ) ) {
		return array();
	}
	$out = array();
	foreach ( ledger_table_rows( $m[1] ) as $row ) {
		$tgl = isset( $row['Toggle'] ) ? trim( $row['Toggle'], "` \t" ) : '';
		if ( '' === $tgl ) {
			continue;
		}
		$val         = isset( $row['Precharge'] ) ? $row['Precharge'] : ( isset( $row['Precarga'] ) ? $row['Precarga'] : '' );
		$out[ $tgl ] = trim( $val, "` \t" );
	}
	return $out;
}

/* shipped-log.md's own "Toggles" cell: "TGL-ID=value; TGL-ID2=value2" (style-catalog PR 5c),
 * chosen so a value itself may hold a bare "=" or spaces (values are prose like "imagen fija")
 * without colliding with the delimiter — only ";" and "|" are reserved, both already claimed by
 * the row this cell lives inside.
 */
function ledger_toggle_map( $cell ) {
	$out = array();
	foreach ( explode( ';', $cell ) as $pair ) {
		$eq = strpos( $pair, '=' );
		if ( false === $eq ) {
			continue;
		}
		$k = trim( substr( $pair, 0, $eq ) );
		if ( '' !== $k ) {
			$out[ $k ] = trim( substr( $pair, $eq + 1 ) );
		}
	}
	return $out;
}

/**
 * What ONE markdown table cell offers as a token value: array( kind, payload ).
 *
 *   array( 'literal',   <token> ) — a hex colour, a bare number, a CSS length/time, `none`, or a
 *                                   CSS function call (var/calc/clamp/rgba/color-mix/…).
 *   array( 'blueprint', <id> )    — a BACKTICKED screaming-case identifier, e.g. `LP-BROKEN-GRID`.
 *   array( '', '' )               — an empty cell, a bare adjective, or a prose sentence.
 *
 * Only the cell's LEADING token decides, and a cell that opens with a backticked run is judged on
 * what is inside those backticks. `0 0 0 1px var(--c-border)` is a value whose first token is a
 * number; "cream/ivory, e.g. `#FFF3E3`" opens with prose, so the hex it mentions later is an
 * illustration and not the cell's value. Requiring the blueprint form to be SCREAMING-CASE is what
 * stops the laziest non-answer of all: repeating the position's own lowercase name as its value.
 *
 * The incident this replaces: RT_AXIS_VALUE_MISSING used to ask only whether the position NAME
 * appeared between backticks ANYWHERE in design-system.md. Replacing the whole value section with a
 * bare table of backticked names and empty value cells kept the gate at 0 FAIL — verified on this
 * checkout — and under that check ground `cool`, ground `ink` and all four composition positions
 * shipped with no value at all, which is the exact "a position with no value is an adjective"
 * failure the row was written to prevent.
 */
function axis_value_kind( $cell ) {
	$cand       = trim( $cell );
	$backticked = false;
	if ( preg_match( '/^`([^`]+)`/', $cand, $m ) ) {
		$cand       = trim( $m[1] );
		$backticked = true;
	}
	/* No early return for an empty cell: every branch below needs at least one character to match,
	   so '' falls through to array( '', '' ) on its own. A guard here would be a branch no fixture
	   can reach, which is the shape this suite treats as a defect rather than as caution.
	   A CSS function is one value even though its first whitespace-delimited token carries a paren
	   and usually a comma, so it is recognised before the token split rather than after it. */
	if ( preg_match( '/^(?:var|calc|clamp|min|max|rgba?|hsla?|color-mix|linear-gradient)\s*\(/i', $cand ) ) {
		return array( 'literal', $cand );
	}
	$parts = preg_split( '/\s+/', $cand );
	$tok   = $parts[0];
	if ( preg_match( '/^(?:#[0-9A-Fa-f]{3,8}|-?(?:\d+(?:\.\d+)?|\.\d+)(?:px|rem|em|ch|ex|vw|vh|vmin|vmax|%|fr|deg|ms|s)?|none)$/i', $tok ) ) {
		return array( 'literal', $tok );
	}
	if ( $backticked && preg_match( '/^[A-Z][A-Z0-9]*(?:-[A-Z0-9]+)+$/', $tok ) ) {
		return array( 'blueprint', $tok );
	}

	return array( '', '' );
}

/**
 * The custom properties design-system.md's per-axis table names, in COLUMN ORDER — a cell at
 * index $i in axis_rows_for()'s output is that property's value for the row's position (scale's
 * table is `| Position | --type-ratio | --display-lh | --fs-h1-max |`, so index 2 is --fs-h1-max).
 * Composition names none here: its positions are a layout blueprint id, and the marker comment
 * (parsed by axis_signature_of_block()/the mismatch loop below) already IS that value, so the
 * label check is also the value check for that one axis and nothing further belongs here.
 * Elevation names only --elev-rest, not its --elev-hover sibling: the two ride on ONE label and
 * the rest value alone is enough to catch a mis-typed position, which keeps this row from also
 * having to police a second, easily-derived shadow nobody re-points independently.
 */
function axis_token_props( $axis ) {
	$props = array(
		'scale'     => array( '--type-ratio', '--display-lh', '--fs-h1-max' ),
		'ground'    => array( '--c-bg', '--c-bg-alt', '--c-text' ),
		'density'   => array( '--sp-scale' ),
		'elevation' => array( '--elev-rest' ),
	);
	return isset( $props[ $axis ] ) ? $props[ $axis ] : array();
}

/**
 * design-system.md's own value for one axis position, keyed by the custom property it belongs to.
 *
 * axis_value_kind() decides whether a cell HAS a token worth reading — RT_AXIS_VALUE_MISSING's own
 * gate, reused here rather than duplicated — but its payload is not reused directly: that function
 * only has to prove a token EXISTS, so a backticked cell holding several space-separated words (an
 * elevation box-shadow) is deliberately truncated to its first word there. Correct for "is there a
 * value", wrong for "what is the value", so this reads the cell itself: the full backticked run
 * when the cell opens with one (the closing backtick ends it, same prefix match axis_value_kind()
 * uses, so trailing prose like "`none` — separation is whitespace" stops at `none`), or the bare
 * cell text for an unbackticked one (scale's and density's plain numbers).
 */
function axis_token_values( $src, $axis, $pos ) {
	$props = axis_token_props( $axis );
	$out   = array();
	foreach ( axis_rows_for( $src, $pos ) as $cells ) {
		foreach ( $cells as $i => $cell ) {
			if ( ! isset( $props[ $i ] ) || isset( $out[ $props[ $i ] ] ) ) {
				continue;
			}
			list( $kind ) = axis_value_kind( $cell );
			if ( '' === $kind ) {
				continue;
			}
			$full = trim( $cell );
			if ( preg_match( '/^`([^`]+)`/', $full, $bm ) ) {
				$full = $bm[1];
			}
			$out[ $props[ $i ] ] = strtolower( trim( preg_replace( '/\s+/', ' ', $full ) ) );
		}
	}
	return $out;
}

/**
 * One custom property's value out of a `:root` block, normalised the same way
 * axis_token_values() normalises design-system.md's side, so the two are comparable one for one.
 * Same (?![\w-]) boundary axis_signature_of_block() needs for --c-bg vs --c-bg-alt, generalised to
 * any property name so it also guards --elev-rest against a longer neighbour.
 */
function root_token_value( $block, $prop ) {
	if ( preg_match( '/' . preg_quote( $prop, '/' ) . '(?![\w-])\s*:\s*([^;}]+)/', $block, $m ) ) {
		return strtolower( trim( preg_replace( '/\s+/', ' ', $m[1] ) ) );
	}
	return null;
}

/* Top level, not inside the `else` above: the axes $PERS_AXES declares exist whether or not the
   catalog file does, so a missing catalog must not also silence this check.
   A position with no value is an adjective. The old catalog was entirely adjectives, which is
   why it produced one look: nothing downstream could act on "softest step of the scale". */
$ds_file = $root . '/skills/web-templates/references/design-system.md';
$ds_src  = file_exists( $ds_file ) ? slurp( $ds_file ) : '';
/* Composition, chassis, accent and ornament are the four axes whose value is not a number, so
   their positions point at named blueprints. A blueprint nobody wrote is an adjective with a code
   number on it — the same failure in a smarter disguise — so the name has to resolve to a real
   heading. Not every marker axis's heading lives in the same file: composition and chassis are
   layout concerns (layout-patterns.md), accent and ornament are colour/surface concerns and
   design-system.md already holds § Ground and § Elevation, so their definitions sit beside those,
   in design-system.md itself (the very file the blueprint id was READ from, for these two). */
$lp_file       = $root . '/skills/ux-design-system/references/layout-patterns.md';
$lp_src        = file_exists( $lp_file ) ? slurp( $lp_file ) : '';
$axis_bp_lookup = array(
	'composition' => array( $lp_src, 'layout-patterns.md', 'ux-design-system' ),
	'chassis'     => array( $lp_src, 'layout-patterns.md', 'ux-design-system' ),
	'accent'      => array( $ds_src, 'design-system.md', 'web-templates' ),
	'ornament'    => array( $ds_src, 'design-system.md', 'web-templates' ),
);
foreach ( $PERS_AXES as $axis => $positions ) {
	foreach ( $positions as $pos ) {
		/* $pos_rows, not $rows: $rows is this script's global findings accumulator, and the first
		   draft of this loop shadowed it — the report section then read axis cells as findings and
		   printed "0 FAIL" over a tree with WARNs in it. The suite's diagnostics tracker caught it;
		   the counts line alone would not have. */
		$pos_rows   = axis_rows_for( $ds_src, $pos );
		$blank      = 0;
		$blueprints = array();
		foreach ( $pos_rows as $pos_cells ) {
			$row_has = false;
			foreach ( $pos_cells as $cell ) {
				list( $kind, $payload ) = axis_value_kind( $cell );
				if ( '' === $kind ) {
					continue;
				}
				$row_has = true;
				if ( 'blueprint' === $kind ) {
					$blueprints[ $payload ] = true;
				}
			}
			if ( ! $row_has ) {
				++$blank;
			}
		}
		if ( array() === $pos_rows ) {
			add( 'RT_AXIS_VALUE_MISSING', 'FAIL', 'web-templates', 'design-system.md gives no value for axis "' . $axis . '" position "' . $pos . '" — no table row of its own anywhere in the file (a prose mention, backticked or not, is not a row)' );
		} elseif ( $blank > 0 ) {
			add( 'RT_AXIS_VALUE_MISSING', 'FAIL', 'web-templates', 'design-system.md gives no value for axis "' . $axis . '" position "' . $pos . '" — ' . $blank . ' of its ' . count( $pos_rows ) . ' table row(s) carry no token-shaped value cell (hex, number, CSS length, var()/calc()/clamp(), none, or a backticked blueprint id)' );
		}
		list( $bp_src, $bp_file, $bp_skill ) = isset( $axis_bp_lookup[ $axis ] )
			? $axis_bp_lookup[ $axis ]
			: array( $lp_src, 'layout-patterns.md', 'ux-design-system' );
		foreach ( array_keys( $blueprints ) as $bp ) {
			if ( ! preg_match( '/^#{2,6}\s+`' . preg_quote( $bp, '/' ) . '`/m', $bp_src ) ) {
				add( 'RT_AXIS_BLUEPRINT_MISSING', 'FAIL', $bp_skill, 'design-system.md values axis "' . $axis . '" position "' . $pos . '" as blueprint `' . $bp . '`, but ' . $bp_file . ' defines no heading by that name — the position points at nothing' );
			}
		}
	}
}

/**
 * The eight axis signatures a proof mockup's `:root` block carries, normalised for comparison.
 *
 * Only the `:root` block is read. Every one of these custom properties is REFERENCED dozens of
 * times further down each file (`box-shadow: var(--elev-rest)` and friends), so a whole-file scan
 * would match a use and call it a declaration — and the axis whose value the page merely consumes
 * would then look declared no matter what `:root` says.
 *
 * Composition, accent, chassis and ornament are the four axes with no custom property, because a
 * marker axis's value is not a number. Composition travels as a CSS comment carrying
 * "composition:" and an LP- blueprint id, the marker design-system.md names. The other three
 * (style-catalog PR 2b) carry their position name directly on the SAME `/* axis: position *\/`
 * shape RT_MOCKUP_AXES_MISMATCH's own marker regex parses — no blueprint indirection, since
 * their positions are not layout rules.
 *
 * Values are lowercased and whitespace-collapsed before comparison: `#FFFFFF` against `#ffffff`
 * is a case change, not a ground, and a gate that called it an axis difference would let a
 * rename pass for a redesign. An axis absent from a `:root` yields '' — which compares EQUAL to
 * the other file's absent axis, so two files that both forgot to declare density are correctly
 * counted as matching on it rather than as differing.
 */
function axis_signature_of_block( $block ) {
	$sig = array();
	foreach ( nm_axes() as $nm_axis => $nm_def ) {
		$sig[ $nm_axis ] = '';
	}
	if ( '' === $block ) {
		return $sig;
	}
	/* Derived from nm_axes() (style-catalog PR 2a), marker axes excluded here the same way
	   composition always was -- they have no custom property (`'prop' => null`), and are parsed
	   separately below. */
	$props = array();
	foreach ( nm_axes() as $nm_axis => $nm_def ) {
		if ( null !== $nm_def['prop'] ) {
			$props[ $nm_axis ] = $nm_def['prop'];
		}
	}
	foreach ( $props as $axis => $prop ) {
		/* (?![\w-]) so `--c-bg` never swallows the `--c-bg-alt` declaration that sits beside it on
		   the same line: the ground axis is --c-bg, and matching its longer neighbour would report
		   the alternate surface as the page's ground. */
		if ( preg_match( '/' . preg_quote( $prop, '/' ) . '(?![\w-])\s*:\s*([^;}]+)/', $block, $m ) ) {
			$sig[ $axis ] = strtolower( trim( preg_replace( '/\s+/', ' ', $m[1] ) ) );
		}
	}
	if ( preg_match( '#/\*\s*composition\s*:\s*(LP-[A-Z0-9-]+)#i', $block, $cm ) ) {
		$sig['composition'] = strtolower( $cm[1] );
	}
	/* accent, chassis, ornament (style-catalog PR 2b) -- marker axes whose position IS the value,
	   so no blueprint id to resolve. FIRST occurrence per axis wins, same rule
	   RT_MOCKUP_AXES_MISMATCH's own marker parsing uses, for the same reason: a repeated marker
	   further down the file with prose after it must not silently override the real declaration. */
	$other_markers = array();
	foreach ( nm_axes() as $nm_axis => $nm_def ) {
		if ( $nm_def['marker'] && 'composition' !== $nm_axis ) {
			$other_markers[] = $nm_axis;
		}
	}
	if ( array() !== $other_markers && preg_match_all( '#/\*\s*(' . implode( '|', $other_markers ) . ')\s*:\s*([A-Za-z0-9-]+)#i', $block, $mm, PREG_SET_ORDER ) ) {
		foreach ( $mm as $one ) {
			$m_axis = strtolower( $one[1] );
			if ( '' === $sig[ $m_axis ] ) {
				$sig[ $m_axis ] = strtolower( $one[2] );
			}
		}
	}

	return $sig;
}

function proof_axis_signature( $src ) {
	$root_block = '';
	if ( preg_match( '/:root\s*\{(.*?)\}/s', $src, $rm ) ) {
		$root_block = $rm[1];
	}
	return axis_signature_of_block( $root_block );
}

/**
 * Which of the eight axes two signatures AGREE on, each named with the value they share.
 *
 * ONE list, two consumers, for the reason axis_declarations() is a function: RT_PROOF_NOT_DISTINCT
 * asks it of the two proof mockups' `:root` blocks and RT_GALLERY_NOT_DISTINCT asks it of two
 * gallery anchors' `[data-anchor]` blocks. A ninth axis, or a different property carrying one, has
 * to land on both at once — and dropping an axis from this list silently INFLATES the difference
 * count on both, which is the shape of a distinctness check that stops distinguishing.
 *
 * Naming the matching axis with its value is the whole point of the return shape. "not different
 * enough" sends the reader back to diff two files; "both sit at --sp-scale: 1.0" is a one-line fix.
 */
function axis_matches( $a, $b ) {
	/* Derived from nm_axes() (style-catalog PR 2a/2b). Order kept exactly as before for the
	   original five (composition LAST, not in its usual fourth slot) -- this list's own order, not
	   $PERS_AXES's, since that is what every existing "share N axes (...)" message was built from.
	   accent/chassis/ornament are appended after composition, in nm_axes()'s own tail order. */
	$nm_defs = nm_axes();
	$labels  = array();
	foreach ( array( 'scale', 'ground', 'density', 'elevation', 'composition', 'accent', 'chassis', 'ornament' ) as $nm_axis ) {
		/* PR 2a hardcoded 'composition marker' here, the only marker axis that existed at the time.
		   Widening to four marker axes turns that literal into a bug -- every marker axis would
		   report ITSELF as "composition marker" -- so the label is now built per axis. */
		$labels[ $nm_axis ] = $nm_defs[ $nm_axis ]['marker'] ? ( $nm_axis . ' marker' ) : $nm_defs[ $nm_axis ]['prop'];
	}
	$same   = array();
	foreach ( $labels as $axis => $label ) {
		if ( $a[ $axis ] === $b[ $axis ] ) {
			$shown  = ( '' === $a[ $axis ] ) ? 'neither declares it' : 'both `' . $a[ $axis ] . '`';
			$same[] = $axis . ' (' . $label . ': ' . $shown . ')';
		}
	}
	return $same;
}

/**
 * Every `.html` file under a directory, AT ANY DEPTH, in a deterministic order.
 *
 * A directory walk and not `glob('*.html')`, which does not descend. Entries are sorted per level
 * before recursion, so the order is the same on every filesystem — a row whose order depends on
 * inode order is a row whose output diffs at random. Paths are built by appending `/`, so a caller
 * can take the tail with substr() and get a relative path on every platform.
 */
function html_assets_deep( $dir ) {
	$out     = array();
	$entries = is_dir( $dir ) ? scandir( $dir ) : false;
	if ( false === $entries ) {
		return $out;
	}
	sort( $entries, SORT_STRING );
	foreach ( $entries as $entry ) {
		if ( '.' === $entry || '..' === $entry ) {
			continue;
		}
		$path = $dir . '/' . $entry;
		if ( is_dir( $path ) ) {
			$out = array_merge( $out, html_assets_deep( $path ) );
		} elseif ( preg_match( '/\.html$/i', $entry ) ) {
			$out[] = $path;
		}
	}
	return $out;
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

/**
 * Every human-visible string a proof mockup renders, in document order, as a MULTISET.
 *
 * Comments, `<style>`/`<script>` bodies and every tag — attribute values with them — are replaced
 * by a separator before anything is read, so what survives is only what a reader would see. The
 * separator matters: stripping tags without one would weld `<h3>A</h3><p>B</p>` into "AB", and two
 * files that nest the same copy differently (one wraps its card text in an extra div) would then
 * differ on a string neither of them changed.
 *
 * A multiset, not a set: `Pedir presupuesto` appears three times on each page, and a set would let
 * one of the three be deleted without a word going missing from the comparison.
 *
 * What it CANNOT see, and the limit is worth stating rather than discovering: characters painted
 * by CSS `content:`. A `.quote::before{content:"«"}` on one file and not the other is a visible
 * difference this returns nothing for. That is why the editorial proof no longer has one.
 */
function proof_visible_strings( $src ) {
	$src = preg_replace( '/<!--.*?-->/s', "\x00", $src );
	$src = preg_replace( '#<(style|script)\b[^>]*>.*?</\1\s*>#is', "\x00", $src );
	$src = preg_replace( '/<[^>]*>/', "\x00", $src );
	$src = html_entity_decode( $src, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$out = array();
	foreach ( explode( "\x00", $src ) as $chunk ) {
		/* \s collapses the newlines a wrapped text run carries, so a run reflowed across source
		   lines is the same string as the run written on one line. Formatting is not copy. */
		$chunk = trim( preg_replace( '/\s+/u', ' ', $chunk ) );
		if ( '' !== $chunk ) {
			$out[] = $chunk;
		}
	}

	return $out;
}

/* The falsifiable claim the whole axis system rests on: ONE content set under TWO anchors has to
   render as two unmistakably different pages. Phase A proved the anchors are far apart on paper —
   that is what RT_STYLE_TOO_SIMILAR guards. The two rows below guard the two halves of the
   criterion the proof files are supposed to demonstrate: SAME content (RT_PROOF_COPY_DIFFERS),
   UNMISTAKABLY DIFFERENT rendering (RT_PROOF_NOT_DISTINCT). Without the first, editing one
   headline contaminates the experiment with every other gate still green.

   What RT_PROOF_NOT_DISTINCT actually checks, stated plainly because the shape of it invites a
   stronger reading: it compares eight DECLARED AXIS VALUES in each file's first `:root` block --
   four literal custom-property values (--type-ratio, --c-bg, --sp-scale, --elev-rest) and four
   marker-comment values (composition, accent, chassis, ornament, style-catalog PR 2b's three new
   marker axes, none of which carry a custom property). It does not render anything, does not look
   at a pixel, and does not know whether a declared token is used. Five of the nine axis-carrying
   custom properties both files declare — --display-lh, --fs-h1-max, --elev-hover, --c-bg-alt and
   --c-text — are read by neither check, so a file could ship a 88px cap against a 120px one and
   this row would be satisfied by --type-ratio alone. It is a guard against the two files quietly
   converging on the same tokens, not evidence that they render differently; that evidence is a
   human looking at them side by side.

   Hardcoded paths, like house-rules.md above, and for the same reason: a missing proof file must
   FAIL rather than silently skip the check. "The gate passes because the evidence is gone" is the
   failure mode these rows exist to make impossible. Keys renamed `PERS-*` -> `STY-*` alongside
   the two files' own `Anchor:` markers, style-catalog PR 4c -- these are LABELS this row prints,
   not a lookup into $axes_of, but a label that disagreed with what the file itself now declares
   would read as a second, competing catalog. */
$PROOF_MOCKUPS = array(
	'STY-EDITORIAL' => 'skills/html-mockup/assets/proof-editorial-mockup.html',
	'STY-DIRECT'    => 'skills/html-mockup/assets/proof-direct-mockup.html',
);
$proof_sigs    = array();
$proof_copy    = array();
$proof_all_here = true;
foreach ( $PROOF_MOCKUPS as $anchor => $rel ) {
	if ( ! file_exists( $root . '/' . $rel ) ) {
		add( 'RT_PROOF_NOT_DISTINCT', 'FAIL', 'html-mockup', $rel . ' is missing — the ' . $anchor . ' half of the axis proof cannot be compared, so the claim that two anchors render differently is unverifiable' );
		$proof_all_here = false;
		continue;
	}
	$proof_src             = slurp( $root . '/' . $rel );
	$proof_sigs[ $anchor ] = proof_axis_signature( $proof_src );
	$proof_copy[ $anchor ] = proof_visible_strings( $proof_src );
}
if ( $proof_all_here && 2 === count( $proof_sigs ) ) {
	$anchors = array_keys( $proof_sigs );
	$same    = axis_matches( $proof_sigs[ $anchors[0] ], $proof_sigs[ $anchors[1] ] );
	$differ  = 8 - count( $same );
	if ( $differ < 6 ) {
		/* Naming the matching axes is the whole point of the message. "not different enough" sends
		   the reader back to diff two 300-line files; "both use --sp-scale: 1.0" is a one-line fix. */
		add(
			'RT_PROOF_NOT_DISTINCT',
			'FAIL',
			'html-mockup',
			$anchors[0] . ' and ' . $anchors[1] . ' differ on only ' . $differ . ' of 8 axes — they match on '
				. implode( ', ', $same ) . '. Same content under two anchors has to render as two different pages, or the anchors are one anchor'
		);
	}
}

/* The OTHER half of the criterion. RT_PROOF_NOT_DISTINCT above gates "unmistakably different";
   this gates "same content", and until it existed only one of the two was a gate. Edit one
   headline in one proof file and the experiment is contaminated with every row green: the reader
   comparing the two pages then cannot tell whether what they see is the axes or the copy, which is
   the exact confound `_axis-proof-content.md` exists to remove.

   Multisets, compared by COUNT per string, not by presence. A set comparison would pass a file
   that renders `Pedir presupuesto` twice against one that renders it three times — a whole CTA
   deleted, with the string still "present" on both sides. */
if ( $proof_all_here && 2 === count( $proof_copy ) ) {
	$anchors = array_keys( $proof_copy );
	$ca      = array_count_values( $proof_copy[ $anchors[0] ] );
	$cb      = array_count_values( $proof_copy[ $anchors[1] ] );
	$diffs   = array();
	/* A's strings first, in document order, then B's that A never had — so the string the message
	   names is deterministic rather than whatever the hash order happened to be. */
	foreach ( array_merge( array_keys( $ca ), array_keys( $cb ) ) as $s ) {
		if ( isset( $diffs[ $s ] ) ) {
			continue;
		}
		$na = isset( $ca[ $s ] ) ? $ca[ $s ] : 0;
		$nb = isset( $cb[ $s ] ) ? $cb[ $s ] : 0;
		if ( $na !== $nb ) {
			$diffs[ $s ] = array( $na, $nb );
		}
	}
	if ( array() !== $diffs ) {
		$first = array_key_first( $diffs );
		list( $na, $nb ) = $diffs[ $first ];
		add(
			'RT_PROOF_COPY_DIFFERS',
			'FAIL',
			'html-mockup',
			$anchors[0] . ' and ' . $anchors[1] . ' do not render the same copy — ' . count( $diffs )
				. ' visible string(s) differ. `' . $first . '` is rendered ' . $na . 'x by '
				. basename( $PROOF_MOCKUPS[ $anchors[0] ] ) . ' and ' . $nb . 'x by '
				. basename( $PROOF_MOCKUPS[ $anchors[1] ] )
				. '. Same content is the other half of the criterion: two pages that differ in a word prove nothing about the axes'
		);
	}
}

/* The two rows above gate the PROOF files — the pair that demonstrates the axis system works.
   This one gates the files a real project is actually copied from, which is a different and
   larger claim: a proof nobody builds on proves nothing about client sites.

   Measured on a532df1, `corporate-mockup.html` and `ecommerce-mockup.html` contained ZERO
   occurrences of --type-ratio, --sp-scale or --elev-rest while both proof files carried the whole
   system and every other row stayed green. So the axis work stopped one step before the first
   real project, and every client kept receiving the same hardcoded 56px h1 cap. The point of this
   row is not that a mockup is pretty; it is that a mockup which cannot EXPRESS an axis silently
   reverts every project that starts from it to one look — which is the defect the whole axis
   effort exists to remove.

   GLOB, not a hardcoded pair, unlike the proof rows above. Those two files are named because a
   missing one must FAIL rather than skip; here the opposite risk dominates — a THIRD production
   asset added later without axes is exactly the regression this row is for, and a hardcoded list
   would not see it.

   Files whose basename starts with `_` are skipped: within this glob, `_` marks a PARTIAL — an
   `.html` fragment a page file includes rather than a page a project is copied from. A partial
   carries markup and copy, not a `:root`, so demanding five axis declarations from it would be
   demanding them from something that has no palette to declare.
   The carve-out is a RESERVATION, not a description of today's tree: as of this commit no `_`
   `.html` partial exists, and the `_axis-proof-content.md` this comment used to cite as "the real
   instance" was never one — the glob is `*.html`, so a `.md` file is out of reach with or without
   the prefix, and naming it made the rule look load-bearing where it does nothing. What the skip
   actually buys is that splitting a shared band out of the two production mockups stays a one-file
   move instead of a new FAIL. tests/test-framework-audit.php covers it with `_shared-copy.html`,
   which is that shape: an `.html` file with no axis declarations at all.

   RECURSIVE, and it was not. `glob($root.'/skills/html-mockup/assets/*.html')` does not descend,
   so `assets/gallery/` — a whole page of anchors, added later, exactly the "third production asset"
   this row's glob exists to catch — sat outside it: a gallery could have shipped with no axis
   declaration at all and this row would have stayed green. The walk below is a directory walk, and
   the message names the path RELATIVE to assets/ rather than the basename, because two files called
   `index.html` in two subdirectories are one message otherwise. */
$mockup_asset_root = $root . '/skills/html-mockup/assets';
$mockup_assets     = html_assets_deep( $mockup_asset_root );
/* Derived from nm_axes() (style-catalog PR 2a), same order as before -- built once, outside the
   per-file loop below, since it never varies per file. */
$mockup_axis_alt = implode( '|', array_keys( nm_axes() ) );
foreach ( $mockup_assets as $mockup_path ) {
	$mockup_name = substr( $mockup_path, strlen( $mockup_asset_root ) + 1 );
	if ( '_' === substr( basename( $mockup_path ), 0, 1 ) ) {
		continue;
	}
	/* Only the `:root` block is read, for the same reason proof_axis_signature() reads only it:
	   every one of these properties is REFERENCED further down each file (`box-shadow:
	   var(--elev-rest)` and friends), so a whole-file scan would match a USE and call it a
	   declaration — and a file that consumes an axis it never defines would pass. */
	$mockup_src  = slurp( $mockup_path );
	$mockup_root = '';
	if ( preg_match( '/:root\s*\{(.*?)\}/s', $mockup_src, $mrm ) ) {
		$mockup_root = $mrm[1];
	}
	$mockup_missing = array();
	/* Shared with RT_QA_NO_AXIS_CHECK — see axis_declarations(). */
	foreach ( axis_declarations() as $mockup_prop ) {
		/* (?![\w-]) so `--elev-rest` never matches on `--elev-rest-something`, the same boundary
		   the proof signature needs to stop `--c-bg` swallowing `--c-bg-alt`. */
		if ( ! preg_match( '/' . preg_quote( $mockup_prop, '/' ) . '(?![\w-])\s*:/', $mockup_root ) ) {
			$mockup_missing[] = $mockup_prop;
		}
	}
	/* Composition is the one axis with no custom property, because its value is a layout rule
	   rather than a number. The marker IS the declaration, so a check that only counted custom
	   properties would pass a file that had quietly dropped a whole axis. */
	if ( ! preg_match( '#/\*\s*composition\s*:\s*LP-[A-Z0-9-]+#i', $mockup_root ) ) {
		$mockup_missing[] = '/* composition: LP-* */';
	}
	if ( array() !== $mockup_missing ) {
		/* Naming each missing declaration is what makes the row actionable: "declares no axis
		   tokens" sends the reader to diff an 880-line file against a reference by eye. */
		add(
			'RT_MOCKUP_NO_AXES',
			'FAIL',
			'html-mockup',
			'assets/' . $mockup_name . ' does not declare ' . implode( ', ', $mockup_missing )
				. ' in its :root — a mockup that cannot express an axis silently reverts every project that starts from it to one look'
		);
	}

	/* ---- RT_MOCKUP_ANCHOR_UNDECLARED / RT_MOCKUP_AXES_MISMATCH ----
	 *
	 * RT_MOCKUP_NO_AXES above asks whether the five axes are DECLARED. This pair asks whether they
	 * are declared COHERENTLY -- that a file naming PERS-INSTITUTIONAL actually carries the positions
	 * PERS-INSTITUTIONAL holds, rather than four of them and one left over from whatever it was
	 * re-pointed away from.
	 *
	 * THE DEFECT THIS EXISTS FOR IS THE HALF-DONE RE-POINT, and it is the likely one. The block is
	 * five token lines plus a composition marker, so re-pointing a project to a new anchor means
	 * editing six things, and every row that existed before this one stayed green on five out of six.
	 * The block's own comment has said `a hand-picked value is how every client site ends up looking
	 * the same` since the axes landed, and nothing read it: writing the explanation is not installing
	 * the gate.
	 *
	 * BOTH THE LABEL AND THE VALUE, not the label alone (style-catalog PR 1e closed the gap this
	 * comment used to admit here: a `scale: contained` marker beside a hand-typed `--fs-h1-max: 53`
	 * used to pass, because the label agreed with the anchor and nothing read the number).
	 * axis_token_values() reads design-system.md's own token table for the position the label
	 * claims and root_token_value() reads the same property back out of `:root`; the two must
	 * agree or the row FAILs, naming the token and both values. This still catches the axis that
	 * was never re-pointed AT ALL — the label mismatch below fires first and the value check is
	 * skipped for that axis, since a wrong label already says everything the value would.
	 *
	 * HARDCODED LIST **AND** GLOB, deliberately, for the two different failures they catch. The four
	 * named files must DECLARE an anchor -- a missing declaration there is the file going quiet, and a
	 * glob alone would let deleting the marker switch the check off, which is exactly how PERS-VITRINE
	 * once shipped outside the catalog's own required-membership list ($PERS_IDS, retired by
	 * style-catalog PR 4c along with design-personalities.md itself -- the lesson holds regardless
	 * of which structure the catalog lives in). Every OTHER asset the walk finds is checked only IF it declares one,
	 * because assets/gallery/index.html renders every anchor at once and belongs to no single one.
	 */
	/* THE TWO STARTING ASSETS, and only those (tasks.md 1f.2 — the two hand-maintained originals,
	   `corporate-mockup.html` / `ecommerce-mockup.html`, are deleted; `chassis/corporate.html` and
	   `chassis/ecommerce.html` are what a real project starts from now). Generated, not hand-copied,
	   but no less a starting asset for that, and generator output earns no exemption from the row it
	   exists to police (client-chassis-generation spec, "Generated Chassis Is Not Exempt From Any
	   Mockup Rule"). They are the files a real project starts from — run the generator, never copy —
	   and therefore the only ones anybody ever RE-POINTS, which is the act this pair exists to
	   police. The two proof-*-mockup.html are fixed by contract — their whole job is to stand at two
	   named anchors over one copy set — and RT_PROOF_NOT_DISTINCT already measures them against EACH
	   OTHER on all eight axes. They still declare `Anchor:` and are still checked below, because the
	   glob checks whatever declares one; they are simply not required to, since a demand nothing
	   would ever violate is a row that only ever fires on fixtures. */


	/* ---- RT_MOCKUP_GRID_AUTOFILL ----
	 *
	 * `auto-fill` AND `auto-fit` LOOK IDENTICAL IN A STYLESHEET AND ARE NOT. `auto-fill` creates
	 * every column that fits the container whether or not there is an element for it; `auto-fit`
	 * collapses the empty ones so the elements that exist share the width. Three team portraits in
	 * a canvas that fits four render, under `auto-fill`, as three cards squeezed left with a quarter
	 * of the section empty — and an empty reserved column is indistinguishable from a missing card.
	 * The reader circled exactly that in red and asked why it keeps happening.
	 *
	 * SAME SHAPE AS EVERY OTHER MISALIGNMENT HERE: a container sizing itself against the space
	 * available instead of against its own siblings. layout-patterns.md § "Grid track counts" says
	 * it in full, and this row is what stops it being said and not applied — which is the failure
	 * that produced `RT_MOCKUP_DISCLOSURE_STATE` one commit ago and is worth naming twice.
	 *
	 * NOT A BAN, A JUSTIFICATION REQUIREMENT, and the difference matters because `auto-fill` is
	 * genuinely right somewhere: a calendar month, a seat map, a contact sheet whose grid must not
	 * reflow as items are filtered out. In all three the empty track IS the point. So the row asks
	 * for the marker `auto-fill:` in a comment within 400 characters before the declaration, which
	 * is the same shape as the verifier markers in a SKILL.md — the reason travels with the code and
	 * the next reader does not have to guess whether it was a decision or a default.
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
			add( 'RT_MOCKUP_GRID_AUTOFILL', 'FAIL', 'html-mockup', 'assets/' . $mockup_name . ': ' . $af_bad . ' grid(s) use repeat(auto-fill) with no `auto-fill:` justification beside them — that reserves a column for every element that FITS rather than for every element that EXISTS, and a reserved empty column reads as a missing card. `auto-fit` collapses them; if the empty track is the point, say so in a comment within 400 chars' );
		}
	}
	/* ---- RT_MOCKUP_DISCLOSURE_STATE ----
	 *
	 * ONE RULE FOR EVERY DISCLOSURE LIST: built from `<details>`, and exactly the FIRST row open.
	 * layout-patterns.md § "Disclosure lists" gives the reasons — all closed is a wall of headings
	 * the reader will not open, all open is a long page pretending to be a short one — and this row
	 * is what stops them being reasons nobody applies.
	 *
	 * WRITTEN AFTER THE DRIFT, NOT BEFORE IT. mockup-guide.md had specified `<details>/<summary>`
	 * for COMP-FAQ from the beginning, and the `.acc .qas` comment had warned in as many words that
	 * a second implementation is how two identical things start to drift. Measured when somebody
	 * finally looked: FOUR emitters, THREE behaviours — the PDP accordion opened its first row, two
	 * FAQ blocks opened none, and one rendered `<div class="qa"><h3>`, not a disclosure at all, with
	 * every answer permanently on screen. Every gate was green throughout, because none of them read
	 * the emitted markup. The reader's words were "no queda bien todas desplegadas".
	 *
	 * A RUN OF SIBLINGS, NOT A LIST OF WRAPPER CLASSES, and the first draft of this row got that
	 * wrong. Keying on `faqlist`/`qas` — the two classes the GALLERY happens to use — left every
	 * other asset unchecked, including `corporate-mockup.html`, which held nine `<details>` under a
	 * wrapper called `faq` and was the file a real project was copied from (style-catalog PR 1f
	 * retired it; the generated `chassis/*.html` are what a real project starts from now). A rule
	 * that only inspects
	 * the tool and not the product is worse than none, because the tool is the part nobody ships.
	 * So: two or more `<details>` separated by nothing but whitespace are one list, whatever wraps
	 * them. A SINGLE `<details>` is not a list and is not judged — that is `.handoff`, the spec
	 * block, which correctly starts closed because nothing in it deserves the reader's first glance.
	 *
	 * THREE CAUSES, three messages, because each is a different edit.
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
			add( 'RT_MOCKUP_DISCLOSURE_STATE', 'FAIL', 'html-mockup', 'assets/' . $mockup_name . ': ' . $disc_none_open . ' disclosure list(s) open no row — all closed reads as a wall of headings and the reader cannot tell whether anything inside is worth opening. The FIRST row carries `open`, and no other' );
		}
		if ( $disc_many_open > 0 ) {
			add( 'RT_MOCKUP_DISCLOSURE_STATE', 'FAIL', 'html-mockup', 'assets/' . $mockup_name . ': ' . $disc_many_open . ' disclosure list(s) open more than one row — all open is not an accordion, it is a long page pretending to be a short one. Exactly the first, and no other' );
		}
	}
	/* A section that calls itself a FAQ and holds no `<details>` at all is the fourth emitter's
	   defect — `<div class="qa"><h3>` with every answer permanently on screen — and the run check
	   above cannot see it, because there is nothing to run over. */
	if ( preg_match_all( '#<section[^>]*class="[^"]*\bfaq\b[^"]*"[^>]*>(.*?)</section>#s', $mockup_src, $disc_fq, PREG_SET_ORDER ) ) {
		$disc_flat = 0;
		foreach ( $disc_fq as $disc_one ) {
			if ( false === strpos( $disc_one[1], '<details' ) ) {
				++$disc_flat;
			}
		}
		if ( $disc_flat > 0 ) {
			add( 'RT_MOCKUP_DISCLOSURE_STATE', 'FAIL', 'html-mockup', 'assets/' . $mockup_name . ': ' . $disc_flat . ' FAQ section(s) hold no <details> at all — every answer is permanently on screen, which is not a disclosure list, it is a long page pretending to be a short one' );
		}
	}
	$anchored_required = array(
		'chassis/corporate.html',
		'chassis/ecommerce.html',
	);
	/* style-catalog PR 4c: catalog ids moved from `PERS-*` to `STY-*` (design-personalities.md
	   retired for references/style-catalog/). Every real asset this row polices was re-stamped in
	   the same PR -- the two generated chassis (_build-gallery.php) and the two hand-authored proof
	   mockups all declare `Anchor: STY-*` now. Like the catalog's own id-extraction regex (see
	   $sty_files' foreach above), this one does NOT hardcode the `STY-` prefix either: the id just
	   has to match whatever `$axes_of` (built from the SAME regex) is keyed by, and hardcoding two
	   independent copies of a prefix string is exactly how the two could quietly drift apart. */
	$mockup_declares = preg_match( '/Anchor:\s*([A-Z][A-Z-]*)/', $mockup_root, $mockup_anm );
	if ( ! $mockup_declares ) {
		if ( in_array( $mockup_name, $anchored_required, true ) ) {
			add( 'RT_MOCKUP_ANCHOR_UNDECLARED', 'FAIL', 'html-mockup', 'assets/' . $mockup_name . ' does not say which anchor it is pointed at — without `Anchor: STY-*` in its :root nothing can check that its eight axis positions belong together' );
		}
	} elseif ( ! isset( $axes_of ) ) {
		/* No catalog at all is RT_PERS_CATALOG_MISSING's row, already FAILing above. Reporting the
		   same absence a second time here would send the reader to the wrong file. */
		$mockup_declares = 0;
	} elseif ( ! isset( $axes_of[ $mockup_anm[1] ] ) ) {
		add( 'RT_MOCKUP_AXES_MISMATCH', 'FAIL', 'html-mockup', 'assets/' . $mockup_name . ' is pointed at "' . $mockup_anm[1] . '", which references/style-catalog/ defines as neither a catalog entry (STY-*.md) nor a ROUTE-BESPOKE declaration (BSP-*.md) — a bespoke project stamps its own BSP id here, it does not borrow a STY it does not hold' );
	} else {
		$mockup_pid    = $mockup_anm[1];
		$mockup_labels = array();
		/* FIRST occurrence per axis wins: proof-editorial-mockup.html repeats its `elevation` marker
		   further down with prose after it, and the one that counts is the one in the axis block. */
		if ( preg_match_all( '#/\*\s*(' . $mockup_axis_alt . ')\s*:\s*([A-Za-z0-9-]+)#i', $mockup_root, $mockup_lm, PREG_SET_ORDER ) ) {
			foreach ( $mockup_lm as $mockup_one ) {
				$mockup_ax = strtolower( $mockup_one[1] );
				if ( isset( $mockup_labels[ $mockup_ax ] ) ) {
					continue;
				}
				$mockup_pos = strtolower( $mockup_one[2] );
				/* composition is the one axis written as a layout pattern rather than a position, because
				   it has no custom property to carry it: `LP-STRICT-GRID` IS `strict-grid`. */
				if ( 'composition' === $mockup_ax ) {
					$mockup_pos = preg_replace( '/^lp-/', '', $mockup_pos );
				}
				$mockup_labels[ $mockup_ax ] = $mockup_pos;
			}
		}
		$mockup_wrong = array();
		foreach ( $PERS_AXES as $mockup_axis => $mockup_ignored ) {
			$mockup_want = $axes_of[ $mockup_pid ][ $mockup_axis ];
			$mockup_got  = isset( $mockup_labels[ $mockup_axis ] ) ? $mockup_labels[ $mockup_axis ] : '(none)';
			if ( $mockup_got !== $mockup_want ) {
				$mockup_wrong[] = $mockup_axis . ' `' . $mockup_got . '` (' . $mockup_pid . ' holds `' . $mockup_want . '`)';
				continue;
			}
			/* The label agrees with the anchor — now does the :root TOKEN agree with what
			   design-system.md's own table gives that position? One $mockup_wrong entry per axis,
			   not per token, so "N of 8 axes disagree" below still counts axes: scale alone has
			   three columns, and a run of three PROPERTY-level entries for one axis would inflate
			   that count past 8 and misreport how many axes actually need fixing. */
			$mockup_want_values = axis_token_values( $ds_src, $mockup_axis, $mockup_want );
			$mockup_bad_tokens  = array();
			foreach ( axis_token_props( $mockup_axis ) as $mockup_prop ) {
				if ( ! isset( $mockup_want_values[ $mockup_prop ] ) ) {
					continue;
				}
				$mockup_actual = root_token_value( $mockup_root, $mockup_prop );
				if ( null !== $mockup_actual && $mockup_actual !== $mockup_want_values[ $mockup_prop ] ) {
					$mockup_bad_tokens[] = $mockup_prop . ' `' . $mockup_actual . '` (' . $mockup_want . ' holds `' . $mockup_want_values[ $mockup_prop ] . '`)';
				}
			}
			if ( array() !== $mockup_bad_tokens ) {
				$mockup_wrong[] = $mockup_axis . ' label `' . $mockup_got . '` matches ' . $mockup_pid . ' but its token(s) do not: ' . implode( ', ', $mockup_bad_tokens );
			}
		}
		if ( array() !== $mockup_wrong ) {
			/* Naming the axis AND both positions is the whole value: "does not match its anchor" sends
			   the reader to compare a :root against a catalog by eye, which is the diff nobody does. */
			add( 'RT_MOCKUP_AXES_MISMATCH', 'FAIL', 'html-mockup', 'assets/' . $mockup_name . ' says it is ' . $mockup_pid . ' but ' . count( $mockup_wrong ) . ' of 8 axes disagree: ' . implode( '; ', $mockup_wrong ) . ' — a half-finished re-point ships as a site that is neither anchor' );
		}
	}

	/* ---- RT_MOCKUP_BLEED_FIXED_BAND ----
	 *
	 * A FIXED `--content-width` IS ONLY A DEFECT NEXT TO A VIEWPORT-EDGE BLEED, which is why this
	 * row has two arms and fires on neither alone. LP-CENTERED and LP-STRICT-GRID cap their content
	 * at a fixed band and centre it, and that is an ordinary, correct desktop layout at any width.
	 * LP-ASYMMETRIC and LP-BROKEN-GRID declare `[full-start] minmax(pad,1fr) … minmax(pad,1fr)
	 * [full-end]` where `full-end` IS the layout viewport's right edge, so the 14 tracks have to sum
	 * to the SCREEN. Cap the twelve columns with a fixed band and the only track left to absorb a
	 * wider screen is the `1fr` gutter — and nothing bounds a `1fr`.
	 *
	 * MEASURED on assets/gallery/index.html at `--content-width:1140px`, left gutter under
	 * LP-BROKEN-GRID: 150.1px at 1440, 390.1px at 1920, 710.1px at 2560 — 10.4% of the viewport
	 * growing to 27.7%. The bleeding edge always reached the glass, so the composition's optical
	 * centre drifted right by half of that (+75 / +195 / +355px) and at 2560 the left quarter of
	 * every section was dead ink. The reader's words were "los márgenes están todos muy mal".
	 *
	 * WHY A TEXT CHECK AND NOT A GEOMETRY ONE. The geometry is house-rules.md row 32's, measured in
	 * a browser at eight widths, and it is the better check — but it needs a browser and a host, and
	 * this audit runs offline in a second on every commit. What a text check CAN decide is whether
	 * the token is a bare literal, and a bare literal beside a `full-end` is the defect with no
	 * further measurement needed. It caught nothing new when it was written — all three assets had
	 * just been fixed — which is the point: it exists so the next fixed literal cannot land.
	 *
	 * `clamp(`/`min(`/`max(`, `vw`, `vmin`, `%` — any one of them means the band tracks the
	 * viewport somehow, and deciding whether it tracks it WELL is row 32's job, not this one's.
	 */
	if ( false !== strpos( $mockup_src, 'full-end' )
		&& preg_match( '/--content-width\s*:\s*([^;}]+)/', $mockup_root, $cwm ) ) {
		$cw_value = trim( $cwm[1] );
		if ( ! preg_match( '/clamp\(|min\(|max\(|\d\s*vw|\d\s*vmin|\d\s*%/i', $cw_value ) ) {
			add(
				'RT_MOCKUP_BLEED_FIXED_BAND',
				'FAIL',
				'html-mockup',
				'assets/' . $mockup_name . ' bleeds to `full-end` but pins --content-width at `' . $cw_value
					. '` — the named-line grid must sum to the viewport, so a fixed band leaves the outer 1fr gutter'
					. ' as the only track that can absorb a wider screen and nothing bounds it (measured 710px of dead'
					. ' margin at 2560 on a 1140px band). See design-system.md § Contenedores for the fluid value'
			);
		}
	}

	/* ---- RT_MOCKUP_BLEED_NOT_MEDIA ----
	 *
	 * WHAT REACHES THE GLASS, not how wide the band beside it is. RT_MOCKUP_BLEED_FIXED_BAND above
	 * reads the RELATIONSHIP between the band and a bleed and is blind to WHICH element bleeds; it
	 * was green on every build described below.
	 *
	 * THE THREE THINGS THAT SHIPPED THROUGH THAT BLIND SPOT, in the order the reader found them:
	 *   1. `.services .items` / `.cases .items` / `.carousel .items` at `c 1 / full-end` — CARD
	 *      rows. The last card's surface ended on the glass while its copy was inset 32px by a
	 *      rule added to "keep the text off the edge", so one object was half bled and half not.
	 *      Measured at 2000: frame right 2000.0, body ink right 1968.0. The reader called it cut
	 *      off, twice, across two rounds of fixes.
	 *   2. `.hero .head` at `c 1 / full-end` — a copy block claiming a gutter its own ink never
	 *      entered (h1 ink stopped 227px short at 2000, 745px short at 2560).
	 *   3. `.band .formwrap` at `c 6 / full-end` — a FORM. Every field and the submit button ended
	 *      at exactly x=2560.0 on a 2560 viewport, and the name input was 1453.3px wide. That is
	 *      not a styling defect, it is a control the reader cannot use: "esto no puede pasar bajo
	 *      ningún concepto".
	 * `documentElement.scrollWidth === clientWidth` throughout all three. Nothing overflowed. Every
	 * overflow gate in this repo was green, which is exactly why this row is not an overflow gate.
	 *
	 * THE RULE, AND IT IS A WHITELIST RATHER THAN A MEASUREMENT. An element may resolve to
	 * `full-start` / `full-end` only if it is MEDIA — a figure, an image, a picture, a video, or a
	 * container whose whole job is to hold one. Media at the glass is the blueprint working: it is
	 * why `layout-patterns.md` gives LP-ASYMMETRIC and LP-BROKEN-GRID named grid lines at all.
	 * Anything else at the glass is a defect whose severity only goes up as the element gets more
	 * interactive — copy is amputated, a card is sliced, a control is broken.
	 *
	 * WHY STATIC AND NOT GEOMETRIC. The geometry is house-rules.md row 32's and it is the better
	 * check, but it needs a browser and a host; this audit runs offline in a second on every
	 * commit. The subject of a `grid-column` declaration is decidable from the text, so this row
	 * decides it there. It reads the LAST simple selector — the element the rule actually styles —
	 * because `.hero .media` is a bleed and `.media .btn` would not be.
	 *
	 * FAIL-CLOSED ON THE UNKNOWN. A subject this row does not recognise is reported, not skipped.
	 * A whitelist that silently passes what it has never seen is the same blind spot one level up,
	 * and the whole reason this row exists is that the previous gate passed things it could not
	 * see. Adding a genuinely new media wrapper means adding it to $bleed_media_ok on purpose.
	 */
	/* MEDIA, PLUS ONE THING THAT IS NOT MEDIA AND IS STILL ALLOWED: an empty decorative panel.
	   `proof-direct-mockup.html`'s `.band .slab` is `<div class="slab"></div>` — no text, no
	   control, nothing to amputate, sitting at `z-index:0` behind the copy as a coloured ground.
	   A slab at the glass is the same act as a photograph at the glass. `.ph` is the mockup
	   vocabulary's photo PLACEHOLDER and `.shot` its photo container; both are media by role.
	   Every name here had to be justified out loud, which is the point of a whitelist. */
	$bleed_media_ok = array( 'media', 'frame', 'figure', 'img', 'picture', 'video',
		'slides', 'slide', 'hero-slides', 'shot', 'ph', 'slab' );
	/* AND ONE NAME THAT IS NOT MEDIA EITHER, ADDED ON PURPOSE AND WITH ITS ARGUMENT.
	   `bleedband` is a SECTION that spans the glass — not an item inside the band's grid.
	   Every defect this row was built from is the same shape: an object that belongs to a ROW
	   claimed the gutter its row-mates did not, so it was half bled and half inset and read as cut
	   off. A band has no row-mates. It is the row, which is what container-hygiene already means by
	   "the section IS the row", and its children keep their own padding, so no copy and no control
	   ever reaches the glass — only the colour does. That is the same act as `slab`, which this
	   list already admits, with the difference that a band is allowed to carry content INSIDE it.
	   The claim is checked and not trusted: the readback below FAILs if `bleedband` is ever found
	   on anything other than a `<section>`, which is the one way this exemption could be turned
	   back into the defect it is carved out of. */
	$bleed_media_ok[] = 'bleedband';
	$bleed_offences = array();
	/* COMMENTS COME OUT FIRST, AND THE ORDER IS THE WHOLE OF IT. Stripping them after splitting the
	   selector list on `,` reported `/* the one bleed per section` and `always the SAME edge` as two
	   separate offending selectors, because the prose above the rule contains commas and lands in
	   the same `[^{}]+` run as the selector. The first version of this row did exactly that and its
	   output was gibberish — a check whose failure message is unreadable is a check nobody acts on. */
	$bleed_css = preg_replace( '#/\*.*?\*/#s', '', $mockup_src );
	/* The declaration, not the whole rule: `grid-column:<something> full-start|full-end`. Capturing
	   the selector list means everything from the previous `}` or `{`-less start up to the `{`. */
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
			'html-mockup',
			'assets/' . $mockup_name . ' sends ' . implode( ', ', array_unique( $bleed_offences ) )
				. ' to `full-start`/`full-end`, which IS the layout viewport edge, and none of them is media.'
				. ' A photograph at the glass is a bleed; a card is sliced, a paragraph is amputated and a form'
				. ' control is unusable — measured on this gallery as a submit button whose right border sat on'
				. ' x=2560.0 with a 1453px name field beside it, while scrollWidth === clientWidth throughout.'
				. ' End the row at the band (`c 13` / `wide-end`) and let only `.media` reach `full-end`.'
				. ' See layout-patterns.md § Sangrado'
		);
	}

	/* ---- the `bleedband` exemption, read back ----
	 *
	 * An allow-list entry is a promise, and this repository has shipped enough promises that were
	 * true when written. `bleedband` is admitted above ONLY as a section-level band; put it on a
	 * card or a form wrapper and it becomes a licence for exactly the three defects the row exists
	 * to catch. So the markup is read: every occurrence of the class must sit on a `<section>`.
	 * Matched on the opening tag, which is where a class attribute lives, so a mention inside a
	 * comment or a data attribute cannot make this pass or fail. */
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
				'html-mockup',
				'assets/' . $mockup_name . ' puts `bleedband` on ' . implode( ', ', array_unique( $bb_bad ) )
					. ' and that class is admitted to the viewport glass ONLY as a section-level band.'
					. ' On anything smaller it is an item inside a row claiming a gutter its row-mates do not,'
					. ' which is the exact defect this row was built from — a sliced card, an amputated'
					. ' paragraph, an unusable control. Move the class to the `<section>` and let the item'
					. ' end at the band.'
			);
		}
	}

	/* ---- RT_MOCKUP_FONT_NOT_EMBEDDED ----
	 *
	 * A declared typeface nobody serves. Every mockup here named real families — Fraunces, Inter
	 * Tight, Archivo Expanded, Instrument Serif, DM Sans, Source Sans 3 — and embedded none of
	 * them, and none of the six is installed on an ordinary machine. So `'Fraunces', Georgia,
	 * serif` rendered GEORGIA, the DIRECT anchor rendered Arial Black, and every visual judgement
	 * anyone made about this framework was a judgement of the fallback stack. The axes were all
	 * green throughout: a token chain resolves perfectly into a face the machine does not have.
	 *
	 * THIS IS THE MOCKUP-SIDE TWIN OF es_font_serving_check(), NOT A DUPLICATE OF IT. That one
	 * warns when a WordPress BUILD names a family nothing on the site serves — no `@font-face`, no
	 * enqueue, no registration. This one asks whether the static HTML a project is copied FROM
	 * carries the bytes. Different surfaces, different serving mechanisms, and fixing one would
	 * have left the other exactly as broken.
	 *
	 * TWO ARMS, because either alone is trivially satisfiable in a way that ships the defect:
	 *   1. a family asked for with no `@font-face` at all — the original bug;
	 *   2. an `@font-face` whose `src` is a URL rather than a `data:` URI. That satisfies arm 1
	 *      while being precisely what the Artifact CSP blocks, and a blocked face falls back to
	 *      the same Georgia. The old comments in these files were right that a URL is unusable
	 *      here; their mistake was concluding that `@font-face` itself had to go.
	 */
	$mockup_css   = slurp( $mockup_path );
	$mockup_asked = mockup_fonts_asked_for( $mockup_css );
	$mockup_serve = mockup_font_faces_served( $mockup_css );

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
			'html-mockup',
			'assets/' . $mockup_name . ' names ' . implode( ', ', $mockup_bare )
				. ' first in a font stack and declares no @font-face for it — the file renders its FALLBACK,'
				. ' so everyone who reviews it reviews a typeface nobody chose. Embed the woff2 as a data: URI:'
				. ' assets/fonts/_embed-fonts.php does it, assets/fonts/_fonts.md says under what licence'
		);
	}
	if ( array() !== $mockup_url ) {
		add(
			'RT_MOCKUP_FONT_NOT_EMBEDDED',
			'FAIL',
			'html-mockup',
			'assets/' . $mockup_name . ' serves ' . implode( ', ', $mockup_url )
				. ' from a URL rather than a data: URI — the Artifact CSP blocks the request, so the face never'
				. ' arrives and the file renders the same fallback it would with no @font-face at all'
		);
	}
}

/* ------------------------------------------------------------------- the gallery
 *
 * A gallery is one page holding many `TPL-* × PERS-*` cards, and its whole claim is that two cards
 * read as two different SITES. The rows above cannot make that claim: RT_MOCKUP_NO_AXES asks each
 * FILE whether it can express an axis, and RT_PROOF_NOT_DISTINCT compares exactly two hardcoded
 * files. Add thirty cards to one document and neither row learns anything — which is the gap these
 * two close, because the failure mode of a catalog is not a missing token, it is forty entries that
 * turn out to be one entry with a different accent colour.
 *
 * WHAT COUNTS AS A GALLERY ASSET, and why the test has two arms. Path: anything under a `gallery/`
 * directory. Content: anything rendering `<section class="strip">` with `data-tpl`/`data-pers`.
 * Either arm alone leaves a silent exit — rename the directory and a path-only test stops looking;
 * delete the two attributes and a content-only test stops looking — and both of those edits would
 * turn the rows below green by removing their subject rather than by fixing anything.
 *
 * WHY THE GENERATED HTML AND NOT `_build-gallery.php`. The generator is where the strip table is
 * WRITTEN, but the HTML is what is opened, approved and handed on, and `_build-gallery.php` says so
 * itself beside its own duplicate guard: "RT_GALLERY_NOT_DISTINCT will assert this from outside".
 * A check that read the generator would pass a hand-edited `index.html`, and would have to parse
 * PHP array literals to do it.
 */

/** One row of a markdown table, cells trimmed, outer pipes dropped. */
function gallery_cells( $line ) {
	$line = preg_replace( '/^\|/', '', trim( $line ) );
	$line = preg_replace( '/\|$/', '', $line );
	return array_map( 'trim', explode( '|', $line ) );
}

/** `|---|:--|` and friends: a row that is only alignment, never data. */
function gallery_is_separator( array $cells ) {
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
 * The image table out of `_gallery-images.md`: its rows, and whether it has a licence column.
 *
 * COLUMNS ARE READ FROM THE HEADER, never counted from the left. The manifest carries more than one
 * table (a "Registers" table sits above the image set), and a cell index hardcoded here would break
 * the moment a column is inserted — quietly, by reading the wrong cell, which is worse than
 * breaking loudly. The table selected is the first whose header carries a cell reading exactly
 * `Slug`; "Slugs" (the Registers table's own column) is deliberately not a match.
 *
 * Returns null when NO table carries a Slug column — which is a different finding from a table
 * whose rows are wrong, and gets its own message.
 */
function gallery_md_tables( $md ) {
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
	return $blocks;
}

/**
 * The shoot a Freepik id belongs to — the id with its last three digits dropped — or null when
 * the cell is not a bare id and so derives nothing.
 *
 * WHY THE ID AND NOT A WRITTEN LABEL. Freepik hands out ids in upload order, so one
 * contributor's one session lands in a contiguous run. `_gallery-images.md` records the spans it
 * measured — 497 ids wide within the batch this set actually shipped, 17,583 to the nearest
 * other batch — and the 1,000-wide bucket that sits between them with 2x headroom under and 17x
 * over. The point of DERIVING rather than reading a label is that a label is prose: a
 * concentration bound over hand-typed shoot names is one you turn green by retyping a name,
 * which is a gate that tests nothing and this repository has shipped enough of those. A Freepik
 * id cannot be retyped without also falsifying the source the row's own Licence cell stakes.
 */
function gallery_shoot_key( $freepik ) {
	if ( ! preg_match( '/^\d+$/', $freepik ) ) {
		return null;
	}
	return 'fp-' . (string) intdiv( (int) $freepik, 1000 );
}

/**
 * How many registers the manifest declares, or null when it declares no Registers table at all.
 * Selected by a header cell reading exactly `Register` — the image table's `Role` and the
 * Registers table's own `Slugs` are both deliberately not matches, the same discipline
 * gallery_manifest_table() uses to avoid reading one table as the other.
 *
 * This is the DIVISOR of the per-shoot cap, which is why it is read and never hardcoded. The
 * register table is the manifest's own statement of how many distinct looks the set carries, so
 * the bound it implies has to move when that statement moves: add a register and the cap
 * loosens, because the set just claimed more variety and has to be allowed to spend it. A number
 * typed here instead would be this auditor's taste standing in for the file's claim.
 */
function gallery_register_count( $md ) {
	foreach ( gallery_md_tables( $md ) as $block ) {
		$is_reg = false;
		foreach ( gallery_cells( $block[0][1] ) as $cell ) {
			if ( 'register' === strtolower( trim( $cell, " \t`*" ) ) ) {
				$is_reg = true;
			}
		}
		if ( ! $is_reg ) {
			continue;
		}
		$n = 0;
		foreach ( array_slice( $block, 1 ) as $r ) {
			if ( ! gallery_is_separator( gallery_cells( $r[1] ) ) ) {
				++$n;
			}
		}
		return $n;
	}
	return null;
}

function gallery_manifest_table( $md ) {
	foreach ( gallery_md_tables( $md ) as $block ) {
		$slug_col  = null;
		$lic_col   = null;
		$shoot_col = null;
		$fp_col    = null;
		foreach ( gallery_cells( $block[0][1] ) as $ci => $cell ) {
			$name = strtolower( trim( $cell, " \t`*" ) );
			if ( 'slug' === $name ) {
				$slug_col = $ci;
			}
			if ( 'licence' === $name || 'license' === $name ) {
				$lic_col = $ci;
			}
			if ( 'shoot' === $name ) {
				$shoot_col = $ci;
			}
			if ( 'freepik' === $name ) {
				$fp_col = $ci;
			}
		}
		if ( null === $slug_col ) {
			continue;
		}
		$rows = array();
		foreach ( array_slice( $block, 1 ) as $r ) {
			$cells = gallery_cells( $r[1] );
			if ( gallery_is_separator( $cells ) ) {
				continue;
			}
			$rows[] = array(
				'line'    => $r[0],
				'slug'    => isset( $cells[ $slug_col ] ) ? trim( $cells[ $slug_col ], " \t`" ) : '',
				'licence' => ( null !== $lic_col && isset( $cells[ $lic_col ] ) ) ? trim( $cells[ $lic_col ], " \t`" ) : '',
				'shoot'   => ( null !== $shoot_col && isset( $cells[ $shoot_col ] ) ) ? trim( $cells[ $shoot_col ], " \t`" ) : '',
				'freepik' => ( null !== $fp_col && isset( $cells[ $fp_col ] ) ) ? trim( $cells[ $fp_col ], " \t`" ) : '',
			);
		}
		return array( 'rows' => $rows, 'licence_col' => $lic_col, 'shoot_col' => $shoot_col, 'freepik_col' => $fp_col );
	}
	return null;
}

/** Every `<section class="strip">` a gallery renders, with the pair it declares, in document order. */
function gallery_strips( $src ) {
	$out = array();
	if ( ! preg_match_all( '/<section\b[^>]*>/i', $src, $m ) ) {
		return $out;
	}
	foreach ( $m[0] as $tag ) {
		if ( ! preg_match( '/\bclass\s*=\s*"[^"]*\bstrip\b[^"]*"/i', $tag ) ) {
			continue;
		}
		$out[] = array(
			'tpl'  => preg_match( '/\bdata-tpl\s*=\s*"([^"]*)"/i', $tag, $t ) ? trim( $t[1] ) : '',
			'pers' => preg_match( '/\bdata-pers\s*=\s*"([^"]*)"/i', $tag, $p ) ? trim( $p[1] ) : '',
		);
	}
	return $out;
}

/**
 * Every declaration block one anchor's selector opens, CONCATENATED — or null if it opens none.
 *
 * preg_match_all and not preg_match, and that is the one lesson this file already paid for once.
 * RT_MOCKUP_NO_AXES reads only the FIRST `:root{…}` with `/:root\s*\{(.*?)\}/s`, and a COMMENT
 * spelling that selector out in prose captured a three-byte block and reported all five axes
 * missing — a verifier cannot tell CSS from a sentence about CSS. Taking every block instead makes
 * a prose mention contribute nothing rather than shadow the real one, and it is also what the
 * cascade does: two rules with the same selector both apply, so reading only the first would let a
 * later override go unseen. `[^{}]*` rather than `.*?` for the same reason from the other side — a
 * body that would have to cross a brace is not a declaration body.
 */
function gallery_anchor_block( $src, $anchor ) {
	$re = '/\[data-anchor\s*=\s*"' . preg_quote( $anchor, '/' ) . '"\]\s*\{([^{}]*)\}/';
	if ( ! preg_match_all( $re, $src, $m ) ) {
		return null;
	}
	return implode( "\n", $m[1] );
}

/** Every slug a gallery hydrates an `<img>` from. Sorted and de-duplicated; `''` survives on purpose. */
function gallery_used_slugs( $src ) {
	$out = array();
	if ( preg_match_all( '/\bdata-img\s*=\s*"([^"]*)"/i', $src, $m ) ) {
		foreach ( $m[1] as $s ) {
			$out[ trim( $s ) ] = true;
		}
	}
	ksort( $out, SORT_STRING );
	return array_keys( $out );
}

function gallery_slug_label( $s ) {
	return ( '' === $s ) ? 'an empty data-img=""' : '`' . $s . '`';
}

/* ---- RT_GALLERY_NOT_BUILT ----
 * Precondition of the walk below, not a member of it: discovery here is by glob
 * (html_assets_deep(), fed via $mockup_assets), and a MISSING file is unreachable by discovery —
 * absence emits nothing. Hardcoded paths, like $PROOF_MOCKUPS above and for the same reason: a
 * missing generated artifact must FAIL rather than silently skip the check. Gated on the
 * generator's own presence so this can never fire inside a fixture root that writes only
 * index.html (fx_gal()) and never the generator — see tests/test-framework-audit.php. */
$gal_gen = $mockup_asset_root . '/gallery/_build-gallery.php';
$gal_out = $mockup_asset_root . '/gallery/index.html';
if ( file_exists( $gal_gen ) && ! file_exists( $gal_out ) ) {
	add(
		'RT_GALLERY_NOT_BUILT',
		'FAIL',
		'html-mockup',
		'assets/gallery/index.html is missing while its generator sits beside it. The gallery is'
			. ' generated output and is no longer tracked, so a fresh clone has none until it is'
			. ' built: php skills/html-mockup/assets/gallery/_build-gallery.php'
	);
}

/* ---- RT_CHASSIS_NOT_BUILT ----
 * Mirrors RT_GALLERY_NOT_BUILT immediately above, for the SECOND artifact the same generator run
 * writes (design.md D1): the client chassis. Same reasoning, same shape — discovery here is by
 * glob (html_assets_deep()), and a MISSING file is unreachable by discovery, so a missing chassis
 * must FAIL rather than silently skip the check. Gated on the generator's own presence so this can
 * never fire inside a fixture root that writes only index.html (fx_gal()) and never the generator
 * — see tests/test-framework-audit.php. Two files, not one: `_build-gallery.php` writes both site
 * types in the same run, so a build that produced one and not the other is the interrupted-write
 * case the generator's own "cannot create $CHASSIS_DIR" fail() cannot see once the directory does
 * exist. */
foreach ( array( 'corporate', 'ecommerce' ) as $chassis_site ) {
	$chassis_out = $mockup_asset_root . '/chassis/' . $chassis_site . '.html';
	if ( file_exists( $gal_gen ) && ! file_exists( $chassis_out ) ) {
		add(
			'RT_CHASSIS_NOT_BUILT',
			'FAIL',
			'html-mockup',
			'assets/chassis/' . $chassis_site . '.html is missing while its generator sits beside'
				. ' it (the same generator as the gallery). The chassis is generated output and is'
				. ' not tracked, so a fresh clone has none until it is built:'
				. ' php skills/html-mockup/assets/gallery/_build-gallery.php'
		);
	}
}

/* ---- RT_GALLERY_STALE ----
 * The other half of the same question. RT_GALLERY_NOT_BUILT above asks "was it built?"; nothing
 * asked "was it built from THESE inputs?", and since index.html stopped being tracked nothing
 * else can: git no longer holds a version to diff the generated file against. Edit an archetype
 * doc or the image manifest, skip the rebuild, and every gate in this repo stays green over
 * output that no longer matches what produced it.
 *
 * THE DEFINITION OF THE INPUT SET IS NOT REPEATED HERE. It is required out of the audited tree,
 * from the same file the generator requires, because a fingerprint stated twice is two
 * fingerprints — and two answers to one question read as staleness the moment they drift, which
 * is the failure this row exists to detect rather than to cause. skills/framework-audit/SKILL.md
 * names which of two implementations loses; the honest way to obey it is to have only one.
 *
 * GATED ON THE DEFINITION'S PRESENCE IN THE AUDITED ROOT, exactly as its sibling gates on the
 * generator's. That is what keeps the row out of every fixture root that writes an index.html and
 * nothing else, and it is also the only conditional under which the require below is reachable.
 */
$gal_fp_lib = $mockup_asset_root . '/gallery/_gallery-fingerprint.php';
if ( file_exists( $gal_gen ) && file_exists( $gal_out ) && file_exists( $gal_fp_lib ) ) {
	require_once $gal_fp_lib;
	$gal_dir    = $mockup_asset_root . '/gallery';
	$gal_want   = nm_gallery_input_digest( $gal_dir );
	$gal_have   = nm_gallery_embedded_digest( slurp( $gal_out ) );
	$gal_inputs = count( nm_gallery_input_manifest( $gal_dir ) );
	if ( '' === $gal_have ) {
		/* Separated from the mismatch below because the two need different reactions. An output
		   with no fingerprint at all was either built by a generator that predates this contract
		   or edited by hand, and both are answered by the same command — but a reader told
		   "different inputs" would go hunting for an edit that never happened. */
		add(
			'RT_GALLERY_STALE',
			'FAIL',
			'html-mockup',
			'assets/gallery/index.html records no input fingerprint, so nothing can say whether it'
				. ' still matches the ' . $gal_inputs . ' file(s) it is generated from — it predates the'
				. ' fingerprint or was hand-edited. Regenerate:'
				. ' php skills/html-mockup/assets/gallery/_build-gallery.php'
		);
	} elseif ( $gal_want !== $gal_have ) {
		/* Both digests are quoted, truncated: the row has to be readable on one line, and the
		   first 12 hex characters are enough for a human to tell two builds apart. Naming WHICH
		   input moved would mean embedding the whole per-file manifest in the output and keeping
		   a second format in sync — a bigger contract than the remedy needs, since the remedy is
		   the same command whichever file it was. */
		add(
			'RT_GALLERY_STALE',
			'FAIL',
			'html-mockup',
			'assets/gallery/index.html was generated from a different set of inputs than the ones on'
				. ' disk now: over its ' . $gal_inputs . ' input file(s) — the image manifest, img/*.webp,'
				. ' the TPL-*.md archetypes, fonts/_fonts.php and its woff2 files, design-tokens.md and'
				. ' the generator itself — the tree hashes to ' . substr( $gal_want, 0, 12 ) . ' while the'
				. ' output records ' . substr( $gal_have, 0, 12 ) . '. Regenerate:'
				. ' php skills/html-mockup/assets/gallery/_build-gallery.php'
		);
	}
}

$gallery_manifests_seen = array();
foreach ( $mockup_assets as $gal_path ) {
	$gal_name = substr( $gal_path, strlen( $mockup_asset_root ) + 1 );
	if ( '_' === substr( basename( $gal_path ), 0, 1 ) ) {
		continue;
	}
	$gal_src    = slurp( $gal_path );
	$gal_strips = gallery_strips( $gal_src );
	if ( ! preg_match( '#(^|/)gallery/#', $gal_name ) && array() === $gal_strips ) {
		continue;
	}
	$gal_where = 'assets/' . $gal_name;

	/* ---- RT_GALLERY_NOT_DISTINCT ---- */

	if ( array() === $gal_strips ) {
		add(
			'RT_GALLERY_NOT_DISTINCT',
			'FAIL',
			'html-mockup',
			$gal_where . ' is a gallery asset that renders no <section class="strip"> — with nothing to compare, every rule below'
				. ' passes over an empty set, which is a gate reporting green on the absence of its own subject'
		);
	}

	$gal_pairs = array();
	$gal_cards = array();
	foreach ( $gal_strips as $gal_ix => $gal_st ) {
		$gal_gaps = array();
		if ( '' === $gal_st['tpl'] ) {
			$gal_gaps[] = 'data-tpl';
		}
		if ( '' === $gal_st['pers'] ) {
			$gal_gaps[] = 'data-pers';
		}
		if ( array() !== $gal_gaps ) {
			add(
				'RT_GALLERY_NOT_DISTINCT',
				'FAIL',
				'html-mockup',
				$gal_where . ' strip #' . ( $gal_ix + 1 ) . ' declares no ' . implode( ' and no ', $gal_gaps )
					. ' — a strip that does not say which archetype and which anchor it renders is a card the pair and axis rules below never see'
			);
			continue;
		}
		$gal_pair = $gal_st['tpl'] . ' × ' . $gal_st['pers'];
		if ( isset( $gal_pairs[ $gal_pair ] ) ) {
			add(
				'RT_GALLERY_NOT_DISTINCT',
				'FAIL',
				'html-mockup',
				$gal_where . ' renders the pair ' . $gal_pair . ' twice, at strips #' . $gal_pairs[ $gal_pair ] . ' and #' . ( $gal_ix + 1 )
					. ' — one card per TPL × PERS pair, so two strips sharing an anchor have to declare different archetypes or they are one card printed twice'
			);
			continue;
		}
		$gal_pairs[ $gal_pair ] = $gal_ix + 1;
		$gal_cards[]            = array( 'tpl' => $gal_st['tpl'], 'pers' => $gal_st['pers'], 'pair' => $gal_pair );
	}

	/* The axes come from the anchor's own `[data-anchor]` block and not from the strip's markup:
	   that block is what actually PAINTS the strip, so an eyebrow claiming an anchor the stylesheet
	   never declares is a card that reads as whatever `:root` happened to leave behind. */
	$gal_sigs = array();
	foreach ( $gal_cards as $gal_c ) {
		if ( array_key_exists( $gal_c['pers'], $gal_sigs ) ) {
			continue;
		}
		$gal_blk = gallery_anchor_block( $gal_src, $gal_c['pers'] );
		$gal_sig = ( null === $gal_blk ) ? null : axis_signature_of_block( $gal_blk );
		/* "no block" and "a block declaring not one of the eight axes" are the same finding, and
		   collapsing them is what stops the comment above from becoming a loophole: taking EVERY
		   matching block means a CSS comment quoting the selector also matches, so a file whose real
		   block was deleted would otherwise report a declaration that is three bytes of prose — and
		   an empty signature against a real one reads as eight axes apart, which passes. Compared
		   against a same-length blank array rather than a hardcoded one, since nm_axes() owns the
		   axis count now (style-catalog PR 2b widened it from 5 to 8). */
		if ( null === $gal_sig || array_fill( 0, count( $gal_sig ), '' ) === array_values( $gal_sig ) ) {
			add(
				'RT_GALLERY_NOT_DISTINCT',
				'FAIL',
				'html-mockup',
				$gal_where . ' has a strip at anchor `' . $gal_c['pers'] . '` but no [data-anchor="' . $gal_c['pers'] . '"] block declares its axes'
					. ' — the card is labelled with an anchor the stylesheet never paints, so what the reader compares is not what the eyebrow says'
			);
			continue;
		}
		$gal_sigs[ $gal_c['pers'] ] = $gal_sig;
	}

	/* ≥6 of 8, the same bar RT_STYLE_TOO_SIMILAR and RT_PROOF_NOT_DISTINCT hold, and
	   the style catalog states the reason in as many words: two anchors that agree on more
	   than two axes are the same site with a different accent colour, not two personalities. Only
	   same-archetype pairs are compared — two cards of DIFFERENT archetypes are already separated by
	   their section inventory, which is the axis the eight perceptual ones do not carry. */
	$gal_compared = array();
	foreach ( $gal_cards as $gal_x ) {
		foreach ( $gal_cards as $gal_y ) {
			if ( $gal_x['tpl'] !== $gal_y['tpl'] || $gal_x['pers'] === $gal_y['pers'] ) {
				continue;
			}
			if ( ! isset( $gal_sigs[ $gal_x['pers'] ] ) || ! isset( $gal_sigs[ $gal_y['pers'] ] ) ) {
				continue;
			}
			$gal_key = ( strcmp( $gal_x['pers'], $gal_y['pers'] ) < 0 )
				? $gal_x['tpl'] . '|' . $gal_x['pers'] . '|' . $gal_y['pers']
				: $gal_x['tpl'] . '|' . $gal_y['pers'] . '|' . $gal_x['pers'];
			if ( isset( $gal_compared[ $gal_key ] ) ) {
				continue;
			}
			$gal_compared[ $gal_key ] = true;
			$gal_same                 = axis_matches( $gal_sigs[ $gal_x['pers'] ], $gal_sigs[ $gal_y['pers'] ] );
			$gal_differ               = 8 - count( $gal_same );
			if ( $gal_differ < 6 ) {
				add(
					'RT_GALLERY_NOT_DISTINCT',
					'FAIL',
					'html-mockup',
					$gal_where . ': ' . $gal_x['pair'] . ' and ' . $gal_y['pair'] . ' share an archetype and their anchors differ on only '
						. $gal_differ . ' of 8 axes — they match on ' . implode( ', ', $gal_same )
						. '. Same sections under two anchors have to read as two sites, or the catalog is one card with a different accent colour'
				);
			}
		}
	}

	/* ---- RT_GALLERY_NO_MANIFEST ---- */

	$gal_used     = gallery_used_slugs( $gal_src );
	$gal_manifest = dirname( $gal_path ) . '/_gallery-images.md';
	if ( ! is_file( $gal_manifest ) ) {
		if ( array() !== $gal_used ) {
			add(
				'RT_GALLERY_NO_MANIFEST',
				'FAIL',
				'html-mockup',
				$gal_where . ' renders ' . count( $gal_used ) . ' image slug(s) and no _gallery-images.md sits beside it'
					. ' — es_photo() resolves a WordPress ATTACHMENT SLUG, not a URL and not a data: URI, so an image with no row is a promise'
					. ' the native build cannot keep and the client gets the grey box the mockup told them they would not get'
			);
		}
		continue;
	}

	$gal_man_rel = substr( $gal_manifest, strlen( $mockup_asset_root ) + 1 );
	$gal_man_src = slurp( $gal_manifest );
	$gal_table   = gallery_manifest_table( $gal_man_src );
	if ( null === $gal_table ) {
		add(
			'RT_GALLERY_NO_MANIFEST',
			'FAIL',
			'html-mockup',
			'assets/' . $gal_man_rel . ' carries no table with a `Slug` column, so none of the ' . count( $gal_used )
				. ' image slug(s) ' . $gal_where . ' renders can be matched to anything — the manifest is prose, not a contract'
		);
		continue;
	}

	$gal_by_slug = array();
	foreach ( $gal_table['rows'] as $gal_r ) {
		if ( '' !== $gal_r['slug'] ) {
			$gal_by_slug[ $gal_r['slug'] ] = $gal_r;
		}
	}
	$gal_unlisted = array();
	foreach ( $gal_used as $gal_s ) {
		if ( ! isset( $gal_by_slug[ $gal_s ] ) ) {
			$gal_unlisted[] = gallery_slug_label( $gal_s );
		}
	}
	if ( array() !== $gal_unlisted ) {
		add(
			'RT_GALLERY_NO_MANIFEST',
			'FAIL',
			'html-mockup',
			$gal_where . ' renders ' . implode( ', ', $gal_unlisted ) . ' with no row in ' . basename( $gal_man_rel )
				. ' — the file name IS the attachment slug, so an image the manifest does not carry is one the operator cannot upload and es_photo() cannot resolve'
		);
	}

	/* Row hygiene is a property of the MANIFEST, not of the asset that reads it, so two galleries in
	   one directory report it once rather than twice. */
	if ( isset( $gallery_manifests_seen[ $gal_manifest ] ) ) {
		continue;
	}
	$gallery_manifests_seen[ $gal_manifest ] = true;

	$gal_no_slug = array();
	$gal_no_lic  = array();
	foreach ( $gal_table['rows'] as $gal_r ) {
		if ( '' === $gal_r['slug'] ) {
			$gal_no_slug[] = 'line ' . $gal_r['line'];
			continue;
		}
		if ( '' === $gal_r['licence'] ) {
			$gal_no_lic[] = '`' . $gal_r['slug'] . '` (line ' . $gal_r['line'] . ')';
		}
	}
	if ( array() !== $gal_no_slug ) {
		add(
			'RT_GALLERY_NO_MANIFEST',
			'FAIL',
			'html-mockup',
			'assets/' . $gal_man_rel . ' has ' . count( $gal_no_slug ) . ' row(s) with an empty Slug cell — ' . implode( ', ', $gal_no_slug )
				. '. A row without a slug names no attachment, so it documents an image the build has no way to ask for'
		);
	}
	if ( array() !== $gal_no_lic ) {
		add(
			'RT_GALLERY_NO_MANIFEST',
			'FAIL',
			'html-mockup',
			'assets/' . $gal_man_rel . ' has ' . count( $gal_no_lic ) . ' row(s) with no licence — ' . implode( ', ', $gal_no_lic )
				. ( null === $gal_table['licence_col'] ? '. The table carries no Licence column at all' : '' )
				. '. This repository is public under Apache-2.0 and its LICENSE hands every reader the right to redistribute what it contains;'
				. ' an image whose terms are recorded nowhere is a right nobody checked we had to give'
		);
	}

	/* ---- RT_GALLERY_ONE_SHOOT ---- */
	/*
	 * WHICH DEFECT THIS IS, and the one it deliberately is not. What a reader SEES is two
	 * photographs of one shoot rendered side by side; what CAUSES it is one shoot owning half the
	 * set. Adjacency is the tempting target and it was measured and rejected: this audit reads
	 * generated HTML, where the only order available is document order, and in the gallery's own
	 * corporate strip 2 of the 5 consecutive `data-img` pairs cross a `<section>` boundary — the
	 * hero and the first services card are consecutive in the DOM and a full screen apart on it.
	 * A 40% false-adjacency rate is not a gate. Scoping to the real grid container would mean
	 * hardcoding `class="items"` from one builder into the auditor, and a class rename would then
	 * silently empty the check — the silent exit this file already refuses twice above.
	 *
	 * Concentration has neither problem: it counts rows in the manifest, which is the artefact
	 * that states the claim. It is also the stabler subject — shuffling the card order would
	 * satisfy an adjacency rule while every strip still showed the same man seven times.
	 *
	 * THE BOUND IS ceil(N / R), not a number chosen here. R is the manifest's own register count
	 * and N its own row count, so the file supplies both halves: a set that declares R registers
	 * has claimed R distinct looks, and one session holding more than an Rth of the images means
	 * the set has fewer distinct looks than its own table says. ceil() rather than floor() so a
	 * set that does not divide evenly is not failed for arithmetic it cannot satisfy.
	 */

	$gal_reg_n = gallery_register_count( $gal_man_src );
	if ( null === $gal_reg_n || 0 === $gal_reg_n ) {
		add(
			'RT_GALLERY_ONE_SHOOT',
			'FAIL',
			'html-mockup',
			'assets/' . $gal_man_rel . ' declares no Registers table with rows, so the per-shoot cap has no divisor'
				. ' — a set that states no register structure has made no claim about how many distinct looks it carries,'
				. ' and a concentration bound measured against no claim is a gate passing over its own missing subject'
		);
	}

	if ( null === $gal_table['shoot_col'] ) {
		add(
			'RT_GALLERY_ONE_SHOOT',
			'FAIL',
			'html-mockup',
			'assets/' . $gal_man_rel . ' carries no Shoot column, so nothing records which images came from one session'
				. ' — four registers say what the set is ABOUT and nothing about how many times a shutter was pressed,'
				. ' which is exactly the axis this set failed on while every register read as healthy'
		);
	} elseif ( null === $gal_table['freepik_col'] ) {
		add(
			'RT_GALLERY_ONE_SHOOT',
			'FAIL',
			'html-mockup',
			'assets/' . $gal_man_rel . ' carries a Shoot column and no Freepik column to derive it from'
				. ' — a shoot cell nothing can be checked against is a label, and a label is edited until the check goes green'
		);
	} else {
		$gal_shoots    = array();
		$gal_bad_shoot = array();
		foreach ( $gal_table['rows'] as $gal_r ) {
			if ( '' === $gal_r['slug'] ) {
				continue;
			}
			$gal_want = gallery_shoot_key( $gal_r['freepik'] );
			if ( null === $gal_want || $gal_want !== $gal_r['shoot'] ) {
				$gal_bad_shoot[] = '`' . $gal_r['slug'] . '` says `' . $gal_r['shoot'] . '` for Freepik `' . $gal_r['freepik']
					. '` which derives ' . ( null === $gal_want ? 'nothing' : '`' . $gal_want . '`' ) . ' (line ' . $gal_r['line'] . ')';
				continue;
			}
			$gal_shoots[ $gal_want ][] = $gal_r['slug'];
		}

		if ( array() !== $gal_bad_shoot ) {
			add(
				'RT_GALLERY_ONE_SHOOT',
				'FAIL',
				'html-mockup',
				'assets/' . $gal_man_rel . ' has ' . count( $gal_bad_shoot ) . ' Shoot cell(s) that do not match their own Freepik id — '
					. implode( ', ', $gal_bad_shoot )
					. '. The Shoot cell is the Freepik id with its last three digits dropped and it is RE-DERIVED here rather than believed,'
					. ' because a shoot label nobody checks is one you retype until the concentration bound below goes green'
			);
		}

		if ( null !== $gal_reg_n && 0 < $gal_reg_n && array() !== $gal_shoots ) {
			$gal_n = 0;
			foreach ( $gal_shoots as $gal_members ) {
				$gal_n += count( $gal_members );
			}
			$gal_cap = (int) ceil( $gal_n / $gal_reg_n );
			foreach ( $gal_shoots as $gal_key => $gal_members ) {
				if ( count( $gal_members ) <= $gal_cap ) {
					continue;
				}
				add(
					'RT_GALLERY_ONE_SHOOT',
					'FAIL',
					'html-mockup',
					'assets/' . $gal_man_rel . ' draws ' . count( $gal_members ) . ' of its ' . $gal_n . ' images from the one shoot `' . $gal_key . '` — '
						. implode( ', ', array_map( function ( $s ) { return '`' . $s . '`'; }, $gal_members ) )
						. '. The manifest declares ' . $gal_reg_n . ' registers, so the cap is ceil(' . $gal_n . '/' . $gal_reg_n . ') = ' . $gal_cap
						. ': a set whose own table claims ' . $gal_reg_n . ' distinct looks cannot take more than a ' . $gal_reg_n . 'th of its images from one session,'
						. ' or the variety is arithmetic rather than photography.'
						. ' WHAT THIS ROW CANNOT SEE: it reads the Freepik id and nothing else, so two photographs of the same subject from two'
						. ' genuinely different shoots — another photographer, another day, the same grey shirt and the same chisel — pass it and'
						. ' still read to a client as one picture printed twice. Only an eye on a contact sheet catches that, which is step 5 of'
						. ' the manifest\'s own "Adding one"'
				);
			}
		}
	}
}

/* --------------------------------------------------- the builder's token layer
 *
 * RT_MOCKUP_NO_AXES above asks whether the file a project is COPIED FROM can express an axis.
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
 * Still a GLOB per skill and not four hardcoded filenames, for the reason RT_MOCKUP_NO_AXES gives
 * above: a SECOND asset dropped into any of those three assets/ directories without a token layer
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
			add( 'RT_TOKENS_HARDCODED_FONT', 'FAIL', 'ux-design-system', 'design-tokens.md still hardcodes "' . $hardcoded . '" as an example font — move concrete pairings into references/style-catalog/' );
		}
	}
}

$uxds_skill = $root . '/skills/ux-design-system/SKILL.md';
if ( file_exists( $uxds_skill ) ) {
	$uxds_src = slurp( $uxds_skill );
	if ( false === strpos( $uxds_src, 'style-catalog' ) ) {
		add( 'RT_CATALOG_UNMENTIONED', 'FAIL', 'ux-design-system', 'SKILL.md never mentions style-catalog/ — the style catalog is unreachable from the skill' );
	}
	/* Scoped to "## Execution Steps" alone (same slicing shape collect_skill_steps() already uses
	   above for the same heading), and matched case-insensitively for BOTH "axis" and "axes": a
	   whole-file scan for the bare word "axis" is too easy to satisfy by accident (CSS "main
	   axis"/"cross axis", "x-axis", or a hit inside "praxis") without the STEPS actually routing
	   the reader to resolving them, which is the property this row exists to guarantee. */
	$uxds_steps = null;
	if ( preg_match( '/^## Execution Steps\b[^\n]*\n(.*?)(?=\n## |\z)/ms', $uxds_src, $sm ) ) {
		$uxds_steps = $sm[1];
	}
	if ( null === $uxds_steps || ( false === stripos( $uxds_steps, 'axis' ) && false === stripos( $uxds_steps, 'axes' ) ) ) {
		add( 'RT_UXDS_NO_AXIS_STEP', 'FAIL', 'ux-design-system', 'SKILL.md Execution Steps never mention the axes — the personality dialogue is unreachable from the skill' );
	}
}

/* -------------------------------------------------- art-direction ledger (style-catalog PR 5b)
 *
 * shipped-log.md is the measured half of a memory skills/blind-judges/references/corpus.md
 * already half-built (the seen half): both are written by the ORCHESTRATOR after both verdicts
 * land, same moment, never by a judge — corpus.md states that rule for itself; this file follows
 * it. Read here, offline, because it is the only surface either question below CAN be asked
 * against: es_manifest_read() is a live WordPress option this static tree walk cannot reach, and a
 * delivered mockup's own HTML is never stored in this repo at all (corpus.md: "client work does
 * not belong in this repository"). Both rows below answer their question from THIS table, exactly
 * the way RT_GALLERY_STALE above answers its own offline-reachable question instead of one only a
 * live build could answer.
 */
$ledger_file = $root . '/skills/ux-design-system/references/shipped-log.md';
if ( file_exists( $ledger_file ) ) {
	$ledger_rows = ledger_table_rows( slurp( $ledger_file ) );

	/* ---- RT_STYLE_REPEATS_RECENT ----
	 * WARN, never FAIL: house-rules.md:31 — "a gate that always fails is a gate everyone learns to
	 * skip." Repetition is a judgment call the JUDGES make (blind-judges), over the identical
	 * 5-delivery window (corpus.md "Retention": "Five matches the window RT_STYLE_REPEATS_RECENT
	 * uses, so the measured half and the seen half never disagree about what 'recent' means") —
	 * this row only advises. WARN when the NEWEST row's Style also appears among the 4 before it in
	 * that same 5-row window; a match further back than that is invisible by design, same as an
	 * entry Judge A is never shown.
	 */
	$ldg_window = array_slice( $ledger_rows, -5 );
	$ldg_n      = count( $ldg_window );
	if ( $ldg_n >= 2 ) {
		$ldg_newest_style = trim( isset( $ldg_window[ $ldg_n - 1 ]['Style'] ) ? $ldg_window[ $ldg_n - 1 ]['Style'] : '' );
		if ( '' !== $ldg_newest_style ) {
			for ( $ldg_i = 0; $ldg_i < $ldg_n - 1; $ldg_i++ ) {
				$ldg_prior_style = trim( isset( $ldg_window[ $ldg_i ]['Style'] ) ? $ldg_window[ $ldg_i ]['Style'] : '' );
				if ( $ldg_prior_style === $ldg_newest_style ) {
					add(
						'RT_STYLE_REPEATS_RECENT',
						'WARN',
						'ux-design-system',
						'shipped-log.md: "' . $ldg_newest_style . '" was also shipped ' . ( $ldg_n - 1 - $ldg_i )
							. ' delivery(ies) ago, within the last 5 — worth a second look, not a block'
					);
					break;
				}
			}
		}
	}

	/* ---- RT_STYLE_UNRESOLVED_DEFAULT ----
	 * FAIL, not a judgment call: mockup-guide.md:436-447 recorded the real defect this closes —
	 * every corporate project shipped PERS-INSTITUTIONAL "not because anyone chose them but because
	 * nobody was asked to." PR 5a made the intake ask and persist the answer
	 * (es_record_style_resolution()); this row is what makes the SILENCE audible when that answer
	 * never reaches the ledger. A row naming which chassis a delivery started from (Chassis) with
	 * no resolved Style beside it is the offline-visible shadow of a delivered mockup that still
	 * carries the untouched chassis default with no style resolution ever recorded — the one fact
	 * the live es_manifest_read()['design'] would hold and this audit cannot read directly.
	 *
	 * style-catalog PR 6 exception: a ROUTE-BESPOKE delivery legitimately carries no Style at all
	 * -- "Zero Precharge" is its own first requirement, not the untended default this rule exists
	 * to catch. `Route` is the one cell offline evidence can read to tell the two apart; a blank
	 * Route beside a blank Style is still the original, unrelated defect.
	 */
	foreach ( $ledger_rows as $ldg_row ) {
		$ldg_style   = trim( isset( $ldg_row['Style'] ) ? $ldg_row['Style'] : '' );
		$ldg_chassis = trim( isset( $ldg_row['Chassis'] ) ? $ldg_row['Chassis'] : '' );
		$ldg_route   = trim( isset( $ldg_row['Route'] ) ? $ldg_row['Route'] : '' );
		if ( '' !== $ldg_chassis && '' === $ldg_style && 'bespoke' !== $ldg_route ) {
			$ldg_client = ( isset( $ldg_row['Client'] ) && '' !== trim( $ldg_row['Client'] ) ) ? trim( $ldg_row['Client'] ) : 'an unnamed delivery';
			$ldg_date   = ( isset( $ldg_row['Date'] ) && '' !== trim( $ldg_row['Date'] ) ) ? trim( $ldg_row['Date'] ) : 'an undated row';
			add(
				'RT_STYLE_UNRESOLVED_DEFAULT',
				'FAIL',
				'ux-design-system',
				'shipped-log.md: ' . $ldg_client . ' (' . $ldg_date . ') shipped on the "' . $ldg_chassis
					. '" chassis default with no Style recorded — nobody was asked, or the answer never reached the ledger'
			);
		}
	}

	/* ---- RT_STYLE_PRECHARGE_UNSHIPPED ----
	 * FAIL, root cause 6 (proposal): "structure varied, look varied, CONFIGURATION never did" — the
	 * demo gallery moved exactly one toggle off default across 67 strips. Every STY-*.md now
	 * declares its own "## Toggle precharge" table (PR 4a); this asks whether a REAL delivery
	 * shipped what ITS OWN resolved style declared, read from the same offline ledger
	 * RT_STYLE_UNRESOLVED_DEFAULT above already reads. NO UNIVERSAL FLOOR, rejected by design: the
	 * comparison is always against the RESOLVED style's own declared list, never a fixed count — a
	 * style declaring 2 toggles is exactly as satisfied by 2 shipped as a style declaring 6 is by 6,
	 * since nothing here ever asks "how many", only "does THIS style's own list match".
	 */
	foreach ( $ledger_rows as $ldg_prow ) {
		$ldg_pstyle = trim( isset( $ldg_prow['Style'] ) ? $ldg_prow['Style'] : '' );
		if ( '' === $ldg_pstyle ) {
			continue; // RT_STYLE_UNRESOLVED_DEFAULT's own case -- nothing declared to check here.
		}
		$sty_file = $root . '/skills/ux-design-system/references/style-catalog/' . $ldg_pstyle . '.md';
		if ( ! file_exists( $sty_file ) ) {
			continue; // an id naming no catalog entry names nothing this rule can check against.
		}
		$ldg_declared = style_precharge_rows( slurp( $sty_file ) );
		if ( array() === $ldg_declared ) {
			continue;
		}
		$ldg_shipped = ledger_toggle_map( isset( $ldg_prow['Toggles'] ) ? $ldg_prow['Toggles'] : '' );
		$ldg_pclient = ( isset( $ldg_prow['Client'] ) && '' !== trim( $ldg_prow['Client'] ) ) ? trim( $ldg_prow['Client'] ) : 'an unnamed delivery';
		foreach ( $ldg_declared as $ldg_tgl => $ldg_want ) {
			$ldg_got = array_key_exists( $ldg_tgl, $ldg_shipped ) ? $ldg_shipped[ $ldg_tgl ] : null;
			/* PRESENCE, NOT EQUALITY, and the difference is the whole point. A precharge is a
			   SUGGESTION the client confirms or overrides, which is what toggles.md offers them.
			   An exact-match check would FAIL a client for exercising exactly that choice, turning
			   a gate against sameness into a gate enforcing uniformity: the defect this change
			   exists to remove, rebuilt inside its own cure. This asks only whether the toggle was
			   DECIDED. An override is a decision; a blank is not. */
			if ( null === $ldg_got || '' === $ldg_got ) {
				add(
					'RT_STYLE_PRECHARGE_UNSHIPPED',
					'FAIL',
					'ux-design-system',
					'shipped-log.md: ' . $ldg_pclient . ' resolved "' . $ldg_pstyle . '", which precharges `' . $ldg_tgl
						. '` = "' . $ldg_want . '", but the ledger shows no shipped value for it at all'
						. ' - a precharged toggle may be OVERRIDDEN by the client, never left undecided'
				);
			}
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
