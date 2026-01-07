<?php
/**
 * Trip Pricings Helper.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Helpers;

defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Helpers\Trip;

/**
 * Price Category class.
 */
class TripPackages implements \IteratorAggregate {

	/**
	 * Trip object.
	 *
	 * @var null|Trip
	 */
		protected $trip = null;

	/**
	 * Pricing data.
	 *
	 * @var array $data
	 */
	protected $data = array();

	/**
	 * Package.
	 *
	 * @var array $package
	 */
	protected $packages = null;

	/**
	 * Package ID.
	 *
	 * @var int ID.
	 */
	protected $id = null;

	/**
	 * Fallback Package id if user not set any package id as default.
	 *
	 * @var int $default_package_id.
	 */
	public $default_package_id = null;

	/**
	 * Price Per value.
	 *
	 * @var string price per value. ['person' | 'group']
	 */
	public $price_per = 'person';

	/**
	 * Transient key related to all pricing data.
	 * Structure : tripzzy_{$transient_key}_{$trip_id}_{$package_id}_{$category_id}
	 * where $package_id and $category_id are optional.
	 *
	 * @var string $transient_key
	 */
		public $transient_key = 'package_data';

	/**
	 * Package args.
	 *
	 * @var array $package_args
	 * @since 1.1.7
	 */
	protected $package_args = array();

	/**
	 * Constructor.
	 *
	 * @param mixed $trip Trip ID or object.
	 * @param array $args package_args.
	 * @throws \Exception If error occur or data is empty.
	 */
	public function __construct( $trip, $args = array() ) {
		if ( is_numeric( $trip ) ) {
			$this->trip = new Trip( $trip );
		} elseif ( $trip instanceof Trip ) {
			$this->trip = $trip;
		}
		$this->package_args = $args;
		$this->set_data();
	}

	/**
	 * Get Trip Package.
	 *
	 * @param int $package_id Pricing arguments.
	 * @return array
	 */
	public function get_package( $package_id = null ) {

		$packages = $this->packages;

		if ( ! $package_id ) {
			$package_id = $this->default_package_id;
		}

		if ( ! $package_id ) {
			return;
		}

		if ( $packages ) {
			foreach ( $packages as $package ) {
				if ( $package->get_id() === absint( $package_id ) ) {
					return $package;
				}
			}
		}
	}

	/**
	 * Set all price related data.
	 *
	 * @param int $trip_id Trip id.
	 */
	protected function set_data( $trip_id = null ) {
		if ( ! is_null( $this->packages ) ) {
			return $this->packages;
		}

		// Set trip object if call pricing function directly.
		if ( is_null( $this->trip ) ) {
			if ( ! $trip_id ) {
				return;
			}
			$this->trip = new Trip( $trip_id );
		}
		$this->price_per = $this->trip->price_per;

		$trip_packages = $this->trip->get_meta( 'trip_packages', array() );
		$package_args  = $this->package_args;
		/**
		 * Argument to pass param to TripPackage class and parse package_args.
		 *
		 * @since 1.1.7
		 */
		$package_args = apply_filters( 'tripzzy_filter_trip_package_args', $package_args, $this->trip );

		$package_data       = array();
		$default_package_id = null;

		$_trip_packages = array();
		if ( is_array( $trip_packages ) && count( $trip_packages ) > 0 && isset( $trip_packages[0]['id'] ) ) {
			$default_package_id = absint( $trip_packages[0]['id'] ); // fallback default package id if user not set the default package.
			foreach ( $trip_packages as $index => $trip_package ) {
				$trip_package['trip_id']   = $this->trip->trip_id; // Add trip id in each package. for further use.
				$trip_package['price_per'] = $this->price_per;

				$trip_package = wp_parse_args( $package_args, $trip_package );
				// Set Package.
				$package          = new TripPackage( $trip_package );
				$_trip_packages[] = $package;

				// Set default package id.
				if ( $trip_package['use_as_default'] ) {
					$default_package_id = absint( $trip_package['id'] );
				}
			}
		}

		$this->default_package_id = $default_package_id;
		$this->packages           = $_trip_packages;
	}


	/**
	 * Get Key as per param provided
	 *
	 * @param int $trip_id Trip ID.
	 * @param int $package_id ID as timestamp.
	 * @param int $category_id Category ID.
	 * @return string
	 */
	public function transient_key( $trip_id = null, $package_id = null, $category_id = null ) {
		$key = $this->transient_key;

		if ( $trip_id ) { // append trip id.
			$key = sprintf( '%s_%s', $key, $trip_id );
		}
		if ( $package_id ) { // append package id.
			$key = sprintf( '%s_%s', $key, $package_id );
		}
		if ( $category_id ) { // append category id.
			$key = sprintf( '%s_%s', $key, $category_id );
		}
		return $key;
	}

	/**
	 * Get getIterator method.
	 *
	 * @return object
	 */
	public function getIterator(): \Traversable {
		return new \ArrayIterator( $this->packages );
	}

	/**
	 * Get Total No of packages.
	 *
	 * @return int
	 */
	public function total() {
		return count( $this->packages );
	}

	/**
	 * Delete Transitent data related to price.
	 *
	 * @param int $trip_id Trip ID.
	 * @return bool True if the transient was deleted, false otherwise.
	 */
	public static function delete_transient( $trip_id = null ) {
		$packages = new TripPackages( $trip_id );
		$key      = $packages->transient_key( $trip_id );

		$keys = Transient::get_all_keys( $trip_id );
		if ( is_array( $keys ) && count( $keys ) > 0 ) {
			foreach ( $keys as $k ) {
				Transient::delete( $k );
			}
		}
		return Transient::delete( $key );
	}
}
