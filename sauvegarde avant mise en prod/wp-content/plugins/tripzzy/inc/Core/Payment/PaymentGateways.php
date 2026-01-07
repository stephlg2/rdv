<?php
/**
 * Base Class For Payment Gateways.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Payment;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Forms\Form;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Helpers\Settings;

if ( ! class_exists( 'Tripzzy\Core\Payment\PaymentGateways' ) ) {
	/**
	 * Base Class For Payment Gateways.
	 *
	 * @since 1.0.0
	 */
	class PaymentGateways {
		use SingletonTrait;

		/**
		 * An array of payment gateways.
		 *
		 * @var array
		 * @since 1.0.0
		 */
		private static $payment_gateways = array(
			'monetico' => array(
				'title'         => 'Monetico',
				'wrapper_class' => 'tab-monetico',
				'description'   => 'Payez vos voyages via Monetico.',
				'fields'        => array(
					'enabled' => array(
						'type'  => 'checkbox',
						'label' => 'Activer Monetico',
						'value' => false,
					),
					'merchant_id' => array(
						'type'        => 'text',
						'label'       => 'Identifiant marchand',
						'placeholder' => 'Ex: 123456789',
					),
					'api_key' => array(
						'type'        => 'text',
						'label'       => 'Clé API',
						'placeholder' => 'Entrez votre clé API Monetico',
					),
					'test_mode' => array(
						'type'  => 'checkbox',
						'label' => 'Mode test',
						'value' => true,
					),
				),
			),
		);

		/**
		 * Gateway scripts.
		 *
		 * @var array
		 */
		private static $gateway_scripts = array();

		/**
		 * Return all payment gateways arguments.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public static function get_all() {
			self::$payment_gateways = apply_filters( 'tripzzy_filter_payment_gateways_args', self::$payment_gateways );
			return self::$payment_gateways;
		}


		/**
		 * Return all payment gateways scripts.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public static function get_gateway_scripts() {
			self::$gateway_scripts = apply_filters( 'tripzzy_filter_gateway_scripts', self::$gateway_scripts );
			return self::$gateway_scripts;
		}

		/**
		 * Get all Payment gateways list to display in dropdown.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public static function get_dropdown_options() {
			$gateways = self::get_all();

			$options = array();

			if ( is_array( $gateways ) && count( $gateways ) > 0 ) {
				foreach ( $gateways as $key => $gateway ) {
					$options[] = array(
						'label' => $gateway['title'],
						'value' => $key,
					);
				}
			}
			return $options;
		}

		/**
		 * Get all Payment gateways input fields.
		 *
		 * @since 1.0.0
		 * @since 1.2.4 Added wrapper_class in gateway data.
		 * @return array
		 */
		public static function get_all_fields() {
			$gateways = self::get_all();

			$all_fields = array();

			if ( is_array( $gateways ) && count( $gateways ) > 0 ) {
				foreach ( $gateways as $key => $gateway ) {
					$all_fields[] = array(
						'gateway'       => $key,
						'title'         => $gateway['title'],
						'wrapper_class' => $gateway['wrapper_class'] ?? '',
						'description'   => isset( $gateway['description'] ) ? $gateway['description'] : '',
						'fields'        => array_values( $gateway['fields'] ),
					);
				}
			}
			return $all_fields;
		}
		/**
		 * To add default enabled gateways in the settings default values.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public static function get_default_active_gateways() {
			$gateways = self::get_all();

			$active_gateways = array();

			if ( is_array( $gateways ) && count( $gateways ) > 0 ) {
				foreach ( $gateways as $key => $gateway ) {
					$enabled_field = isset( $gateway['fields']['enabled'] ) && is_array( $gateway['fields']['enabled'] ) ? $gateway['fields']['enabled'] : array();
					if ( isset( $enabled_field['value'] ) && $enabled_field['value'] ) {
						$active_gateways[] = $key;
					}
				}
			}
			return $active_gateways;
		}

		/**
		 * Init Payment gateway arguments.
		 *
		 * @param array $payment_gateway_args Array arguments.
		 * @since 1.0.0
		 */
		public function init_args( $payment_gateway_args ) {
			$payment_gateway_args[ static::$payment_gateway ] = static::payment_gateway_args();
			return $payment_gateway_args;
		}

		/**
		 * Init Payment gateway arguments.
		 *
		 * @param array $gateway_scripts Array arguments.
		 * @since 1.0.0
		 */
		public function init_gateway_scripts( $gateway_scripts ) {
			$gateway_scripts[ static::$payment_gateway ] = static::gateway_scripts();
			return $gateway_scripts;
		}

		/**
		 * Check whether payment gateway enabled or not. Need to enable atleaset one gateway to return true.
		 *
		 * @since 1.0.0
		 * @return bool
		 */
		public static function has_enabled_gateway() {
			$settings         = Settings::get();
			$gateways         = array_keys( self::get_all() ); // All available gateways.
			$enabled_gateways = $settings['enabled_payment_gateways'];
			$has_enabled      = false;
			foreach ( $gateways as $gateway ) {
				if ( in_array( $gateway, $enabled_gateways, true ) ) {
					$has_enabled = true;
					break;
				}
			}
			return $has_enabled;
		}

		/**
		 * Checks whether current payment gateway is default gateway or not.
		 *
		 * @param string $gateway_name Gateway name.
		 * @since 1.0.0
		 * @since 1.1.6 Changed logic of $enabled_gateways and $default_gateway.
		 * @since 1.2.9 Fixed undefined index error in case of active gateway deactivated.
		 * @return bool
		 */
		public static function is_default_gateway( $gateway_name = 'book_now_pay_later' ) {
			$settings         = Settings::get();
			$gateways         = array_keys( self::get_all() ); // All available gateways.
			$enabled_gateways = $settings['enabled_payment_gateways'];

			$enabled_gateways = array_filter(
				$gateways,
				function ( $gateway ) use ( $enabled_gateways ) {
					return in_array( $gateway, $enabled_gateways, true );
				}
			);
			$enabled_gateways = array_values( $enabled_gateways );

			$default_gateway = $settings['default_gateway'];
			if ( ! in_array( $default_gateway, $enabled_gateways, true ) ) {
				$default_gateway = $enabled_gateways[0];
			}
			$is_default = false;
			if ( in_array( $gateway_name, $enabled_gateways, true ) && $gateway_name === $default_gateway ) {
				$is_default = true;
			}
			return $is_default;
		}

		/**
		 * Checks whether current setup is in test mode or not.
		 *
		 * @since 1.0.0
		 * @return bool
		 */
		public static function is_test_mode() {
			$settings = Settings::get();
			return (bool) $settings['test_mode'];
		}

		/**
		 * Return Enabled gateways list along with data.
		 *
		 * @since 1.0.0
		 * @since 1.1.8 Payment Description added.
		 * @return array
		 */
		public static function get_enabled_gateways() {
			$settings         = Settings::get();
			$all_gateways     = self::get_all(); // All available gateways.
			$gateways_scripts = self::get_gateway_scripts();
			$enabled_gateways = $settings['enabled_payment_gateways'];

			$gateways_data = (array) $settings['payment_gateways'];

			$gateways = array();
			foreach ( $enabled_gateways as $gateway_name ) {
				$data = $gateways_data[ $gateway_name ] ?? array();

				if ( isset( $all_gateways[ $gateway_name ] ) ) {
					$gateway                = $all_gateways[ $gateway_name ];
					$gateway['scripts']     = $gateways_scripts[ $gateway_name ] ?? array();
					$gateway['description'] = $data['description'] ?? '';
					$gateways[]             = $gateway;
				}
			}
			return $gateways;
		}
	}
}
