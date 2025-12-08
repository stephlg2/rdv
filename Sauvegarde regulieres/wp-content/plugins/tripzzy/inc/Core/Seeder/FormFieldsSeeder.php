<?php
/**
 * Array list of Pages to create while activation.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Seeder;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\MetaHelpers;

if ( ! class_exists( 'Tripzzy\Core\Seeder\FormFieldsSeeder' ) ) {
	/**
	 * Create Page seeder class.
	 *
	 * @since 1.0.0
	 */
	class FormFieldsSeeder {

		/**
		 * An array of $args to create page.
		 *
		 * @var array
		 * @since 1.0.0
		 */
		private static $page_args = array();

		/**
		 * Post Type Key.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $post_type = 'tripzzy_form';

		/**
		 * This method will call only on plugin activation. Initialize Pages array to create.
		 *
		 * @since 1.0.0
		 */
		public static function init() {
			$settings = Settings::get();
			$pages    = self::get_pages();

			$has_new_page = false;
			foreach ( $pages as $page_data ) {
				$settings_key = sprintf( '%s_id', $page_data['field_type'] );
				$page_id      = isset( $settings[ $settings_key ] ) ? $settings[ $settings_key ] : 0;

				$new_page_id = self::create( $page_data, $page_id ); // only return page id if post is created.
				if ( $new_page_id > 0 ) {
					$has_new_page              = true;
					$settings[ $settings_key ] = $new_page_id;
				}
			}

			if ( $has_new_page ) {
				// update the settings.
				Settings::update( $settings );
			}
		}

		/**
		 * Get Available page to create.
		 *
		 * @since 1.0.0
		 */
		public static function get_pages() {
			// Note : need to add settings default key = `$field_type}_id` in the settings default fields to save fields id in settings.
			return apply_filters(
				'tripzzy_filter_fields_seeder',
				array(
					array(
						'post_name'    => _x( 'checkout-form', 'Page slug', 'tripzzy' ),
						'post_title'   => _x( 'Checkout Form', 'Page title', 'tripzzy' ),
						'post_content' => '',
						'field_type'   => 'checkout_form', // Required to sync ids. [option and post meta data] and also generate Settings key.
					),
					array(
						'post_name'    => _x( 'enquiry-form', 'Page slug', 'tripzzy' ),
						'post_title'   => _x( 'Enquiry Form', 'Page title', 'tripzzy' ),
						'post_content' => '',
						'field_type'   => 'enquiry_form',
					),
				)
			);
		}

		/**
		 * Create page as per page data argument.
		 *
		 * @param array $page_data Page argument.
		 * @param array $page_id Page id.
		 */
		public static function create( $page_data, $page_id ) {
			global $wpdb;

			$post_name    = $page_data['post_name'];
			$post_title   = $page_data['post_title'];
			$post_content = $page_data['post_content'];
			$field_type   = $page_data['field_type'];

			$page_obj = get_post( $page_id );

			if ( ! $page_id || ( $page_id > 0 && ! $page_obj ) || ( $page_obj && self::$post_type !== $page_obj->post_type ) ) { // Either new post or permanently deleted post case.
				$page_args = array(
					'post_status'    => 'publish',
					'post_type'      => self::$post_type,
					'post_name'      => $post_name,
					'post_title'     => $post_title,
					'post_content'   => $post_content,
					'comment_status' => 'closed',
				);
				$page_id   = wp_insert_post( $page_args );
				MetaHelpers::update_post_meta( $page_id, 'field_type', $page_data['field_type'] ); // Required to sync ids. [option and post meta data] and also generate Settings key.
				return $page_id;
			} elseif ( $page_obj ) {
				// If Page trashed, need to update the page.
				$post_status = get_post_status( $page_id );
				$is_trashed  = 'trash' === $post_status;
				if ( $is_trashed ) {
					$page_args = array(
						'ID'          => $page_id,
						'post_status' => 'publish',
					);
					wp_update_post( $page_args );
				}
			}
		}
	}
}
