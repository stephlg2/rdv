<?php
/**
 * Singleton trait for plugin.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Traits;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Helpers\Icon;
/**
 * Define Trait.
 */
trait AdminMenuTrait {
	/**
	 * All Submenus for Tripzzy.
	 *
	 * @since 1.0.0
	 * @since 1.1.2 Added Themes menu.
	 * @return String
	 */
	public static function get_submenus() {
		$menus = array(
			'edit.php?post_type=tripzzy_booking' => array(
				array(
					'priority'   => '1',
					'page_title' => __( 'Homepage', 'tripzzy' ),
					'menu_title' => __( 'Home', 'tripzzy' ),
					'menu_slug'  => 'tripzzy-homepage',
					'callback'   => array( 'Tripzzy\Admin\Views\HomePageView', 'render' ),
					'position'   => 0,
				),
				array(
					'priority'   => '130',
					'page_title' => __( 'Filters +', 'tripzzy' ),
					'menu_title' => __( 'Filters +', 'tripzzy' ),
					'menu_slug'  => 'tripzzy-custom-categories',
					'callback'   => array( 'Tripzzy\Admin\Views\FilterPlusView', 'render' ),
				),
				array(
					'priority'   => '140',
					'page_title' => __( 'Themes:Tripzzy', 'tripzzy' ),
					'menu_title' => __( 'Themes', 'tripzzy' ),
					'menu_slug'  => 'tripzzy-themes',
					'callback'   => array( 'Tripzzy\Admin\Views\ThemesView', 'render' ),
				),
				array(
					'priority'   => '150',
					'page_title' => __( 'Settings:Tripzzy', 'tripzzy' ),
					'menu_title' => __( 'Settings', 'tripzzy' ),
					'menu_slug'  => 'tripzzy-settings',
					'callback'   => array( 'Tripzzy\Admin\Views\SettingsView', 'render' ),
				),

				array(
					'priority'   => '200',
					'page_title' => __( 'System Information', 'tripzzy' ),
					'menu_title' => __( 'System Information', 'tripzzy' ),
					'menu_slug'  => 'tripzzy-system-info',
					'callback'   => array( 'Tripzzy\Admin\Views\SystemInfoView', 'render' ),
				),
			),
		);
		/**
		 * Sub Menu filter hook to modify custom submenus.
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'tripzzy_filter_submenus', $menus );
	}

	/**
	 * All Admin Bar menus for Tripzzy.
	 *
	 * @since 1.0.0
	 * @return String
	 */
	public static function get_admin_bar_menus() {
		$menus = array();
		if ( ! is_network_admin() ) {
			$menus = array(
				array(
					'id'    => 'tripzzy-admin-bar-menus',
					'group' => null,
					'title' => '<span class="ab-icon" aria-hidden="true" style="width:18px">' . Icon::get_svg_icon( 'brand-grayscale', array( 18, 22.53 ) ) . '</span>' . __( 'Tripzzy', 'tripzzy' ),
					'href'  => admin_url( 'edit.php?post_type=tripzzy_booking&page=tripzzy-homepage' ),
					'meta'  => array(
						'title' => __( 'Tripzzy', 'tripzzy' ),
					),
					'child' => array(
						array(
							'id'    => 'tripzzy-admin-bar-menus-new-trip',
							'group' => null,
							'title' => '<span class="ab-icon" aria-hidden="true"></span>' . __( 'New Trip', 'tripzzy' ),
							'href'  => admin_url( 'post-new.php?post_type=tripzzy' ),
							'meta'  => array(
								'title' => __( 'New Trip', 'tripzzy' ),
							),
						),
						array(
							'id'    => 'tripzzy-admin-bar-menus-all-trips',
							'group' => null,
							'title' => '<span class="ab-icon" aria-hidden="true">' . Icon::get_svg_icon( 'trip' ) . '</span>' . __( 'All Trips', 'tripzzy' ),
							'href'  => admin_url( 'edit.php?post_type=tripzzy' ),
							'meta'  => array(
								'title' => __( 'All Trips', 'tripzzy' ),
							),
						),
						array(
							'id'    => 'tripzzy-admin-bar-menus-all-enquiries',
							'group' => null,
							'title' => '<span class="ab-icon" aria-hidden="true"></span>' . __( 'All Enquiries', 'tripzzy' ),
							'href'  => admin_url( 'edit.php?post_type=tripzzy_enquiry' ),
							'meta'  => array(
								'title' => __( 'All Enquiries', 'tripzzy' ),
							),
						),
						array(
							'id'    => 'tripzzy-admin-bar-menus-all-customers',
							'group' => null,
							'title' => '<span class="ab-icon" aria-hidden="true"></span>' . __( 'All Customers', 'tripzzy' ),
							'href'  => admin_url( 'edit.php?post_type=tripzzy_customer' ),
							'meta'  => array(
								'title' => __( 'All Customers', 'tripzzy' ),
							),
						),
						array(
							'id'    => 'tripzzy-admin-bar-menus-all-coupons',
							'group' => null,
							'title' => '<span class="ab-icon" aria-hidden="true"></span>' . __( 'All Coupons', 'tripzzy' ),
							'href'  => admin_url( 'edit.php?post_type=tripzzy_coupon' ),
							'meta'  => array(
								'title' => __( 'All Coupons', 'tripzzy' ),
							),
						),
						array(
							'id'    => 'tripzzy-admin-bar-menus-all-forms',
							'group' => null,
							'title' => '<span class="ab-icon" aria-hidden="true"></span>' . __( 'All Forms', 'tripzzy' ),
							'href'  => admin_url( 'edit.php?post_type=tripzzy_form' ),
							'meta'  => array(
								'title' => __( 'All Forms', 'tripzzy' ),
							),
						),
						array(
							'id'    => 'tripzzy-admin-bar-menus-all-filters',
							'group' => null,
							'title' => '<span class="ab-icon" aria-hidden="true"></span>' . __( 'All Filters', 'tripzzy' ),
							'href'  => admin_url( 'edit.php?post_type=tripzzy_booking&page=tripzzy-custom-categories' ),
							'meta'  => array(
								'title' => __( 'All Filters', 'tripzzy' ),
							),
						),
						array(
							'id'    => 'tripzzy-admin-bar-menus-settings',
							'group' => null,
							'title' => '<span class="ab-icon" aria-hidden="true"></span>' . __( 'Settings', 'tripzzy' ),
							'href'  => admin_url( 'edit.php?post_type=tripzzy_booking&page=tripzzy-settings' ),
							'meta'  => array(
								'title' => __( 'Settings', 'tripzzy' ),
							),
						),
					),
				),
			);
		}
		/**
		 * Sub Menu filter hook to modify custom submenus.
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'tripzzy_filter_admin_bar_menus', $menus );
	}
}
