<?php
/**
 * color.php — the ONE colour/contrast engine, lifted from `_build-gallery.php`, never re-derived.
 *
 * WHY THIS FILE EXISTS. The gallery generator carried the only WCAG 2.1 contrast maths and the
 * only house-ink derivation in the repo. Once the generator is no longer the sole author of a
 * Plantilla's colour (`openspec/changes/plantillas-reales/design.md`), something else needs the
 * SAME arithmetic — the Maqueta contrast gate a later PR wires into `framework-audit.php`,
 * `veredicto.php`, and a human checking two hexes before committing to them. Copying the formulas
 * a second time is exactly the defect `_gallery-fingerprint.php:9-14` already named: "two
 * implementations of one rule drift,
 * and the hand-rolled one loses." So this file is REQUIRED by `_build-gallery.php` rather than
 * duplicated into it — the generator keeps running, it just stops owning the maths.
 *
 * WHAT MOVED HERE, VERBATIM. `srgb_lum`/`srgb_lum_rgb`/`contrast`/`ratio_str`/`css_mix` (originally
 * `_build-gallery.php:220-244` and `:1565-1733`) and the house-ink derivation `ink_tint`/
 * `ink_ends`/`ink_curve` plus the accent gate — the contrast/spread/endpoint invariants `ink_ends`
 * asserts on its own output (originally `:941-1116`). Not one coefficient, threshold or order of
 * operations changed; only the FAILURE SIGNAL did (see below), because this file now has two
 * callers with different needs.
 *
 * WHY THE FAILURE SIGNAL CHANGED. `_build-gallery.php`'s `fail()` writes to STDERR and calls
 * `exit(1)` unconditionally — correct for a single-purpose generator that has nothing left to do
 * once one measurement is wrong. This file is also a dual-mode CLI with a THREE-WAY exit contract
 * (`0` pass, `1` a measured failure, `2` usage/environment — never `0` for "could not measure",
 * the typed-not-available discipline design.md states "at the process boundary"), and it is also a
 * LIBRARY `framework-audit.php` will `require` in a later PR — a long-running process that must
 * not die because one Plantilla's `ficha.md` has a malformed hex. So every place that used to call
 * the generator's `fail()` now throws one of two typed exceptions instead, and each CALLER decides
 * what that means: the CLI guard at the bottom of this file maps them to exit codes, and
 * `_build-gallery.php` maps them straight back to its own `fail()` so its behaviour is unchanged.
 */

/** A precondition this file's maths cannot proceed without: a malformed hex, a missing file, GD
 *  absent. Usage or environment, never a contrast verdict — maps to exit 2 at a CLI boundary. */
class NmHerramientaEntorno extends RuntimeException {}

/** An invariant this file's own maths asserts did not hold, or a measured value crossed the bar
 *  it was checked against. A real measurement, not a mistake — maps to exit 1 at a CLI boundary. */
class NmHerramientaMedida extends RuntimeException {}

// ─────────────────────────────────────────── WCAG 2.1 luminance / contrast ───────────────────────
//
// L = 0.2126R + 0.7152G + 0.0722B over linearised sRGB, ratio = (Lhi+.05)/(Llo+.05). The same
// formula `design-system.md` states, lifted from `_build-gallery.php:220-244`.

function srgb_lum( $hex ) {
	$hex = ltrim( $hex, '#' );
	/* `strlen() === 6` alone (the original gallery check) accepts `#ZZZZZZ`: `hexdec()` on a
	   non-hex character silently ignores it rather than erroring, which would have measured a
	   malformed CLI argument as if it were `#000000` — a usage error wearing a contrast verdict.
	   The gallery never hit this because every hex it ever measured was hardcoded and valid; a
	   standalone CLI reading arbitrary argv cannot make that assumption. */
	if ( 1 !== preg_match( '/^[0-9a-fA-F]{6}$/', $hex ) ) {
		throw new NmHerramientaEntorno( "not a 6-digit hex: #$hex" );
	}
	$l = 0.0;
	foreach ( array( 0 => 0.2126, 2 => 0.7152, 4 => 0.0722 ) as $off => $coeff ) {
		$c  = hexdec( substr( $hex, $off, 2 ) ) / 255;
		$c  = ( $c <= 0.04045 ) ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		$l += $coeff * $c;
	}
	return $l;
}

/**
 * The same formula as `srgb_lum()`, over raw 0-255 channels rather than a hex string — needed by
 * `scrim.php`, which measures composited pixels that never have a hex form.
 *
 * THE COEFFICIENTS ARE THE VALUES AND THE CHANNELS THE KEYS, not the other way round: PHP casts a
 * float array key to int, so `array( 0.2126 => $r, ... )` would collapse to one entry at key 0 —
 * lifted from `_build-gallery.php:1565-1573` together with the docblock's own warning.
 */
function srgb_lum_rgb( $r, $g, $b ) {
	$l = 0.0;
	foreach ( array( array( 0.2126, $r ), array( 0.7152, $g ), array( 0.0722, $b ) ) as $pair ) {
		$c  = $pair[1] / 255;
		$c  = ( $c <= 0.04045 ) ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		$l += $pair[0] * $c;
	}
	return $l;
}

function contrast( $a, $b ) {
	$la = srgb_lum( $a );
	$lb = srgb_lum( $b );
	$hi = max( $la, $lb );
	$lo = min( $la, $lb );
	return ( $hi + 0.05 ) / ( $lo + 0.05 );
}

function ratio_str( $a, $b ) {
	return number_format( contrast( $a, $b ), 2, '.', '' ) . ':1';
}

/** `color-mix(in srgb, $a $p%, $b)`, in PHP, so a token derived in CSS can be measured here.
 *  Lifted from `_build-gallery.php:1722-1733`. */
function css_mix( $a, $p, $b ) {
	$a   = ltrim( $a, '#' );
	$b   = ltrim( $b, '#' );
	$out = '';
	for ( $i = 0; $i < 3; $i++ ) {
		$out .= sprintf(
			'%02X',
			(int) round( hexdec( substr( $a, $i * 2, 2 ) ) * $p + hexdec( substr( $b, $i * 2, 2 ) ) * ( 1 - $p ) )
		);
	}
	return '#' . $out;
}

// ─────────────────────────────────────────── house-ink derivation + the accent gate ──────────────
//
// Lifted from `_build-gallery.php:941-1116`. The shadow ink IS the anchor's accent laid onto the
// ground's dark extreme (`:864`), so the invariants `ink_ends()` asserts on its own output — the
// tint search converged, the shadow did not get lighter, the shadow carries real hue, neither
// endpoint welds onto the page's own extreme — are collectively "the accent gate": the thing that
// stops a bright accent from silently lifting a black or bleaching into the page it sits on.

/** Five interior stops: two cannot bend (a straight line tints midtones as hard as the ends), and
 *  five is enough for the split tone to fall off before the midtones. `_build-gallery.php:945`. */
if ( ! defined( 'INK_STOPS' ) ) {
	define( 'INK_STOPS', 5 );
}

/**
 * The most an 8-bit rounding of `$hex` can have moved its luminance: half a step on every channel.
 * Derived rather than typed — the sRGB transfer curve is flat near black and steep near white, so
 * one tolerance covering both ends would be loose enough at the dark end to hide a real lift.
 * Lifted from `_build-gallery.php:993-998`.
 */
function ink_quant_bound( $hex ) {
	$c  = array( hexdec( substr( $hex, 1, 2 ) ), hexdec( substr( $hex, 3, 2 ) ), hexdec( substr( $hex, 5, 2 ) ) );
	$hi = srgb_lum_rgb( min( 255, $c[0] + 0.5 ), min( 255, $c[1] + 0.5 ), min( 255, $c[2] + 0.5 ) );
	$lo = srgb_lum_rgb( max( 0, $c[0] - 0.5 ), max( 0, $c[1] - 0.5 ), max( 0, $c[2] - 0.5 ) );
	return ( $hi - $lo ) / 2;
}

/**
 * `$base`, pushed toward `$accent`, then put back on `$base`'s own luminance — a binary search over
 * a single scalar multiplier, so the mix keeps its hue and loses only its weight. Returns the
 * unrounded triple as well as the 8-bit hex, because the caller checks both against different
 * tolerances (float precision for the search, quantisation bound for the hex). Lifted from
 * `_build-gallery.php:962-983`.
 */
function ink_tint( $base, $accent, $w ) {
	$mixed  = css_mix( $base, 1 - $w, $accent );
	$src    = array( hexdec( substr( $mixed, 1, 2 ) ), hexdec( substr( $mixed, 3, 2 ) ), hexdec( substr( $mixed, 5, 2 ) ) );
	$target = srgb_lum( $base );
	$lo     = 0.0;
	$hi     = 2.0;
	for ( $i = 0; $i < 64; $i++ ) {
		$k   = ( $lo + $hi ) / 2;
		$lum = srgb_lum_rgb( min( 255, $src[0] * $k ), min( 255, $src[1] * $k ), min( 255, $src[2] * $k ) );
		if ( $lum < $target ) {
			$lo = $k;
		} else {
			$hi = $k;
		}
	}
	$k     = ( $lo + $hi ) / 2;
	$exact = array( min( 255, $src[0] * $k ), min( 255, $src[1] * $k ), min( 255, $src[2] * $k ) );
	return array(
		'hex'   => sprintf( '#%02X%02X%02X', (int) round( $exact[0] ), (int) round( $exact[1] ), (int) round( $exact[2] ) ),
		'exact' => $exact,
	);
}

/**
 * The two inks for one ground: the accent in the shadows, the ground's own light in the highlights.
 * The 94/96 pull onto `$dark_src`/`$light_src` keeps either endpoint off the page's own extreme, so
 * a photograph's shadow never welds to `--c-bg`/`--c-text` and loses its edge. Lifted from
 * `_build-gallery.php:1010-1081`, including every invariant it asserts on its own output — THE
 * ACCENT GATE proper: convergence, weight preservation, hue spread, endpoint distinctness. Each
 * violation throws `NmHerramientaMedida` where the generator used to call `fail()`.
 */
function ink_ends( $gr, $accent, $tint ) {
	$dark_src  = ( srgb_lum( $gr['bg'] ) < srgb_lum( $gr['text'] ) ) ? $gr['bg'] : $gr['text'];
	$light_src = ( $dark_src === $gr['bg'] ) ? $gr['text'] : $gr['bg'];
	$neutral   = css_mix( $dark_src, 0.94, $light_src );
	$tinted    = ink_tint( $neutral, $accent, $tint );
	$ends      = array(
		'dark'    => $tinted['hex'],
		'light'   => css_mix( $light_src, 0.96, $dark_src ),
		'neutral' => $neutral,
	);

	$ink_target = srgb_lum( $neutral );
	if ( abs( srgb_lum_rgb( $tinted['exact'][0], $tinted['exact'][1], $tinted['exact'][2] ) - $ink_target ) > 1e-9 ) {
		throw new NmHerramientaMedida( sprintf(
			'the tint search for the shadow ink over %s did not converge on its own luminance'
				. ' (%.12f against %.12f) — a channel clipped, so there is no scalar that puts this'
				. ' mix back where the neutral endpoint was, and the shadow would ship lifted',
			$neutral,
			srgb_lum_rgb( $tinted['exact'][0], $tinted['exact'][1], $tinted['exact'][2] ),
			$ink_target
		) );
	}
	$ink_bound = ink_quant_bound( $ends['dark'] );
	if ( abs( srgb_lum( $ends['dark'] ) - $ink_target ) > $ink_bound ) {
		throw new NmHerramientaMedida( sprintf(
			'the shadow ink %s sits at L=%.8f where the neutral endpoint %s it replaced is L=%.8f, a'
				. ' gap of %.2e against the %.2e an 8-bit rounding of this colour can explain — the tint'
				. ' moved the shadow\'s WEIGHT, not just its hue, and a shadow ink that got lighter is a'
				. ' lifted black',
			$ends['dark'],
			srgb_lum( $ends['dark'] ),
			$neutral,
			$ink_target,
			abs( srgb_lum( $ends['dark'] ) - $ink_target ),
			$ink_bound
		) );
	}

	$ink_spread = max( hexdec( substr( $ends['dark'], 1, 2 ) ), hexdec( substr( $ends['dark'], 3, 2 ) ), hexdec( substr( $ends['dark'], 5, 2 ) ) )
		- min( hexdec( substr( $ends['dark'], 1, 2 ) ), hexdec( substr( $ends['dark'], 3, 2 ) ), hexdec( substr( $ends['dark'], 5, 2 ) ) );
	if ( $ink_spread < 20 ) {
		throw new NmHerramientaMedida( sprintf(
			'the shadow ink %s has a channel spread of %d, which is a neutral — a two-colour map whose'
				. ' dark ink is grey is not a two-colour map',
			$ends['dark'],
			$ink_spread
		) );
	}

	foreach ( array( 'dark', 'light' ) as $ink_which ) {
		foreach ( array( 'bg', 'text' ) as $ink_extreme ) {
			if ( strtoupper( $ends[ $ink_which ] ) === strtoupper( $gr[ $ink_extreme ] ) ) {
				throw new NmHerramientaMedida( "the house ink's $ink_which endpoint resolves to {$ends[ $ink_which ]}, which IS"
					. " this ground's --c-$ink_extreme. An endpoint on the page's own extreme gives the"
					. ' photograph a shadow (or a highlight) indistinguishable from the surface behind'
					. ' it, so the frame loses its edge' );
			}
		}
	}
	return $ends;
}

/**
 * The per-channel curve, as the exact strings `feFuncR/G/B` will parse: `$gamma` bends the input
 * before the tone is applied, and the split tone falls off as (1−s)² toward the shadow ink and s²
 * toward the highlight ink. Lifted from `_build-gallery.php:1098-1114`.
 */
function ink_curve( $ends, $gamma ) {
	$rows = array();
	for ( $ch = 0; $ch < 3; $ch++ ) {
		$s   = hexdec( substr( $ends['dark'], 1 + $ch * 2, 2 ) ) / 255;
		$h   = hexdec( substr( $ends['light'], 1 + $ch * 2, 2 ) ) / 255;
		$row = array();
		for ( $i = 0; $i < INK_STOPS; $i++ ) {
			$x  = $i / ( INK_STOPS - 1 );
			$sx = 0.5 + ( $x - 0.5 ) * ( 1 + $gamma * ( 1 - pow( 2 * $x - 1, 2 ) ) );
			$sx = max( 0.0, min( 1.0, $sx ) );
			$v  = $sx + $s * pow( 1 - $sx, 2 ) + ( $h - 1 ) * pow( $sx, 2 );
			$row[] = rtrim( rtrim( sprintf( '%.5f', max( 0.0, min( 1.0, $v ) ) ), '0' ), '.' );
		}
		$rows[] = implode( ' ', $row );
	}
	return $rows;
}

// ─────────────────────────────────────────── the Maqueta's own `:root` pairs ─────────────────────

/**
 * Every `--name: #hex;` declaration inside the FIRST `:root { … }` block of `$html`. Non-hex values
 * (`var()`, `color-mix()`, a bare length) are not colours and are silently skipped — this reads
 * ONLY the resolved hex tokens a Maqueta is required to declare in `:root` once
 * (`skills/html-mockup/SKILL.md` Hard Rules).
 */
function color_root_tokens( $html ) {
	if ( ! preg_match( '/:root\s*\{([^}]*)\}/s', $html, $m ) ) {
		throw new NmHerramientaEntorno( 'no :root { … } block found' );
	}
	$tokens = array();
	if ( preg_match_all( '/--([a-z0-9-]+)\s*:\s*(#[0-9a-fA-F]{6}|#[0-9a-fA-F]{3})\s*;/i', $m[1], $mm, PREG_SET_ORDER ) ) {
		foreach ( $mm as $hit ) {
			$hex = $hit[2];
			if ( 4 === strlen( $hex ) ) { // #abc -> #aabbcc
				$hex = '#' . $hex[1] . $hex[1] . $hex[2] . $hex[2] . $hex[3] . $hex[3];
			}
			$tokens[ '--' . strtolower( $hit[1] ) ] = strtoupper( $hex );
		}
	}
	return $tokens;
}

/**
 * Classify a `:root` token name by the ROLE it plays for contrast purposes, mirroring the naming
 * convention `ux-design-system/references/design-tokens.md` already documents (`--c-bg`/
 * `--c-bg-alt` dominant, `--c-text`/`--c-accent` painted as text, `--c-border` painted as a UI
 * boundary). `null` means "not a role this gate measures" (state colours, secondary, etc.).
 */
function color_token_role( $name ) {
	if ( false !== strpos( $name, 'bg' ) ) {
		return 'bg';
	}
	if ( false !== strpos( $name, 'text' ) || false !== strpos( $name, 'accent' ) ) {
		return 'text';
	}
	if ( false !== strpos( $name, 'border' ) ) {
		return 'ui';
	}
	return null;
}

/**
 * Every (background, foreground) pair a Maqueta's `:root` declares, measured against the role's
 * bar: 4.5:1 for anything painted as TEXT (including the accent, which the eyebrow paints as text —
 * `_build-gallery.php:790-793`), 3.0:1 for a UI boundary such as `--c-border`
 * (`_build-gallery.php:1882-1905`). Returns one row per pair; `ok` is false when the pair is below
 * its bar.
 */
function color_root_pairs( $html ) {
	$tokens = color_root_tokens( $html );
	$bgs    = array();
	$fgs    = array();
	foreach ( $tokens as $name => $hex ) {
		$role = color_token_role( $name );
		if ( 'bg' === $role ) {
			$bgs[ $name ] = $hex;
		} elseif ( null !== $role ) {
			$fgs[ $name ] = array( 'hex' => $hex, 'bar' => ( 'text' === $role ) ? 4.5 : 3.0 );
		}
	}
	if ( array() === $bgs ) {
		throw new NmHerramientaEntorno( 'no --*bg* token found in :root — nothing to pair a text/UI token against' );
	}
	$rows = array();
	foreach ( $fgs as $fg_name => $fg ) {
		foreach ( $bgs as $bg_name => $bg_hex ) {
			$ratio  = contrast( $fg['hex'], $bg_hex );
			$rows[] = array(
				'fg'    => $fg_name,
				'bg'    => $bg_name,
				'ratio' => $ratio,
				'bar'   => $fg['bar'],
				'ok'    => $ratio >= $fg['bar'],
			);
		}
	}
	return $rows;
}

// ─────────────────────────────────────────── dual-mode CLI ───────────────────────────────────────
//
// Guarded exactly like design.md specifies: `framework-audit.php` can `require_once` this file out
// of the tree it was given with `--root`, the same arrangement `_gallery-fingerprint.php` already
// uses, without ever hitting this block.

if ( 'cli' === PHP_SAPI && isset( $argv[0] ) && realpath( $argv[0] ) === __FILE__ ) {
	function color_cli_usage() {
		fwrite( STDERR, "color: usage:\n"
			. "  color.php --contraste <#hex> <#hex>\n"
			. "  color.php --maqueta <archivo-html>\n" );
	}

	$args = array_slice( $argv, 1 );

	if ( array() === $args ) {
		color_cli_usage();
		exit( 2 );
	}

	try {
		if ( '--contraste' === $args[0] ) {
			if ( ! isset( $args[1], $args[2] ) ) {
				color_cli_usage();
				exit( 2 );
			}
			$ratio = contrast( $args[1], $args[2] );
			$str   = ratio_str( $args[1], $args[2] );
			if ( $ratio < 4.5 ) {
				fwrite( STDOUT, "color: {$args[1]} sobre {$args[2]} mide $str — bajo el 4.5:1 de AA\n" );
				exit( 1 );
			}
			fwrite( STDOUT, "color: {$args[1]} sobre {$args[2]} mide $str\n" );
			exit( 0 );
		}

		if ( '--maqueta' === $args[0] ) {
			if ( ! isset( $args[1] ) ) {
				color_cli_usage();
				exit( 2 );
			}
			if ( ! is_file( $args[1] ) ) {
				fwrite( STDERR, "color: no existe el archivo: {$args[1]}\n" );
				exit( 2 );
			}
			$html = file_get_contents( $args[1] );
			$rows = color_root_pairs( $html );
			$fail = false;
			foreach ( $rows as $row ) {
				$mark = $row['ok'] ? 'OK  ' : 'FAIL';
				fwrite( STDOUT, sprintf(
					"%s %s sobre %s = %.2f:1 (bar %.1f:1)\n",
					$mark,
					$row['fg'],
					$row['bg'],
					$row['ratio'],
					$row['bar']
				) );
				$fail = $fail || ! $row['ok'];
			}
			exit( $fail ? 1 : 0 );
		}

		color_cli_usage();
		exit( 2 );
	} catch ( NmHerramientaEntorno $e ) {
		fwrite( STDERR, 'color: ' . $e->getMessage() . "\n" );
		exit( 2 );
	} catch ( NmHerramientaMedida $e ) {
		fwrite( STDERR, 'color: ' . $e->getMessage() . "\n" );
		exit( 1 );
	}
}
