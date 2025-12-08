<?php
/**
 * Tripzzy Customer session class.
 *
 * @since   1.0.0
 * @package tripzzy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Tripzzy_Session
 */
abstract class Tripzzy_Session {

	/**
	 * Customer ID.
	 *
	 * @var int $_customer_id Customer ID.
	 */
	protected $_customer_id; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
	/**
	 * Session Data.
	 *
	 * @var array $_data Data array.
	 */
	protected $_data = array(); // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

	/**
	 * Need to save when something changed.
	 *
	 * @var bool $_dirty When something changes.
	 */
	protected $_dirty = false; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

	/**
	 * Init hooks and Tripzzy session data from child classes.
	 *
	 * @since 1.0.0
	 */
	public function init() {}

	/**
	 * Cleanup session Tripzzy data from child classes.
	 */
	public function cleanup_sessions() {}

	/**
	 * Magic get method.
	 *
	 * @param mixed $key Key to get.
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->get( $key );
	}

	/**
	 * Magic set method.
	 *
	 * @param mixed $key Key to set.
	 * @param mixed $value Value to set.
	 */
	public function __set( $key, $value ) {
		$this->set( $key, $value );
	}

	/**
	 * Magic isset method.
	 *
	 * @param mixed $key Key to check.
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->_data[ sanitize_title( $key ) ] );
	}

	/**
	 * Magic unset method.
	 *
	 * @param mixed $key Key to unset.
	 */
	public function __unset( $key ) {
		if ( isset( $this->_data[ $key ] ) ) {
			unset( $this->_data[ $key ] );
			$this->_dirty = true;
		}
	}

	/**
	 * Get a session variable.
	 *
	 * @param string $key Key to get.
	 * @param mixed  $default_value used if the session variable isn't set.
	 * @return array|string value of session variable
	 */
	public function get( $key, $default_value = null ) {
		$key = sanitize_key( $key );
		return isset( $this->_data[ $key ] ) ? maybe_unserialize( $this->_data[ $key ] ) : $default_value;
	}

	/**
	 * Set a session variable.
	 *
	 * @param string $key Key to set.
	 * @param mixed  $value Value to set.
	 */
	public function set( $key, $value ) {
		if ( $value !== $this->get( $key ) ) {
			$this->_data[ sanitize_key( $key ) ] = maybe_serialize( $value );
			$this->_dirty                        = true;
		}
	}

	/**
	 * Get customer ID.
	 *
	 * @return int
	 */
	public function get_customer_id() {
		return $this->_customer_id;
	}
}
