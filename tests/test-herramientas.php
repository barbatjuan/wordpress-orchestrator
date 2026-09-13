<?php
/**
 * Behavioural assertions for the shared toolbox: `color.php`, `scrim.php`, `huella.php`
 * (`skills/html-mockup/assets/herramientas/`), lifted from `_build-gallery.php`
 * (`openspec/changes/plantillas-reales`, PR 1a).
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

echo "\n$pass OK / $fail FAIL\n";
exit( $fail ? 1 : 0 );
