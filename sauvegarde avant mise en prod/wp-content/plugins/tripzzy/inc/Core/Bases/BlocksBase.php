<?php
/**
 * Tripzzy Blocks Base.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Bases;

use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Helpers\ArrayHelper;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BlocksBase' ) ) {
	/**
	 * Abstract Parent class to hold common functions used by specific Tripzzy Blocks.
	 */
	abstract class BlocksBase {
		use SingletonTrait;

		/**
		 * Block Base Name.
		 *
		 * @var string $block_base
		 */
		protected static $block_base = 'tripzzy';

		/**
		 * Block Path.
		 *
		 * @var string $blocks_path
		 */
		protected static $blocks_path;

		/**
		 * An array of post type arguments to register the custom post type with array key being post type slug and value being $args.
		 *
		 * @var array
		 * @since 1.0.0
		 */
		private static $blocks_args = array();

		/**
		 * Initialize the hooks.
		 */
		public static function init() {
			// Register Tripzzy as new block category.
			add_filter( 'block_categories_all', array( __CLASS__, 'add_block_categories' ) );

			self::$blocks_path = trailingslashit( wp_normalize_path( TRIPZZY_ABSPATH . 'assets/blocks/' ) );
			self::$blocks_args = apply_filters( 'tripzzy_filter_blocks_args', self::$blocks_args );
			if ( function_exists( 'register_block_type' ) ) {
				if ( is_array( self::$blocks_args ) && ! empty( self::$blocks_args ) ) {
					foreach ( self::$blocks_args as $directory => $args ) {
						$blocks_full_path = sprintf( '%s%s', self::$blocks_path, $directory );
						register_block_type( $blocks_full_path, $args );
					}
				}
			}
		}

		/**
		 * Register block category.
		 *
		 * @param array $block_categories List of block categories.
		 * @since 1.0.0
		 * @since 1.0.8 Removed $context Param.
		 */
		public static function add_block_categories( $block_categories ) {
			$block_categories[] = array(
				'slug'  => 'tripzzy',
				'title' => __( 'Tripzzy', 'tripzzy' ),
				'icon'  => null,
			);
			return $block_categories;
		}


		/**
		 * Add Blocks arguments.
		 *
		 * @param array $blocks_args Array arguments.
		 *
		 * @since 1.0.0
		 */
		public function init_args( $blocks_args ) {
			$blocks_args[ static::$block_slug ] = static::blocks_args();
			return $blocks_args;
		}
	}
}
