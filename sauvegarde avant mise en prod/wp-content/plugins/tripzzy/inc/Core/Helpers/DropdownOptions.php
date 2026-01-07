<?php
/**
 * All Dropdown Options.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

use Tripzzy\Core\Bases\EmailBase;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Seeder\PageSeeder;
use Tripzzy\Core\Taxonomies\PriceCategoryTaxonomy;
use Tripzzy\Core\Taxonomies\TripIncludesTaxonomy;
use Tripzzy\Core\Taxonomies\TripExcludesTaxonomy;
use Tripzzy\Core\Emails\AdminBookingEmail;
use Tripzzy\Core\Emails\CustomerBookingEmail;
use Tripzzy\Core\Emails\AdminEnquiryEmail;
use Tripzzy\Core\Payment\PaymentGateways;
use Tripzzy\Core\Image;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\DropdownOptions' ) ) {

	/**
	 * Our main helper class that provides.
	 *
	 * @since 1.0.0
	 */
	class DropdownOptions {
		/**
		 * Get All DropdownOptions Value.
		 *
		 * @since 1.3.0 added 'image_sizes' to options.
		 * @since 1.2.9 Added tripzzy_filter_dropdown_options filter hook.
		 * @since 1.0.0
		 */
		public static function get() {
			$currency_options      = Currencies::get_dropdown_options();
			$page_settings_options = PageSeeder::get_dropdown_options();
			$page_list             = Page::get_all();
			$countries             = Countries::get_dropdown_options( true );

			$options = array(
				'currency_options'       => $currency_options,
				'fontawesome_options'    => Fontawesome::get_dropdown_options(),
				'price_category_options' => PriceCategoryTaxonomy::get_dropdown_options(),
				'page_settings_options'  => $page_settings_options, // Options for 'Settings > General > Pages'.
				'page_list'              => $page_list,
				'countries'              => $countries,
				'email_tags'             => self::get_email_tags(),
				'price_tags'             => Amount::get_tags(),
				'trips'                  => Trip::get_dropdown_options(),
				'users'                  => User::get_list(),
				'map_types'              => TripMap::get_map_type_options( true ),
				'months'                 => self::get_months_dropdown_options(),
				'weekdays'               => self::get_weekdays_dropdown_options(),
				'grouped_trip_includes'  => TripIncludesTaxonomy::get_grouped_dropdown_options(),
				'all_includes'           => TripIncludesTaxonomy::get_terms(), // without grouping. to get parent id while add new includes.
				'grouped_trip_excludes'  => TripExcludesTaxonomy::get_grouped_dropdown_options(),
				'all_excludes'           => TripExcludesTaxonomy::get_terms(), // without grouping. to get parent id while add new includes.
				'trip_info_list'         => TripInfos::get_enabled_list(), // Not a dropdown option.
				'payment_gateways'       => PaymentGateways::get_dropdown_options(),
				'payment_gateway_fields' => PaymentGateways::get_all_fields(), // All Payment gateway fields.
				'image_sizes'            => Image::get_image_sizes(),
			);
			$options = apply_filters( 'tripzzy_filter_dropdown_options', $options );
			return $options;
		}

		/**
		 * Weekdays Options.
		 *
		 * @return array
		 */
		public static function get_weekdays_dropdown_options() {
			return array(
				array(
					'label' => __( 'Sunday', 'tripzzy' ),
					'value' => 'SU',
				),
				array(
					'label' => __( 'Monday', 'tripzzy' ),
					'value' => 'MO',
				),
				array(
					'label' => __( 'Tuesday', 'tripzzy' ),
					'value' => 'TU',
				),
				array(
					'label' => __( 'Wednesday', 'tripzzy' ),
					'value' => 'WE',
				),
				array(
					'label' => __( 'Thursday', 'tripzzy' ),
					'value' => 'TH',
				),
				array(
					'label' => __( 'Friday', 'tripzzy' ),
					'value' => 'FR',
				),
				array(
					'label' => __( 'Saturday', 'tripzzy' ),
					'value' => 'SA',
				),
			);
		}

		/**
		 * Weekdays Options.
		 *
		 * @return array
		 */
		public static function get_months_dropdown_options() {
			return array(
				array(
					'label' => __( 'January', 'tripzzy' ),
					'value' => 1,
				),
				array(
					'label' => __( 'February', 'tripzzy' ),
					'value' => 2,
				),
				array(
					'label' => __( 'March', 'tripzzy' ),
					'value' => 3,
				),
				array(
					'label' => __( 'April', 'tripzzy' ),
					'value' => 4,
				),
				array(
					'label' => __( 'May', 'tripzzy' ),
					'value' => 5,
				),
				array(
					'label' => __( 'June', 'tripzzy' ),
					'value' => 6,
				),
				array(
					'label' => __( 'July', 'tripzzy' ),
					'value' => 7,
				),
				array(
					'label' => __( 'August', 'tripzzy' ),
					'value' => 8,
				),
				array(
					'label' => __( 'September', 'tripzzy' ),
					'value' => 9,
				),
				array(
					'label' => __( 'October', 'tripzzy' ),
					'value' => 10,
				),
				array(
					'label' => __( 'November', 'tripzzy' ),
					'value' => 11,
				),
				array(
					'label' => __( 'December', 'tripzzy' ),
					'value' => 12,
				),
			);
		}

		/**
		 * Get All email tags.
		 *
		 * @since 1.0.0
		 */
		public static function get_email_tags() {
			$tags = array(
				'admin_booking_email_tags'    => AdminBookingEmail::get_tags(),
				'customer_booking_email_tags' => CustomerBookingEmail::get_tags(),
				'admin_enquiry_email_tags'    => AdminEnquiryEmail::get_tags(),
			);
			return $tags;
		}
	}
}
