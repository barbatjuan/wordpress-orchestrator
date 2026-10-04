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
$GLOBALS['status'] = null;

function add_filter( $tag, $cb, $prio = 10, $args = 1 ) { $GLOBALS['hooks'][ $tag ][] = $cb; $GLOBALS['prio'][ $tag ] = $prio; return true; }
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

if ( isset( $argv ) && in_array( '--keep-xmlrpc', $argv, true ) ) {
	/* Child: the site opted out through wp-config.php; xmlrpc and its methods must stay untouched. */
	define( 'ES_SECURITY_KEEP_XMLRPC', true );
	require $plugin;
	$on = $GLOBALS['hooks']['xmlrpc_enabled'][0]( true );
	$m  = $GLOBALS['hooks']['xmlrpc_methods'][0]( array( 'pingback.ping' => 'x' ) );
	echo var_export( $on, true ) . ( isset( $m['pingback.ping'] ) ? ' kept' : ' removed' );
	exit( 0 );
}
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
	$GLOBALS['logged'] = false; $GLOBALS['admin'] = false; $GLOBALS['ssl'] = false; $GLOBALS['404'] = 0; $GLOBALS['status'] = null;
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
ok( 1 === $GLOBALS['404'] && 404 === $GLOBALS['status'], 'anonymous ?author=1 is answered as a 404, never an author redirect' );
ok( 0 === $GLOBALS['prio']['template_redirect'], 'template_redirect is registered at priority 0, ahead of the canonical redirect' );
reset_state();
foreach ( array( '1abc', '1,2', array( '1' ), array( '1', '2' ), '007' ) as $probe ) {
	$_GET['author'] = $probe;
	run( 'template_redirect' );
	ok( 404 === $GLOBALS['status'], 'anonymous author probe ' . json_encode( $probe ) . ' is a 404' );
	reset_state();
}
foreach ( array( 'abc', '' ) as $probe ) {
	$_GET['author'] = $probe;
	run( 'template_redirect' );
	ok( null === $GLOBALS['status'], 'author value ' . json_encode( $probe ) . ' has no digit and is left alone' );
	reset_state();
}
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
ok( false !== strpos( $plain['Permissions-Policy'], 'camera=()' ) && false !== strpos( $plain['Permissions-Policy'], 'microphone=()' ), 'Permissions-Policy denies camera and microphone' );
ok( false === strpos( $plain['Permissions-Policy'], 'geolocation' ), 'Permissions-Policy leaves geolocation alone (store locators)' );
$host = es_security_headers( false, array( 'x-frame-options', 'REFERRER-POLICY' ) );
ok( ! isset( $host['X-Frame-Options'] ) && ! isset( $host['Referrer-Policy'] ) && isset( $host['X-Content-Type-Options'] ), 'a header the host already sends is never overridden, whatever its case' );
$host_tls = es_security_headers( true, array( 'Strict-Transport-Security' ) );
ok( ! isset( $host_tls['Strict-Transport-Security'] ), 'a host HSTS is never replaced by ours' );
ok( ! isset( $plain['Strict-Transport-Security'] ), 'no HSTS over plain HTTP' );
$tls = es_security_headers( true );
ok( isset( $tls['Strict-Transport-Security'] ), 'HSTS over HTTPS' );
$hsts = isset( $tls['Strict-Transport-Security'] ) ? $tls['Strict-Transport-Security'] : '';
ok( preg_match( '/^max-age=(\d+)$/', $hsts, $m ) && (int) $m[1] > 0 && (int) $m[1] <= 90 * 86400, 'HSTS max-age is short (at most 90 days), nothing else in the value' );
ok( false === stripos( $hsts, 'includeSubDomains' ) && false === stripos( $hsts, 'preload' ), 'HSTS has no includeSubDomains and no preload' );
ok( ! empty( $GLOBALS['hooks']['send_headers'] ), 'send_headers is hooked' );
$sent = array();
$emit = function ( $line ) use ( &$sent ) { $sent[] = $line; };
es_security_send( false, array(), $emit );
ok( 4 === count( $sent ) && ! preg_grep( '/^Strict-Transport-Security:/', $sent ), 'emission over HTTP: four headers, no HSTS' );
$sent = array();
es_security_send( true, array( 'x-frame-options' ), $emit );
ok( 4 === count( $sent ) && 1 === count( preg_grep( '/^Strict-Transport-Security: max-age=\d+$/', $sent ) ) && ! preg_grep( '/^X-Frame-Options:/', $sent ), 'emission over HTTPS: HSTS present, host-sent header skipped' );
reset_state();

echo "xmlrpc opt-out\n";
$child = trim( (string) shell_exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' --keep-xmlrpc 2>&1' ) );
ok( 'true kept' === $child, 'ES_SECURITY_KEEP_XMLRPC leaves xmlrpc and pingback methods alone, got: ' . $child );

echo "Jetpack\n";
eval( 'class Jetpack {}' );
ok( true === run( 'xmlrpc_enabled', true ), 'with Jetpack active, xmlrpc is left on' );
$kept = run( 'xmlrpc_methods', array( 'pingback.ping' => 'x' ) );
ok( isset( $kept['pingback.ping'] ), 'with Jetpack active, methods are left alone' );

echo "\n$pass OK / $fail FAIL\n";
exit( $fail ? 1 : 0 );
