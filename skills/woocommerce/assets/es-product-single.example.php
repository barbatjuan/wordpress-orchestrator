<?php
/**
 * Example - single product template (Elementor Pro Theme Builder).
 * Native WooCommerce single-product widgets only.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/*
 * Dependencies. Any .php dropped in novamira-sandbox/ executes on upload, so a bare
 * require_once on a missing file fatals before execute-php is ever called — taking the
 * site down. Fail loudly but safely instead: report what is missing and stop.
 * es-theme-parts.php is es-theme-parts.example.php with the .example suffix stripped.
 */
foreach ( array( 'es-builder.php', 'es-theme-parts.php' ) as $es_dep ) {
	$es_dep_path = WP_CONTENT_DIR . '/novamira-sandbox/' . $es_dep;
	if ( ! file_exists( $es_dep_path ) ) {
		/* Both channels on purpose: es_warn() lives in es-builder.php, which is exactly the
		   file that may be missing here, and error_log() alone is not "loudly" — the sandbox
		   returns STDOUT, so a log-only warning is a build that silently does nothing. */
		$es_msg = 'WordPress Orchestrator: ' . basename( __FILE__ ) . ' requires ' . $es_dep . ' in novamira-sandbox/. Upload it first. NOTHING WAS BUILT.';
		error_log( $es_msg );
		echo $es_msg . "\n";
		return;
	}
	require_once $es_dep_path;
}

/* -------------------------------------------------- start of the visual layer
   This file declares no es_tokens() of its own and must not: es-builder.php,
   required above, holds the ONE token block for the whole build. Everything
   from this marker down to the closing marker at the bottom of this file
   reads it through es_t() / es_rgba(); nothing types a colour, a family, a
   shadow or an easing curve. RT_BUILDER_HARDCODED_TOKEN enforces it.

   The add-to-cart CSS below used to carry a comment reading "Match the site
   green button, hover included." That comment was an instruction to a HUMAN to
   keep two files' colours in sync by hand -- the exact coordination problem
   this layer removes -- and it is gone because the button and the site now read
   the same es_t('accent') and es_t('accent_hover'). Nothing is left to match. */
function es_build_product_single() {
	es_uid_reset( 'prodsingle' );

	$el = array();

	/* ---------- Breadcrumb band ---------- */
	$el[] = es_c(
		array(
			'content_width'  => 'boxed',
			'flex_direction' => 'column',
			'padding'        => es_box( 22, 24, 22, 24 ),
			'padding_mobile' => es_box( 16, 20, 16, 20 ),
			'border_border'  => 'solid',
			'border_width'   => es_box( 0, 0, 1, 0 ),
			'border_color'   => es_t( 'border_soft' ),
		),
		array(
			es_w(
				'woocommerce-breadcrumb',
				array(
					'text_color'            => es_t( 'muted' ),
					'link_color'            => es_t( 'muted' ),
					'link_hover_color'      => es_t( 'accent' ),
					'typography_typography' => 'custom',
					'typography_font_family' => es_t( 'font_body' ),
					'typography_font_size'  => es_size( 13.5 ),
				)
			),
		)
	);

	/* ---------- Main: gallery + buy box ---------- */
	$el[] = es_section(
		array(
			es_c(
				array(
					'content_width'    => 'full',
					'flex_direction'   => 'row',
					'flex_gap'         => array( 'unit' => 'px', 'size' => 56, 'column' => '56', 'row' => '40' ),
					'flex_wrap'        => 'wrap',
					'flex_align_items' => 'flex-start',
				),
				array(
					/* Gallery */
					es_c(
						array( 'content_width' => 'full', 'flex_direction' => 'column', 'width' => es_size( 50, '%' ), 'width_tablet' => es_size( 100, '%' ) ),
						array(
							es_w(
								'woocommerce-product-images',
								array(
									'sale_flash_show'      => 'yes',
									'image_border_radius'  => es_box( 14, 14, 14, 14 ),
									'thumbs_border_radius' => es_box( 8, 8, 8, 8 ),
									'spacing'              => es_size( 10 ),
								)
							),
						),
						true
					),
					/* Buy box */
					es_c(
						array( 'content_width' => 'full', 'flex_direction' => 'column', 'width' => es_size( 45, '%' ), 'width_tablet' => es_size( 100, '%' ) ),
						array(
							es_w(
								'woocommerce-product-title',
								array(
									'text_color'                  => es_t( 'text' ),
									'typography_typography'       => 'custom',
									'typography_font_family'      => es_t( 'font_head' ),
									'typography_font_size'        => es_size( 36 ),
									'typography_font_size_mobile' => es_size( 27 ),
									'typography_font_weight'      => '700',
									'typography_line_height'      => es_size( 1.12, 'em' ),
									'_margin'                     => es_box( 0, 0, 14, 0 ),
								)
							),
							es_w( 'woocommerce-product-rating', array( 'star_color' => es_t( 'accent' ), '_margin' => es_box( 0, 0, 16, 0 ) ) ),
							es_w(
								'woocommerce-product-price',
								array(
									'price_color'                 => es_t( 'text' ),
									'price_typography_typography' => 'custom',
									'price_typography_font_family' => es_t( 'font_head' ),
									'price_typography_font_size'  => es_size( 30 ),
									'price_typography_font_weight' => '700',
									'_margin'                     => es_box( 0, 0, 20, 0 ),
								)
							),
							es_w(
								'woocommerce-product-short-description',
								array(
									'text_color'             => es_t( 'text_soft' ),
									'typography_typography'  => 'custom',
									'typography_font_family' => es_t( 'font_body' ),
									'typography_font_size'   => es_size( 15.5 ),
									'typography_line_height' => es_size( 1.65, 'em' ),
									'_margin'                => es_box( 0, 0, 26, 0 ),
								)
							),
							es_w(
								'woocommerce-product-add-to-cart',
								array(
									'button_background_color'       => es_t( 'accent' ),
									'button_hover_background_color' => es_t( 'accent_hover' ),
									'button_text_color'             => es_t( 'on_accent' ),
									'button_hover_text_color'       => es_t( 'on_accent' ),
									'button_border_radius'          => es_box( 8, 8, 8, 8 ),
									'quantity_border_color'         => es_t( 'border' ),
									'button_typography_typography'  => 'custom',
									'button_typography_font_family' => es_t( 'font_body' ),
									'button_typography_font_size'   => es_size( 15 ),
									'button_typography_font_weight' => '600',
									'_margin'                       => es_box( 0, 0, 26, 0 ),
									'custom_css'                    => 'selector .button,selector .single_add_to_cart_button{background-color:' . es_t( 'accent' ) . '!important;border-color:' . es_t( 'accent' ) . '!important;color:' . es_t( 'on_accent' ) . '!important;border-radius:8px!important;transition:background-color .3s ' . es_t( 'ease' ) . ',box-shadow .35s ' . es_t( 'ease' ) . ',transform .35s ' . es_t( 'ease' ) . '!important;}'
										. 'selector .button:hover,selector .single_add_to_cart_button:hover{background-color:' . es_t( 'accent_hover' ) . '!important;border-color:' . es_t( 'accent_hover' ) . '!important;transform:translateY(-2px);box-shadow:' . es_t( 'elev_accent' ) . '!important;}',
								)
							),
							/* Trust list */
							es_w(
								'icon-list',
								array(
									'icon_list'     => array(
										array( 'text' => 'Envío en 24-48 h o recogida gratis en taller', 'selected_icon' => array( 'value' => 'fas fa-truck', 'library' => 'fa-solid' ) ),
										array( 'text' => 'Garantía oficial de fabricante', 'selected_icon' => array( 'value' => 'fas fa-shield-alt', 'library' => 'fa-solid' ) ),
										array( 'text' => 'Te asesoramos si dudas de la compatibilidad', 'selected_icon' => array( 'value' => 'fas fa-headset', 'library' => 'fa-solid' ) ),
									),
									'space_between' => es_size( 12 ),
									'icon_color'    => es_t( 'accent' ),
									'icon_size'     => es_size( 15 ),
									'text_color'    => es_t( 'text_soft' ),
									'icon_typography_typography' => 'custom',
									'icon_typography_font_family' => es_t( 'font_body' ),
									'icon_typography_font_size' => es_size( 14 ),
									'_padding'      => es_box( 22, 22, 22, 22 ),
									'_background_background' => 'classic',
									'_background_color' => es_t( 'bg_alt' ),
									'_border_radius' => es_box( 12, 12, 12, 12 ),
									'_margin'       => es_box( 0, 0, 24, 0 ),
								)
							),
							es_w(
								'woocommerce-product-meta',
								array(
									'typography_typography'  => 'custom',
									'typography_font_family' => es_t( 'font_body' ),
									'typography_font_size'   => es_size( 13.5 ),
								)
							),
						),
						true
					),
				),
				true
			),
		)
	);

	/* ---------- Product data tabs ---------- */
	$el[] = es_section(
		array(
			es_w(
				'woocommerce-product-data-tabs',
				array(
					'tabs_title_color'                  => es_t( 'muted' ),
					'tabs_title_color_active'           => es_t( 'text' ),
					'tabs_title_typography_typography'  => 'custom',
					'tabs_title_typography_font_family' => es_t( 'font_head' ),
					'tabs_title_typography_font_size'   => es_size( 15 ),
					'tabs_title_typography_font_weight' => '600',
					'border_color'                      => es_t( 'border_soft' ),
					'content_color'                     => es_t( 'text_soft' ),
					'content_typography_typography'     => 'custom',
					'content_typography_font_family'    => es_t( 'font_body' ),
					'content_typography_font_size'      => es_size( 15 ),
					'content_typography_line_height'    => es_size( 1.7, 'em' ),
				)
			),
		),
		array( 'bg' => es_t( 'bg_alt' ) )
	);

	/* ---------- Related products ---------- */
	$el[] = es_section(
		array(
			es_c(
				array( 'content_width' => 'full', 'flex_direction' => 'column', '_margin' => es_box( 0, 0, 36, 0 ) ),
				array(
					es_eyebrow( 'También te puede interesar' ),
					es_h( 'Productos relacionados', 'h2', array( 'typography_typography' => 'custom', 'typography_font_family' => es_t( 'font_head' ), 'typography_font_size' => es_size( 32 ), 'typography_font_size_mobile' => es_size( 24 ), 'typography_font_weight' => '700' ) ),
				),
				true
			),
			es_w(
				'woocommerce-product-related',
				array(
					'columns'                  => 4,
					'columns_tablet'           => 3,
					'columns_mobile'           => 2,
					'posts_per_page'           => 4,
					'text_align'               => 'left',
					'product_title_color'      => es_t( 'text' ),
					'price_color'              => es_t( 'text' ),
					'button_color'             => es_t( 'on_accent' ),
					'button_background_color'  => es_t( 'accent' ),
					'button_hover_color'       => es_t( 'on_accent' ),
					'button_background_hover_color' => es_t( 'accent_hover' ),
					'button_border_radius'     => es_box( 6, 6, 6, 6 ),
					/* Same products-grid language as the archive, from the one shared helper.
					   Related products need no extras. */
					'custom_css'               => es_products_css(),
				)
			),
		)
	);

	return es_save_theme_part(
		'es-single-product',
		'Site - Product',
		'product',
		$el,
		array( 'include/product' )
	);
}

/* -------------------------------------------------- end of the visual layer
   Nothing follows: this file is one build function and the save pipeline it
   calls lives in es-theme-parts.php. The marker is here because the region
   needs a BOTTOM -- an unbounded region is an unscannable one, and the check
   treats a missing marker as the failure itself. */
