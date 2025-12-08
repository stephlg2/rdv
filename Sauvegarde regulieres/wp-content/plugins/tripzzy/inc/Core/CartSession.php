<?php
/**
 * Cart session handling class.
 *
 * @package tripzzy
 * @version 1.0.0
 */

namespace Tripzzy\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Helpers\Cookie;
use Tripzzy\Core\Helpers\Trip;

/**
 * CartSession class.
 *
 * @since 1.0.0
 */
final class CartSession {

	/**
	 * Reference to cart object.
	 *
	 * @since 1.0.0
	 * @var Tripzzy\Core\Cart
	 */
	protected $cart;

	/**
	 * Sets up the items provided, and calculate totals.
	 *
	 * @since 1.0.0
	 * @throws \Exception If missing Tripzzy\Core\Cart object.
	 *
	 * @param Tripzzy\Core\Cart $cart Cart object to calculate totals for.
	 */
	public function __construct( &$cart ) {
		if ( ! is_a( $cart, 'Tripzzy\Core\Cart' ) ) {
			throw new \Exception( 'A valid Tripzzy\Core\Cart object is required' );
		}
		$this->cart = $cart;
	}

	/**
	 * Init cart session hooks.
	 */
	public function init() {
		/**
		 * Filters whether hooks should be initialized for the current cart session.
		 *
		 * @param bool   $must_initialize Will be passed as true, meaning that the cart hooks should be initialized.
		 * @param object CartSession The CartSession object that is being initialized.
		 * @return bool True if the cart hooks should be actually initialized, false if not.
		 *
		 * @since 1.0.0
		 */
		if ( ! apply_filters( 'tripzzy_filter_cart_session_initialize', true, $this ) ) {
			return;
		}

		add_action( 'wp_loaded', array( $this, 'get_cart_from_session' ) );
		add_action( 'tripzzy_cart_emptied', array( $this, 'destroy_cart_session' ) );
		add_action( 'tripzzy_after_calculate_totals', array( $this, 'set_session' ), 1000 );
		add_action( 'tripzzy_cart_loaded_from_session', array( $this, 'set_session' ) );

		// Persistent cart stored to usermeta.
		add_action( 'tripzzy_add_to_cart', array( $this, 'persistent_cart_update' ) ); // start.
		add_action( 'tripzzy_cart_item_removed', array( $this, 'persistent_cart_update' ) );

		// Cookie events - cart cookies need to be set before headers are sent.
		add_action( 'tripzzy_add_to_cart', array( $this, 'maybe_set_cart_cookies' ) );
		add_action( 'wp', array( $this, 'maybe_set_cart_cookies' ), 99 );
		add_action( 'shutdown', array( $this, 'maybe_set_cart_cookies' ), 0 );
	}

	/**
	 * Get the cart data from the PHP session and store it in class variables.
	 *
	 * @since 1.0.0
	 */
	public function get_cart_from_session() {
		if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) { // @todo Need to add is_admin condition to prevent wrong post id in admin trips.
			return;
		}
		do_action( 'tripzzy_load_cart_from_session' );
		$this->cart->set_totals( tripzzy()->session->get( 'cart_totals', null ) );
		$this->cart->set_applied_coupons( tripzzy()->session->get( 'applied_coupons', null ) );

		$update_cart_session = false; // Flag to indicate the stored cart should be updated.
		$cart                = tripzzy()->session->get( 'cart', null );

		$merge_saved_cart = (bool) get_user_meta( get_current_user_id(), '_tripzzy_load_saved_cart_after_login', true );

		// Merge saved cart with current cart.
		if ( is_null( $cart ) || $merge_saved_cart ) {
			$saved_cart          = $this->get_saved_cart();
			$cart                = is_null( $cart ) ? array() : $cart;
			$cart                = array_merge( $saved_cart, $cart );
			$update_cart_session = true;

			delete_user_meta( get_current_user_id(), '_tripzzy_load_saved_cart_after_login' );
		}

		// Prime caches to reduce future queries.
		if ( is_callable( '_prime_post_caches' ) ) {
			_prime_post_caches( wp_list_pluck( $cart, 'trip_id' ) );
		}

		$cart_contents = array();

		foreach ( $cart as $key => $values ) {
			if ( ! is_customize_preview() && 'customize-preview' === $key ) {
				continue;
			}
			$trip_id    = $values['trip_id'];
			$categories = $values['categories'];
			$start_date = $values['start_date'];

			$trip = new Trip( $trip_id ); // @todo this is creating issue in admin. This will initialize trip object with cart item. so trip id of admin trip shows this cart item trip id.

			// Add Trip data in cart content array.
			$session_data = array_merge(
				$values,
				array(
					'data' => $trip::get( $trip_id ),
				)
			);

			$cart_contents[ $key ] = apply_filters( 'tripzzy_filter_get_cart_item_from_session', $session_data, $values, $key );

			// Add to cart right away so the trip is visible in tripzzy_filter_get_cart_item_from_session hook.
			$this->cart->set_cart_contents( $cart_contents );
		}

		// in case of update cart.
		if ( ! empty( $cart_contents ) ) {
			$this->cart->set_cart_contents( apply_filters( 'tripzzy_filter_cart_contents_changed', $cart_contents ) );
		}

		if ( $update_cart_session || is_null( tripzzy()->session->get( 'cart_totals', null ) ) ) {
			tripzzy()->session->set( 'cart', $this->get_cart_for_session() );
			$this->cart->calculate_totals();

			if ( $merge_saved_cart ) {
				$this->persistent_cart_update();
			}
		}
	}

	/**
	 * Destroy cart session data.
	 *
	 * @since 1.0.0
	 */
	public function destroy_cart_session() {
		tripzzy()->session->set( 'cart', null );
		tripzzy()->session->set( 'cart_totals', null );
		tripzzy()->session->set( 'applied_coupons', null );
		tripzzy()->session->set( 'coupon_discount_totals', null );
		tripzzy()->session->set( 'coupon_discount_tax_totals', null );
		tripzzy()->session->set( 'removed_cart_contents', null );
	}

	/**
	 * Will set cart cookies if needed and when possible.
	 *
	 * @since 1.0.0
	 */
	public function maybe_set_cart_cookies() {
		if ( ! headers_sent() && did_action( 'wp_loaded' ) ) {
			if ( ! $this->cart->is_empty() ) {
				$this->set_cart_cookies( true );
			} elseif ( isset( $_COOKIE['tripzzy_items_in_cart'] ) ) { // WPCS: input var ok.
				$this->set_cart_cookies( false );
			}
		}
	}

	/**
	 * Sets the php session data for the cart and coupons.
	 */
	public function set_session() {
		tripzzy()->session->set( 'cart', $this->get_cart_for_session() );
		tripzzy()->session->set( 'applied_coupons', $this->cart->get_applied_coupons() );
		tripzzy()->session->set( 'discount_total', $this->cart->get_discount_total() );
		tripzzy()->session->set( 'cart_totals', $this->cart->get_totals() );

		tripzzy()->session->set( 'removed_cart_contents', $this->cart->get_removed_cart_contents() );
		do_action( 'tripzzy_cart_updated' );
	}

	/**
	 * Returns the contents of the cart in an array without the 'data' element.
	 *
	 * @return array contents of the cart
	 */
	public function get_cart_for_session() {
		$cart_session = array();

		foreach ( $this->cart->get_cart() as $key => $values ) {
			$cart_session[ $key ] = $values;
			unset( $cart_session[ $key ]['data'] ); // Unset product object.
		}

		return $cart_session;
	}

	/**
	 * Save the persistent cart when the cart is updated.
	 */
	public function persistent_cart_update() {
		if ( get_current_user_id() && apply_filters( 'tripzzy_filter_persistent_cart_enabled', true ) ) {
			update_user_meta(
				get_current_user_id(),
				'_tripzzy_persistent_cart_' . get_current_blog_id(),
				array(
					'cart' => $this->get_cart_for_session(),
				)
			);
		}
	}

	/**
	 * Delete the persistent cart permanently.
	 */
	public function persistent_cart_destroy() {
		if ( get_current_user_id() && apply_filters( 'tripzzy_persistent_cart_enabled', true ) ) {
			delete_user_meta( get_current_user_id(), '_tripzzy_persistent_cart_' . get_current_blog_id() );
		}
	}

	/**
	 * Set cart hash cookie and items in cart if not already set.
	 *
	 * @param bool $set Should cookies be set (true) or unset.
	 */
	private function set_cart_cookies( $set = true ) {
		if ( $set ) {
			$setcookies = array(
				'tripzzy_items_in_cart' => '1',
				'tripzzy_cart_hash'     => tripzzy()->cart->get_cart_hash(),
			);
			foreach ( $setcookies as $name => $value ) {
				if ( ! isset( $_COOKIE[ $name ] ) || $_COOKIE[ $name ] !== $value ) {
					Cookie::set( $name, $value );
				}
			}
		} else {
			$unsetcookies = array(
				'tripzzy_items_in_cart',
				'tripzzy_cart_hash',
			);
			foreach ( $unsetcookies as $name ) {
				if ( isset( $_COOKIE[ $name ] ) ) {
					Cookie::set( $name, 0, time() - HOUR_IN_SECONDS );
					unset( $_COOKIE[ $name ] );
				}
			}
		}
		do_action( 'tripzzy_set_cart_cookies', $set ); // step 2 set cart cookie.
	}

	/**
	 * Get the persistent cart from the database.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	private function get_saved_cart() {
		$saved_cart = array();

		if ( apply_filters( 'tripzzy_persistent_cart_enabled', true ) ) {
			$saved_cart_meta = get_user_meta( get_current_user_id(), '_tripzzy_persistent_cart_' . get_current_blog_id(), true );

			if ( isset( $saved_cart_meta['cart'] ) ) {
				$saved_cart = array_filter( (array) $saved_cart_meta['cart'] );
			}
		}

		return $saved_cart;
	}
}
