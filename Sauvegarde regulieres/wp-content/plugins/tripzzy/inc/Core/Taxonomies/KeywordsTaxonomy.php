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

if ( ! class_exists( 'Tripzzy\Core\Taxonomies\KeywordsTaxonomy' ) ) {
	/**
	 * Tripzzy Keyword Taxonomy Class.
	 *
	 * @since 1.0.0
	 */
	class KeywordsTaxonomy extends TaxonomyBase {
		/**
		 * Taxonomy Key to register Taxonomy.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $taxonomy = 'tripzzy_keywords';

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
				'name'              => _x( 'Keywords', 'taxonomy general name', 'tripzzy' ),
				'singular_name'     => _x( 'Keyword', 'taxonomy singular name', 'tripzzy' ),
				'search_items'      => __( 'Search Keywords', 'tripzzy' ),
				'all_items'         => __( 'All Keywords', 'tripzzy' ),
				'parent_item'       => __( 'Parent Keyword', 'tripzzy' ),
				'parent_item_colon' => __( 'Parent Keyword:', 'tripzzy' ),
				'edit_item'         => __( 'Edit Keyword', 'tripzzy' ),
				'update_item'       => __( 'Update Keyword', 'tripzzy' ),
				'add_new_item'      => __( 'Add New Keyword', 'tripzzy' ),
				'new_item_name'     => __( 'New Keyword', 'tripzzy' ),
				'menu_name'         => __( 'Keywords', 'tripzzy' ),
				'back_to_items'     => '← ' . __( 'Go to Keywords', 'tripzzy' ),
			);

			$args = array(
				'hierarchical'      => false,
				'labels'            => $labels,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'query_var'         => true,
				'rewrite'           => array( 'slug' => self::$slugs['tripzzy_trip_keyword_base'] ),
				'object_types'      => self::$object_types, // Where to add This taxonomy.
				'icon'              => 'fa-solid fa-magnifying-glass', // only for trip info section default icon.
				'priority'          => 40,
			);
			return $args;
		}
	}
}
