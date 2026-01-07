<?php
/**
 * Check WP Version, PHP Version Compatibility.
 *
 * @package tripzzy
 * @since   1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Http\Request;

/**
 * Scripts for compatibility actions.
 */
function tripzzy_compatibility_scripts() {
	wp_register_script( 'tripzzy-compatibility', TRIPZZY_PLUGIN_DIR_URL . 'assets/js/compatibility.js', array( 'jquery' ), TRIPZZY_VERSION, true );

	$tripzzy_compatibility = array(
		'ajax_url'      => admin_url( 'admin-ajax.php' ),
		'tripzzy_nonce' => Nonce::create(),
	);
	wp_localize_script( 'tripzzy-compatibility', 'tripzzy_compatibility', $tripzzy_compatibility );
	wp_enqueue_script( 'tripzzy-compatibility' );
}


/**
 * Tripzzy check Plugin compatibility with WP Version along with PHP Version.
 */
function tripzzy_compatibility() {
	if ( ! version_compare( get_bloginfo( 'version' ), TRIPZZY_MIN_WP_VERSION, '>=' ) ) {
		add_action( 'admin_notices', 'tripzzy_notice_min_wp_version' );
		return false;
	} elseif ( ! version_compare( PHP_VERSION, TRIPZZY_MIN_PHP_VERSION, '>=' ) ) {
		add_action( 'admin_notices', 'tripzzy_notice_min_php_version' );
		return false;
	}
	$use_forcefully = MetaHelpers::get_option( 'use_forcefully' );
	// Update status notice if still using uncompatible mode after updating to latest compatible version.
	if ( $use_forcefully ) {
		add_action( 'admin_enqueue_scripts', 'tripzzy_compatibility_scripts' );
		add_action( 'admin_notices', 'tripzzy_notice_still_using_forcefully' );
	}

	return true;
}

/**
 * Function which allow/disallow to use plugin even plugin is not compatible.
 *
 * @return bool
 */
function tripzzy_use_forcefully() {
	$use_forcefully = MetaHelpers::get_option( 'use_forcefully' );
	// Plugin works if we set it true, but we do not recommend this approach.
	$use_forcefully = apply_filters( 'tripzzy_filter_use_forcefully', $use_forcefully );

	if ( $use_forcefully ) {
		add_action( 'admin_notices', 'tripzzy_notice_not_use_forcefully' );

	} else {
		add_action( 'admin_notices', 'tripzzy_notice_use_forcefully' );
	}
	return $use_forcefully;
}

// All Notice Callbacks.

/**
 * Compatibility Notice: Min WP Version.
 */
function tripzzy_notice_min_wp_version() {
	return tripzzy_print_notice( 'minimum_wp_version' );
}

/**
 * Compatibility Notice: Min PHP Version.
 */
function tripzzy_notice_min_php_version() {
	return tripzzy_print_notice( 'minimum_php_version' );
}

/**
 * Compatibility Notice: Use Forcefully.
 */
function tripzzy_notice_use_forcefully() {
	return tripzzy_print_notice( 'use_forcefully' );
}

/**
 * Compatibility Notice: Not Use Forcefully.
 */
function tripzzy_notice_not_use_forcefully() {
	return tripzzy_print_notice( 'not_use_forcefully' );
}

/**
 * Compatibility Notice: Still using uncompatible mode though updatated to compatible version.
 */
function tripzzy_notice_still_using_forcefully() {
	return tripzzy_print_notice( 'still_using_forcefully', 'notice-warning' );
}
