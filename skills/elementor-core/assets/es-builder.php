<?php
/**
 * Elementor page builder helpers (WordPress Orchestrator raw-PHP).
 * Native Elementor / Elementor Pro widgets only. No custom CSS, no third-party widgets.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deterministic element ids.
 *
 * Elementor keys its generated CSS on element ids, so random ids desync the
 * stylesheet from cached HTML on every rebuild. A seeded counter keeps ids
 * stable across regenerations of the same layout.
 */
function es_uid() {
	global $es_id_seed, $es_id_n;
	if ( ! isset( $es_id_seed ) ) {
		$es_id_seed = 'es';
	}
	$es_id_n = isset( $es_id_n ) ? $es_id_n + 1 : 1;
	return substr( md5( $es_id_seed . '-' . $es_id_n ), 0, 7 );
}

/** Start a new stable id sequence for one page or template. */
function es_uid_reset( $seed ) {
	global $es_id_seed, $es_id_n;
	$es_id_seed = $seed;
	$es_id_n    = 0;
}

function es_c( array $settings, array $children = array(), $inner = false ) {
	return array(
		'id'       => es_uid(),
		'elType'   => 'container',
		'settings' => $settings,
		'elements' => $children,
		'isInner'  => $inner,
	);
}

function es_w( $type, array $settings ) {
	return array(
		'id'         => es_uid(),
		'elType'     => 'widget',
		'widgetType' => $type,
		'settings'   => $settings,
		'elements'   => array(),
	);
}

/**
 * A four-sided box value, with the density axis applied ONCE, here (a call site could forget).
 *
 *  - $unit: density scales LENGTHS only. A percentage or viewport unit is already relative, so
 *    multiplying it changes the layout. Every call is px today; the guard keeps a future non-px call
 *    from silently misbehaving.
 *  - $scale: a border WIDTH or RADIUS is not rhythm (at sp_scale 1.7 a 1px hairline rounds to 2px and
 *    a 16px radius becomes 27px). Those call sites say so with es_box_unscaled().
 */
function es_box( $t, $r, $b, $l, $unit = 'px', $scale = true ) {
	if ( $scale && 'px' === $unit ) {
		$t = es_sp( $t );
		$r = es_sp( $r );
		$b = es_sp( $b );
		$l = es_sp( $l );
	}
	return array(
		'unit'     => $unit,
		'top'      => (string) $t,
		'right'    => (string) $r,
		'bottom'   => (string) $b,
		'left'     => (string) $l,
		'isLinked' => false,
	);
}

/** A box the density axis must NOT touch: border widths and border radii. */
function es_box_unscaled( $t, $r, $b, $l ) {
	return es_box( $t, $r, $b, $l, 'px', false );
}

function es_size( $n, $unit = 'px' ) {
	return array( 'unit' => $unit, 'size' => $n, 'sizes' => array() );
}

function es_img( $slug ) {
	static $cache = array();
	if ( isset( $cache[ $slug ] ) ) {
		return $cache[ $slug ];
	}
	$posts = get_posts(
		array(
			'post_type'      => 'attachment',
			'name'           => $slug,
			'posts_per_page' => 1,
			'post_status'    => 'inherit',
		)
	);
	if ( empty( $posts ) ) {
		/* This used to return the empty shape and say NOTHING, which is how a mistyped slug shipped
		   an image widget with no <img> in it while every check stayed green. Two channels, because
		   the mistake happens HERE and is discovered LATER: es_warn() now, and `es_missing`, which
		   rides along in the settings so es_container_walk() can name the slug when it audits the
		   tree — including the re-audit qa-review runs against what actually landed. Elementor
		   reads only `url` and `id`, so the extra key is inert to it and legible to us.
		   Cached like a hit so a slug used in ten places warns once, not ten times. */
		$cache[ $slug ] = array( 'url' => '', 'id' => '', 'es_missing' => $slug );
		es_warn( 'no existe ninguna imagen con el slug "' . $slug . '". El widget se va a construir SIN imagen. Sube el archivo o corrige el slug antes de desplegar.' );
		return $cache[ $slug ];
	}
	$cache[ $slug ] = array(
		'url' => wp_get_attachment_url( $posts[0]->ID ),
		'id'  => $posts[0]->ID,
	);
	return $cache[ $slug ];
}

/**
 * One token's colour, at an alpha, as an rgba() string.
 *
 * Glows and veils are derived through here so overriding the source colour moves all of them (a typed
 * rgba() keeps the old colour). $alpha is TEXT on purpose: `0.10` and `0.1` are the same float and
 * different bytes, and reformatting a number would rewrite emitted bytes.
 */
function es_rgba( $hex, $alpha ) {
	$h = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $h ) ) {
		$h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
	}
	if ( 6 !== strlen( $h ) || ! ctype_xdigit( $h ) ) {
		/* Loud and visibly unpainted beats silently plausible: a wrong colour
		   in a shadow reads as "the theme decided that", a missing one does
		   not, and the warning names the value that could not be read. */
		es_warn( 'es_rgba() no sabe leer "' . $hex . '" como color hex, asi que el efecto que lo usa se queda SIN pintar. Escribe el token como #RGB o #RRGGBB.' );
		return 'rgba(0,0,0,0)';
	}
	return 'rgba(' . hexdec( substr( $h, 0, 2 ) ) . ',' . hexdec( substr( $h, 2, 2 ) ) . ',' . hexdec( substr( $h, 4, 2 ) ) . ',' . $alpha . ')';
}

/**
 * One token's colour, darkened by a factor, as a hex string.
 *
 * The hover of a colour is not a second colour to remember. `accent_hover` was
 * a hand-picked darker GREEN sitting next to an accent a client is expected to
 * change -- so a navy brand got a navy button and a green hover, which is the
 * rgba bug from es_rgba() one level up: an edit point that is only one edit
 * point if you also remember the other one.
 *
 * Multiplying every channel by the same factor is the ordinary "darken": it
 * keeps the hue and the relative channel spacing and works on any brand,
 * including a dark one, where a fixed subtraction would flatten to black.
 */
function es_shade( $hex, $factor ) {
	$h = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $h ) ) {
		$h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
	}
	if ( 6 !== strlen( $h ) || ! ctype_xdigit( $h ) ) {
		/* Same reasoning as es_rgba(): loud and visibly unpainted beats
		   silently plausible, and the warning names the value it could not read. */
		es_warn( 'es_shade() no sabe leer "' . $hex . '" como color hex, asi que el estado derivado que lo usa se queda SIN pintar. Escribe el token como #RGB o #RRGGBB.' );
		return '';
	}
	$out = '#';
	for ( $i = 0; $i < 3; $i++ ) {
		$c    = (int) round( hexdec( substr( $h, $i * 2, 2 ) ) * $factor );
		$out .= sprintf( '%02X', max( 0, min( 255, $c ) ) );
	}
	return $out;
}

/**
 * One token's colour blended toward another's, as a hex string.
 *
 * This is the GROUND axis: `muted`, `text_soft`, `border`, `surface_inverse` and `on_inverse` are
 * blended between `bg` and `text` so they stay contrast-correct on any ground (values hand-picked off
 * a white page failed AA or vanished on dark grounds). Blending covers grounds nobody has documented;
 * a table of values covers only the four positions. Implements design-tokens.md step 4 ("Derive the
 * neutrals from the contrast, not from grey").
 */
function es_mix( $a, $b, $f ) {
	$out = '#';
	foreach ( array( $a, $b ) as $hex ) {
		$h = ltrim( (string) $hex, '#' );
		if ( 3 === strlen( $h ) ) {
			$h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
		}
		if ( 6 !== strlen( $h ) || ! ctype_xdigit( $h ) ) {
			/* Same reasoning as es_rgba() and es_shade(): loud and visibly unpainted beats silently
			   plausible, and the warning names the value it could not read. */
			es_warn( 'es_mix() no sabe leer "' . $hex . '" como color hex, asi que el neutro derivado que lo usa se queda SIN pintar. Escribe el token como #RGB o #RRGGBB.' );
			return '';
		}
		$c[] = $h;
	}
	for ( $i = 0; $i < 3; $i++ ) {
		$v    = (int) round( hexdec( substr( $c[0], $i * 2, 2 ) ) + ( hexdec( substr( $c[1], $i * 2, 2 ) ) - hexdec( substr( $c[0], $i * 2, 2 ) ) ) * $f );
		$out .= sprintf( '%02X', max( 0, min( 255, $v ) ) );
	}
	return $out;
}

/**
 * WCAG 2.x relative luminance of a hex colour, or null when it cannot be read.
 *
 * The coefficients and the 0.03928 / 12.92 / 1.055 / 2.4 constants are the WCAG formula verbatim.
 * Returns null instead of warning: es_contrast() knows WHICH of two colours was unreadable and says
 * so. The `#RGB` to `#RRGGBB` normalisation is the fourth copy of that parse (es_rgba, es_shade,
 * es_mix); folding them into one helper is a separate refactor.
 */
function es_lum( $hex ) {
	$h = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $h ) ) {
		$h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
	}
	if ( 6 !== strlen( $h ) || ! ctype_xdigit( $h ) ) {
		return null;
	}
	$s = 0.0;
	$k = array( 0.2126, 0.7152, 0.0722 );
	for ( $i = 0; $i < 3; $i++ ) {
		$c  = hexdec( substr( $h, $i * 2, 2 ) ) / 255;
		$c  = $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		$s += $k[ $i ] * $c;
	}
	return $s;
}

/**
 * WCAG 2.x contrast ratio between two hex colours, or 0.0 when one is unreadable.
 *
 * 0.0 is an impossible ratio -- two identical colours are 1.0:1 and the range
 * runs to 21:1 -- so it is a sentinel the caller can test for, in the same
 * spirit as es_mix() returning '': loud and visibly unpainted beats silently
 * plausible.
 *
 * Rounded to two decimals ON PURPOSE, and it is not cosmetic. tests/test-write-path.php
 * measures the same ratios with its OWN independent implementation and asserts
 * them against 4.5; if one side rounded and the other did not, a value at 4.4951
 * would pass the suite and warn in the build, or the reverse. Same rounding, one
 * verdict.
 */
function es_contrast( $a, $b ) {
	$x = es_lum( $a );
	$y = es_lum( $b );
	if ( null === $x || null === $y ) {
		es_warn( 'es_contrast() no sabe leer "' . ( null === $x ? $a : $b ) . '" como color hex, asi que la tinta que se elegia midiendo ese color se queda SIN pintar. Escribe el token como #RGB o #RRGGBB.' );
		return 0.0;
	}
	return round( ( max( $x, $y ) + 0.05 ) / ( min( $x, $y ) + 0.05 ), 2 );
}

/**
 * The ink that goes ON a surface: whichever candidate reads best against it.
 *
 * The CHOICE is derived, not the colour: a pinned white fails a light accent and a pinned near-black
 * fails a navy one. Candidates are the two ground extremes (`text` and `bg`), so the label is always a
 * colour already in the palette.
 *
 * TIES (equal at two decimals) go to the first candidate listed, so output is reproducible.
 * WHEN NEITHER CANDIDATE REACHES 4.5:1 it paints the better one and warns naming both measurements.
 * The branch is reachable by construction (on a `paper` ground the worst accents, e.g. #9966BB, top
 * out near 4.22:1 against either extreme); refusing to paint would leave the widget default, and
 * painting silently would hide a real AA failure.
 */
function es_ink_on( $clave, array $t, array $receta ) {
	$fondo_clave = $receta[0];
	$fondo       = $t[ $fondo_clave ];
	$mejor       = '';
	$ratio       = 0.0;
	$medido      = array();
	for ( $i = 1, $n = count( $receta ); $i < $n; $i++ ) {
		$tinta_clave = $receta[ $i ];
		$r           = es_contrast( $t[ $tinta_clave ], $fondo );
		if ( 0.0 === $r ) {
			/* es_contrast() already named the value it could not read. */
			return '';
		}
		$medido[] = '"' . $tinta_clave . '" (' . $t[ $tinta_clave ] . ') ' . number_format( $r, 2 ) . ':1';
		if ( $r > $ratio ) {
			$mejor = $t[ $tinta_clave ];
			$ratio = $r;
		}
	}
	if ( $ratio < 4.5 ) {
		es_warn(
			'ninguna tinta se lee sobre "' . $fondo_clave . '" (' . $fondo . '): ' . implode( ' y ', $medido )
			. '. WCAG AA pide 4.5:1 al texto normal, asi que "' . $clave . '" se pinta con la mejor de las dos (' . $mejor
			. ', ' . number_format( $ratio, 2 ) . ':1) y AUN ASI no cumple. Mueve el acento o fija "' . $clave . '" a mano.'
		);
	}
	return $mejor;
}

/**
 * The hover of a colour: the same colour moved AWAY from the page it sits on.
 *
 * es_shade() always darkens, which lowers contrast on a dark ground (the hover recedes and reads as
 * disabled). So the DIRECTION is derived and the magnitude stays the fitted factor: both candidates
 * are built (shade toward black, tint toward white, the sRGB cube's own ends; not `bg`, since mixing
 * toward the page moves a colour toward the page) and the one further from the page wins. It is
 * measured, not branched on a luminance threshold, since a client's ground is whatever their brand is.
 * A plain mix toward `text` cannot replace the shade: the 0.815 factor reproduces `#0C8A55` exactly
 * because multiplying channels is interpolation toward pure black.
 *
 * TIES go to the darkened candidate, which keeps the framework's own `#0C8A55` byte-identical and
 * resolves a ground with no headroom the same way on every machine.
 */
function es_hover_of( $hex, $factor, $fondo ) {
	/* Read ONCE, here, with the reader that stays quiet on purpose. Building
	   both candidates first would put the same unreadable value through
	   es_shade() AND es_mix() and warn twice about one typo -- the exact
	   double-fire es_lum() returns null to avoid. */
	if ( null === es_lum( $hex ) ) {
		es_warn( 'es_hover_of() no sabe leer "' . $hex . '" como color hex, asi que el estado hover que lo usa se queda SIN pintar. Escribe el token como #RGB o #RRGGBB.' );
		return '';
	}
	$oscuro = es_shade( $hex, $factor );
	/* The mirror of es_shade(): it multiplies the channel, this multiplies the
	   headroom left above it, so the two move the same 18.5% in opposite
	   directions. Written as a mix rather than as a fifth hex parser -- es_lum()'s
	   docblock already names four copies of that parse as a standing finding. */
	$claro = es_mix( $hex, '#FFFFFF', 1 - $factor );
	if ( null === es_lum( $fondo ) ) {
		/* The ground itself is unreadable. es_token_mixes() runs BEFORE this pass
		   and reads the same `bg`, so es_mix() has already warned naming the value;
		   a second warning would name it twice. Fall back to the old unconditional
		   darkening rather than to nothing: half the table is still right. */
		return $oscuro;
	}
	return es_contrast( $claro, $fondo ) > es_contrast( $oscuro, $fondo ) ? $claro : $oscuro;
}

/**
 * Tokens that are the readable ink ON another token: array( surface, candidate... ).
 *
 * Same table shape as es_token_mixes() / es_token_hovers() / es_token_recipes(): the key list, the
 * derivation and the unknown-key guard read ONE table. `text` is listed before `bg` so a tie goes to
 * the ink. `on_inverse` is deliberately not here: it is `bg` on `surface_inverse` (= `text`), so it
 * already is this rule's answer and routing it through would add a measurement with a fixed result.
 */
function es_token_contrasts() {
	return array(
		'on_accent' => array( 'accent', 'text', 'bg' ),
	);
}

/**
 * Tokens that are one ground token blended toward another: array( from, to, fraction ).
 *
 * The fractions are MEASURED off the values this file originally shipped (where each hand-picked
 * colour sat between `text` and `bg` on the `paper` ground), so the default look survives within a
 * unit or two per channel while every other ground gets neutrals of its own.
 *
 * The two at 0.00 are the point: `surface_inverse` = `text` (one role: near-black type and inverted
 * dark surfaces) and `on_inverse` = `bg`. They are mixes, not aliases, so there is one mechanism and
 * a brand can move one number to put its footer a shade off its ink.
 *
 * `muted` is 36.6%, not design-tokens.md's "55-60%": at 57% on `paper` it lands on #9A9C9D, 2.76:1, an
 * AA failure for the body copy es_p() paints with it. `bg_alt` is an axis INPUT the operator sets and
 * is never derived here, though it is a mix TARGET for `muted` (the alternating band paints it).
 */
function es_token_mixes() {
	return array(
		'surface_inverse' => array( 'text', 'bg', 0.00 ),
		'on_inverse'      => array( 'bg', 'text', 0.00 ),
		'text_soft'       => array( 'text', 'bg', 0.230 ),
		/* Hacia `bg_alt`, no hacia `bg`. La banda alterna esta siempre mas cerca del texto que el
		   fondo base, asi que es la superficie DURA, y un muted que pasa AA sobre ella pasa tambien
		   sobre `bg`. Medido contra `bg` fallaban cuatro de los once grounds del catalogo en sus
		   secciones alternas -- warm 4.35:1, b-alinea 4.41:1, b-aranda 4.47:1, b-bergara 4.49:1 --
		   con todas las filas verdes, porque nada medía contra la superficie que se estaba pintando. */
		'muted'           => array( 'text', 'bg_alt', 0.366 ),
		'border'          => array( 'text', 'bg', 0.890 ),
		/* The two extra hairlines the siblings brought with them. Derived so they follow the ground
		   like everything else; still three keys for one job, which remains the standing finding
		   this block records rather than a design anybody chose. */
		'border_soft'     => array( 'text', 'bg', 0.912 ),
		'border_softer'   => array( 'text', 'bg', 0.929 ),
	);
}

/**
 * Tokens that are another token's colour moved away from the page: the HOVER states.
 *
 * array( source token, factor ), same table shape as es_token_recipes(). Named for the ROLE, not the
 * mechanism (a hover may lighten or darken). The factors differ on purpose: pressing a button must be
 * FELT (~18%), a hairline nudging on hover is a hint (~6.5%).
 */
function es_token_hovers() {
	return array(
		/* 0.815 is not a fitted curiosity: it is the factor at which the
		   framework's own accent reproduces its hand-picked hover #0C8A55
		   exactly, so making this derived costs zero emitted bytes on the
		   default brand while every other brand finally gets a hover of its
		   OWN colour. It is also why the DISTANCE could not become a blend
		   toward the ground the way the neutrals did -- es_hover_of()'s
		   docblock has that arithmetic. */
		'accent_hover' => array( 'accent', 0.815 ),
		'border_hover' => array( 'border', 0.935 ),
	);
}

/**
 * Tokens that are another token's colour with a veil over it.
 *
 * array( prefix, source token, alpha ). Kept as data rather than inline so the
 * key list, the derivation and the unknown-key guard all read the SAME table
 * and cannot drift apart.
 *
 * Only the colour is derived; the geometry (`0 18px 40px -12px `) stays a
 * literal because it is a distance, not a colour, and distances belong to the
 * density axis that Task 2 owns.
 */
function es_token_recipes() {
	return array(
		'muted_on_inverse'  => array( '', 'on_inverse', '0.75' ),
		'border_on_inverse' => array( '', 'on_inverse', '0.5' ),
		'accent_wash'       => array( '', 'accent', '0.10' ),
		'scrim_from'        => array( '', 'surface_inverse', '0.92' ),
		'scrim_to'          => array( '', 'surface_inverse', '0.30' ),
		/* ONE neutral lift and ONE accent glow. Both existed twice, and neither
		   pair had a stated reason to differ -- `elev_hover_panel` was the same
		   shadow 6px further out at 0.20, `elev_accent_cart` the same glow 2px
		   tighter at 0.5. Where two values disagreed, the survivor is the one
		   design-system.md actually documents: `0 18px 40px -12px rgba(21,24,26,.16)`
		   is verbatim its `soft-shadow` --elev-hover. The other two were nobody's
		   decision, which is why nothing was keeping them in step. */
		'elev_hover'        => array( '0 18px 40px -12px ', 'text', '0.16' ),
		'elev_accent'       => array( '0 12px 26px -10px ', 'accent', '0.55' ),
	);
}

/** Was this token set by the build (an override), rather than left at the library default? */
function es_token_explicit( $key ) {
	return isset( $GLOBALS['es_token_explicit'] ) && in_array( $key, $GLOBALS['es_token_explicit'], true );
}

/* ---------------------------------------------------------- design tokens
   This block IS the "override es_tokens() -- the one edit point" that elementor-core/SKILL.md step 2
   names: ONE edit point per project, filled from the axis positions the ux-design-system dialogue
   resolved. The values below are the framework default, not a recommendation for any client.

   No colour, family, shadow, easing curve, font size or spacing value between here and the END marker
   below is typed by hand; every duration names es_t('ease'). RT_BUILDER_HARDCODED_TOKEN enforces that
   region mechanically (the lines between es_tokens()'s closing brace and the END marker, PHP comments
   stripped), naming each literal as file:line -> value. A literal that merely EQUALS the token it
   replaced is the golden dump's job: the golden catches a value that moves, the row catches a value
   that stopped being addressable.

   Keys are named for the ROLE the value plays, never for what it looks like (`muted`, not `grey`;
   `surface_inverse`, not `surface_dark`), so a navy brand or a cream inverse surface needs no rename.
   One key per job: two keys for one job are two things to change together. */
function es_tokens( array $override = array(), $reset = false ) {
	static $t = null;
	if ( $reset ) {
		$t = null;
	}
	if ( null === $t || $override ) {
		/* WHICH keys the caller set, as opposed to the library defaults: a setting that lives in a
		   shared place (the kit) is only written when the build asked for it. */
		$GLOBALS['es_token_explicit'] = array_keys( $override );
		$base = array(
			/* ground ------------------------------------------------ */
			'bg'                 => '#FFFFFF', /* the light surface a card sits on */
			/* The quiet band that separates one section from the next without
			   flipping the page over. es-builder.php never needed it -- it has
			   no banded sections -- but the shop archive, the product tabs and
			   the mobile dropdown all reach for the same value, so it belongs
			   in the one edit point rather than three times in three files.
			   design-system.md's ground table pins this per position, and the value
			   here is its `paper` row verbatim. It used to read #F4F5F3, which is on
			   NO row of that table -- an invented fourth ground half a step off the
			   documented one, in a file whose own plan says never to invent a number.
			   tests/test-write-path.php now reads the three documented cells out of
			   design-system.md and compares them against these three defaults, so the
			   next drift is a red row instead of a colour nobody re-checked. */
			'bg_alt'             => '#F6F7F8',
			'surface_inverse'    => null, /* derived: the ground's own `text` -- the surface that flips the page over */
			'transparent'        => 'rgba(0,0,0,0)', /* an explicit no-fill; Elementor needs the value, not an absent key */
			/* ink --------------------------------------------------- */
			'text'               => '#15181A',
			/* Running prose, one step off the title ink. A real third role and
			   not drift: `text` paints titles, prices and UI labels, `muted`
			   paints chrome (a breadcrumb, pagination, an inactive tab), and
			   this paints the paragraphs somebody actually READS -- the product
			   short description, the trust list, the tab body. Folding it into
			   either neighbour would shout the body copy or demote it to
			   furniture. */
			'text_soft'          => null, /* derived: text 23% toward bg */
			'muted'              => null, /* derived: text 36.6% toward bg */
			'on_accent'          => null, /* derived: whichever of `text`/`bg` reads better ON the accent */
			'on_inverse'         => null, /* derived: the ground's own bg -- ink ON surface_inverse or on the CTA scrim */
			'muted_on_inverse'   => null, /* derived: on_inverse at 0.75 */
			/* borders ----------------------------------------------- */
			/* ONE hairline and its hover, DERIVED from the ground (blended off the ink) so it stays a hairline
			   (1.15-1.31:1) on every ground instead of a near-white slash across a dark page. It is asserted as a
			   RANGE, not against WCAG 1.4.11's 3:1, which is for controls and which this divider has never met.
			   The outline BUTTON's edge reads this same token and IS a control at ~1.25:1: a real 1.4.11 gap,
			   reported and still open. */
			'border'             => null, /* derived: text 89% toward bg */
			'border_hover'       => null, /* derived: border moved 6.5% away from the page */
			/* Two MORE hairlines, arriving from the sibling assets, and named
			   honestly as what they are: the same drift this block already
			   collapsed once, living in three files nobody was comparing. They
			   are 5 and 9 units off `border` on every channel (#E5E7E5 ->
			   #EAECEA -> #EEF0EE, measured, not eyeballed) and no decision
			   anywhere says why. Derived now, so they at least follow the ground
			   instead of staying three white-page constants, but three keys for
			   one job is STILL THE FINDING and collapsing them is still a
			   deliberate visual decision somebody has to make and look at. */
			'border_soft'        => null, /* derived: text 91.2% toward bg */
			'border_softer'      => null, /* derived: text 92.9% toward bg */
			'border_on_inverse'  => null, /* derived: on_inverse at 0.5 */
			/* accent -- derives from the BRAND, never from the anchor.
			   design-tokens.md is explicit that accent is not an axis. */
			'accent'             => '#0FA968',
			'accent_hover'       => null, /* derived: accent moved 18.5% away from the page */
			'accent_wash'        => null, /* derived: accent at 0.10 -- the faint tint an outline control fills with */
			/* scrim over the CTA banner photo, so the copy stays legible */
			'scrim_from'         => null, /* derived: surface_inverse at 0.92 */
			'scrim_to'           => null, /* derived: surface_inverse at 0.30 */
			/* scale ------------------------------------------------- */
			'font_head'          => 'Space Grotesk',
			'font_body'          => 'Manrope',
			/* Elementor writes ONE generic fallback per SITE (kit `default_generic_fonts`) after every family,
			   so it cannot follow a serif head and a sans body at once. The default is Elementor's own, so an
			   untouched build is unchanged; set it to the generic of the face that carries most of the text. */
			'font_fallback'      => 'Sans-serif',
			/* The four numbers that ARE the scale axis, at the `classic`
			   position from design-system.md's "Perceptual axes" table. Every
			   heading and body size in this file is es_fs(step) off these; no
			   size is typed. */
			'fs_base'            => 16,
			'type_ratio'         => 1.333,
			'display_lh'         => 1.10,
			'fs_h1_max'          => 64,
			/* The one size deliberately OFF the ratio. design-system.md pins
			   --fs-small at a flat 0.875rem rather than deriving it, because
			   card meta text is not a step in the heading hierarchy: it is the
			   floor under body, and a ratio that pushes headings apart must not
			   also push meta text into unreadability. Nearest-step would have
			   sent it to 16 -- the same size as body -- erasing the distinction
			   it exists to make. An honest exception, not a bent derivation.
			   It does NOT track fs_base; a project that moves the base should
			   move this too. */
			'fs_small'           => 14,
			/* Same reasoning, and design-system.md pins it the same way
			   (--fs-eyebrow: 0.75rem, flat). This one was ON the scale for a
			   while at step -1, and the render numbers showed why that is
			   wrong: a step BELOW body shrinks as the ratio grows, so the
			   monumental position -- the one meant to make headings shout --
			   was quietly taking this uppercase, letter-spaced label down to
			   9.9px. The scale axis pushes the hierarchy APART; it must not
			   push the small end into unreadability. */
			'fs_eyebrow'         => 12,
			/* density ----------------------------------------------- */
			/* `standard`. Applied inside es_box() and the three gap defaults,
			   so one multiplier moves the whole rhythm. */
			'sp_scale'           => 1.0,
			/* The kit's gap between widgets, in px. 20 is Elementor's own factory value, so a build that
			   does not set it renders as before; a maqueta with another rhythm sets it here. */
			'sp_widget'          => 20,
			/* elevation --------------------------------------------- */
			/* The rest state, which did not exist at all before: this file had
			   five :hover shadows and nothing underneath them, which is why
			   `hairline` and `soft-shadow` could not be expressed. `none` is
			   the `none` position from design-system.md and renders exactly as
			   the absence it replaces. Swap it for `0 0 0 1px #E5E7E5`
			   (hairline) or `0 1px 2px rgba(0,0,0,.04)` (soft-shadow) and every
			   card recipe picks it up. */
			'elev_rest'          => 'none',
			'elev_hover'         => null, /* derived: text at 0.16 */
			'elev_accent'        => null, /* derived: accent at 0.55 */
			/* motion ------------------------------------------------ */
			'ease'               => 'cubic-bezier(.22,1,.36,1)',
		);

		/* A mistyped override key used to be accepted in silence: es_tokens()
		   merged `acento` in, nothing read it, the accent stayed green and no
		   channel said a word. es_t()'s guard only catches typos on the READ
		   side, and this is the one edit point the whole token layer exists to
		   create -- a typo here is the likeliest operator error there is. */
		foreach ( $override as $clave => $ignorado ) {
			if ( ! array_key_exists( $clave, $base ) ) {
				es_warn( 'la clave "' . $clave . '" no es un token: el override se ha aceptado y NO cambia nada. Revisa el nombre contra la lista de es_tokens.' );
			}
		}

		$t = array_merge( $base, $override );
		/* Three passes, and the ORDER is the dependency chain, not a style.
		   Mixes read the two ground literals and produce hexes; shades read a hex
		   and produce a hex; veils read a hex and produce an rgba(). So `border`
		   has to exist before `border_hover` can darken it, and `surface_inverse`
		   before `scrim_from` can veil it. Run the veils first and every ground
		   derivative silently reads null. */
		foreach ( es_token_mixes() as $clave => $receta ) {
			/* Same escape hatch as the two passes below: a brand whose hairline is
			   not a blend of its own ink must be able to say so. */
			if ( ! array_key_exists( $clave, $override ) ) {
				$t[ $clave ] = es_mix( $t[ $receta[0] ], $t[ $receta[1] ], $receta[2] );
			}
		}
		/* Hovers run BEFORE the veils: a hover produces a hex, a veil consumes
		   one, so this order is what lets a future glow be built on a hover.
		   They run AFTER the mixes for a second reason now: es_hover_of() reads
		   `bg` to decide which way is away from the page, and on a brand that
		   overrode `bg` the mixes are what settled the rest of the ground. */
		foreach ( es_token_hovers() as $clave => $receta ) {
			/* Same escape hatch as the veils below: a brand whose hover is not
			   its own accent moved away from the page must be able to say so. */
			if ( ! array_key_exists( $clave, $override ) ) {
				$t[ $clave ] = es_hover_of( $t[ $receta[0] ], $receta[1], $t['bg'] );
			}
		}
		/* Contrasts run AFTER the mixes and the shades and BEFORE the veils, and
		   both halves of that are dependency, not style. After: an ink chosen by
		   measuring a surface must be able to measure a DERIVED surface, and
		   `surface_inverse` only exists once the mixes have run. Before: a veil
		   produces an `rgba(...)` string, and es_lum() cannot read one -- run the
		   veils first and every future on-colour over a veiled surface silently
		   returns ''. */
		foreach ( es_token_contrasts() as $clave => $receta ) {
			/* Same escape hatch as the other three passes: a brand whose label on
			   the accent is neither of its ground extremes -- a cream on a navy,
			   say -- must be able to say so, and it must still win here. */
			if ( ! array_key_exists( $clave, $override ) ) {
				$t[ $clave ] = es_ink_on( $clave, $t, $receta );
			}
		}
		foreach ( es_token_recipes() as $clave => $receta ) {
			/* An explicit override of a derived key still wins: a brand whose
			   glow is not its accent must be able to say so. */
			if ( ! array_key_exists( $clave, $override ) ) {
				$t[ $clave ] = $receta[0] . es_rgba( $t[ $receta[1] ], $receta[2] );
			}
		}
		foreach ( $t as $clave => $valor ) {
			if ( null === $valor ) {
				es_warn( 'el token "' . $clave . '" quedo sin valor: hay un hueco declarado arriba sin receta en es_token_recipes(), o al reves.' );
			}
		}
	}
	return $t;
}

function es_t( $key ) {
	$t = es_tokens();
	if ( ! array_key_exists( $key, $t ) ) {
		es_warn( 'es_t("' . $key . '") no existe en es_tokens(); revisa el nombre.' );
		return '';
	}
	return $t[ $key ];
}

/**
 * One step on the type scale, in px.
 *
 * Step 0 is body; each step up multiplies by the ratio, each step down divides.
 * This is what the scale axis IS -- ONE number moves the whole hierarchy,
 * instead of twelve sizes that happen to look related and drift apart the first
 * time someone nudges one of them. (They had: two card titles declared with the
 * same `title_size => h3` were shipping at 19px and 17px.)
 *
 * Capped at fs_h1_max so a monumental ratio cannot run a heading off a phone.
 * At the `classic` position the cap does not engage until step 5, and this file
 * emits nothing above step 3 -- so today it is insurance, not a live value, and
 * the suite proves it engages rather than assuming it.
 */
function es_fs( $step ) {
	$t  = es_tokens();
	$px = $t['fs_base'] * pow( $t['type_ratio'], $step );
	return round( min( $px, $t['fs_h1_max'] ), 1 );
}

/**
 * One step of the type scale, in px, RESOLVED AT A VIEWPORT WIDTH.
 *
 * es_fs() returns the step's FLOOR, right for body and wrong for a heading: design-system.md gives a
 * heading a clamp() whose floor is `fs_base x ratio^n`, whose cap is `fs_h1_max / ratio^(3-n)`, and
 * whose preferred term interpolates that step's floor into its cap between 430px and 1280px.
 * Elementor cannot emit clamp(), so the same formula is RESOLVED at each breakpoint here (a build
 * sized off the floor misses the approved h1 by ~40%). 430 / 850 are design-system.md's `--fluid`
 * endpoints verbatim. Clamp order is CSS's, `max(floor, min(px, cap))`: a cap below the floor keeps
 * the floor.
 *
 * tests/test-write-path.php checks this against design-system.md's measured table, not against
 * itself: 54.00 / 67.52 / 88.00 for editorial h1 and 67.77 / 88.54 / 120.00 for monumental.
 */
function es_fs_at( $step, $vw ) {
	$t     = es_tokens();
	$floor = $t['fs_base'] * pow( $t['type_ratio'], $step );
	$cap   = $t['fs_h1_max'] / pow( $t['type_ratio'], 3 - $step );
	$f     = max( 0.0, min( 1.0, ( $vw - 430 ) / 850 ) );
	return round( max( $floor, min( $floor + ( $cap - $floor ) * $f, $cap ) ), 1 );
}

/**
 * The three widths es_h() resolves the scale at, one per Elementor breakpoint.
 *
 * They are not midpoints or guesses: each is a column of design-system.md's own measured table.
 * 1280 is where every cap engages ("a laptop — which is the entire point"), 768 is the tablet
 * band's floor, and 430 is the fluid range's floor, below which the clamp holds its own floor.
 * Taking the FLOOR of each band rather than its middle is deliberate: a fixed size that is right at
 * the narrow end of a band and a little small at the wide end never overflows, and the failure mode
 * this file cares about is a heading running off a phone.
 */
function es_h_widths() {
	return array( '' => 1280, '_tablet' => 768, '_mobile' => 430 );
}

/**
 * What each heading tag is, on the scale.
 *
 * array( step, line-height, weight ). design-system.md's typography table defines exactly three
 * heading steps — h1 = step 3, h2 = step 2, h3 = step 1 — and pins h1/h2 to `--display-lh`, which
 * IS the scale axis, while h3 keeps a flat 1.25 and body a flat 1.6 because neither is an axis.
 * The `null` leading means "read display_lh"; 1.25 is design-system.md's literal and is declared as
 * a known non-axis residual at the END marker below, with the four body leadings it keeps company.
 *
 * h4/h5/h6 are absent because design-system.md defines no step for them. Emitting step 0 would make
 * a heading the same size as body on a guess, so es_h() emits nothing for them and SAYS SO —— see
 * its warning. Zero call sites in the tree use one today.
 */
function es_h_scale() {
	return array(
		'h1' => array( 3, null, '700' ),
		'h2' => array( 2, null, '700' ),
		'h3' => array( 1, 1.25, '600' ),
	);
}

/**
 * One length on the density axis, in px.
 *
 * Every spacing value in the file goes through here -- via es_box() and the
 * three gap defaults -- so density is one multiplier over the whole rhythm
 * rather than 29 numbers to re-tune by hand and 29 chances to miss one.
 */
function es_sp( $px ) {
	return (int) round( $px * es_tokens()['sp_scale'] );
}

/**
 * Full-width section wrapper with boxed inner content.
 */
function es_section( array $children, array $opts = array() ) {
	$settings = array(
		'content_width'    => 'boxed',
		'flex_direction'   => 'column',
		'flex_gap'         => array( 'unit' => 'px', 'size' => 0, 'column' => '0', 'row' => '0' ),
		'padding'          => es_box( 88, 24, 88, 24 ),
		'padding_tablet'   => es_box( 72, 24, 72, 24 ),
		'padding_mobile'   => es_box( 56, 20, 56, 20 ),
	);
	if ( ! empty( $opts['bg'] ) ) {
		$settings['background_background'] = 'classic';
		$settings['background_color']      = $opts['bg'];
	}
	if ( ! empty( $opts['settings'] ) ) {
		$settings = array_merge( $settings, $opts['settings'] );
	}
	return es_c( $settings, $children );
}

/**
 * Two-column section — THE SECTION IS THE ROW.
 *
 * Instead of `es_section( es_row( array( $left, $right ) ) )` (a whole container level whose only job
 * is "be a flex row"), the section is the row: `flex_direction:row` on the section, `column` at
 * tablet/mobile, the two halves as DIRECT children.
 *
 * A boxed container puts its flex on the generated `.e-con-inner`; the NATIVE flex controls target it
 * correctly. Only hand-written `custom_css` has to say `selector>.e-con-inner` (references/gotchas.md).
 *
 * $opts: bg, gap, align (flex_align_items), reverse (stack mobile in reverse), settings.
 */
function es_split( array $children, array $opts = array() ) {
	/* The three flex gaps are the only spacing that does not travel through
	   es_box(), so density has to be applied to them by hand -- here, at the
	   default, and not at any call site. */
	$gap      = es_sp( isset( $opts['gap'] ) ? (int) $opts['gap'] : 48 );
	$settings = array(
		'content_width'         => 'boxed',
		'flex_direction'        => 'row',
		'flex_direction_tablet' => empty( $opts['reverse'] ) ? 'column' : 'column-reverse',
		'flex_direction_mobile' => empty( $opts['reverse'] ) ? 'column' : 'column-reverse',
		'flex_align_items'      => isset( $opts['align'] ) ? $opts['align'] : 'center',
		'flex_gap'              => array( 'unit' => 'px', 'size' => $gap, 'column' => (string) $gap, 'row' => (string) $gap ),
		'padding'               => es_box( 88, 24, 88, 24 ),
		'padding_tablet'        => es_box( 72, 24, 72, 24 ),
		'padding_mobile'        => es_box( 56, 20, 56, 20 ),
	);
	if ( ! empty( $opts['bg'] ) ) {
		$settings['background_background'] = 'classic';
		$settings['background_color']      = $opts['bg'];
	}
	if ( ! empty( $opts['settings'] ) ) {
		$settings = array_merge( $settings, $opts['settings'] );
	}
	return es_c( $settings, $children );
}

/**
 * Give ONE element a width without wrapping it in a container.
 *
 * A width is not a layout. Wrapping a widget in a container just to make it 58% wide buys a
 * <div>, a CSS block and an editor level for something the element itself can carry:
 * `_element_width:'initial'` unlocks `_element_custom_width`. Works on widgets and on
 * containers alike — both read the same `_element_*` keys.
 *
 * Defaults to full width at mobile, which is what you want ~always; pass $mobile to override.
 */
function es_wide( array $el, $pct, $mobile = 100, $unit = '%' ) {
	$el['settings']['_element_width']        = 'initial';
	$el['settings']['_element_custom_width'] = es_size( $pct, $unit );
	if ( null !== $mobile ) {
		$el['settings']['_element_width_mobile']        = 'initial';
		$el['settings']['_element_custom_width_mobile'] = es_size( $mobile, '%' );
	}
	return $el;
}

/**
 * A photo is a WIDGET, not a container background.
 *
 * `background_image` on a container costs twice: it needs a container that exists only to hold
 * the picture (usually an EMPTY one, which the audit flags), and the image ships with no `alt`,
 * so it is invisible to screen readers and to Google Images. The native image widget with a
 * fixed height + `object-fit:cover` crops identically AND keeps the alt text.
 *
 * Control keys confirmed on the de la O build. If a future Elementor renames them, introspect
 * (`references/gotchas.md` -> "Verify widget/control names") rather than guessing.
 * `object-fit` is hyphenated on purpose — that IS the control id — and Elementor only honours
 * it while `height` has a value.
 */
function es_photo( $img_slug, $height = 420, array $extra = array() ) {
	$settings = array(
		'image'          => es_img( $img_slug ),
		'image_size'     => 'large',
		'width'          => es_size( 100, '%' ),
		'height'         => es_size( $height ),
		'height_mobile'  => es_size( (int) round( $height * 0.72 ) ),
		'object-fit'     => 'cover',
		'object-position' => 'center center',
	);
	$settings = array_merge( $settings, $extra );
	return es_w( 'image', $settings );
}

/**
 * The one marker every helper puts around the CSS it emits. `qa-review` row 37 counts only
 * hand-written `custom_css`; `es_custom_css_audit()` tells the two apart by this pair. A comment
 * is valid CSS and carries no `selector`, so Elementor's replacement is untouched.
 */
const ES_HELPER_CSS_OPEN  = '/* es:helper */';
const ES_HELPER_CSS_CLOSE = '/* /es:helper */';

/** Wrap helper-emitted CSS in the marker. Empty CSS stays empty: nothing to mark. */
function es_helper_css( $css ) {
	return '' === trim( (string) $css ) ? '' : ES_HELPER_CSS_OPEN . $css . ES_HELPER_CSS_CLOSE;
}

/**
 * Card hover, applied through the container's native Custom CSS field so it
 * does not depend on Elementor's conditionally-enqueued animation assets
 * (which are not registered when the layout is written via the API).
 */
function es_card_hover_css() {
	$ease = es_t( 'ease' );
	return es_helper_css( 'selector .elementor-widget-image-box{transition:transform .5s ' . $ease . ',box-shadow .5s ' . $ease . ',border-color .5s ' . $ease . ';box-shadow:' . es_t( 'elev_rest' ) . ';will-change:transform;}'
		. 'selector .elementor-widget-image-box:hover{transform:translateY(-4px);border-color:' . es_t( 'border_hover' ) . ';box-shadow:' . es_t( 'elev_hover' ) . ';}'
		. 'selector .elementor-widget-image-box .elementor-image-box-img{overflow:hidden;}'
		. 'selector .elementor-widget-image-box .elementor-image-box-img img{transition:transform .7s ' . $ease . ';will-change:transform;}'
		. 'selector .elementor-widget-image-box:hover .elementor-image-box-img img{transform:scale(1.045);}'
		. 'selector .elementor-widget-image-box .elementor-image-box-title{transition:color .4s ' . $ease . ';}'
		. 'selector .elementor-widget-image-box:hover .elementor-image-box-title{color:' . es_t( 'accent' ) . ';}' );
}

/**
 * Shared CSS for any native WooCommerce products grid (archive, related, home).
 * Smooth hover lift + image zoom, green add-to-cart with hover, equal-height
 * cards (button pinned to the bottom), the redundant inline "Ver carrito" link
 * hidden, and the added-state button relabelled to "Añadido".
 *
 * This is the single source of truth for the products grid — every consumer must
 * call it instead of pasting a copy, because hand-copied duplicates drift (the
 * archive and related-products templates had already diverged from each other).
 * Grid-specific extras that genuinely belong to one template only (archive
 * pagination, for instance) ride in through `$extra_css` so the shared rules stay
 * shared and the difference stays visible at the call site.
 */
function es_products_css( $extra_css = '' ) {
	$ease = es_t( 'ease' );
	return es_helper_css( 'selector ul.products li.product{transition:transform .5s ' . $ease . ',box-shadow .5s ' . $ease . ';box-shadow:' . es_t( 'elev_rest' ) . ';border-radius:12px;overflow:hidden;padding:10px;will-change:transform;}'
		. 'selector ul.products li.product .woocommerce-loop-product__link img,selector ul.products li.product img{transition:transform .7s ' . $ease . ';border-radius:8px;will-change:transform;}'
		. 'selector ul.products li.product:hover{transform:translateY(-4px);box-shadow:' . es_t( 'elev_hover' ) . ';}'
		. 'selector ul.products li.product:hover img{transform:scale(1.045);}'
		. 'selector ul.products{align-items:stretch;}'
		. 'selector ul.products li.product{display:flex!important;flex-direction:column;height:100%;}'
		. 'selector ul.products li.product .button{margin-top:auto;background-color:' . es_t( 'accent' ) . '!important;border-color:' . es_t( 'accent' ) . '!important;color:' . es_t( 'on_accent' ) . '!important;border-radius:6px!important;transition:background-color .3s ' . $ease . ',box-shadow .35s ' . $ease . '!important;}'
		. 'selector ul.products li.product .button:hover{background-color:' . es_t( 'accent_hover' ) . '!important;box-shadow:' . es_t( 'elev_accent' ) . '!important;}'
		. 'selector ul.products li.product a.added_to_cart{display:none!important;}'
		. 'selector ul.products li.product a.button.added{font-size:0!important;}'
		. 'selector ul.products li.product a.button.added::after{content:"Añadido ✓"!important;font-size:13.5px!important;font-weight:600;}' )
		. $extra_css;
}

/**
 * Grid container - native Elementor grid, avoids nested column containers.
 *
 * grid_rows_grid defaults to 2fr, which paints an empty second row (and a big
 * gap) whenever a grid holds a single row of content. Forcing auto rows makes
 * the grid height follow its content.
 *
 * `$inner` is `isInner`: true (the default, as ever) for a grid INSIDE a section; pass false for a grid
 * that IS the section, a root-level boxed grid with `content_width => 'boxed'` in `$extra` (confirmed
 * on Elementor 4.2.4: it renders as one container, no wrapper).
 */
function es_grid( $cols, array $children, $gap = 24, array $extra = array(), $inner = true ) {
	$gap      = es_sp( $gap );   /* ver es_split(): la densidad se aplica aqui, no en la llamada */
	$settings = array(
		'container_type'          => 'grid',
		'content_width'           => 'full',
		'grid_columns_grid'       => array( 'unit' => 'fr', 'size' => $cols ),
		'grid_columns_grid_tablet' => array( 'unit' => 'fr', 'size' => min( 2, $cols ) ),
		'grid_columns_grid_mobile' => array( 'unit' => 'fr', 'size' => 1 ),
		'grid_rows_grid'          => array( 'unit' => 'custom', 'size' => 'auto' ),
		'grid_rows_grid_tablet'   => array( 'unit' => 'custom', 'size' => 'auto' ),
		'grid_rows_grid_mobile'   => array( 'unit' => 'custom', 'size' => 'auto' ),
		'grid_gap'                => array(
			'unit'   => 'px',
			'column' => (string) $gap,
			'row'    => (string) $gap,
			'isLinked' => true,
		),
		'_flex_grow'              => 0,
		'_flex_shrink'            => 0,
		'custom_css'              => es_card_hover_css(),
	);
	$settings = array_merge( $settings, $extra );
	return es_c( $settings, $children, (bool) $inner );
}

/**
 * Horizontal row (buttons, inline items).
 */
function es_row( array $children, $gap = 14, array $extra = array() ) {
	$gap      = es_sp( $gap );   /* ver es_split(): la densidad se aplica aqui, no en la llamada */
	$settings = array(
		'content_width'  => 'full',
		'flex_direction' => 'row',
		'flex_wrap'      => 'wrap',
		'flex_gap'       => array( 'unit' => 'px', 'size' => $gap, 'column' => (string) $gap, 'row' => (string) $gap ),
		'flex_align_items' => 'flex-start',
		'_flex_grow'     => 0,
		'_flex_shrink'   => 0,
	);
	$settings = array_merge( $settings, $extra );
	return es_c( $settings, $children, true );
}

/**
 * Small uppercase label above a heading, in the accent colour.
 *
 * $color defaults to null rather than to the accent itself because PHP cannot
 * call a function in a parameter default; null means "whatever the tokens say".
 */
function es_eyebrow( $text, $color = null ) {
	$color = ( null === $color ) ? es_t( 'accent' ) : $color;
	return es_w(
		'heading',
		array(
			'title'                      => $text,
			'header_size'                => 'div',
			'title_color'                => $color,
			'typography_typography'      => 'custom',
			'typography_font_family'     => es_t( 'font_body' ),
			'typography_font_size'       => es_size( es_t( 'fs_eyebrow' ) ),
			'typography_font_weight'     => '700',
			'typography_text_transform'  => 'uppercase',
			'typography_letter_spacing'  => es_size( 1.6 ),
			'_margin'                    => es_box( 0, 0, 14, 0 ),
		)
	);
}

/**
 * Section heading, ON the scale axis.
 *
 * Emits typography per breakpoint from the scale tokens (including `display_lh`), so a heading matches
 * the approved mockup instead of inheriting whatever size the active theme has.
 *
 * $extra STILL WINS, and WHOLE: a caller passing `typography_font_size` gets that size at every
 * breakpoint, not that size on desktop and a derived one on tablet (a card title would render larger
 * on tablet). es_feature_card() is such a caller.
 */
function es_h( $text, $tag = 'h2', array $extra = array() ) {
	$settings = array(
		'title'       => $text,
		'header_size' => $tag,
		'_margin'     => es_box( 0, 0, 16, 0 ),
	);

	$escala = es_h_scale();
	if ( isset( $escala[ $tag ] ) ) {
		list( $paso, $leading, $peso ) = $escala[ $tag ];
		$settings['typography_typography']  = 'custom';
		$settings['typography_font_family'] = es_t( 'font_head' );
		$settings['typography_font_weight'] = $peso;
		$settings['typography_line_height'] = es_size( null === $leading ? es_t( 'display_lh' ) : $leading, 'em' );
		foreach ( es_h_widths() as $sufijo => $ancho ) {
			$settings[ 'typography_font_size' . $sufijo ] = es_size( es_fs_at( $paso, $ancho ) );
		}
	} elseif ( preg_match( '/^h[1-6]$/', $tag ) ) {
		/* A heading tag the scale does not define. Loud rather than silent: the whole defect this
		   function is fixing was a heading with no size, and quietly shipping a second one under a
		   different tag would be the same bug wearing an h4. */
		es_warn( 'es_h() no sabe que tamano dar a <' . $tag . '>: design-system.md define la escala solo para h1, h2 y h3. Este titular va a heredar el tamano del TEMA. Usa h1/h2/h3, o pasa la tipografia entera en $extra.' );
	}

	$settings = array_merge( $settings, $extra );
	/* A caller that overrode the desktop size and nothing else would otherwise keep this function's
	   derived tablet/mobile sizes underneath it —— bigger than the desktop size it just set. */
	if ( isset( $extra['typography_font_size'] ) ) {
		foreach ( array_keys( es_h_widths() ) as $sufijo ) {
			if ( '' !== $sufijo && ! isset( $extra[ 'typography_font_size' . $sufijo ] ) ) {
				unset( $settings[ 'typography_font_size' . $sufijo ] );
			}
		}
	}
	return es_w( 'heading', $settings );
}

/** Body paragraph. */
function es_p( $html, array $extra = array() ) {
	$settings = array(
		'editor'                => '<p>' . $html . '</p>',
		'text_color'            => es_t( 'muted' ),
		'typography_typography' => 'custom',
		'typography_font_family' => es_t( 'font_body' ),
		'typography_font_size'  => es_size( es_fs( 0 ) ),
		'typography_line_height' => es_size( 1.65, 'em' ),
		'_margin'               => es_box( 0, 0, 0, 0 ),
	);
	$settings = array_merge( $settings, $extra );
	return es_w( 'text-editor', $settings );
}

/** Button. */
/**
 * Site-wide button system. Two families, one hover language:
 *   'primary'       -> solid green, lifts + green glow on hover.
 *   'outline'       -> ghost on light bg, fills faint green + turns green on hover.
 *   'outline-light' -> ghost on dark bg (heroes), fills white on hover.
 *   'dark'          -> solid near-black (legacy, kept for dark CTAs).
 * NOTE: the Button widget hover keys are button_background_hover_color and
 * button_hover_border_color (NOT background_hover_color). Using the wrong key is
 * why hovers silently did nothing. Transitions + lift ride on native custom_css
 * so they never depend on conditionally-enqueued hover assets.
 */
function es_btn( $text, $link, $style = 'primary', array $extra = array() ) {
	$ease  = es_t( 'ease' );
	$trans = 'selector .elementor-button{transition:background-color .3s ' . $ease . ',color .3s ' . $ease . ',border-color .3s ' . $ease . ',box-shadow .35s ' . $ease . ',transform .35s ' . $ease . ';}';
	$lift_green = es_helper_css( $trans . 'selector .elementor-button:hover{transform:translateY(-2px);box-shadow:' . es_t( 'elev_accent' ) . ';}' );
	$lift_soft  = es_helper_css( $trans . 'selector .elementor-button:hover{transform:translateY(-2px);}' );

	$settings = array(
		'text'                   => $text,
		'link'                   => array( 'url' => $link, 'is_external' => '', 'nofollow' => '' ),
		'border_radius'          => es_box_unscaled( 8, 8, 8, 8 ),
		'text_padding'           => es_box( 14, 26, 14, 26 ),
		'typography_typography'  => 'custom',
		'typography_font_family' => es_t( 'font_body' ),
		'typography_font_size'   => es_size( es_fs( 0 ) ),
		'typography_font_weight' => '600',
	);
	if ( 'primary' === $style ) {
		$settings['background_color']              = es_t( 'accent' );
		$settings['button_text_color']            = es_t( 'on_accent' );
		$settings['button_background_hover_color'] = es_t( 'accent_hover' );
		$settings['hover_color']                  = es_t( 'on_accent' );
		$settings['custom_css']                   = $lift_green;
	} elseif ( 'dark' === $style ) {
		$settings['background_color']              = es_t( 'surface_inverse' );
		$settings['button_text_color']            = es_t( 'on_inverse' );
		$settings['button_background_hover_color'] = es_t( 'accent' );
		$settings['hover_color']                  = es_t( 'on_accent' );
		$settings['custom_css']                   = $lift_soft;
	} elseif ( 'outline' === $style ) {
		$settings['background_color']              = es_t( 'transparent' );
		$settings['button_text_color']            = es_t( 'text' );
		$settings['border_border']                = 'solid';
		$settings['border_width']                 = es_box_unscaled( 1, 1, 1, 1 );
		$settings['border_color']                 = es_t( 'border' );
		$settings['button_background_hover_color'] = es_t( 'accent_wash' );
		$settings['hover_color']                  = es_t( 'accent' );
		$settings['button_hover_border_color']    = es_t( 'accent' );
		$settings['custom_css']                   = $lift_soft;
	} elseif ( 'outline-light' === $style ) {
		$settings['background_color']              = es_t( 'transparent' );
		$settings['button_text_color']            = es_t( 'on_inverse' );
		$settings['border_border']                = 'solid';
		$settings['border_width']                 = es_box_unscaled( 1, 1, 1, 1 );
		$settings['border_color']                 = es_t( 'border_on_inverse' );
		/* The fill is the SOLID version of the ink this button already lives
		   in: border_on_inverse is on_inverse at 0.5, so hovering to on_inverse
		   at 1.0 is the same ink turned up. It used to read es_t('bg') -- the
		   PAGE surface -- which was byte-identical only because both are
		   #FFFFFF today, and would have filled a cream-page client's ghost
		   button with cream on top of a near-black hero. */
		$settings['button_background_hover_color'] = es_t( 'on_inverse' );
		$settings['hover_color']                  = es_t( 'text' );
		$settings['button_hover_border_color']    = es_t( 'on_inverse' );
		$settings['custom_css']                   = $lift_soft;
	}
	$settings = array_merge( $settings, $extra );
	return es_w( 'button', $settings );
}

/**
 * Service / product card on the native image-box widget.
 * image_size is the width slider; thumbnail_size picks the WP file size.
 */
function es_card( $img_slug, $title, $text, $link = '', array $extra = array() ) {
	$settings = array(
		'image'                    => es_img( $img_slug ),
		'thumbnail_size'           => 'large',
		'image_size'               => es_size( 100, '%' ),
		'image_height'             => es_size( 190 ),
		'image_object_fit'         => 'cover',
		'image_border_radius'      => es_size( 8 ),
		'image_space'              => es_size( es_sp( 20 ) ),
		'title_text'               => $title,
		'description_text'         => $text,
		'position'                 => 'top',
		'text_align'               => 'left',
		'title_size'               => 'h3',
		'title_color'              => es_t( 'text' ),
		'description_color'        => es_t( 'muted' ),
		'title_typography_typography' => 'custom',
		'title_typography_font_family' => es_t( 'font_head' ),
		'title_typography_font_size' => es_size( es_fs( 1 ) ),
		'title_typography_font_weight' => '700',
		'description_typography_typography' => 'custom',
		'description_typography_font_family' => es_t( 'font_body' ),
		'description_typography_font_size' => es_size( es_t( 'fs_small' ) ),
		'description_typography_line_height' => es_size( 1.6, 'em' ),
		'title_bottom_space'       => es_size( es_sp( 8 ) ),
		'_padding'                 => es_box( 20, 20, 24, 20 ),
		'_background_background'   => 'classic',
		'_background_color'        => es_t( 'bg' ),
		'_border_border'           => 'solid',
		'_border_width'            => es_box_unscaled( 1, 1, 1, 1 ),
		'_border_color'            => es_t( 'border' ),
		'_border_radius'           => es_box_unscaled( 10, 10, 10, 10 ),
		/* Hover handled by the parent grid's Custom CSS (es_card_hover_css). */
	);
	if ( $link ) {
		$settings['link'] = array( 'url' => $link, 'is_external' => '', 'nofollow' => '' );
	}
	$settings = array_merge( $settings, $extra );
	return es_w( 'image-box', $settings );
}

/**
 * Rounded CTA banner: full-bleed photo, dark scrim, copy and button on the left.
 * Sits inside a normal section so it keeps the page's boxed width.
 */
function es_cta_banner( $img_slug, $title, $text, $btn_text, $btn_link, $bg = '' ) {
	return es_section(
		array(
			es_c(
				array(
					'content_width'         => 'full',
					'flex_direction'        => 'column',
					'flex_justify_content'  => 'center',
					'min_height'            => es_size( 400 ),
					'min_height_mobile'     => es_size( 340 ),
					'padding'               => es_box( 64, 64, 64, 64 ),
					'padding_mobile'        => es_box( 36, 28, 36, 28 ),
					'border_radius'         => es_box_unscaled( 14, 14, 14, 14 ),
					'overflow'              => 'hidden',
					'background_background' => 'classic',
					'background_image'      => es_img( $img_slug ),
					'background_position'   => 'center center',
					'background_size'       => 'cover',
					'background_overlay_background' => 'gradient',
					'background_overlay_color'   => es_t( 'scrim_from' ),
					'background_overlay_color_b' => es_t( 'scrim_to' ),
					'background_overlay_gradient_type'  => 'linear',
					'background_overlay_gradient_angle' => es_size( 90, 'deg' ),
					'background_overlay_color_stop'     => es_size( 10, '%' ),
					'background_overlay_color_b_stop'   => es_size( 95, '%' ),
				),
				array(
					es_c(
						array(
							'content_width'  => 'full',
							'flex_direction' => 'column',
							'width'          => es_size( 56, '%' ),
							'width_tablet'   => es_size( 100, '%' ),
						),
						array(
							es_h(
								$title,
								'h2',
								/* Everything this override used to carry —— the head family, the
								   weight, display_lh and a size per breakpoint —— es_h() now emits
								   for every h2 in the build. Keeping a copy here would be a second
								   place to remember, and it was already drifting: it sized an h2 at
								   the DISPLAY step (es_fs(3)) because es_h() gave it nothing, which
								   is the hand-picked exception the token layer exists to remove.
								   Only the ink is left, and it is genuinely local: this heading
								   sits on a scrim, not on the page. */
								array(
									'title_color' => es_t( 'on_inverse' ),
								)
							),
							es_p(
								$text,
								array(
									'text_color'             => es_t( 'muted_on_inverse' ),
									'typography_font_size'   => es_size( es_fs( 0 ) ),
									'typography_line_height' => es_size( 1.65, 'em' ),
									'_margin'                => es_box( 0, 0, 30, 0 ),
								)
							),
							es_btn( $btn_text, $btn_link, 'primary', array( '_element_width' => 'auto' ) ),
						),
						true
					),
				),
				true
			),
		),
		$bg ? array( 'bg' => $bg ) : array()
	);
}

/** Advantage row built on the native icon-box widget. */
function es_iconbox( $icon, $title, $text ) {
	return es_w(
		'icon-box',
		array(
			'selected_icon'   => array( 'value' => $icon, 'library' => 'fa-solid' ),
			'title_text'      => $title,
			'description_text' => $text,
			'position'        => 'inline-start',
			'text_align'      => 'left',
			'title_size'      => 'h3',
			'primary_color'   => es_t( 'accent' ),
			'icon_space'      => es_size( es_sp( 18 ) ),
			'icon_size'       => es_size( 20 ),
			'title_color'     => es_t( 'text' ),
			'description_color' => es_t( 'muted' ),
			'title_typography_typography' => 'custom',
			'title_typography_font_family' => es_t( 'font_head' ),
			'title_typography_font_size' => es_size( es_fs( 1 ) ),
			'title_typography_font_weight' => '700',
			'description_typography_typography' => 'custom',
			'description_typography_font_family' => es_t( 'font_body' ),
			'description_typography_font_size' => es_size( es_t( 'fs_small' ) ),
			'description_typography_line_height' => es_size( 1.55, 'em' ),
			'title_bottom_space' => es_size( es_sp( 5 ) ),
			'_padding'        => es_box( 22, 0, 22, 0 ),
			'_border_border'  => 'solid',
			'_border_width'   => es_box_unscaled( 1, 0, 0, 0 ),
			'_border_color'   => es_t( 'border' ),
		)
	);
}

/**
 * Premium feature card: white card, green circular icon chip, smooth hover
 * lift with a green top-accent reveal. Shared by home ventajas and inner pages
 * so the whole site keeps one card language. Meant to sit inside es_grid().
 */
function es_feature_card( $icon, $title, $text, array $extra = array() ) {
	$ease     = es_t( 'ease' );
	$defaults = array(
		'content_width'         => 'full',
		'flex_direction'        => 'column',
		'padding'               => es_box( 34, 30, 36, 30 ),
		'background_background'  => 'classic',
		'background_color'      => es_t( 'bg' ),
		'border_border'         => 'solid',
		'border_width'          => es_box_unscaled( 1, 1, 1, 1 ),
		'border_color'          => es_t( 'border' ),
		'border_radius'         => es_box_unscaled( 16, 16, 16, 16 ),
		'custom_css'            => es_helper_css( 'selector{position:relative;overflow:hidden;transition:transform .5s ' . $ease . ',box-shadow .5s ' . $ease . ',border-color .5s ' . $ease . ';box-shadow:' . es_t( 'elev_rest' ) . ';will-change:transform;}'
			. 'selector::before{content:"";position:absolute;top:0;left:0;right:0;height:3px;background:' . es_t( 'accent' ) . ';transform:scaleX(0);transform-origin:left;transition:transform .55s ' . $ease . ';}'
			. 'selector:hover{transform:translateY(-6px);box-shadow:' . es_t( 'elev_hover' ) . ';border-color:' . es_t( 'border_hover' ) . ';}'
			. 'selector:hover::before{transform:scaleX(1);}'
			. 'selector .es-feat-ico{transition:transform .5s ' . $ease . ';}'
			. 'selector:hover .es-feat-ico{transform:translateY(-3px);}' ),
	);
	return es_c(
		array_merge( $defaults, $extra ),
		array(
			es_w(
				'icon',
				array(
					'selected_icon'   => array( 'value' => $icon, 'library' => 'fa-solid' ),
					'view'            => 'stacked',
					'shape'           => 'circle',
					'primary_color'   => es_t( 'accent' ),
					'secondary_color' => es_t( 'on_accent' ),
					'size'            => es_size( 20 ),
					'_css_classes'    => 'es-feat-ico',
					'_margin'         => es_box( 0, 0, 22, 0 ),
				)
			),
			es_h( $title, 'h3', array( 'typography_typography' => 'custom', 'typography_font_family' => es_t( 'font_head' ), 'typography_font_size' => es_size( es_fs( 1 ) ), 'typography_font_weight' => '700', '_margin' => es_box( 0, 0, 9, 0 ) ) ),
			es_p( $text, array( 'typography_font_size' => es_size( es_t( 'fs_small' ) ), 'typography_line_height' => es_size( 1.58, 'em' ) ) ),
		),
		true
	);
}

/* -------------------------------------------------- end of the visual layer
   Everything below is the save pipeline, the container audit, the sandbox and the slug machinery. No
   styling value belongs here, and RT_BUILDER_HARDCODED_TOKEN does not scan past this line. The
   boundary is a reservation: post ids below are concatenated (`'#' . $id`), never typed, because a
   colour regex cannot tell a typed "#732" in a warning from a colour. The START boundary is the one
   carrying weight today (without it the token declarations read as hardcoded literals).

   Known gaps in the region above, reported and not hidden:
     - Lengths INSIDE the CSS blobs (`border-radius:12px`, `padding:10px`, `font-size:13.5px`, motion
       distances such as `translateY(-4px)`) are literals, not on the density axis.
     - Body leading is 1.65 / 1.60 / 1.58 / 1.55 across four helpers and h3 is a flat 1.25.
       design-system.md pins body at 1.6 and h3 at 1.25 and says neither is an axis, so collapsing them
       belongs to whoever owns that number. `display_lh` (an axis) is tokenised and read by every h1
       and h2.
     - `on_accent` is DERIVED (whichever of `text`/`bg` reads better), but the primary button hovers to
       `accent_hover`, and on the three LIGHT grounds (paper 4.06:1, warm 3.82, cool 3.92) the dark
       label and the correctly darkening hover pull opposite ways: still below AA. The DARK grounds
       pass (ink 7.63, ink-warm 7.59, ink-cool 7.69) because es_hover_of() lightens there. Closing the
       light case needs a second on-colour for the hover state, or a roomier accent. */

/**
 * Audit the container tree before it is written.
 *
 * Every extra container level costs a wrapper <div>, a block of generated CSS and one more click in
 * the editor. Reports, never blocks, in three severities:
 *
 *   offenders   — wrong with no argument: an empty container; a container wrapping a single WIDGET
 *                 with no background/border/shadow/boxed width of its own; anything past depth 3.
 *   optimizable — exactly one shape: a container whose only child is a GRID. A flex ROW child or a
 *                 COLUMN child are offenders (both collapse). Kept OUT of `offenders` because
 *                 `es_section( es_grid(...) )` is this repo's dominant idiom and merging the pair is
 *                 not yet confirmed on a live site.
 *   unaudited   — an elType this audit has no opinion about (pre-3.6 `section`/`column`, a kit
 *                 import, a future element). Silence about a tree is not a verdict on it.
 *
 * `unaudited` is a map `elType => {count, first}` (not `string[]`) so an imported kit page with
 * hundreds of legacy elements does not bury the actionable rows. It is NOT an offender (the caller
 * cannot fix an import by rewriting an `es_*()` call) and never blocks, because `es_save_page()`
 * reports mid-write. BELOW an element the audit cannot judge it makes no contextual claim either:
 * depth above a legacy wrapper is measured but not charged, an inherited boxed width is not assumed,
 * and a container whose ONLY child is unjudgeable is not judged. A container's own defect (empty, or
 * wrapping a lone widget for nothing) is still its caller's to fix wherever it sits.
 *
 * @return array{containers:int,widgets:int,max_depth:int,offenders:string[],optimizable:string[],unaudited:array<string,array{count:int,first:string}>}
 */
function es_container_audit( array $elements ) {
	$out = array( 'containers' => 0, 'widgets' => 0, 'max_depth' => 0, 'offenders' => array(), 'optimizable' => array(), 'unaudited' => array() );
	es_container_walk( $elements, 0, '', $out );
	return $out;
}

/**
 * Is this `custom_css` hand-written? Cut every helper block (the marker pair), then every comment;
 * anything left is somebody's own rule. The pair, not a lone prefix: a rule appended to or put in
 * front of a helper's block lands OUTSIDE it and counts. Editing INSIDE a block is not detected —
 * that is the accepted limit, and it needs a deliberate hand rather than an append.
 */
function es_css_is_handwritten( $css ) {
	$css = preg_replace( '~' . preg_quote( ES_HELPER_CSS_OPEN, '~' ) . '.*?' . preg_quote( ES_HELPER_CSS_CLOSE, '~' ) . '~s', '', (string) $css );
	$css = preg_replace( '~/\*.*?\*/~s', '', (string) $css );
	return '' !== trim( (string) $css );
}

/**
 * The verifier of `qa-review` row 37's `css_custom_max`: hand-written `custom_css` in an element
 * tree (the input `es_container_audit()` takes). Helper CSS does not count.
 *
 * @return array{count:int,where:string[]} `where` lists `path elType [widgetType] #id`.
 */
function es_custom_css_audit( array $elements, $path = '' ) {
	$out = array( 'count' => 0, 'where' => array() );
	foreach ( $elements as $i => $el ) {
		$here = $path . '/' . $i;
		$css  = isset( $el['settings']['custom_css'] ) ? $el['settings']['custom_css'] : '';
		if ( is_string( $css ) && es_css_is_handwritten( $css ) ) {
			$out['count']++;
			$out['where'][] = $here . ' ' . ( isset( $el['elType'] ) ? $el['elType'] : '?' ) . ( isset( $el['widgetType'] ) ? ' ' . $el['widgetType'] : '' ) . ( isset( $el['id'] ) ? ' #' . $el['id'] : '' );
		}
		if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
			$sub = es_custom_css_audit( $el['elements'], $here );
			$out['count'] += $sub['count'];
			$out['where']  = array_merge( $out['where'], $sub['where'] );
		}
	}
	return $out;
}

/**
 * `$anc` is what the ancestors say, and both keys exist to stop this audit claiming more than it
 * knows. `boxed` — an ancestor already constrains the width to the boxed content width, the
 * context `es_container_earns_its_place()` needs to tell a wrapper that DOES something from one
 * repeating what its parent already did. `opaque` — an ancestor is an elType this walk cannot
 * judge, so nothing derived from the path above is trustworthy down here.
 */
function es_container_walk( array $els, $depth, $path, array &$out, array $anc = array() ) {
	$boxed  = ! empty( $anc['boxed'] );
	$opaque = ! empty( $anc['opaque'] );
	foreach ( $els as $i => $el ) {
		$type     = isset( $el['elType'] ) ? $el['elType'] : '';
		$here     = $path . '/' . $i;
		$kids     = ( isset( $el['elements'] ) && is_array( $el['elements'] ) ) ? $el['elements'] : array();
		$settings = ( isset( $el['settings'] ) && is_array( $el['settings'] ) ) ? $el['settings'] : array();
		$kid_anc  = $anc;
		/* ABOVE the dispatch: the only background_image here sits on a CONTAINER, so checking it in
		   the widget branch was dead on arrival. isset(), not !empty(): the payload IS the slug. */
		foreach ( array( 'image', 'background_image' ) as $k ) {
			if ( isset( $settings[ $k ]['es_missing'] ) ) { $out['offenders'][] = $here . ' ' . ( '' === $type ? '(sin elType)' : $type ) . ' sin imagen: el slug "' . $settings[ $k ]['es_missing'] . '" no existe, va a renderizar vacio'; }
		}
		/* Also above the dispatch: a setting written on the wrong element type saves fine and never
		   applies — visible in the source, absent on screen, so it gets re-added and re-wondered-at. */
		foreach ( es_key_offenders( $type, $settings, isset( $el['widgetType'] ) ? $el['widgetType'] : '' ) as $why ) {
			$out['offenders'][] = $here . ' ' . ( '' === $type ? '(sin elType)' : $type ) . ' ' . $why;
		}
		if ( 'container' === $type ) {
			$out['containers']++;
			$d = $depth + 1;
			if ( ! $kids ) {
				$out['offenders'][] = empty( $settings['background_image']['url'] )
					? $here . ' contenedor vacio'
					: $here . ' contenedor vacio que solo sostiene una imagen de fondo: usa es_photo() (widget image + object-fit) y gana el alt';
			} elseif ( 1 === count( $kids ) ) {
				$only   = isset( $kids[0]['elType'] ) ? $kids[0]['elType'] : '?';
				$kidset = isset( $kids[0]['settings'] ) && is_array( $kids[0]['settings'] ) ? $kids[0]['settings'] : array();
				/* The child's elType and the ancestors' width are context the settings alone cannot
				   carry, and the predicate needs both — hence the read before the call. */
				$ctx = array( 'only_child' => $only, 'boxed_ancestor' => $boxed );
				/* Three elType families, and the third one is why this is a whitelist and not
				   `'container' !== $only`. That negation sent every elType the walk had just filed
				   under `unaudited` into the lone-WIDGET remedy, so an import shaped
				   `container > column > widget` was told "usa el widget directo" about a child that
				   is not a widget — un-followable advice, counted as an offender, printed on the
				   same line as NO AUDITABLE. An unjudgeable only child means the wrapper cannot be
				   judged either: it is already recorded where it belongs, and nothing is said here. */
				if ( ! es_container_earns_its_place( $settings, $ctx ) ) {
					if ( 'widget' === $only ) {
						$out['offenders'][] = $here . es_lone_widget_remedy( $settings, $boxed );
					} elseif ( 'container' === $only ) {
						if ( isset( $kidset['container_type'] ) && 'grid' === $kidset['container_type'] ) {
							/* section > grid: this repo's own idiom. Mergeable in theory, a human decides. */
							$out['optimizable'][] = $here . ' contenedor cuyo unico hijo es un grid: candidato a fusionar';
						} elseif ( in_array( isset( $kidset['flex_direction'] ) ? $kidset['flex_direction'] : '', array( 'row', 'row-reverse' ), true ) ) {
							/* A flex ROW child is the one es_split() actually collapses: the section becomes
							   the row. Naming that remedy for a child stacking in a COLUMN was advice that
							   could not be followed — es_split() would have changed the layout's axis. Only
							   the desktop value is read on purpose: es_split() sets the tablet/mobile
							   variants itself, so no breakpoint value can change WHICH remedy applies. */
							$out['offenders'][] = $here . ' contenedor cuyo unico hijo es una fila flex: la seccion ES la fila, usa es_split()';
						} else {
							$out['offenders'][] = $here . ' contenedor cuyo unico hijo es otro contenedor en columna: fusiona ambos, el hijo no aporta un eje distinto';
						}
					}
					/* Any other elType falls through saying nothing: the child is already recorded
					   under `unaudited`, and a wrapper around something this walk cannot judge
					   cannot be judged either. */
				}
			}
			/* Depth is MEASURED across legacy levels below and reported in max_depth either way.
			   It is only CHARGED here when every level above was one this walk judged — four
			   imported wrappers are not a nesting decision the caller made. */
			if ( $d > 3 && ! $opaque ) {
				$out['offenders'][] = $here . ' anidado a profundidad ' . $d . ' (max recomendado 3)';
			}
			$kid_anc['boxed'] = $boxed || ( isset( $settings['content_width'] ) && 'boxed' === $settings['content_width'] );
		} elseif ( 'widget' === $type ) {
			$out['widgets']++;
			$d = $depth;                                    /* a widget is content, not a wrapper level */
		} else {
			$k = ( '' === $type ) ? '(sin elType)' : $type;
			if ( ! isset( $out['unaudited'][ $k ] ) ) {
				$out['unaudited'][ $k ] = array( 'count' => 0, 'first' => $here );
			}
			$out['unaudited'][ $k ]['count']++;
			$d       = $depth + 1;                          /* a legacy wrapper IS a level, judged or not */
			$kid_anc = array( 'opaque' => true );           /* below it, inherit nothing — not even boxed */
		}
		if ( $d > $out['max_depth'] ) {
			$out['max_depth'] = $d;
		}
		/* HOISTED out of the container branch. It used to live inside it, so anything under a
		   legacy wrapper — or inside a widget that carries its own elements, like a loop
		   template — was never walked at all. */
		es_container_walk( $kids, $d, $here, $out, $kid_anc );
	}
}

/**
 * Name the remedy for a container whose only child is a widget.
 *
 * Every branch has to be something the caller can DO. "pasa el padding al widget" was printed
 * unconditionally, including for wrappers carrying no padding to pass.
 */
function es_lone_widget_remedy( array $s, $boxed_ancestor ) {
	if ( ! empty( $s['width'] ) ) {
		return ' envoltorio que solo da un ancho: usa es_wide($widget, N) en vez de un contenedor';
	}
	if ( $boxed_ancestor && isset( $s['content_width'] ) && 'boxed' === $s['content_width'] ) {
		return ' envoltorio boxed dentro de otro boxed: acotar de nuevo no cambia el ancho, borra este';
	}
	if ( ! empty( $s['padding'] ) ) {
		return ' envoltorio de 1 widget sin fondo/borde/sombra: pasa el padding al widget';
	}
	return ' envoltorio de 1 widget que no aporta nada: usa el widget directo';
}

/**
 * A container with a single child is only justified if it does something nothing else can.
 *
 * `$ctx` carries what the settings alone cannot say: `only_child` is that child's elType, and
 * `boxed_ancestor` is true when some ancestor already constrains the width. Optional, so every
 * existing caller keeps working and the predicate stays usable on a bare settings array.
 */
function es_container_earns_its_place( array $s, array $ctx = array() ) {
	/* es_img() returns array('url'=>'','id'=>'') when the slug is missing, and a non-empty
	   array is truthy — so a BROKEN image lookup used to buy the container an alibi. */
	if ( ! empty( $s['background_image']['url'] ) ) {
		return true;
	}
	foreach ( array( 'background_background', 'border_border', 'border_radius', 'box_shadow_box_shadow_type', 'sticky' ) as $k ) {
		if ( ! empty( $s[ $k ] ) ) {
			return true;
		}
	}
	/* Changing direction or column count at a breakpoint is a real reason to exist. */
	foreach ( $s as $k => $v ) {
		if ( ! empty( $v ) && preg_match( '/^(flex_direction|grid_columns_grid|content_width)_(tablet|mobile)$/', $k ) ) {
			return true;
		}
	}
	/* Constraining a lone widget to the boxed content width. Elementor gives a widget no way to
	   do this itself, so here the wrapper IS the mechanism and "use the widget directly" was
	   advice that would have changed the layout.
	   All three conditions are load-bearing. The child must be a WIDGET: a container child gets
	   its own boxed setting, and passing this to `es_section( es_row(...) )` would silence the
	   offender that names es_split(). No boxed ANCESTOR: a second boxing inside the first
	   changes nothing. And `content_width` must be present and 'boxed' EXPLICITLY, because
	   Elementor's runtime default is already boxed, so an absent key is not a decision.
	   `padding` is deliberately NOT a pass and must not become one: padding on a wrapper is the
	   canonical thing that belongs on the widget, which is the offender's own remedy. It only
	   sharpens which message es_lone_widget_remedy() prints. */
	if ( 'widget' === ( isset( $ctx['only_child'] ) ? $ctx['only_child'] : '' )
		&& empty( $ctx['boxed_ancestor'] )
		&& isset( $s['content_width'] ) && 'boxed' === $s['content_width'] ) {
		return true;
	}
	return false;
}

/**
 * Say something out loud, once, through BOTH channels.
 *
 * The sandbox returns STDOUT from `execute-php`; `error_log()` goes to the server's PHP log, which
 * nobody fetches. Route every warning through here so a silent failure is impossible by construction.
 *
 * ES_AUDIT_SILENT does NOT reach here: it mutes the routine audit REPORT, while a warning exists only
 * because something went wrong and must not be silenced as a side effect of wanting tidy output.
 */
function es_warn( $msg ) {
	error_log( 'WordPress Orchestrator: ' . str_replace( "\n", ' | ', $msg ) );
	echo 'WordPress Orchestrator AVISO: ' . $msg . "\n";
}

/**
 * Report the audit where a human will actually read it.
 *
 * This used to only call error_log(), and that is precisely why a build shipped with empty and
 * redundant containers anyway: the offenders were written to the server's PHP log, which nobody
 * fetches. The sandbox returns STDOUT from `execute-php`, so echoing is the difference between a
 * rule that is measured and a rule that is seen. error_log() stays as the durable copy.
 *
 * Define ES_AUDIT_SILENT before the build if stdout must stay clean for some other consumer.
 */
function es_container_report( array $elements, $label = '' ) {
	global $es_audit_runs;

	$a   = es_container_audit( $elements );
	$msg = sprintf(
		'WordPress Orchestrator contenedores%s: %d contenedores / %d widgets, profundidad max %d',
		$label ? ' [' . $label . ']' : '',
		$a['containers'],
		$a['widgets'],
		$a['max_depth']
	);
	if ( $a['offenders'] ) {
		$msg .= "\n  A CORREGIR (" . count( $a['offenders'] ) . "):\n    " . implode( "\n    ", $a['offenders'] );
	}
	if ( $a['optimizable'] ) {
		$msg .= "\n  fusionables (" . count( $a['optimizable'] ) . ", decide un humano):\n    " . implode( "\n    ", $a['optimizable'] );
	}
	if ( $a['unaudited'] ) {
		$bits  = array();
		$total = 0;
		foreach ( $a['unaudited'] as $k => $u ) {
			$total += $u['count'];
			$bits[] = 'elType "' . $k . '" x' . $u['count'] . ' (primero en ' . $u['first'] . ')';
		}
		$msg .= "\n  NO AUDITABLE (" . $total . ", esta parte del arbol no fue juzgada):\n    " . implode( "\n    ", $bits );
	}

	error_log( str_replace( "\n", ' | ', $msg ) );
	if ( ! defined( 'ES_AUDIT_SILENT' ) ) {
		echo $msg . "\n";
	}

	if ( ! isset( $es_audit_runs ) || ! is_array( $es_audit_runs ) ) {
		$es_audit_runs = array();
	}
	$es_audit_runs[ $label ? $label : count( $es_audit_runs ) ] = $a;

	return $a;
}

/**
 * One verdict line for the whole build.
 *
 * Call it at the END of the build function; it is the line the deploy step reads. THE LINE IS THE
 * PRIMARY ARTIFACT; the integer is a convenience for a caller that wants to branch:
 *
 *    0  — audited, clean.
 *   >0  — audited, N offenders to fix.
 *   -1  — NOTHING was audited: es_container_report() never ran. Speaks through es_warn(), not the
 *         verdict writer: it warns about the audit, it does not judge a tree.
 *   -2  — audited, but part of the tree is elTypes this audit cannot judge. Zero offenders over a
 *         tree nobody judged is not a pass either.
 *
 * `0 === clean` is preserved because callers branch on it; the failures are NEGATIVE so no existing
 * `if ( es_audit_summary() )` treats them as success, and -2 wins over an offender count. Branch on
 * the integer, never on a word found in the line: the caller's page label is interpolated into the
 * deep-nesting suffix and can contain any text.
 */
function es_audit_summary() {
	global $es_audit_runs;

	/* Before the verdict, and outside all four of its branches: whether `/` serves the blog is a
	   fact about the SITE, not about the tree, so it is true of a clean build and of SIN AUDITAR
	   alike. It stays out of the return value on purpose — callers already branch on that integer,
	   and a fifth meaning would change what an existing `if ( es_audit_summary() )` decides. */
	es_front_page_check();
	/* Same shape, same reason, same place: whether the site can SERVE the families es_tokens() names
	   is a fact about the site, true of a clean tree and of SIN AUDITAR alike, and it belongs on the
	   one line the operator is told to read before deploying. It is wired HERE rather than in
	   es_save_page() because it is one fact about the site — repeating it per page would bury the
	   pages under it — and because es_audit_summary() is the call SKILL.md already requires at the
	   end of every build function, so the wiring costs no new rule nobody enforces. It stays out of
	   the return value for the same reason es_front_page_check() does. */
	es_font_serving_check();

	if ( ! isset( $es_audit_runs ) || ! is_array( $es_audit_runs ) || ! $es_audit_runs ) {
		/* es_warn(), NO es_audit_verdict(): el escritor lo volvia callable, y era la unica linea
		   que este archivo SIEMPRE habia impreso. */
		es_warn( 'auditoria VEREDICTO SIN AUDITAR: ninguna pagina paso por es_container_report(). O falta cablear el audit en la funcion de build, o se llamo al resumen antes de guardar nada, o TODAS las paginas fallaron al guardarse. No hay ningun arbol detras de este numero.' );
		return -1;
	}
	$off  = 0;
	$opt  = 0;
	$un   = 0;
	$deep = array();
	foreach ( $es_audit_runs as $page => $a ) {
		$off += count( $a['offenders'] );
		$opt += count( $a['optimizable'] );
		if ( ! empty( $a['unaudited'] ) ) {
			foreach ( $a['unaudited'] as $u ) {
				$un += $u['count'];   /* the COUNT, not one per elType: an import is hundreds */
			}
		}
		if ( $a['max_depth'] > 3 ) {
			$deep[] = $page . '(' . $a['max_depth'] . ')';
		}
	}
	$tail = sprintf(
		'%d paginas, %d a corregir, %d fusionables%s',
		count( $es_audit_runs ),
		$off,
		$opt,
		$deep ? ', profundidad >3 en ' . implode( ', ', $deep ) : ''
	);
	if ( $un ) {
		return es_audit_verdict(
			'NO AUDITABLE: ' . $tail . ', y ' . $un . ' elementos con un elType que este audit no sabe juzgar (section/column heredados, o un kit importado) — parte de este arbol no fue juzgada',
			-2
		);
	}
	if ( $off ) {
		return es_audit_verdict( 'A CORREGIR: ' . $tail, $off );
	}
	return es_audit_verdict( 'LIMPIO: ' . $tail, 0 );
}

/** One writer for the three verdicts ABOUT a tree, so no branch forgets the silence rule or the log. */
function es_audit_verdict( $rest, $code ) {
	$line = 'WordPress Orchestrator auditoria VEREDICTO ' . $rest;
	error_log( $line );
	if ( ! defined( 'ES_AUDIT_SILENT' ) ) {
		echo $line . "\n";
	}
	return $code;
}

/**
 * Save an Elementor layout onto a page, creating the page when missing.
 *
 * `$tpl` defaults to `elementor_header_footer` (Elementor Full Width): full-bleed content that KEEPS
 * the theme / Theme Builder header and footer. Do not switch the default to `elementor_canvas`: Canvas
 * renders neither, so every page loses the global header (the "header on every page" house rule).
 * Pass `elementor_canvas` explicitly only for a page that must have no chrome (standalone landing,
 * coming-soon splash).
 *
 * BUILD GATE: a slug that did not pass `es_overwrite_preflight()` is refused ('failed', return 0,
 * nothing written, not even the backup). Resuming an interrupted build = preflight the slugs left.
 *
 * Overwriting an existing page is destructive: writing `_elementor_data` through the meta API replaces
 * the whole layout and leaves no revision. Every overwrite therefore parks the displaced state in a
 * timestamped backup key first (see es_backup_page_state): the layout AND the page template, edit
 * mode, template type, version and post fields. Overwriting a page not built with Elementor also
 * warns: its `post_content` survives in the database and the backup but stops being what the visitor
 * sees. The existing `post_status` is preserved (only pages this function creates are published).
 *
 * `$action` is an out-parameter (by reference, because callers rely on the return value being the
 * page id). It reports FOUR outcomes:
 *
 *   'created'         — the page did not exist and now does, at the slug that was asked for.
 *   'updated'         — an existing page was rewritten in place.
 *   'created-renamed' — the page was created, but NOT where you asked. See below.
 *   'failed'          — nothing was written. The return value is 0.
 *
 * Branch on `$action`, and treat anything that is not 'created' or 'updated' as needing a human.
 */
function es_save_page( $slug, $title, array $elements, $tpl = 'elementor_header_footer', &$action = null ) {
	es_safe_mode_check();
	/* The build gate. BEFORE the lookup, the backup and every write: a slug nobody was shown writes
	   nothing, and reports it the way every other refused write does ('failed', 0). */
	if ( ! es_approval_check( $slug ) ) {
		$action = 'failed';
		return 0;
	}
	$page = es_page_by_slug( $slug );
	if ( $page ) {
		$id     = $page->ID;
		$action = 'updated';
		/* BEFORE wp_update_post(), not after. The backup used to sit further down, past this call,
		   so `post_title` and `post_status` were saved as the values this very call had just
		   written -- the meta half of that same bug was fixed earlier and this half survived it,
		   because a page rebuilt under its own name displaces a title identical to the new one and
		   nothing looks wrong. Caught by an end-to-end run on a live site where the title DID
		   change: the backup recorded the new one. Meta keys are still untouched at this point, so
		   moving the whole block up keeps them correct too. */
		es_backup_page_state(
			$id,
			array( '_elementor_data', '_wp_page_template', '_elementor_edit_mode', '_elementor_template_type', '_elementor_version' )
		);
		/* The most destructive overwrite in the repertoire, and the one the old backup covered
		   LEAST: with no `_elementor_data` to copy it returned '' and said nothing, while the
		   post_content that WAS the page stopped rendering for good. */
		$body = trim( (string) get_post_field( 'post_content', $id ) );
		if ( 'builder' !== get_post_meta( $id, '_elementor_edit_mode', true ) && '' !== $body ) {
			es_warn(
				'"' . $slug . '" (#' . $id . ') no era una pagina de Elementor y va a serlo. Su contenido actual ('
				. strlen( $body ) . ' caracteres del editor clasico o de bloques) deja de renderizarse: sigue en la base de '
				. 'datos y en el respaldo, pero el visitante ya no lo ve. Si no era la intencion, para aqui.'
			);
		}
		/* post_status intentionally mirrors what is already there - see docblock above.
		   The return value is KEPT: discarding it made this the one branch that could not fail, so
		   a post WordPress refused to touch still had its layout overwritten and still reported
		   'updated' - a write reporting success over work it did not do. */
		$wrote = wp_update_post( array( 'ID' => $id, 'post_title' => $title, 'post_status' => $page->post_status ) );
	} else {
		$action = 'created';
		$id     = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);
		$wrote  = $id;
	}
	if ( is_wp_error( $wrote ) || ! $wrote ) {
		/* This branch used to return 0 and say NOTHING, so a page that never got built left no
		   trace on either channel and the run still ended on a clean audit verdict - the audit only
		   ever sees the tree it was HANDED, never the write. Fails CLOSED on the update path: if
		   WordPress would not update the row, nothing authorises rewriting its design. */
		es_warn(
			'WordPress rechazo ' . ( 'updated' === $action ? 'actualizar' : 'crear' ) . ' la pagina "' . $slug . '"'
			. ( is_wp_error( $wrote ) ? ': ' . $wrote->get_error_message() : '' )
			. '. NO se escribio ningun diseño. Esa pagina no existe o quedo como estaba; el resto del build sigue.'
		);
		$action = 'failed';
		return 0;
	}

	if ( 'created' === $action ) {
		/* wp_insert_post() does not promise the slug you asked for. When one is taken - by an
		   attachment, by a post, by a reserved term - wp_unique_post_slug() appends a suffix and
		   returns happily, so asking for "contacto" published a page at "contacto-2" while $action
		   said 'created'. The page the caller believes it just built is somebody else's. */
		$real = get_post_field( 'post_name', $id );
		if ( '' !== $real && $real !== $slug ) {
			es_warn(
				'se pidio la pagina "' . $slug . '" y WordPress la creo en "' . $real . '" (#' . $id . '), porque ese slug ya estaba ocupado '
				. '(otra entrada, un adjunto o un termino reservado). La URL que esperabas NO apunta a esta pagina. '
				. 'Libera el slug y renombrala, o cambia el slug en el build.'
			);
			$action = 'created-renamed';
		}
	}

	update_post_meta( $id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $id, '_elementor_template_type', 'wp-page' );
	update_post_meta( $id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0' );
	update_post_meta( $id, '_wp_page_template', $tpl );
	es_container_report( $elements, $slug );
	update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $elements ) ) );
	es_rebuild_css( $id );

	/* What this run actually WROTE, keyed by the slug it actually landed on — not the one that was
	   asked for. `created-renamed` is the whole reason for the distinction: recording the requested
	   slug would put a page in the manifest at a URL that answers with somebody else's. Read by
	   es_front_page_check(), and the honest source for es_manifest_record( 'pages', … ). */
	global $es_saved_pages;
	if ( ! isset( $es_saved_pages ) || ! is_array( $es_saved_pages ) ) {
		$es_saved_pages = array();
	}
	$landed = (string) get_post_field( 'post_name', $id );
	$es_saved_pages[ '' !== $landed ? $landed : $slug ] = (int) $id;

	/* One preflight covers ONE write per slug. Spent only now, after the write landed: an interrupted
	   page keeps its approval, a page that landed needs a new preflight (it is a different overwrite). */
	es_preflight_spend( $slug );

	return $id;
}

/**
 * Find a PAGE by slug — and only a page.
 *
 * `get_page_by_path( $slug, OBJECT, 'page' )` folds attachments into the lookup: a slug held by a
 * media item comes back as a normal result with `post_type` = `attachment` (measured on a live
 * install). Unfiltered, es_save_page() would take the UPDATE branch on the attachment, rename it and
 * write `_elementor_data` onto it, reporting `updated`. One lookup, one type check, one place to be
 * wrong. Callers that need "is ANYTHING holding this slug" (WordPress suffixes against the whole slug
 * space) must ask get_page_by_path() directly and say what they found.
 */
function es_page_by_slug( $slug ) {
	$found = get_page_by_path( $slug, OBJECT, 'page' );

	return ( $found && isset( $found->post_type ) && 'page' === $found->post_type ) ? $found : null;
}

/**
 * What is the site's front page RIGHT NOW?
 *
 * The ONE resolver. Nothing in this library may guess the home from a slug: on an install whose
 * front page is `/`, `/inicio/` is a dead link, and on an install still showing the blog there is
 * no home page at all. Both facts are only knowable from these two options.
 *
 * Two options, not one. `page_on_front` alone is NOT a front page: WordPress renders the blog
 * unless `show_on_front` is also `'page'`, so a reader that checked only the id would report a
 * front page nobody sees. Half the setting is the same as none of it.
 *
 * Returns `array( 'mode' => 'posts'|'page', 'id' => int, 'slug' => string )`.
 */
function es_front_page() {
	$mode = get_option( 'show_on_front' );
	$id   = (int) get_option( 'page_on_front' );
	if ( 'page' !== $mode || ! $id ) {
		return array(
			'mode' => 'posts',
			'id'   => 0,
			'slug' => '',
		);
	}

	return array(
		'mode' => 'page',
		'id'   => $id,
		'slug' => (string) get_post_field( 'post_name', $id ),
	);
}

/**
 * Point the site's front page at a page this build made, and PROVE it landed.
 *
 * Without this a home page can be built, audited clean and handed over while WordPress keeps serving
 * the blog at `/`. The options are READ BACK rather than trusted: `update_option()` returns false both
 * when the write fails and when the value did not change.
 *
 * Repointing an existing front page warns on purpose, naming the page that stops being shown: the old
 * home stays published, it just stops being the landing page.
 *
 * Returns the page id, or 0 when the front page is not what was asked for.
 */
function es_set_front_page( $slug ) {
	$page = es_page_by_slug( $slug );
	if ( ! $page ) {
		es_warn(
			'no existe ninguna pagina con el slug "' . $slug . '", asi que la portada NO se cambio. '
			. 'El sitio sigue mostrando lo que mostraba. Construye y guarda esa pagina antes de fijarla como portada.'
		);
		return 0;
	}
	$before = es_front_page();

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $page->ID );

	$after = es_front_page();
	if ( 'page' !== $after['mode'] || (int) $page->ID !== $after['id'] ) {
		es_warn(
			'se pidio poner "' . $slug . '" (#' . $page->ID . ') como portada, pero al releer las opciones el sitio '
			. ( 'posts' === $after['mode'] ? 'sigue mostrando las entradas' : 'muestra la pagina #' . $after['id'] ) . '. '
			. 'La escritura se acepto y no quedo: revisa permisos, un plugin que filtre la opcion, o una cache de opciones.'
		);
		return 0;
	}
	if ( $before['id'] && $before['id'] !== (int) $page->ID ) {
		es_warn(
			'la portada del sitio era "' . ( '' !== $before['slug'] ? $before['slug'] : '#' . $before['id'] ) . '" y ahora es "' . $slug . '". '
			. 'La anterior sigue publicada, solo deja de ser la que se ve al entrar. Si no era la intencion, esto es lo que hay que revertir.'
		);
	}

	return (int) $page->ID;
}

/**
 * Park EVERYTHING an overwrite is about to displace, in one timestamped backup meta key.
 *
 * Elementor stores a layout as one blob of post meta, so rewriting it destroys the previous design
 * (no revision, no diff). It takes the exact key list the caller is about to write (layout, page
 * template, edit mode, template type, version), the post fields `wp_update_post()` touches, and
 * `post_content`, which is not displaced but STOPS BEING RENDERED when a classic page becomes an
 * Elementor one.
 *
 * Must be called BEFORE the first write: it cannot tell an old value from a new one.
 *
 * Backup key: `_es_page_backup_<Ymd-His>` (UTC), an array keyed by what it saved; restore with
 * es_restore_page_state(). The leading underscore keeps backups out of the custom-fields UI. They are
 * never pruned by default (es_prune_backups()), so a long-lived page accumulates one per rebuild.
 *
 * Returns the key written, or '' when there was genuinely nothing to preserve.
 */
/**
 * Put a page back the way a backup found it — and prove each piece landed.
 *
 * A by-hand restore (key by key, `_elementor_data` through `wp_slash()`, CSS regenerated afterwards)
 * is a procedure nobody executes correctly at the moment something has gone wrong; hence this.
 *
 * Restoring is destructive, so it BACKS UP FIRST (its own timestamped key): a restore aimed at the
 * wrong page or moment is recoverable, and restoring twice returns you to where you started. Every
 * piece is READ BACK, because `update_post_meta()` and `update_option()` return false on failure AND on
 * an unchanged value and `wp_update_post()` can be filtered. It reports what it VERIFIED, never what it
 * attempted, and a partial restore says which parts are missing.
 *
 * `$key` empty picks the NEWEST backup ("undo that").
 *
 * Returns `array( 'key' => string, 'restored' => array, 'failed' => array, 'safety' => string )`, or
 * an empty `restored` with a warning when there is nothing to restore.
 */
function es_restore_page_state( $post_id, $key = '' ) {
	$post_id = (int) $post_id;
	$all     = es_backup_keys( array( $post_id ) );
	$keys    = isset( $all[ $post_id ] ) ? $all[ $post_id ] : array();

	if ( ! $keys ) {
		es_warn( 'la pagina #' . $post_id . ' no tiene ningun respaldo guardado, asi que no hay nada que restaurar.' );
		return array(
			'key'      => '',
			'restored' => array(),
			'failed'   => array(),
			'safety'   => '',
		);
	}
	if ( '' === $key ) {
		$key = end( $keys );   /* es_backup_keys() sorts, so the last one is the newest */
	}
	if ( ! in_array( $key, $keys, true ) ) {
		es_warn(
			'la pagina #' . $post_id . ' no tiene ningun respaldo llamado "' . $key . '". Los que tiene son: '
			. implode( ', ', $keys ) . '. No se restauro nada.'
		);
		return array(
			'key'      => '',
			'restored' => array(),
			'failed'   => array(),
			'safety'   => '',
		);
	}

	$state = get_post_meta( $post_id, $key, true );
	if ( ! is_array( $state ) || ! $state ) {
		es_warn( 'el respaldo "' . $key . '" de la pagina #' . $post_id . ' esta vacio o no tiene la forma esperada. No se restauro nada.' );
		return array(
			'key'      => '',
			'restored' => array(),
			'failed'   => array(),
			'safety'   => '',
		);
	}

	/* Restoring overwrites. Park the CURRENT state first, using the same key list this backup
	   holds, so undoing an undo is possible. */
	$meta_keys = array();
	foreach ( array_keys( $state ) as $k ) {
		if ( 0 === strpos( (string) $k, '_' ) ) {
			$meta_keys[] = $k;
		}
	}
	$safety = es_backup_page_state( $post_id, $meta_keys );

	$restored = array();
	$failed   = array();
	$fields   = array();

	foreach ( $state as $k => $value ) {
		if ( 0 === strpos( (string) $k, '_' ) ) {
			/* `_elementor_data` is stored slashed; everything else round-trips as it is. */
			update_post_meta( $post_id, $k, '_elementor_data' === $k ? wp_slash( $value ) : $value );
			continue;
		}
		$fields[ $k ] = $value;
	}
	if ( $fields ) {
		$fields['ID'] = $post_id;
		wp_update_post( $fields );
	}

	/* The read-back. Nothing above is trusted. */
	foreach ( $state as $k => $value ) {
		$now = ( 0 === strpos( (string) $k, '_' ) )
			? get_post_meta( $post_id, $k, true )
			: get_post_field( $k, $post_id );
		if ( (string) $now === (string) $value ) {
			$restored[] = $k;
		} else {
			$failed[] = $k;
		}
	}

	es_rebuild_css( $post_id );

	if ( $failed ) {
		es_warn(
			'la restauracion de la pagina #' . $post_id . ' desde "' . $key . '" quedo A MEDIAS: '
			. implode( ', ', $failed ) . ' no coincide al releerlo. La pagina esta ahora en un estado mezclado, '
			. ( '' !== $safety ? 'y el estado previo quedo en "' . $safety . '".' : 'y no se pudo guardar el estado previo.' )
		);
	}

	return array(
		'key'      => $key,
		'restored' => $restored,
		'failed'   => $failed,
		'safety'   => $safety,
	);
}

/**
 * Keep the newest N backups of a page and delete the rest.
 *
 * Backups are never pruned by design — a long-lived page accumulates one per rebuild, and each one
 * now holds the whole displaced state rather than a single blob, so they are bigger than they used
 * to be. That is the right default (losing the one you needed costs more than the rows), but it
 * cannot be the ONLY option or the meta table becomes the thing that breaks.
 *
 * Deletes oldest-first and READS BACK: `delete_post_meta()` returns false both when it failed and
 * when there was nothing there, so the proof is the re-read, exactly as with the sandbox purge.
 *
 * Returns `array( 'kept' => array, 'deleted' => array, 'still_there' => array )`.
 */
function es_prune_backups( $post_id, $keep = 5 ) {
	$post_id = (int) $post_id;
	$keep    = max( 1, (int) $keep );   /* keeping zero is not pruning, it is deleting the backups */
	$all     = es_backup_keys( array( $post_id ) );
	$keys    = isset( $all[ $post_id ] ) ? $all[ $post_id ] : array();

	if ( count( $keys ) <= $keep ) {
		return array(
			'kept'        => $keys,
			'deleted'     => array(),
			'still_there' => array(),
		);
	}
	$drop = array_slice( $keys, 0, count( $keys ) - $keep );   /* sorted: oldest first */
	$kept = array_slice( $keys, count( $keys ) - $keep );

	foreach ( $drop as $k ) {
		delete_post_meta( $post_id, $k );
	}

	$after = es_backup_keys( array( $post_id ) );
	$now   = isset( $after[ $post_id ] ) ? $after[ $post_id ] : array();
	$stuck = array_values( array_intersect( $drop, $now ) );

	if ( $stuck ) {
		es_warn(
			'no se pudieron borrar ' . count( $stuck ) . ' respaldos de la pagina #' . $post_id . ': '
			. implode( ', ', $stuck ) . '. Siguen ocupando sitio en la tabla de meta.'
		);
	}

	return array(
		'kept'        => $kept,
		'deleted'     => array_values( array_diff( $drop, $now ) ),
		'still_there' => $stuck,
	);
}

function es_backup_page_state( $post_id, array $meta_keys ) {
	$state = array();
	foreach ( $meta_keys as $key ) {
		$value = get_post_meta( $post_id, $key, true );
		if ( '' !== $value && array() !== $value && '[]' !== $value ) {
			$state[ $key ] = $value;
		}
	}
	foreach ( array( 'post_title', 'post_status', 'post_content' ) as $field ) {
		$value = (string) get_post_field( $field, $post_id );
		if ( '' !== $value ) {
			$state[ $field ] = $value;
		}
	}
	if ( ! $state ) {
		return '';
	}
	$key = '_es_page_backup_' . gmdate( 'Ymd-His' );
	update_post_meta( $post_id, $key, $state );

	return $key;
}

/**
 * Settings written on the wrong element type, which Elementor accepts and then ignores.
 *
 * Elementor names the same control differently by location: a CONTAINER takes `padding`, a WIDGET
 * takes `_padding` (wrapper "advanced" controls carry the underscore). The wrong form saves, opens and
 * renders, and is simply not there. MEASURED on Elementor 4.2.2: a widget with `padding` or
 * `flex_direction`, and a container with `_padding`, emit NO rule and the value appears nowhere in the
 * generated stylesheet; the JSON keeps the setting forever and nothing warns.
 *
 * The key list is deliberately SHORT: `width` is both a container layout key AND a genuine widget
 * control, so flagging it would invent offenders on correct code. When in doubt a key stays off.
 *
 * Reports, never blocks, through the same offender channel as the rest of the walk.
 *
 * `$widget_type` is the widget's own name: `padding` is a real dimensions control on three widget
 * types and `background_background` a real choose control on seven (measured over the 128 widgets that
 * expose controls), so "on a widget it must carry the underscore" would tell their authors to break
 * working code. See es_owns_control().
 */
function es_key_offenders( $type, array $settings, $widget_type = '' ) {
	/* Container-only layout keys: a widget has no flex box of its own to configure. Re-measured
	   across all 128 widget types that expose controls on Elementor 4.2.2 + Pro 4.2.1: not one of
	   these five is a real control on any of them, in either spelling. */
	$container_only = array( 'content_width', 'flex_direction', 'flex_gap', 'flex_justify_content', 'flex_align_items' );
	/* Wrapper controls, spelled bare on a container and underscored on a widget. */
	$wrapper = array( 'padding', 'margin', 'background_background' );

	$out = array();
	if ( 'widget' === $type ) {
		foreach ( $container_only as $key ) {
			if ( isset( $settings[ $key ] ) ) {
				$out[] = 'lleva "' . $key . '", que es una clave de CONTENEDOR: Elementor la guarda y no la aplica. Ponla en el contenedor padre';
			}
		}
		foreach ( $wrapper as $key ) {
			if ( isset( $settings[ $key ] ) && ! isset( $settings[ '_' . $key ] ) && ! es_owns_control( $widget_type, $key ) ) {
				$out[] = 'lleva "' . $key . '" sin guion bajo: en un widget esa clave es "_' . $key . '" y sin el no hace nada';
			}
		}
	} elseif ( 'container' === $type ) {
		foreach ( $wrapper as $key ) {
			if ( isset( $settings[ '_' . $key ] ) && ! isset( $settings[ $key ] ) ) {
				$out[] = 'lleva "_' . $key . '" con guion bajo: en un contenedor esa clave es "' . $key . '" y con el no hace nada';
			}
		}
	}

	return $out;
}

/**
 * Does this widget type own `$key` as a control of its own?
 *
 * The wrapper rule ("a container takes `padding`, a widget takes `_padding`") holds for the wrapper
 * controls every widget inherits and is FALSE where a widget defines a control under the bare name
 * (measured over all 128 widget types on Elementor 4.2.2 + Pro 4.2.1: `padding` is a real control on
 * three, `background_background` on seven, `width` on ten). An invented offender costs more than a
 * missed one.
 *
 * Asks ELEMENTOR when it is there (a hardcoded roster goes stale silently); the measured list below is
 * the fallback for the offline suite: short, dated, derived, never guessed.
 *
 * A type Elementor does not recognise returns no controls, treated as "does not own it" (fails towards
 * reporting; an unregistered widget renders empty and the walk has its own row for it). An element with
 * no `widgetType` arrives as `''`, never null: measured on 4.2.2, `get_widget_types('')` returns NULL
 * and `get_widget_types(null)` returns all 130.
 */
function es_owns_control( $widget_type, $key ) {
	static $live = array();
	if ( class_exists( '\Elementor\Plugin' ) ) {
		if ( ! isset( $live[ $widget_type ] ) ) {
			$w                    = \Elementor\Plugin::instance()->widgets_manager->get_widget_types( $widget_type );
			/* The STACK, not `get_controls()`: on a front-end or CLI request Elementor sets the style
			   controls aside in `style_controls` and `get_controls()` leaves them out (measured on
			   4.2.4: `heading` 176 controls, no `title_color`, no `typography_*`). The stack holds both. */
			$stack                = ( $w && method_exists( $w, 'get_stack' ) ) ? (array) $w->get_stack() : array();
			$live[ $widget_type ] = ( isset( $stack['controls'] ) ? (array) $stack['controls'] : array() )
				+ ( isset( $stack['style_controls'] ) ? (array) $stack['style_controls'] : array() );
		}

		/* Elementor is HERE, so Elementor is the answer — including when it answers "no such widget",
		   which used to fall through to the list below. That fall-through was the only path on which
		   a version-pinned roster could still decide anything on a live site, and a roster whose
		   staleness is guarded by a sentence in a docblock is the exact failure this file spent the
		   day removing. An unregistered type owns no controls; it renders empty, and the walk has
		   its own row for that. */
		return isset( $live[ $widget_type ][ $key ] );
	}
	/* Reached ONLY when Elementor is absent entirely — the offline suite, or a tree audited outside
	   WordPress. MEASURED on Elementor 4.2.2 / Pro 4.2.1; re-run the introspection when either
	   moves. It can no longer go stale behind a live site's back, because a live site never asks it. */
	$measured = array(
		'padding'               => array( 'nested-tabs', 'call-to-action', 'table-of-contents' ),
		'background_background' => array( 'button', 'archive-posts', 'loop-grid', 'off-canvas', 'posts', 'paypal-button', 'stripe-button' ),
	);

	return isset( $measured[ $key ] ) && in_array( $widget_type, $measured[ $key ], true );
}

/**
 * The project manifest: what this framework knows about THIS site, between sessions.
 *
 * Without it every session re-derives the builder, page ids, slugs and approvals, which is how a page
 * gets built twice. It lives in a WordPress option, not a file next to this library, because the
 * library is uploaded to a sandbox the delivery phase deletes; the option travels with the site.
 *
 * Shape: `array( 'schema' => 1, 'updated' => 'Ymd-His', 'sections' => array( name => array(
 * 'at' => 'Ymd-His', 'data' => array( … ) ) ) )`. Sections are namespaced per concern so two skills
 * never overwrite each other's. The names are `es_manifest_sections()`, below.
 */
function es_manifest_read() {
	$raw = get_option( 'es_novamira_manifest' );
	if ( ! is_array( $raw ) || ! isset( $raw['sections'] ) || ! is_array( $raw['sections'] ) ) {
		return array(
			'schema'   => 1,
			'updated'  => '',
			'sections' => array(),
		);
	}

	return $raw;
}

/**
 * The three sections the manifest holds, in order. A flat list, not a writer map: who writes a
 * section is a fact about the tree (grep `es_manifest_record( '<name>'` call sites), not something
 * this function should assert.
 *
 * `pages` is written by `elementor-core` step 8 (slug => id); `site` by that same step
 * (`front_page_id`) and read back by `es_manifest_verify()`. `build` holds what the site was built
 * WITH (`es_build_fingerprint()`), written by step 8 alongside `pages`; it lets a later check tell
 * whether production runs the same PHP, WordPress, Elementor and Elementor Pro as the QA pass.
 *
 * `design` and `delivery` were retired: nothing wrote or read them. A manifest stored before that
 * still carries them; reading ignores them and recording another section leaves them in place.
 */
function es_manifest_sections() {
	return array( 'site', 'pages', 'build' );
}

/**
 * Drop the token cache so the NEXT build starts from the documented defaults.
 *
 * `es_tokens()` caches in `static $t` and recomputes only when `$override` is truthy, and `array()` is
 * falsy: a second build in the same process asking for `es_tokens( array() )` silently gets the
 * PREVIOUS build's palette. That bites when builds are chained and breaks the premise replay rests on
 * (output must not depend on what ran before it in the process). `es_tokens( $defaults )` works only
 * for a caller holding a copy of the defaults (tests/test-write-path.php restores that way); this
 * needs none.
 */
function es_tokens_reset() {
	return es_tokens( array(), true );
}

/**
 * What this site was built WITH — the one fact a replay cannot re-derive later.
 *
 * Replaying a finished site against a second target reproduces it only when the SAME library emitted
 * both. Element ids are `md5( $seed . '-' . $n )` and stable by construction, so a library change
 * between the local build and the production replay leaves plausible ids over a moved layout.
 *
 * The library's identity is read from the library, never declared beside it: `sha1_file()` on
 * `__FILE__` cannot disagree with the running code, where a hand-kept version constant drifts. A
 * version this cannot read is recorded as `unknown`, never a plausible default (two invented `3.0.0`s
 * would MATCH and declare identical a pair nobody checked). `es_save_page()` does carry a `3.0.0`
 * fallback because Elementor expects that meta; a fingerprint expects nothing, it reads.
 */
function es_build_fingerprint() {
	return array(
		'library_sha1' => sha1_file( __FILE__ ),
		'php'          => PHP_VERSION,
		'wp'           => isset( $GLOBALS['wp_version'] ) ? (string) $GLOBALS['wp_version'] : 'unknown',
		'elementor'    => defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : 'unknown',
		'elementor_pro' => defined( 'ELEMENTOR_PRO_VERSION' ) ? ELEMENTOR_PRO_VERSION : 'unknown',
	);
}

/**
 * Record one section, stamped, and READ IT BACK.
 *
 * Merges into the named section only — never the whole manifest — and returns false when the
 * write did not land. `update_option()` returns false both on failure and on an unchanged value,
 * so its boolean is not evidence in either direction; the re-read is.
 */
function es_manifest_record( $section, array $data ) {
	$manifest = es_manifest_read();
	$stamp    = gmdate( 'Ymd-His' );

	$manifest['schema']               = 1;
	$manifest['updated']              = $stamp;
	$manifest['sections'][ $section ] = array(
		'at'   => $stamp,
		'data' => $data,
	);
	update_option( 'es_novamira_manifest', $manifest );

	$back = es_manifest_read();
	if ( ! isset( $back['sections'][ $section ]['data'] ) || $back['sections'][ $section ]['data'] !== $data ) {
		es_warn(
			'el manifiesto NO se guardo: la seccion "' . $section . '" no esta o no coincide al releerla. '
			. 'La proxima sesion va a empezar sin saber nada de esta. Revisa permisos o una cache de opciones.'
		);
		return false;
	}

	return true;
}

/**
 * Check the manifest against the site it claims to describe.
 *
 * Between sessions a page can be deleted, renamed by hand, replaced by a plugin import, or repointed
 * as the front page. Reads the `pages` section (`slug => post_id`) and the `site` section's
 * `front_page_id`, and reports DRIFT rather than repairing it: only a human knows which truth was
 * intended.
 *
 * The lines state what was OBSERVED and never why. A key that is not a slug at all (`front` written
 * into the `pages` map, where the front page does not belong; its home is `site`'s `front_page_id`)
 * once made a confident "somebody moved it" diagnosis wrong, so each row gives the two facts side by
 * side and leaves the inference to the reader.
 *
 * Returns a list of human-readable drift lines, empty when the manifest still matches.
 */
function es_manifest_verify() {
	$manifest = es_manifest_read();
	$pages    = isset( $manifest['sections']['pages']['data'] ) ? (array) $manifest['sections']['pages']['data'] : array();
	$drift    = array();

	foreach ( $pages as $slug => $id ) {
		$id   = (int) $id;
		$page = es_page_by_slug( $slug );
		if ( ! $page ) {
			/* By ID and STATUS, never by slug: WordPress trashes a page by renaming its slug to
			   `<slug>__trashed` (measured on 7.1), so the slug lookup fails and the live slug looks
			   like somebody renamed it. */
			if ( 'trash' === (string) get_post_field( 'post_status', $id ) ) {
				$drift[] = 'la pagina "' . $slug . '" (#' . $id . ') esta en la papelera: se movio alli desde la ultima sesion. Restaurala o '
					. 'corrige el manifiesto; el framework no lo decide';
				continue;
			}
			$real = (string) get_post_field( 'post_name', $id );
			$drift[] = '' !== $real
				? 'el manifiesto llama "' . $slug . '" a la pagina #' . $id . ', y el slug vivo de esa pagina es "' . $real
					. '". Los dos hechos, sin conclusion: puede que la renombraran fuera de este framework, o que lo anotado nunca fuera un slug'
				: 'la pagina "' . $slug . '" (#' . $id . ') ya no existe: borrada del todo desde la ultima sesion';
			continue;
		}
		if ( (int) $page->ID !== $id ) {
			$drift[] = '"' . $slug . '" ahora es la pagina #' . $page->ID . ', no la #' . $id . ' que dice el manifiesto: la de antes se borro y se creo otra en su lugar';
		}
	}

	$recorded_front = isset( $manifest['sections']['site']['data']['front_page_id'] )
		? (int) $manifest['sections']['site']['data']['front_page_id']
		: null;
	if ( null !== $recorded_front ) {
		$live = es_front_page();
		if ( $live['id'] !== $recorded_front ) {
			$drift[] = 'la portada era la #' . $recorded_front . ' y ahora ' . ( $live['id'] ? 'es la #' . $live['id'] : 'el sitio muestra el blog' );
		}
	}

	if ( $drift ) {
		es_warn(
			'el manifiesto ya no describe este sitio (' . count( $drift ) . '): ' . implode( ' | ', $drift )
			. '. NO se corrige solo a proposito: solo un humano sabe cual de las dos versiones es la buena.'
		);
	}

	return $drift;
}

/**
 * Where the sandbox lives. One definition, so nothing hand-builds this path.
 *
 * Every `.php` dropped in here EXECUTES on upload — that is how this framework runs at all, and
 * it is also why leaving files behind matters: they stay executable on the client's site forever,
 * reachable by anyone who guesses the URL, long after anybody remembers writing them.
 */
function es_sandbox_dir() {
	return ( defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : '' ) . '/novamira-sandbox';
}

/**
 * Is the sandbox actually RUNNING, or has it switched itself off?
 *
 * Read from the Novamira loader's own source. It globs `*.php` in the sandbox and `require_once`s
 * every one on EVERY request (not "on upload"), but first does:
 *
 *     $is_safe_mode = file_exists( $crashed_file );
 *     if ( $is_safe_mode ) { return; }
 *
 * So a single `.crashed` file disables the WHOLE sandbox, silently; the only notice is an
 * admin_notices banner an agent working through the connector never sees. On a site carrying
 * `.crashed` NO `es_*` function is defined and every build dies on "undefined function" with nothing
 * explaining why (measured on two live sites).
 *
 * When the sandbox IS in safe mode this function is not loaded either, so `project-context` reads the
 * same file directly, before any of this library exists. This one covers a crash that happens after
 * the library is running, and the delivery phase, which must not hand over a site whose sandbox is
 * quietly off.
 *
 * Returns `array( 'safe_mode' => bool, 'reason' => string|null, 'files' => array )`.
 */
function es_sandbox_state() {
	$dir     = es_sandbox_dir();
	$crashed = $dir . '/.crashed';
	$reason  = null;

	if ( file_exists( $crashed ) ) {
		$raw = (string) @file_get_contents( $crashed );
		$rec = json_decode( $raw, true );
		if ( is_array( $rec ) ) {
			$reason = ( isset( $rec['sandbox_file'] ) ? basename( (string) $rec['sandbox_file'] ) . ': ' : '' )
				. ( isset( $rec['message'] ) ? substr( (string) $rec['message'], 0, 200 ) : 'sin mensaje' );
		} else {
			$reason = '' !== $raw ? substr( $raw, 0, 200 ) : 'el fichero .crashed esta vacio';
		}
		es_warn(
			'el sandbox esta en MODO SEGURO: existe ' . $dir . '/.crashed, asi que el cargador de Novamira NO carga '
			. 'NINGUN fichero del sandbox. Causa registrada: ' . $reason . '. Nada de lo que subas se va a ejecutar. '
			. 'Arregla o borra el fichero culpable ANTES de borrar .crashed — borrarlo sin mas vuelve a cargarlo y a tumbar el sitio otra vez.'
		);
	}

	return array(
		'safe_mode' => null !== $reason,
		'reason'    => $reason,
		'files'     => es_sandbox_report(),
	);
}

/**
 * What is still sitting in the sandbox?
 *
 * Returns a sorted list of filenames, `array()` when empty or absent. This is the READ that makes
 * "I cleaned up" checkable: the delivery phase must show what remains, not assert it is nothing.
 */
function es_sandbox_report() {
	$dir = es_sandbox_dir();
	if ( ! is_dir( $dir ) ) {
		return array();
	}
	$left = array();
	foreach ( (array) scandir( $dir ) as $entry ) {
		if ( '.' === $entry || '..' === $entry ) {
			continue;
		}
		$left[] = $entry;
	}
	sort( $left );

	return $left;
}

/**
 * Delete the build scripts from the sandbox, then READ IT BACK and report what survived.
 *
 * Everything this framework uploads is executable PHP on a live site (helpers, page builders,
 * whatever was pasted in to debug); none is needed once the pages exist. The return value is what is
 * STILL THERE, never what was deleted: a delete that failed on permissions and one that worked look
 * identical from the return of unlink(). An empty array is the only proof of an empty sandbox.
 *
 * Scoped hard: only regular files DIRECTLY inside the sandbox, only when realpath() still resolves
 * inside it, only the extensions this framework uploads. It never recurses, never follows a link out,
 * never removes the directory itself.
 */
function es_sandbox_purge() {
	/* The preflight approvals are build state, not site state: they must not stay on the client's site
	   or travel in an export. Read back like every delete here. */
	delete_option( 'es_preflight_slugs' );
	if ( false !== get_option( 'es_preflight_slugs' ) ) {
		es_warn( 'la opcion es_preflight_slugs (aprobaciones del build) no se pudo borrar y se queda en el sitio. Borrala a mano antes de entregar.' );
	}
	$dir  = es_sandbox_dir();
	$real = realpath( $dir );
	if ( ! $real || ! is_dir( $real ) ) {
		return array();
	}
	foreach ( es_sandbox_report() as $entry ) {
		$path = $real . DIRECTORY_SEPARATOR . $entry;
		$rp   = realpath( $path );
		if ( ! $rp || 0 !== strpos( $rp, $real . DIRECTORY_SEPARATOR ) || ! is_file( $rp ) ) {
			continue;   /* a link pointing out, or a subdirectory: not ours to touch */
		}
		$ext = strtolower( pathinfo( $rp, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'php', 'log', 'txt', 'json' ), true ) ) {
			continue;
		}
		if ( es_sandbox_runtime_hooks( $rp ) ) {
			continue;   /* not scaffolding: it runs on every visit. See below. */
		}
		@unlink( $rp );
	}
	clearstatcache();
	$left = es_sandbox_report();
	if ( $left ) {
		$live = array();
		foreach ( $left as $entry ) {
			$hooks = es_sandbox_runtime_hooks( $real . DIRECTORY_SEPARATOR . $entry );
			if ( $hooks ) {
				$live[] = $entry . ' (' . implode( ', ', $hooks ) . ')';
			}
		}
		es_warn(
			'el sandbox NO quedo vacio: siguen ahi ' . implode( ', ', $left ) . '. Todo .php que quede en '
			. 'wp-content/novamira-sandbox/ se ejecuta y es alcanzable por URL en el sitio del cliente. '
			. 'Borralos a mano antes de entregar.'
			. ( $live
				? ' OJO, esto NO se borro a proposito porque registra hooks de WordPress y por tanto corre en cada '
					. 'visita, no es andamio de build: ' . implode( '; ', $live ) . '. Borrarlo cambia el sitio. '
					. 'Muevelo al tema hijo y borralo de aqui despues, nunca antes.'
				: '' )
		);
	}

	return $left;
}

/**
 * Does this sandbox file register WordPress hooks — that is, does it RUN on every visit?
 *
 * A hooking file is not build scaffolding (a real client's `template_redirect` hook wrapped every page
 * in a `<main>` landmark the theme lacks; deleting it on hand-off would have silently removed the
 * site's accessibility). So it joins what the purge refuses to touch, beside subdirectories and
 * unknown extensions: it still BLOCKS delivery, it just needs a human. The fix is to move it into the
 * child theme (this framework writes PHP outside the sandbox only with explicit human authorization obtained beforehand, naming the exact file and destination, and otherwise hands the move to a person) and delete it here afterwards, never before.
 *
 * Detected by reading the source, not loading it (loading would be a side effect inside a report).
 * Comment lines are skipped, the same rule the audit uses.
 *
 * Returns the hook names found, so the warning can NAME them; an empty array means safe to delete.
 */
function es_sandbox_runtime_hooks( $path ) {
	$src = is_file( $path ) ? (string) @file_get_contents( $path ) : '';
	if ( '' === $src ) {
		return array();
	}
	$found = array();
	foreach ( explode( "\n", str_replace( "\r\n", "\n", $src ) ) as $line ) {
		if ( preg_match( '#^\s*(\*|//|/\*|\#)#', $line ) ) {
			continue;
		}
		if ( preg_match_all( '/(?<![\w>$])(add_action|add_filter|register_activation_hook|register_shutdown_function)\s*\(\s*[\'"]([^\'"]+)/', $line, $m ) ) {
			foreach ( $m[2] as $hook ) {
				$found[ $hook ] = true;
			}
		}
	}

	return array_keys( $found );
}

/**
 * Every backup key this framework has parked on a set of posts.
 *
 * Handing these over is the difference between "there is a backup" and "here is how to restore
 * it". They are never pruned, so a long-lived page has one per rebuild, newest last.
 *
 * Returns `array( post_id => array( key, … ) )`, omitting posts with none.
 */
function es_backup_keys( array $post_ids ) {
	$out = array();
	foreach ( $post_ids as $id ) {
		$keys = array();
		foreach ( (array) get_post_meta( $id ) as $key => $value ) {
			if ( 0 === strpos( (string) $key, '_es_page_backup_' ) ) {
				$keys[] = $key;
			}
		}
		if ( $keys ) {
			sort( $keys );
			$out[ (int) $id ] = $keys;
		}
	}

	return $out;
}

/**
 * Does WordPress currently allow this site to be indexed?
 *
 * `blog_public` = 0 is the "discourage search engines" switch; staging sites are built with it on and
 * the delivered site stays invisible for weeks. It is one option and it decides whether any SEO work
 * matters.
 *
 * Scope: this reads THAT OPTION only. It reports whether a PHYSICAL robots.txt exists (it overrides the
 * virtual one) but does NOT parse it (user-agent groups, wildcards and Allow precedence make a
 * half-parser a confident wrong answer); a virtual robots.txt is invisible from disk. Confirming the
 * served state (the page's `<meta name="robots">`, not `/robots.txt`, which a plugin can replace) is
 * `qa-review`'s job, row 23.
 *
 * Returns `array( 'indexable' => bool, 'blog_public' => mixed, 'robots_file' => string|null )`,
 * where `robots_file` is the file's contents when one exists on disk.
 */
function es_indexing_state() {
	$blog_public = get_option( 'blog_public' );
	$robots      = null;
	$path        = ( defined( 'ABSPATH' ) ? ABSPATH : '' ) . 'robots.txt';
	if ( '' !== $path && file_exists( $path ) ) {
		$robots = (string) file_get_contents( $path );
	}

	return array(
		'indexable'   => ( '0' !== (string) $blog_public && '' !== (string) $blog_public ),
		'blog_public' => $blog_public,
		'robots_file' => $robots,
	);
}

/**
 * Move a page from one slug to another, and record where the old URL went.
 *
 * Building the new page at the new slug leaves the OLD page published and indexed (the two compete);
 * changing the slug in place makes every inbound link 404. So this moves the page, VERIFIES the slug
 * actually moved, and stores the old→new pair in the `es_slug_redirects` option.
 *
 * **Nothing in this framework serves that option** (no mu-plugin, no `template_redirect` hook, no
 * rewrite rule). The map is the record a redirect plugin or snippet can be pointed at, and `qa-review`
 * row 17 checks the old URLs against it. Until something reads it the old URL still 404s, and this
 * function says so out loud on every successful move.
 *
 * Returns the page id on a completed move, 0 otherwise.
 */
function es_migrate_slug( $from, $to ) {
	if ( $from === $to ) {
		return 0;
	}
	$page = es_page_by_slug( $from );
	if ( ! $page ) {
		/* Not an error, but not silence either: a migration that finds nothing is almost always a
		   typo in the OLD slug, and a quiet no-op passes for a completed move. */
		es_warn(
			'no hay ninguna pagina en "' . $from . '", asi que no se movio nada. Si esperabas moverla, revisa el slug de origen: '
			. 'un slug mal escrito aqui no falla, simplemente no hace nada.'
		);
		return 0;
	}
	/* Deliberately NOT es_page_by_slug(): the question here is "is ANYTHING holding this slug",
	   which is a different one. WordPress makes a slug unique against the whole space, so an
	   attachment is as much of a blocker as a page — measured on a live install, where a page
	   asking for a slug an attachment held came back renamed with a "-2" suffix. The type is named
	   in the warning because "la pagina #732" for a media item sends the reader hunting in the
	   wrong list. */
	$taken = get_page_by_path( $to, OBJECT, 'page' );
	if ( $taken && (int) $taken->ID !== (int) $page->ID ) {
		$que = isset( $taken->post_type ) && 'page' !== $taken->post_type ? $taken->post_type : 'pagina';
		es_warn(
			'"' . $to . '" ya lo ocupa ' . ( 'pagina' === $que ? 'la pagina' : 'un elemento de tipo "' . $que . '"' ) . ' #' . $taken->ID
			. ', asi que "' . $from . '" (#' . $page->ID . ') NO se movio. '
			. 'Mover encima habria dejado dos cosas peleando por la misma URL y WordPress renombrando una de ellas a lo que le pareciera. '
			. 'Decide cual sobrevive y borra o mueve la otra a mano.'
		);
		return 0;
	}
	$wrote = wp_update_post(
		array(
			'ID'        => $page->ID,
			'post_name' => $to,
		)
	);
	$real = get_post_field( 'post_name', $page->ID );
	if ( is_wp_error( $wrote ) || ! $wrote || $real !== $to ) {
		es_warn(
			'no se pudo mover "' . $from . '" (#' . $page->ID . ') a "' . $to . '"'
			. ( is_wp_error( $wrote ) ? ': ' . $wrote->get_error_message() : '' )
			. '. El slug sigue siendo "' . $real . '". No se registro ninguna redireccion.'
		);
		return 0;
	}

	$map = get_option( 'es_slug_redirects' );
	if ( ! is_array( $map ) ) {
		$map = array();
	}
	$map[ $from ] = $to;
	update_option( 'es_slug_redirects', $map );

	$check = get_option( 'es_slug_redirects' );
	if ( ! is_array( $check ) || ! isset( $check[ $from ] ) || $check[ $from ] !== $to ) {
		es_warn(
			'la pagina se movio a "' . $to . '" pero el mapa de redirecciones no quedo escrito, asi que ni siquiera hay registro de '
			. 'que "' . $from . '" existio. Apuntalo a mano antes de que se pierda.'
		);
		return (int) $page->ID;
	}

	/* On every successful move, without exception. The day something serves the map, this is the
	   line that has to change, and it is easier to find than a silence. */
	es_warn(
		'"' . $from . '" se movio a "' . $to . '" y quedo anotado en la opcion es_slug_redirects. AVISO: nada en este framework '
		. 'SIRVE ese mapa todavia, asi que /' . $from . '/ sigue devolviendo 404 para quien llegue desde Google o desde un enlace viejo. '
		. 'Este framework NO PUEDE cerrarlo solo: solo escribe .php fuera del sandbox con autorizacion humana explicita obtenida antes, y el sandbox se vacia al '
		. 'entregar. Sin esa autorizacion lo cierra una persona, de dos maneras: un plugin de redirecciones, o el mu-plugin de 15 lineas que lee esta '
		. 'misma opcion, copiado tal cual de elementor-core/references/knowledge.md ("Servir es_slug_redirects"). '
		. 'Comprobalo despues con la fila 17 de qa-review, que hasta entonces FALLA a proposito.'
	);

	return (int) $page->ID;
}

/**
 * Cross the slugs a build is about to write against what is already on the site.
 *
 * This is the report a human approves BEFORE the connector is handed a single write. It prints as well
 * as returning, and its printing is NOT gated on `ES_AUDIT_SILENT` (an approval artifact is not
 * routine output).
 *
 * Returns `array( 'rows' => [...], 'overwrites' => int, 'creates' => int )`. Each row carries
 * `slug`, `id`, `action` (`create`|`overwrite`), `status`, `is_elementor`, `is_front_page` and
 * `converts` -- the last two being the ones that cost the most and show up the least.
 */
function es_overwrite_preflight( array $slugs ) {
	$front = es_front_page();
	$rows  = array();
	$over  = 0;
	$make  = 0;

	foreach ( $slugs as $slug ) {
		if ( ! is_string( $slug ) || '' === $slug ) {
			es_warn( 'el preflight ignora un slug vacio o que no es texto: no puede aprobarse ni escribirse.' );
			continue;
		}
		$page = es_page_by_slug( $slug );
		if ( ! $page ) {
			$rows[] = array(
				'slug'          => $slug,
				'id'            => 0,
				'action'        => 'create',
				'status'        => '',
				'is_elementor'  => false,
				'is_front_page' => false,
				'converts'      => false,
			);
			$make++;
			continue;
		}
		$is_elementor = 'builder' === get_post_meta( $page->ID, '_elementor_edit_mode', true );
		$body         = trim( (string) get_post_field( 'post_content', $page->ID ) );
		$rows[]       = array(
			'slug'          => $slug,
			'id'            => (int) $page->ID,
			'action'        => 'overwrite',
			'status'        => $page->post_status,
			'is_elementor'  => $is_elementor,
			'is_front_page' => ( 'page' === $front['mode'] && $front['id'] === (int) $page->ID ),
			'converts'      => ( ! $is_elementor && '' !== $body ),
		);
		$over++;
	}

	$out = 'WordPress Orchestrator preflight de escritura: ' . count( $rows ) . ' slugs — ' . $over . ' se pisan, ' . $make . ' se crean';
	foreach ( $rows as $row ) {
		if ( 'create' === $row['action'] ) {
			$out .= "\n  CREA       " . $row['slug'];
			continue;
		}
		$out .= "\n  " . ( $row['converts'] ? 'CONVIERTE ' : 'PISA      ' ) . $row['slug']
			. ' #' . $row['id'] . ' ' . $row['status']
			. ' ' . ( $row['is_elementor'] ? 'Elementor' : 'clasica' )
			. ( $row['is_front_page'] ? '  [PORTADA — es lo que ve el visitante al entrar]' : '' )
			. ( $row['converts'] ? '  [su contenido actual deja de renderizarse]' : '' );
	}
	if ( ! $over ) {
		$out .= "\n  nada que pisar: ninguno de estos slugs existe todavia";
	}
	error_log( str_replace( "\n", ' | ', $out ) );
	echo $out . "\n";

	/* Recorded AFTER the block is printed, never before. The approval artifact is the text a human
	   read, so a preflight that dies partway through approves nothing — the same reason every write
	   in this file is read back instead of trusted to its return value. */
	$seen = array();
	foreach ( $rows as $row ) {
		$seen[ $row['slug'] ] = $row['id'];   /* the id the human saw; 0 = the page did not exist */
	}
	es_preflight_record( $seen, false );

	return array(
		'rows'       => $rows,
		'overwrites' => $over,
		'creates'    => $make,
	);
}

/**
 * The preflight for theme parts (header, footer, templates): what `es_overwrite_preflight()` is for
 * pages. A theme part applies to EVERY page its conditions match, so it is the write with the
 * widest reach and the same rule: nobody approves what they were not shown.
 *
 * `$parts` is `array( slug => array( new display conditions ) )`. Prints, per part, CREA or PISA,
 * the existing template id, the conditions it has now against the new ones, and the other templates
 * registered at the same locations (`es_theme_location_rivals()`). Records `tpl:<slug> => id seen`
 * for `es_save_theme_part()`. Returns `array( 'rows' => [...], 'overwrites' => int, 'creates' => int )`.
 */
function es_theme_part_preflight( array $parts ) {
	$rows = array();
	$out  = '';
	$over = 0;
	$make = 0;
	$seen = array();
	foreach ( $parts as $slug => $conditions ) {
		if ( ! is_string( $slug ) || '' === $slug ) {
			es_warn( 'el preflight de theme parts ignora un slug vacio o que no es texto.' );
			continue;
		}
		$conditions = array_map( 'strval', (array) $conditions );
		$id         = es_gate_seen_id( 'tpl:' . $slug );
		$seen[ 'tpl:' . $slug ] = $id;
		if ( ! $id ) {
			$make++;
			$rows[] = array( 'slug' => $slug, 'id' => 0, 'action' => 'create', 'conditions' => array(), 'new_conditions' => $conditions, 'rivals' => array() );
			$out   .= "\n  CREA       " . $slug . '  condiciones: ' . implode( ', ', $conditions );
			continue;
		}
		$over++;
		$now    = get_post_meta( $id, '_elementor_conditions', true );
		$now    = is_array( $now ) ? array_map( 'strval', $now ) : array();
		$rivals = es_theme_location_rivals( $id );
		$rows[] = array( 'slug' => $slug, 'id' => $id, 'action' => 'overwrite', 'conditions' => $now, 'new_conditions' => $conditions, 'rivals' => $rivals );
		$out   .= "\n  PISA       " . $slug . ' #' . $id . '  condiciones ahora: ' . ( $now ? implode( ', ', $now ) : '(ninguna)' )
			. ' -> nuevas: ' . implode( ', ', $conditions );
		foreach ( $rivals as $location => $others ) {
			$out .= "\n             otras plantillas en " . $location . ': #' . implode( ', #', $others );
		}
	}
	$out = 'WordPress Orchestrator preflight de theme parts: ' . count( $rows ) . ' — ' . $over . ' se pisan, ' . $make . ' se crean' . $out;
	error_log( str_replace( "\n", ' | ', $out ) );
	echo $out . "\n";
	es_preflight_record( $seen, true );

	return array(
		'rows'       => $rows,
		'overwrites' => $over,
		'creates'    => $make,
	);
}

/**
 * Is this build writing into a site whose sandbox is switched off?
 *
 * `.crashed` disables the loader, not an explicit `require_once`, so a build survives it: every page
 * can be written and reported done on a site left degraded. `project-context` step 8 REPORTS safe
 * mode; this makes the build itself say so.
 *
 * Once per request, unlike the per-slug approval check: an unapproved write is a fact about ONE page,
 * while safe mode is one fact about the SITE and repeating it per page would bury the pages. Warns
 * rather than refuses, because the way out of a crashed sandbox is to run something and a guard that
 * blocks writes blocks the repair too. It must never stay quiet: an agent on MCP never sees the
 * wp-admin banner.
 *
 * Returns the reason when safe mode is on, `''` otherwise, so a caller can read the verdict without
 * parsing stdout.
 */
function es_safe_mode_check() {
	static $said = false;
	$crashed = es_sandbox_dir() . '/.crashed';
	if ( ! file_exists( $crashed ) ) {
		return '';
	}
	$raw    = trim( (string) @file_get_contents( $crashed ) );
	$rec    = json_decode( $raw, true );
	$reason = is_array( $rec )
		? ( isset( $rec['sandbox_file'] ) ? basename( (string) $rec['sandbox_file'] ) : 'fichero sin nombrar' )
		: ( '' !== $raw ? substr( $raw, 0, 120 ) : 'el fichero .crashed esta vacio' );
	if ( ! $said ) {
		$said = true;
		es_warn(
			'ESTE BUILD ESTA ESCRIBIENDO CON EL SANDBOX APAGADO. Existe .crashed (' . $reason . '), asi que el cargador '
			. 'de Novamira no ejecuta NINGUN fichero del sandbox por su cuenta: lo que se construya hoy depende de que '
			. 'alguien vuelva a requerir estos ficheros a mano, y el fallo que dejo el sitio asi sigue sin arreglarse. '
			. 'Arregla o borra el fichero culpable ANTES de quitar .crashed — quitarlo sin mas vuelve a cargarlo y a tumbar el sitio.'
		);
	}

	return $reason;
}

/**
 * Was THIS slug in a block somebody was shown?
 *
 * `es_overwrite_preflight()` prints the approval artifact; this makes the runtime check it. A build
 * that preflighted five slugs and wrote six would leave the sixth unapproved and unseen.
 *
 * Per slug, not once per request: a per-request flag falls silent after the first warning and hides
 * the unapproved write that follows.
 *
 * It BLOCKS: `es_save_page()` and `es_save_theme_part()` write nothing for a key this says no to.
 * The record is the option `es_preflight_slugs` (`slug => id the human saw`, 0 = it did not exist;
 * `tpl:<slug>` for a theme part), not a variable, because every connector call is a new PHP request
 * and the human's yes arrives between the preflight and the build. What was approved is what was
 * SHOWN: if the page found at the slug now is not the one the preflight saw, the site changed and
 * the answer is no. Resuming an interrupted build needs no override: the keys still unwritten are
 * still approved, and one that already landed is preflighted again, which is cheap and shows the
 * human the page as it is now. A corrected page after an audit verdict is the same case.
 *
 * Returns the verdict so a caller (or a test) can read it without parsing stdout.
 */
function es_approval_check( $slug ) {
	if ( ! is_string( $slug ) || '' === $slug ) {
		es_warn( 'se pidio escribir un slug vacio o que no es texto: NO se escribio nada.' );

		return false;
	}
	$fn   = 0 === strpos( $slug, 'tpl:' ) ? 'es_theme_part_preflight()' : 'es_overwrite_preflight()';
	$seen = es_preflight_approved();
	if ( ! array_key_exists( $slug, $seen ) ) {
		es_warn(
			'"' . $slug . '" NO se escribio: no paso por ' . $fn . ', asi que nadie ha visto el bloque que dice si ya existe, '
			. 'si es la portada, o si su contenido actual deja de renderizarse. Corre ' . $fn . ' con los slugs que faltan, '
			. 'ensena el bloque y vuelve a guardar. Si el build se interrumpio, o corregiste una pagina ya guardada tras el '
			. 'veredicto, es lo mismo: cada escritura necesita un preflight nuevo de ese slug.'
		);

		return false;
	}
	if ( es_gate_seen_id( $slug ) !== (int) $seen[ $slug ] ) {
		es_warn(
			'"' . $slug . '" NO se escribio: el sitio cambio desde el preflight (lo que hay en ese slug ya no es lo que se enseño). '
			. 'Corre ' . $fn . ' otra vez, ensena el bloque nuevo y vuelve a guardar.'
		);

		return false;
	}

	return true;
}

/** What the preflight recorded and no write has spent yet: `key => id seen`. */
function es_preflight_approved() {
	$list = get_option( 'es_preflight_slugs' );

	return is_array( $list ) ? $list : array();
}

/** The id found NOW for a gate key: a page for a plain slug, a template for `tpl:<slug>`. 0 = none. */
function es_gate_seen_id( $key ) {
	if ( 0 === strpos( $key, 'tpl:' ) ) {
		$found = get_posts(
			array(
				'post_type'      => 'elementor_library',
				'name'           => substr( $key, 4 ),
				'posts_per_page' => 1,
				'post_status'    => 'any',
			)
		);

		return $found ? (int) $found[0]->ID : 0;
	}
	$page = es_page_by_slug( $key );

	return $page ? (int) $page->ID : 0;
}

/**
 * Store one kind of preflight (pages, or theme parts), REPLACING the earlier entries of that kind:
 * the run's slugs go in one call, so what an earlier build or session approved cannot linger. The
 * other kind is kept. Read back: an approval that did not land makes every later save refuse.
 */
function es_preflight_record( array $seen, $theme_parts ) {
	$keep = array();
	foreach ( es_preflight_approved() as $key => $id ) {
		if ( ( 0 === strpos( (string) $key, 'tpl:' ) ) !== $theme_parts ) {
			$keep[ $key ] = $id;
		}
	}
	$map = $keep + $seen;
	update_option( 'es_preflight_slugs', $map );
	if ( get_option( 'es_preflight_slugs' ) !== $map ) {
		es_warn(
			'el preflight se imprimio pero la opcion es_preflight_slugs no quedo escrita, asi que el guardado va a rechazar estos slugs. '
			. 'Revisa permisos o un plugin que filtre las opciones.'
		);
	}
}

/** A write landed: its approval is spent (one preflight, one write). Read back, like every write here. */
function es_preflight_spend( $key ) {
	$seen = es_preflight_approved();
	unset( $seen[ $key ] );
	update_option( 'es_preflight_slugs', $seen );
	if ( array_key_exists( $key, es_preflight_approved() ) ) {
		es_warn(
			'"' . $key . '" se escribio, pero su aprobacion sigue en la opcion es_preflight_slugs y no se pudo gastar: '
			. 'otra escritura del mismo slug pasaria sin un preflight nuevo. Revisa permisos o una cache de opciones.'
		);
	}
}

/**
 * Did a build that made pages leave WordPress serving the blog at `/`?
 *
 * Makes a forgotten `es_set_front_page()` visible: `es_audit_summary()` is the one line the operator
 * is told to read before deploying.
 *
 * Fires only when this run SAVED pages and `/` still serves the blog. It does not judge WHICH page is
 * the front page on a site that already has one (the options say which, never whether it is right,
 * and an audit that complains about every correct existing site gets scrolled past).
 *
 * Returns `'nothing-built'`, `'page'` or `'posts'` — the verdict, not the fact that it ran.
 */
function es_front_page_check() {
	global $es_saved_pages;

	if ( ! isset( $es_saved_pages ) || ! is_array( $es_saved_pages ) || ! $es_saved_pages ) {
		return 'nothing-built';
	}
	if ( 'page' === es_front_page()['mode'] ) {
		return 'page';
	}
	es_warn(
		'este build guardo ' . count( $es_saved_pages ) . ' pagina(s) y WordPress sigue sirviendo el BLOG en "/". Ninguna '
		. 'de ellas es la portada, asi que quien entre por la raiz no vera nada de lo que se acaba de construir. Llama a '
		. 'es_set_front_page("<slug>") con la home y relee lo que devuelve.'
	);

	return 'posts';
}

/**
 * Does anything on this site actually SERVE the families `es_tokens()` names?
 *
 * `font_head` and `font_body` are written into every heading and paragraph as `typography_font_family`,
 * but nothing in the framework makes those families exist on the site (no `@font-face`, no enqueue;
 * the mockups decline it because the Artifact CSP blocks external requests). Sizes move correctly while
 * the typeface may never arrive and every gate stays green.
 *
 * WHAT IT CAN HONESTLY SEE:
 *
 *   - A self-hosted family. Asked of WordPress: `get_post_types()` is filtered for names containing
 *     "font" and every published post in those types is read for its title (Elementor Pro's Custom
 *     Fonts is one; so is any custom-fonts plugin that stores a family as a post). The type name is
 *     DERIVED, never enumerated: a constant from one plugin is wrong for the next, and a probe that
 *     silently matches nothing reports a clean site.
 *   - Google's CDN, only as an ENQUEUE. Read from `$GLOBALS['wp_styles']` DIRECTLY, never through
 *     `wp_styles()` (it instantiates the registry as a side effect; a report may not change what it
 *     reports on). What counts is a handle this request put in `queue` or already printed into `done`,
 *     plus transitively their `deps` (WordPress prints those without queueing them), with the src read
 *     from `registered`. A REGISTRATION proves nothing: core registers `open-sans` against
 *     `fonts.googleapis.com` on every installation and enqueues it nowhere, so scanning `registered`
 *     accused every site.
 *
 * WHAT IT CANNOT SEE: a build runs in a REST/CLI request where the front end's `wp_enqueue_scripts`
 * never fires, so nothing is enqueued (measured: `queue_size` 0) and "does this site ask Google for
 * the font?" has no answer from inside a build. That is reported as `sin-confirmar` and WARNS: not a
 * pass and not a finding. So `'alojada'` needs BOTH halves: the families installed AND a front end
 * that was actually looked at.
 *
 * Values that are not families are skipped: a generic stack (`serif`, `system-ui`) or a web-safe face
 * needs no serving path.
 *
 * Returns `'sin-wordpress'` (no site to ask), `'sin-familias'` (the tokens name only generic or
 * web-safe faces), `'alojada'`, `'google'` or `'sin-confirmar'` — the verdict, not the fact that it
 * ran.
 *
 * The once-per-build latch is a GLOBAL, not a `static` like `es_safe_mode_check()`'s, deliberately: it
 * is per-build state like `$es_saved_pages`, and a static cannot be reset, so
 * a suite could observe the warning or its silence, never both.
 */
function es_font_serving_check() {
	global $es_font_said;

	if ( ! isset( $es_font_said ) ) {
		$es_font_said = false;
	}

	/* Not "I looked and found nothing": there is no WordPress here to look at. `get_post_types()` is
	   core and always loaded on a real site, so its absence means this is a dump or a test harness,
	   not a site with a missing font. The two are different facts and only one of them is a
	   warning. */
	if ( ! function_exists( 'get_post_types' ) || ! function_exists( 'get_posts' ) ) {
		return 'sin-wordpress';
	}

	$familias = es_font_declared();

	if ( ! $familias ) {
		return 'sin-familias';
	}

	$tipos = array();
	foreach ( get_post_types( array(), 'names' ) as $tipo ) {
		if ( false !== stripos( (string) $tipo, 'font' ) ) {
			$tipos[] = $tipo;
		}
	}
	$instaladas = array();
	if ( $tipos ) {
		$posts = get_posts(
			array(
				'post_type'   => $tipos,
				'post_status' => 'publish',
				'numberposts' => -1,
			)
		);
		if ( is_array( $posts ) ) {
			foreach ( $posts as $p ) {
				if ( is_object( $p ) && isset( $p->post_title ) ) {
					$instaladas[ strtolower( trim( (string) $p->post_title ) ) ] = true;
				}
			}
		}
	}

	/* AN ENQUEUE IS THE PROOF; A REGISTRATION IS NOT, which is why this block reads two lists.
	   `registered` is what WordPress KNOWS ABOUT; `queue` and `done` are what THIS request asked the
	   browser for. The handles live in `queue`/`done` and the src in `registered`: two halves of one
	   fact. `done` counts as much as `queue`: once styles are printed the queue may be drained and
	   `done` is all that remembers what went out.

	   `$mirado` is the other half: a build runs in a REST/CLI request where the front end's enqueues
	   never fire, so the queue is EMPTY and a probe reading it alone would never fire on any site. An
	   unexercised registry is "I could not look" (the same shape as `sin-wordpress`) and may not be
	   spent as a pass. Absence still proves nothing; now the absence is named. */
	$google = '';
	$mirado = false;
	$reg    = isset( $GLOBALS['wp_styles'] ) ? $GLOBALS['wp_styles'] : null;
	if ( is_object( $reg ) ) {
		$cola    = ( isset( $reg->queue ) && is_array( $reg->queue ) ) ? array_values( $reg->queue ) : array();
		$hechas  = ( isset( $reg->done ) && is_array( $reg->done ) ) ? array_values( $reg->done ) : array();
		$fuentes = ( isset( $reg->registered ) && is_array( $reg->registered ) ) ? $reg->registered : array();
		$mirado  = (bool) ( $cola || $hechas );

		/* A DEPENDENCY IS A REQUEST TOO, and stopping at the queue would have left the hole open one
		   level down. WordPress prints the deps of an enqueued handle without ever putting them in
		   `queue`, so a theme that enqueues its own stylesheet and drags a Google one behind it asks
		   Google for the font on every visit while the queue never mentions it — a false CLEAN,
		   which is the one class of failure worse than the false positive this probe used to be.
		   Walked transitively, because a dep of a dep is the same request. `$vistos` is what makes
		   a cycle terminate: WordPress would not build one, but a plugin with a hand-written deps
		   list can, and a report that hangs stops the build on the very line that exists to warn. */
		$pendientes = array_merge( $cola, $hechas );
		$vistos     = array();
		while ( $pendientes ) {
			$mango = (string) array_shift( $pendientes );
			if ( isset( $vistos[ $mango ] ) ) {
				continue;
			}
			$vistos[ $mango ] = true;
			$hoja             = isset( $fuentes[ $mango ] ) ? $fuentes[ $mango ] : null;
			$src              = ( is_object( $hoja ) && isset( $hoja->src ) ) ? (string) $hoja->src : '';
			if ( false !== stripos( $src, 'fonts.googleapis.com' ) || false !== stripos( $src, 'fonts.gstatic.com' ) ) {
				$google = $mango;
				break;
			}
			if ( is_object( $hoja ) && isset( $hoja->deps ) && is_array( $hoja->deps ) ) {
				foreach ( $hoja->deps as $dep ) {
					$pendientes[] = $dep;
				}
			}
		}
	}

	$faltan = array();
	foreach ( $familias as $cara => $nombre ) {
		if ( ! isset( $instaladas[ $cara ] ) ) {
			$faltan[] = $nombre;
		}
	}
	/* Clean needs BOTH halves answered: the families installed, and a front end that was actually
	   looked at. Installed families say what this site has; only the enqueues say what it sends. */
	if ( ! $faltan && '' === $google && $mirado ) {
		return 'alojada';
	}

	$veredicto = ( '' !== $google ) ? 'google' : 'sin-confirmar';
	if ( ! $es_font_said ) {
		$es_font_said = true;
		if ( 'google' !== $veredicto ) {
			/* Two arms because there are two ways to land here and they send the operator to two
			   different places: a family nobody installed, and a front end nobody could see. Naming
			   the wrong one is how a warning gets scrolled past — arm A sends them to Custom Fonts,
			   arm B to the served HTML, and a run that hits both says both. */
			$dice = '';
			if ( $faltan ) {
				$dice .= 'NO SE PUEDE CONFIRMAR QUE ESTE SITIO SIRVA ' . implode( ' NI ', $faltan ) . '. es_tokens() la escribe '
					. 'en cada titular y cada parrafo de este build, y el build no la instala (es_font_host() la sirve sin Pro): si no esta, el '
					. 'navegador cae al tipo de letra del sistema y el diseno aprobado no es el que ve el cliente. He mirado '
					. 'los tipos de contenido de fuentes registrados (Custom Fonts de Elementor Pro y equivalentes) y ahi no '
					. 'aparece (una cara de es_font_host() no deja ese registro: la prueba es es_front_font_probe()). ';
			}
			if ( ! $mirado ) {
				$dice .= 'NO SE VE QUE CARGA EL FRONT: en esta peticion no hay ni una hoja de estilo encolada ni impresa — un '
					. 'build corre en REST/CLI y los encolados del front nunca se disparan — asi que desde aqui no se puede '
					. 'saber si algo le pide la tipografia al CDN de Google, que es una exposicion legal aparte. Que una hoja '
					. 'este REGISTRADA no vale como prueba: WordPress registra "open-sans" contra fonts.googleapis.com en TODAS '
					. 'las instalaciones y no lo encola en ninguna. Lo prueba el HTML servido: abre la portada y busca '
					. '"googleapis" y "gstatic". ';
			}
			es_warn(
				$dice . 'Esto es un "no lo he podido confirmar", no un "no esta". Si falta, subela AUTOALOJADA — nunca desde '
				. 'el CDN de Google. El procedimiento esta en elementor-core/references/knowledge.md, "Servir las familias '
				. 'tipograficas".'
			);
		} else {
			es_warn(
				'ESTE SITIO CARGA TIPOGRAFIA DESDE EL CDN DE GOOGLE (estilo "' . $google . '"). Eso manda la IP de cada '
				. 'visitante a un tercer pais en cuanto abre la pagina, sin consentimiento y sin base legal, y ya hay '
				. 'sentencias en la UE condenando al titular de la web — no al de Google. Los clientes de este framework son '
				. 'espanoles. Descarga la familia, subela AUTOALOJADA y quita el encolado de Google. El procedimiento esta en '
				. 'elementor-core/references/knowledge.md, "Servir las familias tipograficas".'
			);
		}
	}

	return $veredicto;
}

/**
 * The families the KIT declares, keyed by lower-cased name: `array( 'fraunces' => 'Fraunces' )`.
 *
 * The kit's global typography is what the SITE says and it survives the process (`qa-review` probes
 * from another request, whose `es_tokens()` are defaults). `$tokens` says what to do when the kit
 * declares none: a BUILD (`es_font_serving_check()`) falls back to its own tokens; the PROBE passes
 * false, because a Divi site or a fresh kit declares nothing and the defaults of the calling process
 * are not that site's typefaces. Only the FIRST face of a stack counts; generic and system faces are
 * dropped, they have no file.
 */
function es_font_declared( $tokens = true ) {
	$stacks = array();
	$kit    = function_exists( 'get_option' ) ? (int) get_option( 'elementor_active_kit' ) : 0;
	$ks     = ( $kit && function_exists( 'get_post_meta' ) ) ? get_post_meta( $kit, '_elementor_page_settings', true ) : null;
	if ( is_array( $ks ) && ! empty( $ks['system_typography'] ) && is_array( $ks['system_typography'] ) ) {
		foreach ( $ks['system_typography'] as $slot ) {
			if ( is_array( $slot ) && ! empty( $slot['typography_font_family'] ) ) {
				$stacks[] = (string) $slot['typography_font_family'];
			}
		}
	}
	if ( ! $stacks && $tokens ) {
		foreach ( es_tokens() as $clave => $valor ) {
			if ( 0 === strpos( $clave, 'font_' ) && 'font_fallback' !== $clave && is_string( $valor ) && '' !== trim( $valor ) ) {
				$stacks[] = $valor;
			}
		}
	}
	$familias = array();
	foreach ( $stacks as $valor ) {
		$nombre = trim( explode( ',', $valor )[0], " \t'\"" );
		if ( ! in_array( strtolower( $nombre ), es_font_system_faces(), true ) ) {
			$familias[ strtolower( $nombre ) ] = $nombre;
		}
	}

	return $familias;
}

/**
 * Faces that need no serving path: the generic CSS keywords, and the faces a browser already has.
 *
 * A NAMED system font (Georgia, Segoe UI, Helvetica Neue...) is exempt because it usually ships with
 * the OS, NOT because every device has it: it is not guaranteed, which is what a fallback stack is for.
 *
 * Its own function rather than an inline array so the list is one thing in one place — the same
 * reason `es_token_mixes()` is not an array literal inside `es_tokens()`.
 */
function es_font_system_faces() {
	return array(
		'',
		'ui-serif',
		'ui-sans-serif',
		'ui-monospace',
		'ui-rounded',
		'math',
		'emoji',
		'fangsong',
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
}

/**
 * What does the front end ACTUALLY send to a visitor? The one question a build cannot answer.
 *
 * `es_font_serving_check()` reads the style registry, which a build never populates, so its honest
 * verdict there is `'sin-confirmar'` forever. This reads the SERVED HTML, where the answer lives, and
 * is the only thing here that can clear that warning.
 *
 * A SEPARATE FUNCTION, not a branch of that one: that one is a report and a report may not change the
 * thing it reports on; this makes an HTTP request. It belongs to `qa-review` and is never called from a
 * build: nothing in this asset calls it, which makes it an ENTRY POINT rather than dead weight.
 *
 * THE FALSE CLEAN IS THE WHOLE DIFFICULTY. A 401, a 500, a redirect to a holding page and an empty
 * body all contain zero occurrences of `googleapis`, so "I did not find it" is worth nothing until the
 * bytes are known to be the page's. It demands a 200 AND a body that closes like a document; anything
 * short is `'sin-confirmar'`, never `'limpio'`. A 200 that is a real-looking maintenance page can
 * still fool it, so `'limpio'` is a statement about THE URL IT FETCHED, not the site: `$url` lets
 * `qa-review` walk the pages it built.
 *
 * Two needles, two requests: `fonts.googleapis.com` is the stylesheet, `fonts.gstatic.com` the font
 * file a bad self-hosting job or stray `preconnect` still pulls from the same third country.
 *
 * CANNOT SEE: a font pulled in by JAVASCRIPT at runtime leaves no Google URL in the served HTML, so
 * this says `'limpio'` for a page that asks Google once the script runs. `qa-review` house-rules row
 * 21 (a fresh browser context listing real outbound requests) sees it; this is the cheap pass.
 *
 * No once-per-build latch, unlike `es_font_serving_check()`: this is called deliberately, per URL, by
 * somebody waiting for the answer.
 *
 * Returns `'sin-http'` (no WordPress HTTP API to call), `'google'` (the served page asks Google: proof,
 * and it WARNS), `'limpio'` (a real page at that URL with zero requests to Google) or `'sin-confirmar'`
 * (the request failed, or answered with something that is not the page).
 */
function es_front_font_probe( $url = '' ) {
	if ( ! function_exists( 'wp_remote_get' ) || ! function_exists( 'home_url' ) ) {
		return 'sin-http';
	}

	$url = ( '' !== trim( (string) $url ) ) ? trim( (string) $url ) : (string) home_url( '/' );
	$res = wp_remote_get(
		$url,
		array(
			'timeout'     => 15,
			'redirection' => 3,
		)
	);

	if ( function_exists( 'is_wp_error' ) && is_wp_error( $res ) ) {
		$por = method_exists( $res, 'get_error_message' ) ? (string) $res->get_error_message() : '';
		es_warn(
			'NO SE HA PODIDO LEER ' . $url . ', asi que sigue sin saberse si este sitio le pide la tipografia al CDN de '
			. 'Google: la peticion fallo (' . $por . '). Una peticion que falla NO es un sitio limpio. Miralo desde fuera '
			. '(el HTML servido, buscando "googleapis" y "gstatic") antes de dar el sitio por bueno.'
		);

		return 'sin-confirmar';
	}

	$codigo = function_exists( 'wp_remote_retrieve_response_code' ) ? (int) wp_remote_retrieve_response_code( $res ) : 0;
	$cuerpo = function_exists( 'wp_remote_retrieve_body' ) ? (string) wp_remote_retrieve_body( $res ) : '';

	/* 200 Y documento cerrado, LAS DOS. Un 401 no contiene "googleapis" y un 500 con una pagina de
	   error tampoco, asi que sin las dos condiciones esta funcion aprobaria un sitio detras de un
	   candado por no haber encontrado lo que no podia ver. */
	if ( 200 !== $codigo || false === stripos( $cuerpo, '</html>' ) ) {
		es_warn(
			'LO QUE CONTESTO ' . $url . ' NO ES LA PAGINA (codigo ' . $codigo . ', ' . strlen( $cuerpo ) . ' bytes, sin '
			. 'documento cerrado), asi que no cuenta como haber mirado. Un 401, un 500 o una pagina de mantenimiento '
			. 'tampoco contienen "googleapis": si esto se leyera como limpio, el sitio quedaria aprobado por lo que no se '
			. 'ha podido ver. Arregla el acceso y vuelve a pasarla.'
		);

		return 'sin-confirmar';
	}

	if ( false !== stripos( $cuerpo, 'fonts.googleapis.com' ) || false !== stripos( $cuerpo, 'fonts.gstatic.com' ) ) {
		es_warn(
			'EL HTML QUE SIRVE ' . $url . ' PIDE TIPOGRAFIA AL CDN DE GOOGLE. Esto ya no es una sospecha leida del registro '
			. 'de estilos: son los bytes que recibe el navegador del visitante. Eso manda la IP de cada visitante a un tercer '
			. 'pais en cuanto abre la pagina, sin consentimiento y sin base legal, y ya hay sentencias en la UE condenando al '
			. 'titular de la web — no al de Google. Los clientes de este framework son espanoles. Descarga la familia, subela '
			. 'AUTOALOJADA y quita el encolado. El procedimiento esta en elementor-core/references/knowledge.md, "Servir las '
			. 'familias tipograficas".'
		);

		return 'google';
	}

	/* "Asks nothing of Google" is half of clean; the other half is that the declared families ARRIVE. A
	   site that names Fraunces and serves it from nowhere asks Google for nothing and renders Georgia. */
	$faltan = es_font_unserved( $cuerpo, $url );
	if ( $faltan ) {
		es_warn(
			'EL HTML QUE SIRVE ' . $url . ' NO SIRVE ' . implode( ' NI ', $faltan ) . ': el kit o el CSS de la pagina la declaran y no hay un @font-face '
			. 'alcanzable desde esa pagina con un fichero que conteste 200 con bytes de fuente. El navegador cae al tipo de '
			. 'letra del sistema y el diseno aprobado no es el que ve el visitante, con Google sin tocar. Es un FALLO, no un '
			. 'aviso. Subela AUTOALOJADA (es_font_host(), o el procedimiento de elementor-core/references/knowledge.md, '
			. '"Servir las familias tipograficas").'
		);

		return 'sin-servir';
	}

	return 'limpio';
}

/**
 * Split Additional CSS into the es-fonts rules and everything else (the block removed).
 * Returns `array( $reglas, $resto )`; no block means no rules and the CSS untouched.
 */
function es_font_css_split( $css ) {
	$begin = '/* es-fonts:begin */';
	$end   = '/* es-fonts:end */';
	$a     = strpos( $css, $begin );
	$b     = strpos( $css, $end );
	if ( false === $a || false === $b || $b < $a ) {
		return array( array(), $css );
	}
	$reglas = array_values( array_filter( array_map( 'trim', explode( "\n", substr( $css, $a + strlen( $begin ), $b - $a - strlen( $begin ) ) ) ) ) );
	$antes  = rtrim( substr( $css, 0, $a ) );
	$resto  = ltrim( substr( $css, $b + strlen( $end ) ) );

	return array( $reglas, $antes . ( '' !== $antes && '' !== $resto ? "\n" : '' ) . $resto );
}

/**
 * Put ONE self-hosted face on the site WITHOUT Elementor Pro, without a PHP file and without Google.
 *
 * Custom Fonts is Pro; an `@font-face` in the child theme is PHP outside the sandbox (human
 * authorization first). What needs neither is data: the `woff2` in `uploads/es-fonts/` and the rule in
 * WordPress's own Additional CSS, which core's `wp_update_custom_css_post()` writes and the theme prints
 * on every page.
 *
 * **IT CHANGES THE WHOLE SITE, so run it only after the build's yes.** Elementor's Google Fonts switch
 * (`elementor_google_font`) is turned off for EVERY page, including ones this framework never built, and
 * the Additional CSS of the active theme gains the rules (stored per theme: switching theme drops them
 * until this runs again). The first run records what was there (`es_font_host_previous`: the previous
 * Additional CSS, the previous switch value, the files written) and never overwrites it;
 * `es_font_unhost()` restores it. `es_sandbox_purge()` does not touch that record: the fonts stay on
 * the delivered site, and so does the way to undo them.
 *
 * `$woff2` is the raw bytes (a `wOF2` file; WOFF1 is refused), `$weight` the face's TRUE range
 * (`400` or `400 700`), `$style` `normal` or `italic`, `$family` letters, digits, spaces and hyphens
 * only: all of it ends up in CSS, so anything else is refused and nothing is written. `$licence`, when
 * given, is written beside the font as `<family>-OFL.txt` (the OFL asks for its text to travel with the
 * font; pass the family's `*-OFL.txt` from `skills/html-mockup/assets/fonts/`). Calling it again for the
 * same family, style and weight replaces that rule; the block sits after any leading `@charset` /
 * `@import`. Returns the file's public URL, or false after saying why. Verify with
 * `es_front_font_probe()`.
 */
function es_font_host( $family, $woff2, $weight = '400 700', $style = 'normal', $licence = '' ) {
	$family = (string) $family;
	$slug   = trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( $family ) ), '-' );
	if ( ! preg_match( '/^[A-Za-z0-9 -]+$/D', $family ) || $family !== trim( $family ) || '' === $slug
		|| ! preg_match( '/^\d{3}( \d{3})?$/D', (string) $weight ) || ! in_array( $style, array( 'normal', 'italic' ), true ) ) {
		es_warn( 'es_font_host(): entrada rechazada, no se ha escrito nada. La familia solo admite letras, numeros, espacios y guiones, el peso "400" o "400 700" y el estilo normal o italic: todo ello acaba dentro de CSS.' );

		return false;
	}
	if ( 'wOF2' !== substr( (string) $woff2, 0, 4 ) ) {
		es_warn( 'es_font_host("' . $family . '"): los bytes no son un woff2 (no empiezan por wOF2; WOFF1, un TTF o un 404 guardado como .woff2 no sirven), asi que no se ha escrito nada.' );

		return false;
	}
	$up = wp_upload_dir();
	if ( ! empty( $up['error'] ) || '' === trim( (string) $up['basedir'] ) ) {
		es_warn( 'es_font_host("' . $family . '"): WordPress no tiene un directorio de subidas utilizable (' . ( ! empty( $up['error'] ) ? $up['error'] : 'basedir vacio' ) . '), asi que no se ha escrito nada.' );

		return false;
	}
	$dir   = $up['basedir'] . '/es-fonts';
	$name  = trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( $family . '-' . $style . '-' . $weight ) ), '-' ) . '.woff2';
	$url   = rtrim( $up['baseurl'], '/' ) . '/es-fonts/' . $name;
	$files = array( $name );
	if ( ! wp_mkdir_p( $dir ) || false === file_put_contents( $dir . '/' . $name, $woff2 ) || file_get_contents( $dir . '/' . $name ) !== $woff2 ) {
		es_warn( 'es_font_host("' . $family . '"): no se pudo escribir ' . $dir . '/' . $name . ' (o no se relee igual).' );

		return false;
	}
	if ( '' !== (string) $licence ) {
		$lic = $slug . '-OFL.txt';
		if ( false === file_put_contents( $dir . '/' . $lic, (string) $licence ) ) {
			es_warn( 'es_font_host("' . $family . '"): no se pudo escribir la licencia ' . $dir . '/' . $lic . '.' );
		} else {
			$files[] = $lic;
		}
	}

	/* The record: what was there BEFORE the first run, never overwritten; only the file list grows. */
	$prev        = get_option( 'es_font_host_previous' );
	$google_antes = get_option( 'elementor_google_font' );
	if ( ! is_array( $prev ) ) {
		$prev = array( 'css' => (string) wp_get_custom_css(), 'google' => $google_antes, 'files' => array() );
	}
	$prev['files'] = array_values( array_unique( array_merge( (array) $prev['files'], $files ) ) );
	update_option( 'es_font_host_previous', $prev );

	list( $reglas, $resto ) = es_font_css_split( (string) wp_get_custom_css() );
	$clave   = "@font-face{font-family:'" . $family . "';font-style:" . $style . ';font-weight:' . $weight . ';';
	$reglas  = array_values( array_filter( $reglas, function ( $r ) use ( $clave ) {
		return 0 !== strpos( $r, $clave );
	} ) );
	$reglas[] = $clave . "font-display:swap;src:url('" . $url . "') format('woff2')}";
	$bloque   = "/* es-fonts:begin */\n" . implode( "\n", $reglas ) . "\n/* es-fonts:end */";
	$cabeza   = preg_match( '/\A(?:\s*@(?:charset|import)\b[^;]*;)+/i', $resto, $m ) ? trim( $m[0] ) : '';
	$cola     = ltrim( substr( $resto, strlen( $m[0] ?? '' ) ) );
	$css      = ( '' !== $cabeza ? $cabeza . "\n" : '' ) . $bloque . ( '' !== $cola ? "\n" . $cola : '' );

	$res = wp_update_custom_css_post( $css, array( 'stylesheet' => get_stylesheet() ) );
	if ( ( function_exists( 'is_wp_error' ) && is_wp_error( $res ) ) || ! $res || false === strpos( (string) wp_get_custom_css(), $url ) ) {
		es_warn( 'es_font_host("' . $family . '"): WordPress no guardo el CSS adicional (o no contiene la regla al releerlo), asi que el fichero esta subido y la familia NO esta declarada.' );

		return false;
	}

	update_option( 'elementor_google_font', '0' );
	if ( '0' !== (string) get_option( 'elementor_google_font' ) ) {
		es_warn( 'es_font_host("' . $family . '"): no se pudo apagar elementor_google_font; Elementor puede seguir pidiendo la familia a Google. Apagalo en Elementor > Ajustes > Avanzado.' );
	} elseif ( '0' !== (string) $google_antes ) {
		es_warn(
			'es_font_host(): cambio en TODO el sitio, no solo en esta pagina. Google Fonts de Elementor (elementor_google_font) queda APAGADO para todas las paginas, '
			. 'tambien las que este framework no construyo (valor anterior: ' . ( false === $google_antes ? 'sin valor' : '"' . $google_antes . '"' ) . '), y las reglas @font-face '
			. 'van al CSS adicional del tema activo. Lo anterior queda en la opcion es_font_host_previous (es_sandbox_purge() no la borra: '
			. 'las fuentes se quedan en el sitio entregado). es_font_unhost() lo deshace.'
		);
	}

	return $url;
}

/**
 * Undo `es_font_host()`: remove the es-fonts block from the Additional CSS (anything else in it, edits
 * made since included, stays), restore the previous `elementor_google_font` (no value stays no value),
 * delete the files it wrote and then the record. Returns what it did, or false with no record to read.
 */
function es_font_unhost() {
	$prev = get_option( 'es_font_host_previous' );
	if ( ! is_array( $prev ) ) {
		es_warn( 'es_font_unhost(): no hay registro (es_font_host_previous), asi que no hay nada que deshacer.' );

		return false;
	}
	list( , $resto ) = es_font_css_split( (string) wp_get_custom_css() );
	$res = wp_update_custom_css_post( $resto, array( 'stylesheet' => get_stylesheet() ) );
	if ( ( function_exists( 'is_wp_error' ) && is_wp_error( $res ) ) || ! $res ) {
		es_warn( 'es_font_unhost(): WordPress no guardo el CSS adicional; no se ha tocado nada mas y el registro sigue ahi.' );

		return false;
	}
	if ( false === $prev['google'] ) {
		delete_option( 'elementor_google_font' );
	} else {
		update_option( 'elementor_google_font', $prev['google'] );
	}
	$up      = wp_upload_dir();
	$dir     = $up['basedir'] . '/es-fonts';
	$quitados = array();
	foreach ( (array) $prev['files'] as $f ) {
		if ( is_file( $dir . '/' . $f ) && unlink( $dir . '/' . $f ) ) {
			$quitados[] = $f;
		}
	}
	@rmdir( $dir );
	delete_option( 'es_font_host_previous' );

	return array(
		'css'    => false === strpos( (string) wp_get_custom_css(), '/* es-fonts:begin */' ),
		'google' => $prev['google'],
		'files'  => $quitados,
	);
}

/**
 * Which declared family has NO usable `@font-face` reachable from this served page?
 *
 * DECLARED = the kit's global typography (no token fallback: see `es_font_declared()`) PLUS the first
 * family of every `font-family:` declaration in the page's own CSS (widgets carry their own; `var()`,
 * generic and system faces, and the stylesheets of plugins and core, are skipped). Nothing declared anywhere means nothing to confirm: empty.
 * SERVED = an `@font-face` for it, found in the inline `<style>` and linked stylesheets (the only CSS
 * fetched), whose file is a `data:` URI of font bytes or answers 200 with font magic bytes (a 404 and a
 * theme's soft-404 do not count). Only declared families' files are downloaded.
 *
 * Fetch limits: responses are capped (2 MB), the whole probe has a 30 s budget (what it could not reach
 * counts as unserved, never as served), and a host other than the site's own goes through
 * `wp_safe_remote_get()`. `@import` and JavaScript-loaded faces are not followed.
 */
function es_font_unserved( $html, $url ) {
	$t0    = microtime( true );
	$mio   = function_exists( 'home_url' ) ? parse_url( home_url( '/' ), PHP_URL_HOST ) : parse_url( $url, PHP_URL_HOST );
	$get   = function ( $u ) use ( $t0, $mio ) {
		if ( microtime( true ) - $t0 > 30 ) {
			return null;
		}
		$propio = ( parse_url( $u, PHP_URL_HOST ) === $mio );
		if ( ! $propio && ! function_exists( 'wp_safe_remote_get' ) ) {
			return null;
		}
		$res = $propio ? wp_remote_get( $u, array( 'timeout' => 10, 'limit_response_size' => 2097152 ) )
			: wp_safe_remote_get( $u, array( 'timeout' => 10, 'limit_response_size' => 2097152 ) );
		if ( ( function_exists( 'is_wp_error' ) && is_wp_error( $res ) ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
			return null;
		}

		return (string) wp_remote_retrieve_body( $res );
	};
	$fuente = function ( $bytes ) {
		return in_array( substr( (string) $bytes, 0, 4 ), array( 'wOF2', 'wOFF', 'OTTO', "\0\1\0\0", 'true' ), true );
	};
	$resolver = function ( $ref, $base ) {
		$ref = trim( $ref );
		if ( preg_match( '#^https?://#i', $ref ) ) {
			return $ref;
		}
		$p = parse_url( $base );
		if ( 0 === strpos( $ref, '//' ) ) {
			return ( isset( $p['scheme'] ) ? $p['scheme'] : 'https' ) . ':' . $ref;
		}
		$raiz = ( isset( $p['scheme'] ) ? $p['scheme'] : 'https' ) . '://' . ( isset( $p['host'] ) ? $p['host'] : '' ) . ( isset( $p['port'] ) ? ':' . $p['port'] : '' );
		if ( '/' === substr( $ref, 0, 1 ) ) {
			return $raiz . $ref;
		}
		$dir = isset( $p['path'] ) ? preg_replace( '#/[^/]*$#', '/', $p['path'] ) : '/';

		return $raiz . $dir . $ref;
	};

	/* CSS blocks, each with the URL its relative references resolve against. */
	$bloques = array();
	if ( preg_match_all( '#<style\b[^>]*>(.*?)</style>#is', $html, $m ) ) {
		foreach ( $m[1] as $css ) {
			$bloques[] = array( $css, $url );
		}
	}
	if ( preg_match_all( '#<link\b[^>]*>#i', $html, $m ) ) {
		$n = 0;
		foreach ( $m[0] as $tag ) {
			if ( ! preg_match( '#rel=["\']?stylesheet#i', $tag ) || ! preg_match( '#href=["\']([^"\']+)#i', $tag, $h ) || $n++ >= 40 ) {
				continue;
			}
			$href = $resolver( html_entity_decode( $h[1] ), $url );
			if ( false !== stripos( $href, 'fonts.googleapis.com' ) ) {
				continue;
			}
			$css = $get( $href );
			if ( null !== $css ) {
				$bloques[] = array( $css, $href );
			}
		}
	}

	/* What the page declares: the kit, plus what its CSS USES (outside the @font-face rules). */
	$declaradas = es_font_declared( false );
	foreach ( $bloques as $b ) {
		/* A plugin's or core's own stylesheet uses its icon font (eicons, WooCommerce) whether or not this
		   page loads it: that is not the site's typography. Theme, uploads and inline CSS are. */
		if ( preg_match( '#/wp-(content/plugins|includes)/#', $b[1] ) && $b[1] !== $url ) {
			continue;
		}
		$uso = preg_replace( '#@font-face\s*\{.*?\}#is', '', $b[0] );
		if ( ! preg_match_all( '#font-family\s*:\s*([^;}]+)#i', $uso, $fs ) ) {
			continue;
		}
		foreach ( $fs[1] as $v ) {
			$nombre = trim( preg_replace( '#\s*!important#i', '', explode( ',', $v )[0] ), " \t\r\n'\"" );
			if ( '' === $nombre || false !== strpos( $nombre, '(' ) || in_array( strtolower( $nombre ), es_font_system_faces(), true ) ) {
				continue;
			}
			$declaradas[ strtolower( $nombre ) ] = $nombre;
		}
	}
	if ( ! $declaradas ) {
		return array();
	}

	$servidas = array();
	foreach ( $bloques as $b ) {
		if ( ! preg_match_all( '#@font-face\s*\{(.*?)\}#is', $b[0], $caras ) ) {
			continue;
		}
		foreach ( $caras[1] as $cara ) {
			if ( ! preg_match( '#font-family\s*:\s*([^;}]+)#i', $cara, $f ) ) {
				continue;
			}
			$nombre = strtolower( trim( $f[1], " \t\r\n'\"" ) );
			if ( ! isset( $declaradas[ $nombre ] ) || isset( $servidas[ $nombre ] ) || ! preg_match_all( '#url\(\s*([\'"]?)(.*?)\1\s*\)#is', $cara, $us ) ) {
				continue;
			}
			foreach ( $us[2] as $u ) {
				if ( 0 === stripos( $u, 'data:' ) ) {
					$pos   = strpos( $u, ',' );
					$bytes = ( false !== $pos && false !== stripos( substr( $u, 0, $pos ), 'base64' ) ) ? base64_decode( substr( $u, $pos + 1 ) ) : '';
				} else {
					$bytes = $get( $resolver( $u, $b[1] ) );
				}
				if ( $fuente( $bytes ) ) {
					$servidas[ $nombre ] = true;
					break;
				}
			}
		}
	}

	$faltan = array();
	foreach ( $declaradas as $cara => $nombre ) {
		if ( ! isset( $servidas[ $cara ] ) ) {
			$faltan[] = $nombre;
		}
	}

	return $faltan;
}

/**
 * The kit settings the tokens decide, merged over `$settings` and returned. Pure: nothing is written.
 *
 * Split from `es_kit_apply()` so the settings are a thing the suite can read (a token that only the
 * kit reads is otherwise invisible to "does anybody read this token?") and so the write stays one
 * line. Everything below is what Elementor resolves a widget's missing value against.
 */
function es_kit_settings( array $settings ) {
	$t = es_tokens();

	$settings['system_colors'] = array(
		array( '_id' => 'primary',   'title' => 'Titulares', 'color' => $t['text'] ),
		array( '_id' => 'secondary', 'title' => 'Chrome',    'color' => $t['muted'] ),
		array( '_id' => 'text',      'title' => 'Cuerpo',    'color' => $t['text_soft'] ),
		array( '_id' => 'accent',    'title' => 'Acento',    'color' => $t['accent'] ),
	);

	/* Both keys or neither: Elementor ignores `body_background_color` unless the background TYPE
	   says there is one, so writing the colour alone is a value nobody reads. */
	$settings['body_background_background'] = 'classic';
	$settings['body_background_color']      = $t['bg'];

	/* The fallback after every family Elementor writes: one slot per site, so it is a kit setting. Written
	   only when the build SET the token: a kit that already holds a human's value keeps it. */
	if ( es_token_explicit( 'font_fallback' ) ) {
		$settings['default_generic_fonts'] = $t['font_fallback'];
	}

	/* The gap Elementor puts between widgets (its factory value is 20px). It is the kit's, not the
	   page's, so a maqueta whose rhythm is not 20 never matched until this was written. */
	if ( es_token_explicit( 'sp_widget' ) ) {
		$settings['space_between_widgets'] = array(
			'column'   => (string) $t['sp_widget'],
			'row'      => (string) $t['sp_widget'],
			'unit'     => 'px',
			'isLinked' => true,
		);
	}

	/* A link and a button never disagree about what "the accent" is. */
	$settings['link_normal_color'] = $t['accent'];
	$settings['link_hover_color']  = $t['accent_hover'];

	/* The type pair, in the same four ids Elementor gives the colours, and read by widgets the same
	   way: by id. This file already wrote `font_head` and `font_body` widget by widget — six places
	   and counting — which is a family per element instead of a family per site: changing the
	   brand's typeface meant editing every widget that had ever been written, and none of it was
	   reachable from Site Settings. `design-tokens.md` named the gap in its own table («Global font
	   primary / text — No helper yet»); this closes it with the tokens that already existed rather
	   than inventing a second pair of keys for the same thing.

	   Only the family is global. Size, weight and line height stay where they are, per role, because
	   the scale axis is four numbers this file derives and a global that fixed them would flatten it.
	   `typography_typography => custom` is not decoration: without it Elementor ignores the family
	   sitting next to it. */
	$settings['system_typography'] = array(
		array( '_id' => 'primary',   'title' => 'Titulares', 'typography_typography' => 'custom', 'typography_font_family' => $t['font_head'] ),
		array( '_id' => 'secondary', 'title' => 'Chrome',    'typography_typography' => 'custom', 'typography_font_family' => $t['font_body'] ),
		array( '_id' => 'text',      'title' => 'Cuerpo',    'typography_typography' => 'custom', 'typography_font_family' => $t['font_body'] ),
		array( '_id' => 'accent',    'title' => 'Acento',    'typography_typography' => 'custom', 'typography_font_family' => $t['font_body'] ),
	);

	return $settings;
}

/**
 * Carry the resolved tokens into the GLOBAL KIT, which is where they become the site.
 *
 * This library paints only where a helper writes a colour explicitly (`es_btn()` writes
 * `button_text_color`, `es_p()` writes `text_color`). The page ground, a heading with no `title_color`
 * and every link inherit from the Elementor KIT, whose `_elementor_page_settings` is EMPTY on a fresh
 * install: without this an h1 renders in Elementor's factory blue on a white body even though the type
 * scale is exact (measured on the first real build). `references/knowledge.md` § "Global kit" says to
 * set global colors there.
 *
 * MERGES, never replaces: a real kit carries settings that are not ours (container width, global
 * typography); overwriting the array would change the site behind the operator's back.
 *
 * The four ids are ELEMENTOR'S OWN and must not be renamed: every widget default resolves
 * `Global Colors > Primary` by `_id`, not by the title beside it. A different id silently detaches
 * every widget reading it.
 *
 * Returns the kit id, and only after READING THE WRITE BACK (`update_post_meta()` returns false both
 * on failure and when the value was already there). Zero means nothing was written, and it says why.
 */
function es_kit_apply() {
	$kit = (int) get_option( 'elementor_active_kit' );
	if ( ! $kit ) {
		es_warn(
			'no hay kit activo (`elementor_active_kit` vacio), asi que los colores globales NO se escribieron. '
			. 'El sitio va a heredar los defaults de fabrica de Elementor: titulares azules sobre fondo blanco, '
			. 'con la escala tipografica correcta encima y todos los chequeos en verde. Activa Elementor y repite.'
		);
		return 0;
	}

	$t        = es_tokens();
	$settings = get_post_meta( $kit, '_elementor_page_settings', true );
	$settings = es_kit_settings( is_array( $settings ) ? $settings : array() );

	/* Site-wide colours and typography, replaced: park the previous settings first (es_restore_page_state). */
	es_backup_page_state( $kit, array( '_elementor_page_settings' ) );
	update_post_meta( $kit, '_elementor_page_settings', $settings );

	$back = get_post_meta( $kit, '_elementor_page_settings', true );
	$ok   = is_array( $back )
		&& isset( $back['body_background_color'] ) && $back['body_background_color'] === $t['bg']
		&& isset( $back['system_colors'][0]['color'] ) && $back['system_colors'][0]['color'] === $t['text']
		&& isset( $back['system_typography'][0]['typography_font_family'] )
		&& $back['system_typography'][0]['typography_font_family'] === $t['font_head'];

	if ( ! $ok ) {
		es_warn( 'el kit ' . $kit . ' no conservo los colores ni las fuentes globales al releerlo; el sitio sigue con lo que tuviera' );
		return 0;
	}

	return $kit;
}

/**
 * Rebuild one post's Elementor stylesheet with a fresh cache-busting version.
 *
 * Elementor also stores the rendered markup in `_elementor_element_cache` for
 * 24h. Writing `_elementor_data` directly does not invalidate it, so the front
 * end keeps serving the previous (or empty) HTML until that meta is dropped.
 */
function es_rebuild_css( $post_id ) {
	delete_post_meta( $post_id, '_elementor_element_cache' );

	if ( ! class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
		return;
	}
	$uploads = wp_upload_dir();
	$file    = $uploads['basedir'] . '/elementor/css/post-' . $post_id . '.css';
	if ( file_exists( $file ) ) {
		unlink( $file );
	}
	delete_post_meta( $post_id, '_elementor_css' );
	\Elementor\Core\Files\CSS\Post::create( $post_id )->update();
}

/**
 * Regenerate the Theme Builder conditions cache.
 *
 * Writing `_elementor_conditions` post-meta does NOT register a template: at runtime
 * Elementor Pro reads the cached option `elementor_pro_theme_builder_conditions`
 * (`{location:{post_id:[conds]}}`) and never the meta. A template saved without this
 * step exists in the library and simply never renders on the front end.
 *
 * Use `get_cache()->regenerate()` and not the conditions manager's `save_conditions()`,
 * which throws "Cannot unset string offsets".
 *
 * Every hop is guarded so a site without Elementor Pro degrades to a logged no-op
 * instead of a fatal. Returns true only when the cache was actually rebuilt.
 */
function es_rebuild_theme_conditions() {
	if ( ! class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' )
		|| ! method_exists( '\ElementorPro\Modules\ThemeBuilder\Module', 'instance' ) ) {
		return false;
	}
	$module = \ElementorPro\Modules\ThemeBuilder\Module::instance();
	if ( ! $module || ! method_exists( $module, 'get_conditions_manager' ) ) {
		return false;
	}
	$manager = $module->get_conditions_manager();
	if ( ! $manager || ! method_exists( $manager, 'get_cache' ) ) {
		return false;
	}
	$cache = $manager->get_cache();
	if ( ! $cache || ! method_exists( $cache, 'regenerate' ) ) {
		return false;
	}
	$cache->regenerate();

	return true;
}

/**
 * Is a template present in the Theme Builder conditions cache?
 *
 * Regenerating is not proof: the gotcha is explicit that you must VERIFY the option
 * contains your template afterwards, because a condition string the runtime does not
 * recognise is dropped silently and the template stays invisible.
 *
 * Present is not the same as RENDERING, and this function cannot tell you the difference.
 * Elementor resolves one template per location; when two are registered there, one of them
 * loses, and this returns true for the loser exactly as it does for the winner. Ask
 * es_theme_location_rivals() before reading a `true` here as "the header is on the site".
 */
function es_theme_conditions_registered( $post_id ) {
	$cache = get_option( 'elementor_pro_theme_builder_conditions' );
	if ( ! is_array( $cache ) ) {
		return false;
	}
	foreach ( $cache as $templates ) {
		if ( ! is_array( $templates ) ) {
			continue;
		}
		if ( array_key_exists( (int) $post_id, $templates ) || array_key_exists( (string) $post_id, $templates ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Which OTHER templates are registered at the same locations as this one?
 *
 * The registration check answers "is my template in the cache" and stops there, which reports
 * green on the one failure an operator cannot see: another published template already claiming
 * `header`. Elementor picks one per location, so a header that is correctly saved, correctly
 * conditioned and correctly cached can still never render — and every automated check said yes.
 * That is a leftover from a previous agency, a theme's bundled template, or the last build of
 * this very site, and the site looks unchanged while the report says it worked.
 *
 * Returns `array( location => array( other_template_id, … ) )`, empty when nothing competes.
 * It names rivals, it does not pick a winner: the resolution order is Elementor Pro's and is not
 * knowable from this option, so the honest output is "these compete, go look".
 */
function es_theme_location_rivals( $post_id ) {
	$cache = get_option( 'elementor_pro_theme_builder_conditions' );
	if ( ! is_array( $cache ) ) {
		return array();
	}
	$rivals = array();
	foreach ( $cache as $location => $templates ) {
		if ( ! is_array( $templates ) ) {
			continue;
		}
		if ( ! array_key_exists( (int) $post_id, $templates ) && ! array_key_exists( (string) $post_id, $templates ) ) {
			continue;
		}
		foreach ( array_keys( $templates ) as $other ) {
			if ( (int) $other !== (int) $post_id ) {
				$rivals[ $location ][] = (int) $other;
			}
		}
	}

	return $rivals;
}
