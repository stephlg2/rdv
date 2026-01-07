<?php
/**
 * Tripzzy Checkout Form.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Forms;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\FormBase;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\Countries;
use Tripzzy\Core\Helpers\UserProfile;

if ( ! class_exists( 'Tripzzy\Core\Forms\CheckoutForm' ) ) {
	/**
	 * Tripzzy Checkout Form Class.
	 *
	 * @since 1.0.0
	 */
	class CheckoutForm extends FormBase {

		/**
		 * Current form id. Required.
		 */
		public static function get_form_id() {
			return Settings::get( 'checkout_form_id' ); // Key name must be same as settings default fields.
		}

		/**
		 * Default fields.
		 *
		 * @since 1.0.0
		 * @since 1.1.9 Fixed Profile data not being saved in dashboard.
		 * @since 1.2.0 Fixed phone and email field empty in checkout though filled in dashboard profile.
		 */
		protected static function default_fields() {

			// Billing Infos.
			$billing_first_name = '';
			$billing_last_name  = '';
			$billing_country    = '';
			$billing_address_1  = '';
			$billing_address_2  = '';
			$billing_city       = '';
			$billing_state      = '';
			$billing_postcode   = '';
			$billing_phone      = '';
			$billing_email      = '';
			$user_id            = get_current_user_id();
			if ( $user_id && ! is_admin() ) { // Do not set value for admin side otherwise this will set default values as per current user in the form.
				$profile            = new UserProfile( $user_id );
				$profile_info       = $profile->get();
				$billing_first_name = $profile_info['first_name'] ?? '';
				$billing_last_name  = $profile_info['last_name'] ?? '';
				$billing_country    = $profile_info['billing_country'] ?? '';
				$billing_address_1  = $profile_info['billing_address_1'] ?? '';
				$billing_address_2  = $profile_info['billing_address_2'] ?? '';
				$billing_city       = $profile_info['billing_city'] ?? '';
				$billing_state      = $profile_info['billing_state'] ?? '';
				$billing_postcode   = $profile_info['billing_postcode'] ?? '';
				$billing_phone      = $profile_info['phone'] ?? '';
				$billing_email      = $profile_info['user_email'] ?? '';
			}
			$billing_info_fields = array(
				array(
					'type'          => 'text',
					'label'         => __( 'First Name', 'tripzzy' ),
					'name'          => 'billing_first_name',
					'id'            => 'billing-first-name',
					'class'         => 'billing-first-name',
					'placeholder'   => __( 'Your first name', 'tripzzy' ),
					'required'      => true,
					'priority'      => 10,
					'value'         => $billing_first_name,
					// Additional configurations.
					'is_new'        => false, // Whether it is new field just recently added or not? Always Need to set false for default fields.
					'is_default'    => true, // Whether it is Default field or not.
					'enabled'       => true, // soft enable. this field can be disabled.
					'force_enabled' => true, // You can not disable if this set to true.
				),

				array(
					'type'          => 'text',
					'label'         => __( 'Last Name', 'tripzzy' ),
					'name'          => 'billing_last_name',
					'id'            => 'billing-last-name',
					'class'         => 'billing-last-name',
					'placeholder'   => __( 'Your last name', 'tripzzy' ),
					'required'      => true,
					'priority'      => 20,
					'value'         => $billing_last_name,
					// Additional configurations.
					'is_new'        => false, // Whether it is new field just recently added or not? Always Need to set false for default fields.
					'is_default'    => true, // Whether it is Default field or not.
					'enabled'       => true, // soft enable. this field can be disabled.
					'force_enabled' => true, // You can not disable if this set to true.
				),
				array(
					'type'          => 'country_dropdown',
					'label'         => __( 'Country / Region', 'tripzzy' ),
					'name'          => 'billing_country',
					'id'            => 'billing-country',
					'class'         => 'billing-country',
					'placeholder'   => __( 'Select country', 'tripzzy' ),
					'required'      => true,
					'priority'      => 30,
					'value'         => $billing_country,
					// Additional configurations.
					'is_new'        => false,
					'is_default'    => true,
					'enabled'       => true,
					'force_enabled' => false,
				),

				array(
					'type'          => 'text',
					'label'         => __( 'Street address', 'tripzzy' ),
					'name'          => 'billing_address_1',
					'id'            => 'billing-address-1',
					'class'         => 'billing-address-1',
					'placeholder'   => __( 'Your address', 'tripzzy' ),
					'required'      => true,
					'priority'      => 40,
					'value'         => $billing_address_1,
					// Additional configurations.
					'is_new'        => false,
					'is_default'    => true,
					'enabled'       => true,
					'force_enabled' => false,
				),

				array(
					'type'          => 'text',
					'label'         => '',
					'name'          => 'billing_address_2',
					'id'            => 'billing-address-2',
					'class'         => 'billing-address-2',
					'placeholder'   => __( 'Your address 2', 'tripzzy' ),
					'required'      => false,
					'priority'      => 50,
					'value'         => $billing_address_2,
					// Additional configurations.
					'is_new'        => false,
					'is_default'    => true,
					'enabled'       => true,
					'force_enabled' => false,
				),

				array(
					'type'          => 'text',
					'label'         => __( 'Town / City', 'tripzzy' ),
					'name'          => 'billing_city',
					'id'            => 'billing-city',
					'class'         => 'billing-city',
					'placeholder'   => __( 'Your city', 'tripzzy' ),
					'required'      => true,
					'priority'      => 60,
					'value'         => $billing_city,
					// Additional configurations.
					'is_new'        => false,
					'is_default'    => true,
					'enabled'       => true,
					'force_enabled' => false,
				),

				array(
					'type'          => 'text',
					'label'         => __( 'State / Zone', 'tripzzy' ),
					'name'          => 'billing_state',
					'id'            => 'billing-state',
					'class'         => 'billing-state',
					'placeholder'   => __( 'Your state', 'tripzzy' ),
					'required'      => true,
					'priority'      => 70,
					'value'         => $billing_state,
					// Additional configurations.
					'is_new'        => false,
					'is_default'    => true,
					'enabled'       => true,
					'force_enabled' => false,
				),

				array(
					'type'          => 'text',
					'label'         => __( 'Postcode / ZIP (optional)', 'tripzzy' ),
					'name'          => 'billing_postcode',
					'id'            => 'billing-postcode',
					'class'         => 'billing-postcode',
					'placeholder'   => __( 'Your postcode', 'tripzzy' ),
					'required'      => false,
					'priority'      => 80,
					'value'         => $billing_postcode,
					// Additional configurations.
					'is_new'        => false,
					'is_default'    => true,
					'enabled'       => true,
					'force_enabled' => false,
				),

				array(
					'type'          => 'tel',
					'label'         => __( 'Phone', 'tripzzy' ),
					'name'          => 'billing_phone',
					'id'            => 'billing-phone',
					'class'         => 'billing-phone',
					'placeholder'   => __( 'Your phone', 'tripzzy' ),
					'required'      => false,
					'priority'      => 50,
					'value'         => $billing_phone,
					// Additional configurations.
					'is_new'        => false,
					'is_default'    => true,
					'enabled'       => true,
					'force_enabled' => false,
				),
				array(
					'type'          => 'email',
					'label'         => __( 'Email', 'tripzzy' ),
					'name'          => 'billing_email',
					'id'            => 'billing-email',
					'class'         => 'billing-email',
					'placeholder'   => __( 'Your email', 'tripzzy' ),
					'required'      => true,
					'priority'      => 100,
					'value'         => $billing_email,
					// Additional configurations.
					'is_new'        => false,
					'is_default'    => true,
					'enabled'       => true,
					'force_enabled' => true,
				),

			);

			$fields = array(
				'billing_info'           =>
				array(
					'type'          => 'wrapper',
					'label'         => __( 'Billing Detail', 'tripzzy' ),
					'name'          => 'billing_info',
					'id'            => 'billing-info',
					'class'         => 'billing-info',
					'placeholder'   => '',
					'required'      => false,
					'priority'      => 10,
					'value'         => '',
					// Additional configurations.
					'is_new'        => false,
					'is_default'    => true,
					'enabled'       => true,
					'force_enabled' => true,
					'children'      => $billing_info_fields,
				),

				'additional_information' =>
				array(
					'type'          => 'wrapper',
					'label'         => __( 'Additional information', 'tripzzy' ),
					'name'          => 'additional_information',
					'id'            => 'additional-information',
					'class'         => 'additional-information',
					'placeholder'   => '',
					'required'      => false,
					'priority'      => 20,
					'value'         => '',
					// Additional configurations.
					'is_new'        => false,
					'is_default'    => true,
					'enabled'       => true,
					'force_enabled' => true,
					'children'      => array(
						array(
							'type'          => 'textarea',
							'label'         => __( 'Notes', 'tripzzy' ),
							'name'          => 'order_comments',
							'id'            => 'note',
							'class'         => 'note',
							'placeholder'   => __( 'Your note', 'tripzzy' ),
							'required'      => false,
							'priority'      => 20,
							'value'         => '',
							// Additional configurations.
							'is_new'        => false,
							'is_default'    => true,
							'enabled'       => true,
							'force_enabled' => true,
						),
					),
				),

			);
			return $fields;
		}
	}
}
