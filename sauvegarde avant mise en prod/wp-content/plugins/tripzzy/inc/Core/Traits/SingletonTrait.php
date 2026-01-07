<?php
/**
 * Singleton trait for plugin.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Traits;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Define Trait.
 */
trait SingletonTrait {
	/**
	 * The single instance of the class.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize singleton instance of the class. will return this instance if created otherwise create new instance first.
	 *
	 * @param array $args Array of arguments.
	 * @since 1.0.0
	 * @return object Tripzzy Main singleton instance.
	 */
	public static function instance( $args = array() ) {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $args );
		}
		return self::$instance;
	}

	/**
	 * Prevent cloning.
	 *
	 * @since 1.0.0
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {}
}
