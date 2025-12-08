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
			add_action( 'wp_footer', array( $this, 'theme_scripts' ) );
		}

		/**
		 * Add Theme specific Scripts and Styles.
		 *
		 * @return void
		 */
		public function theme_scripts() {
			?>
			<style>
				:root{
					--tripzzy-primary-color:var(--primary-color);
				}
			</style>
			<script>
				// Function to convert hexadecimal color code to RGB values
				function hexToRGB(hex) {
					const hexValue = hex.replace('#', '');
					const r = parseInt(hexValue.substring(0, 2), 16);
					const g = parseInt(hexValue.substring(2, 4), 16);
					const b = parseInt(hexValue.substring(4, 6), 16);
					return { r, g, b };
				}

				// Get the computed style of an element to access the CSS variable
				const sampleElement = document.getElementById('tripzzy-check-availability');
				if ( sampleElement ) {
					const computedStyle = getComputedStyle(sampleElement);

					// Get the hexadecimal color code stored in the CSS variable --tripzzy-primary-color
					const primaryColorHex = computedStyle.getPropertyValue('--tripzzy-primary-color');

					// Convert the primaryColorHex to RGB values
					const primaryColorRGB = hexToRGB(primaryColorHex);

					// Assign the RGB values to the CSS variable --tz-primary-color-rgb
					document.documentElement.style.setProperty('--tripzzy-primary-color-rgb', `${primaryColorRGB.r}, ${primaryColorRGB.g}, ${primaryColorRGB.b}`);
				}
			</script>
			<?php
		}
	}
	ThemeTravelAgency::instance();
}
