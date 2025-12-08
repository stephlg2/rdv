<?php
/**
 * Base class For Tripzzy Email.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Bases;

use Tripzzy\Core\Helpers\EmailTrackback;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Bases\EmailBase' ) ) {
	/**
	 * Base class For Tripzzy Email.
	 *
	 * @since 1.0.0
	 */
	class EmailBase {

		/**
		 * Raw email content.
		 * Usually the email content with email tags.
		 *
		 * @return string
		 * @abstract
		 */
		public static function email_content() {
			_doing_it_wrong( __METHOD__, 'This method is supposed to be overridden from the child class.', '1.0.0' );
			return '';
		}


		/**
		 * \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		 * ==================================
		 *
		 * Object methods starts from here.
		 *
		 * ==================================
		 * //////////////////////////////////
		 */


		/**
		 * Email address of email receiver.
		 *
		 * @var string
		 */
		private $to = '';

		/**
		 * Email from.
		 *
		 * @var string
		 */
		private $from = '';

		/**
		 * Name.
		 *
		 * @since 1.2.7
		 * @var string
		 */
		private $from_name = '';

		/**
		 * Email reply to.
		 *
		 * @var string
		 */
		private $reply_to = '';

		/**
		 * Email subject.
		 *
		 * @var string
		 */
		private $subject = '';

		/**
		 * Email content or parsed content.
		 *
		 * @var string
		 */
		private $message = '';

		/**
		 * Email headers.
		 *
		 * @var string
		 */
		private $headers = '';

		/**
		 * Email attachments.
		 *
		 * @var array
		 */
		private $attachments = array();

		/**
		 * Enable/disable email seen status trackback.
		 *
		 * @var boolean
		 */
		private $trackback = false;

		/**
		 * Array of email tags and their values.
		 *
		 * @var array
		 */
		private $tags_and_values = array();

		/**
		 * The "Late Static Binding" class name
		 *
		 * @var string
		 */
		private $called_class = '';

		/**
		 * Initialize our class.
		 *
		 * @param array $args Array of wp_mail parameters with key/value pairs.
		 */
		public function __construct( $args ) {

			$this->called_class = get_called_class();
			$this->from         = $args['from'];

			do_action( 'tripzzy_email_init', $this->called_class, $args, $this );

			if ( is_array( $args ) && ! empty( $args ) ) {
				foreach ( $args as $key => $value ) {
					if ( $this->is_valid_param( $key ) ) {
						$this->$key = $value;
					}
				}
			}

			$this->init_reply_to();
			$this->init_headers();
			$this->init_tags();
			$this->parse_tags(); // Parse tag and set email content.
		}

		/**
		 * Initialize reply to email.
		 *
		 * @since 1.2.7
		 * @return void
		 */
		public function init_reply_to() {}

		/**
		 * Initialize default email headers.
		 *
		 * @since 1.0.0
		 * @since 1.2.7 Added From Name.
		 */
		public function init_headers() {
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";

			if ( $this->from ) :
				$headers .= 'From: ' . $this->from_name . ' <' . $this->from . ">\r\n";
			endif;

			if ( $this->reply_to ) :
				$headers .= 'Reply-To: ' . $this->reply_to . "\r\n" .
				'X-Mailer: PHP/' . phpversion();
			endif;

			$this->headers .= $headers;
		}

		/**
		 * Initialize tag related functionalities.
		 *
		 * @return void
		 */
		public function init_tags() {}

		/**
		 * Check if email parameter key is valid.
		 *
		 * @param string $key Key to validate.
		 * @since 1.0.0
		 * @since 1.2.7 Added from name.
		 * @return boolean
		 */
		protected function is_valid_param( $key ) {
			$valid_keys = array(
				'to',
				'from',
				'from_name',
				'reply_to',
				'subject',
				'headers',
				'attachments',
				'trackback',
			);

			return in_array( $key, $valid_keys, true );
		}

		/**
		 * Set reply to email.
		 *
		 * @param string $reply_to Value to set for the email tag key.
		 * @since 1.2.7
		 * @return void
		 */
		public function set_reply_to( $reply_to = '' ) {
			$this->reply_to = $reply_to;
		}

		/**
		 * Set email tag value.
		 *
		 * @param string $tag_key Email tag key.
		 * @param string $tag_value Value to set for the email tag key.
		 * @return void
		 */
		public function set_tag_value( $tag_key, $tag_value = '' ) {
			$this->tags_and_values[ $tag_key ]['value'] = $tag_value;
		}

		/**
		 * Returns email tag value.
		 *
		 * @param string $tag_key Email tag key.
		 * @return mixed
		 */
		public function get_tag_value( $tag_key ) {
			if ( ! isset( $this->tags_and_values[ $tag_key ] ) ) {
				return '';
			}

			return ! empty( $this->tags_and_values[ $tag_key ]['value'] ) ? $this->tags_and_values[ $tag_key ]['value'] : '';
		}

		/**
		 * Parse email tags to their values in email content.
		 *
		 * @return void
		 */
		public function parse_tags() {
			if ( $this->tags_and_values ) {
				$this->message = str_replace( array_keys( $this->tags_and_values ), array_values( wp_list_pluck( $this->tags_and_values, 'value' ) ), static::email_content() );
				$this->subject = str_replace( array_keys( $this->tags_and_values ), array_values( wp_list_pluck( $this->tags_and_values, 'value' ) ), static::email_subject() );
			} else {
				$this->message = static::email_content();
				$this->subject = static::email_subject();
			}
		}

		/**
		 * Change all form fields type to text_view except wrapper and repeator field.
		 *
		 * @param array $array1 Array value.
		 * @since 1.0.4
		 * @return array
		 */
		public static function parse_all_to_text_view( &$array1 ) {
			$array1 = ! $array1 ? array() : (array) $array1;
			$result = $array1;
			if ( count( $array1 ) > 0 ) {
				foreach ( $array1 as $k => &$v ) {
					if ( is_array( $v ) ) {
						$result[ $k ] = self::parse_all_to_text_view( $v );
					} else {
						$default_value = isset( $result[ $k ] ) ? $result[ $k ] : '';
						$value         = $v ? $v : $default_value;
						// Modify the type accordingly.
						if ( 'type' === $k && ! ( 'wrapper' === $value || 'repeator' === $value ) ) {
							$value = 'text_view';
						}
						$result[ $k ] = $value;
					}
				}
			}
			return $result;
		}

		/**
		 * Email header Markup.
		 *
		 * @since 1.0.0
		 * @since 1.2.2 Container background added.
		 * @return Markup string
		 */
		public function get_email_header() {
			ob_start();
			?>
			<!DOCTYPE html>
				<html lang="en">
				<head>
					<meta charset="UTF-8">
					<meta name="viewport" content="width=device-width, initial-scale=1.0">
					<title><?php esc_html_e( 'Tripzzy Email', 'tripzzy' ); ?></title>
					<style>
						.tripzzy-email-container{
						width:100%;
						background:#e9eaec;
						}
						.tripzzy-email-content{
							max-width:800px;
							margin:auto;
							font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
						}
					</style>
					<?php do_action( 'tripzzy_' . static::$email_type . '_email_template_head' ); ?>
				</head>
				<body>
					<div class="tripzzy-email-container">
						<div class="tripzzy-email-content">
			<?php
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}

		/**
		 * Email Footer Markup.
		 *
		 * @return markup string
		 */
		public function get_email_footer() {
			ob_start();
			?>
						</div> <!-- /Tripzzy Email Content -->
					</div> <!-- /Tripzzy Email Contentainer -->
				</body>
			</html>
			<?php
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}

		/**
		 * Email content along with header and footer.
		 *
		 * @return string Markup data.
		 */
		public function message() {
			return sprintf( '%s %s %s', $this->get_email_header(), $this->message, $this->get_email_footer() );
		}

		/**
		 * Send email.
		 *
		 * @return bool
		 */
		public function send() {
			$args = apply_filters(
				'tripzzy_filter_email_args',
				array(
					'to'          => $this->to,
					'subject'     => $this->subject,
					'message'     => apply_filters( 'tripzzy_filter_email_message', $this->message(), static::$email_type ),
					'headers'     => $this->headers,
					'attachments' => $this->attachments,
				),
				$this->called_class
			);

			$email_trackback = new EmailTrackback();

			if ( $this->trackback ) {
				$trackback_title = is_array( $args['to'] ) ? implode( ',', $args['to'] ) : $args['to'];
				$trackback_id    = $email_trackback->create(
					array(
						'post_title'   => $trackback_title,
						'post_content' => $args['message'],
					)
				);

				if ( $trackback_id ) {

					$url = $email_trackback->get_url();

					// Content type must be html format for the trackback feature.
					$args['headers'] .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
					$args['message'] .= '<img style="display:none;" src="' . esc_url( $url ) . '" />';

				}
			}

			do_action( 'tripzzy_before_wp_mail', $this->called_class, $args );

			if ( $args['to'] ) {
				$args['sent'] = wp_mail( $args['to'], $args['subject'], $args['message'], $args['headers'], $args['attachments'] );

				if ( $this->trackback ) {
					$status = true === $args['sent'] ? 'sent' : 'error';
					$email_trackback->update( $status );
				}

				do_action( 'tripzzy_after_wp_mail', $this->called_class, $args );

				return $args['sent'];
			}
		}

		/**
		 * Stylesheet for email template.
		 * Used in:
		 * inc/Core/Bookings.php
		 * inc/Core/Emails/AdminBookingEmail.php
		 * inc/Core/Emails/CustomerBookingEmail.php
		 * inc/Core/Helpers/Customer.php
		 *
		 * @since 1.0.4
		 */
		public static function email_style() {
			?>
			<style>
				.tripzzy-form-field{
					margin-bottom:5px;
					position: relative;
					z-index: 0;
					padding:0!important;
					border-bottom:none!important;
				}
				.tripzzy-form-field  label{
					width:180px;
					display:inline-block;
					vertical-align:top;
					font-size:13px;
				}
				.tripzzy-form-field .tripzzy-required{display: none;}
				.tripzzy-form-field input,
				.tripzzy-form-field textarea,
				.tripzzy-form-field select{
					border:none !important;
					pointer-events:none;
					min-height:20px !important;
					height:auto;
					line-height:1.55;
					background:transparent;
					position: relative;
					z-index: -1;
				}
				.tripzzy-form-field input:focus,
				.tripzzy-form-field textarea:focus{
					border:none;
					box-shadow:none;
					outline:none
				}
				.tripzzy-form-field select{
					border:none;
					pointer-events:none;
					appearance: none;
					-webkit-appearance: none;
					-moz-appearance: none;
					text-indent: 1px;
					background:transparent;

				}
				.tripzzy-form-label.tripzzy-form-label-wrapper{
					text-transform: uppercase;
					font-size: 15px;
					font-weight: 500;
					display: block;
					border-bottom: 1px solid #ccc;
					margin-bottom: 10px;
					padding: 5px 10px;
					background: #626262;
					color:#fff;
				}
				.tripzzy-form-field-wrapper:not(:last-child){
					margin-bottom:15px;
				}

				/* placeholders */
				::-webkit-input-placeholder {
					/* WebKit browsers */
					color: transparent;
				}
				:-moz-placeholder {
					/* Mozilla Firefox 4 to 18 */
					color: transparent;
				}
				::-moz-placeholder {
					/* Mozilla Firefox 19+ */
					color: transparent;
				}
				:-ms-input-placeholder {
					/* Internet Explorer 10+ */
					color: transparent;
				}
				input::placeholder {
					color: transparent;
				}
			</style>
			<?php
		}
	}
}
