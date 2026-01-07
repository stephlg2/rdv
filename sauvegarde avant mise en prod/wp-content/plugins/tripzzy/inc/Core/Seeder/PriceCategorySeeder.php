<?php
/**
 * Create default price category while activation.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Seeder;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Traits\DataTrait;

if ( ! class_exists( 'Tripzzy\Core\Seeder\PriceCategorySeeder' ) ) {
	/**
	 * Price category seeder class.
	 *
	 * @since 1.0.0
	 */
	class PriceCategorySeeder {
		use DataTrait;

		/**
		 * Post Type Key.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $taxonomy = 'tripzzy_price_category';

		/**
		 * This method will call only on plugin activation. Initialize Default terms array and create as well.
		 *
		 * @since 1.0.0
		 */
		public static function init() {
			$settings    = Settings::get();
			$price_terms = self::get_price_cagetory_terms();

			foreach ( $price_terms as $term_data ) {
				self::create( $term_data );
			}
		}

		/**
		 * Get Available page to create.
		 *
		 * @since 1.0.0
		 */
		public static function get_price_cagetory_terms() {
			return apply_filters(
				'tripzzy_filter_price_category_seeder',
				array(
					array(
						'term' => __( 'Adult', 'tripzzy' ),
						'slug' => 'adult',
					),
					array(
						'term' => __( 'Children', 'tripzzy' ),
						'slug' => 'children',
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
			$term     = isset( $term_data['term'] ) ? $term_data['term'] : '';
			$slug     = isset( $term_data['slug'] ) ? $term_data['slug'] : '';
			if ( $term ) {
				$term_exits = term_exists( $slug, $taxonomy );
				if ( 0 === $term_exits || null === $term_exits ) {
					$new_term = wp_insert_term(
						$term,
						$taxonomy,
						array(
							'slug' => $term,
						)
					);
				}
			}
		}
	}
}
