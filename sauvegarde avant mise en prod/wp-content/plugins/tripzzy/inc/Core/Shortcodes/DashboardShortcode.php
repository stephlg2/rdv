<?php
/**
 * Tripzzy User dashboard Shortcode
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Shortcodes;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\ShortcodeBase;
use Tripzzy\Core\Helpers\Trip;

if ( ! class_exists( 'Tripzzy\Core\Shortcodes\DashboardShortcode' ) ) {
	/**
	 * Tripzzy User dashboard Shortcode
	 *
	 * @since 1.0.0
	 */
	class DashboardShortcode extends ShortcodeBase {
		/**
		 * Shortcode name.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $shortcode = 'TRIPZZY_DASHBOARD';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'tripzzy_filter_shortcode_args', array( $this, 'init_args' ) );
		}

		/**
		 * Add shortcode arguments to register Shortcode from base class.
		 *
		 * @since 1.0.0
		 */
		protected static function shortcode_args() {
			$args = array(
				'shortcode' => self::$shortcode,
				'callback'  => array( 'Tripzzy\Core\Shortcodes\DashboardShortcode', 'render' ), // #2.
			);
			return $args;
		}

		/**
		 * Default Shortcode attributes list.
		 *
		 * @since 1.0.0
		 */
		protected static function default_atts() {
			return array();
		}

		/**
		 * Render Shortcode content.
		 *
		 * @since 1.0.0
		 */
		public static function render() {
			ob_start();
			if ( ! is_user_logged_in() ) {
				wp_login_form(
					array(
						'form_id' => 'tripzzy_loginform',
					)
				);
			} else {
				?>
				<div id='tripzzy-dashboard'></div>
				<?php
			}
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}
	}
}
