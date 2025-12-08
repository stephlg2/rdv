<?php
/**
 * Tripzzy Post type.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\PostTypes;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\PostTypeBase;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Helpers\Page;
use Tripzzy\Core\Helpers\Amount;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\Icon;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\Strings;

use Tripzzy\Core\Forms\CheckoutForm;
use Tripzzy\Core\Forms\Form;
use Tripzzy\Core\Bookings;
use Tripzzy\Core\Payment;


if ( ! class_exists( 'Tripzzy\Core\PostTypes\BookingPostType' ) ) {
	/**
	 * Tripzzy Post Type Class.
	 *
	 * @since 1.0.0
	 */
	class BookingPostType extends PostTypeBase {
		/**
		 * Post Type Key to register post type.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $post_type = 'tripzzy_booking';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'tripzzy_filter_post_type_args', array( $this, 'init_args' ) );
			add_filter( 'tripzzy_filter_meta_box_args', array( $this, 'init_meta_box_args' ), 10, 2 );
			add_action( 'tripzzy_' . self::$post_type . '_save_post', array( $this, 'save_meta' ) ); // Save booking only for admin.

			/*
			* Filter Hook : Admin Column - Headings.
			*/
			add_filter( 'manage_edit-' . self::$post_type . '_columns', array( $this, 'admin_column_headings' ) );

			/*
			* Action Hook : Admin Column - Content.
			*/
			add_action( 'manage_' . self::$post_type . '_posts_custom_column', array( $this, 'admin_column_contents' ), 10, 2 );

			/**
			 * Make Column sortable.
			 *
			 * @since 1.0.6
			 */
			add_filter( 'manage_edit-' . self::$post_type . '_sortable_columns', array( $this, 'make_column_sortable' ) );
			/**
			 * Add sortable logic for custom sortable column.
			 *
			 * @since 1.0.6
			 */
			add_action( 'pre_get_posts', array( $this, 'admin_column_sortable_query' ) );

			$screen_id = self::$post_type;
			$box_id    = sprintf( '%s__booking_data', $screen_id );
			$box_id2   = sprintf( '%s__booking_trips', $screen_id );
			add_filter( "postbox_classes_{$screen_id}_{$box_id}", '__return_empty_array' );
			add_filter( "postbox_classes_{$screen_id}_{$box_id2}", '__return_empty_array' );
		}

		/**
		 * Post Type arguments.
		 *
		 * @since 1.0.0
		 */
		protected static function post_type_args() {
			$labels = array(
				'add_new'            => _x( 'New Booking', 'tripzzy', 'tripzzy' ),
				'add_new_item'       => __( 'Add Booking', 'tripzzy' ),
				'all_items'          => __( 'Bookings', 'tripzzy' ),
				'edit_item'          => __( 'Edit Booking', 'tripzzy' ),
				'menu_name'          => _x( 'Tripzzy', 'admin menu', 'tripzzy' ),
				'name'               => _x( 'Bookings', 'post type general name', 'tripzzy' ),
				'name_admin_bar'     => _x( 'Booking', 'add new on admin bar', 'tripzzy' ),
				'new_item'           => __( 'New Booking', 'tripzzy' ),
				'not_found'          => __( 'No Bookings found.', 'tripzzy' ),
				'not_found_in_trash' => __( 'No Bookings found in Trash.', 'tripzzy' ),
				'parent_item_colon'  => __( 'Parent Bookings:', 'tripzzy' ),
				'search_items'       => __( 'Search Bookings', 'tripzzy' ),
				'singular_name'      => _x( 'Booking', 'post type singular name', 'tripzzy' ),
				'view_item'          => __( 'View Booking', 'tripzzy' ),
			);

			$args = array(
				'labels'             => $labels,
				'description'        => __( 'Description.', 'tripzzy' ),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'rewrite'            => array(
					'slug'       => 'tripzzy-booking',
					'with_front' => true,
				),
				'capability_type'    => 'post',
				'capabilities'       => array(
					'create_posts' => false,
				),
				'map_meta_cap'       => true,
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => 30,
				'supports'           => array( 'title' ),
				'menu_icon'          => Icon::get_svg_icon_base_64( 'brand-grayscale' ),
				'show_in_rest'       => true,
				'rest_base'          => 'bookings',
				'priority'           => 30,
			);
			return $args;
		}

		/**
		 * Get meta fields,
		 *
		 * @param int  $booking_id Booking id.
		 * @param bool $for_email Whether is it for email or not. if true then it will return parsed field value instead of text field.
		 *
		 * @since 1.0.0
		 * @since 1.0.4 $for_email param added.
		 * @return array
		 */
		public static function get_booking_metafield_fields( $booking_id, $for_email = false ) {

			if ( get_post_type( $booking_id ) !== self::$post_type ) {
				return array();
			}

			$values = MetaHelpers::get_post_meta( $booking_id, 'checkout_info' );

			if ( ! $values || ! is_array( $values ) ) {
				$values = array();
			}

			$fields = CheckoutForm::get_fields( null, $for_email ); // Fields without values.

			foreach ( $fields as $field_index => $field ) {

				if ( 'repeator' === $field['type'] ) {
					$repeator_fields        = $field['children'];
					$repeator_values        = isset( $values[ $field['name'] ] ) ? $values[ $field['name'] ] : array();
					$child_with_val         = Form::repeator_field_values( $repeator_fields, $repeator_values );
					$field['children']      = $child_with_val;
					$fields[ $field_index ] = $field;
				} elseif ( 'wrapper' === $field['type'] ) {
					$repeator_fields        = $field['children'];
					$child_with_val         = Form::wrapper_field_values( $repeator_fields, $values );
					$field['children']      = $child_with_val;
					$fields[ $field_index ] = $field;
				} else {
					$fallback_value = isset( $field['value'] ) ? $field['value'] : '';
					$value          = isset( $values[ $field['name'] ] ) ? $values[ $field['name'] ] : $fallback_value;

					$field['value']         = $value;
					$fields[ $field_index ] = $field;
				}
			}

			return $fields;
		}

		/**
		 * Meta Box arguments.
		 * Required Method to register Metabox if filter `tripzzy_filter_meta_box_args` is used.
		 *
		 * @param int $booking_id Enquiry ID.
		 * @since 1.2.9 Changed callback for booking_data and booking_trips.
		 * @since 1.0.0
		 */
		protected static function meta_box_args( $booking_id ) {

			if ( get_post_type( $booking_id ) !== self::$post_type ) {
				return array();
			}

			$args = array(

				'booking_data'  => array(  // Meta Box ID.
					'title'   => __( 'Booking Details', 'tripzzy' ), // Required.
					'markups' => Bookings::render_booking_data( $booking_id, true ),
				),
				'booking_trips' => array(  // Meta Box ID.
					'title'   => __( 'Booking Trips', 'tripzzy' ), // Required.
					'markups' => Bookings::render_booking_trips( $booking_id, true ),
				),
				'payment_info'  => array(  // Meta Box ID.
					'title'   => __( 'Payment Info', 'tripzzy' ), // Required.
					'markups' => Payment::render( $booking_id, true ),
				),
				'booking_notes' => array(  // Meta Box ID.
					'title'   => __( 'Booking notes', 'tripzzy' ), // Required.
					'markups' => self::render_booking_notes( $booking_id ),
					'context' => 'side',
				),
			);
			return $args;
		}

		/**
		 * Render Booking status markup for metabox.
		 *
		 * @param int $booking_id Booking id.
		 * @since 1.0.0
		 * @since 1.1.8 Changed label name to guest from customer.
		 * @return void
		 */
		public static function render_booking_status( $booking_id ) {
			if ( get_post_type( $booking_id ) !== self::$post_type ) {
				return;
			}
			$status = MetaHelpers::get_post_meta( $booking_id, 'booking_status' );

			$status_options = Bookings::get_booking_status_options();
			$strings        = Strings::get();
			$labels         = $strings['labels'] ?? array();

			ob_start();
			?>
			<style>
				.tripzzy-pub-section{
					display:flex;
					gap:15px;
				}
			</style>
			<div class="misc-pub-section tripzzy-pub-section">
				<label for="tripzzy-booking-status"><?php echo esc_html( $labels['status'] ?? '' ); ?></label>
				<div class="tripzzy-pub-section-input" >
					<select name ="booking_status" id="tripzzy-booking-status" >
						<?php foreach ( $status_options as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php echo $value === $status ? esc_attr( 'selected' ) : ''; ?> ><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="misc-pub-section tripzzy-pub-section">
				<label><?php esc_html_e( 'Notify', 'tripzzy' ); ?></label>
				<div class="tripzzy-pub-section-input" >
					<label title="Send email if status is changed."> <input type="checkbox" name="send_notification" /><?php esc_html_e( 'Notify guest', 'tripzzy' ); ?></label>
				</div>
			</div>
			<?php
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}

		/**
		 * Render Booking notes markup for metabox.
		 *
		 * @param int $booking_id Booking id.
		 * @since 1.1.8
		 * @return void
		 */
		public static function render_booking_notes( $booking_id ) {
			if ( get_post_type( $booking_id ) !== self::$post_type ) {
				return;
			}
			$args  = array( 'booking_id' => $booking_id );
			$notes = Bookings::get_notes( $args );

			ob_start();
			?>
			<style>
				.tripzzy-booking-notes {
					margin-top:0;
					border-bottom:1px solid #ddd;
				}
				.tripzzy-booking-notes li{
					padding: 0px;
					position: relative;
				}
				.tripzzy-booking-notes li .tripzzy-note-content{
					padding: 10px;
					background: #efefef;
					position: relative;
					border-radius: var( --tripzzy-rounded )
				}
				.tripzzy-booking-notes li.tripzzy-guest-note .tripzzy-note-content{
					background: #a7cedc;
				}
				
				.tripzzy-booking-notes li:not(.empty-notes) .tripzzy-note-content::after{
					content: "";
					display: block;
					position: absolute;
					bottom: -9px;
					left: 20px;
					width: 0;
					height: 0;
					border-width: 10px 10px 0 0;
					border-style: solid;
					border-color: #efefef transparent;
				}
				.tripzzy-booking-notes li.tripzzy-guest-note .tripzzy-note-content::after{
					border-color: #a7cedc transparent;
				}
				.tripzzy-booking-notes li.deleting-note::before{
					content:'';
					background:#fff;
					opacity:0.6;
					z-index: 111;
					position: absolute;
					width: 100%;
					height:100%;
					display:block;
				}
				.tripzzy-booking-notes li .tripzzy-note-content p {
					margin: 0;
					padding: 0;
					word-wrap: break-word;
				}
				.tripzzy-booking-notes li.empty-notes .tripzzy-note-content{
					padding:0;
					background:transparent;
				}
				.tripzzy-booking-notes .tripzzy-note-meta{
					padding: 10px;
					color: #999;
					margin: 0;
					font-size: 11px;
				}


				.tripzzy-add-booking-note{
					padding: 10px;
					background: #eee;
					border-radius: var(--tripzzy-rounded);
				}
				.tripzzy-add-booking-note textarea{
					width: 100%;
					height:60px
				}
				.tripzzy-add-note-dropdown-wrapper{
					display: flex;
					justify-content:space-between;
				}
				.tripzzy-add-note-dropdown-wrapper select{
					min-width:160px;
				}
				.tripzzy-delete-note{
					color:#a00;
				}
			</style>
			<ul class="tripzzy-booking-notes">
				<?php if ( is_array( $notes ) && count( $notes ) > 0 ) : ?>
					<?php
					foreach ( $notes as $note ) :
						$note_class   = $note->guest_note ? 'tripzzy-guest-note' : '';
						$placeholder  = 'tripzzy-guest-note' === $note_class ? __( 'Guest Note', 'tripzzy' ) : __( 'Private Note', 'tripzzy' );
						$date_created = $note->date_created;
						$comment_date = $date_created->format( \tripzzy_date_format() );
						$comment_time = $date_created->format( \tripzzy_time_format() );
						?>
						<li rel="<?php echo absint( $note->id ); ?>" class="tripzzy-note <?php echo esc_attr( $note_class ); ?>" title="<?php echo esc_attr( $placeholder ); ?>" >
							<div class="tripzzy-note-content">
								<?php echo wp_kses_post( wpautop( wptexturize( make_clickable( $note->content ) ) ) ); ?>
							</div>
							<p class="tripzzy-note-meta">
								<abbr class="exact-date" title="<?php echo esc_attr( $comment_date ); ?>">
									<?php
									/* translators: $1: Date created, $2 Time created */
									printf( esc_html__( 'added on %1$s at %2$s', 'tripzzy' ), esc_html( $comment_date ), esc_html( $comment_time ) );
									?>
								</abbr>
								<?php
								if ( 'system' !== $note->added_by ) :
									/* translators: %s: note author */
									printf( ' ' . esc_html__( 'by %s', 'tripzzy' ), esc_html( $note->added_by ) );
								endif;
								?>
								<a href="#" class="tripzzy-delete-note" role="button"><?php esc_html_e( 'Delete note', 'tripzzy' ); ?></a>
							</p>
						</li>
					<?php endforeach; ?>
				<?php else : ?>
					<li class="tripzzy-note empty-notes">
						<div class="tripzzy-note-content">
							<p><?php esc_html_e( 'There are no notes yet.', 'tripzzy' ); ?></p>
						</div>
					</li>
				<?php endif; ?>
			</ul>
			
			<div class="tripzzy-add-booking-note">
				<p>
					<label for="tripzzy-add-booking-note"><?php esc_html_e( 'Add note', 'tripzzy' ); ?></label>
					<textarea type="text" name="booking_note" id="tripzzy-add-booking-note" class="input-text" cols="20" rows="5"></textarea>
				</p>
				<p class="tripzzy-add-note-dropdown-wrapper">
					<label for="tripzzy-booking-note-type" class="screen-reader-text"><?php esc_html_e( 'Note type', 'tripzzy' ); ?></label>
					<select name="booking_note_type" id="tripzzy-booking-note-type">
						<option value=""><?php esc_html_e( 'Private note', 'tripzzy' ); ?></option>
						<option value="guest"><?php esc_html_e( 'Note to guest', 'tripzzy' ); ?></option>
					</select>
					<button type="button" class="button tripzzy-add-note"><?php esc_html_e( 'Add', 'tripzzy' ); ?></button>
				</p>
			</div>
			
			<?php
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}

		/**
		 * Save post meta for Booking data (Only for admin section).
		 *
		 * @param int $booking_id Booking ID.
		 * @since 1.2.9 Return if post type not equal to tripzzy_booking.
		 * @since 1.0.0
		 */
		public function save_meta( $booking_id ) {
			if ( ! Nonce::verify() ) {
				return;
			}
			$post_type = get_post_type( $booking_id );
			if ( self::$post_type !== $post_type ) {
				return;
			}
			$values = MetaHelpers::get_post_meta( $booking_id, 'checkout_info' );
			if ( ! $values || ! is_array( $values ) ) {
				$values = array();
			}
			// First name and last name to check and update title.
			$first_name = '';
			$last_name  = '';

			$fields = CheckoutForm::get_fields();
			foreach ( $fields as $field ) {
				$type = $field['type'];
				if ( 'wrapper' === $type ) {
					$wrapper_fields = $field['children'];
					foreach ( $wrapper_fields as $wrapper_field ) {
						$name = $wrapper_field['name'];
						// Nonce already verified using Nonce::verify method.
						if ( isset( $_POST[ $name ] ) ) { // @codingStandardsIgnoreLine
							$value = sanitize_text_field( wp_unslash( $_POST[ $name ] ) ); // @codingStandardsIgnoreLine
							// Update first and last name value.
							if ( 'billing_first_name' === $name ) {
								$first_name = $value;
							}
							if ( 'billing_last_name' === $name ) {
								$last_name = $value;
							}
							$values[ $name ] = $value;
						}
					}
				} else {
					$name = $field['name'];
					if ( isset( $_POST[ $name ] ) ) { // @codingStandardsIgnoreLine
						$values[ $name ] = sanitize_text_field( wp_unslash( $_POST[ $name ] ) ); // @codingStandardsIgnoreLine
					}
				}

				// change title if billing name changed.
				$existing_title = get_the_title( $booking_id );
				$fullname       = trim( sprintf( '%s %s', $first_name, $last_name ) );
				$new_title      = trim( sprintf( '#%d %s', $booking_id, $fullname ) );
				if ( trim( $existing_title ) !== $new_title ) {
					$post_args = array(
						'ID'         => $booking_id,
						'post_title' => $new_title,
					);
					wp_update_post( $post_args );
				}
			}
			MetaHelpers::update_post_meta( $booking_id, 'checkout_info', $values );
			// Booking Status Update.
			$old_booking_status = MetaHelpers::get_post_meta( $booking_id, 'booking_status' );
			$new_booking_status = isset( $_POST['booking_status'] ) ? sanitize_text_field( wp_unslash( $_POST['booking_status'] ) ) : ''; // @codingStandardsIgnoreLine
			if ( $old_booking_status !== $new_booking_status ) {
				MetaHelpers::update_post_meta( $booking_id, 'booking_status', $new_booking_status );
				$send_notification = ! ! isset( $_POST['send_notification'] ) ? sanitize_text_field( wp_unslash( $_POST['send_notification'] ) ) : false; // @codingStandardsIgnoreLine

				/**
				 * Trigger on Booking Status Change.
				 *
				 * @since 1.0.0
				 */
				do_action( 'tripzzy_booking_status_changed', $booking_id, $send_notification, $new_booking_status, $old_booking_status );

				/**
				 * Booking Status hook is available for each booking status individually.
				 *
				 * @since 1.0.6
				 */
				do_action( 'tripzzy_booking_status_' . $new_booking_status, $booking_id, $send_notification );
			}
		}

		/**
		 * Admin Column Heading
		 *
		 * @param array $columns List of column heading.
		 *
		 * @since 1.0.0
		 * @since 1.0.6 Added booking date column.
		 * @return array
		 */
		public function admin_column_headings( $columns ) {
			unset( $columns['date'] );
			unset( $columns['comments'] );
			$columns['trip_name']      = __( 'Trip Name', 'tripzzy' );
			$columns['booking_status'] = __( 'Booking Status', 'tripzzy' );
			$columns['trip_total']     = __( 'Trip Total', 'tripzzy' );
			$columns['paid_amount']    = __( 'Paid Amount', 'tripzzy' );
			$columns['booking_date']   = __( 'Booked Date', 'tripzzy' );

			return $columns;
		}

		/**
		 * Admin Column Heading
		 *
		 * @param string $column_name Name of the column.
		 * @param string $booking_id Booking id.
		 * @return void
		 */
		public function admin_column_contents( $column_name, $booking_id ) {
			$settings = Settings::get();
			$currency = MetaHelpers::get_post_meta( $booking_id, 'currency' ); // Currency is in booking id.
			if ( ! $currency ) {
				$currency = $settings['currency']; // Fallback. just in case no currency data in booking due to error.
			}
			switch ( $column_name ) {
				case 'trip_name':
					$trip_ids = Bookings::get_trip_ids( $booking_id );
					foreach ( $trip_ids as $trip_id ) :
						?>
						<a class="tz-booking-trip-name" href="<?php echo esc_url( get_permalink( $trip_id ) ); ?>"><?php echo esc_html( get_the_title( $trip_id ) ); ?></a>
							<?php
					endforeach;
					break;
				case 'booking_status':
					$key = MetaHelpers::get_post_meta( $booking_id, 'booking_status' );
					if ( ! $key ) {
						$key = 'pending';
					}
					?>
					<span class="tz-booking-status tz-booking-status-<?php echo esc_attr( $key ); ?>">
						<?php echo esc_html( Bookings::get_booking_status( $booking_id ) ); ?>
					</span>
						<?php
					break;
				case 'paid_amount':
					$paid_amount = Payment::get_total( $booking_id );
					echo esc_html( $currency . ' ' . Amount::format( $paid_amount ) );
					break;
				case 'trip_total':
					$total = Bookings::get_total( $booking_id );
					echo esc_html( $currency . ' ' . Amount::format( $total ) );
					break;

				case 'booking_date':
					$published_time  = get_the_time( 'U' );
					$current_time    = current_time( 'timestamp' ); // @phpcs:ignore
					$time_diff       = $current_time - $published_time;
					$human_time_diff = human_time_diff( $published_time, $current_time );
					?>
					<span title="<?php echo esc_attr( get_the_date( 'j F, Y H:i:s (e)' ) ); ?>"><?php echo esc_html( $human_time_diff . ' ago' ); ?></span>
					<?php
					break;
				default:
					break;
			} // end switch
		}

		/**
		 * Make admin column sortable.
		 *
		 * @param array $columns List of columns.
		 *
		 * @since 1.0.6
		 * @return array;
		 */
		public function make_column_sortable( $columns ) {
			$columns['booking_date'] = 'booking_date';
			return $columns;
		}

		/**
		 * Make admin column sortable functional.
		 *
		 * @param array $query Query args for pre_get_posts.
		 *
		 * @since 1.0.6
		 * @return array;
		 */
		public function admin_column_sortable_query( $query ) {
			if ( ! is_admin() || ! $query->is_main_query() || get_query_var( 'post_type' ) !== self::$post_type ) {
				return;
			}
			if ( 'booking_date' === $query->get( 'orderby' ) ) {
				$query->set( 'orderby', 'date' );
			}
		}
	}
}
