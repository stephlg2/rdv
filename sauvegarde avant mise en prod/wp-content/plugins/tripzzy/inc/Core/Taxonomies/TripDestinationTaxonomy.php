<?php
/**
 * Tripzzy Taxonomy.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Taxonomies;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\TaxonomyBase;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Admin\Permalinks;
use Tripzzy\Core\Traits\DataTrait;

if ( ! class_exists( 'Tripzzy\Core\Taxonomies\TripDestinationTaxonomy' ) ) {
	/**
	 * Tripzzy Taxonomy Class.
	 *
	 * @since 1.0.0
	 */
	class TripDestinationTaxonomy extends TaxonomyBase {
		use DataTrait;

		/**
		 * Taxonomy Key to register Taxonomy.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $taxonomy = 'tripzzy_trip_destination';

		/**
		 * Object Types.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $object_types = array( 'tripzzy' );

		/**
		 * Permalinks.
		 *
		 * @since 1.0.0
		 * @var array
		 */
		protected static $slugs;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$taxonomy    = self::$taxonomy;
			self::$slugs = Permalinks::get();
			add_filter( 'tripzzy_filter_taxonomy_args', array( $this, 'init_args' ) );
			// Term meta.
			add_filter( 'tripzzy_filter_term_meta_form_fields', array( $this, 'init_term_meta_form_fields' ) );

			// Manage Taxonomy columns.
			add_filter( "manage_edit-{$taxonomy}_columns", array( $this, 'taxonomy_columns' ) );
			add_filter( "manage_{$taxonomy}_custom_column", array( $this, 'taxonomy_column_content' ), 10, 3 );

			// Add meta in REST api.
			$meta_fields = self::get_meta_fields();
			if ( is_array( $meta_fields ) && count( $meta_fields ) > 0 ) {
				foreach ( $meta_fields as $meta_key => $args ) {
					register_term_meta( $taxonomy, self::get_prefix( $meta_key ), $args );
				}
			}
		}

		/**
		 * Taxonomy arguments.
		 *
		 * @since 1.0.0
		 */
		protected static function taxonomy_args() {
			$labels = array(
				'name'              => _x( 'Trip Destinations', 'taxonomy general name', 'tripzzy' ),
				'singular_name'     => _x( 'Trip Destination', 'taxonomy singular name', 'tripzzy' ),
				'search_items'      => __( 'Search Trip Destination', 'tripzzy' ),
				'all_items'         => __( 'All Trip Destination', 'tripzzy' ),
				'parent_item'       => __( 'Parent Trip Destination', 'tripzzy' ),
				'parent_item_colon' => __( 'Parent Trip Destination:', 'tripzzy' ),
				'edit_item'         => __( 'Edit Trip Destination', 'tripzzy' ),
				'update_item'       => __( 'Update Trip Destination', 'tripzzy' ),
				'add_new_item'      => __( 'Add New Trip Destination', 'tripzzy' ),
				'new_item_name'     => __( 'New Trip Destination', 'tripzzy' ),
				'menu_name'         => __( 'Trip Destinations', 'tripzzy' ),
				'back_to_items'     => '← ' . __( 'Go to Destinations', 'tripzzy' ),
			);

			$args = array(
				'labels'             => $labels,
				'hierarchical'       => true,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_admin_column'  => true,
				'show_in_rest'       => true,
				'query_var'          => true,
				'rewrite'            => array(
					'slug'       => self::$slugs['tripzzy_trip_destination_base'],
					'with_front' => false,
				),
				'object_types'       => self::$object_types, // Where to add This taxonomy.
				'icon'               => 'fa-solid fa-location-dot', // only for trip info section default icon.
				'priority'           => 20,
			);
			return $args;
		}

		/**
		 * Term meta form fields.
		 *
		 * @since 1.0.0
		 */
		protected static function term_meta_form_fields() {
			$fields = array(
				'taxonomy_image' =>
				array(
					'type'                => 'image',
					'label'               => __( 'Taxonomy image', 'tripzzy' ),
					'name'                => 'taxonomy_image',
					'id'                  => 'taxonomy-image',
					'class'               => 'taxonomy-image',
					'placeholder'         => '',
					'required'            => false,
					'priority'            => 10,
					'value'               => '',
					'input_wrapper'       => 'span',
					'input_wrapper_class' => 'term-meta-input',
					'input_description'   => __( 'Taxonomy default thumbnail image', 'tripzzy' ),
					// Additional configurations.
					'is_new'              => true, // Whether it is new field just recently added or not? Always Need to set false for default fields.
					'is_default'          => true, // Whether it is Default field or not.
					'enabled'             => true, // soft enable. this field can be disabled.
					'force_enabled'       => true, // You can not disable if this set to true.
				),
			);
			return $fields;
		}

		/**
		 * Taxonomy column Titles
		 *
		 * @param array $columns List of column names.
		 * @return array
		 */
		public function taxonomy_columns( $columns ) {
			$columns['thumbnail'] = __( 'Thumbnail', 'tripzzy' );
			return $columns;
		}

		/**
		 * Taxonomy column Titles
		 *
		 * @param array $content Column content.
		 * @param array $column_name Column title.
		 * @param array $term_id current term id.
		 * @return array
		 */
		public function taxonomy_column_content( $content, $column_name, $term_id ) {
			$labels = Strings::get()['labels'];
			if ( 'thumbnail' === $column_name ) {
				$img_id  = MetaHelpers::get_term_meta( $term_id, 'taxonomy_image' );
				$content = self::get_thumbnail_small( $img_id );
			}
			return $content;
		}

		/**
		 * All Meta fields to register as term meta fields in Rest API.
		 *
		 * @sicne 1.0.1
		 * @return array
		 */
		public static function get_meta_fields() {
			$fields = array(
				'taxonomy_image'     => array(
					'type'         => 'number',
					'description'  => 'Tripzzy taxonomy image id',
					'single'       => true,
					'show_in_rest' => array(
						'schema' => array(
							'type'     => 'number',
							'context'  => array( 'view', 'edit' ),
							'readonly' => true,
						),
					),
				),
				'taxonomy_image_url' => array(
					'type'         => 'string',
					'description'  => 'Tripzzy taxonomy image url',
					'single'       => true,
					'show_in_rest' => array(
						'schema' => array(
							'type'     => 'string',
							'format'   => 'url',
							'context'  => array( 'view', 'edit' ),
							'readonly' => true,
						),
					),
				),
			);
			return $fields;
		}
	}
}
