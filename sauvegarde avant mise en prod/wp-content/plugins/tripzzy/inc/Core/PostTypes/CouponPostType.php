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
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Helpers\Coupon;
use Tripzzy\Core\Helpers\Amount;

if ( ! class_exists( 'Tripzzy\Core\PostTypes\CouponPostType' ) ) {
	/**
	 * Tripzzy Post Type Class.
	 *
	 * @since 1.0.0
	 */
	class CouponPostType extends PostTypeBase {
		/**
		 * Post Type Key to register post type.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $post_type = 'tripzzy_coupon';

		/**
		 * Constructor.
		 */
		public function __construct() {
			$post_type = self::$post_type;

			add_filter( 'tripzzy_filter_post_type_args', array( $this, 'init_args' ) );
			add_filter( 'tripzzy_filter_meta_box_args', array( $this, 'init_meta_box_args' ), 10, 2 );

			// Additional Hooks.
			add_action( 'do_meta_boxes', array( $this, 'remove_metaboxes' ) );
			// Layout Column.
			add_filter( "get_user_option_screen_layout_{$post_type}", array( $this, 'layout_column' ), 10, 3 );

			// Always open Forms Metabox.
			$screen_id = $post_type;
			$box_id    = sprintf( '%s__coupons', $screen_id );
			add_filter( "postbox_classes_{$screen_id}_{$box_id}", '__return_empty_array' );

			/*
			* Filter Hook : Admin Column - Headings.
			*/
			add_filter( 'manage_edit-' . $post_type . '_columns', array( $this, 'admin_column_headings' ) );

			/*
			* Action Hook : Admin Column - Content.
			*/
			add_action( 'manage_' . $post_type . '_posts_custom_column', array( $this, 'admin_column_contents' ), 10, 2 );
		}

		/**
		 * Post Type arguments.
		 *
		 * @since 1.0.0
		 */
		protected static function post_type_args() {
			$labels = array(
				'add_new'            => _x( 'New Coupon', 'tripzzy', 'tripzzy' ),
				'add_new_item'       => __( 'Add New Coupon', 'tripzzy' ),
				'all_items'          => __( 'Coupons', 'tripzzy' ),
				'edit_item'          => __( 'Edit Coupon', 'tripzzy' ),
				'menu_name'          => _x( 'Coupons', 'admin menu', 'tripzzy' ),
				'name'               => _x( 'Coupons', 'post type general name', 'tripzzy' ),
				'name_admin_bar'     => _x( 'Coupon', 'add new on admin bar', 'tripzzy' ),
				'new_item'           => __( 'New Coupon', 'tripzzy' ),
				'not_found'          => __( 'No Coupons found.', 'tripzzy' ),
				'not_found_in_trash' => __( 'No Coupons found in Trash.', 'tripzzy' ),
				'parent_item_colon'  => __( 'Parent Coupons:', 'tripzzy' ),
				'search_items'       => __( 'Search Coupons', 'tripzzy' ),
				'singular_name'      => _x( 'Coupon', 'post type singular name', 'tripzzy' ),
				'view_item'          => __( 'View Coupon', 'tripzzy' ),
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
					'slug'       => 'tripzzy-coupons',
					'with_front' => true,
				),
				'capability_type'    => 'post',
				'has_archive'        => false,
				'hierarchical'       => false,
				'menu_position'      => 30,
				'supports'           => array( 'title' ),
				'menu_icon'          => 'dashicons-bank',
				'show_in_rest'       => true,
				'rest_base'          => 'coupons',
				'priority'           => 90,
			);
			return $args;
		}

		/**
		 * Meta Box arguments.
		 * Required Method to register Metabox if filter `tripzzy_filter_meta_box_args` is used.
		 *
		 * @param int $coupon_id Coupon id.
		 * @since 1.0.0
		 */
		protected static function meta_box_args( $coupon_id ) {
			if ( get_post_type( $coupon_id ) !== self::$post_type ) {
				return array();
			}
			$args = array(
				'coupons' => array(  // Meta Box ID.
					'title'    => __( 'Coupons', 'tripzzy' ), // Required.
					'callback' => array( 'Tripzzy\Admin\Views\CouponView', 'render' ),
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
		 * Metabox layout default set to 1 in edit page for this post type.
		 *
		 * @since 1.0.0
		 */
		public function layout_column() {
			// Force set one col layout in form fields.
			return 1;
		}

		/**
		 * Admin Column Heading
		 *
		 * @param array $columns List of column heading.
		 * @return array
		 */
		public function admin_column_headings( $columns ) {
			unset( $columns['date'] );
			unset( $columns['comments'] );
			$columns['title']         = __( 'Coupon code', 'tripzzy' );
			$columns['coupon_value']  = __( 'Coupon value', 'tripzzy' );
			$columns['uses']          = __( 'Uses', 'tripzzy' );
			$columns['coupon_expiry'] = __( 'Expiry date', 'tripzzy' );
			$columns['coupon_status'] = __( 'Coupon status', 'tripzzy' );

			return $columns;
		}

		/**
		 * Admin Column Heading
		 *
		 * @param string $column_name Name of the column.
		 * @param string $coupon_id Coupon id.
		 * @since 1.0.0
		 * @since 1.2.3 Replaced Carbon date with wp_date.
		 * @return void
		 */
		public function admin_column_contents( $column_name, $coupon_id ) {

			$coupon = new Coupon( $coupon_id );

			$coupon_type   = $coupon::get_coupon_type();
			$coupon_value  = $coupon::get_coupon_value();
			$coupon_expiry = $coupon::get_coupon_expiry();
			$coupon_status = $coupon::get_coupon_status();

			switch ( $column_name ) {
				case 'coupon_value':
					?>
					<strong>
						<?php
						if ( 'percentage' === $coupon_type ) {
							echo esc_html( $coupon_value );
							?>
							%
							<?php
						} else {
							Amount::display( $coupon_value, true );
						}
						?>
					</strong>
						<?php
					break;
				case 'uses':
					$limit = $coupon::get_coupon_limit() ? $coupon::get_coupon_limit() : __( 'Unlimited', 'tripzzy' );
					printf( '%s / %s', esc_html( $coupon::get_coupon_uses() ), esc_html( $limit ) );

					break;
				case 'coupon_expiry':
					$date_format = get_option( 'date_format' );
					if ( ! empty( $coupon_expiry ) ) {
						echo esc_html( wp_date( $date_format, strtotime( $coupon_expiry ) ) );
					}
					break;
				case 'coupon_status':
					echo esc_html( $coupon_status );
					break;
				default:
					break;
			} // end switch
		}
	}
}
