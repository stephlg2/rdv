<?php
/**
 * Class For Request Methods.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Http;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Helpers\EscapeHelper;
if ( ! class_exists( 'Tripzzy\Core\Http\Request' ) ) {
	/**
	 * Class For Request
	 *
	 * @since 1.0.0
	 */
	class Request {

		/**
		 * Request Type. Whether Ajax request, admin request etc.
		 *
		 * @param  string $type admin, ajax, cron or frontend.
		 *
		 * @since 1.0.0
		 * @return bool
		 */
		public static function is( $type ) {
			switch ( $type ) {
				case 'admin':
					return is_admin();
				case 'ajax':
					return defined( 'DOING_AJAX' );
				case 'cron':
					return defined( 'DOING_CRON' );
				case 'frontend':
					return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
			}
		}

		/**
		 * Get payload.
		 *
		 * @param bool $return_raw Whether return raw data or sanitized data.
		 * @since 1.0.0
		 * @return array
		 */
		public static function get_payload( $return_raw = false ) {
			if ( ! Nonce::verify() ) {
				return; // Do not send json response here.
			}
			global $wp_filesystem;
			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}

			$raw_post_data = json_decode( $wp_filesystem->get_contents( 'php://input' ) );
			if ( $return_raw ) {
				// Return Raw data where we can not perform early sanitize. like complex array/json structure.
				return (array) $raw_post_data;
			}
			return (array) self::sanitize_data( $raw_post_data );
		}

		/**
		 * Sanitize data. It may be either string or array.
		 *
		 * @param mixed $data         Input data.
		 * @param bool  $wp_kses_post If data need wp keses or not.
		 * @since 1.0.0
		 * @return array
		 */
		public static function sanitize_data( $data, $wp_kses_post = false ) {
			$allowed_html = EscapeHelper::get_allowed_html();
			if ( is_string( $data ) ) {
				if ( $wp_kses_post ) {
					$data = wp_kses( $data, $allowed_html );
				} else {
					$data = self::sanitize_value( $data );
				}
			} elseif ( is_array( $data ) || is_object( $data ) ) {
				if ( $wp_kses_post ) {
					foreach ( $data as $key => &$value ) {
						if ( is_object( $value ) ) {
							$value = (array) $value;
						}
						if ( is_array( $value ) ) {
							$value = self::sanitize_data( wp_unslash( $value ), $wp_kses_post );
						} else {
							$value = wp_kses( $value, $allowed_html );
						}
					}
				} else {
					foreach ( $data as $key => &$value ) {
						if ( is_object( $value ) ) {
							$value = (array) $value;
						}
						if ( is_array( $value ) ) {
							$value = self::sanitize_data( wp_unslash( $value ), $wp_kses_post );
						} else {
							$value = self::sanitize_value( $value );
						}
					}
				}
			}
			return $data;
		}

		/**
		 * Sanitize Value.
		 *
		 * @param String $value Value to sanitize.
		 * @since 1.0.0
		 * @return string
		 */
		public static function sanitize_value( $value ) {
			if ( ! empty( $value ) ) {
				switch ( $value ) {
					case tripzzy_is_email( $value ):
						$value = sanitize_email( $value );
						break;
					case tripzzy_is_url( $value ):
						// Restored / Undeprecated from WP 5.9.
						$value = sanitize_url( $value ); // @phpcs:ignore
						break;
					default:
						$value = sanitize_text_field( wp_unslash( $value ) );
						break;
				}
			}
			return $value;
		}

		/**
		 * Sanitize the input value. It will Directly get value from GET, POST or Payload request.
		 *
		 * @param constant $type Type for filter_input. [INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, or INPUT_ENV].
		 * @param string   $name Name of the input.
		 * @param string   $default_val Fallback Value.
		 * @since 1.1.6
		 * @return mixed
		 */
		public static function sanitize_input( $type, $name = '', $default_val = '' ) {
			switch ( $type ) {
				case 'INPUT_PAYLOAD':
					global $wp_filesystem;
					if ( empty( $wp_filesystem ) ) {
						require_once ABSPATH . '/wp-admin/includes/file.php';
						WP_Filesystem();
					}
					$data = (array) json_decode( $wp_filesystem->get_contents( 'php://input' ) );
					break;
				case 'INPUT_POST':
					$data = $_POST; // phpcs:ignore
					break;
				default:
					$data = $_GET; // phpcs:ignore
					break;
			}

			$data = self::sanitize_data( $data );
			if ( $name ) {
				return $data[ $name ] ?? $default_val;
			} else {
				return $data;
			}
		}
	}
}
