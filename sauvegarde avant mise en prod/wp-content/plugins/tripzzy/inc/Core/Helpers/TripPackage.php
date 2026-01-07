<?php
/**
 * Trip Package Helper Class.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Helpers;

defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Helpers\Trip;

/**
 * Trip Package class.
 */
class TripPackage implements \IteratorAggregate {

	/**
	 * Pricing data.
	 *
	 * @var array $data
	 */
	protected $data = array();

	/**
	 * Package id.
	 *
	 * @var int ID.
	 */
	protected $id = null;

	/**
	 * Package data.
	 *
	 * @var array package.
	 */
	protected $package = null;

	/**
	 * Categories under each package.
	 *
	 * @var array categories.
	 */
	protected $categories = null;

	/**
	 * Default category id.
	 *
	 * @var array default_category_id.
	 */
	protected $default_category_id = null;

	/**
	 * Price per date.
	 *
	 * @var array
	 */
	protected $price_per_date = array();

	/**
	 * Constructor.
	 *
	 * @param array $data Package Data.
	 * @throws \Exception If error occur or data is empty.
	 */
	public function __construct( $data ) {
		if ( ! isset( $data['id'] ) ) {
			throw new \Exception( esc_html__( 'Trip Package must contains `package id`', 'tripzzy' ) );
		}

		if ( ! isset( $data['trip_id'] ) ) {
			throw new \Exception( esc_html__( 'Trip Package must contains `trip id`', 'tripzzy' ) );
		}
		$this->id   = $data['id'];
		$this->data = $data; // Trip Package data array.
		$this->set_data();
	}

	/**
	 * Gets Pricing ID.
	 *
	 * @return int
	 */
	public function get_id() {
		return (int) $this->id;
	}

	/**
	 * Get Title
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->data['title'];
	}

	/**
	 * Return Package Category as per category id
	 *
	 * @param int $category_id Category id.
	 * @return object
	 */
	public function get_category( $category_id = null ) {
		$categories = $this->categories;

		if ( ! $category_id ) {
			$category_id = $this->default_category_id;
		}
		if ( ! $category_id ) {
			return;
		}
		foreach ( $categories as $category ) {
			if ( (int) $category->get_id() === (int) $category_id ) {
				return $category;
			}
		}
	}

	/**
	 * Return Package Category as per category id
	 *
	 * @return object
	 */
	public function get_categories() {
		return $this->categories;
	}


	/**
	 * Set all price related data.
	 */
	protected function set_data() {
		if ( ! is_null( $this->categories ) ) {
			return $this->categories;
		}
		$categories = array();

		$trip_package        = $this->data;
		$default_category_id = null;

		$package_categories = $trip_package['package_categories'];
		if ( is_array( $package_categories ) && count( $package_categories ) > 0 ) {

			$default_category_id = $package_categories[0]['id'];
			foreach ( $package_categories as $package_category ) {
				// Add additional datas to category.
				$package_category['price_per']        = $trip_package['price_per'];
				$package_category['group_price']      = $trip_package['group_price'];
				$package_category['group_sale_price'] = $trip_package['group_sale_price'];

				/**
				 * Argument to pass param to TripPackageCategory class and parse trip_package.
				 *
				 * @since 1.1.7
				 */
				$package_category = apply_filters( 'tripzzy_filter_package_category_args', $package_category, $trip_package );
				$categories[]     = new TripPackageCategory( $package_category );

				if ( $package_category['use_as_default'] ) {
					$default_category_id = $package_category['id'];
				}
			}
		}

		if ( ! isset( $trip_package['price_per_date'] ) || ! is_array( $trip_package['price_per_date'] ) ) {
			$trip_package['price_per_date'] = array();
		}

		$this->price_per_date = $trip_package['price_per_date'];

		$this->default_category_id = $default_category_id;
		$this->categories          = $categories;
	}

	/**
	 * Get price for a specific date.
	 *
	 * @param mixed $date_id Date identifier.
	 * @return mixed|null Price for the date or default price if not set.
	 */
	public function get_price_for_date( $date_id ) {
		if ( is_array( $this->price_per_date ) && isset( $this->price_per_date[ $date_id ] ) ) {
			return $this->price_per_date[ $date_id ];
		}
		if ( isset( $this->data['price'] ) ) {
			return $this->data['price'];
		}
		return null;
	}

	/**
	 * Get getIterator method.
	 *
	 * @return object
	 */
	public function getIterator(): \Traversable {
		return new \ArrayIterator( $this->categories );
	}

	/**
	 * Get Total No of packages.
	 *
	 * @return int
	 */
	public function total() {
		return count( $this->categories );
	}
}
