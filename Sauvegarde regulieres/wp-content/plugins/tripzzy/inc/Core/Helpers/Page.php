<?php
/**
 * Check the current page type.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Helpers\TripFilter;
use Tripzzy\Core\Helpers\ArrayHelper;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Shortcodes\DashboardShortcode;
use Tripzzy\Core\Shortcodes\CheckoutPageShortcode;
use Tripzzy\Core\Shortcodes\ThankyouPageShortcode;
use Tripzzy\Core\Shortcodes\TripSearchResultPageShortcode;
use Tripzzy\Core\PostTypes\TripzzyPostType;

if ( ! class_exists( 'Tripzzy\Core\Helpers\Page' ) ) {
	/**
	 * Class For Page
	 *
	 * @since 1.0.0
	 */
	class Page {
		/**
		 * Check whether current/requested page is Tripzzy pages or not.
		 *
		 * @param string  $key       page key.
		 * @param boolean $admin_page check if page is admin page.
		 *
		 * @since 1.2.9 Removed switch case for admin page check.
		 * @since 1.2.8 Added trip includes and trip excludes as taxonomy pages.
		 * @since 1.1.5 Added Taxonomy page.
		 * @since 1.1.2 Added Themes page.
		 * @since 1.0.8 Added site editor page for admin.
		 * @since 1.0.6 Search page key changed to 'search-result' from 'search'.
		 * @since 1.0.0
		 * @return boolean
		 */
		public static function is( $key, $admin_page = false ) {
			$settings = Settings::get();
			if ( $admin_page ) {
				if ( ! Request::is( 'admin' ) ) {
					return;
				}
				if ( ! function_exists( 'get_current_screen' ) ) {
					return;
				}
				$screen = get_current_screen();
				if ( ! $screen ) {
					return;
				}
				$all_pages = self::admin_page_ids();
				$pages     = $all_pages[ $key ] ?? array();
				if ( ! $pages ) {
					return;
				}
				if ( ! is_array( $pages ) ) {
					return $pages === $screen->id;
				}
				return in_array( $screen->id, $pages, true );
			} else {
				$post_types = array( 'tripzzy' );
				$taxonomies = array_keys( TripFilter::taxonomy_filters() );
				$taxonomies = array_merge( $taxonomies, array( 'tripzzy_trip_includes', 'tripzzy_trip_excludes' ) );
				switch ( $key ) {
					case 'trip': // Single trip page.
						return is_singular( $post_types );
					case 'search-result':
						return self::has_shortcode( TripSearchResultPageShortcode::get_key() );
					case 'trips': // Archive page including taxonomy pages.
						return is_post_type_archive( $post_types ) || is_tax( $taxonomies );
					case 'taxonomy': // taxonomy pages only.
						return is_tax( $taxonomies );
					case 'dashboard':
						return self::has_shortcode( DashboardShortcode::get_key() );
					case 'checkout':
						return self::has_shortcode( CheckoutPageShortcode::get_key() );
					case 'thankyou':
						return self::has_shortcode( ThankyouPageShortcode::get_key() );
				}
				return;
			}
			return false;
		}

		/**
		 * Return the page url of provided key. Only for front end purpose.
		 *
		 * @param mixed $key Page Id or key.
		 *
		 * @since 1.0.0
		 * @since 1.2.7 Called self::get_id method to get page id.
		 * @return string
		 */
		public static function get_url( $key ) {

			if ( ! $key ) {
				return;
			}

			if ( is_numeric( $key ) ) {
				$page_id = $key;
			} else {
				if ( 'trips' === $key ) {
					return get_post_type_archive_link( TripzzyPostType::get_key() );
				}
				$page_id = self::get_id( $key );
			}

			return $page_id ? get_the_permalink( $page_id ) : '';
		}

		/**
		 * Return the page ID of provided key. Only for front end purpose.
		 *
		 * @param mixed $key Page key.
		 *
		 * @since 1.2.7
		 * @return int
		 */
		public static function get_id( $key ) {
			$page_id  = 0;
			$settings = Settings::get();
			switch ( $key ) {
				case 'thankyou':
					$page_id = isset( $settings['thankyou_page_id'] ) ? $settings['thankyou_page_id'] : 0;
					break;
				case 'checkout':
					$page_id = isset( $settings['checkout_page_id'] ) ? $settings['checkout_page_id'] : 0;
					break;
				case 'dashboard':
					$page_id = isset( $settings['dashboard_page_id'] ) ? $settings['dashboard_page_id'] : 0;
					break;
				case 'search-result':
						$page_id = isset( $settings['search_result_page_id'] ) ? $settings['search_result_page_id'] : 0;
					break;
			}
			return $page_id;
		}

		/**
		 * Get page slug by page key.
		 *
		 * @param string $key Page key.
		 * @since 1.2.7
		 * @return string
		 */
		public static function get_slug( $key ) {
			$page_id = self::get_id( $key );
			return get_post_field( 'post_name', $page_id );
		}


		/**
		 * Check whether current page is  Tripzzy admin page or not.
		 *
		 * @return boolean
		 */
		public static function is_admin_pages() {

			if ( ! Request::is( 'admin' ) ) {
				return;
			}
			if ( ! function_exists( 'get_current_screen' ) ) {
				return;
			}

			$screen = \get_current_screen();
			if ( ! $screen ) {
				return;
			}
			$admin_pages = self::admin_page_ids();
			$admin_pages = ArrayHelper::array_values( $admin_pages );
			return in_array( $screen->id, $admin_pages, true );
		}


		/**
		 * Return Admin Page ids.
		 *
		 * @since 1.1.2 Added Themes page.
		 * @since 1.0.0
		 */
		public static function admin_page_ids() {
			$pages = array(
				// Pages.
				'homepage'            => array( 'tripzzy_booking_page_tripzzy-homepage' ),
				'settings'            => array( 'tripzzy_booking_page_tripzzy-settings' ),
				'system-info'         => array( 'tripzzy_booking_page_tripzzy-system-info' ),
				'custom-categories'   => array( 'tripzzy_booking_page_tripzzy-custom-categories' ),
				'themes'              => array( 'tripzzy_booking_page_tripzzy-themes' ),
				// Post Types.
				'coupons'             => array( 'tripzzy_coupon', 'edit-tripzzy_coupon' ),
				'trips'               => array( 'tripzzy', 'edit-tripzzy' ),
				'forms'               => array( 'tripzzy_form', 'edit-tripzzy_form' ),
				'bookings'            => array( 'tripzzy_booking', 'edit-tripzzy_booking' ),
				'enquiry'             => array( 'tripzzy_enquiry', 'edit-tripzzy_enquiry' ),
				'customer'            => array( 'tripzzy_customer', 'edit-tripzzy_customer' ),

				// Taxonomies.
				'trip-type'           => array( 'edit-tripzzy_trip_type' ),
				'trip-includes'       => array( 'edit-tripzzy_trip_includes' ),
				'trip-excludes'       => array( 'edit-tripzzy_trip_excludes' ),
				'trip-destination'    => array( 'edit-tripzzy_trip_destination' ),
				'trip-price-category' => array( 'edit-tripzzy_price_category' ),
				'trip-keywords'       => array( 'edit-tripzzy_keywords' ),
				'site-editor'         => 'site-editor',
			);
			return apply_filters( 'tripzzy_filter_admin_page_ids', $pages );
		}

		/**
		 * Get all available page list.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public static function get_all() {
			// Page Lists.
			$lists     = get_posts(
				array(
					'numberposts' => -1,
					'post_type'   => 'page',
					'orderby'     => 'title',
					'order'       => 'asc',
				)
			);
			$page_list = array();
			$i         = 0;
			foreach ( $lists as $page_data ) {
				$page_list[ $i ]['label'] = sprintf( '%s (#%d)', $page_data->post_title, $page_data->ID );
				$page_list[ $i ]['value'] = $page_data->ID;
				++$i;
			}
			return $page_list;
		}

		/**
		 * Check whether page content has provided tags or not.
		 *
		 * @param string $tag Shortcode tag.
		 * @since 1.0.0
		 * @return boolean
		 */
		public static function has_shortcode( $tag = '' ) {
			if ( ! $tag ) {
				return;
			}
			global $post;

			return is_singular() && is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $tag );
		}
	}
}
