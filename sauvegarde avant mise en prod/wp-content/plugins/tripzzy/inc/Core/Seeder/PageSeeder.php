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

if ( ! class_exists( 'Tripzzy\Core\Seeder\PageSeeder' ) ) {
	/**
	 * Create Page seeder class.
	 *
	 * @since 1.0.0
	 */
	class PageSeeder {

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
				$settings_key = $page_data['settings_key'];
				$page_id      = isset( $settings[ $settings_key ] ) ? $settings[ $settings_key ] : 0;

				$new_page_id = self::create( $page_data, $page_id ); // only return page id if post is created.
				if ( $new_page_id > 0 ) {
					$has_new_page              = true;
					$settings[ $settings_key ] = $new_page_id;
					MetaHelpers::update_post_meta( $new_page_id, 'settings_key', $settings_key ); // To check Tripzzy created pages later.
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
			return apply_filters(
				'tripzzy_filter_page_seeder',
				array(
					array(
						'post_name'      => _x( 'tz-search-result', 'Page slug', 'tripzzy' ),
						'post_title'     => _x( 'Search Result', 'Page title', 'tripzzy' ),
						'post_content'   => '[TRIPZZY_TRIP_SEARCH_RESULT]',
						'post_content_6' => '<!-- wp:shortcode -->[TRIPZZY_TRIP_SEARCH_RESULT]<!-- /wp:shortcode -->', // Add shortcode block to shortcode text.
						'settings_key'   => 'search_result_page_id',
						'title'          => __( 'Tripzzy Search Result Page', 'tripzzy' ), // Display in Settings > General > Page as field title.
						'tooltip'        => __( 'This page is used to display the search results of trips based on the search criteria.', 'tripzzy' ),
					),
					array(
						'post_name'      => _x( 'tz-checkout', 'Page slug', 'tripzzy' ),
						'post_title'     => _x( 'Checkout', 'Page title', 'tripzzy' ),
						'post_content'   => '[TRIPZZY_CHECKOUT]',
						'post_content_6' => '<!-- wp:shortcode -->[TRIPZZY_CHECKOUT]<!-- /wp:shortcode -->', // Add shortcode block to shortcode text.
						'settings_key'   => 'checkout_page_id',
						'title'          => __( 'Tripzzy Checkout Page', 'tripzzy' ), // Display in Settings > General > Page as field title.
						'tooltip'        => __( 'This page is used to display the checkout form for booking a trip.', 'tripzzy' ),
					),
					array(
						'post_name'      => _x( 'tz-thank-you', 'Page slug', 'tripzzy' ),
						'post_title'     => _x( 'Thank you', 'Page title', 'tripzzy' ),
						'post_content'   => '<p>Thank you for your booking with us. We have received your booking. We’ll update you shortly. Here are your booking details below:</p>[TRIPZZY_THANKYOU]',
						'post_content_6' => '<!-- wp:paragraph -->
											<p>Thank you for your booking with us. We have received your booking. We’ll update you shortly. Here are your booking details below:</p>
											<!-- /wp:paragraph -->
											<!-- wp:shortcode -->
											[TRIPZZY_THANKYOU]
											<!-- /wp:shortcode -->',
						'settings_key'   => 'thankyou_page_id',
						'title'          => __( 'Tripzzy Thankyou Page', 'tripzzy' ),
						'tooltip'        => __( 'This page is used to display the thank you message after a successful booking.', 'tripzzy' ),
					),
					array(
						'post_name'      => _x( 'tz-dashboard', 'Page slug', 'tripzzy' ),
						'post_title'     => _x( 'Dashboard', 'Page title', 'tripzzy' ),
						'post_content'   => '[TRIPZZY_DASHBOARD]',
						'post_content_6' => '<!-- wp:shortcode -->[TRIPZZY_DASHBOARD]<!-- /wp:shortcode -->',
						'settings_key'   => 'dashboard_page_id',
						'title'          => __( 'Tripzzy Dashboard Page', 'tripzzy' ),
						'tooltip'        => __( 'This page is used to display the user dashboard where users can manage their bookings and profile.', 'tripzzy' ),
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

			if ( version_compare( get_bloginfo( 'version' ), '6.0.0', '>=' ) ) {
				$post_content = $page_data['post_content_6'];
			}

			$page_obj = get_post( $page_id );

			if ( ! $page_id || ( $page_id > 0 && ! $page_obj ) ) { // Either new post or permanently deleted post case.
				$page_args = array(
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'post_name'      => $post_name,
					'post_title'     => $post_title,
					'post_content'   => $post_content,
					'comment_status' => 'closed',
				);
				$page_id   = wp_insert_post( $page_args );
				return $page_id;
			} elseif ( $page_obj ) {
				// check IF any page has Tripzzy page shortcode.
				if ( 'page' === $page_obj->post_type && ! in_array( $page_obj->post_status, array( 'trash', 'pending', 'future', 'auto-draft' ), true ) ) {
					$cache_key = 'tripzzy_created_page_has_shortcode_' . $page_id;
					$page_id   = wp_cache_get( $cache_key ); // Cached page id.

					if ( false === $page_id ) {
						$page_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'trash', 'pending', 'future', 'auto-draft' ) AND post_content LIKE %s LIMIT 1;", "%{$post_content}%" ) ); // @phpcs:ignore
						wp_cache_set( $cache_key, $page_id );
					}

					if ( $page_id ) {
						return $page_id;
					}
				}

				// If Page trashed, need to update the page.
				$is_trashed = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status = 'trash' AND post_content LIKE %s LIMIT 1;", "%{$post_content}%" ) ); // @phpcs:ignore
				if ( $is_trashed ) {
					$page_args = array(
						'ID'          => $is_trashed,
						'post_status' => 'publish',
					);
					wp_update_post( $page_args );
				}
			}
		}

		/**
		 * Pages option to use it in dropdown.
		 *
		 * @since 1.0.0
		 */
		public static function get_dropdown_options() {
			$lists = self::get_pages();

			$options = array_map(
				function ( $dropdown_list ) {
					return array(
						'label'   => html_entity_decode( $dropdown_list['title'] ),
						'value'   => html_entity_decode( $dropdown_list['settings_key'] ),
						'tooltip' => html_entity_decode( $dropdown_list['tooltip'] ),
					);
				},
				$lists
			);
			return $options;
		}
	}
}
