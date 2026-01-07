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
#[\AllowDynamicProperties]
class CategoryPricing {

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
	 * Constructor.
	 *
	 * @param array $data Data.
	 * @throws \Exception If error occur or data is empty.
	 */
	public function __construct( $data ) {
		if ( ! isset( $data['id'] ) ) {
			throw new \Exception( esc_html__( 'Arguments array must contains `id`', 'tripzzy' ) );
		}
		$this->id   = $data['id'];
		$this->term = get_term( $this->id );
		$this->data = $data;
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
	 * Get Title
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->term->name;
	}

	/**
	 * Gets Actual Price.
	 *
	 * @return float
	 */
	public function get_actual_price() {
		return $this->has_sale() ? $this->get_sale_price() : $this->get_price();
	}

	/**
	 * Gets Price.
	 *
	 * @return int|float
	 */
	public function get_price() {
		return isset( $this->data['price'] ) ? (float) $this->data['price'] : 0;
	}

	/**
	 * Returns sale price if category has sale.
	 *
	 * @return int|float
	 */
	public function get_sale_price() {
		return isset( $this->data['sale_price'] ) ? (float) $this->data['sale_price'] : 0;
	}

	/**
	 * Return if price catgory has sale.
	 *
	 * @return boolean
	 */
	public function has_sale() {
		return ! ! $this->get_sale_price();
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
