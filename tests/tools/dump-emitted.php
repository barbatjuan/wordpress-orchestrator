<?php
/**
 * Deterministic dump of everything the visual layer emits.
 *
 * Run:  php tests/tools/dump-emitted.php > tests/fixtures/emitted-golden.txt
 *
 * WHY IT LIVES IN tests/tools/ AND NOT IN tests/. It used to sit directly in
 * tests/, where `tests/*.php` — the glob the plan's own gate command and
 * framework-audit.php:1642 both use — swept it up with the four suites. Running
 * it printed a 2900-line dump where a runner expects one `N OK / 0 FAIL` line:
 * a file that looks like a test, is run like a test, and asserts nothing. One
 * directory down, the glob no longer reaches it, the four suites still do, and
 * RT_NO_OFFLINE_TESTS still finds them.
 *
 * WHY THIS FILE EXISTS. Task 1 of the axes plan claims the token extraction is
 * byte-identical. That claim lived in a scratch script that was deleted, so
 * nothing committed protected it, and the review proved what that costs: with
 * the four suites green you could change the accent's default, swap the heading
 * family for Comic Sans, collapse two drifted border tokens into one, replace a
 * whole shadow, and turn a white feature card near-black — and every check
 * still said OK. A golden dump turns "it was byte-identical once" into a
 * property the suite re-checks on every run.
 *
 * This dump is NOT frozen for ever. Task 2 is allowed — required — to move
 * values. Its "WRITE DOWN THE SHIFT" step is exactly: regenerate this file and
 * show the diff. A change that appears in the diff is a decision; a change that
 * does not appear anywhere is the defect.
 *
 * THREE TRAPS, all of them proven live on this machine, all defended here:
 *
 *  1. `define( 'ABSPATH', __DIR__ )` is mandatory. es-builder.php:6-8 exits
 *     without it, the dump is 0 bytes — and `cmp` of two EMPTY files passes.
 *     A green proof over nothing is worse than no proof. The guard below turns
 *     that into a loud exit(2) instead of a silent pass.
 *  2. PHP on Windows resolves `/tmp/x` to `C:\tmp\x` while Git Bash's `/tmp`
 *     is somewhere else entirely, so a file PHP "wrote" is not where the shell
 *     looks. Nothing here writes a file: it echoes to STDOUT and the shell
 *     redirects.
 *  3. get_posts()/wp_get_attachment_url() must be stubbed or es_img() finds
 *     nothing, and es_photo / es_card / es_cta_banner fall out of coverage
 *     while the dump gives no signal that it skipped them. es_cta_banner alone
 *     carries four token reads nothing else exercises.
 *
 * And the dump ends with a sentinel line, so a truncated dump cannot pass for
 * a match.
 */

define( 'ABSPATH', __DIR__ );
define( 'ES_AUDIT_SILENT', true );

$es_raiz = dirname( dirname( __DIR__ ) );

/* Trap 3. A hit, not a miss: a miss makes es_img() warn (noise in the dump)
   AND strips the image helpers out of coverage. Values are constants so the
   dump stays deterministic across runs and machines.
   The post_type branch is the SAME distinction tests/test-write-path.php:296
   documents: es_img() asks for an attachment, es_save_theme_part() asks for an
   elementor_library template. Answering both with the image post sends every
   sibling build down the "updated" branch of a template it never created. */
function get_posts( $args ) {
	if ( isset( $args['post_type'] ) && 'elementor_library' === $args['post_type'] ) {
		return array();
	}
	$p     = new stdClass();
	$p->ID = 4242;
	return array( $p );
}
function wp_get_attachment_url( $id ) {
	return 'https://example.invalid/wp-content/uploads/es-golden-' . $id . '.jpg';
}

/**
 * The rest of the WordPress surface es_save_theme_part() touches, faked just
 * far enough to reach the ONE line that matters — the update_post_meta() that
 * writes `_elementor_data`. That string IS the emitted data; everything else in
 * the save pipeline is plumbing this dump has no opinion about.
 *
 * wp_get_nav_menu_object() returning a HIT is trap 3 again, and the most
 * expensive instance of it in the repo: es-theme-parts.example.php builds its
 * nav widget only `if ( wp_get_nav_menu_object( $menu_slug ) )`, and that one
 * widget carries more token reads than any other single widget in the three
 * sibling files. A miss drops it, the build still "succeeds", and the dump says
 * nothing. The guard further down asserts it landed rather than trusting this.
 */
$GLOBALS['es_emitido'] = array();
$GLOBALS['es_slugs']   = array();
function wp_insert_post( array $args ) {
	$id                        = 5000 + count( $GLOBALS['es_slugs'] );
	$GLOBALS['es_slugs'][ $id ] = isset( $args['post_name'] ) ? $args['post_name'] : '';
	return $id;
}
function wp_update_post( array $args ) {
	return isset( $args['ID'] ) ? $args['ID'] : 0;
}
function is_wp_error( $thing ) {
	return false;
}
function get_post_field( $field, $post ) {
	return isset( $GLOBALS['es_slugs'][ $post ] ) ? $GLOBALS['es_slugs'][ $post ] : '';
}
function wp_set_object_terms( $id, $terms, $taxonomy ) {
	return array();
}
function update_post_meta( $id, $key, $value ) {
	if ( '_elementor_data' === $key ) {
		$slug                             = isset( $GLOBALS['es_slugs'][ $id ] ) ? $GLOBALS['es_slugs'][ $id ] : (string) $id;
		$GLOBALS['es_emitido'][ $slug ] = $value;
	}
	return true;
}
function delete_post_meta( $id, $key ) {
	return true;
}
function wp_slash( $v ) {
	return $v;
}
function wp_json_encode( $v ) {
	return json_encode( $v );
}
function wp_get_nav_menu_object( $slug ) {
	$m             = new stdClass();
	$m->term_id    = 77;
	$m->slug       = $slug;
	return $m;
}
function home_url( $path = '/' ) {
	return 'https://example.invalid' . $path;
}

require_once $es_raiz . '/skills/elementor-core/assets/es-builder.php';

/* Trap 1, made loud. If the require above hit the ABSPATH guard and exited,
   we never get here at all; if it loaded something that is not the builder,
   this catches it before an empty dump can masquerade as a pass. */
if ( ! function_exists( 'es_tokens' ) || ! function_exists( 'es_feature_card' ) ) {
	fwrite( STDERR, "ENTORNO: es-builder.php no se cargo (¿falta ABSPATH?). El volcado seria VACIO.\n" );
	exit( 2 );
}

/**
 * The three sibling assets — the header, the footer, the shop and the product
 * page. They resolve their dependencies by ABSOLUTE sandbox path and `return`
 * at top level when one is missing, so without the shims below they load,
 * define NOTHING, and every build function disappears. That failure is silent
 * and looks exactly like a pass, which is trap 1 wearing a different hat: the
 * guard after the requires is what turns it into an exit(2).
 *
 * Each shim re-requires the real file, which require_once resolves to the same
 * realpath and skips — the same trick tests/test-write-path.php:322 uses.
 */
$es_caja = sys_get_temp_dir() . '/es-golden-' . getmypid();
if ( ! @mkdir( $es_caja . '/novamira-sandbox', 0777, true ) ) {
	fwrite( STDERR, "ENTORNO: no se pudo crear el sandbox temporal en $es_caja\n" );
	exit( 2 );
}
$es_partes = $es_raiz . '/skills/elementor-theme-parts/assets/es-theme-parts.example.php';
file_put_contents( $es_caja . '/novamira-sandbox/es-builder.php', "<?php\nrequire_once " . var_export( $es_raiz . '/skills/elementor-core/assets/es-builder.php', true ) . ";\n" );
file_put_contents( $es_caja . '/novamira-sandbox/es-theme-parts.php', "<?php\nrequire_once " . var_export( $es_partes, true ) . ";\n" );
/* es_warn() and es_container_report() ALSO speak through error_log(), which
   with no destination configured lands on STDERR — and tests/test-write-path.php
   runs this dump through shell_exec(), so those twelve lines would print over
   the suite's own output on every run. Routed into the sandbox and deleted with
   it. Nothing is lost: what this dump asserts, it asserts on the DATA below
   (four templates, a nav widget, a closing sentinel), never on the log. */
ini_set( 'error_log', $es_caja . '/pipeline.log' );
register_shutdown_function(
	function () use ( $es_caja ) {
		/* Registered as ONE closure, in this order, on purpose: an rmdir queued
		   before the log's unlink runs first and fails on a non-empty
		   directory, leaving the sandbox behind on every run. */
		@unlink( $es_caja . '/novamira-sandbox/es-builder.php' );
		@unlink( $es_caja . '/novamira-sandbox/es-theme-parts.php' );
		@unlink( $es_caja . '/pipeline.log' );
		@rmdir( $es_caja . '/novamira-sandbox' );
		@rmdir( $es_caja );
	}
);
define( 'WP_CONTENT_DIR', $es_caja );

require_once $es_partes;
require_once $es_raiz . '/skills/woocommerce/assets/es-shop-template.example.php';
require_once $es_raiz . '/skills/woocommerce/assets/es-product-single.example.php';

if ( ! function_exists( 'es_build_theme_parts' ) || ! function_exists( 'es_build_shop_template' ) || ! function_exists( 'es_build_product_single' ) ) {
	fwrite( STDERR, "ENTORNO: uno de los tres ficheros hermanos no definio su build (¿falta WP_CONTENT_DIR o el shim?). Sus literales quedarian SIN cubrir.\n" );
	exit( 2 );
}

/**
 * Every visual helper, every branch that carries a token of its own.
 *
 * es_btn's four style branches are all here on purpose: the branch you leave
 * out is the branch that keeps its hand-written literal, and no check would
 * ever look at it.
 *
 * "Every" is now MEASURED rather than maintained by hand: tests/test-write-path.php
 * reads every `function es_*` declared between es-builder.php's two region
 * markers and fails if one of them is not named in here. It was measured the
 * other way first — deleting `es_feature_card` from this list and regenerating
 * left all four suites and the audit green, so a helper could leave coverage in
 * silence and, once outside the golden, nothing but the audit's literal row was
 * looking at it at all.
 *
 * That is why the primitives and the two scale TABLES are in the list too. They
 * are not widgets and their entries look odd next to `es_card`, but the rule
 * that admits no exception is the only kind that survives: an exemption list is
 * where the next uncovered helper goes to hide. Dumping the tables is not
 * ceremony either — it pins which step each heading tag takes and at which three
 * widths, so moving h2 off step 2 shows up as a diff instead of as nothing.
 */
function es_dump_visuals() {
	return array(
		'es_t'              => es_t( 'accent' ),
		'es_fs'             => es_fs( 1 ),
		'es_fs_at'          => array( es_fs_at( 3, 430 ), es_fs_at( 3, 768 ), es_fs_at( 3, 1280 ) ),
		'es_sp'             => es_sp( 88 ),
		'es_h_widths'       => es_h_widths(),
		'es_h_scale'        => es_h_scale(),
		'es_box'            => es_box( 88, 24, 88, 24 ),
		'es_box_pct'        => es_box( 5, 0, 5, 0, '%' ),
		'es_size'           => es_size( 19 ),
		'es_size_em'        => es_size( 1.65, 'em' ),
		'es_helper_css'     => es_helper_css( 'selector{display:block;}' ),
		'es_card_hover_css' => es_card_hover_css(),
		'es_products_css'   => es_products_css( 'selector .woocommerce-pagination{margin-top:32px;}' ),
		'es_eyebrow'        => es_eyebrow( 'etiqueta' ),
		'es_h1'             => es_h( 'titular', 'h1' ),
		'es_h2'             => es_h( 'titulo' ),
		'es_h3'             => es_h( 'subtitulo', 'h3' ),
		'es_p'              => es_p( 'texto de cuerpo' ),
		'es_btn_primary'    => es_btn( 'Comprar', '#', 'primary' ),
		'es_btn_dark'       => es_btn( 'Comprar', '#', 'dark' ),
		'es_btn_outline'    => es_btn( 'Comprar', '#', 'outline' ),
		'es_btn_outline_lt' => es_btn( 'Comprar', '#', 'outline-light' ),
		'es_photo'          => es_photo( 'foto' ),
		'es_card'           => es_card( 'foto', 'titulo', 'texto' ),
		'es_card_linked'    => es_card( 'foto', 'titulo', 'texto', '/destino' ),
		'es_iconbox'        => es_iconbox( 'fas fa-check', 'titulo', 'texto' ),
		'es_feature_card'   => es_feature_card( 'fas fa-check', 'titulo', 'texto' ),
		'es_cta_banner'     => es_cta_banner( 'foto', 'titulo', 'texto', 'Ir', '#' ),
		'es_row'            => es_row( array( es_p( 'a' ), es_p( 'b' ) ) ),
		'es_grid'           => es_grid( 3, array( es_p( 'a' ) ) ),
		'es_split'          => es_split( array( es_p( 'a' ), es_p( 'b' ) ) ),
		'es_split_rev'      => es_split( array( es_p( 'a' ) ), array( 'reverse' => true, 'gap' => 64 ) ),
		'es_section'        => es_section( array( es_p( 'a' ) ) ),
		'es_wide'           => es_wide( es_p( 'a' ), 58 ),
	);
}

/**
 * The sentinel override set.
 *
 * Named after the key rather than numbered, so adding a token does not
 * renumber — and therefore does not churn — every other line of the golden.
 * The trailing `_Z` is what keeps `accent` from matching inside
 * `ZTOK_accent_hover_Z`.
 *
 * A numeric token cannot take a text sentinel without breaking the arithmetic
 * that reads it, so numbers get an out-of-range NUMBER instead.
 *
 * INTEGERS get a big one (9974+). FLOATS get a small one (7.1+), and the
 * difference is not taste. `type_ratio` is a float that gets EXPONENTIATED:
 * es_fs_at() computes `fs_base * ratio^3`, and at a 9974 sentinel that is
 * 9.9e15 — past 2^53, where a double stops representing integers exactly and
 * the last digits come from whatever `pow()` the platform's libm ships. Pinning
 * that in a golden means pinning a number this repo does not control, and the
 * suite would go red on another machine with no defect to find. 7.1 is far
 * outside every documented position of every float token (`type_ratio`
 * 1.2–1.618, `display_lh` 0.82–1.25, `sp_scale` 0.8–1.7), so it still proves a
 * reader exists, and 7.1^3 stays where arithmetic is exact. The same rule
 * applies to whatever float token lands next.
 */
function es_dump_sentinels() {
	$out = array();
	$n   = 0;
	foreach ( es_tokens() as $clave => $valor ) {
		if ( is_string( $valor ) ) {
			$out[ $clave ] = 'ZTOK_' . $clave . '_Z';
		} elseif ( is_int( $valor ) ) {
			$out[ $clave ] = 9973 + ( ++$n );
		} elseif ( is_float( $valor ) ) {
			$out[ $clave ] = 7.0 + ( ++$n ) / 10;
		} else {
			fwrite( STDERR, "ENTORNO: el token '$clave' no es texto ni numero; el volcado no sabe sustituirlo.\n" );
			exit( 2 );
		}
	}
	return $out;
}

/**
 * What the three sibling assets write to `_elementor_data` — the header, the
 * footer, the shop archive and the product page.
 *
 * Captured at update_post_meta() rather than rebuilt by hand, so what this
 * pins is exactly the bytes that reach the database. Rebuilding the element
 * trees here instead would pin a SECOND copy of the build, and a second copy
 * is the drift these three files exist as a cautionary tale about.
 */
function es_dump_hermanos() {
	$GLOBALS['es_emitido'] = array();
	$GLOBALS['es_slugs']   = array();
	/* The save pipeline talks on stdout: with no Elementor Pro here the
	   theme-conditions cache cannot be regenerated, and es_warn() says so four
	   times. That is pipeline noise, not emitted data. Discarded, not pinned —
	   and the coverage guard below asserts on the DATA, never on the noise. */
	ob_start();
	es_build_theme_parts();
	es_build_shop_template();
	es_build_product_single();
	ob_end_clean();

	$out = array();
	foreach ( $GLOBALS['es_emitido'] as $slug => $json ) {
		$out[ $slug ] = json_decode( $json, true );
	}
	ksort( $out );  /* insertion order is build order; sorted so a reordered build is not a diff */

	/* Trap 3, asserted rather than hoped for. Four templates, and the nav widget
	   inside the header — the single densest cluster of token reads in the three
	   files, and the only one built behind an `if`. */
	$plano = json_encode( $out );
	foreach ( array( 'es-header', 'es-footer', 'es-shop-archive', 'es-single-product' ) as $exigida ) {
		if ( ! array_key_exists( $exigida, $out ) ) {
			fwrite( STDERR, "ENTORNO: la plantilla '$exigida' no llego a _elementor_data; sus literales quedarian SIN cubrir.\n" );
			exit( 2 );
		}
	}
	if ( false === strpos( $plano, 'nav-menu' ) ) {
		fwrite( STDERR, "ENTORNO: el header se construyo SIN su nav (¿wp_get_nav_menu_object devolvio vacio?); es el widget con mas tokens del fichero.\n" );
		exit( 2 );
	}
	return $out;
}

function es_dump_section( $titulo, array $override ) {
	es_uid_reset( 'golden' );  /* es-builder.php:27 — ids must be reproducible or the diff is noise */
	es_tokens( $override );
	$banderas = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
	echo "===== $titulo =====\n";
	echo json_encode( es_dump_visuals(), $banderas ), "\n";

	echo "----- hermanos: header, footer, tienda y ficha de producto -----\n";
	$txt = json_encode( es_dump_hermanos(), $banderas );
	/* gmdate('Y') in the footer copyright is the one value in these three files
	   that moves with nobody editing them: pinned as-is, this golden would go
	   red by itself on 1 January. Only the YEAR is masked, so the copyright
	   line stays covered — a literal that displaced it still shows up here. */
	$txt = str_replace( '© ' . gmdate( 'Y' ) . ' ', '© AAAA ', $txt );
	echo $txt, "\n";
}

/* Section 1: what a build actually emits. This is the byte-identity contract. */
$defaults = es_tokens();
es_dump_section( 'VALORES POR DEFECTO', $defaults );

/* Section 2: the same page with every token replaced by a sentinel named after
   its key. Any hand-written literal that survived the extraction shows up here
   as the literal instead of the sentinel — which is the one thing section 1
   structurally cannot see, because there a token and a literal are the same
   bytes. */
es_dump_section( 'TOKENS SUSTITUIDOS POR CENTINELAS', es_dump_sentinels() );

es_tokens( $defaults );

echo "===== FIN DEL VOLCADO =====\n";
