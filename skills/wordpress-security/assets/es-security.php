<?php
/**
 * Plugin Name: ES Security
 * Description: Closes the findings external scanners repeat: xmlrpc, user enumeration, missing security headers.
 *
 * Drop into wp-content/mu-plugins/. No settings, no options. Delete the file to undo it.
 * Every check that depends on a plugin (Jetpack) runs when the hook fires, not at load, because
 * mu-plugins load before regular plugins and a load-time class_exists() would always be false.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* The one place that decides which headers go out, as a pure function so it can be asserted on.
   HSTS stays at 30 days with no subdomains and no preload: both are hard to undo. */
function es_security_headers( $ssl ) {
	$headers = array(
		'X-Content-Type-Options' => 'nosniff',
		'X-Frame-Options'        => 'SAMEORIGIN',
		'Referrer-Policy'        => 'strict-origin-when-cross-origin',
		'Permissions-Policy'     => 'camera=(), microphone=(), geolocation=()',
	);
	if ( $ssl ) {
		$headers['Strict-Transport-Security'] = 'max-age=' . ( 30 * 86400 );
	}
	return $headers;
}
add_action( 'send_headers', function () {
	if ( headers_sent() ) {
		return;
	}
	foreach ( es_security_headers( is_ssl() ) as $name => $value ) {
		header( $name . ': ' . $value );
	}
} );

/* xmlrpc off, unless Jetpack needs it. */
add_filter( 'xmlrpc_enabled', function ( $enabled ) {
	return class_exists( 'Jetpack' ) ? $enabled : false;
} );
add_filter( 'xmlrpc_methods', function ( $methods ) {
	if ( ! class_exists( 'Jetpack' ) ) {
		unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
	}
	return $methods;
} );

/* Users stay out of the public REST API. Authenticated requests, the connector's included, are untouched. */
add_filter( 'rest_endpoints', function ( $routes ) {
	if ( ! is_user_logged_in() ) {
		unset( $routes['/wp/v2/users'], $routes['/wp/v2/users/(?P<id>[\d]+)'] );
	}
	return $routes;
} );

/* ?author=N would redirect to /author/<login>/ and hand out the login. Answer 404 instead. */
add_action( 'template_redirect', function () {
	if ( is_admin() || is_user_logged_in() || ! isset( $_GET['author'] ) || ! is_numeric( $_GET['author'] ) ) {
		return;
	}
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}, 0 );

/* No users sitemap, no author in oEmbed, one login error. */
add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}, 10, 2 );
add_filter( 'oembed_response_data', function ( $data ) {
	unset( $data['author_name'], $data['author_url'] );
	return $data;
} );
add_filter( 'login_errors', function () {
	return __( 'Login failed. Check your details and try again.', 'es-security' );
} );

/* Theme and plugin file editors off, unless the site already decided. */
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}
