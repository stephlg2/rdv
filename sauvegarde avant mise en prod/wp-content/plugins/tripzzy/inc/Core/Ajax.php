<?php
/**
 * Tripzzy Ajax.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;

if ( ! class_exists( 'Tripzzy\Core\Ajax' ) ) {
	/**
	 * Tripzzy Ajax Class.
	 *
	 * @since 1.0.0
	 */
	class Ajax {
		use SingletonTrait;

		/**
		 * Constructor.
		 */
		public function __construct() {
			foreach ( glob( sprintf( '%1$sinc/Core/Ajax/*.php', TRIPZZY_ABSPATH ) ) as $filename ) {
				$namespace  = 'Tripzzy\Core\Ajax';
				$class_name = basename( $filename, '.php' );
				if ( class_exists( $namespace . '\\' . $class_name ) && method_exists( $namespace . '\\' . $class_name, 'instance' ) ) {
					call_user_func( $namespace . '\\' . $class_name . '::instance' );
				}
			}
		}
	}
}
