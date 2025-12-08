<?php
/**
 * Fix Custom taxonomy not displayed in admin add menu item section.
 *
 * Note : Issue arese in New WP Setup with any custom taxonomy included plugin activated then go to Appearance > menu page.
 *
 * @package tripzzy
 * @since   1.0.0
 */

namespace Tripzzy\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Bases\TaxonomyBase;

if ( ! class_exists( 'Tripzzy\Admin\NavMenuMetaboxes' ) ) {

	/**
	 * Class NavMenuMetaboxes.
	 *
	 * @type  `static`
	 * @since 1.0.0
	 */
	class NavMenuMetaboxes {

		use SingletonTrait;

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			// Alternative to Filter hook default_hidden_meta_boxes.
			self::unhide_metaboxes();
		}

		/**
		 * Unhide tripzzy taxonomy on the admin menu page.
		 *
		 * @since 1.0.0
		 * @since 1.0.7 Fixes Error on admin menu page if plugin activated and go to admin menu for the first time. It will not show error if visited admin menu page first and activate plugin.
		 * @return void
		 */
		public static function unhide_metaboxes() {
			$did_unhide = MetaHelpers::get_option( 'unhide_metaboxes', 'no' );
			if ( 'yes' === $did_unhide ) {
				return;
			}
			$hidden = get_user_option( 'metaboxhidden_nav-menus' );
			if ( ! is_array( $hidden ) ) {
				return;
			}

			$metaboxes_to_show = array(
				'add-tripzzy_keywords',
				'add-tripzzy_trip_activities',
				'add-tripzzy_trip_destination',
				'add-tripzzy_trip_type',
			);
			foreach ( $metaboxes_to_show as $show_metabox ) {
				if ( in_array( $show_metabox, $hidden, true ) ) {
					unset( $hidden[ array_search( $show_metabox, $hidden ) ] ); // @phpcs:ignore
				}
			}
			$user = wp_get_current_user();
			update_user_meta( $user->ID, 'metaboxhidden_nav-menus', array_values( $hidden ) );

			MetaHelpers::update_option( 'unhide_metaboxes', 'yes' );
		}
	}
}
