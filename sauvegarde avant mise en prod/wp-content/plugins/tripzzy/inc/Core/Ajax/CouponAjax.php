<?php
/**
 * Coupon ajax class.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Ajax;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\Coupon;
use Tripzzy\Core\Helpers\ErrorMessage;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Http\Request;

if ( ! class_exists( 'Tripzzy\Core\Ajax\CouponAjax' ) ) {
	/**
	 * Coupon Ajax.
	 *
	 * @since 1.0.0
	 */
	class CouponAjax {
		use SingletonTrait;

		/**
		 * All available strings.
		 *
		 * @var array
		 */
		private $strings;

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'wp_ajax_tripzzy_get_coupon', array( $this, 'get' ) );
			add_action( 'wp_ajax_tripzzy_update_coupon', array( $this, 'update' ) );
			add_action( 'wp_ajax_tripzzy_check_coupon_exist', array( $this, 'check_coupon_exist' ) );

			// Apply coupon code on checkout.
			add_action( 'wp_ajax_nopriv_tripzzy_apply_coupon', array( $this, 'apply_coupon' ) );
			add_action( 'wp_ajax_tripzzy_apply_coupon', array( $this, 'apply_coupon' ) );

			// Clear coupon.
			add_action( 'wp_ajax_nopriv_tripzzy_clear_coupon', array( $this, 'clear_coupon' ) );
			add_action( 'wp_ajax_tripzzy_clear_coupon', array( $this, 'clear_coupon' ) );
		}

		/**
		 * Ajax callback to get coupon data.
		 *
		 * @since 1.0.0
		 */
		public function get() {
			if ( ! Nonce::verify() ) {
				$message = ErrorMessage::get( 'nonce_verification_failed' );
				wp_send_json_error( $message );
			}
			// Nonce already verified using Nonce::verify method.
			$coupon_id = isset( $_GET['coupon_id'] ) ? absint( $_GET['coupon_id'] ) : ''; // @codingStandardsIgnoreLine

			$response_data = Coupon::get( $coupon_id );
			$response      = array(
				'coupon' => $response_data,
			);
			wp_send_json_success( $response, 200 );
		}

		/**
		 * Ajax callback to set form data.
		 *
		 * @since 1.0.0
		 */
		public function update() {
			if ( ! Nonce::verify() ) {
				$message = ErrorMessage::get( 'nonce_verification_failed' );
				wp_send_json_error( $message );
			}

			// Nonce already verified using Nonce::verify method.
			$coupon_id     = isset( $_GET['coupon_id'] ) ? absint( $_GET['coupon_id'] ) : 0; // @codingStandardsIgnoreLine
			$coupon_fields = array_keys( Coupon::default_data() );

			$data = array();
			foreach ( $coupon_fields as $coupon_field ) {
				if ( 'coupon_trips' === $coupon_field || 'coupon_users' === $coupon_field ) { // array of user_ids and trip ids.
					$data[ $coupon_field ] = isset( $_POST[ $coupon_field ] ) && is_array( $_POST[ $coupon_field ] ) ? array_map( 'absint', $_POST[ $coupon_field ] ) : array(); // @codingStandardsIgnoreLine
				} else {
					$data[ $coupon_field ] = isset( $_POST[ $coupon_field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $coupon_field ] ) ) : ''; // @codingStandardsIgnoreLine
				}
			}
			$response_data = Coupon::update( $coupon_id, $data );
			if ( $coupon_id && $response_data ) {
				wp_send_json_success( $response_data, 200 );
			}
		}

		/**
		 * Check Coupon exists.
		 * Only for adding new coupon.
		 *
		 * @since 1.0.0
		 */
		public function check_coupon_exist() {
			if ( ! Nonce::verify() ) {
				$message = ErrorMessage::get( 'nonce_verification_failed' );
				wp_send_json_error( $message );
			}
			// Nonce already verified using Nonce::verify method.
			$coupon_id   = isset( $_GET['coupon_id'] ) ? absint( $_GET['coupon_id'] ) : 0; // @codingStandardsIgnoreLine
			$coupon_code = isset( $_GET['coupon_code'] ) ? sanitize_text_field( wp_unslash( $_GET['coupon_code'] ) ) : ''; // @codingStandardsIgnoreLine
			$res         = Coupon::get_coupon_id_by_code( $coupon_code );

			if ( ! $res || absint( $coupon_id ) === absint( $res ) ) { // If empty or current post.
				wp_send_json_success( $coupon_code );
			}
			wp_send_json_error( $coupon_code );
		}

		/**
		 * Apply from checkout form.
		 *
		 * @since 1.0.0
		 * @since 1.1.6 Implemented Request::sanitize_input to get coupon_code.
		 */
		public function apply_coupon() {
			$coupon_code = Request::sanitize_input( 'INPUT_POST', 'coupon_code' );
			$validated   = Coupon::validate( $coupon_code );

			if ( $validated ) {
				wp_send_json_success( $coupon_code );
			}
		}

		/**
		 * Clear coupon from checkout form.
		 *
		 * @since 1.0.0
		 * @since 1.1.6 Implemented Request::sanitize_input to get coupon_code.
		 */
		public function clear_coupon() {
			$coupon_code = Request::sanitize_input( 'INPUT_POST', 'coupon_code' );
			$validated   = Coupon::clear();

			if ( $validated ) {
				wp_send_json_success( $coupon_code );
			}
		}
	}
	CouponAjax::instance();
}
