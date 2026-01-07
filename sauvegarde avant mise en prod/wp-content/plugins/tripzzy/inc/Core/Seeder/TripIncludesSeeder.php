<?php
/**
 * Add Trip includes Taxonomies while activating plugin.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Seeder;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Traits\DataTrait;

if ( ! class_exists( 'Tripzzy\Core\Seeder\TripIncludesSeeder' ) ) {
	/**
	 * Create trip includes seeder class.
	 *
	 * @since 1.0.0
	 */
	class TripIncludesSeeder {

		use DataTrait;

		/**
		 * Taxonomy Key.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $taxonomy = 'trip_includes';

		/**
		 * This method will call only on plugin activation. Initialize Pages array to create.
		 *
		 * @since 1.0.0
		 */
		public static function init() {
			$settings   = Settings::get();
			$term_datas = self::get_data();

			foreach ( $term_datas as $term_data ) {
				self::create( $term_data );
			}
		}

		/**
		 * Get Available page to create.
		 *
		 * @since 1.0.0
		 */
		public static function get_data() {
			return apply_filters(
				'tripzzy_filter_trip_includes_seeder',
				array(
					array(
						'term'        => __( 'Accomodation', 'tripzzy' ),
						'slug'        => 'accomodation',
						'description' => 'accomodation description',
						'children'    => array(
							array(
								'term'        => 'Hotel',
								'slug'        => 'hotel',
								'description' => 'Hotel description',
							),
							array(
								'term'        => 'Casino',
								'slug'        => 'casino',
								'description' => 'Casino description',
							),
						),
						'term_metas'  => array(
							'fa_class' => 'fa fa-home',
						),
					),
					array(
						'term'        => __( 'Transportation', 'tripzzy' ),
						'slug'        => 'transportation',
						'description' => 'transportation description',
						'children'    => array(
							array(
								'term'        => 'Bus',
								'slug'        => 'bus',
								'description' => 'Bus description',
							),
							array(
								'term'        => 'Taxi',
								'slug'        => 'taxi',
								'description' => 'Taxi description',
							),
						),
						'term_metas'  => array(
							'fa_class' => 'fa fa-bus',
						),
					),

				)
			);
		}

		/**
		 * Create Term as per argument.
		 *
		 * @param array $term_data Arguments to create terms for Price Category Taxonomy.
		 */
		public static function create( $term_data ) {
			$taxonomy = self::get_prefix( self::$taxonomy );

			$term        = isset( $term_data['term'] ) ? $term_data['term'] : '';
			$slug        = isset( $term_data['slug'] ) ? $term_data['slug'] : '';
			$description = isset( $term_data['description'] ) ? $term_data['description'] : '';
			$children    = isset( $term_data['children'] ) ? $term_data['children'] : array();
			$parent      = isset( $term_data['parent'] ) ? $term_data['parent'] : 0;
			if ( $term ) {
				$term_exits = term_exists( $slug, $taxonomy );
				if ( 0 === $term_exits || null === $term_exits ) {
					$term_args = array(
						'description' => $description,
						'slug'        => $term,
					);
					if ( $parent ) {
						$term_args['parent'] = $parent;
					}
					$new_term = wp_insert_term( $term, $taxonomy, $term_args );

					if ( is_array( $new_term ) ) {

						$new_term_id = $new_term['term_id'];
						// Add term metas.
						if ( isset( $term_data['term_metas'] ) && is_array( $term_data['term_metas'] ) && count( $term_data['term_metas'] ) > 0 ) {
							foreach ( $term_data['term_metas'] as $meta_key => $meta_value ) {
								MetaHelpers::update_term_meta( $new_term_id, $meta_key, sanitize_text_field( $meta_value ) );
							}
						}

						// Add Child Terms [recursion].
						if ( is_array( $children ) && count( $children ) > 0 ) {
							foreach ( $children as $child_term ) {
								$child_term['parent'] = $new_term_id;
								self::create( $child_term );
							}
						}
					}
				}
			}
		}
	}
}
