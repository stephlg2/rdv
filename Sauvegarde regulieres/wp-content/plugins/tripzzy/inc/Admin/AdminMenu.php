<?php
/**
 * Tripzzy Admin Menu.
 *
 * @package tripzzy
 */

namespace Tripzzy\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Traits\AdminMenuTrait;

if ( ! class_exists( 'Tripzzy\Admin\AdminMenu' ) ) {
	/**
	 * Tripzzy Admin Menu Class.
	 *
	 * @since 1.0.0
	 */
	class AdminMenu {

		use SingletonTrait;
		use AdminMenuTrait;

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'init' ) );
			add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu_init' ), 100 );
		}
		/**
		 * Update the menu position, remove as per the arguments.
		 *
		 * @since 1.0.0
		 */
		public function init() {
			global $submenu;
			unset( $submenu['edit.php?post_type=tripzzy_booking'][10] ); // Removes 'Add New'.
			$all_submenus = self::get_submenus(); // Method from trait.

			foreach ( $all_submenus as $parent_slug => $sub_menus ) {
				foreach ( $sub_menus as $sub_menu ) {
					if ( ! isset( $sub_menu['page_title'] ) || ! isset( $sub_menu['menu_title'] ) || ! isset( $sub_menu['menu_slug'] ) || ! isset( $sub_menu['callback'] ) ) {
						continue;
					}
					$capability = isset( $sub_menu['capability'] ) ? $sub_menu['capability'] : 'manage_options';
					add_submenu_page( $parent_slug, $sub_menu['page_title'], $sub_menu['menu_title'], $capability, $sub_menu['menu_slug'], $sub_menu['callback'], $sub_menu['position'] ?? 10 );
				}
			}
		}

		/**
		 * Add Tripzzy menu in the admin bar.
		 *
		 * @param object \WP_Admin_Bar $admin_bar Instance of WP_Admin_Bar class.
		 * @since 1.0.0
		 */
		public function admin_bar_menu_init( \WP_Admin_Bar $admin_bar ) {
			$show_admin_bar_menu = current_user_can( 'manage_options' );
			$show_admin_bar_menu = apply_filters( 'tripzzy_filter_show_admin_bar_menu', $show_admin_bar_menu, $admin_bar );
			if ( ! $show_admin_bar_menu ) {
				return;
			}
			$admin_bar_menus = self::get_admin_bar_menus();
			foreach ( $admin_bar_menus as  $admin_bar_menu ) {
				$parent_menu_id = $admin_bar_menu['id'];
				$admin_bar->add_menu( $admin_bar_menu );
				if ( isset( $admin_bar_menu['child'] ) && ! empty( $admin_bar_menu['child'] ) ) {
					foreach ( $admin_bar_menu['child'] as $child_menu ) {
						$child_menu['parent'] = $parent_menu_id;
						$admin_bar->add_menu( $child_menu );
					}
				}
			}
		}
	}
}
