<?php
/**
 * Form Ajax class.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Ajax;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Forms\Form;
use Tripzzy\Core\Forms\Input;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\ArrayHelper;

if ( ! class_exists( 'Tripzzy\Core\Ajax\FormAjax' ) ) {
	/**
	 * Form Ajax class.
	 *
	 * @since 1.0.0
	 */
	class FormAjax {
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
			$this->strings = Strings::messages();
			add_action( 'wp_ajax_tripzzy_get_form', array( $this, 'get' ) );
			add_action( 'wp_ajax_tripzzy_set_form', array( $this, 'set' ) );
			add_action( 'wp_ajax_tripzzy_reset_form', array( $this, 'reset' ) );
		}

		/**
		 * Ajax callback to get form data.
		 *
		 * @since 1.0.0
		 */
		public function get() {
			if ( ! Nonce::verify() ) {
				$message = array( 'message' => $this->strings['nonce_verification_failed'] );
				wp_send_json_error( $message );
			}

			// Nonce already verified using Nonce::verify method.
			$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : ''; // @codingStandardsIgnoreLine
			$fields  = Form::get_fields_by_id( $form_id );
			// Field types.
			$field_types = Input::get_field_types();

			$response = array(
				'fields'      => $fields,
				'field_types' => $field_types,
			);
			wp_send_json_success( $response, 200 );
		}

		/**
		 * Ajax callback to set form data.
		 *
		 * @since 1.0.0
		 */
		public function set() {
			if ( ! Nonce::verify() ) {
				$message = array( 'message' => $this->strings['nonce_verification_failed'] );
				wp_send_json_error( $message );
			}
			// Nonce already verified using Nonce::verify method.
			$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0; // @codingStandardsIgnoreLine
			$data    = Request::get_payload();
			if ( $form_id && $data ) {
				// set new field = false while saving fields.
				$data = array_map(
					function ( $array_values ) {
						$array_values['chosen']   = $array_values['chosen'] ? true : false; // Just to make hash code hash code consistency for sortable item.
						$array_values['selected'] = $array_values['selected'] ? true : false; // Just to make hash code hash code consistency for sortable item.
						$array_values['priority'] = (int) $array_values['priority']; // Just to make hash code hash code consistency for sortable item.
						$array_values['is_new']   = false;
						$array_values['value']    = ''; // do not add value if form saved from admin.
						return $array_values;
					},
					$data
				);
				// @todo Need to set/update is_default [true, false] as per default fields data.
				$data = array_column( $data, null, 'name' );
				MetaHelpers::update_post_meta( $form_id, 'fields', $data );

				// Update Post status to publish.
				$form                = get_post( $form_id, 'ARRAY_A' );
				$form['post_status'] = 'publish';
				wp_update_post( $form );

				wp_send_json_success( $data, 200 );
			}
		}

		/**
		 * Ajax callback to reset form data.
		 *
		 * @since 1.0.0
		 */
		public function reset() {
			if ( ! Nonce::verify() ) {
				$message = array( 'message' => $this->strings['nonce_verification_failed'] );
				wp_send_json_error( $message );
			}
			// Nonce already verified using Nonce::verify method.
			$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : ''; // @codingStandardsIgnoreLine
			if ( $form_id ) {
				MetaHelpers::delete_post_meta( $form_id, 'fields' );
				$this->get(); // $this->get() will also send response as well. So we don't need to send response again.
			}
		}
	}
	FormAjax::instance();
}
