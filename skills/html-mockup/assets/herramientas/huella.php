<?php
/**
 * huella.php — the ONE definition of a Plantilla's fingerprint, modelled on
 * `skills/html-mockup/assets/gallery/_gallery-fingerprint.php` and COMMITTED rather than transient.
 *
 * SAME SHAPE, ONE DIFFERENCE THAT MATTERS. Like the gallery fingerprint: `path => sha256`, paths
 * relative to `skills/`, a missing input recorded as the literal string `absent` (never skipped —
 * skipping would make an input's DELETION invisible), `ksort()` before hashing so discovery order
 * never matters, digest = sha256 over `"<path> <hash>\n"` lines. UNLIKE the gallery fingerprint,
 * THIS digest is committed to the repository as `veredicto.md`'s `hash:` field
 * (`openspec/changes/plantillas-reales/specs/veredicto-gate/spec.md`), so it has to survive a
 * checkout. `_gallery-fingerprint.php` hashes raw and says why (`:35-38`): its output is untracked,
 * so its digest never crosses a checkout boundary. This one does, which is the entire reason the LF
 * normalisation below exists — see `.gitattributes` for the other half of the same guarantee.
 *
 * COVERAGE. `ficha.md`, `manifiesto-imagenes.md`, `canvas/**`, `maqueta/**`, `img/**`, all under
 * `web-templates/references/plantillas/<slug>/`. `veredicto.md` is deliberately EXCLUDED: including
 * the file the digest is stamped into would be a fixed point that can never be sealed.
 *
 * LF NORMALISATION, BY EXTENSION ALLOWLIST. `.md .html .json .css .js .svg .txt` are read,
 * `\r\n` → `\n`, then hashed; every other extension (`.webp .woff2 .png .jpg .avif` — the set
 * `.gitattributes` pins `binary`) is hashed RAW. Sniffing text-vs-binary and getting it wrong on a
 * `.woff2` is silent corruption of the check itself (`_gallery-fingerprint.php:35-38` makes the
 * identical argument for why THAT file never normalises anything) — an explicit allowlist cannot
 * misclassify a file it was never asked to look at.
 *
 * LIBRARY OUT OF THE TREE IT IS GIVEN. `huella_plantilla_manifest( $skills_dir, $slug )` takes the
 * root explicitly, exactly like `nm_gallery_input_manifest( $gallery_dir )` does — so
 * `framework-audit.php` can `require_once` this file and recompute a fingerprint against `--root`,
 * and so this file's own CLI can be pointed at a scratch tree for tests without touching the real
 * library.
 *
 * A CLIENT DELIVERY FOLDER IS NOT A PLANTILLA. `huella_directorio_manifest( $dir )` /
 * `huella_directorio( $dir )` fingerprint an arbitrary folder — `veredicto.php`'s `--sellar-ruta`
 * needs the same normalisation for a folder that is not `skills/web-templates/references/plantillas/
 * <slug>/` shaped and does not commit to owning a `ficha.md` or `manifiesto-imagenes.md` the way a
 * Plantilla does. So unlike `huella_plantilla_manifest()`, which treats `ficha.md` and
 * `manifiesto-imagenes.md` as FIXED inputs and records `absent` when either is missing,
 * `huella_directorio_manifest()` covers whatever of `ficha.md`, `manifiesto-imagenes.md`, `canvas/**`,
 * `maqueta/**`, `img/**` actually exists under `$dir` and records nothing for what does not — a
 * client folder that never had a `manifiesto-imagenes.md` is not carrying a defect the way a
 * Plantilla omitting its own would be. `veredicto.md` stays excluded, same reason as always.
 */

require_once __DIR__ . '/color.php';

/** Text extensions LF-normalised before hashing. Every other extension is hashed raw. */
const NM_HUELLA_TEXT_EXT = array( 'md', 'html', 'json', 'css', 'js', 'svg', 'txt' );

/** `$file`'s sha256 — LF-normalised for the text allowlist, raw otherwise. `null` when absent;
 *  the caller records that as the string `absent`, never by skipping the entry. */
function huella_hash_file( $file ) {
	if ( ! is_file( $file ) ) {
		return null;
	}
	$ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
	if ( in_array( $ext, NM_HUELLA_TEXT_EXT, true ) ) {
		$bytes = str_replace( "\r\n", "\n", file_get_contents( $file ) );
		return hash( 'sha256', $bytes );
	}
	return hash_file( 'sha256', $file );
}

/** Every file under `$dir`, at any depth, as an absolute path with `/` separators. Empty when
 *  `$dir` does not exist — a Plantilla folder that has not been created yet is not an error here,
 *  it is a manifest of `absent` fixed paths and empty globbed sets, same as the gallery's own. */
function huella_walk( $dir ) {
	$out = array();
	if ( ! is_dir( $dir ) ) {
		return $out;
	}
	$it = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS )
	);
	foreach ( $it as $f ) {
		if ( $f->isFile() ) {
			$out[] = str_replace( '\\', '/', $f->getPathname() );
		}
	}
	return $out;
}

/**
 * `path => sha256|absent` for one Plantilla, paths relative to `$skills_dir`, sorted by path.
 * `$skills_dir` plays the role `skills/` plays in the real checkout — a scratch tree in tests
 * simply has the same `web-templates/references/plantillas/<slug>/` shape under it.
 */
function huella_plantilla_manifest( $skills_dir, $slug ) {
	$skills_dir = rtrim( str_replace( '\\', '/', $skills_dir ), '/' );
	$base       = $skills_dir . '/web-templates/references/plantillas/' . $slug;

	/* Fixed paths first, exactly like `nm_gallery_input_manifest()`'s own four: read by name, so
	   their absence is a defect the digest must carry rather than a set that is simply smaller. */
	$files = array(
		$base . '/ficha.md',
		$base . '/manifiesto-imagenes.md',
	);
	foreach ( array( 'canvas', 'maqueta', 'img' ) as $sub ) {
		foreach ( huella_walk( $base . '/' . $sub ) as $hit ) {
			$files[] = $hit;
		}
	}

	$manifest = array();
	foreach ( $files as $file ) {
		$rel               = ( 0 === strpos( $file, $skills_dir . '/' ) ) ? substr( $file, strlen( $skills_dir ) + 1 ) : $file;
		$hash              = huella_hash_file( $file );
		$manifest[ $rel ]  = ( null === $hash ) ? 'absent' : $hash;
	}
	ksort( $manifest, SORT_STRING );
	return $manifest;
}

/** sha256 over `"<path> <hash>\n"` lines, one per manifest entry — the digest itself. */
function huella_digest( array $manifest ) {
	$lines = '';
	foreach ( $manifest as $rel => $hash ) {
		$lines .= $rel . ' ' . $hash . "\n";
	}
	return hash( 'sha256', $lines );
}

/** One Plantilla's own huella: `huella_digest( huella_plantilla_manifest( … ) )`, named so a
 *  caller never has to build the manifest just to throw it away. */
function huella_plantilla( $skills_dir, $slug ) {
	return huella_digest( huella_plantilla_manifest( $skills_dir, $slug ) );
}

/**
 * The library's own huella: sha256 over `"<slug> <huella>\n"` lines sorted by slug — a digest of
 * digests, never a re-hash of every byte (`design.md`, "the library digest is a digest of
 * digests"). Returns the per-slug rows alongside the digest so a caller (`_biblioteca.md`) can
 * print both without recomputing.
 */
function huella_biblioteca( $skills_dir ) {
	$skills_dir     = rtrim( str_replace( '\\', '/', $skills_dir ), '/' );
	$plantillas_dir = $skills_dir . '/web-templates/references/plantillas';
	$rows           = array();
	if ( is_dir( $plantillas_dir ) ) {
		foreach ( glob( $plantillas_dir . '/*', GLOB_ONLYDIR ) as $dir ) {
			$slug           = basename( $dir );
			$rows[ $slug ]  = huella_plantilla( $skills_dir, $slug );
		}
	}
	ksort( $rows, SORT_STRING );
	$lines = '';
	foreach ( $rows as $slug => $hash ) {
		$lines .= $slug . ' ' . $hash . "\n";
	}
	return array(
		'rows'   => $rows,
		'digest' => hash( 'sha256', $lines ),
	);
}

/**
 * `path => sha256` for an arbitrary folder `$dir`, paths relative to `$dir` itself — see the
 * docblock above for how this differs from `huella_plantilla_manifest()`. A symlink among the
 * covered files that resolves outside `$dir` is refused before it is ever opened for hashing
 * (`NmHerramientaEntorno`, naming the offending path): a client delivery folder is not committed
 * repository content, so nothing here should follow a link off of it.
 */
function huella_directorio_manifest( $dir ) {
	$dir  = rtrim( str_replace( '\\', '/', $dir ), '/' );
	$real = realpath( $dir );

	$files = array();
	foreach ( array( 'ficha.md', 'manifiesto-imagenes.md' ) as $fijo ) {
		if ( is_file( $dir . '/' . $fijo ) ) {
			$files[] = $dir . '/' . $fijo;
		}
	}
	foreach ( array( 'canvas', 'maqueta', 'img' ) as $sub ) {
		foreach ( huella_walk( $dir . '/' . $sub ) as $hit ) {
			$files[] = $hit;
		}
	}

	$manifest = array();
	foreach ( $files as $file ) {
		$rel = ( 0 === strpos( $file, $dir . '/' ) ) ? substr( $file, strlen( $dir ) + 1 ) : $file;
		if ( false !== $real ) {
			$real_file = realpath( $file );
			if ( false === $real_file || 0 !== strpos( $real_file, $real . DIRECTORY_SEPARATOR ) ) {
				throw new NmHerramientaEntorno( "fuera de la carpeta: $rel resuelve fuera de $dir" );
			}
		}
		$manifest[ $rel ] = huella_hash_file( $file );
	}
	ksort( $manifest, SORT_STRING );
	return $manifest;
}

/** One folder's own huella: `huella_digest( huella_directorio_manifest( … ) )` — one digest
 *  definition shared with `huella_plantilla()`, never a second implementation. */
function huella_directorio( $dir ) {
	return huella_digest( huella_directorio_manifest( $dir ) );
}

// ─────────────────────────────────────────── dual-mode CLI ───────────────────────────────────────

if ( 'cli' === PHP_SAPI && isset( $argv[0] ) && realpath( $argv[0] ) === __FILE__ ) {
	function huella_cli_usage() {
		fwrite( STDERR, "huella: usage:\n"
			. "  huella.php --plantilla <slug> [--root=<dir>]\n"
			. "  huella.php --biblioteca [--root=<dir>]\n"
			. "  huella.php --comprobar <slug> <sha256> [--root=<dir>]\n" );
	}

	$args = array_slice( $argv, 1 );

	/* Default root: the `skills/` this file's own checkout lives under — three levels up from
	   `assets/herramientas/`, exactly like `_gallery-fingerprint.php`'s own `$skills` derivation. */
	$root = dirname( __DIR__, 3 );
	foreach ( $args as $a ) {
		if ( 0 === strpos( $a, '--root=' ) ) {
			$root = rtrim( substr( $a, 7 ), '/\\' );
		}
	}
	$args = array_values( array_filter( $args, function ( $a ) {
		return 0 !== strpos( $a, '--root=' );
	} ) );

	if ( array() === $args ) {
		huella_cli_usage();
		exit( 2 );
	}

	if ( '--biblioteca' === $args[0] ) {
		$r = huella_biblioteca( $root );
		foreach ( $r['rows'] as $slug => $hash ) {
			echo $slug . ' sha256:' . $hash . "\n";
		}
		echo "huella: sha256:" . $r['digest'] . "\n";
		exit( 0 );
	}

	if ( '--plantilla' === $args[0] ) {
		if ( ! isset( $args[1] ) ) {
			huella_cli_usage();
			exit( 2 );
		}
		$manifest = huella_plantilla_manifest( $root, $args[1] );
		foreach ( $manifest as $rel => $hash ) {
			echo $rel . ' ' . $hash . "\n";
		}
		echo 'huella: sha256:' . huella_digest( $manifest ) . "\n";
		exit( 0 );
	}

	if ( '--comprobar' === $args[0] ) {
		if ( ! isset( $args[1], $args[2] ) ) {
			huella_cli_usage();
			exit( 2 );
		}
		$slug     = $args[1];
		$expected = strtolower( $args[2] );
		$actual   = huella_plantilla( $root, $slug );
		if ( hash_equals( $expected, $actual ) ) {
			echo "huella: $slug coincide (sha256:$actual)\n";
			exit( 0 );
		}
		fwrite( STDERR, "huella: $slug no coincide — esperado sha256:$expected, actual sha256:$actual\n" );
		exit( 1 );
	}

	huella_cli_usage();
	exit( 2 );
}
