<?php
/**
 * Tripzzy Bookings.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\ErrorMessage;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\Page;
use Tripzzy\Core\Helpers\Amount;
use Tripzzy\Core\Helpers\EscapeHelper;
use Tripzzy\Core\Image;
use Tripzzy\Core\Forms\Form;
use Tripzzy\Core\Forms\CheckoutForm;
use Tripzzy\Core\PostTypes\BookingPostType;
use Tripzzy\Core\Payment;
use Tripzzy\Core\Payment\PaymentGateways;
use Tripzzy\Core\Bases\EmailBase;

if ( ! class_exists( 'Tripzzy\Core\Bookings' ) ) {
	/**
	 * Bookings main class.
	 */
	class Bookings {

		/**
		 * Post Type name
		 *
		 * @var string
		 */
		private static $post_type = 'tripzzy_booking';

		/**
		 * Initialize bookings.
		 *
		 * @return void
		 */
		public static function init() {
			add_action( 'template_redirect', array( __CLASS__, 'init_bookings' ), 100 );
		}

		/**
		 * Start Booking Process.
		 * Always listen for booking.
		 *
		 * @return mixed
		 */
		public static function init_bookings() {

			if ( ! Nonce::verify() ) {
				return;
			}

			// Nonce already verified using Nonce::verify method.
			$tripzzy_action = isset($_POST[ 'tripzzy_action' ]) ?  sanitize_text_field( wp_unslash( $_POST[ 'tripzzy_action' ] ) ) : ''; // @codingStandardsIgnoreLine
			if ( 'tripzzy_book_now' !== $tripzzy_action ) {
				return;
			}
			$nonce_name           = Nonce::get_nonce_name();
			$field_names          = array(
				$nonce_name, // nonce need to pass in thankyou page url.
				'tripzzy_action',
				'payment_details',
				'currency',
				'payment_amount',
				'payment_mode',
			);
			$checkout_field_names = CheckoutForm::get_field_names();

			$field_names = wp_parse_args( $checkout_field_names, $field_names );

			$data = array(); // Add Post data array from booking form fields.
			foreach ( $field_names as $field_name ) {
				switch ( $field_name ) {
					case 'billing_email':
						// Nonce already verified using Nonce::verify method.
						$data[ $field_name ] = isset( $_POST[ $field_name ] ) ? sanitize_email( wp_unslash( $_POST[ $field_name ] ) ) : ''; // @codingStandardsIgnoreLine
						break;
					default:
						// Nonce already verified using Nonce::verify method.
						$data[ $field_name ] = isset( $_POST[ $field_name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) ) : ''; // @codingStandardsIgnoreLine
						break;
				}
			}

			// Book Now.
			$cart          = tripzzy()->cart;
			$cart_contents = $cart->get_cart_contents();
			$totals        = $cart->get_totals();

			do_action( 'tripzzy_before_booking', $data );

			$booking_id = self::insert_booking( $data );

			// Update additional metas.
			MetaHelpers::update_post_meta( $booking_id, 'cart_contents', $cart_contents ); // Cart Data.
			MetaHelpers::update_post_meta( $booking_id, 'totals', $totals ); // Cart Total Data.
			// To protect other booking data.
			MetaHelpers::update_post_meta( $booking_id, 'key', $data['tripzzy_nonce'] ); // Save nonce value as key to verify booking.

			/**
			 * Hook: tripzzy_after_booking.
			 *
			 * @hooked Tripzzy\Core\Payment->add_payment_data - 10
			 * @hooked Tripzzy\Core\Cart->empty_cart - 20;
			 * @hooked Tripzzy\Core\SendEmails->send_booking_emails - 30;
			 * @hooked Tripzzy\Core\Helpers\Customer->create_customer_on_booking - 40;
			 * @hooked Tripzzy\Core\Payment\PaymentGateways\BankTransfer->update_booking_status_to_on_hold - 50;
			 */
			do_action( 'tripzzy_after_booking', $booking_id, $data );

			$thankyou_page_url = Page::get_url( 'thankyou' );
			$thankyou_page_url = add_query_arg( 'tripzzy_key', $data['tripzzy_nonce'], $thankyou_page_url );
			$thankyou_page_url = add_query_arg( 'booking_id', $booking_id, $thankyou_page_url );
			$thankyou_page_url = apply_filters( 'tripzzy_filter_thankyou_page_url', $thankyou_page_url );

			wp_safe_redirect( $thankyou_page_url );
			exit;
		}

		/**
		 * Insert Booking.
		 *
		 * This will not handle (save post meta) cart_content and totals (amount) data. So need to insert this by self.
		 *
		 * Method seperated from Bookings::init_bookings method.
		 *
		 * @param array $data Date need to insert in bookings.
		 *
		 * @since 1.0.7
		 * @return int $booking_id
		 */
		public static function insert_booking( $data ) {
			if ( ! is_array( $data ) || ( is_array( $data ) && ! count( $data ) ) ) {
				return;
			}
			// Add New Booking.
			$post_args  = array(
				'post_title'   => 'Book now',
				'post_content' => '',
				'post_status'  => 'publish',
				'post_slug'    => uniqid(),
				'post_type'    => self::$post_type,
			);
			$booking_id = wp_insert_post( $post_args, true );

			// Update Booking Data.
			$first_name = isset( $data['billing_first_name'] ) ? $data['billing_first_name'] : '';
			$last_name  = isset( $data['billing_last_name'] ) ? $data['billing_last_name'] : '';
			$fullname   = trim( sprintf( '%s %s', $first_name, $last_name ) );
			$post_args  = array(
				'ID'         => $booking_id,
				'post_title' => sprintf( '#%s %s', $booking_id, $fullname ),
			);
			wp_update_post( $post_args );

			// Update Post metas. Common metas.
			MetaHelpers::update_post_meta( $booking_id, 'checkout_info', $data ); // checkout info.
			MetaHelpers::update_post_meta( $booking_id, 'booking_status', 'pending' );

			/**
			 * Update user data in bookings if logged in.
			 * If not logged in. 'Core\Helpers\User::create_user_on_booking' will create user and update meta.
			 * create_user_on_booking will create user if create user on booking is enabled in settings.
			 */
			$user_id = get_current_user_id();
			if ( $user_id ) {
				MetaHelpers::update_post_meta( $booking_id, 'user_id', $user_id );
			}

			return $booking_id;
		}

		/**
		 * It returns an array of trip ids from a booking id.
		 *
		 * @param int $booking_id The booking ID.
		 *
		 * @return array|null Array of trip ids.
		 */
		public static function get_trip_ids( $booking_id ) {
			if ( $booking_id ) {
				$cart_contents = MetaHelpers::get_post_meta( $booking_id, 'cart_contents' );

				if ( $cart_contents ) {
					return array_values( wp_list_pluck( $cart_contents, 'trip_id' ) );
				}
				return array();
			}
		}

		/**
		 * Return the total amount data for booked trips. like: gross_total, discount, net total etc.
		 *
		 * @param int $booking_id Booking Id.
		 * @return number
		 */
		public static function get_totals( $booking_id ) {
			$totals = MetaHelpers::get_post_meta( $booking_id, 'totals' );
			if ( ! $totals || empty( $totals ) ) { // Default data from cart. @todo need to fetch it from cart default data.
				$totals = array(
					'gross_total'    => 0, // i.e 1000  Item Total.
					'discount_total' => 0, // i.e -100  Total applicable discount amount (assuming 10%).
					'sub_total'      => 0, // i.e  900  ( Item Total - Total applicable discount amount).
					'tax_total'      => 0, // i.e +119  // Assuming 13% tax.
					'net_total'      => 0, // i.e 1019.
				);

			}
			return $totals;
		}

		/**
		 * Return the total amount for booked trips.
		 *
		 * @param int $booking_id Booking Id.
		 * @return number
		 */
		public static function get_total( $booking_id ) {
			$totals = self::get_totals( $booking_id );
			return $totals['net_total'] ?? 0;
		}

		/**
		 * Render Booking summary as per booking id.
		 *
		 * @param int  $booking_id Booking ID.
		 * @param bool $has_return Whether return or echo the markups.
		 *
		 * @since 1.1.8
		 * @since 1.2.2 Minor issue with undefined index if no payment gateway is active.
		 * @return void
		 */
		public static function render_booking_summary( $booking_id, $has_return = false ) {

			if ( ! $booking_id ) {
				return;
			}

			$cart_contents = MetaHelpers::get_post_meta( $booking_id, 'cart_contents' );
			$totals        = MetaHelpers::get_post_meta( $booking_id, 'totals' );
			$checkout_info = MetaHelpers::get_post_meta( $booking_id, 'checkout_info' );

			// summary.
			$booking_date = get_the_date( '', $booking_id );
			$payment_mode = $checkout_info['payment_mode'] ?? '';

			$payment_gateways = PaymentGateways::get_enabled_gateways();
			$payment_gateway  = array_filter(
				$payment_gateways,
				function ( $method ) use ( $payment_mode ) {
					return $payment_mode === $method['name'];
				}
			);

			$payment_method = $payment_mode;
			// Reset array keys.
			if ( is_array( $payment_gateway ) && count( $payment_gateway ) > 0 ) {
				$payment_gateway_data = array_values( $payment_gateway )[0];
				$payment_method       = $payment_gateway_data['title'] ?? '';
			}
			ob_start();
			?>
			<label class="tripzzy-form-label tripzzy-form-label-wrapper"><?php esc_html_e( 'Booking Summary', 'tripzzy' ); ?></label>
			<ul class="tripzzy-booking-summary-list">
				<li class="tripzzy-booking-summary-list-item">
					<span class="tripzzy-booking-summary-list-item__key" ><?php esc_html_e( 'Booking ID', 'tripzzy' ); ?></span>
					<span class="tripzzy-booking-summary-list-item__value">#<?php echo absint( $booking_id ); ?></span>
				</li>
				<li class="tripzzy-booking-summary-list-item">
					<span class="tripzzy-booking-summary-list-item__key" ><?php esc_html_e( 'Booking Date', 'tripzzy' ); ?></span>
					<span class="tripzzy-booking-summary-list-item__value"><?php echo esc_html( $booking_date ); ?></span>
				</li>
				<li class="tripzzy-booking-summary-list-item">
					<span class="tripzzy-booking-summary-list-item__key" ><?php esc_html_e( 'Total', 'tripzzy' ); ?></span>
					<span class="tripzzy-booking-summary-list-item__value"><?php echo esc_html( Amount::display( $totals['net_total'] ?? 0 ) ); ?></span>
				</li>
				<li class="tripzzy-booking-summary-list-item">
					<span class="tripzzy-booking-summary-list-item__key" ><?php esc_html_e( 'Email', 'tripzzy' ); ?></span>
					<span class="tripzzy-booking-summary-list-item__value"><?php echo esc_html( $checkout_info['billing_email'] ?? '' ); ?></span>
				</li>
				<li class="tripzzy-booking-summary-list-item">
					<span class="tripzzy-booking-summary-list-item__key" ><?php esc_html_e( 'Payment method', 'tripzzy' ); ?></span>
					<span class="tripzzy-booking-summary-list-item__value"><?php echo esc_html( $payment_method ); ?></span>
				</li>
			</ul>
			<?php
			$contents = ob_get_contents();
			ob_end_clean();
			if ( $has_return ) {
				return $contents;
			}
			echo wp_kses_post( $contents );
		}

		/**
		 * Render Booking details as per booking id.
		 *
		 * @note Need to use inline style here because this method is also used in email.
		 *
		 * @param int  $booking_id Booking ID.
		 * @param bool $has_return Whether return or echo the markups.
		 *
		 * @since 1.2.9 Added support for trip extras.
		 * @since 1.1.3 Added time support.
		 * @since 1.0.8 Check category exist before returning term name. if ( $category ).
		 * @since 1.0.0
		 * @return void
		 */
		public static function render_booking_details( $booking_id, $has_return = false ) {

			if ( ! $booking_id ) {
				return;
			}

			$cart_contents = MetaHelpers::get_post_meta( $booking_id, 'cart_contents' );
			$totals        = MetaHelpers::get_post_meta( $booking_id, 'totals' );

			ob_start();
			$i = 1;
			?>
			<label class="tripzzy-form-label tripzzy-form-label-wrapper"><?php esc_html_e( 'Booking Detail', 'tripzzy' ); ?></label>
			<?php if ( is_array( $cart_contents ) && ! empty( $cart_contents ) ) { ?>
				<table style="border-collapse:collapse; background:#fff;width:100%; margin:10px 0 20px; border-radius:5px; overflow:hidden">
					<tbody>
						<?php
						$has_extras_selected = false;
						foreach ( $cart_contents as $cart_key => $item ) :
							$trip_id    = $item['trip_id'];
							$package_id = $item['package_id'];
							$trip       = new Trip( $trip_id );
							$packages   = $trip->packages();
							$package    = $packages->get_package( $package_id );

							$package_title = '';
							if ( $package ) {
								$package_title = $package->get_title();
							}
							$row_style = 0 === $i % 2 ? 'background-color:#eaf8e6' : '';
							// Extras.
							if ( isset( $item['extras'] ) && ! $has_extras_selected ) {
								$selected_extras_count = array_sum( array_values( $item['extras'] ) );
								$has_extras_selected   = $selected_extras_count > 0;
							}
							?>
							<tr valign="top" style="<?php echo esc_attr( $row_style ); ?>" >
								<td style="width:150px; padding:10px; font-size:14px; border-left:1px solid #e1e1e1">
									<?php
									$time       = $item['time'] ?? '';
									$start_date = new \DateTime( $item['start_date'] );
									echo esc_html( $start_date->format( tripzzy_date_format() ) );

									if ( $time ) {
										$time = new \DateTime( $time );
										echo esc_html( ' ' . $time->format( tripzzy_time_format() ) );
									}
									?>
								</td>
								<td style="width:400px; padding:10px; font-size:14px;">
									<p style="margin:0"><strong style="display:inline-block; width:60px">Trip</strong><a href="<?php echo esc_url( get_permalink( $trip_id ) ); ?>" target="_blank"><?php echo esc_html( $item['title'] ); ?></a></p>
									<p style="margin:0"><strong style="display:inline-block; width:60px">Package</strong><?php echo esc_html( $package_title ); ?></p>
									<ul style="list-style:none; padding-left:10px;max-width:200px">
										<?php
										$categories       = $item['categories'];
										$categories_price = $item['categories_price'];
										$price_per        = $item['price_per'];
										if ( is_array( $categories ) && count( $categories ) > 0 ) {
											foreach ( $categories as $category_id => $no_of_person ) {
												if ( ! $no_of_person ) {
													continue;
												}
												$category = get_term( $category_id );
												if ( $category ) {
													if ( 'person' === $price_per ) :
														?>
														<li>
															<?php echo esc_html( $category->name ); ?>
															<span style="display:inline-block;">( <?php echo esc_html( $no_of_person ); ?> * <?php echo esc_html( Amount::display( $categories_price[ $category_id ] ) ); ?> )</span>
														</li>
													<?php else : ?>
														<li><?php echo esc_html( $category->name ); ?> <span style="display:inline-block;">( <?php echo esc_html( $no_of_person ); ?> )</span></li>
														<?php
													endif;
												}
											}
										}

										?>
									</ul>
								</td>
								<?php if ( $has_extras_selected ) : ?>
										<td class="trip-info-extras">
											<?php
											if ( class_exists( 'Tripzzy\Modules\ExtraServices\Core\Helpers\Extras' ) ) {
												foreach ( $item['extras'] as $key => $qty ) :
													if ( ! $qty ) {
														continue;
													}
													$parts = explode( '-', $key );

													$service_id = $parts[0];
													$extras_id  = $parts[1];
													$title      = \Tripzzy\Modules\ExtraServices\Core\Helpers\Extras::get_title( $extras_id, $service_id );
													$price      = \Tripzzy\Modules\ExtraServices\Core\Helpers\Extras::get_price( $extras_id, $service_id );
													?>
												<p>
													<?php echo esc_html( $title ); ?>
													<ul>
														<li>
															<span style="display:inline-block;">( <?php echo esc_html( $qty ); ?> * <?php echo esc_html( Amount::display( $price ) ); ?> )</span>
														</li>
													</ul>
												</p>
													<?php
											endforeach;
											}
											?>
										</td>
										<?php endif; ?>
								<td style="width:100px; padding:10px; font-size:14px; border-right:1px solid #e1e1e1"><strong><?php echo esc_html( Amount::display( $item['item_total'] ) ); ?></strong></td>
							</tr>
							<?php
							++$i;
						endforeach;

						$colspan = 2;
						if ( $has_extras_selected ) {
							$colspan = 3;
						}
						if ( is_array( $totals ) && $totals['discount_total'] > 0 ) :

							?>
							<tr>
								<td colspan="<?php echo esc_attr( $colspan ); ?>" style="width:550px;  font-size:12px; color:#2e2e2e;background-color:#fff;text-transform:uppercase; padding:10px; text-align:left; line-height:1;border-top:1px solid #e1e1e1;  border-left:1px solid #e1e1e1"><?php esc_html_e( 'Gross Total', 'tripzzy' ); ?></td>
								<td style="width:100px;  font-size:12px; color:#2e2e2e;background-color:#fff;text-transform:uppercase; padding:10px; text-align:left; line-height:1;border-top:1px solid #e1e1e1;  border-right:1px solid #e1e1e1"><?php echo esc_html( Amount::display( $totals['gross_total'] ) ); ?></td>
							</tr>
							<tr>
								<td colspan="<?php echo esc_attr( $colspan ); ?>" style="width:550px;  font-size:12px; color:#2e2e2e;background-color:#fff;text-transform:uppercase; padding:10px; text-align:left; line-height:1; border-left:1px solid #e1e1e1"><?php esc_html_e( 'Discount', 'tripzzy' ); ?></td>
								<td style="width:100px;  font-size:12px; color:#2e2e2e;background-color:#fff;text-transform:uppercase; padding:10px; text-align:left; line-height:1; border-right:1px solid #e1e1e1">(<?php echo esc_html( Amount::display( $totals['discount_total'] ) ); ?>)</td>
							</tr>
						<?php endif; ?>
						<tr>
							<td colspan="<?php echo esc_attr( $colspan ); ?>" style="width:150px;  font-size:12px; color:#2e2e2e;background-color:#eceff1;text-transform:uppercase; padding:10px; text-align:left; line-height:1.6; border-left:1px solid #e1e1e1"><b><?php esc_html_e( 'Total', 'tripzzy' ); ?></b></td>
							<td style="width:100px;  font-size:12px; color:#2e2e2e;background-color:#eceff1;text-transform:uppercase; padding:10px; text-align:left; line-height:1.6; border-right:1px solid #e1e1e1;"><b><?php echo esc_html( Amount::display( $totals['net_total'] ?? 0 ) ); ?></b></td>
						</tr>
					</tbody>
					<thead>
						<tr>
							<th style="width:150px; font-size:12px; color:#2e2e2e;background-color:#eceff1;text-transform:uppercase; padding:10px; text-align:left; line-height:1.6; border-left:1px solid #e1e1e1"><?php esc_html_e( 'Trip Date', 'tripzzy' ); ?></th>
							<th style="width:400px; font-size:12px; color:#2e2e2e;background-color:#eceff1;text-transform:uppercase; padding:10px; text-align:left; line-height:1.6;"><?php esc_html_e( 'Trip Name', 'tripzzy' ); ?></th>
							<?php if ( $has_extras_selected ) : ?>
							<th style="font-size:12px; color:#2e2e2e;background-color:#eceff1;text-transform:uppercase; padding:10px; text-align:left; line-height:1.6;"><?php esc_html_e( 'Extras', 'tripzzy' ); ?></th>
							<?php endif; ?>
							<th style="width:100px; font-size:12px; color:#2e2e2e;background-color:#eceff1;text-transform:uppercase; padding:10px; text-align:left; line-height:1.6; border-right:1px solid #e1e1e1"><?php esc_html_e( 'Total', 'tripzzy' ); ?></th>
						</tr>
					</thead>
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

		/**
		 * Render payment details as per booking id.
		 *
		 * @note Need to use inline style here because this method is also used in email.
		 *
		 * @param int  $booking_id Booking ID.
		 * @param bool $has_return Whether return or echo the markups.
		 *
		 * @return void
		 */
		public static function render_payment_details( $booking_id, $has_return = false ) {

			if ( ! $booking_id ) {
				return;
			}

			$payment_ids = MetaHelpers::get_post_meta( $booking_id, 'payment_ids' );

			ob_start();
			if ( ! empty( $payment_ids ) ) :
				?>
				<label class="tripzzy-form-label tripzzy-form-label-wrapper"><?php esc_html_e( 'Payment Detail', 'tripzzy' ); ?></label> 
				<?php
				Payment::render( $booking_id );
				endif;
				$contents = ob_get_contents();
				ob_end_clean();
			if ( $has_return ) {
				return $contents;
			}
			echo wp_kses_post( $contents );
		}

		/**
		 * It renders the form fields for the booking meta fields
		 *
		 * @param int  $booking_id The ID of the booking post.
		 * @param bool $has_return If true, the function will return the output instead of echoing it.
		 * @param bool $for_email Whether is it for email or not.
		 *
		 * @since 1.0.0
		 * @since 1.0.4 $for_email param added.
		 * @return the fields that are being passed to the Form::render function.
		 */
		public static function render_customer_details( $booking_id, $has_return = false, $for_email = false ) {
			$fields = BookingPostType::get_booking_metafield_fields( $booking_id, $for_email );
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
		 * It renders the booking details and traveler details.
		 *
		 * @param int  $booking_id The booking ID.
		 * @param bool $has_return Whether to return the contents or echo them.
		 *
		 * @return string booking details and traveler details are being returned.
		 */
		public static function render( $booking_id, $has_return = false ) {
			if ( ! $booking_id ) {
				return;
			}

			ob_start();

			self::render_booking_details( $booking_id );
			self::render_customer_details( $booking_id );

			$contents = ob_get_contents();
			ob_end_clean();

			if ( $has_return ) {
				return $contents;
			}

			$allowed_html = EscapeHelper::get_allowed_html();
			echo wp_kses( $contents, $allowed_html );
		}

		/**
		 * Render Booking data.
		 *
		 * @param int  $booking_id Booking ID.
		 * @param bool $has_return Whether return or echo the markups.
		 *
		 * @since 1.2.9
		 * @return mixed
		 */
		public static function render_booking_data( $booking_id, $has_return = false ) {
			$fields = BookingPostType::get_booking_metafield_fields( $booking_id );
			if ( ! $fields ) {
				return;
			}
			$fields2 = BookingPostType::get_booking_metafield_fields( $booking_id, true );

			$strings = Strings::get();
			$labels  = $strings['labels'] ?? array();

			$booking_data_title = sprintf( $labels['booking_details'], $booking_id );
			$status_options     = self::get_booking_status_options();
			$status             = MetaHelpers::get_post_meta( $booking_id, 'booking_status' );
			ob_start();

			?>
			<h2 class="tripzzy-booking-data__heading"><?php echo esc_html( $booking_data_title ); ?></h2>
			<div class="booking-data-column-container">
				<div class="booking-data-column booking-data-column-general">
					<h3><?php echo esc_html( $labels['general'] ); ?></h3>
					<div class="tripzzy-input-field-wrapper">

						<div class="tripzzy-form-field">
							<label class="tripzzy-form-label" for="test"><?php echo esc_html( $labels['booking_date'] ); ?></label>
							<span title=""><?php echo esc_attr( get_the_date( tripzzy_date_format() . ' ' . tripzzy_time_format() . ' (e)' ) ); ?></span>
						</div>
						<div class="tripzzy-form-field">
							<label class="tripzzy-form-label" for="test"><?php echo esc_html( $labels['status'] ?? '' ); ?></label>
							<select name ="booking_status" id="tripzzy-booking-status" >
								<?php foreach ( $status_options as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php echo $value === $status ? esc_attr( 'selected' ) : ''; ?> ><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="tripzzy-form-field">
							<label class="tripzzy-form-label" for="test"><?php echo esc_html( $labels['send_notification'] ); ?></label>
							<label title="Send email if status is changed."> <input type="checkbox" name="send_notification" /><?php esc_html_e( 'Send Notification on Status Change.', 'tripzzy' ); ?></label>
						</div>
					</div>
				</div>
				<div class="booking-data-column booking-data-column-billing-info">
					<div class="booking-data-column-billing-info-edit hidden" >
						<?php Form::render( compact( 'fields' ) ); ?>
					</div>
					<div class="booking-data-column-billing-info-view" >
						<?php Form::render( array( 'fields' => $fields2 ) ); ?>
					</div>
				</div>

			</div>
			<?php
			$contents = ob_get_contents();
			ob_end_clean();

			if ( $has_return ) {
				return $contents;
			}

			$allowed_html = EscapeHelper::get_allowed_html();
			echo wp_kses( $contents, $allowed_html );
		}

		/**
		 * Render Booked Trip details for front end and admin side.
		 *
		 * @param int  $booking_id Booking ID.
		 * @param bool $has_return Whether return or echo the markups.
		 *
		 * @since 1.2.9
		 * @return mixed
		 */
		public static function render_booking_trips( $booking_id, $has_return = false ) {
			if ( ! $booking_id ) {
				return;
			}

			$cart_contents = MetaHelpers::get_post_meta( $booking_id, 'cart_contents' );
			$totals        = MetaHelpers::get_post_meta( $booking_id, 'totals' );

			if ( ! $cart_contents ) {
				return;
			}

			ob_start();
			if ( is_array( $cart_contents ) && ! empty( $cart_contents ) ) {
				?>
				<div class="tripzzy-booking-trips-wrapper">
					<div class="tripzzy-booking-trips-info">
						<table cellspacing="0" cellpadding="0" >
							
							<tbody>
								<?php
								// For Extras.
								$has_extras_selected = false;
								foreach ( $cart_contents as $cart_key => $item ) :
									$trip_id    = $item['trip_id'];
									$package_id = $item['package_id'];
									$trip       = new Trip( $trip_id );
									$packages   = $trip->packages();
									$package    = $packages->get_package( $package_id );

									$package_title = '';
									if ( $package ) {
										$package_title = $package->get_title();
									}
									$time = $item['time'] ?? '';

									// Extras.
									if ( isset( $item['extras'] ) && ! $has_extras_selected ) {
										$selected_extras_count = array_sum( array_values( $item['extras'] ) );
										$has_extras_selected   = $selected_extras_count > 0;
									}
									?>
									<tr valign="top"  >
										<td class="trip-info-date">
											<p>
											<?php echo esc_html( date_i18n( tripzzy_date_format(), strtotime( $item['start_date'] ) ) ); ?>
											<?php
											if ( $time ) {
												$time = new \DateTime( $time );
												?>
												<span class="trip-info-time"> 
													<?php esc_html_e( 'at', 'tripzzy' ); ?>
													<span><?php echo esc_html( ' ' . $time->format( tripzzy_time_format() ) ); ?></span>
												</span>
												<?php
											}
											?>
											</p>
										</td>
										<td class="trip-info-trip-image">
											<p>
												<?php Image::get_thumbnail( $trip_id, 'thumbnail' ); ?>	
											</p>
										</td>
										<td class="trip-info-trip-name">
											<p>
											<a href="<?php echo esc_url( get_permalink( $trip_id ) ); ?>" target="_blank"><?php echo esc_html( $item['title'] ); ?></a></p>
										</td>
										<td class="trip-info-package">
											<p><?php echo esc_html( $package_title ); ?></p>
											<ul>
												<?php
												$categories       = $item['categories'];
												$categories_price = $item['categories_price'];
												$price_per        = $item['price_per'];
												if ( is_array( $categories ) && count( $categories ) > 0 ) {
													foreach ( $categories as $category_id => $no_of_person ) {
														if ( ! $no_of_person ) {
															continue;
														}
														$category = get_term( $category_id );
														if ( $category ) {
															if ( 'person' === $price_per ) :
																?>
																<li>
																	<?php echo esc_html( $category->name ); ?>
																	<span style="display:inline-block;">( <?php echo esc_html( $no_of_person ); ?> * <?php echo esc_html( Amount::display( $categories_price[ $category_id ] ) ); ?> )</span>
																</li>
															<?php else : ?>
																<li><?php echo esc_html( $category->name ); ?> <span style="display:inline-block;">( <?php echo esc_html( $no_of_person ); ?> )</span></li>
																<?php
															endif;
														}
													}
												}

												?>
											</ul>
										</td>
										<?php if ( $has_extras_selected ) : ?>
										<td class="trip-info-extras">
											<?php
											if ( class_exists( 'Tripzzy\Modules\ExtraServices\Core\Helpers\Extras' ) ) {
												foreach ( $item['extras'] as $key => $qty ) :
													if ( ! $qty ) {
														continue;
													}
													$parts = explode( '-', $key );

													$service_id = $parts[0];
													$extras_id  = $parts[1];
													$title      = \Tripzzy\Modules\ExtraServices\Core\Helpers\Extras::get_title( $extras_id, $service_id );
													$price      = \Tripzzy\Modules\ExtraServices\Core\Helpers\Extras::get_price( $extras_id, $service_id );
													?>
												<p>
													<?php echo esc_html( $title ); ?>
													<ul>
														<li>
															<span style="display:inline-block;">( <?php echo esc_html( $qty ); ?> * <?php echo esc_html( Amount::display( $price ) ); ?> )</span>
														</li>
													</ul>
												</p>
													<?php
											endforeach;
											}
											?>
										</td>
										<?php endif; ?>
										<td class="trip-info-total"><p><strong><?php echo esc_html( Amount::display( $item['item_total'] ) ); ?></strong></p></td>
									</tr>
									<?php
								endforeach;
								?>
								
							</tbody>
							<thead>
								<tr>
									<th class="trip-info-date"><?php esc_html_e( 'Trip Date', 'tripzzy' ); ?></th>
									<th class="trip-info-trip-name" colspan="2"><?php esc_html_e( 'Trip Name', 'tripzzy' ); ?></th>
									<th class="trip-info-package"><?php esc_html_e( 'Package', 'tripzzy' ); ?></th>
									<?php if ( $has_extras_selected ) : ?>
									<th class="trip-info-extras"><?php esc_html_e( 'Extras', 'tripzzy' ); ?></th>
									<?php endif; ?>
									<th class="trip-info-total"><?php esc_html_e( 'Total', 'tripzzy' ); ?></th>
								</tr>
							</thead>
						</table>
					</div>
					<div class="tripzzy-booking-trips-info__total">
						<table cellspacing="0" cellpadding="0">
							<?php if ( is_array( $totals ) && $totals['discount_total'] > 0 ) : ?>
								<tr>
									<td class="trip-info-total-label"><?php esc_html_e( 'Subtotal', 'tripzzy' ); ?></td>
									<td class="trip-info-total-amount"><b><?php echo esc_html( Amount::display( $totals['gross_total'] ) ); ?></b></td>
								</tr>
								<tr>
									<td class="trip-info-total-label"><?php esc_html_e( 'Discount', 'tripzzy' ); ?></td>
									<td class="trip-info-total-amount"><b>(<?php echo esc_html( Amount::display( $totals['discount_total'] ) ); ?>)</b></td>
								</tr>
							<?php endif; ?>
							<tr>
								<td class="trip-info-total-label net-total-label"><?php esc_html_e( 'Total', 'tripzzy' ); ?></td>
								<td class="trip-info-total-amount"><b><?php echo esc_html( Amount::display( $totals['net_total'] ?? 0 ) ); ?></b></td>
							</tr>
						</table>
					</div>
				</div>
				<?php
			}
			$contents = ob_get_contents();
			ob_end_clean();

			if ( $has_return ) {
				return $contents;
			}

			$allowed_html = EscapeHelper::get_allowed_html();
			echo wp_kses( $contents, $allowed_html );
		}

		/**
		 * Get Booking status Dropdown option.
		 *
		 * @since 1.0.0
		 * @since 1.1.8 Added Status On Hold.
		 * @return array
		 */
		public static function get_booking_status_options() {
			$status = array(
				'pending'  => __( 'Pending', 'tripzzy' ),
				'on_hold'  => __( 'On Hold', 'tripzzy' ),
				'booked'   => __( 'Booked', 'tripzzy' ),
				'canceled' => __( 'Canceled', 'tripzzy' ),
				'refunded' => __( 'Refunded', 'tripzzy' ),
			);
			return $status;
		}

		/**
		 * Get Booking status.
		 *
		 * @param int $booking_id Booking Id.
		 *
		 * @return string
		 */
		public static function get_booking_status( $booking_id ) {
			if ( ! $booking_id ) {
				return __( 'N/A', 'tripzzy' );
			}
			$key            = MetaHelpers::get_post_meta( $booking_id, 'booking_status' );
			$status_options = self::get_booking_status_options();

			if ( ! $key ) {
				$key = 'pending'; // fallback.
			}
			return isset( $status_options[ $key ] ) ? $status_options[ $key ] : __( 'N/A', 'tripzzy' );
		}

		/**
		 * Adds a note (comment) to the booking. Booking must exist.
		 *
		 * @param  int    $booking_id     Booking ID.
		 * @param  string $note           Note to add.
		 * @param  int    $is_guest_note  Is this a note for the guest?.
		 * @param  bool   $added_by_user  Was the note added by a user?.
		 *
		 * @since 1.1.8
		 * @return int Comment ID.
		 */
		public static function add_note( $booking_id, $note, $is_guest_note = 0, $added_by_user = false ) {

			if ( is_user_logged_in() && $added_by_user ) {
				$user                 = get_user_by( 'id', get_current_user_id() );
				$comment_author       = $user->display_name;
				$comment_author_email = $user->user_email;
			} else {
				$comment_author        = __( 'Tripzzy', 'tripzzy' );
				$comment_author_email  = strtolower( __( 'Tripzzy', 'tripzzy' ) ) . '@';
				$comment_author_email .= \tripzzy_domain_name();
				$comment_author_email  = sanitize_email( $comment_author_email );
			}
			$commentdata = apply_filters(
				'tripzzy_filter_new_booking_note_data',
				array(
					'comment_post_ID'      => $booking_id,
					'comment_author'       => $comment_author,
					'comment_author_email' => $comment_author_email,
					'comment_author_url'   => '',
					'comment_content'      => $note,
					'comment_agent'        => 'Tripzzy',
					'comment_type'         => 'booking_note',
					'comment_parent'       => 0,
					'comment_approved'     => 1,
				),
				array(
					'booking_id'    => $booking_id,
					'is_guest_note' => $is_guest_note,
				)
			);

			$comment_id = wp_insert_comment( $commentdata );

			if ( $is_guest_note ) {
				add_comment_meta( $comment_id, 'is_guest_note', 1 );

				do_action(
					'tripzzy_new_guest_note',
					array(
						'booking_id' => $booking_id,
						'guest_note' => $commentdata['comment_content'],
					)
				);
			}

			/**
			 * Action hook fired after an booking note is added.
			 *
			 * @param int      $booking_note_id Booking note ID.
			 *
			 * @since 1.1.8
			 */
			do_action( 'tripzzy_booking_note_added', $comment_id );

			return $comment_id;
		}

		/**
		 * Get booking notes.
		 *
		 * @param  array $args Query arguments {
		 *     Array of query parameters.
		 *
		 *     @type string $limit           Maximum number of notes to retrieve.
		 *                                   Default empty (no limit).
		 *     @type int    $booking_id      Limit results to those affiliated with a given booking ID.
		 *                                   Default 0.
		 *     @type array  $booking__in     Array of booking IDs to include affiliated notes for.
		 *                                   Default empty.
		 *     @type array  $booking__not_in Array of booking IDs to exclude affiliated notes for.
		 *                                   Default empty.
		 *     @type string $orderby         Define how should sort notes.
		 *                                   Accepts 'date_created', 'date_created_gmt' or 'id'.
		 *                                   Default: 'id'.
		 *     @type string $order           How to booking retrieved notes.
		 *                                   Accepts 'ASC' or 'DESC'.
		 *                                   Default: 'DESC'.
		 *     @type string $type            Define what type of note should retrieve.
		 *                                   Accepts 'customer', 'internal' or empty for both.
		 *                                   Default empty.
		 * }
		 * @since  1.1.8
		 * @return stdClass[]              Array of stdClass objects with booking notes details.
		 */
		public static function get_notes( $args ) {
			$key_mapping = array(
				'limit'           => 'number',
				'booking_id'      => 'post_id',
				'booking__in'     => 'post__in',
				'booking__not_in' => 'post__not_in',
			);

			foreach ( $key_mapping as $query_key => $db_key ) {
				if ( isset( $args[ $query_key ] ) ) {
					$args[ $db_key ] = $args[ $query_key ];
					unset( $args[ $query_key ] );
				}
			}

			// Define orderby.
			$orderby_mapping = array(
				'date_created'     => 'comment_date',
				'date_created_gmt' => 'comment_date_gmt',
				'id'               => 'comment_ID',
			);

			$args['orderby'] = ! empty( $args['orderby'] ) && in_array( $args['orderby'], array( 'date_created', 'date_created_gmt', 'id' ), true ) ? $orderby_mapping[ $args['orderby'] ] : 'comment_ID';

			// Set Booking type.
			if ( isset( $args['type'] ) && 'booking' === $args['type'] ) {
				$args['meta_query'] = array( // @phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'is_guest_note',
						'value'   => 1,
						'compare' => '=',
					),
				);
			} elseif ( isset( $args['type'] ) && 'internal' === $args['type'] ) {
				$args['meta_query'] = array( // @phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'is_guest_note',
						'compare' => 'NOT EXISTS',
					),
				);
			}

			// Set correct comment type.
			$args['type'] = 'booking_note';

			// Always approved.
			$args['status'] = 'approve';

			// Does not support 'count' or 'fields'.
			unset( $args['count'], $args['fields'] );
			remove_filter( 'comments_clauses', array( __CLASS__, 'exclude_booking_comments' ), 10, 1 );

			$notes = get_comments( $args );
			add_filter( 'comments_clauses', array( __CLASS__, 'exclude_booking_comments' ), 10, 1 );

			return array_map( array( __CLASS__, 'get_note' ), $notes );
		}

		/**
		 * Get Note.
		 *
		 * @param mixed $data Either object or numeric.
		 * @since 1.1.8
		 * @return object
		 */
		public static function get_note( $data ) {
			if ( is_numeric( $data ) ) { // when deleting note.
				$data = get_comment( $data );
			}

			return (object) apply_filters(
				'tripzzy_filter_get_booking_note',
				array(
					'id'           => (int) $data->comment_ID,
					'date_created' => new \DateTime( $data->comment_date ),
					'content'      => $data->comment_content,
					'guest_note'   => (bool) get_comment_meta( $data->comment_ID, 'is_guest_note', true ),
					'added_by'     => __( 'Tripzzy', 'tripzzy' ) === $data->comment_author ? 'system' : $data->comment_author,
					'booking_id'   => absint( $data->comment_post_ID ),
				),
				$data
			);
		}

		/**
		 * Exclude booking comments from queries and RSS.
		 *
		 * @param  array $clauses A compacted array of comment query clauses.
		 * @since 1.1.8
		 * @return array
		 */
		public static function exclude_booking_comments( $clauses ) {
			$clauses['where'] .= ( $clauses['where'] ? ' AND ' : '' ) . " comment_type != 'booking_note' ";
			return $clauses;
		}

		/**
		 * Delete an booking note.
		 *
		 * @param  int $note_id Booking note id.
		 * @since  1.1.8
		 * @return bool         True on success, false on failure.
		 */
		public static function delete_note( $note_id ) {
			$note = self::get_note( $note_id );
			if ( $note && wp_delete_comment( $note_id, true ) ) {
				/**
				 * Action hook fired after an booking note is deleted.
				 *
				 * @param int      $note_id Booking note ID.
				 * @param stdClass $note    Object with the deleted booking note details.
				 *
				 * @since 1.1.8
				 */
				do_action( 'tripzzy_note_deleted', $note_id, $note );

				return true;
			}

			return false;
		}
	}
}
