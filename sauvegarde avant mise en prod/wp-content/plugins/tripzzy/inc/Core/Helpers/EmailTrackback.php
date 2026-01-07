<?php
/**
 * Helper class for email trackback.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\EmailTrackback' ) ) {

	/**
	 * Helper class for email trackback.
	 *
	 * @since 1.0.0
	 */
	class EmailTrackback {

		const POSTTYPE = 'tz_email_trackback';

		const TRACKBACK_STATUES_METAKEY = 'email_trackback_statuses';

		const EMAIL_FAILED_MSG_METAKEY = 'email_failed_message';

		/**
		 * Post id of email trackback post type.
		 *
		 * @var int
		 */
		protected $trackback_id;

		/**
		 * Initialize our class.
		 *
		 * @param int $trackback_id Post id of email trackback post type.
		 */
		public function __construct( $trackback_id = null ) {
			if ( self::POSTTYPE === get_post_type( $trackback_id ) ) {
				$this->trackback_id = absint( $trackback_id );
			}

			add_action( 'wp_mail_failed', array( $this, 'set_email_failed_message' ) );
		}

		/**
		 * Sets last email failed message.
		 *
		 * @param \WP_Error $wp_error WP Error instance.
		 * @return bool
		 */
		public function set_email_failed_message( $wp_error ) {

			$trackback_id = $this->trackback_id;

			if ( ! $trackback_id ) {
				return;
			}

			return MetaHelpers::update_post_meta( $trackback_id, self::EMAIL_FAILED_MSG_METAKEY, $wp_error->get_error_message() );
		}

		/**
		 * Returns last email failed message.
		 *
		 * @return string
		 */
		public function get_email_failed_message() {
			return MetaHelpers::get_post_meta( $this->trackback_id, self::EMAIL_FAILED_MSG_METAKEY );
		}

		/**
		 * Returns action for nonce.
		 *
		 * @return string
		 */
		public function get_nonce_action() {
			return self::POSTTYPE . $this->trackback_id;
		}

		/**
		 * Sets nonce.
		 *
		 * @return string
		 */
		public function create_nonce() {
			$nonce = trim( wp_create_nonce( $this->get_nonce_action() ) );
			MetaHelpers::update_post_meta( $this->trackback_id, 'email_trackback_nonce', $nonce );
			return $nonce;
		}

		/**
		 * Returns saved set nonce key.
		 *
		 * @return string
		 */
		public function get_nonce() {
			return trim( MetaHelpers::get_post_meta( $this->trackback_id, 'email_trackback_nonce' ) );
		}

		/**
		 * Checks nonce verification.
		 *
		 * @param string $nonce Previously set nonce key.
		 * @return bool
		 */
		public function verify_nonce( $nonce ) {
			$nonce = trim( $nonce );

			if ( wp_verify_nonce( $nonce, $this->get_nonce_action() ) ) {
				return true;
			}

			if ( $nonce === $this->get_nonce() ) {
				MetaHelpers::delete_post_meta( $this->trackback_id, 'email_trackback_nonce' );
				return true;
			}

			return false;
		}

		/**
		 * Creates post using `wp_insert_post` and sets post id.
		 *
		 * @param array $args Arguments for `wp_insert_post`. The `post_type` argument will be override by the method.
		 * @return int Post ID.
		 */
		public function create( $args = array() ) {

			$parsed = wp_parse_args(
				$args,
				array(
					'post_status'    => 'publish',
					'comment_status' => 'closed',
				)
			);

			$parsed['post_type'] = self::POSTTYPE;

			$trackback_id = wp_insert_post( $parsed );

			if ( is_wp_error( $trackback_id ) ) {
				return 0;
			}

			$this->trackback_id = $trackback_id;

			return $this->trackback_id;
		}

		/**
		 * Update trackback statuses.
		 *
		 * @param string $status Trackback status. Allowed values: `sent`, `error`, and `read`.
		 * @return void
		 */
		public function update( $status ) {

			$trackback_id = $this->trackback_id;

			if ( ! $trackback_id ) {
				return;
			}

			$valid = array( 'sent', 'error', 'read' );

			if ( ! in_array( $status, $valid, true ) ) {
				return;
			}

			$statuses[] = array(
				'status'    => $status,
				'timestamp' => time(),
			);

			MetaHelpers::update_post_meta( $trackback_id, self::TRACKBACK_STATUES_METAKEY, $statuses );
		}

		/**
		 * Returns array of email trackback statuses arrays.
		 *
		 * @return array[]
		 */
		public function get() {

			$trackback_id = $this->trackback_id;

			if ( ! $trackback_id ) {
				return;
			}

			$statuses = MetaHelpers::get_post_meta( $trackback_id, self::TRACKBACK_STATUES_METAKEY );

			if ( ! $statuses ) {
				$statuses = array();
			}

			return $statuses;
		}

		/**
		 * Returns trackback url for the email message.
		 *
		 * @return string
		 */
		public function get_url() {
			if ( ! $this->trackback_id ) {
				return;
			}

			$nonce = $this->create_nonce();

			return admin_url( "/admin-ajax.php?action=tripzzy_email_trackback&post_id={$this->trackback_id}&nonce={$nonce}" );
		}

		/**
		 * Returns latest trackback update.
		 *
		 * @return array
		 */
		public function get_latest() {

			$statuses = $this->get();

			if ( ! $statuses ) {
				return array();
			}

			$last_key = ArrayHelper::array_key_last( $statuses );

			if ( null === $last_key ) {
				return array();
			}

			return $statuses[ $last_key ];
		}
	}
}
