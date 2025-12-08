<?php
/**
 * Base Class For Tripzzy Rest API.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Bases;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Traits\SingletonTrait;

if ( ! class_exists( 'Tripzzy\Core\Bases\RestApiBase' ) ) {
	/**
	 * Base Class For Tripzzy Rest API.
	 *
	 * @since 1.0.0
	 */
	class RestApiBase {
		use SingletonTrait;

		/**
		 * An array of Rest API args.
		 *
		 * @var array
		 * @since 1.0.0
		 */
		private static $api_args = array();

		/**
		 * Initialize Rest APIs.
		 *
		 * @since 1.0.0
		 */
		public static function init() {
			// Register Rest API.
			$api_args = apply_filters( 'tripzzy_filter_rest_api_args', self::$api_args );
			if ( is_array( $api_args ) && ! empty( $api_args ) ) {
				foreach ( $api_args as $namespace => $args ) {
					// Routes.
					foreach ( $args as $route_name => $api_arg ) {
						register_rest_route(
							$namespace,
							$route_name,
							$api_arg
						);
					}
				}
			}
		}

		/**
		 * Add Rest API args to create rest api routes.
		 *
		 * @param array $api_args Array arguments.
		 *
		 * @since 1.0.0
		 */
		public function init_args( $api_args ) {
			$api_args[ static::$api_namespace ] = static::api_args();
			return $api_args;
		}
	}
}
