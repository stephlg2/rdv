<?php
/**
 * Helper function for all error notices.
 *
 * @package tripzzy
 * @since   1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Tripzzy List of all Notice.
 *
 * @since 1.0.0
 * @return array
 */
function tripzzy_get_all_notice() {

	$notices = array(
		/* translators: 1: Min Php Version, 2: Min Php Version. */
		'minimum_php_version'    => sprintf( __( '\'Tripzzy\' requires minimum PHP version %1$s+. Please update PHP version to %2$s+.', 'tripzzy' ), TRIPZZY_MIN_PHP_VERSION, TRIPZZY_MIN_PHP_VERSION ),
		/* translators: 1: Min WP Version, 2: Min WP Version. */
		'minimum_wp_version'     => sprintf( __( '\'Tripzzy\' requires minimum WordPress version %1$s+. Please update WordPress version to %2$s+.', 'tripzzy' ), TRIPZZY_MIN_WP_VERSION, TRIPZZY_MIN_WP_VERSION ),
		'use_forcefully'         => __( 'We do not recommend you to use forcefully. If you still want to use \'Tripzzy\' forcefully. Please <a href="#" class="tripzzy-use-forcefully" data-use="yes" >click here</a>.', 'tripzzy' ),
		'not_use_forcefully'     => __( 'You are using uncompatible version of \'Tripzzy\'. Please <a href="#" class="tripzzy-use-forcefully" data-use="no" >Click here</a> to undo the current action.', 'tripzzy' ),
		'still_using_forcefully' => __( 'You are still in uncompatible mode for \'Tripzzy\'. Please <a href="#" class="tripzzy-use-forcefully" data-use="no" >Click here</a> to update compatibility mode.', 'tripzzy' ),
	);
	return $notices;
}

/**
 * Tripzzy Print Notices.
 *
 * @param string $message_type Type of message.
 * @param string $notice_type  Type of notice [ notice-error | notice-warning | notice-success ].
 * @since 1.0.0
 * @return Mixed
 */
function tripzzy_print_notice( $message_type = '', $notice_type = 'notice-error' ) {
	if ( ! $message_type ) {
		return;
	}
	$notices = tripzzy_get_all_notice();
	if ( isset( $notices[ $message_type ] ) ) {
		$html_message = sprintf( '<div class="notice %s">%s</div>', $notice_type, wpautop( $notices[ $message_type ] ) );
		echo wp_kses_post( $html_message );
	}
}
