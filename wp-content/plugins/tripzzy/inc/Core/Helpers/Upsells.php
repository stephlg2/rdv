<?php
/**
 * Upsell Message Helpers.
 *
 * @since 1.3.4
 * @package tripzzy
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Helpers\Page;

if ( ! class_exists( 'Tripzzy\Core\Helpers\Upsells' ) ) {
	/**
	 * Class For Upsell
	 *
	 * @since 1.3.4
	 */
	class Upsells {
		const URL = 'https://wptripzzy.com/pricing';

		/**
		 * List of Upsells type
		 *
		 * @since 1.3.4
		 * @return array
		 */
		public static function get() {
			$notices = array(
				'seasonal_pricing'    => array(
					'title'       => __( 'Offer dynamic prices for different seasons.', 'tripzzy' ),
					/* translators: 1: Either Upgrade to or Update */
					'tagline'     => __( '%1$s Tripzzy Pro to unlock Seasonal Pricing.', 'tripzzy' ),
					'description' => __( 'Set flexible pricing for specific months, date ranges, or peak/off seasons to maximize bookings and revenue.', 'tripzzy' ),
					'icon'        => 'fa-solid fa-clock',
					'enabled'     => true,
				),
				'payment_gateways'    => array(
					'title'       => __( 'Accept payments with more flexibility.', 'tripzzy' ),
					/* translators: 1: Either Upgrade to or Update */
					'tagline'     => __( '%1$s Tripzzy Pro to get Additional Payment Gateway support.', 'tripzzy' ),
					'description' => __( 'Enable multiple global and local payment gateways to ensure a smoother checkout experience for your customers.', 'tripzzy' ),
					'icon'        => 'fa-solid fa-credit-card',
					'enabled'     => true,
				),
				'utilities'           => array(
					'title'       => __( 'Powerful tools for better control.', 'tripzzy' ),
					/* translators: 1: Either Upgrade to or Update */
					'tagline'     => __( '%1$s Tripzzy Pro to use advanced Utility Features.', 'tripzzy' ),
					'description' => __( 'Access enhanced utilities like Time slot, Trip Exclude Dates, Infos in Itineraries to simplify management.', 'tripzzy' ),
					'icon'        => 'fa-solid fa-gear',
					'enabled'     => true,
				),
				'additional_services' => array(
					'title'       => __( 'Sell more with customizable add-ons.', 'tripzzy' ),
					/* translators: 1: Either Upgrade to or Update */
					'tagline'     => __( '%1$s Tripzzy Pro to enable Additional Services.', 'tripzzy' ),
					'description' => __( 'Offer to add extra services like Rooms, airport transfers, special meals, or travel insurance to personalize each booking.', 'tripzzy' ),
					'icon'        => 'fa-solid fa-suitcase-rolling',
					'enabled'     => true,
				),
				'group_discount'      => array(
					'title'       => __( 'Encourage group travel with automated discounts.', 'tripzzy' ),
					/* translators: 1: Either Upgrade to or Update */
					'tagline'     => __( '%1$s Tripzzy Pro to activate Group Discount options.', 'tripzzy' ),
					'description' => __( 'Set up flexible group-based pricing to attract families, teams, and large bookings.', 'tripzzy' ),
					'icon'        => 'fa-solid fa-users',
					'enabled'     => true,
				),
				'form'                => array(
					'title'       => __( 'Customize your booking and enquiry forms with ease.', 'tripzzy' ),
					/* translators: 1: Either Upgrade to or Update */
					'tagline'     => __( '%1$s Tripzzy Pro to unlock the Form Builder and add custom fields to your checkout and enquiry forms.', 'tripzzy' ),
					'description' => __( 'Add, or remove form fields to collect exactly the information you need from travelers — from custom questions to special requirements. Perfect for creating a more personalized booking experience.', 'tripzzy' ),
					'enabled'     => true,
				),

				'pro'                 => array(
					'title'       => __( 'Unlock more features with Tripzzy Pro!', 'tripzzy' ),
					/* translators: 1: Either Upgrade to or Update */
					'tagline'     => __( '%1$s Tripzzy Pro to get all benifit.', 'tripzzy' ),
					'description' => __( 'Upgrade now to access More Payment Gateways, additional options, and premium integrations.', 'tripzzy' ),
					'icon'        => 'fa-solid fa-lock-open',
					'enabled'     => true,
				),
			);
			return apply_filters( 'tripzzy_filter_upsell_notices', $notices );
		}
		/**
		 * Handle External Redirects.
		 *
		 * @since 1.3.4
		 * @return void
		 */
		public static function handle_external_redirects() {
			if ( Page::is( 'go-tripzzy-pro', true ) ) {
				wp_redirect( self::URL ); // phpcs:ignore
				die;
			}
		}
	}
}
