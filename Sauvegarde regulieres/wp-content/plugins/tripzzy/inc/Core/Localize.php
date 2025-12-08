<?php
/**
 * To get all localized strings for admin and frontend.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\TripFilter;
use Tripzzy\Core\Helpers\Page;
use Tripzzy\Admin\Pointers;
use Tripzzy\Core\Helpers\Countries;
use Tripzzy\Core\PostTypes\TripzzyPostType;

if ( ! class_exists( 'Tripzzy\Core\Localize' ) ) {
	/**
	 * Localize the strings for frontend and admin.
	 *
	 * @since 1.0.0
	 */
	class Localize {

		/**
		 * Get all variable for localize.
		 *
		 * @since 1.0.0
		 * @since 1.0.9 Added is_search_result_page in variable list.
		 * @since 1.1.1 Added gateway and currency.
		 * @since 1.1.5 Added is_trips and is_taxonomy.
		 * @since 1.1.7 Added has_seasonal_pricing.
		 * @since 1.1.9 Added Dashboard Page URL.
		 * @since 1.2.1 Added allow_decimal_ratings, and emoji_on_ratings.
		 * @since 1.2.5 Added has_time and is_rtl.
		 * @since 1.2.8 Added trip includes and excludes as taxonomies.
		 *
		 * @return array
		 */
		public static function get_var() {
			global $post, $wp_query;
			$settings     = Settings::get();
			$taxonomies   = array_keys( TripFilter::taxonomy_filters() );
			$taxonomies[] = 'tripzzy_trip_includes';
			$taxonomies[] = 'tripzzy_trip_excludes';

			$localize_variable = array(
				'ajax_url'               => admin_url( 'admin-ajax.php' ),
				'urls'                   => array(
					'tripsArchive' => get_post_type_archive_link( TripzzyPostType::get_key() ),
					'home'         => home_url(),
					'dashboard'    => Page::get_url( 'dashboard' ),
				),
				'paged'                  => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
				'is_search_result_page'  => Page::is( 'search-result' ),
				'is_trips'               => Page::is( 'trips' ),
				'is_taxonomy'            => Page::is( 'taxonomy' ),
				'query_var'              => $wp_query->query_vars, // To help taxonomy page to filter trip as per taxonomy.
				'taxonomies'             => $taxonomies, // All taxonomies including custom (filter plus).
				'nonce'                  => Nonce::create(),
				'pointers'               => Pointers::get(),
				'strings'                => Strings::get(),
				'countries'              => Countries::get_dropdown_options( true ),
				'enable_smooth_scroll'   => $settings['enable_smooth_scroll'] ?? false,
				'smooth_scroll_offset'   => $settings['smooth_scroll_offset'] ?? 70, // offset 70px by default.
				'smooth_scroll_duration' => $settings['smooth_scroll_duration'] ?? 1000, // duration 1 sec by default.
				'sticky_tab_position'    => $settings['sticky_tab_position'] ?? 0,
				'allow_decimal_ratings'  => $settings['allow_decimal_ratings'] ?? true,
				'emoji_on_ratings'       => $settings['emoji_on_ratings'] ?? true,
				'gateway'                => array(), // list of Gateways and its config.
				'currency'               => $settings['currency'] ?? 'USD',
				'payment_description'    => $settings['payment_description'] ?? __( 'Payment for tripzzy', 'tripzzy' ),
				'has_seasonal_pricing'   => false,
				'is_rtl'                 => is_rtl(),
				'locale'                 => get_locale(),
			);
			if ( Request::is( 'admin' ) ) {
				$localize_variable['plugin_url'] = TRIPZZY_PLUGIN_DIR_URL;
				$localize_variable['has_time']   = tripzzy_has_time();
			}
			if ( is_object( $post ) ) {
				$localize_variable['post_id'] = $post->ID;
			}

			/**
			 * Filter the localize variable as per requirement.
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'tripzzy_filter_localize_variables', $localize_variable );
		}
	}
}
