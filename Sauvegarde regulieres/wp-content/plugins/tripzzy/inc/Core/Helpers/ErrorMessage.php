<?php
/**
 * Error Message class.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\ErrorMessage' ) ) {

	/**
	 * Main Error Message class to deal with error object.
	 *
	 * @since 1.0.0
	 */
	class ErrorMessage {
		/**
		 * Get Error Message.
		 *
		 * @param string $key  Error key.
		 * @param array  $args Additional param for adding dynamic value in the string.
		 * @param string $context Context of message.
		 * @since 1.0.0
		 * @since 1.2.1 Context added for singular and plural message.
		 * @return object Error object.
		 */
		public static function get( $key = '', $args = array(), $context = 'singular' ) {
			$messages = self::error_messages();
			if ( 'singular' === $context ) {
				$key           = ! empty( $key ) && isset( $messages[ $key ] ) ? $key : 'error';
				$error_message = $messages[ $key ];
				if ( is_array( $args ) && count( $args ) > 0 ) {
					$error_message = sprintf( $error_message, ...$args );
				}
			} elseif ( 'plural' === $context ) {
				if ( is_array( $args ) && count( $args ) > 0 ) {
					$count = (int) $args[0];

					$plural_messages = self::plural_error_messages( $count );
					$key             = ! empty( $key ) && isset( $plural_messages[ $key ] ) ? $key : 'error';
					$error_message   = $plural_messages[ $key ] ?? $messages['error'];
				}
			}
			return new \WP_Error( $key, $error_message );
		}


		/**
		 * Message strings. can directly used in ajax response etc.
		 *
		 * @since 1.0.0
		 * @since 1.0.9 Added negative_cart_value key.
		 * @since 1.1.3 Added invalid_key and invalid_booking_id keys.
		 * @since 1.1.9 Added no_changes, empty_password, incorrect_password, same_password, password_mismatch keys.
		 */
		public static function error_messages() {
			$strings = array(
				'error'                      => __( 'An Error has occur!!', 'tripzzy' ), // default.
				'nonce_verification_failed'  => __( 'Page session expired. Please reload the page!!', 'tripzzy' ),
				'invalid_cart_request'       => __( 'Please select atleast one category!!', 'tripzzy' ),
				'negative_cart_value'        => __( 'Please add +ve value for Qty!!', 'tripzzy' ),
				// translators: 1: No of people.
				'min_cart_value_required'    => __( 'You must select atleast %1$d %2$s!!', 'tripzzy' ),
				'unable_to_add_cart_item'    => __( 'Unable to add trip in the cart!!', 'tripzzy' ),
				'page_expired'               => __( 'This link has been expired.', 'tripzzy' ),
				'coupon_required'            => __( 'Please enter your coupon !', 'tripzzy' ),
				'invalid_coupon'             => __( 'Invalid coupon !', 'tripzzy' ),
				'expired_coupon'             => __( 'Coupon already expired !', 'tripzzy' ),
				'coupon_limit_exceed'        => __( 'Coupon limit exceed !', 'tripzzy' ),
				'unauthorized_coupon'        => __( 'You are not allowed to use this coupon !', 'tripzzy' ),
				'unauthorized_coupon_trips'  => __( 'You are not allowed to use this coupon in this trips !', 'tripzzy' ),
				'coupon_amount_limit_exceed' => __( 'Coupon amount is more than trip amount !', 'tripzzy' ),
				'full_name_required'         => __( 'Full name is required !', 'tripzzy' ),
				'email_required'             => __( 'Email is required !', 'tripzzy' ),
				'message_required'           => __( 'Message is required !', 'tripzzy' ),
				'invalid_key'                => __( 'Invalid Key.', 'tripzzy' ),
				'invalid_booking_id'         => __( 'Invalid Booking ID.', 'tripzzy' ),
				// translators: 1: Changes made to.
				'no_changes'                 => __( 'No changes were made %s', 'tripzzy' ),
				// translators: 1: Password type like new password, confirm password.
				'empty_password'             => __( '%s password is empty.', 'tripzzy' ),
				// translators: 1: Password type like new password, confirm password.
				'incorrect_password'         => __( '%s password is incorrect.', 'tripzzy' ),
				'same_password'              => __( 'You can not set your current password as a new password.', 'tripzzy' ),
				'password_mismatch'          => __( "New password doesn't match with confirm password.", 'tripzzy' ),
			);
			return $strings;
		}

		/**
		 * Message strings. can directly used in ajax response etc.
		 *
		 * @param int $count Number.
		 * @since 1.2.1
		 */
		public static function plural_error_messages( $count = 0 ) {
			$strings = array(
				// translators: 1: No of people.
				'min_cart_value_required' => sprintf( _n( 'You must select atleast %d person!!', 'You must select atleast %d people!!', $count, 'tripzzy' ), number_format_i18n( $count ) ),
			);

			return $strings;
		}
	}
}
