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
class TripPackageCategory {

	/**
	 * Pricing data.
	 *
	 * @var array $data
	 */
	protected $data = array();

	/**
	 * Pricing category/term id.
	 *
	 * @var int ID.
	 */
	protected $id = null;

	/**
	 * Price Per value.
	 *
	 * @var string price per value. ['person' | 'group']
	 */
	public $price_per = 'person';

	/**
	 * Term object instance.
	 *
	 * @since 1.0.7
	 * @var object term object
	 */
	public $term = null;

	/**
	 * Constructor.
	 *
	 * @param array $data Data.
	 * @throws \Exception If error occur or data is empty.
	 */
	public function __construct( $data ) {
		if ( ! isset( $data['id'] ) ) {
			throw new \Exception( esc_html__( 'Arguments array must contains `id`', 'tripzzy' ) );
		}
		$this->id        = $data['id'];
		$this->term      = get_term( $this->id );
		$this->data      = $data;
		$this->price_per = $data['price_per'];
	}

	/**
	 * Gets Pricing ID.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->term->name ?? '';
	}

	/**
	 * Get Data.
	 *
	 * @since 1.2.1
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Gets Actual Price.
	 *
	 * @return float
	 */
	public function get_price() {
		return $this->has_sale() ? $this->get_sale_price() : $this->get_regular_price();
	}

	/**
	 * Gets Price.
	 *
	 * @return int|float
	 */
	public function get_regular_price() {
		if ( 'person' === $this->price_per ) {
			$price = isset( $this->data['price'] ) ? (float) $this->data['price'] : 0;
		} else {
			$price = isset( $this->data['group_price'] ) ? (float) $this->data['group_price'] : 0;
		}
		return $price;
	}

	/**
	 * Returns sale price if category has sale.
	 *
	 * @return int|float
	 */
	public function get_sale_price() {

		if ( 'person' === $this->price_per ) {
			$price = isset( $this->data['sale_price'] ) ? (float) $this->data['sale_price'] : 0;
		} else {
			$price = isset( $this->data['group_sale_price'] ) ? (float) $this->data['group_sale_price'] : 0;
		}
		return $price;
	}

	/**
	 * Return if price catgory has sale.
	 *
	 * @return boolean
	 */
	public function has_sale() {
		return (bool) $this->get_sale_price();
	}

	/**
	 * Return Sale percent if available.
	 *
	 * @since 1.0.0
	 * @since 1.0.6 Sale percentage issue with -ve value.
	 * @since 1.2.7 Added filter to modify sale percent.
	 * @return void
	 */
	public function get_sale_percent() {
		if ( ! $this->has_sale() ) {
			return;
		}
		$percent = ( 100 - ( ( $this->get_price() * 100 ) / $this->get_regular_price() ) );
		$percent = number_format( $percent, 2 );

		$percent = $percent > 0 ? $percent : 0;
		/**
		 * Filter to modify sale percent.
		 *
		 * @since 1.2.7
		 */
		return apply_filters( 'tripzzy_filter_get_sale_percent', $percent, $this );
	}


	/**
	 * Returns if the category is default for the trip.
	 *
	 * @return boolean
	 */
	public function is_default() {
		return ! empty( $this->data['use_as_default'] ) && '1' === $this->data['use_as_default'];
	}
}
