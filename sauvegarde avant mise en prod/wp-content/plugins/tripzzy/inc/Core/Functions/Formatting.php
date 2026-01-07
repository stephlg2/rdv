<?php
/**
 * Formatting functions for Tripzzy.
 *
 * @since 1.1.8
 * @package tripzzy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Tripzzy Date Format - Allows to change date format.
 *
 * @since 1.1.8
 * @return string
 */
function tripzzy_date_format() {
	$date_format = get_option( 'date_format' );
	if ( empty( $date_format ) ) {
		// Return default date format if the option is empty.
		$date_format = 'F j, Y';
	}
	return apply_filters( 'tripzzy_filter_date_format', $date_format );
}

/**
 * Tripzzy Time Format - Allows to change time format.
 *
 * @since 1.1.8
 * @return string
 */
function tripzzy_time_format() {
	$time_format = get_option( 'time_format' );
	if ( empty( $time_format ) ) {
		// Return default time format if the option is empty.
		$time_format = 'g:i a';
	}
	return apply_filters( 'tripzzy_filter_time_format', $time_format );
}
