<?php
/**
 * Trip ajax class.
 *
 * @since 1.0.6
 * @package tripzzy
 */

namespace Tripzzy\Core\Ajax;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Helpers\Strings;

if ( ! class_exists( 'Tripzzy\Core\Ajax\NonceAjax' ) ) {
	/**
	 * Trip Ajax class.
	 *
	 * @since 1.0.6
	 */
	class NonceAjax {
		use SingletonTrait;

		/**
		 * All available messages.
		 *
		 * @var array
		 */
		private $messages;
		/**
		 * All available labels.
		 *
		 * @var array
		 */
		private $labels;


		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->messages = Strings::messages();
			$this->labels   = Strings::labels();

			add_action( 'wp_ajax_nopriv_tripzzy_handle_nonce', array( $this, 'handle_nonce' ) );
			add_action( 'wp_ajax_tripzzy_handle_nonce', array( $this, 'handle_nonce' ) );
		}

		/**
		 * Ajax callback to handle nonce update.
		 *
		 * This will generate and return new nonce if nonce is invalid.
		 *
		 * @since 1.0.6
		 */
		public function handle_nonce() {
			if ( ! Nonce::verify() ) {
				$response = array(
					'nonce' => Nonce::create(),
				);
				wp_send_json_success( $response, 200 );
			}
			$message = array( 'message' => 'Nonce is valid no need to update.' );
			wp_send_json_error( $message );
		}
	}
}
