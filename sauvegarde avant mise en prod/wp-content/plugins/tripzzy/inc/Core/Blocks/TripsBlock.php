<?php
/**
 * Tripzzy Trips Block.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Blocks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\BlocksBase;

if ( ! class_exists( 'Tripzzy\Core\Blocks\TripsBlock' ) ) {
	/**
	 * Tripzzy Trips Block Class.
	 *
	 * @since 1.0.0
	 */
	class TripsBlock extends BlocksBase {
		/**
		 * Trips Block slug to register block.
		 * var $block_slug must be identical to dir name at assets/blocks/<block dir name>.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $block_slug = 'trips';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'tripzzy_filter_blocks_args', array( $this, 'init_args' ) );
		}

		/**
		 * Trips Block arguments.
		 *
		 * @since 1.0.0
		 */
		protected static function blocks_args() {
			return array();
		}
	}
}
