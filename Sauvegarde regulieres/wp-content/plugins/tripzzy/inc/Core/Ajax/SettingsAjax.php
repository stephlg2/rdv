<?php
/**
 * Settings Ajax class.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Ajax;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\DropdownOptions;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Seeder\PageSeeder;

if ( ! class_exists( 'Tripzzy\Core\Ajax\SettingsAjax' ) ) {
	/**
	 * Settings Ajax class.
	 *
	 * @since 1.0.0
	 */
	class SettingsAjax {
		use SingletonTrait;

		/**
		 * All available strings.
		 *
		 * @var array
		 */
		private $strings;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->strings = Strings::messages();
			add_action( 'wp_ajax_tripzzy_get_settings', array( $this, 'get' ) );
			add_action( 'wp_ajax_tripzzy_update_settings', array( $this, 'update' ) );
			add_action( 'wp_ajax_tripzzy_reset_settings', array( $this, 'reset' ) );
		}

		/**
		 * Ajax callback to get settings data.
		 *
		 * @since 1.0.0
		 */
		public function get() {
			if ( ! Nonce::verify() ) {
				$message = array(
					'message' => $this->strings['nonce_verification_failed'],
				);
				wp_send_json_error( $message );
			}

			$options              = DropdownOptions::get();
			$settings             = Settings::get();
			$defaults             = Settings::default_settings(); // To Fetch new Taxonomy and custom taxonomy.
			$settings['options']  = $options;
			$settings['defaults'] = $defaults;
			wp_send_json_success( $settings, 200 );
		}

		/**
		 * Ajax callback to set settings data.
		 *
		 * @since 1.0.0
		 */
		public function update() {
			if ( ! Nonce::verify() ) {
				$message = array(
					'message' => $this->strings['nonce_verification_failed'],
				);
				wp_send_json_error( $message );
			}
			Settings::update();
			$this->get(); // this will also send response as well. So we don't need to send response again.
		}

		/**
		 * Ajax callback to reset form data.
		 *
		 * @since 1.0.0
		 */
		public function reset() {
			if ( ! Nonce::verify() ) {
				$message = array(
					'message' => $this->strings['nonce_verification_failed'],
				);
				wp_send_json_error( $message );
			}

			$settings      = Settings::default_settings();
			$prev_settings = Settings::get();
			// Do Not reset some settings values.
			// 1. Feild Editor data.
			$form_data = Settings::form_data();
			$form_keys = array_keys( $form_data );
			if ( is_array( $form_keys ) && count( $form_keys ) > 0 ) {
				foreach ( $form_keys as $key ) {
					$settings[ $key ] = isset( $prev_settings[ $key ] ) ? $prev_settings[ $key ] : 0;
				}
			}

			// 2. Tripzzy Pages data.
			$pages = PageSeeder::get_pages();

			foreach ( $pages as $page_data ) {
				$settings_key              = $page_data['settings_key'];
				$page_id                   = isset( $prev_settings[ $settings_key ] ) ? $prev_settings[ $settings_key ] : 0;
				$settings[ $settings_key ] = $page_id;
			}
			MetaHelpers::update_option( 'settings', $settings );
			$this->get(); // this will also send response as well. So we don't need to send response again.
		}
	}
	SettingsAjax::instance();
}
