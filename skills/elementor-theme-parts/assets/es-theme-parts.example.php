<?php
/**
 * Example site - global header and footer (Elementor Pro Theme Builder).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/*
 * Dependencies. Any .php dropped in novamira-sandbox/ executes on upload, so a bare
 * require_once on a missing file fatals before execute-php is ever called — taking the
 * site down. This file used to do exactly that: a bare require_once, and an operator who
 * uploaded the theme parts before the builder got a white screen and ZERO output, with no
 * way to tell a missing dependency from a build that ran and did nothing. The two commerce
 * assets already carried this guard; the file that creates the site's header and footer,
 * the one whose absence is most visible, did not.
 */
foreach ( array( 'es-builder.php' ) as $es_dep ) {
	$es_dep_path = WP_CONTENT_DIR . '/novamira-sandbox/' . $es_dep;
	if ( ! file_exists( $es_dep_path ) ) {
		/* Both channels on purpose: es_warn() lives in es-builder.php, which is exactly the
		   file that may be missing here, and error_log() alone is not "loudly" — the sandbox
		   returns STDOUT, so a log-only warning is a build that silently does nothing. */
		$es_msg = 'WordPress Orchestrator: ' . basename( __FILE__ ) . ' necesita ' . $es_dep . ' en novamira-sandbox/. Subelo primero. NO SE CONSTRUYO NADA.';
		error_log( $es_msg );
		echo $es_msg . "\n";
		return;
	}
	require_once $es_dep_path;
}

/**
 * Create or update a theme template and apply a global display condition.
 *
 * Saving `_elementor_conditions` is only half the job: the runtime resolves templates
 * from the cached option `elementor_pro_theme_builder_conditions`, so a part saved
 * without regenerating that cache is created invisible — it exists in the library and
 * never renders. Regeneration is therefore part of saving a theme part, not something
 * a caller has to remember, and the result is verified before we call it done.
 *
 * Overwriting also replaces `_elementor_data` outright with no revision behind it, so the
 * displaced state is parked in a timestamped backup key first (see es_backup_page_state in
 * es-builder.php) — including `_elementor_conditions`, the key that decides WHERE a template
 * renders and the one the old narrower backup never covered.
 *
 * Unlike es_save_page(), a theme part IS forced to `publish`: it is only ever saved in
 * order to be live, and this same call is writing the conditions that put it on the
 * front end. A part deliberately parked as a draft must not be rebuilt by this function.
 *
 * `$action` is an out-parameter reporting the same four outcomes as `es_save_page()` —
 * 'created', 'updated', 'created-renamed', 'failed' — without breaking the returned template id.
 * The two functions share a shape, so they share the reporting contract; when they drifted apart,
 * the one that mattered more was the one nobody could see failing.
 *
 * `$known_rivals` are template ids the caller has already LOOKED AT and decided to leave alone.
 * Some sites legitimately keep two templates at one location under different conditions, and the
 * rival warning had no way to say so — it fired on every build of a correct site, which is how a
 * warning becomes scenery. Acknowledgement is by ID and never by location, deliberately: a rival
 * that appears LATER is a new fact and still warns, which is the whole reason the check exists.
 */
function es_save_theme_part( $slug, $title, $type, array $elements, array $conditions, &$action = null, array $known_rivals = array() ) {
	$existing = get_posts(
		array(
			'post_type'      => 'elementor_library',
			'name'           => $slug,
			'posts_per_page' => 1,
			'post_status'    => 'any',
		)
	);
	if ( $existing ) {
		$id     = $existing[0]->ID;
		$action = 'updated';
		$wrote  = wp_update_post( array( 'ID' => $id, 'post_title' => $title, 'post_status' => 'publish' ) );
	} else {
		$action = 'created';
		$id     = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_name'   => $slug,
				'post_type'   => 'elementor_library',
				'post_status' => 'publish',
			)
		);
		$wrote  = $id;
	}
	if ( is_wp_error( $wrote ) || ! $wrote ) {
		/* A page that fails to save costs you one page. A HEADER that fails to save costs you the
		   header of every page, and this branch is the one with the fewest eyes on it: this file is
		   uploaded and executed on the site, far from whoever wrote the build. */
		es_warn(
			'WordPress rechazo ' . ( 'updated' === $action ? 'actualizar' : 'crear' ) . ' el theme part "' . $slug . '" (' . $type . ')'
			. ( is_wp_error( $wrote ) ? ': ' . $wrote->get_error_message() : '' )
			. '. NO se escribio ningun diseño ni ninguna condicion, asi que esa parte del sitio NO va a aparecer.'
		);
		$action = 'failed';
		return 0;
	}

	if ( 'created' === $action ) {
		/* Same trap as es_save_page(): wp_unique_post_slug() hands back a suffixed slug rather than
		   an error, so the template exists under a name no condition string was written for. */
		$real = get_post_field( 'post_name', $id );
		if ( '' !== $real && $real !== $slug ) {
			es_warn(
				'se pidio el theme part "' . $slug . '" y WordPress lo creo en "' . $real . '" (#' . $id . '), porque ese slug ya estaba ocupado. '
				. 'Revisa que las condiciones se hayan escrito sobre la plantilla correcta antes de dar el sitio por bueno.'
			);
			$action = 'created-renamed';
		}
	}

	if ( 'updated' === $action ) {
		/* BEFORE the writes, and it did not use to be: the backup sat below three update_post_meta()
		   calls, so it preserved what this call had just written. `_elementor_conditions` was never
		   covered at all, and that is the key that decides WHERE a template renders -- overwriting
		   it can un-hijack the whole site or hijack it, with nothing to restore from. */
		es_backup_page_state(
			$id,
			array( '_elementor_data', '_elementor_conditions', '_elementor_template_type', '_elementor_edit_mode', '_elementor_version' )
		);
	}

	wp_set_object_terms( $id, $type, 'elementor_library_type' );
	update_post_meta( $id, '_elementor_template_type', $type );
	update_post_meta( $id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0' );
	/* Theme parts used to skip the audit entirely, which is backwards: headers, footers and
	   commerce templates are the MOST nested artifacts in the system (see the mobile 3-zone
	   header recipe) and they are reused on every page, so a wasted level here is paid site-wide. */
	es_container_report( $elements, $type . ':' . $slug );
	update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $elements ) ) );
	update_post_meta( $id, '_elementor_conditions', $conditions );
	es_rebuild_css( $id );

	if ( ! es_rebuild_theme_conditions() ) {
		es_warn( 'no se pudo regenerar la cache de condiciones del theme builder para "' . $slug . '" (#' . $id . '). Elementor Pro no esta disponible, asi que esta plantilla NO va a aparecer en el front.' );
	} elseif ( ! es_theme_conditions_registered( $id ) ) {
		es_warn( '"' . $slug . '" (#' . $id . ') no esta en elementor_pro_theme_builder_conditions despues de regenerar. Revisa las condiciones: ' . implode( ', ', $conditions ) );
	} else {
		/* Registered is not rendering. Elementor resolves ONE template per location, so a rival
		   already claiming this one means this template can be saved, conditioned and cached and
		   still never appear — with every check green and the site looking untouched. */
		$where = array();
		$seen  = 0;
		foreach ( es_theme_location_rivals( $id ) as $location => $ids ) {
			/* Acknowledged ones are subtracted, not hidden: the count is still reported, so a
			   silenced rival stays visible as a number and cannot become a permanent blind spot. */
			$news = array_values( array_diff( array_map( 'intval', $ids ), array_map( 'intval', $known_rivals ) ) );
			$seen += count( $ids ) - count( $news );
			if ( $news ) {
				$where[] = $location . ' (#' . implode( ', #', $news ) . ')';
			}
		}
		if ( $where ) {
			es_warn(
				'"' . $slug . '" (#' . $id . ') quedo registrado, pero NO es la unica plantilla en su ubicacion: ' . implode( '; ', $where ) . '. '
				. 'Elementor resuelve una sola por ubicacion, asi que puede que la que se vea siga siendo la otra. '
				. 'Borra o reacondiciona las rivales y vuelve a mirar el front — registrado no es lo mismo que visible.'
				. ( $seen ? ' (' . $seen . ' rival(es) mas, ya reconocidas por quien llamo.)' : '' )
			);
		}
	}

	return $id;
}

/* -------------------------------------------------- start of the visual layer
   This file declares no es_tokens() of its own and must not: es-builder.php,
   required above, holds the ONE token block for the whole build, and a second
   copy of it here would be the drift this layer exists to end. Everything from
   this marker down to the closing marker at the bottom of this file reads
   it through es_t() / es_rgba(); nothing types a colour, a family, a shadow or
   an easing curve. RT_BUILDER_HARDCODED_TOKEN enforces it.

   THE REGION STARTS HERE AND NOT AT THE TOP OF THE FILE, on purpose. The save
   pipeline -- es_save_theme_part(), 100 lines of it -- sits ABOVE this marker
   rather than below it, which is the reverse of es-builder.php's layout. Its
   warnings build post ids by concatenating '#' with a variable, and its
   '(#' . $id . ')' strings are outside the scanned region for exactly the
   reason es-builder.php:1962's "#732" is: a hex regex cannot tell a post id
   from a colour, and neither one can reach the emitted data.

 * =====================================================================
 * HEADER
 * ===================================================================== */
function es_foot_col( $title, array $links ) {
	$children = array(
		es_w(
			'heading',
			array(
				'title'                     => $title,
				'header_size'               => 'div',
				'title_color'               => es_rgba( es_t( 'on_inverse' ), '0.42' ),
				'typography_typography'     => 'custom',
				'typography_font_family'    => es_t( 'font_body' ),
				'typography_font_size'      => es_size( 11.5 ),
				'typography_font_weight'    => '700',
				'typography_text_transform' => 'uppercase',
				'typography_letter_spacing' => es_size( 1.5 ),
				'_margin'                   => es_box( 0, 0, 18, 0 ),
			)
		),
	);
	$items = array();
	foreach ( $links as $label => $url ) {
		$items[] = array(
			'text' => $label,
			'link' => array( 'url' => $url, 'is_external' => '', 'nofollow' => '' ),
		);
	}
	$children[] = es_w(
		'icon-list',
		array(
			'icon_list'             => $items,
			'view'                  => 'traditional',
			'space_between'         => es_size( 11 ),
			'text_color'            => es_rgba( es_t( 'on_inverse' ), '0.76' ),
			'text_color_hover'      => es_t( 'accent' ),
			'icon_color'            => es_rgba( es_t( 'on_inverse' ), '0' ),
			'icon_size'             => es_size( 0 ),
			'text_indent'           => es_size( 0 ),
			'icon_typography_typography' => 'custom',
			'icon_typography_font_family' => es_t( 'font_body' ),
			'icon_typography_font_size' => es_size( 14.5 ),
		)
	);
	return es_c(
		array( 'content_width' => 'full', 'flex_direction' => 'column' ),
		$children,
		true
	);
}

function es_build_theme_parts() {
	es_uid_reset( 'header' );

	/*
	 * The nav widget points at a menu by slug and renders an empty shell when that menu
	 * does not exist - a header whose navigation silently goes nowhere. Check it up front:
	 * name the missing menu in the log and leave the widget out altogether rather than
	 * shipping an empty nav that looks intentional.
	 */
	$menu_slug   = 'menu-principal';
	$nav_widgets = array();
	if ( wp_get_nav_menu_object( $menu_slug ) ) {
		$nav_widgets[] = es_w(
			'nav-menu',
			array(
				'menu'                  => $menu_slug,
				'layout'                => 'horizontal',
				'submenu_icon'          => array( 'value' => 'fas fa-caret-down', 'library' => 'fa-solid' ),
				'align_items'           => 'center',
				'pointer'               => 'underline',
				'animation_line'        => 'fade',
				'color_menu_item'       => es_t( 'muted' ),
				'color_menu_item_hover' => es_t( 'text' ),
				'color_menu_item_active' => es_t( 'text' ),
				'pointer_color_menu_item_hover' => es_t( 'accent' ),
				'pointer_color_menu_item_active' => es_t( 'accent' ),
				'menu_typography_typography' => 'custom',
				'menu_typography_font_family' => es_t( 'font_body' ),
				'menu_typography_font_size' => es_size( 14.5 ),
				'menu_typography_font_weight' => '500',
				'padding_horizontal_menu_item' => es_size( 13 ),
				'padding_vertical_menu_item' => es_size( 8 ),
				'_element_width'        => 'auto',
				/* ---- Mobile / tablet: modern full-width panel ---- */
				'dropdown'              => 'tablet',
				'toggle'                => 'burger',
				'color_dropdown_item'   => es_t( 'text' ),
				'background_color_dropdown_item' => es_t( 'bg' ),
				'color_dropdown_item_hover' => es_t( 'accent' ),
				'background_color_dropdown_item_hover' => es_t( 'bg_alt' ),
				'color_dropdown_item_active' => es_t( 'accent' ),
				'background_color_dropdown_item_active' => es_rgba( es_t( 'accent' ), '0.08' ),
				'dropdown_typography_typography' => 'custom',
				'dropdown_typography_font_family' => es_t( 'font_head' ),
				'dropdown_typography_font_size' => es_size( 17 ),
				'dropdown_typography_font_weight' => '600',
				'padding_horizontal_dropdown_item' => es_size( 22 ),
				'padding_vertical_dropdown_item' => es_size( 17 ),
				'dropdown_divider_border' => 'solid',
				'dropdown_divider_width' => es_size( 1 ),
				'dropdown_divider_color' => es_t( 'border_softer' ),
				'dropdown_border_border' => 'solid',
				'dropdown_border_width'  => es_box( 1, 1, 1, 1 ),
				'dropdown_border_color'  => es_t( 'border_soft' ),
				'dropdown_border_radius' => es_box( 14, 14, 14, 14 ),
				'dropdown_top_distance'  => es_size( 10 ),
				/* ---- Toggle: rounded square, not a bare burger ---- */
				'toggle_size'           => es_size( 19 ),
				'toggle_color'          => es_t( 'text' ),
				'toggle_background_color' => es_rgba( es_t( 'accent' ), '0.07' ),
				'toggle_color_hover'    => es_t( 'on_accent' ),
				'toggle_background_color_hover' => es_t( 'accent' ),
				'toggle_border_width'   => es_box( 0, 0, 0, 0 ),
				'toggle_border_radius'  => es_box( 10, 10, 10, 10 ),
				/* Green underline only on desktop; in the mobile panel it read
				   as odd green bars, so it is scoped out below. */
				'custom_css'            => 'selector .elementor-nav-menu--dropdown{overflow:hidden;box-shadow:0 24px 50px -18px ' . es_rgba( es_t( 'text' ), '0.22' ) . ';}'
					. '@media(max-width:1024px){selector .elementor-menu-toggle{width:46px;height:46px;position:relative;z-index:100;}'
					. 'selector .elementor-menu-toggle[aria-expanded="true"] ~ .elementor-nav-menu--dropdown{position:fixed!important;top:0!important;left:0!important;right:0!important;width:100vw!important;max-width:100vw!important;height:100vh!important;max-height:100vh!important;border:0!important;border-radius:0!important;box-shadow:none!important;background:' . es_t( 'bg' ) . '!important;display:flex!important;flex-direction:column!important;justify-content:center!important;padding:104px 22px 44px!important;overflow-y:auto!important;z-index:99!important;}'
					. 'selector .elementor-menu-toggle[aria-expanded="true"] ~ .elementor-nav-menu--dropdown .elementor-nav-menu{width:100%;max-width:440px;margin:0 auto;}'
					. 'selector .elementor-menu-toggle[aria-expanded="true"] ~ .elementor-nav-menu--dropdown .elementor-item{text-align:center!important;font-size:24px!important;padding:16px!important;border-radius:12px!important;}}'
					/* Zone 1 of the mobile 3-zone header. The toggle ships its own
					   horizontal margin:auto that re-centres it and ignores the row's
					   alignment, and a CLOSED dropdown stays display:block at height 0
					   with a margin-top that shoves the toggle off the vertical centre.
					   Neutralise both so the burger really sits left and centred. */
					. '@media(max-width:767px){selector .elementor-menu-toggle{margin:0!important;}'
					. 'selector .elementor-menu-toggle[aria-expanded="false"] ~ .elementor-nav-menu--dropdown{position:absolute!important;}}'
					. '@media(min-width:1025px){selector .elementor-item{position:relative;}'
					. 'selector .elementor-item::after{content:"";position:absolute;left:13px;right:13px;bottom:2px;height:2px;background:' . es_t( 'accent' ) . ';opacity:0;transform:translateY(2px);transition:opacity .28s ' . es_t( 'ease' ) . ',transform .28s ' . es_t( 'ease' ) . ';}'
					. 'selector .elementor-item:hover::after,selector .current-menu-item .elementor-item::after,selector .elementor-item-active::after{opacity:1;transform:translateY(0);}}',
			)
		);
	} else {
		es_warn( 'el menu "' . $menu_slug . '" no existe. El header se esta construyendo SIN su navegacion: crea el menu y vuelve a construir los theme parts. Esto rompe la regla de casa "la barra es navegacion de verdad" en TODAS las paginas.' );
	}

	$header = array(
		es_c(
			array(
				'content_width'         => 'boxed',
				'flex_direction'        => 'column',
				'flex_gap'              => array( 'unit' => 'px', 'size' => 0, 'column' => '0', 'row' => '0' ),
				'padding'               => es_box( 16, 24, 16, 24 ),
				'border_border'         => 'solid',
				'border_width'          => es_box( 0, 0, 1, 0 ),
				'border_color'          => es_rgba( es_t( 'text' ), '0.08' ),
				/* Glass lives on a ::before layer, NOT the container. backdrop-filter
				   on the container itself creates a containing block that traps the
				   fixed side-cart inside the header box; the pseudo-element keeps the
				   frosted look while the cart is free to cover the viewport. */
				'custom_css'            => 'selector{position:relative;}'
					. 'selector::before{content:"";position:absolute;inset:0;z-index:-1;background:' . es_rgba( es_t( 'bg' ), '0.72' ) . ';backdrop-filter:saturate(180%) blur(16px);-webkit-backdrop-filter:saturate(180%) blur(16px);}',
				'sticky'                => 'top',
				'sticky_on'             => array( 'desktop', 'tablet', 'mobile' ),
				'sticky_effects_offset' => 0,
				'z_index'               => 99,
			),
			array(
				/* Top bar: logo (left) + cluster (right), always one row. */
				es_c(
					array(
						'flex_direction'       => 'row',
						'flex_align_items'     => 'center',
						'flex_justify_content' => 'space-between',
						'flex_wrap'            => 'nowrap',
						'flex_gap'             => array( 'unit' => 'px', 'size' => 20, 'column' => '20', 'row' => '12' ),
						/* Mobile 3-zone header (burger | logo | cart), step 1: the row becomes
						   the positioning context so the logo can be absolutely centred over it.
						   Burger and cart sit nested together inside the cluster while the logo
						   is a separate sibling, so CSS `order` cannot interleave them - absolute
						   positioning is the only way to get the three zones. */
						'custom_css'           => '@media(max-width:767px){selector{position:relative!important;}}',
					),
					array(
				/* Logo */
				es_w(
					'heading',
					array(
						'title'                     => 'YOUR<span style="color:' . es_t( 'accent' ) . '">BRAND</span>',
						'header_size'               => 'div',
						'link'                      => array( 'url' => home_url( '/' ) ),
						'title_color'               => es_t( 'text' ),
						'typography_typography'     => 'custom',
						'typography_font_family'    => es_t( 'font_head' ),
						'typography_font_size'      => es_size( 20 ),
						'typography_font_weight'    => '700',
						'typography_letter_spacing' => es_size( -0.4 ),
						'_element_width'            => 'auto',
						/* Step 2: pinned to the exact centre of the top bar and taken out of
						   flow, so the cluster below can span the full row width without the
						   logo claiming any of it. */
						'custom_css'                => '@media(max-width:767px){selector{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);z-index:1;width:auto;white-space:nowrap;}}',
					)
				),
				/* Right cluster: nav + phone + cart + CTA, right-aligned as one unit. */
				es_c(
					array(
						'flex_direction'   => 'row',
						'flex_align_items' => 'center',
						'flex_wrap'        => 'nowrap',
						'flex_gap'         => array( 'unit' => 'px', 'size' => 12, 'column' => '12', 'row' => '10' ),
						'_element_width'   => 'auto',
						'_flex_grow'       => 0,
						'_flex_shrink'     => 0,
						/* Step 3: the cluster spans the whole row and splits burger left /
						   actions right. margin-left MUST be 0 - any inset shifts the burger
						   off the left edge and drags it toward the logo's centre.
						   This container is boxed (no content_width => full), so its real flex
						   row is the generated .e-con-inner: justify-content on `selector` does
						   nothing at all here and has to go on selector>.e-con-inner. */
						'custom_css'       => '@media(max-width:767px){selector{width:100%!important;margin-left:0!important;}'
							. 'selector>.e-con-inner{justify-content:space-between!important;align-items:center!important;}}',
					),
					array_merge(
						$nav_widgets,
						array(
				/* Actions */
				es_c(
					array(
						'content_width'    => 'full',
						'flex_direction'   => 'row',
						'flex_align_items' => 'center',
						'flex_gap'         => array( 'unit' => 'px', 'size' => 10, 'column' => '10', 'row' => '10' ),
						'width'            => es_size( 'auto', 'custom' ),
						'_element_width'   => 'auto',
						/* Step 4: shrink-wrap the actions and pin the cart to the right edge, so
						   the space-between above puts the burger hard left and this hard right.
						   content_width is full here, so there is no .e-con-inner and
						   justify-content belongs on `selector` itself. */
						'custom_css'       => '@media(max-width:767px){selector{width:auto!important;flex:0 0 auto!important;justify-content:flex-end!important;}}',
					),
					array(
						es_btn(
							'000 000 000',
							'tel:0000000000',
							'outline',
							array(
								'selected_icon'        => array( 'value' => 'fas fa-phone-alt', 'library' => 'fa-solid' ),
								'icon_align'           => 'left',
								'icon_indent'          => es_size( 7 ),
								'text_padding'         => es_box( 10, 16, 10, 16 ),
								'typography_font_size' => es_size( 14 ),
								'hide_mobile'          => 'hidden-mobile',
							)
						),
						/* Side cart: opens automatically when a product is added. */
						es_w(
							'woocommerce-menu-cart',
							array(
								'icon'                     => 'cart-medium',
								'items_indicator'          => 'bubble',
								'show_subtotal'            => '',
								'cart_type'                => 'side-cart',
								'side_cart_alignment'      => 'right',
								'automatically_open_cart'  => 'yes',
								'automatically_update_cart' => 'yes',
								'close_cart_button_show'   => 'yes',
								'view_cart_button_show'    => 'yes',
								'toggle_button_icon_color' => es_t( 'text' ),
								'toggle_button_background_color' => es_t( 'transparent' ),
								'toggle_button_border_color' => es_t( 'transparent' ),
								'toggle_button_border_width' => es_box( 0, 0, 0, 0 ),
								'toggle_button_hover_icon_color' => es_t( 'accent' ),
								'toggle_icon_size'         => es_size( 22 ),
								'toggle_button_padding'    => es_box( 4, 4, 4, 4 ),
								/* Just the icon, no square frame. */
								'custom_css'               => 'selector .elementor-menu-cart__toggle .elementor-button{border:0!important;background:' . es_t( 'transparent' ) . '!important;box-shadow:none!important;}'
									. '@media(max-width:767px){selector .elementor-menu-cart__container{width:100vw!important;max-width:100vw!important;}}',
								'items_indicator_text_color' => es_t( 'on_accent' ),
								'items_indicator_background_color' => es_t( 'accent' ),
								'view_cart_button_background_color' => es_t( 'surface_inverse' ),
								'view_cart_button_text_color' => es_t( 'on_inverse' ),
								/* Cart item contrast (product name was inheriting a pink link color) */
								'product_title_color'      => es_t( 'text' ),
								'product_title_hover_color' => es_t( 'accent' ),
								'product_price_color'      => es_t( 'text' ),
								'product_quantity_color'   => es_t( 'muted' ),
								'subtotal_color'           => es_t( 'text' ),
								'_element_width'           => 'auto',
							)
						),
						es_btn(
							'Pedir cita',
							'/contacto/',
							'primary',
							array(
								'text_padding'         => es_box( 11, 20, 11, 20 ),
								'typography_font_size' => es_size( 14 ),
								'hide_mobile'          => 'hidden-mobile',
							)
						),
					),
					true
				),
						)
					),
			),
				)
			),
				/* Pedir cita on mobile: its own centered row at 70% width. */
				es_c(
					array(
						'content_width'        => 'full',
						'flex_direction'       => 'row',
						'flex_justify_content' => 'center',
						'width_mobile'         => es_size( 92, '%' ),
						'hide_desktop'         => 'hidden-desktop',
						'hide_tablet'          => 'hidden-tablet',
						'_margin_mobile'       => es_box( 12, 0, 0, 0 ),
						'custom_css'           => '@media(max-width:767px){selector{margin-left:auto!important;margin-right:auto!important;}'
								. 'selector .elementor-widget-button{width:100%!important;}'
								. 'selector .elementor-button{width:100%!important;justify-content:center!important;}}',
					),
					array(
						es_btn(
							'Pedir cita',
							'/contacto/',
							'primary',
							array(
								'align'        => 'justify',
								'text_padding' => es_box( 13, 20, 13, 20 ),
							)
						),
					),
					true
				),
			)
		)
	);

	$header_id = es_save_theme_part(
		'es-header',
		'Site - Header',
		'header',
		$header,
		array( 'include/general' )
	);
	
	/* =====================================================================
	 * FOOTER
	 * ===================================================================== */
	
	es_uid_reset( 'footer' );
	
	$footer = array(
		es_c(
			array(
				'content_width'         => 'boxed',
				'flex_direction'        => 'column',
				'padding'               => es_box( 76, 24, 32, 24 ),
				'padding_mobile'        => es_box( 56, 20, 28, 20 ),
				'background_background' => 'classic',
				'background_color'      => es_t( 'surface_inverse' ),
			),
			array(
				/* Columns */
				es_grid(
					4,
					array(
						es_c(
							array( 'content_width' => 'full', 'flex_direction' => 'column' ),
							array(
								es_w(
									'heading',
									array(
										'title'                  => 'YOUR<span style="color:' . es_t( 'accent' ) . '">BRAND</span>',
										'header_size'            => 'div',
										'title_color'            => es_t( 'on_inverse' ),
										'typography_typography'  => 'custom',
										'typography_font_family' => es_t( 'font_head' ),
										'typography_font_size'   => es_size( 21 ),
										'typography_font_weight' => '700',
										'_margin'                => es_box( 0, 0, 16, 0 ),
									)
								),
								es_p(
									'Short brand tagline goes here — one or two lines describing the business.',
									array(
										'text_color'           => es_rgba( es_t( 'on_inverse' ), '0.52' ),
										'typography_font_size' => es_size( 14 ),
										'_margin'              => es_box( 0, 0, 22, 0 ),
									)
								),
								es_w(
									'social-icons',
									array(
										'social_icon_list' => array(
											array( 'social_icon' => array( 'value' => 'fab fa-instagram', 'library' => 'fa-brands' ), 'link' => array( 'url' => '#' ) ),
											array( 'social_icon' => array( 'value' => 'fab fa-facebook-f', 'library' => 'fa-brands' ), 'link' => array( 'url' => '#' ) ),
											array( 'social_icon' => array( 'value' => 'fab fa-tiktok', 'library' => 'fa-brands' ), 'link' => array( 'url' => '#' ) ),
										),
										'shape'            => 'rounded',
										'icon_size'        => es_size( 15 ),
										'icon_padding'     => es_size( 11 ),
										'icon_spacing'     => es_size( 9 ),
										'icon_color'       => 'custom',
										'icon_primary_color' => es_rgba( es_t( 'on_inverse' ), '0.08' ),
										'icon_secondary_color' => es_t( 'on_inverse' ),
										'hover_primary_color' => es_t( 'accent' ),
										'hover_secondary_color' => es_t( 'on_accent' ),
									)
								),
							),
							true
						),
						es_foot_col(
							'Sitio',
							array(
								/* home_url(), never a hardcoded '/inicio/': on any install whose
								   front page is '/' that slug is a dead link. */
								'Inicio'        => home_url( '/' ),
								'Quiénes somos' => '/quienes-somos/',
								'Servicios'     => '/servicios/',
								'Tienda online' => '/tienda/',
							)
						),
						es_foot_col(
							'Servicios',
							array(
								'Servicio 1' => '/servicios/',
								'Servicio 2'        => '/servicios/',
								'Servicio 3'          => '/servicios/',
								'Servicio 4'           => '/servicios/',
							)
						),
						es_foot_col(
							'Contacto',
							array(
								'000 000 000'               => 'tel:0000000000',
								'hello@example.com'  => 'mailto:hello@example.com',
								'Cómo llegar'               => '/encuentranos/',
								'Pedir cita'                => '/contacto/',
							)
						),
					),
					48,
					array( '_margin' => es_box( 0, 0, 48, 0 ) )
				),
				/* Bottom bar */
				es_c(
					array(
						'content_width'        => 'full',
						'flex_direction'       => 'row',
						'flex_justify_content' => 'space-between',
						'flex_align_items'     => 'center',
						'flex_wrap'            => 'wrap',
						'flex_gap'             => array( 'unit' => 'px', 'size' => 14, 'column' => '14', 'row' => '10' ),
						'padding'              => es_box( 28, 0, 0, 0 ),
						'border_border'        => 'solid',
						'border_width'         => es_box( 1, 0, 0, 0 ),
						'border_color'         => es_rgba( es_t( 'on_inverse' ), '0.11' ),
					),
					array(
						es_p(
							'© ' . gmdate( 'Y' ) . ' Your Brand. All rights reserved.',
							array( 'text_color' => es_rgba( es_t( 'on_inverse' ), '0.42' ), 'typography_font_size' => es_size( 13 ), '_element_width' => 'auto' )
						),
						es_p(
							'Aviso legal · Privacidad · Cookies',
							array( 'text_color' => es_rgba( es_t( 'on_inverse' ), '0.42' ), 'typography_font_size' => es_size( 13 ), '_element_width' => 'auto' )
						),
					),
					true
				),
			)
		),
	);
	
	$footer_id = es_save_theme_part(
		'es-footer',
		'Site - Footer',
		'footer',
		$footer,
		array( 'include/general' )
	);

	return array( 'header' => $header_id, 'footer' => $footer_id );
}

/* -------------------------------------------------- end of the visual layer
   Nothing follows. In this file the save pipeline is es_save_theme_part(), up
   at the top above the start marker, so there is no tail to exclude -- but the
   marker is here anyway because the region needs a BOTTOM. An unbounded region
   is an unscannable one, and a region nothing scans is a rule nothing
   enforces: the check treats a missing marker as the failure itself. */
