<?php
/**
 * Taxonomy Dropdown Input.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Forms\Inputs;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Forms\Inputs\Dropdown;

use Tripzzy\Core\Helpers\DropdownOptions;
use Tripzzy\Core\Helpers\Taxonomy;

if ( ! class_exists( 'Tripzzy\Core\Forms\Inputs\TaxonomyDropdown' ) ) {
	/**
	 * Taxonomy Dropdown Input.
	 *
	 * @since 1.0.0
	 */
	class TaxonomyDropdown extends Dropdown {
		/**
		 * Taxonomy.
		 *
		 * @var $taxonomy
		 * @since 1.0.0
		 */
		protected static $taxonomy;

		/**
		 * Field Type.
		 *
		 * @var $field_type
		 * @since 1.0.0
		 */
		protected static $field_type = 'taxonomy_dropdown';


		/**
		 * Dropdown Options
		 *
		 * @var $options
		 * @since 1.0.0
		 */
		protected static $options = null;

		/**
		 * Init Attributes defined in individual input class.
		 *
		 * @since 1.0.0
		 */
		public static function init_attribute() {
			add_filter( 'tripzzy_filter_field_attributes', array( 'Tripzzy\Core\Forms\Inputs\TaxonomyDropdown', 'register_attribute' ) );
		}

		/**
		 * Callback to init attributes.
		 *
		 * @param array $attribute Field data along with attributes.
		 * @since 1.0.0
		 */
		public static function register_attribute( $attribute ) {
			$attribute[ self::$field_type ] = array(
				'label' => __( 'Taxonomy Dropdown', 'tripzzy' ),
				'class' => 'Tripzzy\Core\Forms\Inputs\TaxonomyDropdown',
				'attr'  => array(),
			);
			return $attribute;
		}
	}
}
