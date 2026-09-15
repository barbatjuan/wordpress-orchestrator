<?php
/**
 * Behavioural assertions for the shared toolbox: `color.php`, `scrim.php`, `huella.php`
 * (`skills/html-mockup/assets/herramientas/`), lifted from `_build-gallery.php`
 * (`openspec/changes/plantillas-reales`, PR 1a), and `veredicto.php`, which seals a Plantilla's
 * veredicto against huella.php's fingerprint (PR 1e).
 *
 * Run:  php tests/test-herramientas.php     (exit 0 = green)
 *
 * WHY THIS EXISTS. These three files are dual-mode: a library `_build-gallery.php` (and, from a
 * later PR, `framework-audit.php`) `require`s, and a standalone CLI with its own `0`/`1`/`2` exit
 * contract. Neither half is exercised by the gallery build alone — a CLI usage error never runs
 * during a normal build, and `--comprobar`/`--maqueta`/`--peor-pixel` have no gallery call site at
 * all. This suite is the only thing that proves the CLI contract `design.md` states.
 *
 * TWO KINDS OF ASSERTION. Direct `require_once` + in-process calls for the LIBRARY half (fast,
 * and exceptions can be caught here without a subprocess — these tools throw, they no longer
 * `exit()` on their own). `exec()` child processes for the CLI half, because an exit code and a
 * process's own STDOUT/STDERR can only be observed from outside it.
 */
define( 'NM_HERRAMIENTAS_DIR', dirname( __DIR__ ) . '/skills/html-mockup/assets/herramientas' );

require_once NM_HERRAMIENTAS_DIR . '/color.php';
require_once NM_HERRAMIENTAS_DIR . '/scrim.php';
require_once NM_HERRAMIENTAS_DIR . '/huella.php';

$pass = 0;
$fail = 0;
function ok( $cond, $label ) {
	global $pass, $fail;
	if ( $cond ) {
		$pass++;
		echo "  OK   $label\n";
	} else {
		$fail++;
		echo "  FAIL $label\n";
	}
}

/** Run a herramienta's CLI as a real child process; only way to observe its own exit code. */
function run_cli( $tool, $args ) {
	if ( ! function_exists( 'exec' ) ) {
		return array( 'out' => '', 'code' => -1, 'no_exec' => true );
	}
	$cmd  = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( NM_HERRAMIENTAS_DIR . '/' . $tool ) . ' ' . $args;
	$out  = array();
	$code = -1;
	exec( $cmd . ' 2>&1', $out, $code );
	return array( 'out' => implode( "\n", $out ), 'code' => $code );
}

/** Same as `run_cli()`, but with GD's WebP support disabled in the child — the one way to
 *  reproduce "GD absent" without actually uninstalling the extension. */
function run_cli_no_gd( $tool, $args ) {
	if ( ! function_exists( 'exec' ) ) {
		return array( 'out' => '', 'code' => -1, 'no_exec' => true );
	}
	$cmd  = escapeshellarg( PHP_BINARY ) . ' -d disable_functions=imagecreatefromwebp '
		. escapeshellarg( NM_HERRAMIENTAS_DIR . '/' . $tool ) . ' ' . $args;
	$out  = array();
	$code = -1;
	exec( $cmd . ' 2>&1', $out, $code );
	return array( 'out' => implode( "\n", $out ), 'code' => $code );
}

function nm_tmpdir( $label ) {
	$dir = sys_get_temp_dir() . '/nm-herramientas-' . $label . '-' . getmypid() . '-' . mt_rand( 1000, 9999 );
	@mkdir( $dir, 0777, true );
	return str_replace( '\\', '/', $dir );
}

function nm_write( $path, $bytes ) {
	@mkdir( dirname( $path ), 0777, true );
	file_put_contents( $path, $bytes );
}

/** A tiny solid-colour WebP fixture, for scrim.php's pixel sweep. */
function nm_write_webp( $path, $hex ) {
	@mkdir( dirname( $path ), 0777, true );
	$hex = ltrim( $hex, '#' );
	$im  = imagecreatetruecolor( 10, 10 );
	imagefill( $im, 0, 0, imagecolorallocate(
		$im,
		hexdec( substr( $hex, 0, 2 ) ),
		hexdec( substr( $hex, 2, 2 ) ),
		hexdec( substr( $hex, 4, 2 ) )
	) );
	imagewebp( $im, $path, 100 );
	imagedestroy( $im );
}

// ═══════════════════════════════════════════ color.php ═══════════════════════════════════════════

echo "=== color.php: srgb_lum() / srgb_lum_rgb() agree — one contrast engine, not two ===\n";
/* This exact self-check used to run on every gallery build (`_build-gallery.php`'s own docblock:
   "srgb_lum_rgb() is a second implementation of a formula this file already has"). It moved here
   because it is an invariant of the SHARED LIBRARY now, not of any one caller. */
foreach ( array( '#FFFFFF', '#15181A', '#0E1113', '#8C3A1F', '#000000' ) as $probe ) {
	$hex = srgb_lum( $probe );
	$rgb = srgb_lum_rgb(
		hexdec( substr( $probe, 1, 2 ) ),
		hexdec( substr( $probe, 3, 2 ) ),
		hexdec( substr( $probe, 5, 2 ) )
	);
	ok( abs( $hex - $rgb ) < 1e-12, "srgb_lum() and srgb_lum_rgb() agree on $probe ($hex vs $rgb)" );
}

echo "--- contrast() / ratio_str() ---\n";
ok( abs( contrast( '#000000', '#FFFFFF' ) - 21.0 ) < 1e-9, 'black on white measures 21:1, the WCAG ceiling' );
ok( '21.00:1' === ratio_str( '#000000', '#FFFFFF' ), 'ratio_str() formats it as a label' );

echo "--- css_mix() ---\n";
ok( '#808080' === css_mix( '#000000', 0.5, '#FFFFFF' ), 'a 50/50 mix of black and white is mid-grey' );
ok( '#000000' === css_mix( '#000000', 1.0, '#FFFFFF' ), '100% of $a is $a untouched' );

echo "--- the accent gate (ink_ends): a real ground+accent derives without throwing ---\n";
try {
	$ends = ink_ends( array( 'bg' => '#FFFFFF', 'text' => '#15181A' ), '#8C3A1F', 0.45 );
	ok( isset( $ends['dark'], $ends['light'] ), 'ink_ends() returns dark + light endpoints for a real ground/accent pair' );
	$curve = ink_curve( $ends, 0.12 );
	ok( 3 === count( $curve ) && INK_STOPS === count( explode( ' ', $curve[0] ) ), 'ink_curve() returns one row per channel, INK_STOPS values each' );
} catch ( Exception $e ) {
	ok( false, 'ink_ends()/ink_curve() should not throw on a well-formed real ground+accent pair: ' . $e->getMessage() );
}

echo "--- the accent gate (ink_ends): a channel spread under 20 throws NmHerramientaMedida, not a silent pass ---\n";
try {
	ink_ends( array( 'bg' => '#FFFFFF', 'text' => '#15181A' ), '#20203A', 0.45 );
	ok( false, 'a shadow ink with a channel spread under 20 must throw, not return' );
} catch ( NmHerramientaMedida $e ) {
	ok( false !== strpos( $e->getMessage(), 'channel spread of' ), 'the message names the measured spread: ' . $e->getMessage() );
} catch ( Exception $e ) {
	ok( false, 'wrong exception type for a measured invariant: ' . get_class( $e ) );
}

echo "--- color.php --contraste: RED below 4.5:1, GREEN at/above it ---\n";
$r = run_cli( 'color.php', '--contraste "#777777" "#888888"' );
ok( 1 === $r['code'], "a pair below 4.5:1 exits 1 (measured failure): {$r['out']}" );
ok( false !== strpos( $r['out'], '1.26:1' ), "the message names the measured ratio: {$r['out']}" );

$r = run_cli( 'color.php', '--contraste "#000000" "#FFFFFF"' );
ok( 0 === $r['code'], "a pair at/above 4.5:1 exits 0: {$r['out']}" );

echo "--- color.php --contraste: a malformed hex is USAGE/ENVIRONMENT, never a contrast verdict ---\n";
$r = run_cli( 'color.php', '--contraste "#ZZZZZZ" "#FFFFFF"' );
ok( 2 === $r['code'], "a malformed hex exits 2, never 0 or 1: {$r['out']}" );

echo "--- color.php: no arguments is a usage error, exit 2 ---\n";
$r = run_cli( 'color.php', '' );
ok( 2 === $r['code'], "no flags at all exits 2: {$r['out']}" );

echo "--- color.php --maqueta: every :root pair against 4.5:1 text / 3:1 UI ---\n";
$maqueta_dir = nm_tmpdir( 'maqueta' );
nm_write( $maqueta_dir . '/ok.html', '<html><head><style>:root{--c-bg:#FFFFFF;--c-text:#111111;--c-border:#CCCCCC;}</style></head></html>' );
$r = run_cli( 'color.php', escapeshellarg( $maqueta_dir . '/ok.html' ) );
ok( 2 === $r['code'], 'no --contraste/--maqueta flag at all is still a usage error even with a trailing path' );

$r = run_cli( 'color.php', '--maqueta ' . escapeshellarg( $maqueta_dir . '/ok.html' ) );
ok( 1 === $r['code'], "a Maqueta where --c-border/--c-bg does not clear 3:1 exits 1 even though --c-text/--c-bg clears 4.5:1 — ANY failing pair fails the whole gate: {$r['out']}" );
ok( false !== strpos( $r['out'], 'FAIL' ) && false !== strpos( $r['out'], 'OK' ), 'and both the passing and the failing pair are named, not swallowed by the overall verdict' );

nm_write( $maqueta_dir . '/all-ok.html', '<html><head><style>:root{--c-bg:#FFFFFF;--c-text:#111111;--c-border:#767676;}</style></head></html>' );
$r = run_cli( 'color.php', '--maqueta ' . escapeshellarg( $maqueta_dir . '/all-ok.html' ) );
ok( 0 === $r['code'] && false === strpos( $r['out'], 'FAIL' ), "every pair clearing its bar exits 0 with no FAIL row: {$r['out']}" );

nm_write( $maqueta_dir . '/bad.html', '<html><head><style>:root{--c-bg:#FFFFFF;--c-text:#EEEEEE;}</style></head></html>' );
$r = run_cli( 'color.php', '--maqueta ' . escapeshellarg( $maqueta_dir . '/bad.html' ) );
ok( 1 === $r['code'], "a text/bg pair below 4.5:1 exits 1: {$r['out']}" );

$r = run_cli( 'color.php', '--maqueta ' . escapeshellarg( $maqueta_dir . '/no-such-file.html' ) );
ok( 2 === $r['code'], 'a missing Maqueta file is a usage/environment error, exit 2' );

echo "--- color.php --maqueta: an --c-on-X token is text ON --c-X, not on the page ground ---\n";
/* Four Plantillas tripped the same false failure. A token named `--c-on-accent` is the text painted
   ON the accent — a button label, a selected chip — and every one of BAJURA's seven uses sits on
   `--c-accent` or `--c-accent-hover`. But its name contains «accent», so the role matcher called it
   TEXT and crossed it with every ground: dark text on the dark page, 1,00:1, because on BAJURA
   `--c-on-accent` and `--c-bg` are literally the same hex. The gate failed a pair no CSS forms, and
   the two ways out were renaming a correct token or leaving the gate red. The convention is the fix:
   `--c-on-X` is measured against `--c-X` and its hover, and never against the ground. */
nm_write(
	$maqueta_dir . '/on-accent.html',
	'<html><head><style>:root{--c-bg:#0F1714;--c-text:#E9F1EC;--c-accent:#FF8A3D;--c-accent-hover:#FFA466;--c-on-accent:#0F1714;}</style></head></html>'
);
$r = run_cli( 'color.php', '--maqueta ' . escapeshellarg( $maqueta_dir . '/on-accent.html' ) );
ok( 0 === $r['code'], "--c-on-accent equal to --c-bg is NOT a failure: it never sits on the ground: {$r['out']}" );
ok( 1 === preg_match( '/--c-on-accent sobre --c-accent\b/', $r['out'] ), "it is measured against --c-accent, the colour it actually sits on: {$r['out']}" );
ok( 1 === preg_match( '/--c-on-accent sobre --c-accent-hover/', $r['out'] ), "and against the hover it also sits on: {$r['out']}" );
ok( 0 === preg_match( '/--c-on-accent sobre --c-bg/', $r['out'] ), "and never against the ground, which is the pair that was never formed: {$r['out']}" );

/* And the convention must not become a way to hide a real failure: a light label on a light accent
   still fails, measured against the accent it sits on. */
nm_write(
	$maqueta_dir . '/on-accent-bad.html',
	'<html><head><style>:root{--c-bg:#0F1714;--c-text:#E9F1EC;--c-accent:#FFD9BF;--c-on-accent:#FFFFFF;}</style></head></html>'
);
$r = run_cli( 'color.php', '--maqueta ' . escapeshellarg( $maqueta_dir . '/on-accent-bad.html' ) );
ok( 1 === $r['code'] && 1 === preg_match( '/FAIL\s+--c-on-accent sobre --c-accent\b/', $r['out'] ), "white text on a pale accent still exits 1, named against the accent: {$r['out']}" );

/* The convention comes in two spellings, and delao uses the second: its light `--c-on-inverse` sits
   on `--c-surface-inverse`, a near-black band, and there is no `--c-inverse` at all. The first
   version of this rule only looked for `--c-X`, found nothing, fell back to the grounds and measured
   light text on the light page: 1,00:1, a failure it had just invented on a Plantilla that passed the
   day before. Caught by running the change over the real library, not over these fixtures. */
nm_write(
	$maqueta_dir . '/on-surface.html',
	'<html><head><style>:root{--c-bg:#F6F4F0;--c-bg-alt:#EFEBE4;--c-text:#17181A;--c-surface-inverse:#17181A;--c-on-inverse:#F6F4F0;}</style></head></html>'
);
$r = run_cli( 'color.php', '--maqueta ' . escapeshellarg( $maqueta_dir . '/on-surface.html' ) );
ok( 0 === $r['code'], "--c-on-inverse is measured on --c-surface-inverse, not on the light ground: {$r['out']}" );
ok( 1 === preg_match( '/--c-on-inverse sobre --c-surface-inverse/', $r['out'] ), "and the pair is named against that surface: {$r['out']}" );
ok( 0 === preg_match( '/--c-on-inverse sobre --c-bg/', $r['out'] ), "never against the ground: {$r['out']}" );

/* An --c-on-X with neither --c-X nor --c-surface-X declared has nothing to be ON: fall back to the
   grounds rather than silently measuring nothing. */
nm_write(
	$maqueta_dir . '/on-orphan.html',
	'<html><head><style>:root{--c-bg:#FFFFFF;--c-text:#111111;--c-on-panel:#EEEEEE;}</style></head></html>'
);
$r = run_cli( 'color.php', '--maqueta ' . escapeshellarg( $maqueta_dir . '/on-orphan.html' ) );
ok( 1 === preg_match( '/--c-on-panel sobre --c-bg/', $r['out'] ), "an --c-on-X whose --c-X is not declared is still measured, against the grounds: {$r['out']}" );

// ═══════════════════════════════════════════ scrim.php ═══════════════════════════════════════════

echo "=== scrim.php: GD/WebP absent exits 2, never 0 (threat matrix) ===\n";
$scrim_dir = nm_tmpdir( 'scrim' );
nm_write_webp( $scrim_dir . '/black.webp', '#000000' );
$r = run_cli_no_gd( 'scrim.php', '--peor-pixel ' . escapeshellarg( $scrim_dir . '/black.webp' ) . ' 0 0 10 10' );
if ( isset( $r['no_exec'] ) ) {
	ok( false, 'ENTORNO, no el cambio: exec() esta deshabilitado, el caso "GD ausente" no se pudo reproducir aqui' );
} else {
	ok( 2 === $r['code'], "GD/WebP support absent exits 2, never 0 or 1: {$r['out']}" );
}

echo "--- scrim.php --peor-pixel: a real sweep, and the rectangle is honoured, not the whole image ---\n";
/* Left half white, right half black; white text. The left half alone must FAIL (1:1), the right
   half alone must PASS (21:1) — proving <x><y><w><h> actually restricts the sweep. */
$split_path = $scrim_dir . '/split.webp';
$im = imagecreatetruecolor( 20, 20 );
imagefilledrectangle( $im, 0, 0, 9, 19, imagecolorallocate( $im, 255, 255, 255 ) );
imagefilledrectangle( $im, 10, 0, 19, 19, imagecolorallocate( $im, 0, 0, 0 ) );
imagewebp( $im, $split_path, 100 );
imagedestroy( $im );

$r = run_cli( 'scrim.php', '--peor-pixel ' . escapeshellarg( $split_path ) . ' 0 0 10 20 --scrim "#000000" --alpha 0 --texto "#FFFFFF" --barra 4.5' );
ok( 1 === $r['code'], "the white-only left rectangle measures ~1:1 against white text — exit 1: {$r['out']}" );

$r = run_cli( 'scrim.php', '--peor-pixel ' . escapeshellarg( $split_path ) . ' 10 0 10 20 --scrim "#000000" --alpha 0 --texto "#FFFFFF" --barra 4.5' );
ok( 0 === $r['code'], "the black-only right rectangle measures 21:1 against white text — exit 0: {$r['out']}" );

echo "--- scrim.php: a region entirely outside the image is a usage/environment error, exit 2 ---\n";
$r = run_cli( 'scrim.php', '--peor-pixel ' . escapeshellarg( $split_path ) . ' 500 500 10 10' );
ok( 2 === $r['code'], "a rectangle outside the image bounds exits 2, never 0 or 1: {$r['out']}" );

echo "--- scrim.php: a missing image is a usage/environment error, exit 2 ---\n";
$r = run_cli( 'scrim.php', '--peor-pixel ' . escapeshellarg( $scrim_dir . '/no-such.webp' ) . ' 0 0 10 10' );
ok( 2 === $r['code'], "a nonexistent image exits 2: {$r['out']}" );

echo "--- scrim.php: no arguments is a usage error, exit 2 ---\n";
$r = run_cli( 'scrim.php', '' );
ok( 2 === $r['code'], "no flags at all exits 2: {$r['out']}" );

echo "--- scrim.php: fe_table() needs at least two entries ---\n";
try {
	fe_table( 0.5, array( 1.0 ) );
	ok( false, 'fe_table() with one entry should throw, not return' );
} catch ( NmHerramientaEntorno $e ) {
	ok( true, 'a single-entry table is a usage error, not a measurement: ' . $e->getMessage() );
}

// ═══════════════════════════════════════════ huella.php ═══════════════════════════════════════════

echo "=== huella.php: LF-normalised text — a CRLF tree and its LF twin hash identically ===\n";
$crlf_root = nm_tmpdir( 'huella-crlf' );
$lf_root   = nm_tmpdir( 'huella-lf' );
nm_write( $crlf_root . '/web-templates/references/plantillas/foo/ficha.md', "linea uno\r\nlinea dos\r\n" );
nm_write( $lf_root . '/web-templates/references/plantillas/foo/ficha.md', "linea uno\nlinea dos\n" );
$h_crlf = huella_plantilla( $crlf_root, 'foo' );
$h_lf   = huella_plantilla( $lf_root, 'foo' );
ok( $h_crlf === $h_lf, "a CRLF-committed ficha.md and its LF twin fingerprint identically ($h_crlf vs $h_lf) — plantilla-library spec, \"Fingerprint is platform-stable\"" );

echo "--- huella.php: a .woff2 with a \\r\\n byte pair hashes UNCHANGED — binary is never normalised ---\n";
$woff_root = nm_tmpdir( 'huella-woff' );
$woff_path = $woff_root . '/web-templates/references/plantillas/foo/maqueta/font.woff2';
nm_write( $woff_path, "FAKEWOFF2\r\nBYTES\r\n" );
$raw_hash = hash_file( 'sha256', $woff_path );
$manifest = huella_plantilla_manifest( $woff_root, 'foo' );
$rel      = 'web-templates/references/plantillas/foo/maqueta/font.woff2';
ok( isset( $manifest[ $rel ] ) && $raw_hash === $manifest[ $rel ], "the .woff2 entry equals hash_file() on the raw bytes, unchanged by LF normalisation ($raw_hash)" );

echo "--- huella.php: a missing input is recorded as the literal string 'absent', never skipped ---\n";
$empty_root = nm_tmpdir( 'huella-empty' );
@mkdir( $empty_root . '/web-templates/references/plantillas/foo', 0777, true );
$manifest = huella_plantilla_manifest( $empty_root, 'foo' );
ok(
	isset( $manifest['web-templates/references/plantillas/foo/ficha.md'] )
		&& 'absent' === $manifest['web-templates/references/plantillas/foo/ficha.md'],
	'ficha.md missing entirely is recorded as "absent", not omitted from the manifest'
);
ok(
	isset( $manifest['web-templates/references/plantillas/foo/manifiesto-imagenes.md'] )
		&& 'absent' === $manifest['web-templates/references/plantillas/foo/manifiesto-imagenes.md'],
	'manifiesto-imagenes.md missing is likewise "absent"'
);

echo "--- huella.php: editing one file inside the Plantilla moves the huella; unrelated files do not ---\n";
$sens_root = nm_tmpdir( 'huella-sens' );
nm_write( $sens_root . '/web-templates/references/plantillas/foo/ficha.md', "v1\n" );
$before = huella_plantilla( $sens_root, 'foo' );
nm_write( $sens_root . '/web-templates/references/plantillas/foo/ficha.md', "v2\n" );
$after = huella_plantilla( $sens_root, 'foo' );
ok( $before !== $after, 'editing ficha.md changes the huella' );
nm_write( $sens_root . '/web-templates/references/plantillas/bar/ficha.md', "unrelated\n" );
$still = huella_plantilla( $sens_root, 'foo' );
ok( $after === $still, "a sibling Plantilla's own files do not move this one's huella" );

echo "--- huella.php: --biblioteca is a digest of digests, sorted by slug ---\n";
$bib_root = nm_tmpdir( 'huella-biblioteca' );
nm_write( $bib_root . '/web-templates/references/plantillas/zzz/ficha.md', "z\n" );
nm_write( $bib_root . '/web-templates/references/plantillas/aaa/ficha.md', "a\n" );
$bib = huella_biblioteca( $bib_root );
ok( array( 'aaa', 'zzz' ) === array_keys( $bib['rows'] ), 'rows come back sorted by slug, aaa before zzz' );
$expected_lines = "aaa " . $bib['rows']['aaa'] . "\nzzz " . $bib['rows']['zzz'] . "\n";
ok( hash( 'sha256', $expected_lines ) === $bib['digest'], 'the library digest is sha256 over "<slug> <huella>\\n" lines, not a re-hash of every byte' );

echo "--- huella.php CLI: --plantilla / --biblioteca / --comprobar ---\n";
$r = run_cli( 'huella.php', '' );
ok( 2 === $r['code'], "no flags at all exits 2: {$r['out']}" );

$r = run_cli( 'huella.php', '--plantilla foo --root=' . escapeshellarg( $sens_root ) );
ok( 0 === $r['code'] && false !== strpos( $r['out'], 'huella: sha256:' ), "--plantilla prints the manifest and the digest, exit 0: {$r['out']}" );

$r = run_cli( 'huella.php', '--biblioteca --root=' . escapeshellarg( $bib_root ) );
ok( 0 === $r['code'] && false !== strpos( $r['out'], 'aaa sha256:' ) && false !== strpos( $r['out'], 'zzz sha256:' ), "--biblioteca lists every slug, exit 0: {$r['out']}" );

$known = huella_plantilla( $sens_root, 'foo' );
$r     = run_cli( 'huella.php', '--comprobar foo ' . $known . ' --root=' . escapeshellarg( $sens_root ) );
ok( 0 === $r['code'], "--comprobar against the CURRENT hash matches, exit 0: {$r['out']}" );

$r = run_cli( 'huella.php', '--comprobar foo 0000000000000000000000000000000000000000000000000000000000000000 --root=' . escapeshellarg( $sens_root ) );
ok( 1 === $r['code'], "--comprobar against a stale/wrong hash is a measured mismatch, exit 1: {$r['out']}" );

echo "--- huella.php --comprobar against a scratch tree (PR 1a's own runtime-harness command) ---\n";
$r = run_cli( 'huella.php', '--comprobar foo ' . $known . ' --root=' . escapeshellarg( $sens_root ) );
ok( 0 === $r['code'], 'the exact runtime-harness shape from tasks.md PR 1a runs clean against a scratch tree' );

// ═══════════════════════════════════════ uniform exit contract (1a.9) ═══════════════════════════

echo "=== uniform exit contract across all three tools: 0 pass / 1 measured failure / 2 usage-or-environment ===\n";
$contract = array(
	'color.php (usage)'   => run_cli( 'color.php', '' ),
	'scrim.php (usage)'   => run_cli( 'scrim.php', '' ),
	'huella.php (usage)'  => run_cli( 'huella.php', '' ),
);
foreach ( $contract as $label => $r ) {
	ok( 2 === $r['code'], "$label: no arguments is exit 2, never 0 — \"could not measure\" is never disguised as a pass" );
}

$measured_pass = array(
	'color.php (pass)'  => run_cli( 'color.php', '--contraste "#000000" "#FFFFFF"' ),
	'scrim.php (pass)'  => run_cli( 'scrim.php', '--peor-pixel ' . escapeshellarg( $split_path ) . ' 10 0 10 20 --scrim "#000000" --alpha 0 --texto "#FFFFFF" --barra 4.5' ),
	'huella.php (pass)' => run_cli( 'huella.php', '--comprobar foo ' . $known . ' --root=' . escapeshellarg( $sens_root ) ),
);
foreach ( $measured_pass as $label => $r ) {
	ok( 0 === $r['code'], "$label: a genuine pass is exit 0: {$r['out']}" );
}

$measured_fail = array(
	'color.php (fail)'  => run_cli( 'color.php', '--contraste "#777777" "#888888"' ),
	'scrim.php (fail)'  => run_cli( 'scrim.php', '--peor-pixel ' . escapeshellarg( $split_path ) . ' 0 0 10 20 --scrim "#000000" --alpha 0 --texto "#FFFFFF" --barra 4.5' ),
	'huella.php (fail)' => run_cli( 'huella.php', '--comprobar foo 0000000000000000000000000000000000000000000000000000000000000000 --root=' . escapeshellarg( $sens_root ) ),
);
foreach ( $measured_fail as $label => $r ) {
	ok( 1 === $r['code'], "$label: a genuine measured failure is exit 1, never 0: {$r['out']}" );
}

/* ---------------------------------------------------------------------------------------------
   THE FONT REGISTRY CARRIES MORE THAN ONE FACE PER FAMILY. The four shop Plantillas ask for
   faces the original registry could not express: Bodoni Moda and Newsreader are set in italic as
   well as roman, and IBM Plex Mono ships as two static files, 400 and 500. The registry keyed one
   file per CSS family name and always wrote `font-style:normal`, so an italic would have been
   served as a synthesised slant of the roman and the second Plex weight could not be registered
   at all. These assertions pin the multi-face shape and prove the single-face entries did not
   move. Registry membership is checked before calling nm_font_faces(), because that function
   exit()s on an unknown family and would end this suite instead of failing one assertion. */
require_once dirname( __DIR__ ) . '/skills/html-mockup/assets/fonts/_fonts.php';

$reg       = nm_font_registry();
$nuevas    = array( 'Bodoni Moda', 'Jost', 'Newsreader', 'Schibsted Grotesk', 'IBM Plex Mono', 'Instrument Sans', 'Martian Mono' );
$fonts_dir = dirname( __DIR__ ) . '/skills/html-mockup/assets/fonts';
$caras_de  = function ( $css ) {
	return preg_match_all( '/@font-face\{/', $css );
};

foreach ( $nuevas as $fam ) {
	ok( isset( $reg[ $fam ] ), "fonts: `$fam` is registered" );
}

if ( ! function_exists( 'nm_font_entry_faces' ) ) {
	ok( false, 'fonts: nm_font_entry_faces() exists, normalising a registry entry into its list of faces' );
} else {
	$archivos = array();
	foreach ( $reg as $fam => $entry ) {
		foreach ( nm_font_entry_faces( $entry ) as $face ) {
			$archivos[] = $face['file'];
			$bytes      = @file_get_contents( $fonts_dir . '/' . $face['file'] );
			ok( false !== $bytes && 'wOF2' === substr( $bytes, 0, 4 ), "fonts: `$fam` {$face['style']} {$face['weight']} names {$face['file']}, a real woff2 on disk" );
		}
	}
	ok( count( $archivos ) === count( array_unique( $archivos ) ), 'fonts: no woff2 file is registered twice' );
}

if ( isset( $reg['Newsreader'] ) ) {
	$css = nm_font_faces( array( 'Newsreader' ) );
	ok( 2 === $caras_de( $css ), 'fonts: Newsreader emits two faces, roman and italic' );
	ok( false !== strpos( $css, 'font-style:normal' ) && false !== strpos( $css, 'font-style:italic' ), 'fonts: one face is font-style:normal and the other font-style:italic, never a synthesised slant' );
}
if ( isset( $reg['IBM Plex Mono'] ) ) {
	$css = nm_font_faces( array( 'IBM Plex Mono' ) );
	ok( 2 === $caras_de( $css ), 'fonts: IBM Plex Mono emits two faces, one per static weight file' );
	ok( false !== strpos( $css, 'font-weight:400;' ) && false !== strpos( $css, 'font-weight:500;' ), 'fonts: the two Plex faces declare 400 and 500, the weights their files actually hold' );
	$b = nm_font_bytes( array( 'IBM Plex Mono' ) );
	ok( $b['raw'] === filesize( $fonts_dir . '/ibm-plex-mono-400-latin.woff2' ) + filesize( $fonts_dir . '/ibm-plex-mono-500-latin.woff2' ), 'fonts: nm_font_bytes counts every face of a family, not only the first file' );
}

/* Cormorant Garamond arrives for the second yoga direction (`amalia-salvia`), which sets its
   display serif light and in italic: one variable file per style, 300..500, like Newsreader. */
ok( isset( $reg['Cormorant Garamond'] ), 'fonts: `Cormorant Garamond` is registered' );
if ( isset( $reg['Cormorant Garamond'] ) ) {
	$css = nm_font_faces( array( 'Cormorant Garamond' ) );
	ok( 2 === $caras_de( $css ), 'fonts: Cormorant Garamond emits two faces, roman and italic' );
	ok( false !== strpos( $css, "font-family:'Cormorant Garamond';font-style:normal;font-weight:300 500;" ) && false !== strpos( $css, "font-family:'Cormorant Garamond';font-style:italic;font-weight:300 500;" ), 'fonts: both Cormorant faces declare 300 500, the range the variable files hold' );
}

$fr = nm_font_faces( array( 'Fraunces' ) );
ok( 1 === $caras_de( $fr ), 'fonts: a single-face family still emits exactly one face' );
ok( false !== strpos( $fr, "font-family:'Fraunces';font-style:normal;font-weight:400 700;font-display:swap;" ), 'fonts: the single-face output keeps the exact shape it had before multi-face support' );
$ax = nm_font_faces( array( 'Archivo Expanded' ) );
ok( false !== strpos( $ax, 'font-stretch:125%;' ), 'fonts: font-stretch still reaches the emitted face' );

// ═══════════════════════════════════════════ veredicto.php ═══════════════════════════════════════
/* ---------------------------------------------------------------------------------------------
   THE VEREDICTO IS SEALED AGAINST THE BYTES IT JUDGED. `veredicto.php --sellar` stamps huella.php's
   fingerprint into `plantillas/<slug>/veredicto.md`; `--comprobar` says whether that stamp still
   answers for the bytes on disk. The failure this tool exists to prevent is sealing a Plantilla
   nobody finished judging, so every refusal case below is asserted as its own scenario. Every
   fixture is a synthetic tree in the temp directory; the live library is never touched.

   A RED run must fail ASSERTIONS, not abort the suite: the file is required only when it exists,
   every in-process call is behind `function_exists()`, and every CLI exit-1 assertion also demands
   its reason keyword — PHP itself exits 1 on a missing script, which would otherwise read as a
   measured failure. */

$veredicto_file = NM_HERRAMIENTAS_DIR . '/veredicto.php';
if ( is_file( $veredicto_file ) ) {
	require_once $veredicto_file;
}
$veredicto_lib = function_exists( 'veredicto_sellar' ) && function_exists( 'veredicto_comprobar' ) && function_exists( 'veredicto_biblioteca' );
ok( $veredicto_lib, 'veredicto: veredicto.php defines veredicto_sellar(), veredicto_comprobar() and veredicto_biblioteca()' );

/** A veredicto.md body. `cabecera` entries set to null are omitted; `filas` is page => three cells;
 *  `hallazgos` null omits the whole section. No hash/fecha by default: that is what sealing adds. */
function nm_veredicto_md( array $o = array() ) {
	$cabecera = array( 'juez_b' => 'profesional', 'autojuzgado' => 'sí', 'vistas' => '2', 'saltadas' => '0' );
	if ( isset( $o['cabecera'] ) ) {
		$cabecera = array_merge( $cabecera, $o['cabecera'] );
	}
	$filas = isset( $o['filas'] ) ? $o['filas'] : array(
		'inicio'   => array( '✓', '✓', '✓' ),
		'contacto' => array( '✓', '✓', '✓' ),
	);
	$hallazgos = array_key_exists( 'hallazgos', $o ) ? $o['hallazgos'] : 'ninguno';

	$md = "---\n";
	foreach ( $cabecera as $k => $v ) {
		if ( null !== $v ) {
			$md .= "$k: $v\n";
		}
	}
	$md .= "---\n\n# Veredicto de prueba\n\n## Barrido\n\n| Página | 430 | 768 | 1280 |\n|---|---|---|---|\n";
	foreach ( $filas as $pagina => $celdas ) {
		$md .= '| ' . $pagina . ' | ' . implode( ' | ', $celdas ) . " |\n";
	}
	if ( null !== $hallazgos ) {
		$md .= "\n## Hallazgos\n\n" . $hallazgos . "\n";
	}
	return $md;
}

/** A complete synthetic Plantilla under `<root>/skills/…/plantillas/<slug>/`: every input huella.php
 *  covers, plus veredicto.md when one is given. Returns the Plantilla folder. */
function nm_veredicto_plantilla( $root, $slug, $veredicto_md = null ) {
	$base = $root . '/skills/web-templates/references/plantillas/' . $slug;
	nm_write( "$base/ficha.md", "---\nslug: $slug\npaginas: [inicio, contacto]\n---\n\n# Ficha de prueba\n" );
	nm_write( "$base/manifiesto-imagenes.md", "| Slug | Rol |\n|---|---|\n| $slug-hero | hero |\n" );
	nm_write( "$base/canvas/Inicio.dc.html", "<div>lienzo</div>\n" );
	nm_write( "$base/maqueta/index.html", "<!doctype html>\n<title>$slug</title>\n<section id=\"inicio\">hola</section>\n" );
	nm_write( "$base/img/$slug-hero.webp", "RIFF0000WEBPVP8 fake image bytes" );
	if ( null !== $veredicto_md ) {
		nm_write( "$base/veredicto.md", $veredicto_md );
	}
	return $base;
}

/** One header field of a veredicto.md on disk, or null when the file or the field is absent. */
function nm_veredicto_campo( $base, $key ) {
	$t = @file_get_contents( "$base/veredicto.md" );
	if ( false === $t ) {
		return null;
	}
	return preg_match( '/^' . preg_quote( $key, '/' ) . ':[ \t]*(.*?)[ \t]*$/m', $t, $m ) ? $m[1] : null;
}

/** Flip ONE byte — the last one that is not a line ending — so the change is a single byte. */
function nm_flip_byte( $path ) {
	$b = file_get_contents( $path );
	for ( $i = strlen( $b ) - 1; $i >= 0; $i-- ) {
		if ( "\n" !== $b[ $i ] && "\r" !== $b[ $i ] ) {
			$b[ $i ] = chr( ord( $b[ $i ] ) ^ 0x01 );
			break;
		}
	}
	file_put_contents( $path, $b );
}

function nm_veredicto_cli( $args, $root ) {
	return run_cli( 'veredicto.php', $args . ' --root=' . escapeshellarg( $root ) );
}

echo "=== veredicto.php --sellar: a complete, professional veredicto is sealed with huella's own fingerprint ===\n";
$v_root = nm_tmpdir( 'veredicto-sellar' );
$v_base = nm_veredicto_plantilla( $v_root, 'foo', nm_veredicto_md() );
$r      = nm_veredicto_cli( '--sellar foo', $v_root );
ok( 0 === $r['code'], "veredicto: --sellar on a complete, professional veredicto exits 0: {$r['out']}" );
ok( 'sha256:' . huella_plantilla( $v_root . '/skills', 'foo' ) === nm_veredicto_campo( $v_base, 'hash' ), 'veredicto: the sealed hash: is exactly huella_plantilla() over the same tree — one fingerprint definition, not two' );
ok( date( 'Y-m-d' ) === nm_veredicto_campo( $v_base, 'fecha' ), "veredicto: the sealed fecha: is today's date" );
$v_text = (string) @file_get_contents( "$v_base/veredicto.md" );
ok(
	null !== nm_veredicto_campo( $v_base, 'hash' ) && nm_veredicto_md() === preg_replace( '/^(hash|fecha):.*\n/m', '', $v_text ),
	'veredicto: sealing adds exactly the hash: and fecha: lines — strip those two and the judged file comes back byte for byte'
);

echo "--- veredicto.php --comprobar: passes right after sealing, and resealing unchanged bytes is idempotent ---\n";
$r = nm_veredicto_cli( '--comprobar foo', $v_root );
ok( 0 === $r['code'] && false !== strpos( $r['out'], 'vigente' ), "veredicto: --comprobar right after sealing exits 0 and says vigente: {$r['out']}" );
$v_hash = nm_veredicto_campo( $v_base, 'hash' );
$r      = nm_veredicto_cli( '--sellar foo', $v_root );
ok( 0 === $r['code'] && null !== $v_hash && $v_hash === nm_veredicto_campo( $v_base, 'hash' ), 'veredicto: resealing unchanged bytes writes the same hash' );

echo "--- veredicto.php --comprobar: one changed byte in any covered input makes the veredicto stale ---\n";
foreach ( array( 'maqueta/index.html', 'img/foo-hero.webp', 'ficha.md', 'manifiesto-imagenes.md', 'canvas/Inicio.dc.html' ) as $covered ) {
	nm_veredicto_cli( '--sellar foo', $v_root );
	$before = nm_veredicto_cli( '--comprobar foo', $v_root );
	nm_flip_byte( "$v_base/$covered" );
	$r = nm_veredicto_cli( '--comprobar foo', $v_root );
	ok( 0 === $before['code'] && 1 === $r['code'] && false !== strpos( $r['out'], 'caducado' ), "veredicto: one byte changed in $covered after sealing exits 1 with a caducado reason: {$r['out']}" );
}
$r = nm_veredicto_cli( '--sellar foo', $v_root );
$r = nm_veredicto_cli( '--comprobar foo', $v_root );
ok( 0 === $r['code'], "veredicto: a stale veredicto is current again once --sellar reseals it: {$r['out']}" );

echo "--- veredicto.php --comprobar: CRLF against LF in a text file does not make the veredicto stale ---\n";
foreach ( array( 'maqueta/index.html', 'ficha.md' ) as $texto ) {
	$lf = file_get_contents( "$v_base/$texto" );
	file_put_contents( "$v_base/$texto", str_replace( "\n", "\r\n", str_replace( "\r\n", "\n", $lf ) ) );
	$r = nm_veredicto_cli( '--comprobar foo', $v_root );
	ok( 0 === $r['code'], "veredicto: $texto rewritten with CRLF line endings still matches the LF-sealed hash: {$r['out']}" );
}

echo "--- veredicto.php --sellar: refuses every incomplete veredicto, and writes nothing ---\n";
$incompletos = array(
	'juez_b missing'                                   => nm_veredicto_md( array( 'cabecera' => array( 'juez_b' => null ) ) ),
	'a barrido cell left empty'                        => nm_veredicto_md( array( 'filas' => array( 'inicio' => array( '✓', '', '✓' ), 'contacto' => array( '✓', '✓', '✓' ) ) ) ),
	'vistas missing'                                   => nm_veredicto_md( array( 'cabecera' => array( 'vistas' => null ) ) ),
	'saltadas missing'                                 => nm_veredicto_md( array( 'cabecera' => array( 'saltadas' => null ) ) ),
	'autojuzgado missing'                              => nm_veredicto_md( array( 'cabecera' => array( 'autojuzgado' => null ) ) ),
	'a page in ficha paginas: with no barrido row'     => nm_veredicto_md( array( 'cabecera' => array( 'vistas' => '1' ), 'filas' => array( 'inicio' => array( '✓', '✓', '✓' ) ) ) ),
	'vistas + saltadas not matching the swept rows'    => nm_veredicto_md( array( 'cabecera' => array( 'vistas' => '7' ) ) ),
	'no ## Hallazgos section'                          => nm_veredicto_md( array( 'hallazgos' => null ) ),
	'a cell citing a finding ## Hallazgos never lists' => nm_veredicto_md( array( 'filas' => array( 'inicio' => array( 'H1', '✓', '✓' ), 'contacto' => array( '✓', '✓', '✓' ) ) ) ),
);
foreach ( $incompletos as $caso => $md ) {
	$i_root = nm_tmpdir( 'veredicto-incompleto' );
	$i_base = nm_veredicto_plantilla( $i_root, 'foo', $md );
	$r      = nm_veredicto_cli( '--sellar foo', $i_root );
	ok( 1 === $r['code'] && false !== strpos( $r['out'], 'incompleto' ), "veredicto: --sellar refuses $caso — exit 1, incompleto: {$r['out']}" );
	ok( $md === file_get_contents( "$i_base/veredicto.md" ), "veredicto: refusing $caso leaves veredicto.md byte-for-byte untouched" );
}

$np_root = nm_tmpdir( 'veredicto-no-profesional' );
$np_md   = nm_veredicto_md( array( 'cabecera' => array( 'juez_b' => 'no-profesional' ) ) );
$np_base = nm_veredicto_plantilla( $np_root, 'foo', $np_md );
$r       = nm_veredicto_cli( '--sellar foo', $np_root );
ok( 1 === $r['code'] && false !== strpos( $r['out'], 'no-profesional' ), "veredicto: --sellar refuses juez_b: no-profesional — exit 1: {$r['out']}" );
ok( $np_md === file_get_contents( "$np_base/veredicto.md" ), 'veredicto: refusing a no-profesional veredicto writes no hash' );

$ab_root = nm_tmpdir( 'veredicto-ausente' );
nm_veredicto_plantilla( $ab_root, 'foo' );
$r = nm_veredicto_cli( '--sellar foo', $ab_root );
ok( 1 === $r['code'] && false !== strpos( $r['out'], 'ausente' ), "veredicto: --sellar with no veredicto.md at all exits 1, ausente — there is nothing judged to seal: {$r['out']}" );

echo "--- veredicto.php --comprobar: absent, unsealed, incomplete, not professional, partial ---\n";
$r = nm_veredicto_cli( '--comprobar foo', $ab_root );
ok( 1 === $r['code'] && false !== strpos( $r['out'], 'ausente' ), "veredicto: --comprobar with no veredicto.md exits 1, ausente: {$r['out']}" );

$us_root = nm_tmpdir( 'veredicto-sin-sellar' );
nm_veredicto_plantilla( $us_root, 'foo', nm_veredicto_md() );
$r = nm_veredicto_cli( '--comprobar foo', $us_root );
ok( 1 === $r['code'] && false !== strpos( $r['out'], 'sin sellar' ), "veredicto: a complete veredicto that was never sealed exits 1, sin sellar: {$r['out']}" );

$ic_root = nm_tmpdir( 'veredicto-comprobar-incompleto' );
$ic_base = nm_veredicto_plantilla( $ic_root, 'foo', nm_veredicto_md() );
nm_veredicto_cli( '--sellar foo', $ic_root );
file_put_contents( "$ic_base/veredicto.md", preg_replace( '/^juez_b:.*\n/m', '', file_get_contents( "$ic_base/veredicto.md" ) ) );
$r = nm_veredicto_cli( '--comprobar foo', $ic_root );
ok( 1 === $r['code'] && false !== strpos( $r['out'], 'incompleto' ), "veredicto: a sealed veredicto whose juez_b was removed afterwards exits 1, incompleto — a matching hash does not excuse a missing judge: {$r['out']}" );

$cn_root = nm_tmpdir( 'veredicto-comprobar-no-profesional' );
$cn_base = nm_veredicto_plantilla( $cn_root, 'foo', nm_veredicto_md() );
nm_veredicto_cli( '--sellar foo', $cn_root );
file_put_contents( "$cn_base/veredicto.md", str_replace( 'juez_b: profesional', 'juez_b: no-profesional', file_get_contents( "$cn_base/veredicto.md" ) ) );
$r = nm_veredicto_cli( '--comprobar foo', $cn_root );
ok( 1 === $r['code'] && false !== strpos( $r['out'], 'no-profesional' ), "veredicto: a sealed veredicto edited to juez_b: no-profesional exits 1: {$r['out']}" );

$pa_root = nm_tmpdir( 'veredicto-parcial' );
nm_veredicto_plantilla( $pa_root, 'foo', nm_veredicto_md( array(
	'cabecera' => array( 'vistas' => '1', 'saltadas' => '1' ),
	'filas'    => array( 'inicio' => array( '✓', '✓', '✓' ), 'contacto' => array( '✓', 'no-disponible', '✓' ) ),
) ) );
$r_seal = nm_veredicto_cli( '--sellar foo', $pa_root );
$r      = nm_veredicto_cli( '--comprobar foo', $pa_root );
ok( 0 === $r_seal['code'], "veredicto: a PARCIAL sweep can be sealed — the veredicto records what was seen: {$r_seal['out']}" );
ok( 1 === $r['code'] && false !== strpos( $r['out'], 'PARCIAL' ), "veredicto: but --comprobar never reads a PARCIAL sweep as current — exit 1: {$r['out']}" );

$ha_root = nm_tmpdir( 'veredicto-hallazgo' );
nm_veredicto_plantilla( $ha_root, 'foo', nm_veredicto_md( array(
	'filas'     => array( 'inicio' => array( 'H1', '✓', '✓' ), 'contacto' => array( '✓', '✓', '✓' ) ),
	'hallazgos' => '- H1 · inicio · rejilla de servicios · 430 · esperado: 1 columna · observado: 2 columnas',
) ) );
$r_seal = nm_veredicto_cli( '--sellar foo', $ha_root );
$r      = nm_veredicto_cli( '--comprobar foo', $ha_root );
ok( 0 === $r_seal['code'] && 1 === $r['code'] && false !== strpos( $r['out'], 'H1' ), "veredicto: a sealed barrido with an open finding exits 1 on --comprobar and names it: {$r['out']}" );

echo "--- veredicto.php library: typed exceptions, never exit() ---\n";
if ( ! $veredicto_lib ) {
	ok( false, 'veredicto: library functions unavailable, the in-process half cannot run' );
} else {
	$lx_root = nm_tmpdir( 'veredicto-lib' );
	nm_veredicto_plantilla( $lx_root, 'foo', nm_veredicto_md( array( 'cabecera' => array( 'juez_b' => null ) ) ) );
	try {
		veredicto_sellar( $lx_root, 'foo' );
		ok( false, 'veredicto: veredicto_sellar() on an incomplete veredicto must throw, not return' );
	} catch ( NmHerramientaMedida $e ) {
		ok( false !== strpos( $e->getMessage(), 'juez_b' ), 'veredicto: an incomplete veredicto throws NmHerramientaMedida naming the gap: ' . $e->getMessage() );
	} catch ( Exception $e ) {
		ok( false, 'veredicto: wrong exception type for an incomplete veredicto: ' . get_class( $e ) );
	}
	try {
		veredicto_sellar( $lx_root, 'no-existe' );
		ok( false, 'veredicto: veredicto_sellar() on a Plantilla that does not exist must throw' );
	} catch ( NmHerramientaEntorno $e ) {
		ok( true, 'veredicto: a missing Plantilla folder throws NmHerramientaEntorno — usage, not a verdict: ' . $e->getMessage() );
	} catch ( Exception $e ) {
		ok( false, 'veredicto: wrong exception type for a missing Plantilla: ' . get_class( $e ) );
	}
	$res = veredicto_comprobar( $v_root, 'foo' );
	ok( is_array( $res ) && true === $res['ok'] && array() === $res['motivos'], 'veredicto: veredicto_comprobar() returns ok with no reasons for a current veredicto' );
	nm_flip_byte( "$v_base/maqueta/index.html" );
	$res = veredicto_comprobar( $v_root, 'foo' );
	ok( is_array( $res ) && false === $res['ok'] && count( $res['motivos'] ) > 0, 'veredicto: and not ok, with its reasons listed, once a covered byte changes' );
}

echo "--- veredicto.php --biblioteca: every Plantilla folder, _-prefixed folders skipped ---\n";
$b_root = nm_tmpdir( 'veredicto-biblioteca' );
nm_veredicto_plantilla( $b_root, 'aaa', nm_veredicto_md() );
$b_bbb = nm_veredicto_plantilla( $b_root, 'bbb', nm_veredicto_md() );
@mkdir( $b_root . '/skills/web-templates/references/plantillas/_capturas', 0777, true );
nm_veredicto_cli( '--sellar aaa', $b_root );
nm_veredicto_cli( '--sellar bbb', $b_root );
$r = nm_veredicto_cli( '--biblioteca', $b_root );
ok( 0 === $r['code'] && 1 === preg_match( '/^OK\s+aaa\b/m', $r['out'] ) && 1 === preg_match( '/^OK\s+bbb\b/m', $r['out'] ), "veredicto: --biblioteca with both Plantillas current exits 0, one line each: {$r['out']}" );
ok( 0 === $r['code'] && false === strpos( $r['out'], '_capturas' ), 'veredicto: --biblioteca skips a folder whose name starts with _ — it is not a Plantilla, so it cannot fail as one' );
nm_flip_byte( "$b_bbb/maqueta/index.html" );
$r = nm_veredicto_cli( '--biblioteca', $b_root );
ok( 1 === $r['code'] && 1 === preg_match( '/^OK\s+aaa\b/m', $r['out'] ) && 1 === preg_match( '/^FAIL\s+bbb\b.*caducado/m', $r['out'] ), "veredicto: --biblioteca with one of two Plantillas stale exits 1 and names the stale one: {$r['out']}" );

echo "--- veredicto.php: usage errors exit 2, never 0 or 1 ---\n";
$usos = array(
	'no arguments'                              => run_cli( 'veredicto.php', '' ),
	'--sellar with no slug'                     => nm_veredicto_cli( '--sellar', $v_root ),
	'--comprobar with no slug'                  => nm_veredicto_cli( '--comprobar', $v_root ),
	'an unknown flag'                           => nm_veredicto_cli( '--aprobar foo', $v_root ),
	'a slug with no Plantilla folder'           => nm_veredicto_cli( '--comprobar no-existe', $v_root ),
	'a slug that is a path (even one resolving to a real Plantilla)' => nm_veredicto_cli( '--sellar ../plantillas/foo', $v_root ),
	'--biblioteca on a root with no plantillas' => nm_veredicto_cli( '--biblioteca', nm_tmpdir( 'veredicto-vacio' ) ),
);
foreach ( $usos as $caso => $r ) {
	ok( 2 === $r['code'], "veredicto: $caso exits 2: {$r['out']}" );
}

// ═══════════════════════════════════════ veredicto.php, client folder ═══════════════════════════
/* ---------------------------------------------------------------------------------------------
   A CLIENT MAQUETA IS SEALED LIKE A PLANTILLA. `--sellar-ruta <dir>` / `--comprobar-ruta <dir>`
   take a client delivery folder (`maqueta/`, optionally `img/`, `canvas/`, `ficha.md`,
   `manifiesto-imagenes.md`, and its `veredicto.md`) instead of a library slug. The fingerprint is
   huella.php's normalisation over whatever of those inputs exists, keyed by paths relative to the
   folder, so the seal does not depend on where the folder lives. With no `ficha.md`, the pages the
   sweep owed are the rows the veredicto itself lists.

   Same RED-run discipline as above: every exit-1 assertion demands its reason keyword, and every
   in-process call sits behind `function_exists()`. */

$huella_dir_lib = function_exists( 'huella_directorio_manifest' ) && function_exists( 'huella_directorio' );
ok( $huella_dir_lib, 'huella: huella.php defines huella_directorio_manifest() and huella_directorio() for an arbitrary folder' );
$ruta_lib = function_exists( 'veredicto_sellar_ruta' ) && function_exists( 'veredicto_comprobar_ruta' );
ok( $ruta_lib, 'veredicto: veredicto.php defines veredicto_sellar_ruta() and veredicto_comprobar_ruta()' );

/** A synthetic client delivery folder at `$dir`: every input the folder fingerprint covers, `ficha.md`
 *  only when asked for, plus `veredicto.md` when one is given. Returns `$dir`. */
function nm_entrega( $dir, $veredicto_md = null, $ficha = true ) {
	if ( $ficha ) {
		nm_write( "$dir/ficha.md", "---\nslug: cliente\npaginas: [inicio, contacto]\n---\n\n# Ficha del cliente\n" );
	}
	nm_write( "$dir/canvas/Inicio.dc.html", "<div>lienzo del cliente</div>\n" );
	nm_write( "$dir/maqueta/index.html", "<!doctype html>\n<title>cliente</title>\n<section id=\"inicio\">hola</section>\n" );
	nm_write( "$dir/img/cliente-hero.webp", "RIFF0000WEBPVP8 client image bytes" );
	if ( null !== $veredicto_md ) {
		nm_write( "$dir/veredicto.md", $veredicto_md );
	}
	return $dir;
}

function nm_ruta_cli( $flag, $dir ) {
	return run_cli( 'veredicto.php', $flag . ' ' . escapeshellarg( $dir ) );
}

/** Copy a directory tree, files only. */
function nm_copiar_arbol( $from, $to ) {
	foreach ( huella_walk( $from ) as $file ) {
		nm_write( $to . substr( $file, strlen( str_replace( '\\', '/', $from ) ) ), file_get_contents( $file ) );
	}
}

echo "--- huella.php: the folder fingerprint covers what exists, relative to the folder ---\n";
$hd_dir = nm_entrega( nm_tmpdir( 'huella-dir' ) . '/entrega', nm_veredicto_md() );
nm_write( "$hd_dir/notas.txt", "not a covered input\n" );
if ( ! $huella_dir_lib ) {
	ok( false, 'huella: folder functions unavailable, the in-process half cannot run' );
} else {
	$hd_manifest = huella_directorio_manifest( $hd_dir );
	ok(
		array( 'canvas/Inicio.dc.html', 'ficha.md', 'img/cliente-hero.webp', 'maqueta/index.html' ) === array_keys( $hd_manifest ),
		'huella: the folder manifest lists canvas/, ficha.md, img/ and maqueta/ relative to the folder, sorted — no veredicto.md, no notas.txt, no "absent" row for a missing manifiesto-imagenes.md: ' . implode( ', ', array_keys( $hd_manifest ) )
	);
	ok( hash( 'sha256', "canvas/Inicio.dc.html\n" ) !== huella_directorio( $hd_dir ) && huella_digest( $hd_manifest ) === huella_directorio( $hd_dir ), 'huella: huella_directorio() is huella_digest() over that manifest — one digest definition' );
	$hd_before = huella_directorio( $hd_dir );
	nm_write( "$hd_dir/notas.txt", "edited, still not covered\n" );
	ok( $hd_before === huella_directorio( $hd_dir ), 'huella: editing a file outside the covered inputs does not move the folder huella' );
	file_put_contents( "$hd_dir/maqueta/index.html", str_replace( "\n", "\r\n", file_get_contents( "$hd_dir/maqueta/index.html" ) ) );
	ok( $hd_before === huella_directorio( $hd_dir ), 'huella: CRLF against LF in a covered text file does not move the folder huella' );
}

echo "=== veredicto.php --sellar-ruta: seals a complete, professional client veredicto ===\n";
$rt_dir = nm_entrega( nm_tmpdir( 'ruta-sellar' ) . '/entrega', nm_veredicto_md() );
$r      = nm_ruta_cli( '--sellar-ruta', $rt_dir );
ok( 0 === $r['code'] && false !== strpos( $r['out'], 'sellado' ), "veredicto: --sellar-ruta on a complete, professional client veredicto exits 0: {$r['out']}" );
ok( $huella_dir_lib && 'sha256:' . huella_directorio( $rt_dir ) === nm_veredicto_campo( $rt_dir, 'hash' ), "veredicto: the sealed hash: is exactly huella_directorio() over the same folder" );
ok( date( 'Y-m-d' ) === nm_veredicto_campo( $rt_dir, 'fecha' ), "veredicto: --sellar-ruta writes today's fecha:" );

echo "--- veredicto.php --comprobar-ruta: current after sealing, stale after one byte, not stale after CRLF ---\n";
$r = nm_ruta_cli( '--comprobar-ruta', $rt_dir );
ok( 0 === $r['code'] && false !== strpos( $r['out'], 'vigente' ), "veredicto: --comprobar-ruta right after sealing exits 0 and says vigente: {$r['out']}" );
foreach ( array( 'maqueta/index.html', 'img/cliente-hero.webp', 'canvas/Inicio.dc.html', 'ficha.md' ) as $covered ) {
	nm_ruta_cli( '--sellar-ruta', $rt_dir );
	$before = nm_ruta_cli( '--comprobar-ruta', $rt_dir );
	nm_flip_byte( "$rt_dir/$covered" );
	$r = nm_ruta_cli( '--comprobar-ruta', $rt_dir );
	ok( 0 === $before['code'] && 1 === $r['code'] && false !== strpos( $r['out'], 'caducado' ), "veredicto: one byte changed in the client's $covered after sealing exits 1, caducado: {$r['out']}" );
}
nm_ruta_cli( '--sellar-ruta', $rt_dir );
foreach ( array( 'maqueta/index.html', 'ficha.md' ) as $texto ) {
	$lf = file_get_contents( "$rt_dir/$texto" );
	file_put_contents( "$rt_dir/$texto", str_replace( "\n", "\r\n", str_replace( "\r\n", "\n", $lf ) ) );
	$r = nm_ruta_cli( '--comprobar-ruta', $rt_dir );
	ok( 0 === $r['code'], "veredicto: the client's $texto rewritten with CRLF still matches the LF-sealed hash: {$r['out']}" );
}
$rt_copia = nm_tmpdir( 'ruta-copia' ) . '/otra-ubicacion';
nm_copiar_arbol( $rt_dir, $rt_copia );
$r = nm_ruta_cli( '--comprobar-ruta', $rt_copia );
ok( 0 === $r['code'], "veredicto: the same sealed folder copied somewhere else is still vigente — the seal names no absolute path: {$r['out']}" );

echo "--- veredicto.php --sellar-ruta: refuses an incomplete client veredicto, and writes nothing ---\n";
$rt_incompletos = array(
	'juez_b missing'                                   => nm_veredicto_md( array( 'cabecera' => array( 'juez_b' => null ) ) ),
	'a barrido cell left empty'                        => nm_veredicto_md( array( 'filas' => array( 'inicio' => array( '✓', '', '✓' ), 'contacto' => array( '✓', '✓', '✓' ) ) ) ),
	'vistas + saltadas not matching the swept rows'    => nm_veredicto_md( array( 'cabecera' => array( 'vistas' => '7' ) ) ),
	'no ## Hallazgos section'                          => nm_veredicto_md( array( 'hallazgos' => null ) ),
	'a cell citing a finding ## Hallazgos never lists' => nm_veredicto_md( array( 'filas' => array( 'inicio' => array( 'H1', '✓', '✓' ), 'contacto' => array( '✓', '✓', '✓' ) ) ) ),
	'a page the client ficha declares with no row'     => nm_veredicto_md( array( 'cabecera' => array( 'vistas' => '1' ), 'filas' => array( 'inicio' => array( '✓', '✓', '✓' ) ) ) ),
);
foreach ( $rt_incompletos as $caso => $md ) {
	$i_dir = nm_entrega( nm_tmpdir( 'ruta-incompleto' ) . '/entrega', $md );
	$r     = nm_ruta_cli( '--sellar-ruta', $i_dir );
	ok( 1 === $r['code'] && false !== strpos( $r['out'], 'incompleto' ), "veredicto: --sellar-ruta refuses $caso — exit 1, incompleto: {$r['out']}" );
	ok( $md === file_get_contents( "$i_dir/veredicto.md" ), "veredicto: refusing $caso leaves the client veredicto.md byte-for-byte untouched" );
}
$np_dir = nm_entrega( nm_tmpdir( 'ruta-no-profesional' ) . '/entrega', nm_veredicto_md( array( 'cabecera' => array( 'juez_b' => 'no-profesional' ) ) ) );
$r      = nm_ruta_cli( '--sellar-ruta', $np_dir );
ok( 1 === $r['code'] && false !== strpos( $r['out'], 'no-profesional' ), "veredicto: --sellar-ruta refuses juez_b: no-profesional — exit 1: {$r['out']}" );
$ab_dir = nm_entrega( nm_tmpdir( 'ruta-ausente' ) . '/entrega' );
$r      = nm_ruta_cli( '--sellar-ruta', $ab_dir );
ok( 1 === $r['code'] && false !== strpos( $r['out'], 'ausente' ), "veredicto: --sellar-ruta on a folder with no veredicto.md exits 1, ausente: {$r['out']}" );
$r = nm_ruta_cli( '--comprobar-ruta', $ab_dir );
ok( 1 === $r['code'] && false !== strpos( $r['out'], 'ausente' ), "veredicto: --comprobar-ruta on a folder with no veredicto.md exits 1, ausente: {$r['out']}" );

echo "--- veredicto.php ruta: with no ficha.md, the pages owed are the rows the veredicto lists ---\n";
$nf_dir = nm_entrega( nm_tmpdir( 'ruta-sin-ficha' ) . '/entrega', nm_veredicto_md( array(
	'cabecera' => array( 'vistas' => '3' ),
	'filas'    => array( 'inicio' => array( '✓', '✓', '✓' ), 'contacto' => array( '✓', '✓', '✓' ), 'servicios' => array( '✓', '✓', '✓' ) ),
) ), false );
$r_seal = nm_ruta_cli( '--sellar-ruta', $nf_dir );
$r      = nm_ruta_cli( '--comprobar-ruta', $nf_dir );
ok( 0 === $r_seal['code'] && 0 === $r['code'], "veredicto: a client folder with no ficha.md seals and checks against its own three rows: {$r_seal['out']} / {$r['out']}" );
$nr_md  = "---\njuez_b: profesional\nautojuzgado: sí\nvistas: 0\nsaltadas: 0\n---\n\n## Barrido\n\n| Página | 430 | 768 | 1280 |\n|---|---|---|---|\n\n## Hallazgos\n\nninguno\n";
$nr_dir = nm_entrega( nm_tmpdir( 'ruta-sin-filas' ) . '/entrega', $nr_md, false );
$r      = nm_ruta_cli( '--sellar-ruta', $nr_dir );
ok( 1 === $r['code'] && false !== strpos( $r['out'], 'incompleto' ), "veredicto: with no ficha.md and a sweep with no rows, there is nothing that was swept — exit 1, incompleto: {$r['out']}" );
ok( $nr_md === file_get_contents( "$nr_dir/veredicto.md" ), 'veredicto: and the empty sweep is not sealed' );

echo "--- veredicto.php ruta: PARCIAL and open findings are sealed but never current ---\n";
$pr_dir = nm_entrega( nm_tmpdir( 'ruta-parcial' ) . '/entrega', nm_veredicto_md( array(
	'cabecera' => array( 'vistas' => '1', 'saltadas' => '1' ),
	'filas'    => array( 'inicio' => array( '✓', '✓', '✓' ), 'contacto' => array( '✓', 'no-disponible', '✓' ) ),
) ) );
$r_seal = nm_ruta_cli( '--sellar-ruta', $pr_dir );
$r      = nm_ruta_cli( '--comprobar-ruta', $pr_dir );
ok( 0 === $r_seal['code'] && 1 === $r['code'] && false !== strpos( $r['out'], 'PARCIAL' ), "veredicto: a PARCIAL client sweep seals, and --comprobar-ruta exits 1, PARCIAL: {$r['out']}" );

echo "--- veredicto.php ruta: a folder that is not there, or not a delivery folder, is usage — exit 2 ---\n";
$rt_nada = nm_tmpdir( 'ruta-nada' );
$rt_file = $rt_nada . '/un-fichero.txt';
nm_write( $rt_file, "no soy una carpeta\n" );
$rt_sin_maqueta = $rt_nada . '/sin-maqueta';
nm_write( "$rt_sin_maqueta/veredicto.md", nm_veredicto_md() );
$rt_usos = array(
	'--sellar-ruta on a directory that does not exist'    => nm_ruta_cli( '--sellar-ruta', $rt_nada . '/no-existe' ),
	'--comprobar-ruta on a directory that does not exist' => nm_ruta_cli( '--comprobar-ruta', $rt_nada . '/no-existe' ),
	'--sellar-ruta on a file, not a directory'            => nm_ruta_cli( '--sellar-ruta', $rt_file ),
	'--sellar-ruta on a folder with no maqueta/'          => nm_ruta_cli( '--sellar-ruta', $rt_sin_maqueta ),
	'--sellar-ruta with no directory at all'              => run_cli( 'veredicto.php', '--sellar-ruta' ),
	'--comprobar-ruta with no directory at all'           => run_cli( 'veredicto.php', '--comprobar-ruta' ),
);
foreach ( $rt_usos as $caso => $r ) {
	ok( 2 === $r['code'], "veredicto: $caso exits 2: {$r['out']}" );
}
ok( nm_veredicto_md() === file_get_contents( "$rt_sin_maqueta/veredicto.md" ), 'veredicto: refusing a folder with no maqueta/ writes no seal into its veredicto.md' );

if ( $ruta_lib ) {
	try {
		veredicto_comprobar_ruta( $rt_nada . '/no-existe' );
		ok( false, 'veredicto: veredicto_comprobar_ruta() on a missing folder must throw, not return' );
	} catch ( NmHerramientaEntorno $e ) {
		ok( true, 'veredicto: a missing client folder throws NmHerramientaEntorno — usage, not a verdict: ' . $e->getMessage() );
	} catch ( Exception $e ) {
		ok( false, 'veredicto: wrong exception type for a missing client folder: ' . get_class( $e ) );
	}
	$res = veredicto_comprobar_ruta( $rt_copia );
	ok( is_array( $res ) && true === $res['ok'] && array() === $res['motivos'], 'veredicto: veredicto_comprobar_ruta() returns ok with no reasons for a current client veredicto' );
}

echo "--- veredicto.php ruta: a covered file that links outside the folder is refused, never read ---\n";
$sl_dir = nm_entrega( nm_tmpdir( 'ruta-enlace' ) . '/entrega', nm_veredicto_md() );
$sl_out = nm_tmpdir( 'ruta-fuera' ) . '/secreto.html';
nm_write( $sl_out, "fuera de la carpeta\n" );
if ( function_exists( 'symlink' ) && @symlink( $sl_out, "$sl_dir/maqueta/enlace.html" ) ) {
	$sl_md = file_get_contents( "$sl_dir/veredicto.md" );
	$r     = nm_ruta_cli( '--sellar-ruta', $sl_dir );
	ok( 2 === $r['code'] && false !== strpos( $r['out'], 'fuera' ), "veredicto: a symlink in maqueta/ resolving outside the folder exits 2 and says fuera: {$r['out']}" );
	ok( $sl_md === file_get_contents( "$sl_dir/veredicto.md" ), 'veredicto: and nothing is sealed' );
} else {
	echo "  SKIP this platform does not let this process create a symlink; the escape case is not reproducible here\n";
}

// ═══════════════════════════════════════════ empaquetar.php ═══════════════════════════════════════
/* ---------------------------------------------------------------------------------------------
   A MAQUETA PUBLISHED AS ONE FILE. A Plantilla's maqueta references its photographs as
   `../img/<file>`; published as a single-file Artifact those paths resolve to nothing and the CSP
   blocks every remote URL. `empaquetar.php` writes a copy with each reference replaced by a `data:`
   URI of that file, and refuses (writing nothing) when an image is missing, a remote resource is
   left, or the result passes the 16 MB Artifact ceiling. The source maqueta is never modified.

   Same RED-run discipline: the file is required only when it exists, in-process calls sit behind
   `function_exists()`, and every exit-1 assertion demands its reason keyword. */

$empaquetar_file = NM_HERRAMIENTAS_DIR . '/empaquetar.php';
if ( is_file( $empaquetar_file ) ) {
	require_once $empaquetar_file;
}
$empaquetar_lib = function_exists( 'empaquetar_maqueta_plantilla' ) && function_exists( 'empaquetar_html' )
	&& function_exists( 'empaquetar_remotos' ) && function_exists( 'empaquetar_archivo' );
ok( $empaquetar_lib, 'empaquetar: empaquetar.php defines empaquetar_maqueta_plantilla(), empaquetar_html(), empaquetar_remotos() and empaquetar_archivo()' );

/** An embedded font face exactly as `nm_font_faces()` writes one — must survive byte for byte. */
const NM_EMP_FONT = "@font-face{font-family:'Prueba';font-style:normal;font-weight:400;font-display:swap;src:url(data:font/woff2;base64,d09GMgABAAAAAAr0ABAAAAAAFbQAAAqSAAEAAAAAAAAAAAAAAAAAAAAA) format('woff2');}";

/** A maqueta with three images referenced seven times — `src`, `srcset`, `poster`, a `<style>`
 *  `url()` and an inline-style `url()` — one more mention inside an HTML comment, an embedded font,
 *  and three links that are not resources. `$extra` is appended before the end. */
function nm_emp_html( $extra = '' ) {
	return "<!doctype html>\n<meta charset=\"utf-8\">\n<title>foo</title>\n"
		. "<!-- NM-IMG:BEGIN\n     ../img/foo-a.webp   hero\n     NM-IMG:END -->\n"
		. "<style>\n/* NM-FONTS:BEGIN */\n" . NM_EMP_FONT . "\n/* NM-FONTS:END */\n"
		. ".banda{background-image:url(\"../img/foo-c.webp\")}\n</style>\n"
		. "<img src=\"../img/foo-a.webp\" alt=\"a\">\n"
		. "<img srcset=\"../img/foo-a.webp 1x, ../img/foo-b.webp 2x\" src=\"../img/foo-b.webp\" alt=\"b\">\n"
		. "<video poster='../img/foo-b.webp'></video>\n"
		. "<div style=\"background:url('../img/foo-c.webp') center/cover\"></div>\n"
		. "<p><a href=\"https://www.aepd.es\">aepd</a> · <a href=\"mailto:hola@foo.es\">correo</a> · <a href=\"tel:+34910000000\">tel</a></p>\n"
		. $extra;
}

/** A Plantilla `<slug>` under `<root>/skills/…/plantillas/` holding `$html` as its maqueta and three
 *  distinct small image files. Returns the Plantilla folder. */
function nm_emp_plantilla( $root, $slug, $html ) {
	$base = $root . '/skills/web-templates/references/plantillas/' . $slug;
	nm_emp_carpeta( $base, $html );
	return $base;
}

/** `<dir>/maqueta/index.html` = `$html`, plus `<dir>/img/foo-{a,b,c}.webp` with distinct bytes. */
function nm_emp_carpeta( $dir, $html ) {
	nm_write( "$dir/maqueta/index.html", $html );
	foreach ( array( 'a', 'b', 'c' ) as $i => $n ) {
		nm_write( "$dir/img/foo-$n.webp", "RIFF\x00\x01\x02\xffWEBPVP8 foo-$n " . str_repeat( chr( 200 + $i ), 40 + $i ) );
	}
	return $dir;
}

function nm_emp_uri( $file ) {
	return 'data:image/webp;base64,' . base64_encode( file_get_contents( $file ) );
}

echo "=== empaquetar.php --plantilla: every ../img reference becomes a data: URI of that file ===\n";
$e_root = nm_tmpdir( 'empaquetar' );
$e_html = nm_emp_html();
$e_base = nm_emp_plantilla( $e_root, 'foo', $e_html );
$e_src  = "$e_base/maqueta/index.html";
$e_out  = "$e_root/foo-publicable.html";
$r      = run_cli( 'empaquetar.php', '--plantilla foo --out ' . escapeshellarg( $e_out ) . ' --root=' . escapeshellarg( $e_root ) );
$e_txt  = (string) @file_get_contents( $e_out );
ok( 0 === $r['code'] && '' !== $e_txt, "empaquetar: --plantilla foo --out exits 0 and writes the packaged file: {$r['out']}" );
ok( 1 === preg_match( '/\b3 imágenes\b/u', $r['out'] ), "empaquetar: it reports 3 images embedded — the number of distinct files referenced: {$r['out']}" );
ok( '' !== $e_txt && false !== strpos( $r['out'], (string) strlen( $e_txt ) . ' bytes' ), "empaquetar: it reports the output size in bytes, equal to what it wrote: {$r['out']}" );
ok( 7 === substr_count( $e_txt, 'data:image/webp;base64,' ), 'empaquetar: seven resource references become seven data: URIs (src, srcset ×2, second src, poster, <style> url(), inline url()): ' . substr_count( $e_txt, 'data:image/webp;base64,' ) );
foreach ( array( 'a' => 2, 'b' => 3, 'c' => 2 ) as $n => $veces ) {
	ok( $veces === substr_count( $e_txt, nm_emp_uri( "$e_base/img/foo-$n.webp" ) ), "empaquetar: foo-$n.webp is embedded $veces times, each URI decoding to that file's exact bytes" );
}
ok( '' !== $e_txt && false === strpos( $e_txt, '../img/' ), 'empaquetar: the output contains no ../img/ string anywhere, comment included' );
ok( false !== strpos( $e_txt, NM_EMP_FONT ), 'empaquetar: the embedded font data: URI is byte-identical in the output' );
ok(
	false !== strpos( $e_txt, '<a href="https://www.aepd.es">aepd</a>' ) && false !== strpos( $e_txt, 'href="mailto:hola@foo.es"' ) && false !== strpos( $e_txt, 'href="tel:+34910000000"' ),
	'empaquetar: an external <a href="https://…"> link, mailto: and tel: are left exactly as they were'
);
ok( $e_html === file_get_contents( $e_src ), 'empaquetar: the source maqueta is byte-for-byte untouched' );

echo "--- empaquetar.php --maqueta: a client maqueta outside the library, images beside its own folder ---\n";
$c_root = nm_tmpdir( 'empaquetar-cliente' );
$c_dir  = nm_emp_carpeta( "$c_root/entrega", $e_html );
$c_out  = "$c_root/cliente-publicable.html";
$r      = run_cli( 'empaquetar.php', '--maqueta ' . escapeshellarg( "$c_dir/maqueta/index.html" ) . ' --out ' . escapeshellarg( $c_out ) );
$c_txt  = (string) @file_get_contents( $c_out );
ok( 0 === $r['code'] && 1 === preg_match( '/\b3 imágenes\b/u', $r['out'] ), "empaquetar: --maqueta <path> --out exits 0 and embeds 3 images: {$r['out']}" );
ok( '' !== $c_txt && false === strpos( $c_txt, '../img/' ) && 1 === substr_count( $c_txt, nm_emp_uri( "$c_dir/img/foo-c.webp" ) ) * 0 + ( 2 === substr_count( $c_txt, nm_emp_uri( "$c_dir/img/foo-c.webp" ) ) ? 1 : 0 ), 'empaquetar: the client images are resolved from the maqueta\'s own folder and embedded' );
ok( $e_html === file_get_contents( "$c_dir/maqueta/index.html" ), 'empaquetar: the client source maqueta is untouched' );

echo "--- empaquetar.php: refuses a missing image, and writes nothing ---\n";
$m_root = nm_tmpdir( 'empaquetar-falta' );
$m_html = nm_emp_html( "<img src=\"../img/foo-no-existe.webp\" alt=\"falta\">\n" );
$m_base = nm_emp_plantilla( $m_root, 'foo', $m_html );
$m_out  = "$m_root/foo-publicable.html";
$r      = run_cli( 'empaquetar.php', '--plantilla foo --out ' . escapeshellarg( $m_out ) . ' --root=' . escapeshellarg( $m_root ) );
ok( 1 === $r['code'] && false !== strpos( $r['out'], 'foo-no-existe.webp' ), "empaquetar: a referenced image that does not exist exits 1 and names it: {$r['out']}" );
ok( ! file_exists( $m_out ), 'empaquetar: refusing a missing image writes no output file' );
ok( $m_html === file_get_contents( "$m_base/maqueta/index.html" ), 'empaquetar: and leaves the source maqueta untouched' );

echo "--- empaquetar.php: refuses a remote resource left in the maqueta ---\n";
$remotos = array(
	'a remote <img src>'               => '<img src="https://cdn.example.com/foto.webp" alt="x">',
	'a remote <script src>'            => '<script src="https://cdn.example.com/app.js"></script>',
	'a remote <link rel=stylesheet>'   => '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter">',
	'a remote CSS url()'               => '<style>.x{background:url(https://example.com/fondo.webp)}</style>',
	'a remote url() in an inline style' => '<div style="background-image:url(\'http://example.com/fondo.webp\')"></div>',
	'a protocol-relative <script src>' => '<script src="//cdn.example.com/app.js"></script>',
);
foreach ( $remotos as $caso => $fragmento ) {
	$x_root = nm_tmpdir( 'empaquetar-remoto' );
	nm_emp_plantilla( $x_root, 'foo', nm_emp_html( $fragmento . "\n" ) );
	$x_out = "$x_root/foo-publicable.html";
	$r     = run_cli( 'empaquetar.php', '--plantilla foo --out ' . escapeshellarg( $x_out ) . ' --root=' . escapeshellarg( $x_root ) );
	ok( 1 === $r['code'] && false !== strpos( $r['out'], 'remoto' ), "empaquetar: $caso exits 1, remoto: {$r['out']}" );
	ok( ! file_exists( $x_out ), "empaquetar: refusing $caso writes no output file" );
}

echo "--- empaquetar.php: refuses an output past the 16 MB Artifact ceiling ---\n";
$g_root = nm_tmpdir( 'empaquetar-grande' );
$g_base = $g_root . '/skills/web-templates/references/plantillas/foo';
nm_write( "$g_base/maqueta/index.html", "<!doctype html>\n<title>foo</title>\n<img src=\"../img/foo-enorme.webp\" alt=\"x\">\n" );
nm_write( "$g_base/img/foo-enorme.webp", str_repeat( "\x9a", 12700000 ) );
$g_out = "$g_root/foo-publicable.html";
$r     = run_cli( 'empaquetar.php', '--plantilla foo --out ' . escapeshellarg( $g_out ) . ' --root=' . escapeshellarg( $g_root ) );
ok( 1 === $r['code'] && false !== strpos( $r['out'], '16 MB' ), "empaquetar: a 12.7 MB image, 16.9 MB once base64-encoded, exits 1 naming the 16 MB ceiling: {$r['out']}" );
ok( ! file_exists( $g_out ), 'empaquetar: refusing an oversized result writes no output file' );
@unlink( "$g_base/img/foo-enorme.webp" );

echo "--- empaquetar.php library: typed exceptions, never exit() ---\n";
if ( ! $empaquetar_lib ) {
	ok( false, 'empaquetar: library functions unavailable, the in-process half cannot run' );
} else {
	try {
		empaquetar_html( $m_html, "$m_base/maqueta" );
		ok( false, 'empaquetar: empaquetar_html() with a missing image must throw, not return' );
	} catch ( NmHerramientaMedida $e ) {
		ok( false !== strpos( $e->getMessage(), 'foo-no-existe.webp' ), 'empaquetar: a missing image throws NmHerramientaMedida naming it: ' . $e->getMessage() );
	} catch ( Exception $e ) {
		ok( false, 'empaquetar: wrong exception type for a missing image: ' . get_class( $e ) );
	}
	$lib = empaquetar_html( $e_html, "$e_base/maqueta" );
	ok( 3 === $lib['imagenes'] && 7 === $lib['referencias'] && strlen( $lib['html'] ) === $lib['bytes'], 'empaquetar: empaquetar_html() returns the packaged html with 3 images, 7 references and its byte count' );
	ok(
		array( 'https://cdn.example.com/x.webp' ) === empaquetar_remotos( '<a href="https://example.com">x</a><!-- <img src="https://comentado.example.com/y.webp"> --><img alt="" src="https://cdn.example.com/x.webp">' ),
		'empaquetar: empaquetar_remotos() lists the remote <img>, not the <a href> link nor a commented-out tag'
	);
	try {
		empaquetar_maqueta_plantilla( $e_root, '../plantillas/foo' );
		ok( false, 'empaquetar: a slug that is a path must throw, not resolve' );
	} catch ( NmHerramientaEntorno $e ) {
		ok( true, 'empaquetar: a slug that is a path throws NmHerramientaEntorno: ' . $e->getMessage() );
	}
}

echo "--- empaquetar.php: the data: URI carries the file's OWN type, not a hardcoded one ---\n";
/* Today every photograph in the library is .webp, so a hardcoded `image/webp` passes every test
   above while being wrong. A client maqueta brings a logo: an SVG served as image/webp renders
   as a broken image in every browser, silently. The type comes from the extension or the file
   is refused — never guessed. */
$t_root = nm_tmpdir( 'empaquetar-tipos' );
$t_base = $t_root . '/skills/web-templates/references/plantillas/foo';
$t_tipos = array(
	'logo.svg'   => array( '<svg xmlns="http://www.w3.org/2000/svg"><rect width="4" height="4"/></svg>', 'image/svg+xml' ),
	'sello.png'  => array( "\x89PNG\r\n\x1a\n sello", 'image/png' ),
	'foto.jpg'   => array( "\xff\xd8\xff\xe0 foto", 'image/jpeg' ),
	'foto2.jpeg' => array( "\xff\xd8\xff\xe0 foto2", 'image/jpeg' ),
	'anim.gif'   => array( 'GIF89a anim', 'image/gif' ),
	'hero.webp'  => array( "RIFF\x00\x01WEBPVP8 hero", 'image/webp' ),
	'icono.avif' => array( "\x00\x00\x00\x20ftypavif icono", 'image/avif' ),
);
$t_html = "<!doctype html>\n<title>foo</title>\n";
foreach ( $t_tipos as $nombre => $par ) {
	nm_write( "$t_base/img/$nombre", $par[0] );
	$t_html .= "<img src=\"../img/$nombre\" alt=\"$nombre\">\n";
}
nm_write( "$t_base/maqueta/index.html", $t_html );
$t_out = "$t_root/foo-publicable.html";
$r     = run_cli( 'empaquetar.php', '--plantilla foo --out ' . escapeshellarg( $t_out ) . ' --root=' . escapeshellarg( $t_root ) );
$t_txt = (string) @file_get_contents( $t_out );
ok( 0 === $r['code'] && '' !== $t_txt, "empaquetar: a maqueta mixing svg/png/jpg/gif/webp/avif packages and exits 0: {$r['out']}" );
foreach ( $t_tipos as $nombre => $par ) {
	$esperado = 'data:' . $par[1] . ';base64,' . base64_encode( $par[0] );
	ok( '' !== $t_txt && false !== strpos( $t_txt, $esperado ), "empaquetar: $nombre is embedded as {$par[1]}, not as a guessed type" );
}
/* And an extension this file cannot name a type for is refused, never shipped with a wrong one. */
nm_write( "$t_base/img/hoja.xyz", 'no soy una imagen' );
nm_write( "$t_base/maqueta/index.html", "<!doctype html>\n<title>foo</title>\n<img src=\"../img/hoja.xyz\" alt=\"x\">\n" );
$t_out2 = "$t_root/foo-desconocido.html";
$r      = run_cli( 'empaquetar.php', '--plantilla foo --out ' . escapeshellarg( $t_out2 ) . ' --root=' . escapeshellarg( $t_root ) );
ok( 1 === $r['code'] && false !== strpos( $r['out'], 'hoja.xyz' ), "empaquetar: an extension with no known image type exits 1 and names the file: {$r['out']}" );
ok( ! file_exists( $t_out2 ), 'empaquetar: refusing an unknown image type writes no output file' );

echo "--- empaquetar.php: usage and environment errors exit 2, never 0 or 1 ---\n";
$u_root = nm_tmpdir( 'empaquetar-uso' );
$u_base = nm_emp_plantilla( $u_root, 'foo', $e_html );
$emp_usos = array(
	'no arguments'                              => run_cli( 'empaquetar.php', '' ),
	'--plantilla with no --out'                 => run_cli( 'empaquetar.php', '--plantilla foo --root=' . escapeshellarg( $u_root ) ),
	'a slug with no Plantilla folder'           => run_cli( 'empaquetar.php', '--plantilla no-existe --out ' . escapeshellarg( "$u_root/x.html" ) . ' --root=' . escapeshellarg( $u_root ) ),
	'a slug that is a path'                     => run_cli( 'empaquetar.php', '--plantilla ../plantillas/foo --out ' . escapeshellarg( "$u_root/x.html" ) . ' --root=' . escapeshellarg( $u_root ) ),
	'--maqueta naming a file that does not exist' => run_cli( 'empaquetar.php', '--maqueta ' . escapeshellarg( "$u_root/no-existe/index.html" ) . ' --out ' . escapeshellarg( "$u_root/x.html" ) ),
	'--plantilla and --maqueta together'        => run_cli( 'empaquetar.php', '--plantilla foo --maqueta ' . escapeshellarg( "$u_base/maqueta/index.html" ) . ' --out ' . escapeshellarg( "$u_root/x.html" ) . ' --root=' . escapeshellarg( $u_root ) ),
	'--out in a directory that does not exist'  => run_cli( 'empaquetar.php', '--plantilla foo --out ' . escapeshellarg( "$u_root/no-existe/x.html" ) . ' --root=' . escapeshellarg( $u_root ) ),
	'--out naming the source maqueta itself'    => run_cli( 'empaquetar.php', '--plantilla foo --out ' . escapeshellarg( "$u_base/maqueta/index.html" ) . ' --root=' . escapeshellarg( $u_root ) ),
);
foreach ( $emp_usos as $caso => $r ) {
	ok( 2 === $r['code'], "empaquetar: $caso exits 2: {$r['out']}" );
}
ok( $e_html === file_get_contents( "$u_base/maqueta/index.html" ), 'empaquetar: --out naming the source maqueta leaves it byte-for-byte untouched' );

echo "\n$pass OK / $fail FAIL\n";
exit( $fail ? 1 : 0 );
