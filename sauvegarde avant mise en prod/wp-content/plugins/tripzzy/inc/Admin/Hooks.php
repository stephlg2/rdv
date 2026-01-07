<?php
/**
 * Additional Hooks for admin pages, the hooks are other than post types and taxonomy.
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
use Tripzzy\Core\Helpers\Page;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Seeder\PageSeeder;
use Tripzzy\Core\Forms\CheckoutForm;
use Tripzzy\Core\Forms\EnquiryForm;
if ( ! class_exists( 'Tripzzy\Admin\Hooks' ) ) {
	/**
	 * Admin Info Hooks Class
	 */
	class Hooks {

		use SingletonTrait;


		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 * @since 1.1.5 Added admin help tab.
		 */
		public function __construct() {
			if ( Page::is_admin_pages() ) {
				add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
				add_action( 'in_admin_header', 'tripzzy_get_admin_header' );
				tripzzy_set_admin_help_tab();
			}
			add_filter( 'display_post_states', array( $this, 'display_post_states' ), 10, 2 );
		}

		/**
		 * Add admin body class.
		 *
		 * @param string $classes Admin class names.
		 * @return string
		 */
		public function admin_body_class( $classes ) {
			$classes .= ' tripzzy-admin-page'; // Common class for all tripzzy admin page.
			return $classes;
		}

		/**
		 * Display Post state for Tripzzy pages
		 *
		 * @param array  $states List of post states.
		 * @param object $post Post Object.
		 *
		 * @since 1.0.0
		 * @since 1.2.2 Added Post State for Forms.
		 * @return array
		 */
		public function display_post_states( $states, $post ) {
			$page_id   = absint( $post->ID );
			$post_type = $post->post_type;
			if ( 'page' === $post_type ) {
				$settings_key = MetaHelpers::get_post_meta( $post->ID, 'settings_key' );
				if ( ! $settings_key ) {
					return $states;
				}
				$settings_pages_data = PageSeeder::get_pages();
				$settings_pages      = array_reduce(
					$settings_pages_data,
					function ( $result, $item ) {
						$result[ $item['settings_key'] ] = $item['title'];
						return $result;
					},
					array()
				);
				$settings            = Settings::get();
				$settings_page_id    = absint( $settings[ $settings_key ] ?? 0 );
				if ( isset( $settings_pages[ $settings_key ] ) && $page_id === $settings_page_id ) {
					$states[] = $settings_pages[ $settings_key ];
				}
				return $states;
			} elseif ( 'tripzzy_form' === $post_type ) {
				$checkout_page_id = absint( CheckoutForm::get_form_id() );
				$enquiry_page_id  = absint( EnquiryForm::get_form_id() );

				if ( $checkout_page_id && $checkout_page_id === $page_id ) {
					$states[] = __( 'Checkout Form', 'tripzzy' );
				} elseif ( $enquiry_page_id && $enquiry_page_id === $page_id ) {
					$states[] = __( 'Enquiry Form', 'tripzzy' );
				}
				return $states;
			}

			return $states;
		}
	}
}
