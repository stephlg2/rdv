<?php
/**
 * Tripzzy Send Emails.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Emails\AdminBookingEmail;
use Tripzzy\Core\Emails\AdminEnquiryEmail;
use Tripzzy\Core\Emails\CustomerBookingEmail;
use Tripzzy\Core\Emails\CustomerBookingCancelationEmail;
use Tripzzy\Core\Emails\CustomerBookingOnHoldEmail;
use Tripzzy\Core\Emails\CustomerBookingRefundedEmail;
use Tripzzy\Core\Bookings;


if ( ! class_exists( 'Tripzzy\Core\SendEmails' ) ) {
	/**
	 * SendEmails main class.
	 */
	class SendEmails {

		/**
		 * Settings.
		 *
		 * @var array
		 */
		protected static $settings;

		/**
		 * Initialize SendEmails.
		 *
		 * @return void
		 */
		public static function init() {
			self::$settings = Settings::get();
			add_action( 'tripzzy_after_booking', array( __CLASS__, 'send_booking_emails' ), 30, 2 );
			add_action( 'tripzzy_after_enquiry', array( __CLASS__, 'send_enquiry_emails' ) );

			add_action( 'tripzzy_booking_status_changed', array( __CLASS__, 'send_update_booking_notification' ), 10, 4 );
		}

		/**
		 * Send booking-related emails while making a booking like booking emails to admin, customer, etc.
		 *
		 * @param int   $booking_id Booking ID.
		 * @param array $data Booking related data.
		 *
		 * @return void
		 */
		public static function send_booking_emails( $booking_id, $data ) {
			$has_payment                   = (bool) ( $data['payment_details'] ?? false );
			$disable_admin_notification    = (bool) ( self::$settings['disable_admin_notification'] ?? false );
			$disable_customer_notification = (bool) ( self::$settings['disable_customer_notification'] ?? false );

			if ( ! $disable_admin_notification ) {
				$email     = new AdminBookingEmail( $booking_id );
				$mail_sent = $email->send();
			}

			if ( ! $disable_customer_notification ) {
				$email     = new CustomerBookingEmail( $booking_id );
				$mail_sent = $email->send();
			}
		}

		/**
		 * Send enquiry emails.
		 *
		 * @param int $enquiry_id Enquiry ID.
		 *
		 * @return void
		 */
		public static function send_enquiry_emails( $enquiry_id ) {
			$disable_admin_notification   = (bool) self::$settings['disable_admin_notification'] ?? false;
			$disable_enquiry_notification = (bool) self::$settings['disable_enquiry_notification'] ?? false;

			if ( $disable_admin_notification || $disable_enquiry_notification ) {
				return;
			}
			$email     = new AdminEnquiryEmail( $enquiry_id );
			$mail_sent = $email->send();
		}

		/**
		 * Send Email on booking status update.
		 *
		 * @param int    $booking_id Booking Id.
		 * @param bool   $send_notification Whether send notification or not.
		 * @param string $new_booking_status Changed booking status.
		 * @param string $old_booking_status Previous booking status.
		 *
		 * @since 1.1.0
		 * @since 1.1.8 Added notes for booking status change.
		 * @return void
		 */
		public static function send_update_booking_notification( $booking_id, $send_notification, $new_booking_status, $old_booking_status ) {

			if ( $send_notification ) {
				$disable_customer_notification = (bool) self::$settings['disable_customer_notification'] ?? false;

				switch ( $new_booking_status ) {
					case 'canceled':
						if ( ! $disable_customer_notification ) {
							$email = new CustomerBookingCancelationEmail( $booking_id );
							$email->send();
						}
						break;
					case 'on_hold':
						if ( ! $disable_customer_notification ) {
							$email = new CustomerBookingOnHoldEmail( $booking_id );
							$email->send();
						}
						break;
					case 'refunded':
						if ( ! $disable_customer_notification ) {
							$email = new CustomerBookingRefundedEmail( $booking_id );
							$email->send();
						}
						break;
					case 'booked':
						if ( ! $disable_customer_notification ) {
							$email = new CustomerBookingEmail( $booking_id );
							$email->send();
						}
						break;

				}
			}

			// Update Note @since 1.1.8.
			$booking_status_options = Bookings::get_booking_status_options();

			$old_label = $booking_status_options[ $old_booking_status ] ?? $old_booking_status;
			$new_label = $booking_status_options[ $new_booking_status ] ?? $new_booking_status;
			/* translators: 1: Old Booking Status, 2: New Booking status. */
			$note = sprintf( __( 'Booking status changed from "%1$s" to "%2$s".', 'tripzzy' ), $old_label, $new_label );
			Bookings::add_note( $booking_id, $note );
		}
	}
}
