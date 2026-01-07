<?php
/**
 * Payment Post type.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\PostTypes;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\PostTypeBase;

if ( ! class_exists( 'Tripzzy\Core\PostTypes\PaymentPostType' ) ) {
	/**
	 * Payment Post Type Class.
	 *
	 * @since 1.0.0
	 */
	class PaymentPostType extends PostTypeBase {
		/**
		 * Post Type Key to register post type.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $post_type = 'tripzzy_payment';

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
				'add_new'            => _x( 'New Payment', 'tripzzy', 'tripzzy' ),
				'add_new_item'       => __( 'Add New Payment', 'tripzzy' ),
				'all_items'          => __( 'Payment', 'tripzzy' ),
				'edit_item'          => __( 'Edit Payment', 'tripzzy' ),
				'menu_name'          => _x( 'Payment', 'admin menu', 'tripzzy' ),
				'name'               => _x( 'Payment', 'post type general name', 'tripzzy' ),
				'name_admin_bar'     => _x( 'Payment', 'add new on admin bar', 'tripzzy' ),
				'new_item'           => __( 'New Payment', 'tripzzy' ),
				'not_found'          => __( 'No Payment found.', 'tripzzy' ),
				'not_found_in_trash' => __( 'No Payment found in Trash.', 'tripzzy' ),
				'parent_item_colon'  => __( 'Parent Payment:', 'tripzzy' ),
				'search_items'       => __( 'Search Payment', 'tripzzy' ),
				'singular_name'      => _x( 'Payment', 'post type singular name', 'tripzzy' ),
				'view_item'          => __( 'View Payment', 'tripzzy' ),
			);

			$args = array(
				'labels'             => $labels,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => false,
				'show_in_menu'       => 'edit.php?post_type=tripzzy_payment',
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
				'priority'           => 60,
			);
			return $args;
		}
	}
}
