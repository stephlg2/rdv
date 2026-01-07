<?php
/**
 * Settings.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

use Tripzzy\Core\Emails\AdminBookingEmail;
use Tripzzy\Core\Emails\CustomerBookingEmail;
use Tripzzy\Core\Emails\AdminEnquiryEmail;
use Tripzzy\Core\Emails\CustomerBookingCancelationEmail;
use Tripzzy\Core\Emails\CustomerBookingOnHoldEmail;
use Tripzzy\Core\Emails\CustomerBookingRefundedEmail;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Helpers\TripInfos;
use Tripzzy\Core\Payment\PaymentGateways;
use Tripzzy\Core\Seeder\PageSeeder;
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\Settings' ) ) {

	/**
	 * Our main helper class that provides.
	 *
	 * @since 1.0.0
	 */
	class Settings {
		/**
		 * Get All Settings Value.
		 *
		 * @param Mixed $key Either Array or string.
		 * @since 1.0.0
		 */
		public static function get( $key = '' ) {
			$default  = self::default_settings(); // Default Values.
			$settings = MetaHelpers::get_option( 'settings', array() ); // Saved Values.
			$settings = array_merge( $default, $settings );

			if ( isset( $settings['trip_infos'] ) ) {
				$settings['trip_infos'] = TripInfos::get_all_list();
			}

			$settings = apply_filters( 'tripzzy_filter_settings', $settings );

			if ( isset( $settings['payment_gateways'] ) && empty( $settings['payment_gateways'] ) ) {

				// Initial data init issue.
				// fixes. unable to save payment data when save empty initially [Js part].
				// but need to typecast in GatewayTrait as array becasue it need to be in array [php part].
				$settings['payment_gateways'] = (object) $settings['payment_gateways'];
			}

			// Return all result if no key.
			if ( ! $key ) {
				return $settings;
			}

			if ( is_array( $key ) ) {
				if ( empty( $key ) ) {
					return;
				}
				// Return Specified option as per key [array].
				$options = array();
				foreach ( $key as $k ) {
					if ( isset( $settings[ $k ] ) ) {
						$options[ $k ] = $settings[ $k ];
					}
				}
			} else {
				// Return Specified option as per key [string].
				$options = '';

				if ( isset( $settings[ $key ] ) ) {
					$options = $settings[ $key ];
				}
			}
			return $options;
		}

		/**
		 * Update All Settings Value.
		 *
		 * @param object $raw_data Request Payload data.
		 * @since 1.0.0
		 */
		public static function update( $raw_data = array() ) {
			if ( ! $raw_data ) {
				// This Raw data is sanitized later in the loop below.
				$raw_data = Request::get_payload( true );
			}

			if ( empty( $raw_data ) ) {
				return;
			}

			$raw_data = (array) $raw_data;
			$settings = self::get(); // Settings.
			$default  = self::default_settings();
			unset( $settings['is_data_changed'], $settings['is_requesting'] ); // Unset if accidently saved.
			foreach ( $default as $key => $value ) {
				if ( isset( $raw_data[ $key ] ) ) {

					$wp_kses_post      = false;
					$wp_kses_post_list = self::required_wp_keses_settings();
					if ( in_array( $key, $wp_kses_post_list, true ) ) {
						$wp_kses_post = true;
					}
					$settings[ $key ] = Request::sanitize_data( $raw_data[ $key ], $wp_kses_post );
					if ( 'sticky_tab_items' === $key ) { // Quick solution to add slash in saved namespace.
						$items     = $settings[ $key ]; // already sanitize so already converted all object into array via sanitization method.
						$raw_items = $raw_data[ $key ];
						foreach ( $items as $k => $item ) {
							$item['render_class'] = sanitize_text_field( $raw_items[ $k ]->render_class ?? '' );
							$items[ $k ]          = $item;
						}
						$settings[ $key ] = $items;
					}
				}
			}

			/**
			 * Filter settings data before save.
			 *
			 * @since 1.0.0
			 */
			$settings = apply_filters( 'tripzzy_filter_before_save_settings', $settings, $raw_data );

			MetaHelpers::update_option( 'settings', $settings );
			/**
			 * Filter settings data after save.
			 *
			 * @since 1.0.0
			 */
			$settings = apply_filters( 'tripzzy_filter_after_save_settings', $settings, $raw_data );
			return $settings;
		}
		/**
		 * Get All Default Settings Value.
		 *
		 * @since 1.3.0 Added filter_duration_in, enable_trip_slider and trip_slider_image_size.
		 * @since 1.2.7 Default modules seperated.
		 * @since 1.2.3 Added enable_schema, enable_itinerary_schema, and enable_faqs_schema as default options.
		 * @since 1.2.2 Added enable_overlay, and enable_lightbox as default options.
		 * @since 1.2.1 Added allow_decimal_ratings, and emoji_on_ratings as default options.
		 * @since 1.2.0 Added hide_coupon_on_checkout, coupon_position as default options.
		 * @since 1.1.6 Payment description added.
		 * @since 1.0.2 Smooth scroll and sticky tab option added.
		 * @since 1.0.0
		 */
		public static function default_settings() {
			$settings = array(
				'currency'                 => 'USD',
				'use_currency_code'        => false,
				'amount_display_format'    => '%CURRENCY_SYMBOL%%DISPLAY_AMOUNT%',
				'thousand_separator'       => ',',
				'decimal_separator'        => '.',
				'number_of_decimals'       => '2',
				'faqs'                     => array(),
				'enable_itinerary_date'    => false,
				'enable_itinerary_time'    => false,
				'enable_trip_difficulties' => true,
				'trip_difficulties'        => self::trip_difficulties_defaults(),
				'enable_trip_features'     => true,
				'trip_features'            => self::trip_features_defaults(),
				'enable_trip_slider'       => true,
				'trip_slider_image_size'   => 'tripzzy_slider_thumbnail',
				// Map Settings.
				'enable_google_map'        => true,
				'google_map_api_key'       => '',
				// Filters.
				'show_filter_button'       => true,
				'filter_duration_in'       => 'days',
				'trip_infos'               => TripInfos::default_data(),

				// Trip Tabs. @since 1.0.2.
				'enable_sticky_tab'        => true,
				'sticky_tab_items'         => TripStickyTab::get_default_sticky_tab_items(),
				'sticky_tab_position'      => 0,

				// Payment.
				'test_mode'                => false,
				'default_gateway'          => 'book_now_pay_later',
				'enabled_payment_gateways' => PaymentGateways::get_default_active_gateways(),
				'payment_gateways'         => (object) array(), // All Payment gateway fields.
				'payment_description'      => __( 'Payment for tripzzy', 'tripzzy' ), // @since 1.1.6
				// Checkout.
				'create_user_on_booking'   => true,
				'hide_coupon_on_checkout'  => false,
				'coupon_position'          => 'left', // left || sidebar.
				'modules'                  => self::default_modules(), // value will added from modules.
				// Misc. @since 1.0.2.
				'enable_smooth_scroll'     => true,
				'smooth_scroll_offset'     => 70,
				'smooth_scroll_duration'   => 1000,
				'allow_decimal_ratings'    => true,
				'emoji_on_ratings'         => true,
				'enable_overlay'           => true,
				'enable_lightbox'          => true,
				'enable_schema'            => true,
				'enable_itinerary_schema'  => true,
				'enable_faqs_schema'       => true,
			);

			// Add email keys.
			$emails   = self::email_defaults();
			$settings = wp_parse_args( $emails, $settings );

			// hidded form fields id.
			$form_data = self::form_data();
			$form_ids  = array_map(
				function () {
					return '';
				},
				$form_data
			);
			if ( is_array( $form_ids ) && count( $form_ids ) > 0 ) {
				$settings = wp_parse_args( $form_ids, $settings );
			}
			// Add Tripzzy pages keys.
			$page_data = PageSeeder::get_pages();
			$page_keys = array_map(
				function ( $item ) {
					return array( $item['settings_key'] => '' );
				},
				$page_data
			);
			// Combine the arrays into a single array.
			$page_keys = call_user_func_array( 'array_merge', $page_keys );
			$settings  = wp_parse_args( $page_keys, $settings );
			return apply_filters( 'tripzzy_filter_default_settings', $settings );
		}

		/**
		 * List of meta key which data need to save as wp_kses to support tags and shortcodes.
		 *
		 * @since 1.0.0
		 * @since 1.2.5 Added customer_booking_on_hold_notification_content key.
		 */
		private static function required_wp_keses_settings() {
			$list = array(
				'customer_booking_notification_content',
				'customer_booking_cancelation_notification_content',
				'customer_booking_on_hold_notification_content',
				'customer_booking_refunded_notification_content',
				'admin_booking_notification_content',
				'admin_enquiry_notification_content',
			);
			return $list;
		}

		/**
		 * Array data. To fetch Form class as per provided key.
		 */
		public static function form_data() {
			// Form Page id = `$field_type}_id`.
			// form_page_id => respectie form class.
			$data = array(
				'checkout_form_id' => '\Tripzzy\Core\Forms\CheckoutForm',
				'enquiry_form_id'  => '\Tripzzy\Core\Forms\EnquiryForm',
			);
			return $data;
		}

		/**
		 * Default email Fields.
		 *
		 * @since 1.0.0
		 * @since 1.1.8 Added Booking On Hold email subject and content.
		 * @since 1.2.7 Added admin_from_name, and customer_from_name.
		 */
		private static function email_defaults() {
			$booking_id_tag = '%BOOKING_ID%';
			$enquiry_id_tag = '%ENQUIRY_ID%';
			$domain_name    = tripzzy_domain_name();
			$from_email     = 'localhost' === $domain_name ? get_option( 'admin_email' ) : sprintf( 'wordpress@%s', $domain_name );
			$defaults       = array(
				'disable_admin_notification'            => false,
				'disable_enquiry_notification'          => false,
				'disable_customer_notification'         => false,
				'admin_from_name'                       => '',
				'admin_from_email'                      => $from_email,
				'customer_from_name'                    => '',
				'customer_from_email'                   => $from_email,
				'admin_to_emails'                       => '',
				/* Translators: 1: Email Tag  */
				'admin_booking_notification_subject'    => sprintf( __( 'New Booking (#%s)', 'tripzzy' ), $booking_id_tag ),
				'admin_booking_notification_content'    => AdminBookingEmail::email_content(),
				/* Translators: 1: Email Tag  */
				'customer_booking_notification_subject' => sprintf( __( 'New Booking (#%s)', 'tripzzy' ), $booking_id_tag ),
				'customer_booking_notification_content' => CustomerBookingEmail::email_content(),
				/* Translators: 1: Email Tag  */
				'customer_booking_cancelation_notification_subject' => sprintf( __( 'Booking Canceled (#%s)', 'tripzzy' ), $booking_id_tag ),
				'customer_booking_cancelation_notification_content' => CustomerBookingCancelationEmail::email_content(),
				/* Translators: 1: Email Tag  */
				'customer_booking_on_hold_notification_subject' => sprintf( __( 'Booking on Hold (#%s)', 'tripzzy' ), $booking_id_tag ),
				'customer_booking_on_hold_notification_content' => CustomerBookingOnHoldEmail::email_content(),
				/* Translators: 1: Email Tag  */
				'customer_booking_refunded_notification_subject' => sprintf( __( 'Booking Refunded (#%s)', 'tripzzy' ), $booking_id_tag ),
				'customer_booking_refunded_notification_content' => CustomerBookingRefundedEmail::email_content(),
				/* Translators: 1: Email Tag  */
				'admin_enquiry_notification_subject'    => sprintf( __( 'New Enquiry (#%s)', 'tripzzy' ), $enquiry_id_tag ),
				'admin_enquiry_notification_content'    => AdminEnquiryEmail::email_content(),
			);
			return $defaults;
		}

		/**
		 * Default trip difficulties.
		 *
		 * @since 1.0.0
		 */
		private static function trip_difficulties_defaults() {
			$defaults = array(
				array(
					'label'       => __( 'Easy', 'tripzzy' ),
					'description' => 'Short trips for 1-2 days. like hiking, night stay packages',
				),
				array(
					'label'       => __( 'Medium', 'tripzzy' ),
					'description' => 'Medium trip for 3-5 days. Weekend holiday trips',
				),
				array(
					'label'       => __( 'Hard', 'tripzzy' ),
					'description' => 'Trip with 7-12 days. like vacation trips',
				),

				array(
					'label'       => __( 'Extreme', 'tripzzy' ),
					'description' => 'Trip with more than 2 weeks. like mountaininig etc.',
				),
			);
			return $defaults;
		}

		/**
		 * Default trip features.
		 *
		 * @since 1.0.0
		 */
		private static function trip_features_defaults() {
			$defaults = array(
				array(
					'label'       => __( 'Best price guaranteed.', 'tripzzy' ),
					'description' => '',
				),
				array(
					'label'       => __( 'No booking fees.', 'tripzzy' ),
					'description' => '',
				),
				array(
					'label'       => __( 'Professional local guide.', 'tripzzy' ),
					'description' => '',
				),
			);
			return $defaults;
		}

		/**
		 * Default modules.
		 *
		 * @since 1.2.7
		 * @return array
		 */
		public static function default_modules() {
			return apply_filters( 'tripzzy_filter_default_modules', array() );
		}
	}
}
