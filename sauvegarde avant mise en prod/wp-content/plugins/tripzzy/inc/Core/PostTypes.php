<?php
/**
 * Tripzzy PostTypes.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Helpers\Page;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Localize;

if ( ! class_exists( 'Tripzzy\Core\PostTypes' ) ) {
	/**
	 * Tripzzy PostTypes Class.
	 *
	 * @since 1.0.0
	 */
	class PostTypes {
		use SingletonTrait;

		/**
		 * Constructor.
		 */
		public function __construct() {
			foreach ( glob( sprintf( '%1$sinc/Core/PostTypes/*.php', TRIPZZY_ABSPATH ) ) as $filename ) {
				$namespace  = 'Tripzzy\Core\PostTypes';
				$class_name = basename( $filename, '.php' );
				if ( class_exists( $namespace . '\\' . $class_name ) ) {
					$name = $namespace . '\\' . $class_name;
					new $name();
				}
			}
		}
	}
}
