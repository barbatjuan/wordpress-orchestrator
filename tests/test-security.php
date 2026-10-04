<?php
/**
 * Behavioural assertions for the security mu-plugin (skills/wordpress-security/assets/es-security.php).
 *
 * Run:  php tests/test-security.php     (exit 0 = green)
 *
 * No WordPress here. The test stubs the handful of WordPress functions the plugin touches, records
 * every hook the plugin registers, then calls the registered callbacks and asserts on what they
 * return. Two things need a fresh process, because PHP cannot undefine a constant or a class: the
 * "DISALLOW_FILE_EDIT already defined" case runs in a child (`--predefined`), and the Jetpack case
 * runs last in this one, once its class exists.
 */
$predefined = isset( $argv ) && in_array( '--predefined', $argv, true );

define( 'ABSPATH', __DIR__ );

$GLOBALS['hooks']  = array();
$GLOBALS['logged'] = false;
$GLOBALS['admin']  = false;
$GLOBALS['ssl']    = false;
$GLOBALS['404']    = 0;

function add_filter( $tag, $cb, $prio = 10, $args = 1 ) { $GLOBALS['hooks'][ $tag ][] = $cb; return true; }
function add_action( $tag, $cb, $prio = 10, $args = 1 ) { return add_filter( $tag, $cb, $prio, $args ); }
function is_user_logged_in() { return $GLOBALS['logged']; }
function is_admin() { return $GLOBALS['admin']; }
function is_ssl() { return $GLOBALS['ssl']; }
function __( $text, $domain = 'default' ) { return $text; }
function status_header( $code ) { $GLOBALS['status'] = $code; }
function nocache_headers() {}

class Stub_Query {
	public function set_404() { $GLOBALS['404']++; }
}
$GLOBALS['wp_query'] = new Stub_Query();

$plugin = dirname( __DIR__ ) . '/skills/wordpress-security/assets/es-security.php';

if ( $predefined ) {
	/* Child: the constant is set to false BEFORE the plugin loads; the plugin must not override it. */
	define( 'DISALLOW_FILE_EDIT', false );
	require $plugin;
	echo var_export( DISALLOW_FILE_EDIT, true );
	exit( 0 );
}

$pass = 0; $fail = 0;
function ok( $cond, $label ) {
	global $pass, $fail;
	if ( $cond ) { $pass++; echo "  OK   $label\n"; }
	else { $fail++; echo "  FAIL $label\n"; }
}

/** Run every callback registered on $tag, threading the first argument through like apply_filters. */
function run( $tag, $value = null, ...$rest ) {
	foreach ( isset( $GLOBALS['hooks'][ $tag ] ) ? $GLOBALS['hooks'][ $tag ] : array() as $cb ) {
		$value = $cb( $value, ...$rest );
	}
	return $value;
}
function reset_state() {
	$GLOBALS['logged'] = false; $GLOBALS['admin'] = false; $GLOBALS['ssl'] = false; $GLOBALS['404'] = 0;
	unset( $_GET['author'] );
}

if ( ! file_exists( $plugin ) ) {
	echo "  FAIL plugin file is missing: skills/wordpress-security/assets/es-security.php\n";
	echo "0 OK / 1 FAIL\n";
	exit( 1 );
}
require $plugin;

echo "xmlrpc\n";
ok( false === run( 'xmlrpc_enabled', true ), 'xmlrpc_enabled returns false' );
$methods = run( 'xmlrpc_methods', array( 'pingback.ping' => 'x', 'pingback.extensions.getPingbacks' => 'x', 'wp.getPosts' => 'x' ) );
ok( ! isset( $methods['pingback.ping'] ) && ! isset( $methods['pingback.extensions.getPingbacks'] ), 'pingback methods are removed' );
ok( isset( $methods['wp.getPosts'] ), 'other methods are left alone' );

echo "REST users\n";
$routes = array(
	'/wp/v2/users'                => array( 'x' ),
	'/wp/v2/users/(?P<id>[\d]+)'  => array( 'x' ),
	'/wp/v2/users/me'             => array( 'x' ),
	'/wp/v2/posts'                => array( 'x' ),
);
$anon = run( 'rest_endpoints', $routes );
ok( ! isset( $anon['/wp/v2/users'] ) && ! isset( $anon['/wp/v2/users/(?P<id>[\d]+)'] ), 'anonymous: users list and single user are gone' );
ok( isset( $anon['/wp/v2/posts'] ) && isset( $anon['/wp/v2/users/me'] ), 'anonymous: unrelated routes stay' );
$GLOBALS['logged'] = true;
ok( $routes === run( 'rest_endpoints', $routes ), 'logged in (the connector): endpoints untouched' );
reset_state();

echo "?author=N\n";
$_GET['author'] = '1';
run( 'template_redirect' );
ok( 1 === $GLOBALS['404'], 'anonymous ?author=1 is answered as a 404, never an author redirect' );
reset_state();
$_GET['author'] = '1'; $GLOBALS['logged'] = true;
run( 'template_redirect' );
ok( 0 === $GLOBALS['404'], 'logged in: ?author=1 untouched' );
reset_state();
$_GET['author'] = '1'; $GLOBALS['admin'] = true;
run( 'template_redirect' );
ok( 0 === $GLOBALS['404'], 'admin screens: untouched' );
reset_state();
run( 'template_redirect' );
ok( 0 === $GLOBALS['404'], 'no author parameter: untouched' );
reset_state();

echo "sitemap and oEmbed\n";
ok( false === run( 'wp_sitemaps_add_provider', 'provider', 'users' ), 'sitemap users provider is dropped' );
ok( 'provider' === run( 'wp_sitemaps_add_provider', 'provider', 'posts' ), 'other sitemap providers stay' );
$embed = run( 'oembed_response_data', array( 'title' => 'T', 'author_name' => 'mcp-agent', 'author_url' => 'https://x.test/author/mcp-agent/' ) );
ok( ! isset( $embed['author_name'] ) && ! isset( $embed['author_url'] ), 'oEmbed drops author_name and author_url' );
ok( 'T' === $embed['title'], 'oEmbed keeps the rest' );

echo "login errors\n";
$e1 = run( 'login_errors', 'Unknown username.' );
$e2 = run( 'login_errors', 'The password you entered is incorrect.' );
ok( $e1 === $e2 && '' !== $e1, 'one generic message whatever core said' );
ok( false === stripos( $e1, 'username' ) && false === stripos( $e1, 'password' ), 'the message names neither field' );

echo "file editor\n";
ok( defined( 'DISALLOW_FILE_EDIT' ) && true === DISALLOW_FILE_EDIT, 'DISALLOW_FILE_EDIT is defined true' );
$child = trim( (string) shell_exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' --predefined 2>&1' ) );
ok( 'false' === $child, 'an existing DISALLOW_FILE_EDIT definition is respected, got: ' . $child );

echo "headers\n";
$plain = es_security_headers( false );
ok( 'nosniff' === $plain['X-Content-Type-Options'], 'X-Content-Type-Options: nosniff' );
ok( 'SAMEORIGIN' === $plain['X-Frame-Options'], 'X-Frame-Options: SAMEORIGIN' );
ok( 'strict-origin-when-cross-origin' === $plain['Referrer-Policy'], 'Referrer-Policy' );
ok( ! empty( $plain['Permissions-Policy'] ) && false !== strpos( $plain['Permissions-Policy'], 'camera=()' ), 'Permissions-Policy denies camera' );
ok( ! isset( $plain['Strict-Transport-Security'] ), 'no HSTS over plain HTTP' );
$tls = es_security_headers( true );
ok( isset( $tls['Strict-Transport-Security'] ), 'HSTS over HTTPS' );
$hsts = isset( $tls['Strict-Transport-Security'] ) ? $tls['Strict-Transport-Security'] : '';
ok( preg_match( '/^max-age=(\d+)$/', $hsts, $m ) && (int) $m[1] > 0 && (int) $m[1] <= 90 * 86400, 'HSTS max-age is short (at most 90 days), nothing else in the value' );
ok( false === stripos( $hsts, 'includeSubDomains' ) && false === stripos( $hsts, 'preload' ), 'HSTS has no includeSubDomains and no preload' );
ok( ! empty( $GLOBALS['hooks']['send_headers'] ), 'send_headers is hooked' );
$GLOBALS['ssl'] = true;
run( 'send_headers' );
ok( true, 'the send_headers callback runs without error' );
reset_state();

echo "Jetpack\n";
eval( 'class Jetpack {}' );
ok( true === run( 'xmlrpc_enabled', true ), 'with Jetpack active, xmlrpc is left on' );
$kept = run( 'xmlrpc_methods', array( 'pingback.ping' => 'x' ) );
ok( isset( $kept['pingback.ping'] ), 'with Jetpack active, methods are left alone' );

echo "\n$pass OK / $fail FAIL\n";
exit( $fail ? 1 : 0 );
