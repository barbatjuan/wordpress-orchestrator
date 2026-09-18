<?php
/**
 * empaquetar.php — packages ONE maqueta (a Plantilla's, or a client delivery's) into a single
 * self-contained HTML file, publishable as an Artifact.
 *
 * WHY THIS FILE EXISTS. A maqueta's `<img>`/`srcset`/`poster`/CSS `url()` references its photographs
 * as `../img/<file>.webp` — correct for the folder it lives in, meaningless once that HTML is
 * published on its own (an Artifact has no `../img/` beside it, and its CSP blocks every remote URL
 * regardless). `skills/web-templates/references/plantillas/<slug>/maqueta/index.html` carries a
 * `NM-IMG:BEGIN…END` comment naming exactly the strings a later mechanical step is meant to
 * search-and-replace (see that comment in `delao`'s own maqueta) — THIS is that step. It replaces
 * every `../img/<file>` occurrence with a `data:` URI of that file's own bytes, drops the manifest
 * comment itself (it is build-time documentation, not something to ship), and refuses — writing
 * nothing — when a referenced file is missing, a remote resource was left unresolved, or the packaged
 * result would cross the 16 MB an Artifact allows.
 *
 * TWO SOURCES, ONE PACKAGER. `--plantilla <slug>` resolves a committed Plantilla's own
 * `maqueta/index.html`; `--maqueta <archivo>` takes any maqueta file directly, images resolved
 * beside its own folder — a client delivery under `docs/entregas/…` or anywhere else, never required
 * to sit under `skills/`. Both funnel through the same `empaquetar_html()` / `empaquetar_archivo()`,
 * so a client folder is packaged with exactly the same rules a Plantilla is.
 *
 * ONE SEARCH-AND-REPLACE, NOT AN HTML PARSER. Every `../img/<file>` occurrence becomes that file's
 * `data:` URI wherever it sits — `src=`, one entry of a `srcset=` list, `poster=`, a `<style>` or
 * inline `url()` — because the substring itself is the only thing that needs to change; the
 * surrounding quotes, commas and descriptors are left exactly as they were. `empaquetar_remotos()`
 * is the one place that DOES need tag-aware matching (an `<a href="https://…">` link must survive
 * untouched, a `<link rel="stylesheet" href="https://…">` must not).
 *
 * EXIT CONTRACT, shared with the rest of the toolbox: `0` pass and the file is written, `1` a
 * measured failure (a missing image, a remote resource left, the 16 MB ceiling) and NOTHING is
 * written, `2` usage or environment (bad flags, an invalid slug, a source or `--out` that cannot be
 * resolved). Library functions throw `NmHerramientaMedida` / `NmHerramientaEntorno` (declared in
 * color.php) and never exit(); only the CLI guard at the bottom maps them to codes.
 */

require_once __DIR__ . '/color.php';

/** A slug is one path segment of lowercase letters, digits and hyphens — same shape `veredicto.php`
 *  anchors a Plantilla unit on. Anything else (`..`, a slash) is refused before touching disk. */
const NM_EMPAQUETAR_SLUG = '/^[a-z0-9][a-z0-9-]*$/';

/** The Artifact ceiling this file refuses to cross: 16 MiB, the same number the publishing surface
 *  itself enforces on the finished page. */
const NM_EMPAQUETAR_MAX_BYTES = 16 * 1024 * 1024;

/**
 * The media type a `data:` URI must carry, by file extension. Every photograph in the library is
 * `.webp` today, so a single hardcoded `image/webp` would package all of them correctly and be
 * wrong the first time a client maqueta brings a logo: an SVG served as `image/webp` renders as a
 * broken image in every browser, with no error anywhere. The type is read from the name or the
 * file is refused — `empaquetar_tipo()` never guesses.
 */
const NM_EMPAQUETAR_TIPOS = array(
	'webp' => 'image/webp',
	'png'  => 'image/png',
	'jpg'  => 'image/jpeg',
	'jpeg' => 'image/jpeg',
	'gif'  => 'image/gif',
	'svg'  => 'image/svg+xml',
	'avif' => 'image/avif',
	'ico'  => 'image/x-icon',
);

/**
 * `$nombre`'s media type from its extension (case-insensitive). Throws `NmHerramientaMedida` for an
 * extension not in `NM_EMPAQUETAR_TIPOS`: a maqueta referencing a file this packager cannot type is
 * a measured defect of that maqueta, refused before anything is written — never shipped under a
 * plausible-looking wrong type.
 */
function empaquetar_tipo( $nombre ) {
	$ext = strtolower( pathinfo( $nombre, PATHINFO_EXTENSION ) );
	if ( ! isset( NM_EMPAQUETAR_TIPOS[ $ext ] ) ) {
		throw new NmHerramientaMedida(
			"tipo desconocido: $nombre — un data: URI necesita su tipo real; admitidos: "
			. implode( ', ', array_keys( NM_EMPAQUETAR_TIPOS ) )
		);
	}
	return NM_EMPAQUETAR_TIPOS[ $ext ];
}

/**
 * The path to `<slug>`'s own `maqueta/index.html` under `$root`'s library — validates the slug shape
 * and that the file exists, without reading or packaging anything yet. Throws
 * `NmHerramientaEntorno` for a malformed slug (a path, `..`, a slash) or a Plantilla with no
 * `maqueta/index.html`: a request for a maqueta that is not there is usage, not a measurement.
 */
function empaquetar_maqueta_plantilla( $root, $slug ) {
	if ( ! is_string( $slug ) || 1 !== preg_match( NM_EMPAQUETAR_SLUG, $slug ) ) {
		throw new NmHerramientaEntorno( "slug no válido: «{$slug}» — solo minúsculas, dígitos y guiones" );
	}
	$root    = rtrim( str_replace( '\\', '/', $root ), '/' );
	$archivo = $root . '/skills/web-templates/references/plantillas/' . $slug . '/maqueta/index.html';
	if ( ! is_file( $archivo ) ) {
		throw new NmHerramientaEntorno( "no existe maqueta/index.html para la plantilla {$slug} (buscado en $archivo)" );
	}
	return $archivo;
}

/**
 * Every remote resource `$html` still leans on, ONLY the contexts that actually load bytes over the
 * network — `src=`/`poster=` (any tag), a `<link rel="stylesheet" href=…>`, a CSS `url(...)` whether
 * in a `<style>` block or an inline `style=` attribute — never a plain `<a href="https://…">` link,
 * which is a reference for a human to follow, not a resource this document depends on to render.
 * Anything inside an HTML comment is invisible to this scan: a commented-out tag loads nothing.
 * Protocol-relative (`//host/…`) counts as remote, the same as an explicit `http(s)://`.
 */
function empaquetar_remotos( $html ) {
	$sin_comentarios = preg_replace( '/<!--.*?-->/s', '', $html );
	$remoto          = '(?:https?:)?\/\/[^"\']*';
	$remotos         = array();

	if ( preg_match_all( '/\b(?:src|poster)\s*=\s*(["\'])(' . $remoto . ')\1/i', $sin_comentarios, $m ) ) {
		foreach ( $m[2] as $url ) {
			$remotos[] = $url;
		}
	}
	if ( preg_match_all( '/<link\b[^>]*>/i', $sin_comentarios, $tags ) ) {
		foreach ( $tags[0] as $tag ) {
			if ( preg_match( '/\brel\s*=\s*(["\'])stylesheet\1/i', $tag )
				&& preg_match( '/\bhref\s*=\s*(["\'])(' . $remoto . ')\1/i', $tag, $hm ) ) {
				$remotos[] = $hm[2];
			}
		}
	}
	if ( preg_match_all( '/url\(\s*(["\']?)((?:https?:)?\/\/[^"\')]*)\1\s*\)/i', $sin_comentarios, $um ) ) {
		foreach ( $um[2] as $url ) {
			$remotos[] = $url;
		}
	}
	return array_values( array_unique( $remotos ) );
}

/**
 * Package `$html` — the bytes of a `maqueta/index.html` sitting in `$maqueta_dir` (its OWN `maqueta/`
 * folder; `../img/` resolves to the sibling `img/` beside it) — into one self-contained document.
 *
 * Drops the `NM-IMG:BEGIN…END` manifest comment first: it exists to make the search-and-replace
 * below mechanical, not to ship in the artifact, and its own mentions of `../img/<file>` are not
 * "references this document loads" — counting them would inflate `imagenes`/`referencias` with a
 * comment nobody renders. Then refuses (throwing, changing nothing) on a remote resource left
 * unresolved, or a `../img/<file>` naming a file that is not on disk, before ever encoding a byte.
 * Every remaining `../img/<file>` occurrence becomes that file's `data:<su tipo>;base64,…` URI, the
 * type read from its extension by `empaquetar_tipo()` (an extension with no known type is refused,
 * never shipped under a guessed one) — the whole file is base64, never re-encoded, so the embedded
 * bytes decode back to exactly what was on disk. Finally refuses when the packaged result would
 * cross the 16 MB Artifact ceiling — the one check that can only run after encoding, since only the
 * ENCODED size decides it.
 *
 * Returns `[ 'html' => packaged bytes, 'imagenes' => distinct files embedded, 'referencias' =>
 * total `../img/` occurrences replaced, 'bytes' => strlen( html ) ]`.
 */
function empaquetar_html( $html, $maqueta_dir ) {
	$html = preg_replace_callback(
		'/<!--.*?-->/s',
		function ( $m ) {
			return ( false !== strpos( $m[0], 'NM-IMG:BEGIN' ) ) ? '' : $m[0];
		},
		$html
	);

	$remotos = empaquetar_remotos( $html );
	if ( array() !== $remotos ) {
		throw new NmHerramientaMedida(
			'remoto: la maqueta deja recursos remotos sin resolver — ' . implode( ', ', $remotos )
			. ' — un archivo único no puede depender de la red; descárgalo a img/ y referencia ../img/'
		);
	}

	if ( ! preg_match_all( '/\.\.\/img\/([A-Za-z0-9._-]+)/', $html, $m ) ) {
		return array(
			'html'        => $html,
			'imagenes'    => 0,
			'referencias' => 0,
			'bytes'       => strlen( $html ),
		);
	}
	$referencias = count( $m[0] );
	$archivos    = array_values( array_unique( $m[1] ) );
	$img_dir     = rtrim( str_replace( '\\', '/', dirname( $maqueta_dir ) ), '/' ) . '/img';

	$faltan = array();
	foreach ( $archivos as $nombre ) {
		if ( ! is_file( $img_dir . '/' . $nombre ) ) {
			$faltan[] = $nombre;
		}
	}
	if ( array() !== $faltan ) {
		throw new NmHerramientaMedida(
			'no existe la imagen referenciada: ' . implode( ', ', $faltan ) . ' (esperada en ' . $img_dir . ')'
		);
	}

	$buscar    = array();
	$reemplazo = array();
	foreach ( $archivos as $nombre ) {
		$tipo        = empaquetar_tipo( $nombre );
		$buscar[]    = '../img/' . $nombre;
		$reemplazo[] = 'data:' . $tipo . ';base64,' . base64_encode( file_get_contents( $img_dir . '/' . $nombre ) );
	}
	$html  = str_replace( $buscar, $reemplazo, $html );
	$bytes = strlen( $html );
	if ( $bytes > NM_EMPAQUETAR_MAX_BYTES ) {
		throw new NmHerramientaMedida( sprintf(
			'supera el techo de 16 MB de un Artifact: %d bytes empaquetados (%.1f MB)',
			$bytes,
			$bytes / 1000000
		) );
	}

	return array(
		'html'        => $html,
		'imagenes'    => count( $archivos ),
		'referencias' => $referencias,
		'bytes'       => $bytes,
	);
}

/**
 * Package the maqueta at `$maqueta_html` and write the result to `$out` — the only function in this
 * file that touches `$out`. Refuses, writing nothing, when `$maqueta_html` does not exist, `$out`'s
 * own directory does not exist, `$out` names `$maqueta_html` itself (packaging can never overwrite
 * its own source), or `empaquetar_html()` itself refuses. Returns what `empaquetar_html()` returns.
 */
function empaquetar_archivo( $maqueta_html, $out ) {
	if ( ! is_string( $maqueta_html ) || ! is_file( $maqueta_html ) ) {
		throw new NmHerramientaEntorno( "no existe la maqueta: $maqueta_html" );
	}
	$origen  = realpath( $maqueta_html );
	$out_dir = dirname( $out );
	if ( ! is_dir( $out_dir ) ) {
		throw new NmHerramientaEntorno( "el directorio de --out no existe: $out_dir" );
	}
	if ( is_file( $out ) && false !== $origen && realpath( $out ) === $origen ) {
		throw new NmHerramientaEntorno( "--out no puede ser la propia maqueta de origen: $out" );
	}

	$html      = file_get_contents( $maqueta_html );
	$resultado = empaquetar_html( $html, str_replace( '\\', '/', dirname( $maqueta_html ) ) );

	if ( false === file_put_contents( $out, $resultado['html'] ) ) {
		throw new NmHerramientaEntorno( "no se pudo escribir $out" );
	}
	return $resultado;
}

// ─────────────────────────────────────────── dual-mode CLI ───────────────────────────────────────

if ( 'cli' === PHP_SAPI && isset( $argv[0] ) && realpath( $argv[0] ) === __FILE__ ) {
	function empaquetar_cli_usage() {
		fwrite( STDERR, "empaquetar: usage:\n"
			. "  empaquetar.php --plantilla <slug> --out <archivo> [--root=<dir>]\n"
			. "  empaquetar.php --maqueta <archivo> --out <archivo>\n" );
	}

	/* Default root: the repository this file's checkout lives in — four levels up from
	   `skills/html-mockup/assets/herramientas/`, exactly like `veredicto.php`'s own derivation. */
	$root      = dirname( __DIR__, 4 );
	$plantilla = null;
	$maqueta   = null;
	$out       = null;
	$args      = array_slice( $argv, 1 );

	if ( array() === $args ) {
		empaquetar_cli_usage();
		exit( 2 );
	}

	for ( $i = 0, $n = count( $args ); $i < $n; $i++ ) {
		$a = $args[ $i ];
		if ( 0 === strpos( $a, '--root=' ) ) {
			$root = rtrim( substr( $a, 7 ), '/\\' );
		} elseif ( '--plantilla' === $a && isset( $args[ $i + 1 ] ) ) {
			$plantilla = $args[ ++$i ];
		} elseif ( '--maqueta' === $a && isset( $args[ $i + 1 ] ) ) {
			$maqueta = $args[ ++$i ];
		} elseif ( '--out' === $a && isset( $args[ $i + 1 ] ) ) {
			$out = $args[ ++$i ];
		} else {
			empaquetar_cli_usage();
			exit( 2 );
		}
	}

	if ( null === $out || ( null === $plantilla && null === $maqueta ) || ( null !== $plantilla && null !== $maqueta ) ) {
		empaquetar_cli_usage();
		exit( 2 );
	}

	try {
		if ( null !== $plantilla ) {
			$maqueta_html = empaquetar_maqueta_plantilla( $root, $plantilla );
		} else {
			if ( ! is_file( $maqueta ) ) {
				throw new NmHerramientaEntorno( "no existe la maqueta: $maqueta" );
			}
			$maqueta_html = $maqueta;
		}
		$res = empaquetar_archivo( $maqueta_html, $out );
		printf(
			"empaquetar: %s — %d bytes, %d imágenes, %d referencias\n",
			$out,
			$res['bytes'],
			$res['imagenes'],
			$res['referencias']
		);
		exit( 0 );
	} catch ( NmHerramientaMedida $e ) {
		fwrite( STDERR, 'empaquetar: ' . $e->getMessage() . "\n" );
		exit( 1 );
	} catch ( NmHerramientaEntorno $e ) {
		fwrite( STDERR, 'empaquetar: ' . $e->getMessage() . "\n" );
		exit( 2 );
	}
}
