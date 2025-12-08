<?php
/**
 * Conditional functions for Tripzzy.
 *
 * @package tripzzy
 */

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\Trip;

if ( ! function_exists( 'tripzzy_site_is_https' ) ) {
	/**
	 * Check whether website uses https or not.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	function tripzzy_site_is_https() {
		return false !== strstr( get_option( 'home' ), 'https:' );
	}
}

if ( ! function_exists( 'tripzzy_post_exists' ) ) {
	/**
	 * Check whether post is exists or not.
	 *
	 * @param int $post_id Post id.
	 * @since 1.0.0
	 * @return bool
	 */
	function tripzzy_post_exists( $post_id ) {
		return is_string( get_post_status( $post_id ) );
	}
}

if ( ! function_exists( 'tripzzy_is_url' ) ) {
	/**
	 * Check for valid url.
	 *
	 * @param string $value Value to check.
	 * @since 1.0.0
	 * @return boolean
	 */
	function tripzzy_is_url( $value ) {
		return filter_var( $value, FILTER_VALIDATE_URL ) !== false;
	}
}

if ( ! function_exists( 'tripzzy_is_email' ) ) {
	/**
	 * Check for valid email.
	 *
	 * @param string $value Value to check.
	 * @since 1.0.0
	 * @return boolean
	 */
	function tripzzy_is_email( $value ) {
		return filter_var( $value, FILTER_VALIDATE_EMAIL ) !== false;
	}
}

if ( ! function_exists( 'tripzzy_is_fse_theme' ) ) {
	/**
	 * Check current active theme is block/fse theme or not.
	 *
	 * @since 1.0.6
	 * @return boolean
	 */
	function tripzzy_is_fse_theme() {
		if ( function_exists( 'wp_is_block_theme' ) ) {
			return (bool) wp_is_block_theme();
		}
		if ( function_exists( 'gutenberg_is_fse_theme' ) ) {
			return (bool) gutenberg_is_fse_theme();
		}

		return false;
	}
}

if ( ! function_exists( 'tripzzy_theme_has_theme_json' ) ) {
	/**
	 * Check theme.json file in current active theme.
	 *
	 * @internal This is an identical copy of WP Core function wp_theme_has_theme_json in legacy block.
	 *
	 * @since 1.0.7
	 * @return boolean
	 */
	function tripzzy_theme_has_theme_json() {
		/**
		 * For WP version  greater than 6.2
		 */
		if ( function_exists( 'wp_theme_has_theme_json' ) ) {
			return (bool) wp_theme_has_theme_json();
		} else {
			// Legacy version compatible.
			static $theme_has_support = array();
			$stylesheet               = get_stylesheet();

			if ( isset( $theme_has_support[ $stylesheet ] ) && ! wp_is_development_mode( 'theme' ) ) {
				return $theme_has_support[ $stylesheet ];
			}

			$stylesheet_directory = get_stylesheet_directory();
			$template_directory   = get_template_directory();

			if ( $stylesheet_directory !== $template_directory && file_exists( $stylesheet_directory . '/theme.json' ) ) {
				$path = $stylesheet_directory . '/theme.json';
			} else {
				$path = $template_directory . '/theme.json';
			}

			$theme_has_support[ $stylesheet ] = file_exists( $path );

			return $theme_has_support[ $stylesheet ];
		}

		return false;
	}
}

if ( ! function_exists( 'tripzzy_has_upgrade_for' ) ) {
	/**
	 * Conditional function to check whether the tripzzy needs an upgrade for the provided version.
	 *
	 * @param string $version Tripzzy version to check the upgrade available for the provided version.
	 * @since 1.1.4
	 * @return boolean
	 */
	function tripzzy_has_upgrade_for( $version ) {
		if ( ! $version ) {
			return;
		}

		$used_since = MetaHelpers::get_option( 'used_since', TRIPZZY_VERSION );
		if ( version_compare( $version, $used_since, '>' ) ) {
			return true;
		}
		return false;
	}
}

if ( ! function_exists( 'tripzzy_enable_default_option_for_user' ) ) {
	/**
	 * Conditional function to enable/disable newly added default settings option.
	 *
	 * @param string $version Tripzzy version to check the upgrade available for the provided version.
	 * @since 1.2.2
	 * @return boolean
	 */
	function tripzzy_enable_default_option_for_user( $version ) {
		if ( ! $version ) {
			return;
		}
		$used_since = MetaHelpers::get_option( 'used_since', TRIPZZY_VERSION );
		if ( version_compare( $used_since, $version, '>=' ) ) {
			return true;
		}
		return false;
	}
}

if ( ! function_exists( 'tripzzy_has_time' ) ) {
	/**
	 * Conditional function to check Trip time module exists or not.
	 *
	 * @since 1.2.5
	 * @return boolean
	 */
	function tripzzy_has_time() {
		return class_exists( 'Tripzzy\Modules\Utilities\TripTime' );
	}
}

if ( ! function_exists( 'tripzzy_has_time_enabled' ) ) {
	/**
	 * Conditional function to check Trip time enabled or not.
	 *
	 * @param object $trip Trip object.
	 * @since 1.2.5
	 * @return boolean
	 */
	function tripzzy_has_time_enabled( $trip ) {
		if ( ! is_object( $trip ) ) {
			$trip = new Trip( $trip );
		}
		return apply_filters( 'tripzzy_filter_has_time_enabled', false, $trip );
	}
}
