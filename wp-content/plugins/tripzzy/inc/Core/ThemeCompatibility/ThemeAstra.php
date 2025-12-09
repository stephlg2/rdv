<?php
/**
 * Astra Theme Compatibility.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\ThemeCompatibility;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;

if ( ! class_exists( 'Tripzzy\Core\ThemeCompatibility\ThemeAstra' ) ) {
	/**
	 * Astra Theme Compatibility.
	 *
	 * @since 1.0.0
	 */
	class ThemeAstra {
		use SingletonTrait;

		/**
		 * Constructor.
		 */
		public function __construct() {
			// For Theme specific inline Scripts and Styles.
			add_action( 'wp_enqueue_scripts', array( $this, 'theme_scripts' ), 20 );

			/**
			 * Add container before sticky tab in single trip.
			 *
			 * @since 1.0.6
			 */
			add_action( 'tripzzy_before_sticky_tab', array( $this, 'container_open_tag' ) );
			add_action( 'tripzzy_after_sticky_tab', array( $this, 'container_close_tag' ) );
		}

		/**
		 * Add Theme specific Scripts and Styles.
		 *
		 * @return void
		 */
		public function theme_scripts() {
			$inline_styles  = '
				:root{
					--tripzzy-primary-color:var(--ast-global-color-0);
				}
				.ast-container .tripzzy-container{max-width:100%; padding:0}
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

		/**
		 * Container open tag.
		 *
		 * @since 1.0.6
		 * @return void
		 */
		public function container_open_tag() {
			?>
			<div class="ast-container">
			<?php
		}

		/**
		 * Container close tag.
		 *
		 * @since 1.0.6
		 * @return void
		 */
		public function container_close_tag() {
			?>
			</div>
			<?php
		}
	}
	ThemeAstra::instance();
}
