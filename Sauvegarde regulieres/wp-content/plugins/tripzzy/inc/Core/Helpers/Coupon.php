<?php
/**
 * Coupons.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Helpers\Currencies;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\ErrorMessage;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\Coupon' ) ) {

	/**
	 * Our main helper class that provides.
	 *
	 * @since 1.0.0
	 */
	class Coupon {

		/**
		 * Coupon
		 *
		 * @var object
		 */
		protected static $coupon;

		/**
		 * Coupon ID
		 *
		 * @var int
		 */
		protected $coupon_id;

		/**
		 * Post status
		 *
		 * @var string
		 */
		protected $post_status;

		/**
		 * Coupon Data.
		 *
		 * @var $coupon_data.
		 */
		public static $coupon_data;

		/**
		 * Coupon Init.
		 *
		 * @param mixed $coupon either coupon id or coupon object.
		 */
		public function __construct( $coupon = null ) {
			if ( is_object( $coupon ) ) {
				self::$coupon = $coupon;
			} elseif ( is_numeric( $coupon ) ) {
				self::$coupon = get_post( $coupon );
			} else {
				self::$coupon = get_post( get_the_ID() );
			}
			$this->coupon_id   = self::$coupon->ID;
			$this->post_status = self::$coupon->post_status;

			self::set( $this->coupon_id );
		}
		/**
		 * Get All Coupon data.
		 *
		 * @param int $coupon_id Coupon ID.
		 * @since 1.0.0
		 */
		public static function get( $coupon_id = null ) {
			if ( ! $coupon_id ) {
				if ( empty( self::$coupon_data ) ) {
					return array();
				}
			} else {
				self::set( $coupon_id );
			}
			return self::$coupon_data;
		}

		/**
		 * Gets Coupon Id.
		 */
		public function get_id() {
			return $this->coupon_id;
		}

		/**
		 * Gets Coupon Id.
		 */
		public function get_post_status() {
			return $this->post_status;
		}


		/**
		 * Get Coupon Value.
		 *
		 * @param int $coupon_id Coupon Id.
		 * @return string
		 */
		public static function get_coupon_value( $coupon_id = null ) {
			$data = self::get( $coupon_id );
			return isset( $data['coupon_value'] ) ? $data['coupon_value'] : '';
		}

		/**
		 * Get Coupon Type.
		 *
		 * @param int $coupon_id Coupon Id.
		 * @return string
		 */
		public static function get_coupon_type( $coupon_id = null ) {
			$data = self::get( $coupon_id );
			return isset( $data['coupon_type'] ) ? $data['coupon_type'] : '';
		}

		/**
		 * Get Coupon Expiry.
		 *
		 * @param int $coupon_id Coupon Id.
		 * @return string
		 */
		public static function get_coupon_expiry( $coupon_id = null ) {
			$data   = self::get( $coupon_id );
			$expiry = isset( $data['coupon_expiry'] ) ? $data['coupon_expiry'] : '';
			if ( 'invalid date' === strtolower( $expiry ) ) {
				$expiry = null;
			}
			return $expiry;
		}

		/**
		 * Get Coupon Status.
		 *
		 * @param int $coupon_id Coupon Id.
		 * @return string
		 */
		public static function get_coupon_status( $coupon_id = null ) {
			$data   = self::get( $coupon_id );
			$coupon = new Coupon( $coupon_id );

			$post_status = $coupon->get_post_status();

			if ( 'publish' !== $post_status ) {
				return $post_status;
			}
			if ( self::is_limit_exceed( $coupon_id ) ) {
				return 'limit exceed';
			}
			if ( self::is_expired( $coupon_id ) ) {
				return 'expired';
			}
			return 'active';
		}

		/**
		 * Check coupon uses.
		 *
		 * @param int $coupon_id Coupon Id.
		 * @return int
		 */
		public static function get_coupon_uses( $coupon_id = null ) {
			$data = self::get( $coupon_id );
			$uses = isset( $data['coupon_uses'] ) && $data['coupon_uses'] ? $data['coupon_uses'] : 0;
			return absint( $uses );
		}

		/**
		 * Check coupon limit.
		 *
		 * @param int $coupon_id Coupon Id.
		 * @return int
		 */
		public static function get_coupon_limit( $coupon_id = null ) {
			$data = self::get( $coupon_id );
			return isset( $data['coupon_limit'] ) && $data['coupon_limit'] ? $data['coupon_limit'] : '';
		}



		/**
		 * Set All Coupon data.
		 *
		 * @param int $coupon_id Coupon ID.
		 * @since 1.0.0
		 */
		public static function set( $coupon_id ) {
			if ( ! $coupon_id ) {
				return array();
			}
			$default     = self::default_data(); // Default Values.
			$coupon_data = MetaHelpers::get_post_meta( $coupon_id, 'coupon' ); // Saved Values.

			$coupon_data = $coupon_data ? $coupon_data : array();
			$coupon_data = wp_parse_args( $coupon_data, $default );

			// Individual metadata.
			$coupon_code = MetaHelpers::get_post_meta( $coupon_id, 'coupon_code' );
			$coupon_uses = MetaHelpers::get_post_meta( $coupon_id, 'coupon_uses' );

			$coupon_data['coupon_id']   = $coupon_id;
			$coupon_data['coupon_code'] = $coupon_code;
			$coupon_data['coupon_uses'] = $coupon_uses;
			self::$coupon_data          = $coupon_data;
			return $coupon_data;
		}

		/**
		 * Update All Coupon data.
		 *
		 * @param int    $coupon_id Coupon id to save.
		 * @param object $raw_data  Request Payload data.
		 * @since 1.0.0
		 */
		public static function update( $coupon_id, $raw_data = array() ) {
			if ( ! $raw_data ) {
				// This Raw data is sanitized later in the loop below.
				$raw_data = Request::get_payload( true );
			}

			if ( empty( $raw_data ) ) {
				return;
			}

			$raw_data    = (array) $raw_data;
			$coupon_data = self::get( $coupon_id ); // Coupon Data.
			$default     = self::default_data(); // Default Coupon Data.
			$coupon_code = '';
			unset( $coupon_data['is_data_changed'], $coupon_data['is_requesting'] ); // Unset if accidently saved.
			foreach ( $default as $key => $value ) {
				if ( isset( $raw_data[ $key ] ) ) {
					if ( 'coupon_code' === $key ) {
						$coupon_code = Request::sanitize_data( $raw_data[ $key ] );
						MetaHelpers::update_post_meta( $coupon_id, 'coupon_code', $coupon_code );
						continue;
					}
					$coupon_data[ $key ] = Request::sanitize_data( $raw_data[ $key ] );
				}
			}

			/**
			 * Filter coupon data before save.
			 *
			 * @since 1.0.0
			 */
			$coupon_data = apply_filters( 'tripzzy_filter_before_save_coupon', $coupon_data, $raw_data );
			MetaHelpers::update_post_meta( $coupon_id, 'coupon', $coupon_data );
			/**
			 * Filter coupon data after save.
			 *
			 * @since 1.0.0
			 */
			$coupon_data                = apply_filters( 'tripzzy_filter_after_save_coupon', $coupon_data, $raw_data );
			$coupon_data['coupon_code'] = $coupon_code;

			// Update Post status to publish.
			$coupon                = get_post( $coupon_id, 'ARRAY_A' );
			$coupon['post_status'] = 'publish';
			$coupon['post_title']  = $coupon_code;
			wp_update_post( $coupon );
			return $coupon_data;
		}

		/**
		 * Get All Default Coupon Value.
		 *
		 * @since 1.0.0
		 */
		public static function default_data() {
			$coupon_data = array(
				'coupon_code'   => '',
				'coupon_limit'  => '',
				'coupon_expiry' => '',
				'coupon_type'   => 'percentage',
				'coupon_value'  => '10',
				'coupon_trips'  => array(),
				'coupon_users'  => array(),
			);
			return apply_filters( 'tripzzy_filter_default_coupon_data', $coupon_data );
		}

		/**
		 * Get Coupon ID by Coupon Code.
		 *
		 * @param string $coupon_code Coupon code to check.
		 * @since 1.0.0
		 */
		public static function get_coupon_id_by_code( $coupon_code ) {
			if ( ! $coupon_code ) {
				return false;
			}
			global $wpdb;

			$meta_key = 'tripzzy_coupon_code';

			$sql = $wpdb->prepare(
				"
				SELECT post_id
				FROM $wpdb->postmeta
				WHERE meta_key = %s
				AND meta_value = %s
			",
				$meta_key,
				esc_sql( $coupon_code )
			);

			$results = $wpdb->get_results( $sql ); // @phpcs:ignore

			if ( empty( $results ) ) {
				return false;
			}

			return $results['0']->post_id;
		}

		/**
		 * Check Whether coupon is expired or not.
		 *
		 * @param int $coupon_id Coupon Id.
		 *
		 * @since 1.0.0
		 * @since 1.2.3 Fixed warning if expiry date removed after adding.
		 * @return boolean
		 */
		public static function is_expired( $coupon_id = null ) {

			$data = self::get( $coupon_id );
			if ( empty( $data ) ) {
				return;
			}
			$coupon_expiry = $data['coupon_expiry'];
			if ( ! empty( $coupon_expiry ) ) {
				if ( 'invalid date' === strtolower( $coupon_expiry ) ) {
					$coupon_expiry = null;
				}
				if ( ! $coupon_expiry ) {
					return false;
				}
				$now_date    = new \DateTime();
				$coupon_date = new \DateTime( $coupon_expiry );

				// Check Expiry Date.
				$now_date    = $now_date->format( 'Y-m-d' );
				$coupon_date = $coupon_date->format( 'Y-m-d' );

				if ( strtotime( $now_date ) > strtotime( $coupon_date ) ) {
					return true;
				}
			}
		}



		/**
		 * Check Whether coupon limit is exceed or not.
		 *
		 * @param int $coupon_id Coupon Id.
		 * @return boolean
		 */
		public static function is_limit_exceed( $coupon_id = null ) {
			$data = self::get( $coupon_id );
			if ( empty( $data ) ) {
				return;
			}
			$coupon_limit = $data['coupon_limit'];
			if ( ! empty( $coupon_limit ) ) {
				$coupon_uses = self::get_coupon_uses( $coupon_id );
				if ( ! ( absint( $coupon_limit ) > $coupon_uses ) ) {
					return true;
				}
			}
		}

		/**
		 * Check for user specific coupon.
		 *
		 * @param int $coupon_id Coupon Id.
		 * @return boolean
		 */
		public static function is_valid_user( $coupon_id = null ) {
			$data = self::get( $coupon_id );

			if ( empty( $data ) ) {
				return;
			}

			$coupon_users = $data['coupon_users'];
			if ( ! empty( $coupon_users ) ) {

				if ( ! is_user_logged_in() ) {
					return false;
				}
				$current_user    = wp_get_current_user();
				$current_user_id = $current_user->data->ID;
				$coupon_users    = array_map( 'intval', $coupon_users );
				if ( in_array( absint( $current_user_id ), $coupon_users, true ) ) {
					return true;
				}
				return false;
			}
			return true;
		}



		/**
		 * Check coupon is valid for trips in the cart items.
		 *
		 * @param int $coupon_id Coupon Id.
		 * @return boolean
		 */
		public static function validate_and_apply( $coupon_id ) {
			$default_values = self::default_data();
			$data           = self::get( $coupon_id );

			$coupon_trips = $data['coupon_trips'];
			$coupon_type  = $data['coupon_type'];
			$coupon_value = $data['coupon_value'] ?? $default_values['coupon_value'];
			$response     = array(
				'success' => true,
				'code'    => 'valid',
			);

			$coupon_trip_total = 0;
			$coupon_discount   = 0;

			// Check Trip Specific coupon.
			$cart          = tripzzy()->cart;
			$cart_contents = $cart->get_cart_contents();
			$cart_totals   = $cart->get_totals();

			$coupon_cart_contents = array(); // coupon total applied for each trip.
			if ( ! empty( $coupon_trips ) ) {
				$coupon_trips = array_map( 'intval', $coupon_trips );
				// Defaults.
				$cart_has_coupon_trip = false; // Falg to indicate coupon code is valid for the item in the cart if coupon is added for certain trips.

				foreach ( $cart_contents as $key => $cart_content ) {
					$trip_id                      = $cart_content['trip_id'];
					$coupon_cart_contents[ $key ] = 0;
					if ( in_array( absint( $trip_id ), $coupon_trips, true ) ) {
						$cart_has_coupon_trip = true;
						$coupon_trip_total   += $cart_content['item_total'];

						// Check each item price with discount price. whether discount price is higher than trip price or not.
						if ( 'amount' === $coupon_type ) {
							$coupon_discount = $coupon_value;
							if ( $coupon_discount >= $cart_content['item_total'] ) {
								$response = array(
									'success' => false,
									'code'    => 'coupon_amount_limit_exceed',
								);
								return $response; // return if discount amount is higher than trip amount.
							}
						} else {
							$coupon_discount              = ( $cart_content['item_total'] * $coupon_value ) / 100;
							$coupon_cart_contents[ $key ] = $coupon_discount; // doesn't need this in case of above flat discount amount ['amount' === $coupon_type].
						}
					}
				}

				if ( ! $cart_has_coupon_trip ) {
					$response['success'] = false;
					$response['code']    = 'no_coupon_trip';
					return $response;
				}
			} else {
				// Check in all trips.
				foreach ( $cart_contents as $key => $cart_content ) {

					$trip_id                      = $cart_content['trip_id'];
					$coupon_trip_total           += $cart_content['item_total'];
					$coupon_cart_contents[ $key ] = 0;
					// Check each item price with discount price. whether discount price is higher than trip price or not.
					if ( 'amount' === $coupon_type ) {
						$coupon_discount = $coupon_value;
						if ( $coupon_discount >= $cart_content['item_total'] ) {
							$response = array(
								'success' => false,
								'code'    => 'coupon_amount_limit_exceed',
							);
							return $response; // return if discount amount is higher than trip amount.
						}
					} else {
						$coupon_discount              = ( $cart_content['item_total'] * $coupon_value ) / 100;
						$coupon_cart_contents[ $key ] = $coupon_discount; // doesn't need this in case of above flat discount amount ['amount' === $coupon_type].
					}
				}
			}
			// Total Coupon Discount Calculation.
			if ( 'amount' === $coupon_type ) {
				$coupon_discount_total = $coupon_value;
				if ( $coupon_discount_total >= $coupon_trip_total ) {
					$response = array(
						'success' => false,
						'code'    => 'coupon_amount_limit_exceed',
					);
					return $response;
				}
			} else {
				$coupon_discount_total = ( $coupon_trip_total * $coupon_value ) / 100;
			}
			$data['coupon_cart_contents']  = $coupon_cart_contents;
			$data['coupon_discount_total'] = $coupon_discount_total;

			// Set Coupon Discount start.
			$cart->set_applied_coupons( $data );
			do_action( 'tripzzy_applied_coupons', $data );

			return $response;
		}

		/**
		 * Validate Coupon
		 *
		 * @param string $coupon_code Coupon code.
		 */
		public static function validate( $coupon_code ) {

			// Empty check.
			if ( ! $coupon_code ) {
				$error_message = ErrorMessage::get( 'coupon_required' );
				wp_send_json_error( $error_message );
			}

			// Coupon ID exists check.
			$coupon_id = self::get_coupon_id_by_code( $coupon_code );
			if ( ! $coupon_id ) {
				$error_message = ErrorMessage::get( 'invalid_coupon' );
				wp_send_json_error( $error_message );
			}

			// Coupon post status publish check.
			$coupon = get_post( $coupon_id );
			if ( 'publish' !== $coupon->post_status ) {
				$error_message = ErrorMessage::get( 'invalid_coupon' );
				wp_send_json_error( $error_message );
			}

			if ( self::is_expired( $coupon_id ) ) {
				$error_message = ErrorMessage::get( 'expired_coupon' );
				wp_send_json_error( $error_message );
			}

			if ( self::is_limit_exceed( $coupon_id ) ) {
				$error_message = ErrorMessage::get( 'coupon_limit_exceed' );
				wp_send_json_error( $error_message );
			}

			if ( ! self::is_valid_user( $coupon_id ) ) {
				$error_message = ErrorMessage::get( 'unauthorized_coupon' );
				wp_send_json_error( $error_message );
			}

			$validation = self::validate_and_apply( $coupon_id );
			if ( ! $validation['success'] ) {
				$code = $validation['code'];
				switch ( $code ) {
					case 'no_coupon_trip':
					case 'coupon_amount_limit_exceed':
						$error_message = ErrorMessage::get( $code );
						wp_send_json_error( $error_message );
						break;

				}
			}
			return true;
		}

		/**
		 * Clear Coupon
		 *
		 * @return bool
		 */
		public static function clear() {

			// Clear Coupon.
			$data = array();
			$cart = tripzzy()->cart;
			$cart->set_applied_coupons( $data );
			do_action( 'tripzzy_cleared_coupons', $data );
			return true;
		}

		/**
		 * Get Applied Coupon data.
		 *
		 * @return array
		 */
		public static function get_applied_coupons() {
			$cart = tripzzy()->cart;
			return $cart->get_applied_coupons();
		}

		/**
		 * Get Applied Coupon code.
		 *
		 * @return string
		 */
		public static function get_applied_coupon_code() {
			$data = self::get_applied_coupons();

			return isset( $data['coupon_code'] ) ? $data['coupon_code'] : '';
		}
	}
}
