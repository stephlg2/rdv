<?php
/**
 * Payment Gateway : Book Now Pay Later.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Payment\PaymentGateways;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Payment\PaymentGateways; // Base.
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Traits\GatewayTrait;

if ( ! class_exists( 'Tripzzy\Core\Payment\PaymentGateways\BookNowPayLater' ) ) {
	/**
	 * Payment Gateway : Book Now Pay Later.
	 *
	 * @since 1.0.0
	 */
	class BookNowPayLater extends PaymentGateways {
		use SingletonTrait;
		use GatewayTrait;

		/**
		 * Payment Gateway type.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $payment_gateway = 'book_now_pay_later'; // key/slug.

		/**
		 * Payment Gateway name.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $payment_gateway_title; // name initialized from constructor.

		/**
		 * Settings
		 *
		 * @since 1.0.0
		 * @var array
		 */
		protected static $settings;

		/**
		 * Constructor.
		 */
		public function __construct() {
			self::$payment_gateway_title = __( 'Book Now Pay Later', 'tripzzy' );
			self::$settings              = Settings::get(); // for traits.
			add_filter( 'tripzzy_filter_payment_gateways_args', array( $this, 'init_args' ) );
		}

		/**
		 * Payment gateway arguments.
		 *
		 * @since 1.0.0
		 * @since 1.1.8 Description field added.
		 */
		protected static function payment_gateway_args() {
			$args = array(
				'title'  => self::$payment_gateway_title,
				'name'   => self::$payment_gateway,
				'fields' => array(
					'enabled'     => array(
						'name'  => 'enabled',
						'label' => __( 'Enabled', 'tripzzy' ),
						'value' => true,
					),
					'description' => array(
						'name'  => 'description',
						'label' => __( 'Description', 'tripzzy' ),
						'type'  => 'textarea',
						'value' => __( 'Book Your trips without payment.', 'tripzzy' ),
					),
				),

			);
			return $args;
		}
	}
}
