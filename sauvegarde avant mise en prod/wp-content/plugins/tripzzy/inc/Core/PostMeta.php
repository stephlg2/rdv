<?php
/**
 * Tripzzy PostMeta.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Bases\PostTypeBase;

if ( ! class_exists( 'Tripzzy\Core\PostMeta' ) ) {
	/**
	 * Tripzzy PostMeta Class.
	 *
	 * @since 1.0.0
	 */
	class PostMeta {
		use SingletonTrait;

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'save_post', array( $this, 'save_meta' ) );
		}

		/**
		 * Save meta data main callback.
		 *
		 * @param int $post_id Post id to save post meta.
		 */
		public function save_meta( $post_id ) {
			if ( ! Nonce::verify() ) {
				return;
			}
			if ( ! is_admin() ) {
				return;
			}

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
			if ( wp_is_post_revision( $post_id ) ) {
				return;
			}

			// Nonce already verified using Nonce::verify method.
			$action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : ''; // @codingStandardsIgnoreLine
			if ( $action ) {
				if ( 'inline-save' === $action ) {
					return; // Return if action is quick edit.
				}
				if ( 'elementor_ajax' === $action ) {
					return; // Return if action is elementor ajax.
				}
			}
			$screen = get_current_screen();
			if ( ! $screen ) {
				return;
			}
			remove_action( 'save_post', array( $this, 'save_meta' ) );
			$post_types = PostTypeBase::get_post_types();
			foreach ( $post_types as $post_type ) {
				// Dynamic hook to save post meta.
				do_action( 'tripzzy_' . $post_type . '_save_post', $post_id );
			}
			add_action( 'save_post', array( $this, 'save_meta' ) );
		}
	}
}
