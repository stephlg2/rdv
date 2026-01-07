<?php
/**
 * Admin Booking ajax class.
 *
 * @since 1.3.4
 * @package tripzzy
 */

namespace Tripzzy\Core\Ajax;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Helpers\Amount;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\ErrorMessage;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\Modules;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\TemplateHooks;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Bookings;
use Tripzzy\Core\Cart;
use Tripzzy\Core\Image;

if ( ! class_exists( 'Tripzzy\Core\Ajax\AdminBookingsAjax' ) ) {
	/**
	 * Admin Booking Ajax.
	 *
	 * @since 1.3.4
	 */
	class AdminBookingsAjax {
		use SingletonTrait;

		/**
		 * Constructor.
		 */
		public function __construct() {
			// Remove.
			add_action( 'wp_ajax_tripzzy_remove_cart_item_admin', array( $this, 'remove' ) );
			add_action( 'wp_ajax_nopriv_tripzzy_remove_cart_item_admin', array( $this, 'remove' ) );

			// get Packages.
			add_action( 'wp_ajax_tripzzy_get_packages_admin', array( $this, 'get_packages' ) );
			add_action( 'wp_ajax_nopriv_tripzzy_get_packages_admin', array( $this, 'get_packages' ) );

			// Save Trips in booking.
			add_action( 'wp_ajax_tripzzy_save_booking_admin', array( $this, 'add_booking_item' ) );
			add_action( 'wp_ajax_nopriv_tripzzy_save_booking_admin', array( $this, 'add_booking_item' ) );
		}

		/**
		 * Return calculated totals as per cart contents.
		 *
		 * @param array $cart_contents Cart contents.
		 * @since 1.3.4
		 *
		 * @return array
		 */
		public function calculate_totals( $cart_contents = array() ) {
			$totals = array();

			$gross_total    = 0;
			$discount_total = 0;
			$tax_total      = 0;

			foreach ( $cart_contents as $key => $cart_content ) {
				$gross_total += $cart_content['item_total'];
			}

			$totals['gross_total']    = $gross_total;
			$totals['discount_total'] = $discount_total;
			$totals['sub_total']      = $totals['gross_total'] - $discount_total;
			$totals['net_total']      = $totals['sub_total'] + $tax_total;
			return $totals;
		}

		/**
		 * Remove from booking ajax action.
		 *
		 * @since 1.3.4
		 */
		public function remove() {
			if ( ! Nonce::verify() ) {
				$message = array( 'message' => $this->messages['nonce_verification_failed'] );
				wp_send_json_error( $message );
			}

			$data       = Request::get_payload();
			$cart_id    = $data['cart_id'] ?? '';
			$booking_id = $data['booking_id'] ?? 0;

			if ( empty( $cart_id ) && empty( $booking_id ) ) {
				$error_message = ErrorMessage::get( 'invalid_cart_request' );
				wp_send_json_error( $error_message );
			}

			$cart_contents = MetaHelpers::get_post_meta( $booking_id, 'cart_contents' );
			$totals        = MetaHelpers::get_post_meta( $booking_id, 'totals' );

			// Re calculate totals. [todo: discount_total, tax_total].
			$cart_item                = $cart_contents[ $cart_id ] ?? array();
			$item_total               = $cart_item ['item_total'] ?? 0;
			$totals['gross_total']   -= $item_total;
			$totals['discount_total'] = 0;
			$totals['sub_total']     -= $item_total;
			$totals['net_total']     -= $item_total;

			unset( $cart_contents[ $cart_id ] );

			$note_string = sprintf(
				/* translators: %s: Trip name */
				__( 'Trip "%s" removed from booking.', 'tripzzy' ),
				isset( $cart_item['title'] ) ? $cart_item['title'] : ''
			);

			$comment_id = Bookings::add_note( $booking_id, $note_string, 0, true );
			$note       = Bookings::get_note( $comment_id );

			$formatted_totals = array_map(
				function ( $value ) {
					return is_numeric( $value ) ? Amount::display( $value ) : $value;
				},
				$totals
			);

			MetaHelpers::update_post_meta( $booking_id, 'cart_contents', $cart_contents );
			MetaHelpers::update_post_meta( $booking_id, 'totals', $totals );

			$response_args = array(
				'cart_id'          => $cart_id,
				'message'          => __( 'Remove from cart successfully.', 'tripzzy' ),
				'note'             => Strings::trim_nl( tripzzy_get_booking_note_list_item( $note ) ),
				'totals'           => $totals,
				'formatted_totals' => $formatted_totals,
			);

			wp_send_json_success( $response_args, 200 );
		}

		/**
		 * Get Packages ajax action.
		 *
		 * @since 1.3.4
		 */
		public function get_packages() {
			if ( ! Nonce::verify() ) {
				$message = array( 'message' => $this->messages['nonce_verification_failed'] );
				wp_send_json_error( $message );
			}

			$data       = Request::get_payload();
			$trip_id    = $data['trip_id'] ?? 0;
			$start_date = $data['start_date'] ?? '';

			$trip                 = new Trip( $trip_id );
			$packages             = $trip->packages(); // all Packages.
			$has_seasonal_pricing = $trip->has_seasonal_pricing();
			$packages             = $trip->packages();
			if ( $has_seasonal_pricing ) {
				$packages = $trip->packages( null, compact( 'start_date' ) );
			}

			$default_package_id = $packages->default_package_id;
			$default_package    = $packages->get_package();
			$category           = $default_package ? $default_package->get_category() : null;
			$price              = $category ? $category->get_price() : 0;
			$price_per          = $trip->price_per;
			$price_per_label    = $trip->price_per_label;

			// Advanced min people markup.
			$enable_advanced_min_people = (bool) $trip->get_meta( 'enable_advanced_min_people' ) && Modules::is_active( 'tripzzy_utilities_module' );
			$is_active_group_discount   = (bool) Modules::is_active( 'tripzzy_group_discount_module' );

			$mapped_packages = array();
			foreach ( $packages as $package ) {
				$categories = $package->get_categories();

				$mapped_categories = array();
				foreach ( $categories as $package_category ) {
					$package_category_id = $package_category->get_id();
					if ( ! get_term( $package_category_id ) ) {
						continue;
					}

					// Assign group discount data.
					if ( $has_seasonal_pricing ) {
						if ( 'person' === $trip->price_per ) {
							$group_discount_data = $package_category->get_group_discount( compact( 'start_date', 'trip', 'package' ) );
						} else {
							$group_discount_data = $package->get_group_discount( compact( 'start_date', 'trip' ) );
						}
					} elseif ( 'person' === $trip->price_per ) {
						$group_discount_data = $package_category->get_group_discount();
					} else {
						$group_discount_data = $package->get_group_discount();
					}

					// Min people markup.
					ob_start();
					if ( $enable_advanced_min_people ) {
						\Tripzzy\Modules\Utilities\MinMaxPeople::display_min_people( $package_category, $trip );
					}
					$min_people_markup = ob_get_contents();
					ob_end_clean();

					// Group Discount Markup.
					ob_start();
					if ( $is_active_group_discount ) {
						\Tripzzy\Modules\GroupDiscount\GroupDiscount::display_group_discount_in_package_category( $package_category, $trip, $package, $start_date );
					}
					$group_discount_markup = ob_get_contents();
					ob_end_clean();

					$price               = $package_category->get_price();
					$id                  = $package_category->get_id();
					$title               = $package_category->get_title();
					$mapped_categories[] = array(
						'id'                    => (int) $id,
						'title'                 => $title,
						'price'                 => $price,
						'group_discount'        => $group_discount_data,
						'min_people_markup'     => $min_people_markup,
						'group_discount_markup' => $group_discount_markup,
					);

				}
				$mapped_packages[] = array(
					'id'         => (int) $package->get_id(),
					'title'      => $package->get_title(),
					'price'      => $price, // For Price per group.
					'categories' => $mapped_categories,
				);

			}

			// Min people markup.
			ob_start();
			TemplateHooks::add_min_people( $trip );
			$min_people_markup = ob_get_contents();
			ob_end_clean();

			$dates         = $trip->dates( null, 60 ); // 60 dates in calendar.
			$trip_dates    = $dates->get_dates();
			$exclude_dates = $dates->get_exclude_dates();

			$packages_data = array(
				'packages'                   => $mapped_packages,
				'default_package_id'         => $default_package_id,
				'price_per'                  => $price_per,
				'price_per_label'            => $price_per_label,
				'has_seasonal_pricing'       => $has_seasonal_pricing,
				'min_people_markup'          => $min_people_markup,
				'enable_advanced_min_people' => $enable_advanced_min_people,
				'trip_dates'                 => $trip_dates,
				'exclude_dates'              => $exclude_dates,
			);

			$response_args = array(
				'packages_data' => $packages_data,
				'message'       => __( 'Package Loaded.', 'tripzzy' ),
			);

			wp_send_json_success( $response_args, 200 );
		}

		/**
		 * Save Trips in booking ajax action.
		 *
		 * @since 1.3.4
		 */
		public function add_booking_item() {

			if ( ! Nonce::verify() ) {
				$message = array( 'message' => $this->messages['nonce_verification_failed'] );
				wp_send_json_error( $message );
			}
			$payload    = Request::sanitize_input( 'INPUT_PAYLOAD' );
			$booking_id = $payload['booking_id'] ?? 0;
			$bookings   = $payload['bookings'] ?? array();

			$trip_id    = $bookings['trip_id'] ?? 0;
			$start_date = $bookings['start_date'] ?? '';
			$categories = $bookings['categories'] ?? array();
			$package_id = $bookings['package_id'] ?? 0;
			$cart_data  = array(
				'trip_id'    => $trip_id,
				'start_date' => $start_date,
				'categories' => $categories,
				'package_id' => $package_id,
			);

			$settings      = Settings::get();
			$currency_code = $settings['currency'];

			/**
			 * Filter to modify cart data.
			 *
			 * @internal Same Hook present in CartAjax.php file also.
			 */
			$cart_data = apply_filters( 'tripzzy_filter_add_to_cart_data', $cart_data, $payload );

			$validation = $this->validate_cart( $cart_data );

			if ( ! $validation['success'] ) {
				$error_message = $validation['message'];
				wp_send_json_error( $error_message );
			}

			// Add to cart.
			$trip_id   = absint( $trip_id );
			$quantity  = array_sum( $categories );
			$cart_data = (array) apply_filters( 'tripzzy_filter_cart_data', $cart_data, $trip_id, $quantity );

			$cart_id = Cart::generate_cart_id(
				$trip_id,
				$cart_data['start_date'],
				$cart_data
			);

			if ( ! $cart_id ) {
					wp_send_json_error( new \WP_Error( 'add_to_cart_failed', __( 'Couldn\'t add trip to the cart.', 'tripzzy' ) ) );
			}

			$cart_contents = MetaHelpers::get_post_meta( $booking_id, 'cart_contents' );
			if ( ! $cart_contents ) {
				$cart_contents = array();
			}

			$args = array(
				'trip_id'       => $trip_id,
				'cart_data'     => $cart_data,
				'quantity'      => $quantity,
				'currency_code' => $currency_code,
				'cart_id'       => $cart_id,
			);

			$item                      = Cart::generate_cart_item( $args );
			$cart_contents[ $cart_id ] = $item;
			$totals                    = $this->calculate_totals( $cart_contents );
			MetaHelpers::update_post_meta( $booking_id, 'cart_contents', $cart_contents );
			MetaHelpers::update_post_meta( $booking_id, 'totals', $totals );

			// Add note.
			$note_string = sprintf(
				/* translators: %s: Trip name */
				__( 'Trip "%s" added to booking.', 'tripzzy' ),
				isset( $item['title'] ) ? $item['title'] : ''
			);
			$comment_id = Bookings::add_note( $booking_id, $note_string, 0, true );
			$note       = Bookings::get_note( $comment_id );

			$response_args = array(
				'cart_id'      => $cart_id,
				'booking_item' => $this->get_booking_item_markup( $booking_id, $item ),
				'totals'       => $totals,
				'note'         => Strings::trim_nl( tripzzy_get_booking_note_list_item( $note ) ),
				'message'      => __( 'Booking Added.', 'tripzzy' ),
			);

			wp_send_json_success( $response_args, 200 );
		}

		/**
		 * Markup to display newly added trip in the booking tirp list.
		 *
		 * @param int   $booking_id Booking Id.
		 * @param array $item Booking Item data.
		 *
		 * @since 1.3.4
		 * @return mixed
		 */
		public function get_booking_item_markup( $booking_id, $item ) {
			$cart_key   = $item['key'] ?? '';
			$trip_id    = $item['trip_id'] ?? 0;
			$package_id = $item['package_id'] ?? 0;
			$trip       = new Trip( $trip_id );
			$packages   = $trip->packages();
			$package    = $packages->get_package( $package_id );

			$package_title = '';
			if ( $package ) {
				$package_title = $package->get_title();
			}
			$time = $item['time'] ?? '';

			// Extras.
			$has_extras_selected = false;
			if ( isset( $item['extras'] ) && ! $has_extras_selected ) {
				$selected_extras_count = array_sum( array_values( $item['extras'] ) );
				$has_extras_selected   = $selected_extras_count > 0;
			}

			ob_start();
			?>
			<tr valign="top" data-id="tz<?php echo esc_attr( $cart_key ); ?>" class="tripzzy-booking-trip-item tz-highlight" >
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
				<td class="trip-info-total">
					<p><strong><?php echo esc_html( Amount::display( $item['item_total'] ) ); ?></strong></p>
					<?php if ( Request::is( 'admin' ) ) : ?>
					<p class="tripzzy-booking-edit-line-item-actions">
						<a href="#" class="remove-booking" data-booking-id="<?php echo esc_attr( $booking_id ); ?>" data-cart-id="<?php echo esc_attr( $cart_key ); ?>">
							<i class="dashicons dashicons-no-alt"></i>
						</a>
					</p>
					<?php endif; ?>
				</td>
			</tr>
			<?php
			$cart_item = ob_get_contents();
			ob_end_clean();
			return $cart_item;
		}

		/**
		 * Responsible for cart item validation.
		 *
		 * @param array $cart_data Cart Data.
		 * @since 1.3.4
		 * @return array
		 */
		protected function validate_cart( $cart_data ) {
			$response = array(
				'success' => false,
				'message' => ErrorMessage::get(),
			);

			$has_negative_qty = false;
			$total_qty        = 0;
			if ( is_array( $cart_data['categories'] ) ) {
				foreach ( $cart_data['categories'] as $qty ) {
					if ( $qty < 0 ) {
						$has_negative_qty = true;
						break;
					}
					$total_qty += $qty;
				}
			}

			if ( ! empty( $cart_data ) ) {
				$trip       = new Trip( $cart_data['trip_id'] );
				$min_people = $trip->get_meta( 'min_people' );
				/**
				 * Filter to check whether advanced min people enabled or not.
				 */
				$has_advanced_min_people = apply_filters( 'tripzzy_filter_has_advanced_min_people', false, $trip, $cart_data );

				if ( $has_negative_qty ) {
					$response['success'] = false;
					$response['message'] = ErrorMessage::get( 'negative_cart_value' );
					return $response;
				}
				$cart_categories = $cart_data['categories'] ?? array();
				if ( ! $this->validate_cart_request( $cart_data ) && ! $has_advanced_min_people ) {
					$response['success'] = false;
					if ( 1 === count( $cart_categories ) ) {
						$term_id  = key( $cart_categories );
						$category = get_term( $term_id );
						$cat_name = __( 'People', 'tripzzy' );
						if ( ! is_wp_error( $category ) ) {
							$cat_name = $category->name;
						}
						$response['message'] = ErrorMessage::get( 'min_cart_value_required', array( $min_people, $cat_name ) );
					} else {
						$response['message'] = ErrorMessage::get( 'invalid_cart_request' );
					}
					return $response;
				}
				if ( ! ( $total_qty >= (int) $min_people ) && ! $has_advanced_min_people ) {
					$response['success'] = false;
					if ( 1 === count( $cart_categories ) ) {
						$term_id  = key( $cart_categories );
						$category = get_term( $term_id );
						$cat_name = __( 'People', 'tripzzy' );
						if ( ! is_wp_error( $category ) ) {
							$cat_name = $category->name;
						}
						$response['message'] = ErrorMessage::get( 'min_cart_value_required', array( $min_people, $cat_name ) );
					} else {
						$response['message'] = ErrorMessage::get( 'min_cart_value_required', array( $min_people ), 'plural' );
					}
					return $response;
				}

				// Return true if all validation complete.
				$response['success'] = true;
				$response['message'] = '';
				/**
				 * Modify cart validation response.
				 *
				 * @since 1.2.1
				 */
				$response = apply_filters( 'tripzzy_filter_validate_cart_response', $response, $cart_data, $trip );

				return $response;

			}

			return $response;
		}

		/**
		 * Validate Add to request cart.
		 *
		 * @param array $cart_data Cart Data.
		 * @since 1.3.4
		 * @return boolean
		 */
		protected function validate_cart_request( $cart_data ) {
			return (bool) $cart_data && isset( $cart_data['trip_id'] ) && isset( $cart_data['categories'] ) && is_array( $cart_data['categories'] ) && array_sum( $cart_data['categories'] ) > 0;
		}
	}

	AdminBookingsAjax::instance();
}
