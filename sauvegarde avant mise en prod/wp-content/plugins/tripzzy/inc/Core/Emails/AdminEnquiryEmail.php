<?php
/**
 * Handle Enquiry Email.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Emails;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\EmailBase;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\Page;
use Tripzzy\Core\PostTypes\EnquiryPostType;
use Tripzzy\Core\Forms\EnquiryForm;

if ( ! class_exists( 'Tripzzy\Core\Emails\AdminEnquiryEmail' ) ) {

	/**
	 * Class related to bookings.
	 *
	 * @since 1.0.0
	 */
	class AdminEnquiryEmail extends EmailBase {

		/**
		 * Trip Enquiry id.
		 *
		 * @var int
		 */
		protected $enquiry_id = 0;

		/**
		 * From email id.
		 *
		 * @var string
		 */
		protected $from_email = '';

		/**
		 * From name.
		 *
		 * @since 1.2.7
		 * @var string
		 */
		protected $from_name = '';

		/**
		 * To Email Ids.
		 *
		 * @var string
		 */
		protected $to_emails = '';

		/**
		 * Email subject.
		 *
		 * @var string
		 */
		protected $subject = '';

		/**
		 * Enquiry Meta Data.
		 *
		 * @var string
		 */
		protected $enquiry_meta = array();


		/**
		 * Settings.
		 *
		 * @var array
		 */
		protected static $settings = array();

		/**
		 * Email Type
		 *
		 * @var string
		 */
		protected static $email_type = 'admin_booking_email';

		/**
		 * {@inheritDoc}
		 *
		 * @param integer $enquiry_id Trip booking id.
		 * @since 1.0.0
		 * @since 1.2.7 Added from name.
		 */
		public function __construct( $enquiry_id = 0 ) {

			self::$settings = Settings::get();

			if ( ! empty( self::$settings['disable_admin_notification'] ) ) {
				return;
			}

			if ( ! empty( self::$settings['disable_enquiry_notification'] ) ) {
				return;
			}
			// Config start.
			$this->init_from();
			$this->init_to_emails();

			$this->enquiry_id   = $enquiry_id;
			$this->enquiry_meta = MetaHelpers::get_post_meta( $enquiry_id, 'enquiry' );

			// Email args.
			$args = array(
				'to'        => $this->to_emails,
				'from'      => $this->from_email,
				'from_name' => $this->from_name,
				'subject'   => '',
				'trackback' => true,
			);
			parent::__construct( $args );
		}

		/**
		 * {@inheritDoc}
		 */
		public static function email_subject() {
			return self::$settings['admin_enquiry_notification_subject'];
		}

		/**
		 * Admin Enquiry Email content.
		 *
		 * @since 1.0.0
		 * @since 1.2.2 Added Default header and footer.
		 */
		public static function email_content() {

			$settings = self::$settings;
			$content  = ! empty( $settings['admin_enquiry_notification_content'] ) ? $settings['admin_enquiry_notification_content'] : '';

			if ( $content ) {
				return $content;
			}

			return '
			<table style="background-color:#fff; width:100%" border="0" cellspacing="0" cellpadding="0" >
				<thead>
					<tr>
						<td style="background-color:#f1f1f1; padding:30px 20px; font-size:24px">
							%SITENAME%
						</td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td style="border-top:3px solid #de5b09; padding:20px">
							<div class="tripzzy-admin" style="color: #5a5a5a; font-family: Roboto, sans-serif; margin: auto;">
								<p style="line-height: 1.55; font-size: 14px;">Hello <b>%SITENAME% Admin</b>,</p>
								<p style="line-height: 1.55; font-size: 14px;">You have received trip enquiry from <b>%ENQUIRY_FULL_NAME%</b>:</p>
								<table class="tripzzy-wrapper" style="color: #565656; font-family: Roboto, sans-serif; margin: auto;" width="100%" cellspacing="0" cellpadding="0">
									<tbody>
										<tr class="tripzzy-content" style="background: #fff;">
											<td class="tripzzy-content-title" style="background: #fff; margin: 0; padding: 10px" colspan="2" align="left">
												<h3 style="font-size: 16px; line-height: 1; margin: 0; margin-top: 30px;"><b>Enquiry Details:</b></h3>
											</td>
										</tr>
										<tr class="tripzzy-content" style="background: #fff;">
											<td style="font-size: 14px; margin: 0; padding: 10px;width:140px" align="left"><b>Trip</b></td>
											<td style="font-size: 14px; margin: 0; padding: 10px" align="left"><a style="color: #5a418b; text-decoration: none;" href="%TRIP_URL%" target="_blank" rel="noopener">%TRIP_TITLE%</a></td>
										</tr>
										<tr class="tripzzy-content" style="background: #fff;">
											<td style="font-size: 14px; margin: 0; padding: 10px;width:140px" align="left"><b>Name</b></td>
											<td style="font-size: 14px; margin: 0; padding: 10px" align="left">%ENQUIRY_FULL_NAME%</td>
										</tr>
										<tr class="tripzzy-content" style="background: #fff;">
											<td style="font-size: 14px; margin: 0; padding: 10px;width:140px" align="left"><b>E-mail</b></td>
											<td style="font-size: 14px; margin: 0; padding: 10px" align="left">%ENQUIRY_EMAIL%</td>
										</tr>
										<tr class="tripzzy-content" style="background: #fff;">
											<td style="font-size: 14px; margin: 0; padding: 10px;width:140px" align="left"><b>Enquiry Message</b></td>
											<td style="font-size: 14px; margin: 0; padding: 10px" align="left">%ENQUIRY_MESSAGE%</td>
										</tr>
									</tbody>
								</table>
							</div>	
						</td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<td style="background:#de5b09; padding:16px 20px">
							<table cellspacing="0" cellpadding="0" border="0" width="100%">
								<tr>
									<td>
										<p style="text-align: left; color:#fff">%SITENAME%</p>
									</td>
									<td>
										<p style="text-align: right; color:#fff">Copyright &copy; All right reserved</p>
									</td>
								</tr>
							</table>
							
						</td>
					</tr>
				</tfoot>
			</table>
			';
		}


		/**
		 * List of admin booking email tags.
		 *
		 * @since 1.0.0
		 * @since 1.2.2 Added additional fields from enquiry form as tags.
		 *
		 * @return array
		 */
		public static function get_tags() {

			$tags = array(
				'%ENQUIRY_ID%'        => __( 'Enquiry ID.', 'tripzzy' ),
				'%ENQUIRY_FULL_NAME%' => __( 'Full Name.', 'tripzzy' ),
				'%ENQUIRY_EMAIL%'     => __( 'Email ID.', 'tripzzy' ),
				'%ENQUIRY_MESSAGE%'   => __( 'Enquiry Message.', 'tripzzy' ),
				'%SITENAME%'          => __( 'Current website name.', 'tripzzy' ),
				'%TRIP_URL%'          => __( 'URL of the trip.', 'tripzzy' ),
				'%TRIP_TITLE%'        => __( 'Title of the trip.', 'tripzzy' ),
			);

			$fields = EnquiryForm::get_fields();
			foreach ( $fields as $field ) {
				$is_default = (bool) ( $field['is_default'] ?? false );
				if ( ! $is_default ) {
					$tag = '%' . strtoupper( $field['name'] ) . '%';
					if ( ! in_array( $tag, $tags, true ) ) {
						$tags[ $tag ] = $field['label'] ?? '';
					}
				}
			}
			return $tags;
		}

		/**
		 * Init Reply To Email ID.
		 *
		 * @since 1.2.7
		 * @return void
		 */
		public function init_reply_to() {
			$enquiry_meta = $this->enquiry_meta;
			$reply_to     = isset( $enquiry_meta['email'] ) ? $enquiry_meta['email'] : '';
			$this->set_reply_to( $reply_to );
		}

		/**
		 * Parse Email tag into content.
		 *
		 * @since 1.0.0
		 * @since 1.2.2 Parse additional fields from enquiry form as tag values.
		 */
		public function init_tags() {
			$tag_keys     = array_keys( self::get_tags() );
			$enquiry_meta = $this->enquiry_meta;
			$trip_id      = isset( $enquiry_meta['trip_id'] ) ? absint( $enquiry_meta['trip_id'] ) : 0;

			foreach ( $tag_keys as $tag_key ) {
				switch ( $tag_key ) {
					case '%ENQUIRY_ID%':
						$this->set_tag_value( $tag_key, $this->enquiry_id );
						break;
					case '%ENQUIRY_FULL_NAME%':
						$value = isset( $enquiry_meta['full_name'] ) ? $enquiry_meta['full_name'] : '';
						$this->set_tag_value( $tag_key, $value );
						break;
					case '%ENQUIRY_EMAIL%':
						$value = isset( $enquiry_meta['email'] ) ? $enquiry_meta['email'] : '';
						$this->set_tag_value( $tag_key, $value );
						break;
					case '%ENQUIRY_MESSAGE%':
						$value = isset( $enquiry_meta['message'] ) ? $enquiry_meta['message'] : '';
						$this->set_tag_value( $tag_key, $value );
						break;
					case '%SITENAME%':
						$this->set_tag_value( $tag_key, wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) );
						break;
					case '%TRIP_URL%':
						$this->set_tag_value( $tag_key, get_permalink( $trip_id ) );
						break;
					case '%TRIP_TITLE%':
						$this->set_tag_value( $tag_key, get_the_title( $trip_id ) );
						break;
					default:
						$this->set_tag_value( $tag_key, $this->set_additional_tag_values( $this->enquiry_id, $tag_key ) );
						break;
				}
			}
		}

		/**
		 * Parse additional Email tags. Newly generated form fields are parsed here.
		 *
		 * @param int    $enquiry_id Enquiry id.
		 * @param string $tag_key    Tag name.
		 * @since 1.2.2
		 * @return array
		 */
		public function set_additional_tag_values( $enquiry_id, $tag_key ) {
			$fields = EnquiryPostType::get_fields_data( $enquiry_id );
			foreach ( $fields as $field ) {
				$is_default = (bool) ( $field['is_default'] ?? false );
				if ( ! $is_default ) {
					$tag = '%' . strtoupper( $field['name'] ) . '%';
					if ( $tag === $tag_key ) {
						return $field['value'] ?? '';
					}
				}
			}
		}

		/**
		 * Init From address.
		 *
		 * @since 1.0.0
		 * @since 1.2.7 Added from name.
		 * @return void
		 */
		public function init_from() {
			$from_email = self::$settings['admin_from_email'];
			$from_name  = self::$settings['admin_from_name'];
			if ( empty( $from_email ) ) {
				$from_email = get_bloginfo( 'admin_email' );
			}
			$this->from_email = $from_email;
			$this->from_name  = $from_name;
		}

		/**
		 * Init To Email IDs.
		 *
		 * @return void
		 */
		public function init_to_emails() {
			$to_emails = self::$settings['admin_to_emails'];
			if ( empty( $to_emails ) ) {
				$to_emails = get_bloginfo( 'admin_email' );
			}
			$this->to_emails = $to_emails;
		}
	}
}
