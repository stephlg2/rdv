<?php
/**
 * Base Class For Nonce.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Http;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'Tripzzy\Core\Http\Nonce' ) ) {
	/**
	 * Base Class For Nonce
	 *
	 * @since 1.0.0
	 */
	class Nonce {

		/**
		 * Nonce Action.
		 *
		 * @var $nonce_action
		 */
		protected static $nonce_action = 'tripzzy_nonce_action';

		/**
		 * Nonce Name for input.
		 *
		 * @var $nonce_name
		 */
		protected static $nonce_name = 'tripzzy_nonce';

		/**
		 * Create nonce in case of any request.
		 *
		 * @since 1.0.0
		 * @return boolean
		 */
		public static function create() {
			if ( function_exists( 'wp_create_nonce' ) ) {
				return wp_create_nonce( self::$nonce_action );
			}
		}

		/**
		 * Create nonce field.
		 *
		 * @since 1.0.0
		 */
		public static function create_field() {
			?>
			<input type="hidden" name="<?php echo esc_attr( self::$nonce_name ); ?>" value="<?php echo esc_attr( self::create() ); ?>" />
			<?php
		}

		/**
		 * Verify nonce in case of any request.
		 *
		 * @since 1.0.0
		 * @since 1.1.1 sanitize_text_field and wp_unslash replaced with sanitize_key.
		 * @return boolean
		 */
		public static function verify() {
			/**
			 * Nonce Verification.
			 */
			if ( ! isset( $_REQUEST[ self::$nonce_name ] ) || ! \wp_verify_nonce( \sanitize_key( $_REQUEST[ self::$nonce_name ] ), self::$nonce_action ) ) {
				return false;
			}
			return true;
		}

		/**
		 * Get Nonce Name.
		 *
		 * @since 1.0.0
		 * @return string
		 */
		public static function get_nonce_name() {
			return self::$nonce_name;
		}

		/**
		 * Get Nonce action.
		 *
		 * @since 1.0.0
		 * @return string
		 */
		public static function get_nonce_action() {
			return self::$nonce_action;
		}
	}
}
