<?php
/**
 * Tripzzy Cart.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\CartSession;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\Coupon;
use Tripzzy\Core\Helpers\MetaHelpers;

if ( ! class_exists( 'Tripzzy\Core\Cart' ) ) {
	/**
	 * Cart main class.
	 */
	class Cart {

		/**
		 * Contains Tripzzy cart items array.
		 *
		 * @var array
		 */
		public $cart_contents = array();

		/**
		 * Contains Removed Tripzzy cart items array.
		 *
		 * @var array
		 */
		public $removed_cart_contents = array();

		/**
		 * Contains an array of coupon codes applied to the cart.
		 *
		 * @var array
		 */
		public $applied_coupons = array();

		/**
		 * Total defaults used to reset.
		 *
		 * @var array
		 */
		protected $default_totals = array(
			'gross_total'    => 0, // i.e 1000  Item Total.
			'discount_total' => 0, // i.e -100  Total applicable discount amount (assuming 10%).
			'sub_total'      => 0, // i.e  900  ( Item Total - Total applicable discount amount).
			'tax_total'      => 0, // i.e +119  // Assuming 13% tax.
			'net_total'      => 0, // i.e 1019.
		);

		/**
		 * Store calculated totals.
		 *
		 * @var array
		 */
		protected $totals = array();

		/**
		 * Coupon discount total.
		 *
		 * @var integer
		 */
		protected $coupon_discount_total = 0;

		/**
		 * Cart session instance.
		 *
		 * @since 1.0.7
		 * @var CartSession
		 */
		private CartSession $session;

		/**
		 * Constructor.
		 */
		public function __construct() {

			$this->session = new CartSession( $this );

			// Register hooks for the objects.
			$this->session->init();

			// Add to cart.
			add_action( 'tripzzy_add_to_cart', array( $this, 'calculate_totals' ), 20, 0 );
			add_action( 'tripzzy_applied_coupons', array( $this, 'calculate_totals' ), 20, 0 );
			add_action( 'tripzzy_cleared_coupons', array( $this, 'calculate_totals' ), 20, 0 );
			add_action( 'tripzzy_cart_item_removed', array( $this, 'calculate_totals' ), 20, 0 );

			// update coupon uses.
			add_action( 'tripzzy_after_booking', array( $this, 'update_coupon_uses' ), 20, 0 );
			// Clear cart.
			add_action( 'tripzzy_after_booking', array( $this, 'empty_cart' ), 20, 0 );
		}

		/**
		 * Return Checkout method. Whether default checkout is use or not.
		 *
		 * @return boolean
		 */
		protected function cart_type() {
			return apply_filters( 'tripzzy_filter_' . __FUNCTION__, 'tripzzy' );
		}

		/**
		 * Check which cart to use.
		 * default is own cart. i.e. Tripzzy cart.
		 *
		 * @return bool
		 */
		protected function use_default_cart() {
			return 'tripzzy' === $this->cart_type();
		}

		/*
		|---------------------------------------------------------------------------
		| Getters.
		|---------------------------------------------------------------------------
		*/

		/**
		 * Gets cart contents.
		 *
		 * @since 1.0.0
		 * @return array List of cart items
		 */
		public function get_cart_contents() {
			return apply_filters( 'tripzzy_filter_' . __FUNCTION__, (array) $this->cart_contents );
		}

		/**
		 * Return items removed from the cart.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public function get_removed_cart_contents() {
			return (array) $this->removed_cart_contents;
		}

		/**
		 * Gets the array of applied coupon codes.
		 *
		 * @return array of applied coupons
		 */
		public function get_applied_coupons() {
			return (array) $this->applied_coupons;
		}

		/**
		 * Return calculated totals either all or selected keys if keys not empty.
		 *
		 * @param string $key Total key.
		 * @since 1.0.0
		 * @return mixed
		 */
		public function get_totals( $key = '' ) {
			$totals = empty( $this->totals ) ? $this->default_totals : $this->totals;
			if ( $key ) {
				return isset( $this->totals[ $key ] ) ? $this->totals[ $key ] : $this->default_totals[ $key ];
			}
			return $totals;
		}

		/**
		 * Get Gross Total.
		 *
		 * @since 1.0.0
		 * @return float
		 */
		public function get_gross_total() {
			return apply_filters( 'tripzzy_filter_' . __FUNCTION__, $this->get_totals( 'gross_total' ) );
		}

		/**
		 * Get Discount Total.
		 *
		 * @since 1.0.0
		 * @return float
		 */
		public function get_discount_total() {
			return apply_filters( 'tripzzy_filter_' . __FUNCTION__, $this->get_totals( 'discount_total' ) );
		}

		/**
		 * Get Sub Total.
		 *
		 * @since 1.0.0
		 * @return float
		 */
		public function get_sub_total() {
			return apply_filters( 'tripzzy_filter_' . __FUNCTION__, $this->get_totals( 'sub_total' ) );
		}

		/**
		 * Get Tax Total.
		 *
		 * @since 1.0.0
		 * @return float
		 */
		public function get_tax_total() {
			return apply_filters( 'tripzzy_filter_' . __FUNCTION__, $this->get_totals( 'tax_total' ) );
		}

		/**
		 * Get Net Total.
		 *
		 * @since 1.0.0
		 * @return float
		 */
		public function get_net_total() {
			return apply_filters( 'tripzzy_filter_' . __FUNCTION__, $this->get_totals( 'net_total' ) );
		}

		/**
		 * Get total for each item.
		 *
		 * @param array $cart_data Cart item data.
		 *
		 * @since 1.2.9 Added tripzzy_filter_item_total_data filter hook.
		 * @since 1.1.7 Check for seasonal pricing.
		 * @since 1.0.5
		 * @return array
		 */
		public static function get_item_total_data( $cart_data ) {
			$trip_id    = $cart_data['trip_id'];
			$package_id = $cart_data['package_id'];
			$categories = $cart_data['categories'];
			$start_date = $cart_data['start_date'];

			$trip             = new Trip( $trip_id );
			$packages         = $trip->packages(); // all Packages.
			$package          = $packages->get_package( $package_id );
			$categories_price = array();
			$item_total       = 0;

			$has_seasonal_pricing = $trip->has_seasonal_pricing();
			if ( $has_seasonal_pricing ) {
				$packages = $trip->packages( null, compact( 'start_date' ) );
				$package  = $packages->get_package( $package_id );
			}
			foreach ( $categories as $category_id => $qty ) {
				$package_category                 = $package ? $package->get_category( $category_id ) : null;
				$price                            = $package_category ? $package_category->get_price() : 0;
				$categories_price[ $category_id ] = $price;
				// Item Total Calculation.
				if ( 'person' === $packages->price_per ) {
					$item_total += $price * $qty;
				}
			}
			// Override price if its group price.
			if ( 'group' === $packages->price_per ) {
				$package_category = $package->get_category();
				$item_total      += $package_category->get_price();
			}
			$item_total_data = array(
				'categories_price' => $categories_price,
				'item_total'       => $item_total,
			);
			return apply_filters( 'tripzzy_filter_item_total_data', $item_total_data, $cart_data );
		}

		/**
		 * Returns the contents of the cart in an array.
		 *
		 * @return array contents of the cart
		 */
		public function get_cart() {

			if ( ! did_action( 'tripzzy_load_cart_from_session' ) ) { // $this->session prevent error from ajax request.
				$this->session->get_cart_from_session();
			}
			return array_filter( $this->get_cart_contents() );
		}

		/**
		 * Gets cart total after calculation.
		 *
		 * @since 1.0.0
		 * @return float|string
		 */
		public function get_total() {
			return apply_filters( 'tripzzy_filter_cart_' . __FUNCTION__, $this->get_totals( 'net_total' ) );
		}

		/*
		|---------------------------------------------------------------------------
		| Setters.
		|---------------------------------------------------------------------------
		*/

		/**
		 * Sets the contents of the cart.
		 *
		 * @param array $value Cart array.
		 */
		public function set_cart_contents( $value ) {
			$this->cart_contents = (array) $value;
		}

		/**
		 * Set items removed from the cart.
		 *
		 * @since 1.0.0
		 * @param array $value Item array.
		 */
		public function set_removed_cart_contents( $value = array() ) {
			$this->removed_cart_contents = (array) $value;
		}

		/**
		 * Sets the array of applied coupon codes.
		 *
		 * @param array $value List of applied coupon codes.
		 */
		public function set_applied_coupons( $value = array() ) {
			$this->applied_coupons = (array) $value;
		}


		/**
		 * Sets the array of calculated coupon tax totals.
		 *
		 * @since 1.0.0
		 * @param array $value Value to set.
		 */
		public function set_coupon_discount_tax_totals( $value = array() ) {
			$this->coupon_discount_tax_totals = (array) $value;
		}

		/**
		 * Set all calculated totals.
		 *
		 * @since 1.0.0
		 * @param array $value Value to set.
		 */
		public function set_totals( $value = array() ) {
			$this->totals = wp_parse_args( $value, $this->default_totals );
		}

		/**
		 * Set subtotal.
		 *
		 * @since 1.0.0
		 * @param string $value Value to set.
		 */
		public function set_subtotal( $value ) {
			$this->totals['subtotal'] = $value;
		}

		/**
		 * Set subtotal.
		 *
		 * @since 1.0.0
		 * @param string $value Value to set.
		 */
		public function set_subtotal_tax( $value ) {
			$this->totals['subtotal_tax'] = $value;
		}

		/**
		 * Set discount_total.
		 *
		 * @since 1.0.0
		 * @param string $value Value to set.
		 */
		public function set_discount_total( $value ) {
			$this->totals['discount_total'] = $value;
		}

		/**
		 * Set cart total.
		 *
		 * @since 1.0.0
		 * @param string $value Value to set.
		 */
		public function set_total( $value ) {
			$this->totals['total'] = $value;
		}


		/*
		|--------------------------------------------------------------------------
		| Helper methods.
		|--------------------------------------------------------------------------
		*/

		/**
		 * Returns a specific item in the cart.
		 *
		 * @param string $item_key Cart item key.
		 * @return array Item data
		 */
		public function get_cart_item( $item_key ) {
			return isset( $this->cart_contents[ $item_key ] ) ? $this->cart_contents[ $item_key ] : array();
		}

		/**
		 * Checks if the cart is empty.
		 *
		 * @return bool
		 */
		public function is_empty() {
			return 0 === count( $this->get_cart() );
		}

		/**
		 * Get cart's owner.
		 *
		 * @since  1.0.0
		 * @return Tripzzy\Core\Helpers\Customer
		 */
		public function get_customer() {
			return tripzzy()->customer;
		}

		/**
		 * Generate a unique ID for the cart item being added.
		 *
		 * @param int   $trip_id - Trip id to generate cart id.
		 * @param int   $start_date Start date to generate cart id.
		 * @param array $cart_item_data Cart item data.
		 *
		 * @since 1.0.0
		 * @since 1.0.7 Changed Method to static.
		 * @return string cart item key
		 */
		public static function generate_cart_id( $trip_id, $start_date = '', $cart_item_data = array() ) {
			$cart_id_parts = array( $trip_id );

			if ( isset( $cart_item_data['package_id'] ) ) {
				$cart_id_parts[] = $cart_item_data['package_id'];
			}

			if ( $start_date ) {
				$cart_id_parts[] = $start_date;
			}

			return apply_filters( 'tripzzy_filter_cart_id', md5( implode( '_', $cart_id_parts ) ), $trip_id, $start_date, $cart_item_data );
		}

		/**
		 * Add to Cart.
		 *
		 * @param array $cart_data Cart Data.
		 * @param int   $trip_id Trip ID.
		 * @param int   $quantity No of persons.
		 */
		public function add( $cart_data, $trip_id = 0, $quantity = 1 ) {
			$settings      = Settings::get();
			$currency_code = $settings['currency'];
			try {
				$trip_id   = absint( $trip_id );
				$cart_data = (array) apply_filters( 'tripzzy_filter_cart_data', $cart_data, $trip_id, $quantity );
			} catch ( \Exception $e ) {
				return false;
			}
			if ( $this->use_default_cart() ) {
				// Start of add to cart.
				$categories = $cart_data['categories'];
				$start_date = $cart_data['start_date'];

				$cart_id = self::generate_cart_id(
					$trip_id,
					$start_date,
					$cart_data
				);

				if ( ! $cart_id ) {
					wp_send_json_error( new \WP_Error( 'add_to_cart_failed', __( 'Couldn\'t add trip to the cart.', 'tripzzy' ) ) );
				}

				$args                            = array(
					'trip_id'       => $trip_id,
					'cart_data'     => $cart_data,
					'quantity'      => $quantity,
					'currency_code' => $currency_code,
					'cart_id'       => $cart_id,
				);
				$this->cart_contents[ $cart_id ] = self::generate_cart_item( $args );

				/**
				 * Filters the entire cart contents when the cart changes.
				 *
				 * @param array $cart_contents Array of all cart items.
				 * @return array Updated array of all cart items.
				 */
				$this->cart_contents = apply_filters( 'tripzzy_filter_cart_contents_changed', $this->cart_contents );

				// Clear Coupon on add to cart.
				$this->set_applied_coupons( array() );

				/**
				 * Fires when an item is added to the cart.
				 *
				 * @param string $cart_id ID of the item in the cart.
				 * @param integer $trip_id ID of the trip added to the cart.
				 * @param integer $quantity Quantity of the item added to the cart.
				 * @param array $cart_data Array of other cart item data.
				 */
				do_action(
					'tripzzy_add_to_cart',
					$cart_id,
					$trip_id, // Trip Id.
					$quantity, // Qty.
					$cart_data // cart item data.
				);

				return $cart_id;

			} else {
				// @Since 1.0.5
				$data    = array(
					'cart_data' => $cart_data,
					'trip_id'   => $trip_id,
					'quantity'  => $quantity,
				);
				$cart_id = apply_filters( 'tripzzy_add_to_cart_' . $this->cart_type(), $data );
				return $cart_id;
			}
		}

		/**
		 * Generate cart item data as per param provided.
		 *
		 * @param array $args Cart item args.
		 * @since 1.0.7
		 * @since 1.1.3 Added Time.
		 * @return array
		 */
		public static function generate_cart_item( $args = array() ) {
			$cart_data     = $args['cart_data'] ?? array();
			$trip_id       = $args['trip_id'] ?? 0;
			$quantity      = $args['quantity'] ?? 1;
			$cart_id       = $args['cart_id'] ?? '';
			$currency_code = $args['currency_code'] ?? 'usd';
			// Extract cart data.
			$package_id = $cart_data['package_id'];
			$start_date = $cart_data['start_date'] ?? '';
			$categories = $cart_data['categories'] ?? array();

			if ( ! $trip_id ) { // Cart id also exist in $cart_data. So extract from here.
				$trip_id = $cart_data['trip_id'] ?? 0;
			}

			if ( ! $cart_id ) { // Not required if cart id passed from tripzzy as well.
				$cart_id = self::generate_cart_id(
					$trip_id,
					$start_date,
					$cart_data
				);
			}

			$trip     = new Trip( $trip_id );
			$packages = $trip->packages(); // all Packages.
			$package  = $packages->get_package( $package_id );

			$item_total_data  = self::get_item_total_data( $cart_data );
			$categories_price = $item_total_data['categories_price'];
			$item_total       = $item_total_data['item_total'];

			$cart_item = array(
				'key'              => $cart_id,
				'trip_id'          => $trip_id,
				'package_id'       => $package_id,
				'title'            => get_the_title( $trip_id ),
				'quantity'         => $quantity,
				'categories'       => $categories,
				'categories_price' => $categories_price,
				'price_per'        => $packages->price_per,
				'start_date'       => $start_date,
				'currency_code'    => $currency_code,
				'item_total'       => $item_total,
			);
			/**
			 * Cart Contents Filter.
			 *
			 * @since 1.0.0
			 * @since 1.1.3 Added $args param.
			 */
			return apply_filters( 'tripzzy_filter_add_cart_item', $cart_item, $cart_id, $args );
		}

		/**
		 * Remvoe from cart.
		 *
		 * @param array $cart_item_key Cart item id.
		 */
		public function remove( $cart_item_key ) {
			if ( $this->use_default_cart() ) {

				if ( isset( $this->cart_contents[ $cart_item_key ] ) ) {
					$this->removed_cart_contents[ $cart_item_key ] = $this->cart_contents[ $cart_item_key ];

					unset( $this->removed_cart_contents[ $cart_item_key ]['data'] );

					do_action( 'tripzzy_remove_cart_item', $cart_item_key, $this );

					unset( $this->cart_contents[ $cart_item_key ] );

					do_action( 'tripzzy_cart_item_removed', $cart_item_key, $this );

					return true;
				}
				return false;
			}
		}

		/**
		 * Calculate totals for the items in the cart.
		 */
		public function calculate_totals() {
			$this->reset_totals();

			$applied_coupons = $this->get_applied_coupons();

			if ( $this->is_empty() ) {
				$this->session->set_session();
				return;
			}

			do_action( 'tripzzy_before_calculate_totals', $this );
			$totals = $this->get_totals();

			$gross_total    = 0;
			$discount_total = $applied_coupons && isset( $applied_coupons['coupon_discount_total'] ) ? $applied_coupons['coupon_discount_total'] : 0;
			$tax_total      = 0;

			foreach ( $this->get_cart_contents() as $key => $cart_content ) {
				$trip_id          = $cart_content['trip_id'];
				$categories       = $cart_content['categories'];
				$categories_price = array();
				$trip             = new Trip( $trip_id );
				$gross_total     += $cart_content['item_total'];
			}

			$totals['gross_total']    = $gross_total;
			$totals['discount_total'] = $discount_total;
			$totals['sub_total']      = $totals['gross_total'] - $discount_total;
			$totals['net_total']      = $totals['sub_total'] + $tax_total;
			$this->set_totals( $totals );
			do_action( 'tripzzy_after_calculate_totals', $this );
		}


		/**
		 * Reset cart totals to the defaults. Useful before running calculations.
		 */
		private function reset_totals() {
			$this->totals = $this->default_totals;
			do_action( 'tripzzy_cart_reset', $this, false );
		}

		/**
		 * Returns the hash code based on cart contents.
		 *
		 * @since 1.0.0
		 * @return string hash for cart content
		 */
		public function get_cart_hash() {
			$cart_session = $this->session->get_cart_for_session();
			$hash         = $cart_session ? md5( wp_json_encode( $cart_session ) . $this->get_total() ) : '';
			return apply_filters( 'tripzzy_filter_cart_hash', $hash, $cart_session );
		}

		/**
		 * Empties the cart.
		 *
		 * @param bool $clear_persistent_cart Should the persistent cart be cleared too.
		 */
		public function empty_cart( $clear_persistent_cart = true ) {

			do_action( 'tripzzy_before_cart_emptied', $clear_persistent_cart );

			$this->cart_contents         = array();
			$this->removed_cart_contents = array();
			$this->coupon_discount_total = array();
			$this->applied_coupons       = array();
			$this->totals                = $this->default_totals;

			if ( $clear_persistent_cart ) {
				$this->session->persistent_cart_destroy();
			}

			do_action( 'tripzzy_cart_emptied', $clear_persistent_cart );
		}

		/**
		 * Update Coupon uses if applied.
		 *
		 * @return void
		 */
		public function update_coupon_uses() {
			$cart           = tripzzy()->cart;
			$applied_coupon = $cart->get_applied_coupons();
			if ( ! empty( $applied_coupon ) ) {
				$coupon_id = $applied_coupon['coupon_id'];
				$uses      = Coupon::get_coupon_uses( $coupon_id );
				++$uses;
				MetaHelpers::update_post_meta( $coupon_id, 'coupon_uses', $uses );
			}
		}
	}
}
