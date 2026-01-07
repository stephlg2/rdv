<?php
/**
 * Trips.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\Amount' ) ) {

	/**
	 * Our main helper class that provides.
	 *
	 * @since 1.0.0
	 */
	class Amount {

		/**
		 * All tags to format the amount as per tags provided.
		 *
		 * @return array
		 */
		public static function get_tags() {
			$tags = array(
				'%CURRENCY_CODE%'   => __( 'Display Currency Code.', 'tripzzy' ),
				'%CURRENCY_SYMBOL%' => __( 'Display Currency Symbol.', 'tripzzy' ),
				'%AMOUNT%'          => __( 'Display Amount figure without any formating.', 'tripzzy' ),
				'%DISPLAY_AMOUNT%'  => __( 'Display amount with amount format as per settings.', 'tripzzy' ),
			);
			return $tags;
		}

		/**
		 * Returns formated amount value.
		 *
		 * @param number $amount Amount value to convert it in proper format.
		 * @return mixed
		 */
		public static function format( $amount = 0 ) {
			$settings = Settings::get();
			$ts       = $settings['thousand_separator'];
			$ds       = $settings['decimal_separator'];
			$nd       = $settings['number_of_decimals'];
			return number_format( (float) $amount, $nd, $ds, $ts );
		}

		/**
		 * Returns formated amount along with currency as per display format.
		 *
		 * @param number $amount Amount value to convert it in proper format.
		 * @param bool   $has_echo Whether print or return the value.
		 * @return mixed
		 */
		public static function display( $amount = 0, $has_echo = false ) {
			$settings              = Settings::get();
			$amount_display_format = $settings['amount_display_format'];
			$currency_code         = $settings['currency'];
			$currency_symbol       = Currencies::get_symbol( $currency_code );

			$amount_tags = array(
				'%CURRENCY_CODE%'   => $currency_code,
				'%CURRENCY_SYMBOL%' => $currency_symbol,
				'%AMOUNT%'          => $amount,
				'%DISPLAY_AMOUNT%'  => self::format( $amount ),
			);
			$amount      = str_replace( array_keys( $amount_tags ), array_values( $amount_tags ), $amount_display_format );
			if ( $has_echo ) {
				echo wp_kses_post( $amount );
			}
			return $amount;
		}
	}
}
