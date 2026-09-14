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

echo "\n$pass OK / $fail FAIL\n";
exit( $fail ? 1 : 0 );
