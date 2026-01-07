<?php
/**
 * Base Class.
 * Real meta value is saved in associative array to perform array function.
 * when we fetch it convert into indexed array to paly with javascript array and vice versa.
 *
 * @todo Setter Method is in FormAjax file for now. need to add here ASAP.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Bases;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Helpers\ArrayHelper;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Forms\Form;

if ( ! class_exists( 'Tripzzy\Core\Bases\FormBase' ) ) {
	/**
	 * Base Class.
	 *
	 * @since 1.0.0
	 */
	class FormBase {

		/**
		 * Meta key to save fields value
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $meta_key = 'fields';

		/**
		 * Get Form Fields. [ Checkout, Enquiry ].
		 *
		 * @uses CheckoutForm::get_fields(); Don't use directly from base class like FormBase::get_fields().
		 *
		 * @param int  $form_id Form id will pass from admin form pages like checkout, enquiry form etc.
		 * @param bool $for_email Whether is it for email or not.
		 * @since 1.0.0
		 * @since 1.0.4 $for_email param added.
		 * @since 1.2.4 Fixes for admin enquiry shows same trip name.
		 */
		public static function get_fields( $form_id = null, $for_email = false ) {
			if ( ! $form_id ) {
				$form_id = static::get_form_id();
			}

			if ( ! $form_id ) {
				return;
			}
			$fields = MetaHelpers::get_post_meta( $form_id, self::$meta_key );
			$fields = ArrayHelper::wp_parse_args_recursive( $fields, static::default_fields() );
			if ( $for_email ) {
				$fields = EmailBase::parse_all_to_text_view( $fields );
			}

			// Fix empty trip id on enquiy form if form is modified. @since 1.2.4.
			if ( 'Tripzzy\Core\Forms\EnquiryForm' === self::get_called_class() && ! \is_admin() && isset( $fields['trip_id'] ) ) {
				$fields['trip_id']['value'] = get_the_ID();
			}
			// Need to sorting functionality because above parse arg will return array as per default value. where as we need to get sorted value from saved post_meta.
			// So when we sort the item priority must be updated as per sorted value.
			$fields = ArrayHelper::sort_by_priority( $fields );
			$fields = array_values( $fields ); // Convert into indexed array to play with javascript array.
			return $fields;
		}

		/**
		 * Return the called class
		 *
		 * @since 1.2.4
		 * @return string
		 */
		public static function get_called_class() {
			return get_called_class();
		}

		/**
		 * Get Form Field names. [ Checkout, Enquiry ]. This will only return filed name of parent field.
		 * This will not return child field names except wrapper field as it it only a wrapper div element rather than input.
		 *
		 * @uses CheckoutForm::get_field_names(); Don't use directly from base class like FormBase::get_field_names().
		 *
		 * @param int   $form_id Form id will pass from admin form pages like checkout, enquiry form etc.
		 * @param array $fields Child fields while recursion.
		 * @param array $names Child field names while recursion.
		 * @since 1.0.0
		 */
		public static function get_field_names( $form_id = null, $fields = array(), $names = array() ) {
			if ( ! $fields || ! count( $fields ) > 0 ) {
				if ( ! $form_id ) {
					$form_id = static::get_form_id();
				}

				if ( ! $form_id ) {
					return;
				}
				$fields = self::get_fields( $form_id );
			}
			foreach ( $fields as $field ) {
				if ( 'wrapper' === $field['type'] && isset( $field['children'] ) && count( $field['children'] ) > 0 ) {
					$names = self::get_field_names( null, $field['children'], $names );
				} else {
					$names[] = $field['name'];
				}
			}
			return $names;
		}

		/**
		 * Get the list of input type along with its own attributes.
		 *
		 * @since 1.0.0
		 */
		public static function get_field_type_attributes() {
			return apply_filters( 'tripzzy_filter_field_type_attributes', array() );
		}

		/**
		 * Render form fields html.
		 */
		public static function render() {
			$fields = self::get_fields();
			return Form::render( compact( 'fields' ) );
		}
	}
}
