<?php
/**
 * Cookie Class
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Traits\DataTrait;

if ( ! class_exists( 'Tripzzy\Core\Helpers\Cookie' ) ) {
	/**
	 * Class For Cookie.
	 *
	 * @since 1.0.0
	 */
	class Cookie {

		use DataTrait;

		/**
		 * Get Cookie Value.
		 *
		 * @param string $name Cookie name.
		 */
		public static function get( $name ) {

			$name = self::get_prefix( $name );

			if ( isset( $_COOKIE[ $name ] ) ) {
				return self::json_to_data( sanitize_text_field( wp_unslash( $_COOKIE[ $name ] ) ) );
			}

			return '';
		}

		/**
		 * Get Cookie Value.
		 *
		 * @param string  $name Cookie name.
		 * @param mixed   $value Cookie value to store.
		 * @param number  $expire Cookie Expire time in second from now. If set 0 this will expire when browser is closed.
		 * @param boolean $secure Optional. Specifies whether or not the cookie should only be transmitted over a secure HTTPS connection.
		 * @param boolean $httponly Optional. If set to TRUE the cookie will be accessible only through the HTTP protocol (the cookie will not be accessible by scripting languages).
		 */
		public static function set( $name, $value, $expire = 0, $secure = false, $httponly = false ) {

			if ( ! headers_sent() ) {
				$name    = self::get_prefix( $name );
				$options = apply_filters(
					'tripzzy_filter_set_cookie_options',
					array(
						'expires'  => $expire,
						'secure'   => $secure,
						'path'     => COOKIEPATH ? COOKIEPATH : '/',
						'domain'   => COOKIE_DOMAIN,
						'httponly' => apply_filters( 'tripzzy_filter_cookie_httponly', $httponly, $name, $value, $expire, $secure ),
					),
					$name,
					$value
				);

				if ( version_compare( PHP_VERSION, '7.3.0', '>=' ) ) {
					return setcookie( $name, self::data_to_json( $value ), $options );
				} else {
					return setcookie( $name, self::data_to_json( $value ), $options['expires'], $options['path'], $options['domain'], $options['secure'], $options['httponly'] );
				}
			}
		}
	}
}
