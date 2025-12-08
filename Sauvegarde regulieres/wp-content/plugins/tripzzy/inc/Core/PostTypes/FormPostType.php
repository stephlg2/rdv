<?php
/**
 * Tripzzy Post type.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\PostTypes;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\PostTypeBase;
use Tripzzy\Core\Forms\CheckoutForm;
use Tripzzy\Core\Http\Request;

if ( ! class_exists( 'Tripzzy\Core\PostTypes\FormPostType' ) ) {
	/**
	 * Tripzzy Post Type Class.
	 *
	 * @since 1.0.0
	 */
	class FormPostType extends PostTypeBase {
		/**
		 * Post Type Key to register post type.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $post_type = 'tripzzy_form';

		/**
		 * Constructor.
		 */
		public function __construct() {
			$post_type = self::$post_type;
			add_filter( 'tripzzy_filter_post_type_args', array( $this, 'init_args' ) );
			add_filter( 'tripzzy_filter_meta_box_args', array( $this, 'init_meta_box_args' ), 10, 2 );

			// Additional Hooks.
			add_action( 'do_meta_boxes', array( $this, 'remove_metaboxes' ) );
			// Hide edit, quick edit, delete, view option.
			add_filter( 'post_row_actions', array( $this, 'remove_row_actions' ) );
			// Layout Column.
			add_filter( "get_user_option_screen_layout_{$post_type}", array( $this, 'layout_column' ), 10, 3 );
			// Hide screen option.
			add_filter( 'screen_options_show_screen', array( $this, 'show_screen_option' ) );
			// Hide Bulk Action dropdown.
			add_filter( "bulk_actions-edit-{$post_type}", '__return_empty_array' );
			// Hide data dropdown.
			add_filter( 'months_dropdown_results', '__return_empty_array' );

			// Always open Forms Metabox.
			$screen_id = $post_type;
			$box_id    = sprintf( '%s__form_fields', $screen_id );
			add_filter( "postbox_classes_{$screen_id}_{$box_id}", '__return_empty_array' );
			// Remove view (All | Trash | Published | Pending ).
			add_filter( "views_edit-{$screen_id}", array( $this, 'hide_view_edit' ) );
			// Hide Add New From admin bar.
			add_action( 'admin_bar_menu', array( $this, 'hide_new_from_admin_bar' ), 999 );
		}

		/**
		 * Metabox layout default set to 1 in edit page for this post type.
		 *
		 * @since 1.0.0
		 */
		public function layout_column() {
			// Force set one col layout in form fields.
			return 1;
		}

		/**
		 * Hide screen option for this post type.
		 *
		 * @since 1.0.0
		 * @param bool $show either true or false.
		 */
		public function show_screen_option( $show ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}
			if ( ! get_current_screen() ) {
				return false;
			}
			$screen    = get_current_screen();
			$post_type = $screen->post_type;

			if ( ! $post_type ) {
				return;
			}
			if ( $post_type === self::$post_type ) {
				return false;
			}
			return $show;
		}

		/**
		 * Post Type arguments.
		 * Required Method to register Metabox if filter `tripzzy_filter_post_type_args` is used.
		 *
		 * @since 1.0.0
		 */
		protected static function post_type_args() {
			$labels = array(
				'add_new'            => _x( 'New Form', 'tripzzy', 'tripzzy' ),
				'add_new_item'       => __( 'Add New Form', 'tripzzy' ),
				'all_items'          => __( 'Forms', 'tripzzy' ),
				'edit_item'          => __( 'Edit Form', 'tripzzy' ),
				'menu_name'          => _x( 'Forms', 'admin menu', 'tripzzy' ),
				'name'               => _x( 'Forms', 'post type general name', 'tripzzy' ),
				'name_admin_bar'     => _x( 'Tripzzy Form', 'add new on admin bar', 'tripzzy' ),
				'new_item'           => __( 'New Form', 'tripzzy' ),
				'not_found'          => __( 'No Forms found.', 'tripzzy' ),
				'not_found_in_trash' => __( 'No Forms found in Trash.', 'tripzzy' ),
				'parent_item_colon'  => __( 'Parent Forms:', 'tripzzy' ),
				'search_items'       => __( 'Search Forms', 'tripzzy' ),
				'singular_name'      => _x( 'Form', 'post type singular name', 'tripzzy' ),
				'view_item'          => __( 'View Form', 'tripzzy' ),
			);

			$args = array(
				'labels'             => $labels,
				'description'        => __( 'Description.', 'tripzzy' ),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => 'edit.php?post_type=tripzzy_booking',
				'query_var'          => true,
				'rewrite'            => array(
					'slug'       => 'tripzzy-form',
					'with_front' => true,
				),
				'capability_type'    => 'post',
				'capabilities'       => array(
					'create_posts' => 'do_not_allow', // Removes support for the "Add New" function, including Super Admin's.
				),
				'map_meta_cap'       => true, // Set to false, if users are not allowed to edit/delete existing posts.
				'has_archive'        => false,
				'hierarchical'       => false,
				'menu_position'      => 30,
				'supports'           => array( 'title' ),
				'menu_icon'          => 'dashicons-bank',
				'show_in_rest'       => true,
				'rest_base'          => 'form-fields',
				'priority'           => 110,
			);
			return $args;
		}

		/**
		 * Meta Box arguments.
		 * Required Method to register Metabox if filter `tripzzy_filter_meta_box_args` is used.
		 *
		 * @param int $form_id Form id.
		 * @since 1.0.0
		 */
		protected static function meta_box_args( $form_id ) {
			if ( get_post_type( $form_id ) !== self::$post_type ) {
				return array();
			}
			$args = array(
				'form_fields' => array(  // Meta Box ID.
					'title'    => __( 'Form Fields', 'tripzzy' ), // Required.
					'callback' => array( 'Tripzzy\Admin\Views\FormFieldsView', 'render' ),
				),
			);
			return $args;
		}

		/**
		 * Add/remove remove metaboxes.
		 *
		 * @since 1.0.0
		 */
		public function remove_metaboxes() {
			// Remove Default.
			remove_meta_box( 'submitdiv', self::$post_type, 'side' );
			remove_meta_box( 'commentsdiv', self::$post_type, 'normal' );
			remove_meta_box( 'commentstatusdiv', self::$post_type, 'normal' );
		}

		/**
		 * Remove Row action.
		 *
		 * @param array $actions List of actions of post in post list page.
		 * @since 1.0.0
		 * @return array
		 */
		public function remove_row_actions( $actions ) {
			if ( get_post_type() === self::$post_type ) {
				unset( $actions['edit'] );
				unset( $actions['view'] );
				unset( $actions['inline hide-if-no-js'] );
			}
			return $actions;
		}

		/**
		 * Hide View | Edit from list.
		 *
		 * @param array $views list of displayed items.
		 */
		public function hide_view_edit( $views ) {
			$args         = array(
				'post_type'   => self::$post_type,
				'post_status' => array( 'trash' ),
			);
			$query        = new \WP_Query( $args );
			$trashed_post = count( $query->posts );
			if ( $trashed_post > 0 ) {

				return $views;
			} else {
				$post_status = isset( $_GET['post_status'] ) ? sanitize_text_field( wp_unslash( $_GET['post_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( 'trash' === $post_status ) {
					wp_safe_redirect( 'edit.php?post_type=tripzzy_form' );
				}
			}

			return array();
		}

		/**
		 * Hide View | Edit from list.
		 *
		 * @param array $wp_admin_bar list of menu items.
		 */
		public function hide_new_from_admin_bar( $wp_admin_bar ) {
			$wp_admin_bar->remove_node( 'new-tripzzy_form' );
		}
	}
}
