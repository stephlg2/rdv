<?php
/**
 * Email ajax class.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Ajax;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Helpers\EmailTrackback;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Traits\SingletonTrait;

if ( ! class_exists( 'Tripzzy\Core\Ajax\EmailAjax' ) ) {
	/**
	 * Email ajax class.
	 *
	 * @since 1.0.0
	 */
	class EmailAjax {
		use SingletonTrait;

		/**
		 * Initialize ajax class.
		 */
		public function __construct() {
			add_action( 'wp_ajax_nopriv_tripzzy_email_trackback', array( $this, 'set' ) );
			add_action( 'wp_ajax_tripzzy_email_trackback', array( $this, 'set' ) );
		}

		/**
		 * Set email trackback status.
		 *
		 * @return void
		 */
		public function set() {
			$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
			if ( empty( $post_id ) ) {
				wp_die();
			}
			$nonce           = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
			$email_trackback = new EmailTrackback( $post_id );

			if ( ! wp_verify_nonce( $nonce, $email_trackback->get_nonce_action() ) ) {
				wp_die();
			}
			$email_trackback->update( 'read' );

			wp_die();
		}
	}

	EmailAjax::instance();
}
