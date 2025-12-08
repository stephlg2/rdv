<?php
/**
 * Customer Class to manage Customer.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Helpers;

use Tripzzy\Core\Traits\DataTrait;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Bookings;
use Tripzzy\Core\PostTypes\CustomerPostType;
use Tripzzy\Core\Forms\Form;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Helpers\EscapeHelper;
use Tripzzy\Core\Bases\EmailBase;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\Customer' ) ) {
	/**
	 * Customer Class Defination.
	 *
	 * @since 1.0.0
	 */
	class Customer {

		use DataTrait;

		/**
		 * Post Type name
		 *
		 * @var string
		 */
		private static $post_type = 'tripzzy_customer';

		/**
		 * Initialize Customer class.
		 *
		 * @return void
		 */
		public static function init() {
			// Create a user while making a booking.
			add_action( 'tripzzy_after_booking', array( __CLASS__, 'create_customer_on_booking' ), 40, 2 );
		}

		/**
		 * Create customer on booking process if not already created.
		 *
		 * @param int   $booking_id Booking ID.
		 * @param array $data Customer detail.
		 * @return void
		 */
		public static function create_customer_on_booking( $booking_id, $data ) {
			$checkout_info = MetaHelpers::get_post_meta( $booking_id, 'checkout_info' );
			$customer_id   = self::create( $checkout_info );

			// Customer created without error.
			if ( $customer_id && ! is_wp_error( $customer_id ) ) {
				MetaHelpers::update_post_meta( $booking_id, 'customer_id', $customer_id );

				$user_id = get_current_user_id();
				/**
				 * Also updaated this latter.
				 * if not logged in. 'Core\Helpers\User\create_user_on_booking' will create user and update meta here.
				 */
				if ( $user_id ) {
					MetaHelpers::update_post_meta( $customer_id, 'user_id', $user_id );
				}

				/**
				 * Hook: tripzzy_customer_data_updated.
				 *
				 * @hooked Tripzzy\Core\Helpers\User->create_user_on_booking - 10
				 */
				do_action( 'tripzzy_customer_data_updated', $customer_id, $booking_id, $data );
			}
		}

		/**
		 * Create a new User.
		 *
		 * @param  array $customer_data Customer data to create.
		 * @return int|WP_Error Returns WP_Error on failure, Int (customer ID) on success.
		 */
		public static function create( $customer_data ) {
			// Error handling part remaining.
			$email = $customer_data['billing_email'] ?? '';
			// Check the email address.
			if ( empty( $email ) || ! is_email( $email ) ) {
				return;
			}

			// Need to add email in seprate post meta.
			unset( $customer_data['email'] );

			$customer_id = self::get_customer_id_by_email( $email );

			if ( empty( $customer_id ) ) { // Need to create new customer.
				// Add New customer.
				$post_args   = array(
					'post_title'   => 'Customer',
					'post_content' => '',
					'post_status'  => 'publish',
					'post_slug'    => uniqid(),
					'post_type'    => self::$post_type,
				);
				$customer_id = wp_insert_post( $post_args, true );
				// Update Booking Data.
				$first_name = isset( $customer_data['billing_first_name'] ) ? $customer_data['billing_first_name'] : '';
				$last_name  = isset( $customer_data['billing_last_name'] ) ? $customer_data['billing_last_name'] : '';
				$fullname   = trim( sprintf( '%s %s', $first_name, $last_name ) );
				$post_args  = array(
					'ID'         => $customer_id,
					'post_title' => $fullname,
				);
				wp_update_post( $post_args );

				// Update customer metas.
				MetaHelpers::update_post_meta( $customer_id, 'customer_email', $email );
				MetaHelpers::update_post_meta( $customer_id, 'customer_data', $customer_data );

				do_action( 'tripzzy_customer_created', $customer_id, $email, $customer_data );
			} else {
				// Update post modified date to display last booked date.
				$now       = current_time( 'mysql' );
				$now_gmt   = current_time( 'mysql', 1 );
				$post_args = array(
					'ID'                => $customer_id,
					'post_modified'     => $now,
					'post_modified_gmt' => $now_gmt,
				);
				wp_update_post( $post_args );
			}

			return $customer_id;
		}

		/**
		 * Get Customer by email id.
		 *
		 * @param  array $email Customer email id.
		 * @return int|WP_Error Returns WP_Error on failure, Int (customer ID) on success.
		 */
		public static function get_customer_id_by_email( $email ) {

			// Check the email address.
			if ( empty( $email ) || ! is_email( $email ) ) {
				return;
			}

			global $wpdb;

			$meta_key = self::get_prefix( 'customer_email' );

			$sql = $wpdb->prepare(
				"
				SELECT POST_META.* FROM (SELECT post_id
				FROM $wpdb->postmeta
				WHERE meta_key = %s
				AND meta_value = %s) POST_META 
				JOIN $wpdb->posts POSTS on POST_META.post_id = POSTS.ID WHERE POSTS.post_type=%s
			",
				$meta_key,
				esc_sql( $email ),
				esc_sql( self::$post_type )
			);

			$results = $wpdb->get_results( $sql ); // @phpcs:ignore

			if ( empty( $results ) ) {
				return false;
			}

			$customer_id = $results['0']->post_id;
			if ( tripzzy_post_exists( $customer_id ) && get_post_type( $customer_id ) === self::$post_type ) {
				return absint( $customer_id );
			}
		}

		/**
		 * Get Customer bookings.
		 *
		 * @param  array $customer_id Customer id.
		 * @return array
		 */
		public static function get_bookings( $customer_id ) {
			$customer = get_post( $customer_id );
			$args     = array(
				'post_type'      => 'tripzzy_booking',
				'post_status'    => 'publish',
				'posts_per_page' => -1, // need to remove after dashboard bookings pagination and booking history in customer section.
				'meta_key'       => self::get_prefix( 'customer_id' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $customer_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'order'          => 'DESC',
				'orderby'        => 'ID',
			);
			$query    = new \WP_Query( $args );

			// Loop.
			$bookings       = array();
			$paid_bookings  = 0;
			$total_payments = array();
			while ( $query->have_posts() ) {
				$query->the_post();

				$booking_id  = get_the_ID();
				$trip_ids    = Bookings::get_trip_ids( $booking_id );
				$payment_ids = MetaHelpers::get_post_meta( $booking_id, 'payment_ids' );

				$trip_names = array_map(
					function ( $trip_id ) {
						return get_the_title( $trip_id );
					},
					$trip_ids
				);

				// Calculate paid bookings.
				$paid_booking = ! empty( $payment_ids );
				if ( $paid_booking ) {
					++$paid_bookings;
					$currency                      = MetaHelpers::get_post_meta( $booking_id, 'currency' ); // Selected currency while booking trip.
					$total_payments[ $currency ][] = MetaHelpers::get_post_meta( $booking_id, 'total_payment' ); // Total payment for each booking.
				}

				$bookings[] = array(
					'id'           => get_the_ID(),
					'title'        => get_the_title(),
					'status'       => 'Confirmed',
					'trips'        => implode( ', ', $trip_names ),
					'render'       => Bookings::render( get_the_ID(), true ),
					'booking_date' => get_the_date( 'U', get_the_ID() ),
				);
			}
			wp_reset_postdata();
			// Loop End.

			$data = array(
				'all_bookings'  => $query->found_posts, // All bookings count.
				'paid_bookings' => $paid_bookings, // Paid bookings count.
				'total_spent'   => $total_payments, // Total Paid amount by customer.
				'bookings'      => $bookings, // Booking data.
				'last_active'   => $customer->post_modified ? wp_date( 'Y-m-d', strtotime( $customer->post_modified ) ) : null,
			);
			return $data;
		}

		/**
		 * It renders the form fields for the booking meta fields
		 *
		 * @param int  $customer_id The ID of the booking post.
		 * @param bool $has_return If true, the function will return the output instead of echoing it.
		 *
		 * @return the fields that are being passed to the Form::render function.
		 */
		public static function render( $customer_id, $has_return = false ) {
			$fields = CustomerPostType::get_customer_metafield_fields( $customer_id );

			if ( ! $fields ) {
				return;
			}

			ob_start();
			// current render_customer_details method also used in email template. so rendered style from email template.
			EmailBase::email_style();

			Form::render( compact( 'fields' ) );

			$contents = ob_get_contents();
			ob_end_clean();

			if ( $has_return ) {
				return $contents;
			}

			$allowed_html = EscapeHelper::get_allowed_html();
			echo wp_kses( $contents, $allowed_html );
		}

		/**
		 * It renders the form fields for the booking meta fields
		 *
		 * @param int  $customer_id The ID of the booking post.
		 * @param bool $has_return If true, the function will return the output instead of echoing it.
		 *
		 * @return the fields that are being passed to the Form::render function.
		 */
		public static function render_booking_history( $customer_id, $has_return = false ) {
			$booking_data      = self::get_bookings( $customer_id );
			$customer_bookings = $booking_data['bookings'] ?? array();

			ob_start();
			if ( ! empty( $customer_bookings ) ) {
				?>
				<table class="widefat tripzzy-customer-booking-history">
					<thead>
						<tr>
							<td><?php esc_html_e( 'S/N', 'tripzzy' ); ?></td>
							<td><?php esc_html_e( 'Bookings', 'tripzzy' ); ?></td>
							<td><?php esc_html_e( 'Booked on', 'tripzzy' ); ?></td>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $customer_bookings as $index => $booking ) :
							$url = sprintf( 'post.php?post=%s&action=edit', $booking['id'] ?? '' );
							?>
							<tr>
								<td><?php echo esc_html( $index + 1 ); ?></td>
								<td><a href="<?php echo esc_url( $url ); ?>" target="_blank"><?php echo esc_html( $booking['title'] ?? '' ); ?></a></td>
								<td><?php echo esc_html( date_i18n( 'Y-m-d H:i:s', $booking['booking_date'] ?? '' ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php

			}
			$contents = ob_get_contents();
			ob_end_clean();

			if ( $has_return ) {
				return $contents;
			}

			echo wp_kses_post( $contents );
		}
	}
}
