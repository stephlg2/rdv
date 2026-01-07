<?php
/**
 * Enquiry ajax class.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Ajax;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\PostTypes\EnquiryPostType;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\ErrorMessage;
use Tripzzy\Core\SendEmails;
use Tripzzy\Core\Forms\EnquiryForm;


if ( ! class_exists( 'Tripzzy\Core\Ajax\EnquiryAjax' ) ) {
	/**
	 * Enquiry ajax class.
	 *
	 * @since 1.0.0
	 */
	class EnquiryAjax {
		use SingletonTrait;

		/**
		 * Initialize ajax class.
		 */
		public function __construct() {
			add_action( 'wp_ajax_nopriv_tripzzy_add_enquiry', array( $this, 'add' ) );
			add_action( 'wp_ajax_tripzzy_add_enquiry', array( $this, 'add' ) );
		}

		/**
		 * Add new enquiry.
		 *
		 * @since 1.0.0
		 * @since 1.1.6 Implemented Request::sanitize_input to get data.
		 *
		 * @return void
		 */
		public function add() {
			$payload = Request::sanitize_input( 'INPUT_POST' );

			$field_names = EnquiryForm::get_field_names();
			$data        = array();
			foreach ( $field_names as $field_name ) {
				$data[ $field_name ] = $payload[ $field_name ] ?? '';
			}
			// Validation.
			if ( empty( $data['full_name'] ) ) {
				$message = ErrorMessage::get( 'full_name_required' );
				wp_send_json_error( $message );
			}
			if ( empty( $data['email'] ) ) {
				$message = ErrorMessage::get( 'email_required' );
				wp_send_json_error( $message );
			}
			if ( empty( $data['message'] ) ) {
				$message = ErrorMessage::get( 'message_required' );
				wp_send_json_error( $message );
			}

			$enquiry_id = wp_insert_post(
				array(
					'post_type'   => 'tripzzy_enquiry',
					'post_status' => 'pending',
					'post_title'  => sanitize_email( $data['email'] ),
				)
			);

			if ( is_wp_error( $enquiry_id ) ) {
				wp_send_json_error( __( 'Unable to create enquiry', 'tripzzy' ) );
			}
			EnquiryPostType::save_meta( $enquiry_id, $data );

			/**
			 * Hook: tripzzy_after_enquiry.
			 *
			 * @hooked Tripzzy\Core\SendEmails->send_enquiry_emails - 10;
			 */
			do_action( 'tripzzy_after_enquiry', $enquiry_id, $data );

			$response = array(
				'enquiry_id' => $enquiry_id,
				'message'    => __( 'Enquiry sent successfully!', 'tripzzy' ),
			);
			wp_send_json_success( $response, 200 );
		}
	}

	EnquiryAjax::instance();
}
