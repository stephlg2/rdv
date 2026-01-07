<?php
/**
 * Travel Agency Theme Compatibility.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\ThemeCompatibility;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;

if ( ! class_exists( 'Tripzzy\Core\ThemeCompatibility\ThemeTravelAgency' ) ) {
	/**
	 * Travel Agency Theme Compatibility.
	 *
	 * @since 1.0.0
	 */
	class ThemeTravelAgency {
		use SingletonTrait;

		/**
		 * Constructor.
		 */
		public function __construct() {
			// For Theme specific inline Scripts and Styles.
			add_action( 'wp_enqueue_scripts', array( $this, 'theme_scripts' ), 20 );
		}

		/**
		 * Add Theme specific Scripts and Styles.
		 *
		 * @return void
		 */
		public function theme_scripts() {
			$inline_styles  = '
				:root{
					--tripzzy-primary-color:var(--primary-color);
				}
			';
			$inline_scripts = '
				function hexToRGB(hex) {
					const hexValue = hex.replace("#", "");
					const r = parseInt(hexValue.substring(0, 2), 16);
					const g = parseInt(hexValue.substring(2, 4), 16);
					const b = parseInt(hexValue.substring(4, 6), 16);
					return { r, g, b };
				}

				const styleElement = document.getElementById("tripzzy-check-availability");
				if ( styleElement ) {
					const computedStyle = getComputedStyle(styleElement);
					const primaryColorHex = computedStyle.getPropertyValue("--tripzzy-primary-color");
					const primaryColorRGB = hexToRGB(primaryColorHex);
					document.documentElement.style.setProperty("--tripzzy-primary-color-rgb", `${primaryColorRGB.r}, ${primaryColorRGB.g}, ${primaryColorRGB.b}`);
				}';
			wp_add_inline_style( 'tripzzy-trips', $inline_styles );
			wp_add_inline_script( 'tripzzy-trips', $inline_scripts );
		}
	}
	ThemeTravelAgency::instance();
}
