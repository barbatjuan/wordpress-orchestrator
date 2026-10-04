<?php
/**
 * color.php — the ONE colour/contrast engine, lifted from the retired gallery generator, never re-derived.
 *
 * WHY THIS FILE EXISTS. The gallery generator carried the only WCAG 2.1 contrast maths and the
 * only house-ink derivation in the repo. Once the generator is no longer the sole author of a
 * Plantilla's colour (`openspec/changes/plantillas-reales/design.md`), something else needs the
 * SAME arithmetic — the Maqueta contrast gate a later PR wires into `framework-audit.php`,
 * `veredicto.php`, and a human checking two hexes before committing to them. Copying the formulas
 * a second time is exactly the defect the retired gallery fingerprint already named: "two
 * implementations of one rule drift,
 * and the hand-rolled one loses." So this file is REQUIRED by the retired gallery generator rather than
 * duplicated into it — the generator keeps running, it just stops owning the maths.
 *
 * WHAT MOVED HERE, VERBATIM. `srgb_lum`/`srgb_lum_rgb`/`contrast`/`ratio_str`/`css_mix` (originally
 * the retired gallery generator). Not one coefficient, threshold or order of
 * operations changed; only the FAILURE SIGNAL did (see below), because this file now has two
 * callers with different needs.
 *
 * WHY THE FAILURE SIGNAL CHANGED. The retired gallery generator's `fail()` writes to STDERR and calls
 * `exit(1)` unconditionally — correct for a single-purpose generator that has nothing left to do
 * once one measurement is wrong. This file is also a dual-mode CLI with a THREE-WAY exit contract
 * (`0` pass, `1` a measured failure, `2` usage/environment — never `0` for "could not measure",
 * the typed-not-available discipline design.md states "at the process boundary"), and it is also a
 * LIBRARY `framework-audit.php` will `require` in a later PR — a long-running process that must
 * not die because one Plantilla's `ficha.md` has a malformed hex. So every place that used to call
 * the generator's `fail()` now throws one of two typed exceptions instead, and each CALLER decides
 * what that means: the CLI guard at the bottom of this file maps them to exit codes, and
 * the retired gallery generator maps them straight back to its own `fail()` so its behaviour is unchanged.
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
// formula `design-system.md` states, lifted from the retired gallery generator.

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
 * lifted from the retired gallery generator together with the docblock's own warning.
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
 *  Lifted from the retired gallery generator. */
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
 * the retired gallery generator), 3.0:1 for a UI boundary such as `--c-border`
 * (the retired gallery generator). Returns one row per pair; `ok` is false when the pair is below
 * its bar.
 */
function color_root_pairs( $html ) {
	$tokens = color_root_tokens( $html );
	$bgs    = array();
	$fgs    = array();
	foreach ( $tokens as $name => $hex ) {
		/* `--c-on-X` is the text painted ON `--c-X`: a button label, a selected chip. It is TEXT
		   whatever else its name contains, and it is checked below against the colour it sits on. */
		$role = ( 1 === preg_match( '/^--c-on-/', $name ) ) ? 'text' : color_token_role( $name );
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
		/* WHICH backgrounds a foreground is measured on. Every ground, by default: body text can land
		   on any of them. But an `--c-on-X` never sits on the ground — it sits on `--c-X` and on
		   `--c-X-hover` — and crossing it with the ground measures a pair no CSS forms. Four
		   Plantillas hit that: on BAJURA `--c-on-accent` and `--c-bg` are the same hex, so the gate
		   read 1,00:1 and failed a label that measures 7,77:1 on the accent it is printed on. The
		   choice was renaming a correct token or leaving the gate red; the convention was the fix.

		   The convention has two spellings, and both are looked for: `--c-X` (BAJURA's
		   `--c-accent`) and `--c-surface-X` (delao's `--c-surface-inverse`, the band its
		   `--c-on-inverse` sits on). The first version knew only the first, fell back to the grounds
		   on delao and invented a 1,00:1 failure on a Plantilla that had passed the day before.
		   With neither declared there is nothing to be on, so it falls back to the grounds rather
		   than measuring nothing. */
		$on = $bgs;
		if ( 1 === preg_match( '/^--c-on-(.+)$/', $fg_name, $m ) ) {
			$sobre = null;
			foreach ( array( '--c-' . $m[1], '--c-surface-' . $m[1] ) as $candidato ) {
				if ( isset( $tokens[ $candidato ] ) ) {
					$sobre = $candidato;
					break;
				}
			}
			if ( null !== $sobre ) {
				$on = array( $sobre => $tokens[ $sobre ] );
				if ( isset( $tokens[ $sobre . '-hover' ] ) ) {
					$on[ $sobre . '-hover' ] = $tokens[ $sobre . '-hover' ];
				}
			}
		}
		foreach ( $on as $bg_name => $bg_hex ) {
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
// of the tree it was given with `--root`, the same arrangement the retired gallery fingerprint already
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
