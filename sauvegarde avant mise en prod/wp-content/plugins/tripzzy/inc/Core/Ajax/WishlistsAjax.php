<?php
/**
 * Wishlist ajax class.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Ajax;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\Wishlists;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Http\Request;

if ( ! class_exists( 'Tripzzy\Core\Ajax\WishlistsAjax' ) ) {
	/**
	 * Wishlist Ajax Class.
	 *
	 * @since 1.0.0
	 */
	class WishlistsAjax {
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
			// Frontend side Ajax.
			add_action( 'wp_ajax_tripzzy_set_wishlists', array( $this, 'set' ) );
			add_action( 'wp_ajax_nopriv_tripzzy_set_wishlists', array( $this, 'set' ) );
		}

		/**
		 * Ajax callback to set trip in wishlist.
		 *
		 * @since 1.0.0
		 * @since 1.1.6 Implemented Request::sanitize_input to get values.
		 */
		public function set() {
			$user_id = get_current_user_id();
			$trip_id = Request::sanitize_input( 'INPUT_POST', 'trip_id' );
			$value   = Request::sanitize_input( 'INPUT_POST', 'value' );

			$data          = array(
				'trip_id' => $trip_id,
				'value'   => 'true' === $value ? true : false,
			);
			$response_data = Wishlists::update( $user_id, $data );

			if ( $user_id && $response_data ) {
				wp_send_json_success( $response_data, 200 );
			}
		}
	}

	WishlistsAjax::instance();
}
