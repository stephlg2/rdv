<?php
/**
 * Admin Pointers.
 *
 * @package tripzzy
 */

namespace Tripzzy\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Http\Nonce;

if ( ! class_exists( 'Tripzzy\Admin\Pointers' ) ) {
	/**
	 * Admin Info Pointers Class
	 */
	class Pointers {

		use SingletonTrait;

		/**
		 * Dismissed Pointers data as per user.
		 *
		 * @var string $dismissed_pointers_key User meta name to store dismissed pointers.
		 * @since 1.0.0
		 */
		public static $dismissed_pointers_key = 'dismissed_pointers';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'tripzzy_filter_localize_variables', array( $this, 'localize' ) );

			// Ajax req to hide pointer on close.
			add_action( 'wp_ajax_tripzzy_dismiss_pointer', array( $this, 'dismiss' ) );

			// Pointers.
			// Display in plugins page.
			add_filter( 'tripzzy_filter_pointer_plugins', array( $this, 'display_menu' ) );
			// Display in admin home page.
			add_filter( 'tripzzy_filter_pointer_tripzzy_booking_page_tripzzy-homepage', array( $this, 'display_menu' ) );
		}

		/**
		 * Localize Pointers data.
		 *
		 * @param array $data Localized data to filter.
		 * @since 1.0.0
		 */
		public function localize( $data ) {
			$pointers = self::get();
			if ( $pointers && is_array( $pointers ) && count( $pointers ) ) {
				$data['pointers'] = $pointers;
			}
			return $data;
		}

		/**
		 * Get All Pointers list. Pointers will load from javascript as per return value of this method.
		 * This value is localized and used that localized value to display pointers.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public static function get() {

			if ( ! is_admin() ) {
				return;
			}

			$screen    = get_current_screen();
			$screen_id = $screen->id;
			/**
			 * Dynamic filter for pointers as per screen id.
			 *
			 * @since 1.0.0
			 */
			$all_pointers = apply_filters( 'tripzzy_filter_pointer_' . $screen_id, array() );

			if ( ! $all_pointers || ! is_array( $all_pointers ) ) {
				return;
			}

			// Get closed pointers data as per user.
			$dismissed = MetaHelpers::get_user_meta( get_current_user_id(), self::$dismissed_pointers_key, true );
			if ( ! $dismissed ) {
				$dismissed = array();
			}

			$valid_pointers = array();

			foreach ( $all_pointers as $pointer_id => $pointer ) {
				// Valid pointer check.
				if (
					empty( $pointer ) ||
					empty( $pointer_id ) ||
					empty( $pointer['target'] ) ||
					empty( $pointer['options'] ) ||
					in_array( $pointer_id, $dismissed, true )
					) {
					continue;
				}

				$pointer['pointer_id'] = $pointer_id;

				// Add the pointer to $valid_pointers array.
				$valid_pointers[] = $pointer;

			}
			return $valid_pointers;
		}

		/**
		 * Dismiss info nag message.
		 */
		public function dismiss() {
			if ( ! Nonce::verify() ) {
				return;
			}
			// Nonce already verified using Nonce::verify method.
			$pointer_id = sanitize_text_field( wp_unslash( $_POST['pointer_id'] ?? '' ) ); // @codingStandardsIgnoreLine

			if ( ! $pointer_id ) {
				return;
			}

			$user_id            = get_current_user_id();
			$dismissed_pointers = MetaHelpers::get_user_meta( $user_id, self::$dismissed_pointers_key, true );

			if ( ! $dismissed_pointers ) {
				$dismissed_pointers = array();
			}
			$dismissed_pointers[] = $pointer_id;

			MetaHelpers::update_user_meta( $user_id, self::$dismissed_pointers_key, $dismissed_pointers );
		}

		/**
		 * Display the Tripzzy menu as pointer on activation.
		 *
		 * @param array $pointers List of available pointers.
		 * @since 1.0.0
		 * @return array
		 */
		public function display_menu( $pointers ) {
			$pointer_content                     = '<p>Create your trips here !</p>';
			$pointers['tripzzy_welcome_pointer'] = array(
				'target'  => '#menu-posts-tripzzy',
				'options' => array(
					'content'  => sprintf( '<h3 class="update-notice"> %s </h3> <p> %s </p>', __( 'Welcome to Tripzzy', 'tripzzy' ), $pointer_content ),
					'position' => array(
						'edge'  => 'left',
						'align' => 'center',
					),
				),
			);
			return $pointers;
		}
	}
}
