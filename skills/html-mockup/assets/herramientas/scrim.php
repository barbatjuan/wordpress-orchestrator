<?php
/**
 * scrim.php — the GD worst-case text-over-photo maths, lifted from `_build-gallery.php:1545-1700`.
 *
 * WHAT THIS MEASURES. The worst contrast any pixel of a photograph can reach once a text colour
 * sits over it through an alpha veil (a "scrim") and, optionally, the house ink filter — the same
 * sweep that caught the slider's 1.95:1 defect in the gallery generator (`_build-gallery.php:
 * 1644-1685`'s own docblock).
 *
 * ONE CONTRAST ENGINE. This file `require`s `color.php` for `srgb_lum`/`srgb_lum_rgb` rather than
 * re-deriving them — see `color.php`'s header for why a second copy of that maths is the exact
 * defect this whole change removes.
 *
 * TWO ENTRY POINTS, ONE SWEEP. `worst_pixel()` keeps its ORIGINAL signature and behaviour
 * (`$slug` resolved through a global `$IMG_DIR`, the whole image swept) so `_build-gallery.php`
 * keeps working unmodified at the call site once it `require`s this file instead of defining these
 * functions itself. `worst_pixel_path()` is the general-purpose form the CLI exposes: an explicit
 * file path and an explicit rectangle, because a standalone tool has no gallery manifest to resolve
 * a slug against and a Maqueta's text rarely sits over the FULL bleed of its hero image. Both call
 * the same private sweep, `scrim_sweep_worst()` — one measurement loop, two ways to reach it.
 *
 * THE EXIT CONTRACT. GD (or its WebP support) being absent is an ENVIRONMENT failure — the sweep
 * never ran, so it must never report `0` (pass) nor `1` (a measured failure that did not happen).
 * `NmHerramientaEntorno` (declared in `color.php`) carries that distinction through to the CLI
 * guard at the bottom of this file, which is what the threat matrix in `design.md` requires:
 * "scrim.php exits 2 (never 0) when GD is missing."
 */

require_once __DIR__ . '/color.php';

/**
 * `feFunc* type="table"`, per the SVG spec: piecewise-linear over n entries. Lifted from
 * `_build-gallery.php:1596-1607` — the exact primitive the browser applies to `ink_curve()`'s
 * output, so a sweep that skipped this would be measuring a picture the browser never paints.
 */
function fe_table( $c, $values ) {
	$n = count( $values );
	if ( $n < 2 ) {
		throw new NmHerramientaEntorno( 'a `tableValues` with fewer than two entries is not a transfer function' );
	}
	$c = max( 0.0, min( 1.0, $c ) );
	$k = (int) floor( $c * ( $n - 1 ) );
	if ( $k > $n - 2 ) {
		$k = $n - 2;
	}
	return $values[ $k ] + ( $c * ( $n - 1 ) - $k ) * ( $values[ $k + 1 ] - $values[ $k ] );
}

/**
 * The SVG filter, in PHP, so the sweep measures the pixels the browser will actually paint —
 * `feColorMatrix type="saturate"` then a five-entry `type="table"` per channel. Lifted from
 * `_build-gallery.php:1629-1642`.
 */
function ink_pixel( $r, $g, $b, $ink ) {
	$s = (float) $ink['sat'];
	$p = array(
		( 0.213 + 0.787 * $s ) * $r + ( 0.715 - 0.715 * $s ) * $g + ( 0.072 - 0.072 * $s ) * $b,
		( 0.213 - 0.213 * $s ) * $r + ( 0.715 + 0.285 * $s ) * $g + ( 0.072 - 0.072 * $s ) * $b,
		( 0.213 - 0.213 * $s ) * $r + ( 0.715 - 0.715 * $s ) * $g + ( 0.072 + 0.928 * $s ) * $b,
	);
	$out = array();
	for ( $i = 0; $i < 3; $i++ ) {
		$values = array_map( 'floatval', explode( ' ', $ink['table'][ $i ] ) );
		$out[]  = 255 * fe_table( max( 0.0, min( 255.0, $p[ $i ] ) ) / 255, $values );
	}
	return $out;
}

/**
 * Every 2nd pixel of an already-decoded GD image, in `[$x0,$x1) × [$y0,$y1)`, worst contrast
 * against `$text` under an `$alpha` veil of `$scrim`, optionally through `$ink`. The one
 * measurement loop both entry points below share — see the file header.
 */
function scrim_sweep_worst( $im, $x0, $y0, $x1, $y1, $scrim, $alpha, $text, $ink = null ) {
	$sr    = hexdec( substr( ltrim( $scrim, '#' ), 0, 2 ) );
	$sg    = hexdec( substr( ltrim( $scrim, '#' ), 2, 2 ) );
	$sb    = hexdec( substr( ltrim( $scrim, '#' ), 4, 2 ) );
	$l_txt = srgb_lum( $text );
	$worst   = INF;
	$surface = INF;
	for ( $y = $y0; $y < $y1; $y += 2 ) {
		for ( $x = $x0; $x < $x1; $x += 2 ) {
			$p  = imagecolorat( $im, $x, $y );
			$px = array( ( $p >> 16 ) & 0xFF, ( $p >> 8 ) & 0xFF, $p & 0xFF );
			if ( null !== $ink ) {
				$px = ink_pixel( $px[0], $px[1], $px[2], $ink );
			}
			$l   = srgb_lum_rgb(
				$px[0] * ( 1 - $alpha ) + $sr * $alpha,
				$px[1] * ( 1 - $alpha ) + $sg * $alpha,
				$px[2] * ( 1 - $alpha ) + $sb * $alpha
			);
			$hi  = max( $l, $l_txt );
			$lo  = min( $l, $l_txt );
			$rat = ( $hi + 0.05 ) / ( $lo + 0.05 );
			if ( $rat < $worst ) {
				$worst   = $rat;
				$surface = $l;
			}
		}
	}
	return array( 'ratio' => $worst, 'surface_l' => $surface );
}

/**
 * ORIGINAL signature and behaviour, lifted from `_build-gallery.php:1644-1685` unchanged: `$slug`
 * resolved through the global `$IMG_DIR` the gallery generator already defines, full-image sweep.
 * Kept exactly as-is so `_build-gallery.php` needs no call-site changes once it `require`s this
 * file — only the failure signal changed, from `fail()` to `NmHerramientaEntorno`.
 */
function worst_pixel( $slug, $scrim, $alpha, $text, $ink = null ) {
	global $IMG_DIR;
	if ( ! function_exists( 'imagecreatefromwebp' ) ) {
		throw new NmHerramientaEntorno( 'PHP has no GD WebP support — the slider scrim cannot be measured, and an unmeasured'
			. ' scrim over a photograph is the 1.95:1 defect this build already shipped once' );
	}
	$im = @imagecreatefromwebp( $IMG_DIR . '/' . $slug . '.webp' );
	if ( false === $im ) {
		throw new NmHerramientaEntorno( "cannot decode img/$slug.webp to measure the slider scrim" );
	}
	$w = imagesx( $im );
	$h = imagesy( $im );
	$r = scrim_sweep_worst( $im, 0, 0, $w, $h, $scrim, $alpha, $text, $ink );
	imagedestroy( $im );
	return $r;
}

/**
 * The general-purpose form: an explicit file path and an explicit rectangle rather than a gallery
 * slug and the whole frame. `$x,$y,$w,$h` are clamped to the decoded image's own bounds, so a
 * rectangle that only partly overlaps the image still measures the overlapping part rather than
 * refusing outright — a Maqueta's text box is placed by a human, not guaranteed pixel-exact.
 */
function worst_pixel_path( $path, $x, $y, $w, $h, $scrim, $alpha, $text, $ink = null ) {
	if ( ! function_exists( 'imagecreatefromwebp' ) ) {
		throw new NmHerramientaEntorno( 'PHP has no GD WebP support — a text-over-photo scrim cannot be measured' );
	}
	if ( ! is_file( $path ) ) {
		throw new NmHerramientaEntorno( "no existe la imagen: $path" );
	}
	$im = @imagecreatefromwebp( $path );
	if ( false === $im ) {
		throw new NmHerramientaEntorno( "no se pudo decodificar $path como WebP" );
	}
	$iw = imagesx( $im );
	$ih = imagesy( $im );
	$x0 = max( 0, (int) $x );
	$y0 = max( 0, (int) $y );
	$x1 = min( $iw, $x0 + max( 0, (int) $w ) );
	$y1 = min( $ih, $y0 + max( 0, (int) $h ) );
	if ( $x1 <= $x0 || $y1 <= $y0 ) {
		imagedestroy( $im );
		throw new NmHerramientaEntorno( 'la región pedida no cae dentro de la imagen' );
	}
	$r = scrim_sweep_worst( $im, $x0, $y0, $x1, $y1, $scrim, $alpha, $text, $ink );
	imagedestroy( $im );
	return $r;
}

/** Shared sweep behind both `ink_mean()` entry points below: the mean r/g/b of an already-decoded
 *  GD image under `$ink`, at stride 2. One measurement loop, same reason as `scrim_sweep_worst()`. */
function scrim_sweep_mean( $im, $ink ) {
	$w   = imagesx( $im );
	$h   = imagesy( $im );
	$sum = array( 0.0, 0.0, 0.0 );
	$n   = 0;
	for ( $y = 0; $y < $h; $y += 2 ) {
		for ( $x = 0; $x < $w; $x += 2 ) {
			$p  = imagecolorat( $im, $x, $y );
			$px = ink_pixel( ( $p >> 16 ) & 0xFF, ( $p >> 8 ) & 0xFF, $p & 0xFF, $ink );
			for ( $i = 0; $i < 3; $i++ ) {
				$sum[ $i ] += max( 0.0, min( 255.0, $px[ $i ] ) );
			}
			$n++;
		}
	}
	return array( $sum, $n );
}

/**
 * ORIGINAL signature and behaviour, lifted from `_build-gallery.php:1694-1719` unchanged: `$slug`
 * resolved through the global `$IMG_DIR`. Kept exactly as-is so `_build-gallery.php` needs no
 * call-site changes once it `require`s this file — only the failure signal changed.
 */
function ink_mean( $slug, $ink ) {
	global $IMG_DIR;
	if ( ! function_exists( 'imagecreatefromwebp' ) ) {
		throw new NmHerramientaEntorno( 'PHP has no GD WebP support — the house ink cannot be measured against a photograph' );
	}
	$im = @imagecreatefromwebp( $IMG_DIR . '/' . $slug . '.webp' );
	if ( false === $im ) {
		throw new NmHerramientaEntorno( "cannot decode img/$slug.webp to measure the house ink against it" );
	}
	list( $sum, $n ) = scrim_sweep_mean( $im, $ink );
	imagedestroy( $im );
	if ( 0 === $n ) {
		throw new NmHerramientaEntorno( "img/$slug.webp decoded to zero pixels" );
	}
	return array( $sum[0] / $n, $sum[1] / $n, $sum[2] / $n );
}

/**
 * The general-purpose form: an explicit file path rather than a gallery slug — what the standalone
 * tool uses.
 */
function ink_mean_path( $path, $ink ) {
	if ( ! function_exists( 'imagecreatefromwebp' ) ) {
		throw new NmHerramientaEntorno( 'PHP has no GD WebP support — the house ink cannot be measured against a photograph' );
	}
	if ( ! is_file( $path ) ) {
		throw new NmHerramientaEntorno( "no existe la imagen: $path" );
	}
	$im = @imagecreatefromwebp( $path );
	if ( false === $im ) {
		throw new NmHerramientaEntorno( "no se pudo decodificar $path como WebP" );
	}
	list( $sum, $n ) = scrim_sweep_mean( $im, $ink );
	imagedestroy( $im );
	if ( 0 === $n ) {
		throw new NmHerramientaEntorno( "$path decoded to zero pixels" );
	}
	return array( $sum[0] / $n, $sum[1] / $n, $sum[2] / $n );
}

// ─────────────────────────────────────────── dual-mode CLI ───────────────────────────────────────

if ( 'cli' === PHP_SAPI && isset( $argv[0] ) && realpath( $argv[0] ) === __FILE__ ) {
	function scrim_cli_usage() {
		fwrite( STDERR, "scrim: usage: scrim.php --peor-pixel <img.webp> <x> <y> <w> <h>"
			. " [--scrim <#hex>] [--alpha <0..1>] [--texto <#hex>] [--barra <n>]\n" );
	}

	$args = array_slice( $argv, 1 );

	if ( array() === $args || '--peor-pixel' !== $args[0] ) {
		scrim_cli_usage();
		exit( 2 );
	}

	if ( ! isset( $args[1], $args[2], $args[3], $args[4], $args[5] ) ) {
		scrim_cli_usage();
		exit( 2 );
	}

	$img = $args[1];
	$x   = $args[2];
	$y   = $args[3];
	$w   = $args[4];
	$h   = $args[5];

	$scrim_hex = '#000000';
	$alpha     = 0.55; // same floor `_build-gallery.php`'s own $SCRIM_FLOOR uses
	$text_hex  = '#FFFFFF';
	$bar       = 4.5;  // same bar `_build-gallery.php`'s own $SCRIM_BAR uses

	for ( $i = 6; $i < count( $args ); $i++ ) {
		if ( '--scrim' === $args[ $i ] && isset( $args[ $i + 1 ] ) ) {
			$scrim_hex = $args[ ++$i ];
		} elseif ( '--alpha' === $args[ $i ] && isset( $args[ $i + 1 ] ) ) {
			$alpha = (float) $args[ ++$i ];
		} elseif ( '--texto' === $args[ $i ] && isset( $args[ $i + 1 ] ) ) {
			$text_hex = $args[ ++$i ];
		} elseif ( '--barra' === $args[ $i ] && isset( $args[ $i + 1 ] ) ) {
			$bar = (float) $args[ ++$i ];
		} else {
			scrim_cli_usage();
			exit( 2 );
		}
	}

	try {
		$r = worst_pixel_path( $img, $x, $y, $w, $h, $scrim_hex, $alpha, $text_hex );
		fwrite( STDOUT, sprintf( "scrim: peor pixel = %.2f:1 (bar %.1f:1, superficie L=%.4f)\n", $r['ratio'], $bar, $r['surface_l'] ) );
		exit( $r['ratio'] < $bar ? 1 : 0 );
	} catch ( NmHerramientaEntorno $e ) {
		fwrite( STDERR, 'scrim: ' . $e->getMessage() . "\n" );
		exit( 2 );
	} catch ( NmHerramientaMedida $e ) {
		fwrite( STDERR, 'scrim: ' . $e->getMessage() . "\n" );
		exit( 1 );
	}
}
