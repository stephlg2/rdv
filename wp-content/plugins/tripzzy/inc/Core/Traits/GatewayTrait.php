<?php
/**
 * Gateway Trait.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Traits;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\MetaHelpers;

/**
 * Define Trait.
 */
trait GatewayTrait {
	/**
	 * Get geteway data.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public static function geteway_data() {
		$gateway_name  = self::$payment_gateway;
		$settings      = self::$settings;
		$gateways_data = (array) $settings['payment_gateways'];

		// Put data in array.
		$data = array( 'test_mode' => ! ! $settings['test_mode'] );
		if ( ! $gateway_name ) {
			return $data;
		}
		$data['config'] = $gateways_data[ $gateway_name ] ?? array();
		return $data;
	}
}
