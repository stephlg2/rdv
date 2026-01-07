<?php
/**
 * Tripzzy Initialize all payments.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Helpers\Page;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\Amount;
use Tripzzy\Core\Localize;
use Tripzzy\Core\Payment\PaymentGateways;

if ( ! class_exists( 'Tripzzy\Core\Payment' ) ) {
	/**
	 * Tripzzy Payment Class.
	 *
	 * @since 1.0.0
	 */
	class Payment {
		use SingletonTrait;

		/**
		 * Constructor.
		 */
		public function __construct() {
			foreach ( glob( sprintf( '%1$sinc/Core/Payment/PaymentGateways/*.php', TRIPZZY_ABSPATH ) ) as $filename ) {
				$namespace  = 'Tripzzy\Core\Payment\PaymentGateways';
				$class_name = basename( $filename, '.php' );
				if ( class_exists( $namespace . '\\' . $class_name ) ) {
					$name = $namespace . '\\' . $class_name;
					new $name();
				}
			}

			// Save Payment Gateway settings data.
			add_filter( 'tripzzy_filter_before_save_settings', array( $this, 'update_settings' ) );

			// Save Payment data while booking.
			add_action( 'tripzzy_after_booking', array( $this, 'add_payment_data' ), 10, 2 );
		}

		/**
		 * Update payment gateway settings.
		 *
		 * @param array $settings Settings data.
		 * @return array
		 */
		public function update_settings( $settings ) {
			$default_gateway          = isset( $settings['default_gateway'] ) ? $settings['default_gateway'] : '';
			$enabled_payment_gateways = isset( $settings['enabled_payment_gateways'] ) ? $settings['enabled_payment_gateways'] : array();
			if ( is_array( $enabled_payment_gateways ) && count( $enabled_payment_gateways ) > 0 ) {
				if ( ! $default_gateway || ( $default_gateway && ! in_array( $default_gateway, $enabled_payment_gateways, true ) ) ) {
					$settings['default_gateway'] = $enabled_payment_gateways[0];
				}
			} else {
				$settings['default_gateway'] = '';
			}
			return $settings;
		}

		/**
		 * Add payment related data while making a booking.
		 *
		 * @param int   $booking_id Booking ID.
		 * @param array $data Booking related data.
		 *
		 * @return void
		 */
		public function add_payment_data( $booking_id, $data ) {
			$has_payment = (bool) ( $data['payment_details'] ?? false );

			if ( $has_payment ) {
				$total_payment = (float) MetaHelpers::get_post_meta( $booking_id, 'total_payment' ); // paid upto.

				// Add New Payment.
				$post_args  = array(
					'post_title'   => 'Payment',
					'post_content' => '',
					'post_status'  => 'publish',
					'post_slug'    => uniqid(),
					'post_type'    => 'tripzzy_payment',
				);
				$payment_id = wp_insert_post( $post_args, true );

				// Update Booking Data.
				$first_name = isset( $data['billing_first_name'] ) ? $data['billing_first_name'] : '';
				$last_name  = isset( $data['billing_last_name'] ) ? $data['billing_last_name'] : '';
				$fullname   = trim( sprintf( '%s %s', $first_name, $last_name ) );
				$post_args  = array(
					'ID'         => $payment_id,
					'post_title' => sprintf( '#%s %s', $payment_id, $fullname ),
				);
				wp_update_post( $post_args );

				// Update payment metas.
				$payment_amount = $data['payment_amount'] ?? 0;
				MetaHelpers::update_post_meta( $payment_id, 'payment_details', $data['payment_details'] ?? '' ); // Payment Details.
				MetaHelpers::update_post_meta( $payment_id, 'payment_mode', $data['payment_mode'] ?? '' );
				MetaHelpers::update_post_meta( $payment_id, 'payment_amount', $data['payment_amount'] ?? 0 );

				// Update booking metas.
				MetaHelpers::update_post_meta( $booking_id, 'booking_status', 'booked' );
				MetaHelpers::update_post_meta( $booking_id, 'payment_status', 'paid' );
				MetaHelpers::update_post_meta( $booking_id, 'currency', $data['currency'] ?? '' );

				// Update Payment ids in booking meta.
				$payment_ids = MetaHelpers::get_post_meta( $booking_id, 'payment_ids' );

				// update total payment.
				$total_payment += (float) $payment_amount;
				MetaHelpers::update_post_meta( $booking_id, 'total_payment', $total_payment );
				if ( ! $payment_ids ) {
					$payment_ids = array();
				}
				$payment_ids[] = $payment_id;
				MetaHelpers::update_post_meta( $booking_id, 'payment_ids', $payment_ids );
			}
		}

		/**
		 * It renders the payment details.
		 *
		 * @param int  $booking_id The Booking ID.
		 * @param bool $has_return Whether to return the contents or echo them.
		 *
		 * @return string payment details Markups.
		 */
		public static function render( $booking_id, $has_return = false ) {
			if ( ! $booking_id ) {
				return;
			}

			$payment_ids  = MetaHelpers::get_post_meta( $booking_id, 'payment_ids' );
			$settings     = Settings::get();
			$all_gateways = PaymentGateways::get_all();
			ob_start();

			if ( ! empty( $payment_ids ) ) {
				?>
				<table style="border-collapse:collapse; background:#fff;width:100%; margin:10px 0 20px">
					<thead>
						<tr>
							<th style="width:150px; font-family:Montserrat-Medium; font-size:12px; color:#333;background-color:#ccc;text-transform:uppercase; padding:10px; text-align:left; line-height:1.6"><?php esc_html_e( 'Payment Date', 'tripzzy' ); ?></th>
							<th style="width:350px; font-family:Montserrat-Medium; font-size:12px; color:#333;background-color:#ccc;text-transform:uppercase; padding:10px; text-align:left; line-height:1.6;"><?php esc_html_e( 'Payment Mode', 'tripzzy' ); ?></th>
							<th style="width:100px; font-family:Montserrat-Medium; font-size:12px; color:#333;background-color:#ccc;text-transform:uppercase; padding:10px; text-align:left; line-height:1.6;"><?php esc_html_e( 'Paid Amount', 'tripzzy' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $payment_ids as $payment_id ) {
							$currency = MetaHelpers::get_post_meta( $booking_id, 'currency' ); // Currency is in booking id.
							if ( ! $currency ) {
								$currency = $settings['currency']; // Fallback. just in case no currency data in booking due to error.
							}
							$payment_details = MetaHelpers::get_post_meta( $payment_id, 'payment_details' ); // Payment Details.
							$payment_mode    = MetaHelpers::get_post_meta( $payment_id, 'payment_mode' );
							$payment_amount  = MetaHelpers::get_post_meta( $payment_id, 'payment_amount' );
							?>
							<tr>
								<td><?php echo esc_html( get_the_time( 'Y-m-d H:i:s', $payment_id ) ); ?></td>
								<td><?php echo esc_html( $all_gateways[ $payment_mode ]['title'] ?? 'N/A' ); ?></td>
								<td><?php printf( '%s %s', esc_html( $currency ), esc_html( Amount::format( $payment_amount ) ) ); ?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
				<?php

			} else {
				esc_html_e( 'N/A', 'tripzzy' );
			}

			$contents = ob_get_contents();
			ob_end_clean();

			if ( $has_return ) {
				return $contents;
			}

			echo wp_kses_post( $contents );
		}

		/**
		 * Return the total paid amount for the booking.
		 *
		 * @param int $booking_id Booking Id.
		 * @return number
		 */
		public static function get_total( $booking_id ) {
			$total_payment = MetaHelpers::get_post_meta( $booking_id, 'total_payment' );
			if ( ! $total_payment ) {
				$total_payment = 0;
			}
			return $total_payment;
		}
	}
}
