<?php
/**
 * A WordPress that can be told to fail, plus the assertion harness — shared fixture.
 *
 * Extracted verbatim from tests/test-write-path.php, which grew it and is still its heaviest
 * consumer. It lives here because a SECOND suite now needs the same site: tests/test-replay.php
 * asserts that building the same page twice emits identical bytes, and it cannot do that against
 * a copy. Two fake WordPresses drift, and the day they differ is the day one suite proves
 * determinism the other one has already lost.
 *
 * The including suite MUST set $GLOBALS['es_suite'] to a short name before requiring this file.
 * It keys the temp sandbox, so two suites running at once do not delete each other's files.
 *
 * Defines ABSPATH, ES_AUDIT_SILENT, OBJECT and WP_CONTENT_DIR; loads es-builder.php, the theme
 * parts example and an Elementor Pro stub; and exposes ok()/grab()/has()/log_mark()/log_since()/
 * any_layout_written() plus $pass and $fail.
 */

if ( ! isset( $GLOBALS['es_suite'] ) || '' === (string) $GLOBALS['es_suite'] ) {
	fwrite( STDERR, "ENTORNO: \$GLOBALS['es_suite'] no esta puesto antes de requerir fake-wp.php\n" );
	exit( 2 );
}
$GLOBALS['es_repo'] = dirname( dirname( __DIR__ ) );

define( 'ABSPATH', dirname( __DIR__ ) );
define( 'ES_AUDIT_SILENT', true );
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}

/* error_log() is the OTHER channel, and the only one still there once the operator has scrolled
   past. Redirecting it to a file both keeps this run readable and makes it assertable. */
$GLOBALS['es_log'] = tempnam( sys_get_temp_dir(), 'eswp' );
if ( false === ini_set( 'error_log', $GLOBALS['es_log'] ) ) {
	fwrite( STDERR, "ENTORNO: ini_set('error_log') fue rechazado, el canal durable no se puede leer\n" );
	exit( 2 );
}
register_shutdown_function(
	function () {
		@unlink( $GLOBALS['es_log'] );
	}
);

/* ---------------------------------------------------------------------------
 * A WordPress that can be told to fail.
 * ------------------------------------------------------------------------- */

class WP_Error {
	private $code;
	private $message;
	public function __construct( $code = '', $message = '' ) {
		$this->code    = $code;
		$this->message = $message;
	}
	public function get_error_message() {
		return $this->message;
	}
	public function get_error_code() {
		return $this->code;
	}
}
function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}

/**
 * Reset the fake site.
 *
 * `insert_ret` / `update_ret` hold the EXACT value the corresponding WordPress function returns;
 * null means "behave normally". `rename_to` reproduces wp_unique_post_slug() handing back a slug
 * that is not the one that was asked for.
 */
function wp_fake_reset() {
	$GLOBALS['wp'] = array(
		'posts'      => array(),   /* id   => stdClass{ ID, post_status, post_name, post_title } */
		'by_slug'    => array(),   /* slug => the same object                                    */
		'meta'       => array(),   /* id   => array( key => value )                              */
		'terms'      => array(),   /* id   => array( taxonomy => terms )                         */
		'next_id'    => 100,
		'insert_ret' => null,
		'update_ret' => null,
		'rename_to'  => null,
		'options'    => array(),
		'option_ro'  => array(),   /* names update_option() accepts and silently does not write */
		'meta_ro'    => array(),   /* meta keys update/delete_post_meta accept and do not touch */
		/* The site's registered post types, and the font families installed in whichever of them is
		   a font type. es_font_serving_check() DERIVES the type name from this list instead of
		   carrying one plugin's constant, so the fixture has to be able to rename it. */
		'post_types' => array( 'post', 'page', 'attachment' ),
		'font_posts' => array(),   /* post type => array of published post_title */
	);
	/* The builder's own per-run state lives in globals, so a fixture that forgot these would
	   inherit the previous fixture's approvals and its list of saved pages — and the assertions
	   that depend on them would pass for the wrong reason, which is the failure this whole suite
	   is about. Reset with the site, not beside it. */
	$GLOBALS['es_preflight_slugs'] = array();
	$GLOBALS['es_saved_pages']     = array();
	/* The style registry is site state too, and it is the one a fixture is likeliest to forget: it
	   is built by wp_fake_style() rather than declared here, so a leftover from the previous
	   scenario would silently answer the next one's question about Google. */
	unset( $GLOBALS['wp_styles'] );
}

/**
 * Approve slugs the way a real build does — through the preflight, whose printed block IS the
 * approval. `grab()` only keeps that block out of the output an assertion is about to read.
 */
function approve() {
	$slugs = func_get_args();
	grab(
		function () use ( $slugs ) {
			return es_overwrite_preflight( $slugs );
		}
	);
}

function wp_fake_page( $slug, $status = 'publish', $title = 'existente', $content = '', array $meta = array(), $type = 'page' ) {
	$w   = &$GLOBALS['wp'];
	$id  = $w['next_id']++;
	$obj = (object) array(
		'ID'           => $id,
		'post_status'  => $status,
		'post_name'    => $slug,
		'post_title'   => $title,
		'post_content' => $content,
		'post_type'    => $type,
	);

	$w['posts'][ $id ]     = $obj;
	$w['by_slug'][ $slug ] = $obj;
	if ( $meta ) {
		$w['meta'][ $id ] = $meta;
	}

	return $id;
}

/** The one backup key this run wrote for $id, or '' — the tests never hard-code a timestamp. */
function backup_of( $id ) {
	foreach ( array_keys( isset( $GLOBALS['wp']['meta'][ $id ] ) ? $GLOBALS['wp']['meta'][ $id ] : array() ) as $k ) {
		if ( 0 === strpos( $k, '_es_page_backup_' ) ) {
			return $GLOBALS['wp']['meta'][ $id ][ $k ];
		}
	}

	return '';
}

/**
 * The real one IGNORES its post_type argument for attachments.
 *
 * MEASURED on a live install: an attachment at a given slug is returned for a `'page'` lookup,
 * with `post_type` = `attachment`. The fake used to return only what tests put in `by_slug` with
 * no type at all, which made the whole defect class unreachable — every caller here treated the
 * result as a page, and no assertion could tell the difference.
 */
function get_page_by_path( $slug, $output = OBJECT, $post_type = 'page' ) {
	$w = &$GLOBALS['wp'];
	return isset( $w['by_slug'][ $slug ] ) ? $w['by_slug'][ $slug ] : null;
}

function wp_insert_post( array $args ) {
	$w = &$GLOBALS['wp'];
	if ( null !== $w['insert_ret'] ) {
		return $w['insert_ret'];
	}
	/* The real one never guarantees post_name survives; that is finding 15. */
	$name = ( null === $w['rename_to'] ) ? $args['post_name'] : $w['rename_to'];
	$id   = $w['next_id']++;
	$obj  = (object) array(
		'ID'          => $id,
		'post_status' => isset( $args['post_status'] ) ? $args['post_status'] : 'publish',
		'post_name'   => $name,
		'post_title'  => isset( $args['post_title'] ) ? $args['post_title'] : '',
		'post_type'   => isset( $args['post_type'] ) ? $args['post_type'] : 'page',
	);

	$w['posts'][ $id ]    = $obj;
	$w['by_slug'][ $name ] = $obj;

	return $id;
}

function wp_update_post( array $args ) {
	$w = &$GLOBALS['wp'];
	if ( null !== $w['update_ret'] ) {
		return $w['update_ret'];
	}
	$id = $args['ID'];
	if ( isset( $w['posts'][ $id ] ) ) {
		if ( isset( $args['post_title'] ) ) {
			$w['posts'][ $id ]->post_title = $args['post_title'];
		}
		/* post_name has to MOVE, in the object and in the slug index. Without this the fake would
		   accept a rename and keep answering with the old slug, so es_migrate_slug()'s read-back
		   check could never be exercised — the branch would be untestable rather than untested. */
		if ( isset( $args['post_name'] ) && $args['post_name'] !== $w['posts'][ $id ]->post_name ) {
			/* `rename_to` applies here too: wp_unique_post_slug() runs on UPDATE as well as on
			   insert, and the slug space includes attachments and posts, which get_page_by_path()
			   never sees. So a destination no PAGE holds can still come back suffixed, and the
			   update reports success either way. */
			$name = ( null === $w['rename_to'] ) ? $args['post_name'] : $w['rename_to'];
			unset( $w['by_slug'][ $w['posts'][ $id ]->post_name ] );
			$w['posts'][ $id ]->post_name = $name;
			$w['by_slug'][ $name ]        = $w['posts'][ $id ];
		}
	}

	return $id;   /* WordPress returns the post id on success. */
}

function get_post_field( $field, $post ) {
	$w  = &$GLOBALS['wp'];
	$id = is_object( $post ) ? $post->ID : (int) $post;
	if ( ! isset( $w['posts'][ $id ] ) || ! isset( $w['posts'][ $id ]->$field ) ) {
		return '';
	}

	return $w['posts'][ $id ]->$field;
}

/**
 * `meta_ro` reproduces a meta write that is ACCEPTED and does not land.
 *
 * Without it, three mutations survived the whole suite: a restore that reports what it attempted
 * instead of what it verified, a prune that assumes its deletes worked, and a restore that forgets
 * to slash `_elementor_data`. All three read-back branches were UNREACHABLE because the fake could
 * not fail. That is the fourth time in this branch a surviving mutant turned out to be the test
 * double's fault rather than the assertion's.
 */
function update_post_meta( $id, $key, $value ) {
	if ( in_array( $key, $GLOBALS['wp']['meta_ro'], true ) ) {
		return false;
	}
	/* The real one runs wp_unslash() on its input, which is the ONLY reason wp_slash() exists at the
	   call site: without it, the backslashes inside encoded JSON are stripped and the value lands
	   corrupted. Modelling only one half of that pair made the missing-slash mutation invisible. */
	$GLOBALS['wp']['meta'][ $id ][ $key ] = is_string( $value ) ? stripslashes( $value ) : $value;
	return true;
}
/**
 * Real get_post_meta() returns the WHOLE meta map when called with no key, which is how
 * es_backup_keys() enumerates backup keys it cannot guess the timestamps of. A stub that required
 * a key would have made that function unreachable rather than untested.
 */
function get_post_meta( $id, $key = null, $single = false ) {
	$all = isset( $GLOBALS['wp']['meta'][ $id ] ) ? $GLOBALS['wp']['meta'][ $id ] : array();
	if ( null === $key ) {
		return $all;
	}

	return isset( $all[ $key ] ) ? $all[ $key ] : '';
}
function delete_post_meta( $id, $key ) {
	if ( in_array( $key, $GLOBALS['wp']['meta_ro'], true ) ) {
		return false;
	}
	unset( $GLOBALS['wp']['meta'][ $id ][ $key ] );
	return true;
}
function wp_set_object_terms( $id, $terms, $taxonomy ) {
	$GLOBALS['wp']['terms'][ $id ][ $taxonomy ] = $terms;
	return array();
}
/**
 * The real one ADDS SLASHES, which is the whole reason `_elementor_data` needs it: the value goes
 * through `wp_unslash()` on the way into the database. A stub returning the value untouched made
 * "did the caller slash it?" unanswerable, so forgetting the slash was undetectable.
 */
function wp_slash( $v ) {
	return is_string( $v ) ? addslashes( $v ) : $v;
}
function wp_json_encode( $v ) {
	return json_encode( $v );
}
function get_option( $name, $default = false ) {
	$w = &$GLOBALS['wp'];
	return array_key_exists( $name, $w['options'] ) ? $w['options'][ $name ] : $default;
}

/**
 * `option_ro` reproduces a write that is accepted and does not land.
 *
 * The return value is deliberately NOT a reliable success signal even in real WordPress:
 * update_option() also returns false when the new value equals the old one. A caller that trusts
 * the boolean either misses a failure or invents one, which is why the only honest check is to
 * read the option back.
 */
function update_option( $name, $value ) {
	$w = &$GLOBALS['wp'];
	if ( in_array( $name, $w['option_ro'], true ) ) {
		return false;
	}
	$w['options'][ $name ] = $value;

	return true;
}
/**
 * A style registry that can tell REGISTERED from ENQUEUED, because WordPress can.
 *
 * What this replaces was `(object) array( 'registered' => ... )` written inline in the suite, and
 * for as long as it existed it made a real defect unreachable. `es_font_serving_check()` read
 * `registered` and called a hit there PROOF that Google was serving the family; every fixture fed
 * that stub a registry whose only Google entry was one a test had deliberately put there, so the
 * probe looked exact. WordPress core REGISTERS `open-sans` pointing at `fonts.googleapis.com` on
 * every installation and enqueues it nowhere — so on a real site the probe matched ALWAYS, and the
 * RGPD warning about visitor IPs leaving the EU printed on sites that request nothing from Google.
 * MEASURED on a live Hostinger site through the bridge: `open-sans` registered with `enqueued`
 * false and `done` false, `wp-editor-font` the same, `elementor_google_fonts` = "0", ZERO
 * occurrences of `googleapis` or `gstatic` in the rendered front end — and the full warning
 * printed anyway. A stub with one array cannot express that state, so nothing could assert against
 * it. Same disease as get_page_by_path() above: a double simpler than the thing it doubles hides
 * exactly the bugs that live in the difference.
 *
 * The shapes are WP_Dependencies': `registered` maps handle => object carrying `->src`, while
 * `queue` and `done` are flat lists of HANDLES with no src on them. A probe that expects to read a
 * src straight out of `queue` finds nothing on a real site, so this fake may not hand it one.
 *
 * $state is 'registered' (the default: known to WordPress, asked for by nobody), 'enqueued' (in
 * the queue for this request) or 'done' (already printed, which is where the queue has been
 * drained and only `done` still remembers).
 */
function wp_fake_style( $handle, $src, $state = 'registered' ) {
	if ( ! isset( $GLOBALS['wp_styles'] ) ) {
		$GLOBALS['wp_styles'] = (object) array(
			'registered' => array(),
			'queue'      => array(),
			'done'       => array(),
		);
	}
	$reg                        = $GLOBALS['wp_styles'];
	$reg->registered[ $handle ] = (object) array(
		'handle' => $handle,
		'src'    => $src,
	);
	if ( 'enqueued' === $state ) {
		$reg->queue[] = $handle;
	} elseif ( 'done' === $state ) {
		$reg->done[] = $handle;
	}
}

/**
 * The two Google stylesheets core registers on EVERY installation and enqueues nowhere.
 *
 * Both handles and both URLs are the measured ones, not plausible ones: a fixture that invented a
 * handle would still pass a probe reading `registered` and would prove nothing about the site
 * every client actually has.
 */
function wp_fake_core_styles() {
	wp_fake_style( 'open-sans', 'https://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,300,400,600&subset=latin,latin-ext&display=fallback' );
	wp_fake_style( 'wp-editor-font', 'https://fonts.googleapis.com/css?family=Noto+Serif:400,400i,700,700i' );
}

/**
 * Two callers, two meanings.
 *
 * es_save_theme_part() looks a template up by slug through get_posts(); es_img() looks an
 * attachment up the same way. Returning array() for BOTH is what let a mutation that breaks the
 * theme-part UPDATE path survive: that branch was unreachable, so nothing could be asserted about
 * it. Attachments still find nothing, because no fixture here asks for an image.
 */
function get_posts( $args ) {
	$w = &$GLOBALS['wp'];
	if ( isset( $args['post_type'] ) && 'elementor_library' === $args['post_type'] ) {
		$slug = isset( $args['name'] ) ? $args['name'] : '';
		return isset( $w['by_slug'][ $slug ] ) ? array( $w['by_slug'][ $slug ] ) : array();
	}
	/* A LIST of post types is the third caller — es_font_serving_check(), which asks for every type
	   whose name looks like a font type at once. A scalar is the two lookups above; keeping the
	   shapes apart is what stopped the theme-part branch being unreachable, and the same reasoning
	   applies here. */
	if ( isset( $args['post_type'] ) && is_array( $args['post_type'] ) ) {
		$hits = array();
		foreach ( $w['font_posts'] as $tipo => $titulos ) {
			if ( ! in_array( $tipo, $args['post_type'], true ) ) {
				continue;
			}
			foreach ( $titulos as $titulo ) {
				$hits[] = (object) array( 'post_title' => $titulo );
			}
		}
		return $hits;
	}

	return array();
}
function wp_get_attachment_url( $id ) {
	return '';
}

wp_fake_reset();

require_once $GLOBALS['es_repo'] . '/skills/elementor-core/assets/es-builder.php';

/* The example asset resolves its dependency by absolute sandbox path, so it needs that path to
   exist before it can be loaded at all. The shim re-requires the file already loaded above, which
   require_once resolves to the same realpath and therefore skips. */
$GLOBALS['es_sandbox'] = sys_get_temp_dir() . '/es-' . $GLOBALS['es_suite'] . '-' . getmypid();
if ( ! @mkdir( $GLOBALS['es_sandbox'] . '/novamira-sandbox', 0777, true ) ) {
	fwrite( STDERR, "ENTORNO: no se pudo crear el sandbox temporal en " . $GLOBALS['es_sandbox'] . "\n" );
	exit( 2 );
}
file_put_contents(
	$GLOBALS['es_sandbox'] . '/novamira-sandbox/es-builder.php',
	"<?php\nrequire_once " . var_export( $GLOBALS['es_repo'] . '/skills/elementor-core/assets/es-builder.php', true ) . ";\n"
);
register_shutdown_function(
	function () {
		@unlink( $GLOBALS['es_sandbox'] . '/novamira-sandbox/es-builder.php' );
		@unlink( $GLOBALS['es_sandbox'] . '/pro-stub.php' );
		@rmdir( $GLOBALS['es_sandbox'] . '/novamira-sandbox' );
		@rmdir( $GLOBALS['es_sandbox'] );
	}
);
define( 'WP_CONTENT_DIR', $GLOBALS['es_sandbox'] );
require_once $GLOBALS['es_repo'] . '/skills/elementor-theme-parts/assets/es-theme-parts.example.php';

/* A stand-in for Elementor Pro, in its own file because a namespace declaration cannot share a
   file with non-namespaced code. Without it es_rebuild_theme_conditions() returns false at its
   first guard and the registration/rival branches are UNREACHABLE — which is how a check that
   reports green on a template hijack went untested. instance() returns null until $es_pro is set,
   so every assertion written before this existed keeps the world it was written against. */
file_put_contents(
	$GLOBALS['es_sandbox'] . '/pro-stub.php',
	'<?php' . "\n"
	. 'namespace ElementorPro\Modules\ThemeBuilder;' . "\n"
	. 'class Cache { public function regenerate() { $GLOBALS["wp"]["regenerated"] = true; } }' . "\n"
	. 'class ConditionsManager { public function get_cache() { return new Cache(); } }' . "\n"
	. 'class Module {' . "\n"
	. '  public static function instance() { return empty( $GLOBALS["es_pro"] ) ? null : new self(); }' . "\n"
	. '  public function get_conditions_manager() { return new ConditionsManager(); }' . "\n"
	. '}' . "\n"
);
require_once $GLOBALS['es_sandbox'] . '/pro-stub.php';

/* ---------------------------------------------------------------------------
 * Harness.
 * ------------------------------------------------------------------------- */

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

/** Run $fn with stdout captured, and hand back BOTH what it printed and what it returned. */
function grab( $fn ) {
	ob_start();
	$ret = $fn();
	return array( 'out' => ob_get_clean(), 'ret' => $ret );
}

function has( $haystack, $needle ) {
	return false !== strpos( $haystack, $needle );
}

function log_mark() {
	clearstatcache();
	return strlen( (string) @file_get_contents( $GLOBALS['es_log'] ) );
}
function log_since( $offset ) {
	clearstatcache();
	return substr( (string) @file_get_contents( $GLOBALS['es_log'] ), $offset );
}

/** Did ANY post end up with a layout written to it? */
function any_layout_written() {
	foreach ( $GLOBALS['wp']['meta'] as $meta ) {
		if ( isset( $meta['_elementor_data'] ) ) {
			return true;
		}
	}
	return false;
}
