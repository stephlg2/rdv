<?php
/**
 * Tripzzy Theme Compatibility.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;

if ( ! class_exists( 'Tripzzy\Core\ThemeCompatibility' ) ) {
	/**
	 * Tripzzy ThemeCompatibility Class.
	 *
	 * @since 1.0.0
	 */
	class ThemeCompatibility {
		use SingletonTrait;

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'after_setup_theme', array( $this, 'init_compatibility' ), 0 );
		}

		/**
		 * Add Theme compatibility.
		 *
		 * @return void
		 */
		public function init_compatibility() {
			$active_theme = get_option( 'template' );
			if ( $active_theme ) {
				switch ( $active_theme ) {
					case 'astra':
						require_once sprintf( '%1$sinc/Core/ThemeCompatibility/Astra.php', TRIPZZY_ABSPATH );
						break;
					case 'travel-agency':
						require_once sprintf( '%1$sinc/Core/ThemeCompatibility/TravelAgency.php', TRIPZZY_ABSPATH );
						break;
				}
			}
		}
	}
}
