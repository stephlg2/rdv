<?php
/**
 * Modules Helpers.
 *
 * @package tripzzy
 * @since 1.0.5
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\MetaHelpers;

if ( ! class_exists( 'Tripzzy\Core\Helpers\Modules' ) ) {

	/**
	 * Define Modules Helper class. Introduce this helper in v1.0.5.
	 *
	 * @since 1.0.5
	 */
	class Modules {

		/**
		 * Get All Modules Data.
		 *
		 * @since 1.0.5
		 * @return array
		 */
		public static function get_data() {
			return apply_filters( 'tripzzy_filter_modules_data', array() );
		}

		/**
		 * Get All Modules.
		 *
		 * @param mixed $name Module Name either array or string.
		 *
		 * @since 1.0.5
		 * @return array
		 */
		public static function get( $name = '' ) {
			$default  = self::get_data(); // Default Modules.
			$settings = MetaHelpers::get_option( 'settings', array() ); // Saved settings values.
			$modules  = $settings['modules'] ?? array(); // Saved modules values.
			$modules  = array_merge( $default, $modules );

			// Return all result if no name.
			if ( ! $name ) {
				return $modules;
			}

			if ( is_array( $name ) ) {
				if ( empty( $name ) ) {
					return;
				}
				// Return Specified module as per name [array].
				$options = array();
				foreach ( $name as $k ) {
					if ( isset( $modules[ $k ] ) ) {
						$options[ $k ] = $modules[ $k ];
					}
				}
			} else {
				// Return Specified module as per name [string].
				$options = '';

				if ( isset( $modules[ $name ] ) ) {
					$options = $modules[ $name ];
				}
			}
			return $options;
		}

		/**
		 * Get Active Modules.
		 *
		 * @since 1.0.5
		 * @since 1.2.1 Filter only active by checking modules exists via default modules data.
		 * @return array
		 */
		public static function get_active_modules() {
			$all_modules = self::get();
			$default     = self::get_data();
			$active      = array();

			foreach ( $all_modules as $key => $module ) {
				$enabled   = (bool) $module['enabled'];
				$available = (bool) ( $default[ $key ] ?? false );
				if ( $enabled && $available ) {
					$active[ $key ] = $module;
				}
			}
			return $active;
		}

		/**
		 * Check modules active or not.
		 *
		 * @param String $module_name Name of the module.
		 *
		 * @since 1.0.5
		 * @return bool
		 */
		public static function is_active( $module_name = '' ) {
			$active = self::get_active_modules();
			return (bool) ( isset( $active[ $module_name ] ) && $active[ $module_name ] );
		}
	}
}
