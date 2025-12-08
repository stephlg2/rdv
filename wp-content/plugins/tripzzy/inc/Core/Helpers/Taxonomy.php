<?php
/**
 * Taxonomy Helper.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Traits\TripTrait;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\MetaHelpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\Taxonomy' ) ) {

	/**
	 * Taxonomy Helper.
	 *
	 * @since 1.0.0
	 */
	class Taxonomy {

		/**
		 * Get Taxonomy Terms.
		 *
		 * @param string $taxonomy Taxonomy name.
		 * @param array  $args Taxonomy arguments.
		 * @since 1.0.0
		 */
		public static function get_terms( $taxonomy, $args = array() ) {
			$tax_args             = array(
				'hide_empty' => false,
			);
			$tax_args             = wp_parse_args( $args, $tax_args );
			$tax_args['taxonomy'] = $taxonomy;
			return get_terms( $tax_args );
		}

		/**
		 * Get Term hierarchy as per texanomoy and its arguments.
		 *
		 * @param string $taxonomy Taxonomy.
		 * @param array  $args     Taxonomy arguments.
		 */
		public static function get_terms_hierarchy( $taxonomy, $args = array() ) {
			$terms = self::get_terms( $taxonomy, $args );
			return self::set_terms_hierarchy( $terms );
		}

		/**
		 * Order the Terms in paren chield hierarchy.
		 *
		 * @param array $terms List of terms.
		 * @return array
		 */
		public static function set_terms_hierarchy( $terms ) {
			$term_hierarchy = array();
			$unset_array    = array();
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term_object ) {
					$term_object->children = array();
					if ( isset( $term_hierarchy[ $term_object->term_id ]->children ) ) { // Init child if not previously set.
						$term_object->children = $term_hierarchy[ $term_object->term_id ]->children;
					}
					$term_hierarchy[ $term_object->term_id ] = $term_object;

					if ( $term_object->parent ) {
						if ( ! isset( $term_hierarchy[ $term_object->parent ] ) ) {
							$term_hierarchy[ $term_object->parent ] = (object) array(); // in case of child terms rendered first, therer will no parent term added in the array.
						}
						$term_hierarchy[ $term_object->parent ]->children[ $term_object->term_id ] = $term_object;
						$unset_array[] = $term_object->term_id;
					}
				}
			}
			// temp fixes.
			foreach ( $unset_array as $k ) {
				unset( $term_hierarchy[ $k ] );
			}
			return $term_hierarchy;
		}

		/**
		 * Parse the data for gruped dropdown options.
		 *
		 * @param array   $terms List of terms.
		 * @param boolean $term_id_as_value Either use term_id or slug as value.
		 * @param boolean $set_hierarchy Skip hierarchy for child list.
		 * @return array
		 */
		public static function parse_grouped_terms( $terms, $term_id_as_value = true, $set_hierarchy = true ) {
			if ( $set_hierarchy ) {
				$terms = self::set_terms_hierarchy( $terms );
			}
			$options = array();
			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$data = array( 'label' => $term->name );

					$data['value'] = $term_id_as_value ? $term->term_id : $term->slug;
					$data['term']  = $term; // term object. fetch term info if required.
					if ( isset( $term->children ) && is_array( $term->children ) && count( $term->children ) > 0 ) {
						$data['options'] = self::parse_grouped_terms( array_values( $term->children ), $term_id_as_value, false );
					}
					$options[] = $data;

				}
			}
			return $options;
		}

		/**
		 * Return the taxonomy/category terms assignd in trip.
		 *
		 * @param int    $trip_id Trip ID.
		 * @param string $taxonomy Taxonomy name.
		 */
		public static function get_trip_terms( $trip_id, $taxonomy ) {
			if ( ! $trip_id || ! $taxonomy ) {
				return;
			}
			$taxonomy = MetaHelpers::get_prefix( $taxonomy );
			$terms    = get_the_terms( $trip_id, $taxonomy );

			return is_array( $terms ) && count( $terms ) > 0 ? $terms : array();
		}

		/**
		 * Return dropdown options for taxonomy.
		 *
		 * @param string $taxonomy Name of taxonomy.
		 * @param bool   $term_id_as_value Whether use slug as value or id as value.
		 *
		 * @since 1.0.0
		 */
		public static function get_dropdown_options( $taxonomy, $term_id_as_value = false ) {
			$term_args = array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			);
			$terms     = self::get_terms( $taxonomy, $term_args );
			$options   = array();
			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$options[] = array(
						'label' => $term->name,
						'value' => $term_id_as_value ? $term->term_id : $term->slug,
					);
				}
			}
			return $options;
		}

		/**
		 * Get Taxonomy grouped dropdown options.
		 *
		 * Do not use directly form base class.
		 *
		 * @param string $taxonomy Name of taxonomy.
		 * @param bool   $term_id_as_value Whether use slug as value or id as value.
		 *
		 * @since 1.0.0
		 */
		public static function get_grouped_dropdown_options( $taxonomy, $term_id_as_value = true ) {
			$term_args = array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			);
			$terms     = self::get_terms( $taxonomy, $term_args );
			$options   = self::parse_grouped_terms( $terms, $term_id_as_value );
			return $options;
		}

		/**
		 * Retrieves the taxonomy name associated on the specified $term_id.
		 *
		 * @access public
		 * @param  int $term_id  The term ID from which to retrieve the taxonomy name.
		 * @return string $taxonomy The name of the taxaonomy associated with the term ID.
		 */
		public function get_taxonomy_by_term_id( $term_id ) {

			// We can't get a term if we don't have a term ID.
			if ( 0 === $term_id || null === $term_id ) {
				return;
			}

			// Grab the term using the ID then read the name from the associated taxonomy.
			$taxonomy = '';
			$term     = get_term( $term_id );
			if ( false !== $term ) {
				$taxonomy = $term->taxonomy;
			}

			return trim( $taxonomy );
		}
	}
}
