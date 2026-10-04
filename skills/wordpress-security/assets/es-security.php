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
   HSTS stays at 30 days with no subdomains and no preload: both are hard to undo. A header the
   host already sends (names in $sent, any case) is never replaced: the host knows more than we do. */
function es_security_headers( $ssl, $sent = array() ) {
	$headers = array(
		'X-Content-Type-Options' => 'nosniff',
		'X-Frame-Options'        => 'SAMEORIGIN',
		'Referrer-Policy'        => 'strict-origin-when-cross-origin',
		'Permissions-Policy'     => 'camera=(), microphone=()',
	);
	if ( $ssl ) {
		$headers['Strict-Transport-Security'] = 'max-age=' . ( 30 * 86400 );
	}
	$have = array_map( 'strtolower', $sent );
	foreach ( array_keys( $headers ) as $name ) {
		if ( in_array( strtolower( $name ), $have, true ) ) {
			unset( $headers[ $name ] );
		}
	}
	return $headers;
}
/* $emit is header() in production and a recorder in the test. */
function es_security_send( $ssl, $sent, $emit ) {
	foreach ( es_security_headers( $ssl, $sent ) as $name => $value ) {
		$emit( $name . ': ' . $value );
	}
}
add_action( 'send_headers', function () {
	if ( ! headers_sent() ) {
		es_security_send( is_ssl(), array_map( function ( $line ) {
			return trim( strstr( $line, ':', true ) );
		}, headers_list() ), 'header' );
	}
} );

/* xmlrpc off, unless Jetpack needs it or wp-config.php says define( 'ES_SECURITY_KEEP_XMLRPC', true )
   (the WordPress mobile app, for one). */
function es_security_keeps_xmlrpc() {
	return class_exists( 'Jetpack' ) || ( defined( 'ES_SECURITY_KEEP_XMLRPC' ) && ES_SECURITY_KEEP_XMLRPC );
}
add_filter( 'xmlrpc_enabled', function ( $enabled ) {
	return es_security_keeps_xmlrpc() ? $enabled : false;
} );
add_filter( 'xmlrpc_methods', function ( $methods ) {
	if ( ! es_security_keeps_xmlrpc() ) {
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

/* ?author=N would redirect to /author/<login>/ and hand out the login. Answer 404 instead. Core
   reads the leading digits, so any value holding a digit, and any array, counts as a probe. */
add_action( 'template_redirect', function () {
	if ( is_admin() || is_user_logged_in() || ! isset( $_GET['author'] ) || ! ( is_array( $_GET['author'] ) || preg_match( '/\d/', $_GET['author'] ) ) ) {
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
