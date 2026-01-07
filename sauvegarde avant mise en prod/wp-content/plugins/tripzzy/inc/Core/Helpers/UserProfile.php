<?php
/**
 * Helper class to handle Tripzzy user profile related work
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\UserProfile' ) ) {
	/**
	 * UserProfile Class Defination.
	 *
	 * @since 1.0.0
	 */
	class UserProfile {

		const USERDATA_METAKEY = 'userdata';

		const USERPROFILE_METAKEY = 'userprofile';

		/**
		 * User ID.
		 *
		 * @var int
		 */
		private $user_id;

		/**
		 * WP_User class instance.
		 *
		 * @var \WP_User
		 */
		private $user;

		/**
		 * Default User fields data.
		 *
		 * @var array
		 * @since 1.1.9
		 */
		private $default_user_fields = array();

		/**
		 * Default Profile fields data.
		 *
		 * @var array
		 * @since 1.1.9
		 */
		private $default_profile_fields = array();


		/**
		 * Initialize our class.
		 *
		 * @param int $user_id User ID.
		 */
		public function __construct( $user_id = null ) {
			$this->set_user_id( $user_id );
			$this->set_default_user_fields();
			$this->set_default_profile_fields();
		}

		/**
		 * Sets user id for current instance.
		 *
		 * @param int|null $user_id User ID.
		 * @return void
		 */
		public function set_user_id( $user_id = null ) {
			if ( is_null( $user_id ) ) {
				$user_id = get_current_user_id();
			}

			$this->user_id = absint( $user_id );
		}

		/**
		 * Returns current instance user id.
		 *
		 * @return int
		 */
		public function get_user_id() {
			return $this->user_id;
		}

		/**
		 * Sets array default field.
		 * User data and profile data are different.
		 *
		 * @since 1.1.9
		 * @return void
		 */
		protected function set_default_user_fields() {

			$user_id    = $this->get_user_id();
			$this->user = get_user_by( 'ID', $user_id );

			$this->default_user_fields = apply_filters(
				'tripzzy_filter_userprofile_default_user_fields',
				array(
					'ID'         => $user_id,
					'first_name' => $this->user->first_name,
					'last_name'  => $this->user->last_name,
					'user_email' => $this->user->user_email,
					'phone'      => '',
				)
			);
		}

		/**
		 * Sets array default field.
		 * User data and profile data are different.
		 *
		 * @since 1.1.9
		 * @return void
		 */
		protected function set_default_profile_fields() {
			$this->default_profile_fields = apply_filters(
				'tripzzy_filter_userprofile_default_profile_fields',
				array(
					'billing_country'   => '',
					'billing_address_1' => '',
					'billing_address_2' => '',
					'billing_city'      => '',
					'billing_state'     => '',
					'billing_postcode'  => '',
				)
			);
		}

		/**
		 * Returns default fields.
		 *
		 * @since 1.1.9
		 * @return array
		 */
		public function get_default_profile_fields() {
			return $this->default_profile_fields;
		}

		/**
		 * Returns default fields.
		 *
		 * @since 1.1.9
		 * @return array
		 */
		public function get_default_user_fields() {
			return $this->default_user_fields;
		}

		/**
		 * Validate data fields.
		 *
		 * @param array $data Data and fields to validate.
		 * @return \WP_Error|void
		 */
		public function validate( $data ) {

			$required_fields = apply_filters(
				'tripzzy_filter_userprofile_required_fields',
				array(
					'first_name' => __( 'First Name', 'tripzzy' ),
					'last_name'  => __( 'Last Name', 'tripzzy' ),
				)
			);

			if ( is_array( $required_fields ) && ! empty( $required_fields ) ) {
				foreach ( $required_fields as $required_field => $label ) {
					if ( empty( $data[ $required_field ] ) ) {
						return new \WP_Error(
							'tripzzy_userprofile_empty_required_field',
							/* translators: %s is the required field label. */
							sprintf( __( '"%s" is a required field.', 'tripzzy' ), esc_html( $label ) )
						);
					}
				}
			}
		}

		/**
		 * WordPress user data.
		 *
		 * @since 1.0.0
		 * @since 1.1.9 Added and parse get_default_user_fields.
		 * @return array
		 */
		protected function user_data() {

			$user_id  = $this->get_user_id();
			$userdata = MetaHelpers::get_user_meta( $user_id, self::USERDATA_METAKEY );

			if ( ! is_array( $userdata ) ) {
				$userdata = array();
			}
			return wp_parse_args( $userdata, $this->get_default_user_fields() );
		}

		/**
		 * Returns profile data.
		 *
		 * @return array
		 */
		protected function profile_data() {
			$userprofile = MetaHelpers::get_user_meta( $this->get_user_id(), self::USERPROFILE_METAKEY );

			if ( ! is_array( $userprofile ) ) {
				$userprofile = array();
			}

			return wp_parse_args( $userprofile, $this->get_default_profile_fields() );
		}

		/**
		 * Get user profile data.
		 *
		 * @return array
		 */
		public function get() {

			return apply_filters(
				'tripzzy_filter_userprofile_data',
				array_merge(
					$this->user_data(),
					$this->profile_data(),
					array(
						'greetings' => $this->user->first_name
						?
						/* translators: %s is user full name. */
						trim( sprintf( esc_html__( 'Welcome, %s', 'tripzzy' ), esc_html( $this->user->first_name . ' ' . $this->user->last_name ) ) )
						:
						'',
					),
				)
			);
		}

		/**
		 * Update user profile data
		 *
		 * @param array $data User profile data.
		 * @return bool
		 */
		public function update( $data ) {

			$success = false;

			if ( ! $data ) {
				return $success;
			}

			$saved_user_data = $this->user_data();

			$profile_data           = array();
			$user_data              = $saved_user_data;
			$default_profile_fields = $this->get_default_profile_fields();

			/**
			 * Separate data into user and profile.
			 */
			if ( is_array( $data ) && ! empty( $data ) ) {
				foreach ( $data as $key => $value ) {
					if ( isset( $default_profile_fields[ $key ] ) ) {
						$profile_data[ $key ] = $value;
					} else {
						$user_data[ $key ] = $value;
					}
				}
			}

			$success = false !== MetaHelpers::update_user_meta( $this->get_user_id(), self::USERPROFILE_METAKEY, $profile_data );

			if ( $saved_user_data !== $user_data ) {

				$success = MetaHelpers::update_user_meta( $this->get_user_id(), self::USERDATA_METAKEY, $user_data );
			}

			return $success;
		}
	}

}
