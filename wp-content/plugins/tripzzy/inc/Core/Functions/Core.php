<?php
/**
 * Overridable pluggable functions for Tripzzy.
 *
 * @package tripzzy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
if ( ! function_exists( 'tripzzy_version' ) ) {
	/**
	 * Return the current version of tripzzy.
	 *
	 * @return string
	 */
	function tripzzy_version() {
		return TRIPZZY_VERSION;
	}
}

if ( ! function_exists( 'tripzzy_domain_name' ) ) {
	/**
	 * Return the domain name.
	 *
	 * @return string
	 */
	function tripzzy_domain_name() {
		$site_url    = get_site_url();
		$domain_name = wp_parse_url( $site_url, PHP_URL_HOST );
		return $domain_name;
	}
}
