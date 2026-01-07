<?php
/**
 * Locale trait for plugin.
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
trait LocaleTrait {
	/**
	 * Get Website locale.
	 *
	 * @since 1.0.0
	 * @return String
	 */
	public static function get_locale() {
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			// Backward compatibility below WP 5.0.
			$locale = is_admin() ? get_user_locale() : get_locale();
		}
		return $locale;
	}
}
