<?php
/**
 * Cache class.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Cache.
 */
class Cache {

	/**
	 * Get prefix for use with wp_cache_set. Allows all cache in a group to be invalidated at once.
	 *
	 * @param  string $group Group of cache to get.
	 * @return string
	 */
	public static function get_cache_prefix( $group ) {
		$prefix = wp_cache_get( 'tripzzy_' . $group . '_cache_prefix', $group );
		if ( false === $prefix ) {
			$prefix = microtime();
			wp_cache_set( 'tripzzy_' . $group . '_cache_prefix', $prefix, $group );
		}

		return 'tripzzy_cache_' . $prefix . '_';
	}

	/**
	 * Invalidate cache group.
	 *
	 * @param string $group Group of cache to clear.
	 * @since 1.0.0
	 */
	public static function invalidate_cache_group( $group ) {
		wp_cache_set( 'tripzzy_' . $group . '_cache_prefix', microtime(), $group );
	}
}
