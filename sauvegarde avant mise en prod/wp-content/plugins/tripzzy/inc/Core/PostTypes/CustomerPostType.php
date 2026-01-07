<?php
/**
 * Customer Post type.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\PostTypes;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\PostTypeBase;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\Customer;

use Tripzzy\Core\Forms\CheckoutForm;
use Tripzzy\Core\Forms\Form;
use Tripzzy\Core\Bookings;

if ( ! class_exists( 'Tripzzy\Core\PostTypes\CustomerPostType' ) ) {
	/**
	 * Customer Post Type Class.
	 *
	 * @since 1.0.0
	 */
	class CustomerPostType extends PostTypeBase {
		/**
		 * Post Type Key to register post type.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $post_type = 'tripzzy_customer';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'tripzzy_filter_post_type_args', array( $this, 'init_args' ) );
			add_filter( 'tripzzy_filter_meta_box_args', array( $this, 'init_meta_box_args' ), 10, 2 );

			/*
			* Filter Hook : Admin Column - Headings.
			*/
			add_filter( 'manage_edit-' . self::$post_type . '_columns', array( $this, 'admin_column_headings' ) );

			/*
			* Action Hook : Admin Column - Content.
			*/
			add_action( 'manage_' . self::$post_type . '_posts_custom_column', array( $this, 'admin_column_contents' ), 10, 2 );
		}

		/**
		 * Post Type arguments.
		 *
		 * @since 1.0.0
		 */
		protected static function post_type_args() {
			$labels = array(
				'add_new'            => _x( 'New Customer', 'tripzzy', 'tripzzy' ),
				'add_new_item'       => __( 'Add New Customer', 'tripzzy' ),
				'all_items'          => __( 'Customers', 'tripzzy' ),
				'edit_item'          => __( 'Edit Customer', 'tripzzy' ),
				'menu_name'          => _x( 'Customers', 'admin menu', 'tripzzy' ),
				'name'               => _x( 'Customers', 'post type general name', 'tripzzy' ),
				'name_admin_bar'     => _x( 'Customer', 'add new on admin bar', 'tripzzy' ),
				'new_item'           => __( 'New Customer', 'tripzzy' ),
				'not_found'          => __( 'No Customers found.', 'tripzzy' ),
				'not_found_in_trash' => __( 'No Customers found in Trash.', 'tripzzy' ),
				'parent_item_colon'  => __( 'Parent Customers:', 'tripzzy' ),
				'search_items'       => __( 'Search Customers', 'tripzzy' ),
				'singular_name'      => _x( 'Customer', 'post type singular name', 'tripzzy' ),
				'view_item'          => __( 'View Customer', 'tripzzy' ),
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
					'slug'       => 'tripzzy-customer',
					'with_front' => true,
				),
				'capability_type'    => 'post',
				'capabilities'       => array(
					'create_posts' => false,
				),
				'map_meta_cap'       => true,
				'has_archive'        => false,
				'hierarchical'       => false,
				'menu_position'      => 20,
				'supports'           => array( 'title' ),
				'menu_icon'          => 'dashicons-bank',
				'show_in_rest'       => true,
				'rest_base'          => 'customer',
				'priority'           => 50,
			);
			return $args;
		}

		/**
		 * Meta Box arguments.
		 * Required Method to register Metabox if filter `tripzzy_filter_meta_box_args` is used.
		 *
		 * @param int $customer_id C IDustomer.
		 * @since 1.0.0
		 */
		protected static function meta_box_args( $customer_id ) {

			if ( get_post_type( $customer_id ) !== self::$post_type ) {
				return array();
			}

			$args = array(

				'customer_details' => array(  // Meta Box ID.
					'title'   => __( 'Customer Details', 'tripzzy' ), // Required.
					'markups' => Customer::render( $customer_id, true ),
				),

				'booking_history'  => array(  // Meta Box ID.
					'title'    => __( 'Booking History', 'tripzzy' ), // Required.
					'callback' => array( 'Tripzzy\Core\PostTypes\CustomerPostType', 'render_customer_booking_history' ),
				),
			);
			return $args;
		}

		/**
		 * Render booking history.
		 *
		 * @param object $post Customer object.
		 * @return void
		 */
		public static function render_customer_booking_history( $post ) {
			global $post;
			$customer_id = $post->ID; ?>
			<div class="inside-content">
				<?php Customer::render_booking_history( $customer_id ); ?>
			</div>
			<?php
		}

		/**
		 * Get meta fields,
		 *
		 * @param int $customer_id Customer id.
		 * @return array
		 */
		public static function get_customer_metafield_fields( $customer_id ) {

			if ( get_post_type( $customer_id ) !== self::$post_type ) {
				return array();
			}

			$customer_data  = MetaHelpers::get_post_meta( $customer_id, 'customer_data' ); // excluding email.
			$customer_email = MetaHelpers::get_post_meta( $customer_id, 'customer_email' );
			$values         = wp_parse_args( $customer_data, $customer_email );

			if ( ! $values || ! is_array( $values ) ) {
				$values = array();
			}

			$fields = CheckoutForm::get_fields(); // Fields without values.

			foreach ( $fields as $field_index => $field ) {

				if ( 'repeator' === $field['type'] ) {
					$repeator_fields        = $field['children'];
					$repeator_values        = isset( $values[ $field['name'] ] ) ? $values[ $field['name'] ] : array();
					$child_with_val         = Form::repeator_field_values( $repeator_fields, $repeator_values );
					$field['children']      = $child_with_val;
					$fields[ $field_index ] = $field;
				} elseif ( 'wrapper' === $field['type'] ) {
					$repeator_fields        = $field['children'];
					$child_with_val         = Form::wrapper_field_values( $repeator_fields, $values );
					$field['children']      = $child_with_val;
					$fields[ $field_index ] = $field;
				} else {
					$fallback_value = isset( $field['value'] ) ? $field['value'] : '';
					$value          = isset( $values[ $field['name'] ] ) ? $values[ $field['name'] ] : $fallback_value;

					$field['value']         = $value;
					$fields[ $field_index ] = $field;
				}
			}

			return $fields;
		}

		/**
		 * Admin Column Heading
		 *
		 * @param array $columns List of column heading.
		 * @return array
		 */
		public function admin_column_headings( $columns ) {
			unset( $columns['title'], $columns['date'], $columns['comments'] );
			$columns['title']          = __( 'Customer Name', 'tripzzy' );
			$columns['customer_id']    = __( 'Customer ID', 'tripzzy' );
			$columns['customer_email'] = __( 'Email', 'tripzzy' );
			$columns['all_bookings']   = __( 'All Bookings', 'tripzzy' );
			$columns['paid_bookings']  = __( 'Paid Bookings', 'tripzzy' );
			$columns['total_spent']    = __( 'Total Spent', 'tripzzy' );
			$columns['last_active']    = __( 'Last Active', 'tripzzy' );

			return $columns;
		}

		/**
		 * Admin Column Heading
		 *
		 * @param string $column_name Name of the column.
		 * @param string $customer_id Customer id.
		 * @return void
		 */
		public function admin_column_contents( $column_name, $customer_id ) {
			$settings = Settings::get();

			$customer_bookings = Customer::get_bookings( $customer_id );
			switch ( $column_name ) {
				case 'customer_id':
					echo esc_html( $customer_id );
					break;
				case 'customer_email':
					echo esc_html( MetaHelpers::get_post_meta( $customer_id, 'customer_email' ) );
					break;
				case 'all_bookings':
					echo esc_html( $customer_bookings['all_bookings'] ?? 0 );
					break;
				case 'paid_bookings':
					echo esc_html( $customer_bookings['paid_bookings'] ?? 0 );
					break;
				case 'total_spent':
					$total_spent = $customer_bookings['total_spent'] ?? array();
					if ( ! empty( $total_spent ) ) {
						foreach ( $total_spent as $currency => $amounts ) {
							?>
							<span><?php printf( '%s %s', esc_html( $currency ), esc_html( array_sum( $amounts ) ) ); ?></span>
							<?php
						}
					} else {
						printf( '%s 0', esc_html( $settings['currency'] ?? 'USD' ) );
					}
					break;
				case 'last_active':
					echo esc_html( $customer_bookings['last_active'] ?? '' );
					break;
				default:
					break;
			} // end switch
		}
	}
}
