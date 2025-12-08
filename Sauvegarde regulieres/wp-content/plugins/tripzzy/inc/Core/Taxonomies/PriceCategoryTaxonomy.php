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
use Tripzzy\Admin\Permalinks;

if ( ! class_exists( 'Tripzzy\Core\Taxonomies\PriceCategoryTaxonomy' ) ) {
	/**
	 * Tripzzy Taxonomy Class.
	 *
	 * @since 1.0.0
	 */
	class PriceCategoryTaxonomy extends TaxonomyBase {
		/**
		 * Taxonomy Key to register Taxonomy.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $taxonomy = 'tripzzy_price_category';

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
			self::$slugs = Permalinks::get();
			add_filter( 'tripzzy_filter_taxonomy_args', array( $this, 'init_args' ) );
		}

		/**
		 * Taxonomy arguments.
		 *
		 * @since 1.0.0
		 */
		protected static function taxonomy_args() {
			$labels = array(
				'name'              => _x( 'Price Categories', 'taxonomy general name', 'tripzzy' ),
				'singular_name'     => _x( 'Price Category', 'taxonomy singular name', 'tripzzy' ),
				'search_items'      => __( 'Search Price Categories', 'tripzzy' ),
				'all_items'         => __( 'All Price Categories', 'tripzzy' ),
				'parent_item'       => __( 'Parent Price Category', 'tripzzy' ),
				'parent_item_colon' => __( 'Parent Price Category:', 'tripzzy' ),
				'edit_item'         => __( 'Edit Price Category', 'tripzzy' ),
				'update_item'       => __( 'Update Price Category', 'tripzzy' ),
				'add_new_item'      => __( 'Add New Price Category', 'tripzzy' ),
				'new_item_name'     => __( 'New Price Category', 'tripzzy' ),
				'menu_name'         => __( 'Price Categories', 'tripzzy' ),
				'back_to_items'     => '← ' . __( 'Go to Price Categories', 'tripzzy' ),
			);

			$args = array(
				'hierarchical'      => false,
				'labels'            => $labels,
				'show_ui'           => true,
				'show_admin_column' => false,
				'show_in_rest'      => true,
				'query_var'         => true,
				'object_types'      => self::$object_types, // Where to add This taxonomy.
				'icon'              => 'fa-solid fa-people-group', // only for trip info section default icon.
				'priority'          => 10,
			);
			return $args;
		}
	}
}
