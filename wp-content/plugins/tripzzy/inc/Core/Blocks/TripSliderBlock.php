<?php
/**
 * Tripzzy Slider Block.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Blocks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\BlocksBase;

if ( ! class_exists( 'Tripzzy\Core\Blocks\TripSliderBlock' ) ) {
	/**
	 * Tripzzy Slider Block Class.
	 *
	 * @since 1.0.9
	 */
	class TripSliderBlock extends BlocksBase {
		/**
		 * Block slug to register block.
		 * var $block_slug must be identical to dir name at assets/blocks/<block dir name>.
		 *
		 * @since 1.0.9
		 * @var string
		 */
		protected static $block_slug = 'trip-slider';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'tripzzy_filter_blocks_args', array( $this, 'init_args' ) );
		}

		/**
		 * Search Block arguments.
		 *
		 * @since 1.0.9
		 */
		protected static function blocks_args() {
			return array();
		}
	}
}
