<?php
/**
 * Enquiry Post type.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\PostTypes;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\PostTypeBase;
use Tripzzy\Core\Forms\EnquiryForm;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\Page;
use Tripzzy\Core\Helpers\DropdownOptions;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Http\Request;


if ( ! class_exists( 'Tripzzy\Core\PostTypes\EnquiryPostType' ) ) {
	/**
	 * Enquiry Post Type Class.
	 *
	 * @since 1.0.0
	 */
	class EnquiryPostType extends PostTypeBase {
		/**
		 * Post Type Key to register post type.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $post_type = 'tripzzy_enquiry';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'tripzzy_filter_post_type_args', array( $this, 'init_args' ) );
			add_filter( 'tripzzy_filter_meta_box_args', array( $this, 'init_meta_box_args' ), 10, 2 );
			add_action( 'tripzzy_' . self::$post_type . '_save_post', array( __CLASS__, 'save_meta' ) );

			/*
			* Filter Hook : Admin Column - Headings.
			*/
			add_filter( 'manage_edit-' . self::$post_type . '_columns', array( $this, 'admin_column_headings' ) );

			/*
			* Action Hook : Admin Column - Content.
			*/
			add_action( 'manage_' . self::$post_type . '_posts_custom_column', array( $this, 'admin_column_contents' ), 10, 2 );

			add_filter( 'display_post_states', array( $this, 'display_post_states' ), 10, 2 );
			add_filter( 'load-post.php', array( $this, 'mark_as_read' ) );
		}

		/**
		 * Post Type arguments.
		 *
		 * @since 1.0.0
		 */
		protected static function post_type_args() {
			$labels = array(
				'add_new'            => _x( 'New Enquiry', 'tripzzy', 'tripzzy' ),
				'add_new_item'       => __( 'Add New Enquiry', 'tripzzy' ),
				'all_items'          => __( 'Enquiries', 'tripzzy' ),
				'edit_item'          => __( 'Edit Enquiry', 'tripzzy' ),
				'menu_name'          => _x( 'Enquiries', 'admin menu', 'tripzzy' ),
				'name'               => _x( 'Enquiries', 'post type general name', 'tripzzy' ),
				'name_admin_bar'     => _x( 'Enquiry', 'add new on admin bar', 'tripzzy' ),
				'new_item'           => __( 'New Enquiry', 'tripzzy' ),
				'not_found'          => __( 'No Enquiries found.', 'tripzzy' ),
				'not_found_in_trash' => __( 'No Enquiries found in Trash.', 'tripzzy' ),
				'parent_item_colon'  => __( 'Parent Enquiries:', 'tripzzy' ),
				'search_items'       => __( 'Search Enquiries', 'tripzzy' ),
				'singular_name'      => _x( 'Enquiry', 'post type singular name', 'tripzzy' ),
				'view_item'          => __( 'View Enquiry', 'tripzzy' ),
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
					'slug'       => 'tripzzy-enquiries',
					'with_front' => true,
				),
				'capability_type'    => 'post',
				'has_archive'        => false,
				'hierarchical'       => false,
				'menu_position'      => 20,
				'supports'           => array( 'title' ),
				'menu_icon'          => 'dashicons-bank',
				'show_in_rest'       => true,
				'rest_base'          => 'enquiry',
				'priority'           => 70,
			);
			return $args;
		}
		/**
		 * Meta Box arguments.
		 * Required Method to register Metabox if filter `tripzzy_filter_meta_box_args` is used.
		 *
		 * @param int $enquiry_id Enquiry ID.
		 * @since 1.0.0
		 * @since 1.1.7 Fixed trip dropdown issue in enquiry edit page if form is saved.
		 * @since 1.1.9 Fixed showing input type dropdown for all inputs.
		 * @since 1.2.2 Fetched fields using get_fields_data method.
		 *
		 * @return array
		 */
		protected static function meta_box_args( $enquiry_id ) {
			if ( get_post_type( $enquiry_id ) !== self::$post_type ) {
				return;
			}
			$args = array(
				'trip_enquiries' => array(  // Meta Box ID.
					'title'  => __( 'Trip Enquiry', 'tripzzy' ), // Required.
					'fields' => self::get_fields_data( $enquiry_id ),
				),
			);
			return $args;
		}

		/**
		 * Get Enquiry fields with saved data.
		 *
		 * @param int $enquiry_id Enquiry ID.
		 *
		 * @since 1.2.2
		 * @return array
		 */
		public static function get_fields_data( $enquiry_id ) {
			$values = MetaHelpers::get_post_meta( $enquiry_id, 'enquiry' );

			if ( ! $values || ! is_array( $values ) ) {
				$values = array();
			}
			if ( get_post_type( $enquiry_id ) !== self::$post_type ) {
				return;
			}

			$fields = EnquiryForm::get_fields(); // Fields without values. @todo need to add get_fields_with_value in base.

			foreach ( $fields as $field_index => $field ) {
				if ( ! is_array( $field ) ) {
					continue;
				}

				$field_name = $field['name'];
				if ( 'repeator' === $field['type'] ) {
					$repeator_fields        = $field['children'];
					$repeator_values        = isset( $values[ $field_name ] ) ? $values[ $field_name ] : array();
					$child_with_val         = self::repeator_field_values( $repeator_fields, $repeator_values );
					$field['children']      = $child_with_val;
					$fields[ $field_index ] = $field;
				} else {
					$fallback_value = isset( $field['value'] ) ? $field['value'] : '';
					$value          = isset( $values[ $field_name ] ) ? $values[ $field_name ] : $fallback_value;

					// For Range additional values.
					if ( 'range' === $field['type'] ) {
						$fallback_value  = isset( $field['attributes']['value'] ) ? $field['attributes']['value'] : '';
						$fallback_value1 = isset( $field['attributes']['value1'] ) ? $field['attributes']['value1'] : '';
						$fallback_value2 = isset( $field['attributes']['value2'] ) ? $field['attributes']['value2'] : '';
						$fallback_values = array( $fallback_value1, $fallback_value2 );
						$range_values    = isset( $values[ $field_name ] ) ? $values[ $field_name ] : $fallback_values;

						foreach ( $range_values as $k => $range_value ) {
							if ( 0 == $k ) { // @phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
								$field['attributes']['value'] = $range_value; // for single range.
							}
							$field['attributes'][ 'value' . ( $k + 1 ) ] = $range_value;
						}
					}

					$field['value']         = $value;
					$fields[ $field_index ] = $field;
				}

				if ( Page::is( 'enquiry', true ) && 'trip_id' === $field_name ) { // if admin enquiry page.
					$field['type']          = 'dropdown';
					$field['options']       = DropdownOptions::get()['trips'];
					$fields[ $field_index ] = $field;
				}
			}
			return $fields;
		}

		/**
		 * Add Field values to repeator fields.
		 *
		 * @param array $fields Repeator Fields.
		 * @param array $values Repeator Values.
		 */
		public static function repeator_field_values( $fields, $values ) {
			foreach ( $fields as $field_index => $field ) {
				if ( 'repeator' === $field['type'] ) {
					$repeator_fields        = $field['children'];
					$repeator_values        = isset( $values[ $field['name'] ] ) ? $values[ $field['name'] ] : array();
					$child_with_val         = self::repeator_field_values( $repeator_fields, $repeator_values );
					$field['children']      = $child_with_val;
					$fields[ $field_index ] = $field;
					return $fields;
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
		 * Save post meta for enquiry data.
		 *
		 * @param int $enquiry_id Enquiry ID.
		 * @since 1.2.9 Return if post type not equal to tripzzy_enquiry.
		 * @since 1.0.0
		 */
		public static function save_meta( $enquiry_id ) {
			if ( ! Nonce::verify() ) {
				return;
			}
			$post_type = get_post_type( $enquiry_id );
			if ( self::$post_type !== $post_type ) {
				return;
			}
			$values = MetaHelpers::get_post_meta( $enquiry_id, 'enquiry' );
			if ( ! $values || ! is_array( $values ) ) {
				$values = array();
			}
			$fields = EnquiryForm::get_fields();
			foreach ( $fields as $field ) {
				$name = $field['name'];
				if ( isset( $_POST[ $name ] ) ) { // @codingStandardsIgnoreLine
					$values[ $name ] = Request::sanitize_data( $_POST[ $name ] ?? '' ); // @codingStandardsIgnoreLine
				}
			}
			MetaHelpers::update_post_meta( $enquiry_id, 'enquiry', $values );
		}

		/**
		 * Display Post state form trip enquiry.
		 *
		 * @param array  $states List of stated.
		 * @param object $post Post object.
		 * @return array
		 */
		public function display_post_states( $states, $post ) {
			if ( Page::is( 'enquiry', true ) ) {
				$status = $post->post_status;
				$status = 'pending' === $status ? __( 'Unread', 'tripzzy' ) : __( 'Read', 'tripzzy' );
				$states = array( $status );
			}
			return $states;
		}

		/**
		 * Make enquiry Post mark as read on post load from admin.
		 *
		 * @return void
		 */
		public function mark_as_read() {
			if ( ! is_admin() ) {
				return;
			}
			$enquiry_id = absint( $_GET['post'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( get_post_type( $enquiry_id ) !== self::$post_type ) {
				return;
			}
					$post_status = get_post_status( $enquiry_id );
			if ( 'pending' === $post_status ) {
				wp_update_post(
					array(
						'ID'          => $enquiry_id,
						'post_status' => 'publish',
					)
				);
			}
		}

		/**
		 * Admin Column Heading
		 *
		 * @param array $columns List of column heading.
		 *
		 * @since 1.2.4
		 * @return array
		 */
		public function admin_column_headings( $columns ) {
			$_date = $columns['date'];
			unset( $columns['date'] );
			unset( $columns['comments'] );
			$columns['trip_name'] = __( 'Trip Name', 'tripzzy' );
			$columns['date']      = $_date;
			return $columns;
		}

		/**
		 * Admin Column Heading
		 *
		 * @param string $column_name Name of the column.
		 * @param string $enquiry_id Enquiry id.
		 * @since 1.2.4
		 * @return void
		 */
		public function admin_column_contents( $column_name, $enquiry_id ) {

			switch ( $column_name ) {
				case 'trip_name':
					$values  = MetaHelpers::get_post_meta( $enquiry_id, 'enquiry' );
					$trip_id = $values['trip_id'] ?? 0;
					echo esc_html( get_the_title( $trip_id ) );
					break;
				default:
					break;
			} // end switch
		}
	}
}
