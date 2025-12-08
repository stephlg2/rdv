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

if ( ! class_exists( 'Tripzzy\Core\PostTypes\EmailTrackbackPostType' ) ) {
	/**
	 * Tripzzy Post Type Class.
	 *
	 * @since 1.0.0
	 */
	class EmailTrackbackPostType extends PostTypeBase {
		/**
		 * Post Type Key to register post type.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $post_type = 'tz_email_trackback';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'tripzzy_filter_post_type_args', array( $this, 'init_args' ) );
		}

		/**
		 * Post Type arguments.
		 *
		 * @since 1.0.0
		 */
		protected static function post_type_args() {
			$labels = array(
				'add_new'            => _x( 'New Email Trackback', 'tripzzy', 'tripzzy' ),
				'add_new_item'       => __( 'Add New Email Trackback', 'tripzzy' ),
				'all_items'          => __( 'Email Trackback', 'tripzzy' ),
				'edit_item'          => __( 'Edit Email Trackback', 'tripzzy' ),
				'menu_name'          => _x( 'Email Trackback', 'admin menu', 'tripzzy' ),
				'name'               => _x( 'Email Trackback', 'post type general name', 'tripzzy' ),
				'name_admin_bar'     => _x( 'Email Trackback', 'add new on admin bar', 'tripzzy' ),
				'new_item'           => __( 'New Email Trackback', 'tripzzy' ),
				'not_found'          => __( 'No Email Trackback found.', 'tripzzy' ),
				'not_found_in_trash' => __( 'No Email Trackback found in Trash.', 'tripzzy' ),
				'parent_item_colon'  => __( 'Parent Email Trackback:', 'tripzzy' ),
				'search_items'       => __( 'Search Email Trackback', 'tripzzy' ),
				'singular_name'      => _x( 'Email Trackback', 'post type singular name', 'tripzzy' ),
				'view_item'          => __( 'View Email Trackback', 'tripzzy' ),
			);

			$args = array(
				'labels'             => $labels,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => false,
				'show_in_menu'       => 'edit.php?post_type=tripzzy_booking',
				'query_var'          => true,
				'rewrite'            => array(),
				'capability_type'    => 'post',
				'capabilities'       => array(
					'create_posts' => 'do_not_allow', // Removes support for the "Add New" function, including Super Admin's.
				),
				'map_meta_cap'       => true, // Set to false, if users are not allowed to edit/delete existing posts.
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => 30,
				'supports'           => array( 'title', 'editor' ),
				'menu_icon'          => 'dashicons-bank',
				'show_in_rest'       => false,
				'priority'           => 70,
			);
			return $args;
		}
	}
}
