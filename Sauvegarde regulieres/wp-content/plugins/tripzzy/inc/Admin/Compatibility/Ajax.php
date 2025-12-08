<?php
/**
 * Ajax Requests for Compatibility.
 *
 * @package tripzzy
 * @since   1.0.0
 */

use Tripzzy\Core\Helpers\MetaHelpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_tripzzy_use_forcefully', 'tripzzy_ajax_use_forcefully' );
add_action( 'wp_ajax_nopriv_tripzzy_use_forcefully', 'tripzzy_ajax_use_forcefully' );

/**
 * Ajax Callback to update compatibility.
 *
 * @since 1.0.0
 */
function tripzzy_ajax_use_forcefully() {
	check_ajax_referer( 'tripzzy_nonce_action', 'tripzzy_nonce' );

	if ( isset( $_POST['value'] ) && 'yes' === $_POST['value'] ) {
		MetaHelpers::update_option( 'use_forcefully', true );
	} else {
		MetaHelpers::delete_option( 'use_forcefully' );
	}
	wp_send_json_success( true );
}
