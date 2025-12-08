<?php
/**
 * Cart ajax class.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Ajax;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\Coupon;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\ErrorMessage;
use Tripzzy\Core\Helpers\Page;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Cart;
use Tripzzy\Core\SessionHandler;

if ( ! class_exists( 'Tripzzy\Core\Ajax\CartAjax' ) ) {
	/**
	 * Cart Ajax.
	 *
	 * @since 1.0.0
	 */
	class CartAjax {
		use SingletonTrait;

		/**
		 * Constructor.
		 */
		public function __construct() {
			// Add.
			add_action( 'wp_ajax_tripzzy_add_to_cart', array( $this, 'add' ) );
			add_action( 'wp_ajax_nopriv_tripzzy_add_to_cart', array( $this, 'add' ) );

			// Remove.
			add_action( 'wp_ajax_tripzzy_remove_cart_item', array( $this, 'remove' ) );
			add_action( 'wp_ajax_nopriv_tripzzy_remove_cart_item', array( $this, 'remove' ) );
		}

		/**
		 * Get main instance of cart class.
		 *
		 * @return \Cart
		 */
		public function get_cart_instance() {
			$cart = tripzzy()->cart;

			if ( ! $cart || ! $cart instanceof Cart ) {
				$error_message = new \WP_Error( 'tripzzy_cart_error', __( 'Unable to retrieve cart.', 'tripzzy' ), 500 );
				wp_send_json_error( $error_message );
			}
			return $cart;
		}

		/**
		 * Validate Add to request cart.
		 *
		 * @param array $cart_data Cart Data.
		 * @since 1.0.0
		 * @return boolean
		 */
		protected function validate_cart_request( $cart_data ) {
			return (bool) $cart_data && isset( $cart_data['trip_id'] ) && isset( $cart_data['categories'] ) && is_array( $cart_data['categories'] ) && array_sum( $cart_data['categories'] ) > 0;
		}

		/**
		 * Validate for Negative qty value.
		 *
		 * @param array $cart_data Cart Data.
		 * @since 1.0.9
		 * @deprecated 1.1.7 Use validate_cart.
		 * @return boolean
		 */
		protected function validate_item_qty( $cart_data ) {
			$has_negative_qty = false;
			if ( is_array( $cart_data['categories'] ) ) {
				foreach ( $cart_data['categories'] as $qty ) {
					if ( $qty < 0 ) {
						$has_negative_qty = true;
						break;
					}
				}
			}
			return (bool) $cart_data && ! $has_negative_qty;
		}

		/**
		 * Responsible for cart item validation.
		 *
		 * @param array $cart_data Cart Data.
		 * @since 1.2.9 Error response message updated for package with one category.
		 * @since 1.2.1 Added tripzzy_filter_has_advanced_min_people, and tripzzy_filter_validate_cart_response hook.
		 * @since 1.1.7
		 * @return array
		 */
		protected function validate_cart( $cart_data ) {
			$response = array(
				'success' => false,
				'message' => ErrorMessage::get(),
			);

			$has_negative_qty = false;
			$total_qty        = 0;
			if ( is_array( $cart_data['categories'] ) ) {
				foreach ( $cart_data['categories'] as $qty ) {
					if ( $qty < 0 ) {
						$has_negative_qty = true;
						break;
					}
					$total_qty += $qty;
				}
			}

			/**
			 * To filter total Qty according to category and other flags.
			 *
			 * @internal Unused hook.
			 * @since 1.1.7
			 */
			$total_qty = apply_filters( 'tripzzy_filter_cart_total_qty', $total_qty, $cart_data );

			if ( ! empty( $cart_data ) ) {
				$trip       = new Trip( $cart_data['trip_id'] );
				$min_people = $trip->get_meta( 'min_people' );
				/**
				 * Filter to check whether advanced min people enabled or not.
				 *
				 * @since 1.2.1
				 */
				$has_advanced_min_people = apply_filters( 'tripzzy_filter_has_advanced_min_people', false, $trip, $cart_data );

				if ( $has_negative_qty ) {
					$response['success'] = false;
					$response['message'] = ErrorMessage::get( 'negative_cart_value' );
					return $response;
				}
				$cart_categories = $cart_data['categories'] ?? array();
				if ( ! $this->validate_cart_request( $cart_data ) && ! $has_advanced_min_people ) {
					$response['success'] = false;
					if ( 1 === count( $cart_categories ) ) {
						$term_id  = key( $cart_categories );
						$category = get_term( $term_id );
						$cat_name = __( 'People', 'tripzzy' );
						if ( ! is_wp_error( $category ) ) {
							$cat_name = $category->name;
						}
						$response['message'] = ErrorMessage::get( 'min_cart_value_required', array( $min_people, $cat_name ) );
					} else {
						$response['message'] = ErrorMessage::get( 'invalid_cart_request' );
					}
					return $response;
				}
				if ( ! ( $total_qty >= (int) $min_people ) && ! $has_advanced_min_people ) {
					$response['success'] = false;
					if ( 1 === count( $cart_categories ) ) {
						$term_id  = key( $cart_categories );
						$category = get_term( $term_id );
						$cat_name = __( 'People', 'tripzzy' );
						if ( ! is_wp_error( $category ) ) {
							$cat_name = $category->name;
						}
						$response['message'] = ErrorMessage::get( 'min_cart_value_required', array( $min_people, $cat_name ) );
					} else {
						$response['message'] = ErrorMessage::get( 'min_cart_value_required', array( $min_people ), 'plural' );
					}
					return $response;
				}

				// Return true if all validation complete.
				$response['success'] = true;
				$response['message'] = '';
				/**
				 * Modify cart validation response.
				 *
				 * @since 1.2.1
				 */
				$response = apply_filters( 'tripzzy_filter_validate_cart_response', $response, $cart_data, $trip );

				return $response;

			}

			return $response;
		}

		/**
		 * Add to cart ajax action.
		 *
		 * @since 1.0.0
		 * @since 1.0.9 Added validation check for validate_item_qty.
		 * @since 1.1.3 Added Hook tripzzy_filter_add_to_cart_data.
		 * @since 1.1.6 Implemented Request::sanitize_input to get payload data.
		 */
		public function add() {
			$payload   = Request::sanitize_input( 'INPUT_PAYLOAD' );
			$cart_data = array(
				'trip_id'    => $payload['trip_id'] ?? 0,
				'start_date' => $payload['start_date'] ?? '',
				'categories' => $payload['categories'] ?? array(),
				'package_id' => $payload['package_id'] ?? 0,
			);
			/**
			 * Filter to modify cart data.
			 *
			 * @since 1.1.3
			 */
			$cart_data = apply_filters( 'tripzzy_filter_add_to_cart_data', $cart_data, $payload );

			$validation = $this->validate_cart( $cart_data );

			if ( ! $validation['success'] ) {
				$error_message = $validation['message'];
				wp_send_json_error( $error_message );
			}
			$trip_id  = $cart_data['trip_id'];
			$quantity = array_sum( $cart_data['categories'] );
			$cart     = $this->get_cart_instance();
			$cart_id  = $cart->add( $cart_data, $trip_id, $quantity );

			if ( ! $cart_id ) {
				$error_message = ErrorMessage::get( 'unable_to_add_cart_item' );
				wp_send_json_error( $error_message );
			}

			// Redirect after trip added to cart.
			$checkout_url = Page::get_url( 'checkout' );
			/**
			 * Flter the redirect url after add to cart.
			 *
			 * @since 1.0.0
			 */
			$checkout_url = apply_filters( 'tripzzy_filter_checkout_url', $checkout_url );

			$response_args = array(
				'cart_id'  => $cart_id,
				'message'  => __( 'Added to cart successfully.', 'tripzzy' ),
				'redirect' => $checkout_url,
			);

			wp_send_json_success( $response_args, 200 );
		}

		/**
		 * Remove from cart ajax action.
		 *
		 * @since 1.0.0
		 * @since 1.1.5 Added Coupon clear on remove item.
		 * @since 1.1.6 Implemented Request::sanitize_input to get cart_id.
		 */
		public function remove() {

			$cart_id = Request::sanitize_input( 'INPUT_POST', 'cart_id' );
			$cart    = $this->get_cart_instance();
			$cart_id = $cart->remove( $cart_id );

			// Clear Coupon use if cart item removed. @since 1.1.5.
			Coupon::clear();

			$response_args = array(
				'cart_id' => $cart_id,
				'message' => __( 'Remove from cart successfully.', 'tripzzy' ),
			);

			wp_send_json_success( $response_args, 200 );
		}
	}

	CartAjax::instance();
}
