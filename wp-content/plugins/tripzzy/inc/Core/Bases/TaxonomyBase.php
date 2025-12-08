<?php
/**
 * Base Class For Register Taxonomy.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Bases;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Traits\DataTrait;
use Tripzzy\Core\Helpers\Taxonomy;
use Tripzzy\Core\Helpers\ArrayHelper;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Forms\Form;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Image;

if ( ! class_exists( 'Tripzzy\Core\Bases\TaxonomyBase' ) ) {

	/**
	 * Base Class For Tripzzy Post Type.
	 *
	 * @since 1.0.0
	 */
	class TaxonomyBase {
		use SingletonTrait;
		use DataTrait;

		/**
		 * An array of post type arguments to register the custom post type with array key being post type slug and value being $args.
		 *
		 * @var array
		 * @since 1.0.0
		 */
		private static $taxonomy_args = array();

		/**
		 * An array of Form fields for term meta values.
		 *
		 * @var array
		 * @since 1.0.0
		 */
		private static $term_meta_form_fields = array();

		/**
		 * Initialize Taxonomies.
		 *
		 * @since 1.0.0
		 */
		public static function init() {
			$taxonomy_args       = apply_filters( 'tripzzy_filter_taxonomy_args', self::$taxonomy_args );
			self::$taxonomy_args = ArrayHelper::sort_by_priority( $taxonomy_args );

			if ( is_array( self::$taxonomy_args ) && ! empty( self::$taxonomy_args ) ) {
				foreach ( self::$taxonomy_args as $taxonomy => $args ) {
					$taxonomy = self::get_prefix( $taxonomy );
					if ( ! taxonomy_exists( $taxonomy ) ) {
						$object_types = $args['object_types'];
						unset( $args['object_types'] );
						register_taxonomy( $taxonomy, $object_types, $args );
					}
				}
				add_filter( 'term_updated_messages', array( __CLASS__, 'updated_messages' ) );
			}
		}

		/**
		 * Update Message Labels.
		 *
		 * @param array $messages List of taxonomy update message.
		 *
		 * @return array
		 */
		public static function updated_messages( $messages ) {
			$args = self::get_args();

			if ( is_array( $args ) && ! empty( $args ) ) {
				foreach ( $args as $taxonomy => $fields ) {
					$taxonomy      = self::get_prefix( $taxonomy );
					$labels        = $fields['labels'] ?? array();
					$name          = $labels['name'] ?? '';
					$singular_name = $labels['singular_name'] ?? '';
					if ( ! empty( $name ) && ! empty( $singular_name ) ) {
						$message = array(
							0 => '',
							/* translators: 1: Taxonomy singular name */
							1 => sprintf( __( '%s added.', 'tripzzy' ), $singular_name ),
							/* translators: 1: Taxonomy singular name */
							2 => sprintf( __( '%s deleted.', 'tripzzy' ), $singular_name ),
							/* translators: 1: Taxonomy singular name */
							3 => sprintf( __( '%s updated.', 'tripzzy' ), $singular_name ),
							/* translators: 1: Taxonomy singular name */
							4 => sprintf( __( '%s not added.', 'tripzzy' ), $singular_name ),
							/* translators: 1: Taxonomy singular name */
							5 => sprintf( __( '%s not updated.', 'tripzzy' ), $singular_name ),
							/* translators: 1: Taxonomy name */
							6 => sprintf( __( '%s deleted.', 'tripzzy' ), $name ),
						);

						$messages[ $taxonomy ] = $message;
					}
				}
			}
			return $messages;
		}

		/**
		 * Add/Edit term meta hooks.
		 *
		 * @return void
		 */
		public static function init_term_meta() {
			self::$term_meta_form_fields = apply_filters( 'tripzzy_filter_term_meta_form_fields', self::$term_meta_form_fields );

			if ( is_array( self::$term_meta_form_fields ) && ! empty( self::$term_meta_form_fields ) ) {
				foreach ( self::$term_meta_form_fields as $taxonomy => $fields ) {
					$taxonomy = self::get_prefix( $taxonomy );

					add_action( $taxonomy . '_add_form_fields', array( __CLASS__, 'add_form_fields' ), 10 );
					add_action( $taxonomy . '_edit_form_fields', array( __CLASS__, 'edit_form_fields' ), 10, 2 );
					add_action( 'created_' . $taxonomy, array( __CLASS__, 'update_form_fields' ), 10 );
					add_action( 'edited_' . $taxonomy, array( __CLASS__, 'update_form_fields' ), 10, 2 );
				}
			}
		}

		/**
		 * Get the Taxonomy name defined in the child class.
		 * Note: Do not call this method directly form parent class.
		 *
		 * @since 1.0.0
		 * @return string
		 */
		public static function get_key() {
			return self::get_prefix( static::$taxonomy );
		}

		/**
		 * Get all taxonomy args.
		 * Helps to add list all taxonomies and add any options like filters option in taxonomies as well.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public static function get_args() {
			return self::$taxonomy_args;
		}

		/**
		 * Add Taxonomy arguments to create taxonomy.
		 *
		 * @param array $taxonomy_args Array arguments.
		 *
		 * @since 1.0.0
		 */
		public function init_args( $taxonomy_args ) {
			$taxonomy_args[ self::get_key() ] = static::taxonomy_args();
			return $taxonomy_args;
		}

		/**
		 * Add form fields arguments.
		 *
		 * @param array $term_meta_form_fields Array arguments.
		 *
		 * @since 1.0.0
		 */
		public function init_term_meta_form_fields( $term_meta_form_fields ) {
			$term_meta_form_fields[ self::get_key() ] = static::term_meta_form_fields();
			return $term_meta_form_fields;
		}

		/**
		 * Term meta field list of all taxonomy.
		 *
		 * @return array
		 */
		public static function get_term_meta_form_fields() {
			return self::$term_meta_form_fields;
		}

		/**
		 * Depth level set to 1 while creating new terms.
		 *
		 * @param array  $dropdown_args Array arguments.
		 * @param string $taxonomy Taxonomy name.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public function parent_dropdown_args( $dropdown_args, $taxonomy ) {
			if ( self::get_key() === $taxonomy && isset( static::$depth ) ) {
				$dropdown_args['depth'] = static::$depth;
			}
			return $dropdown_args;
		}


		/**
		 * Get Taxonomy Terms.
		 *
		 * @since 1.0.0
		 * @param array $args Taxonomy arguments.
		 */
		public static function get_terms( $args = array() ) {
			$tax_args = array(
				'hide_empty' => false,
			);
			$tax_args = wp_parse_args( $args, $tax_args );
			return Taxonomy::get_terms( self::get_key(), $tax_args );
		}

		/**
		 * Get Taxonomy dropdown options.
		 *
		 * Do not use directly form base class.
		 *
		 * @param bool $term_id_as_value Whether use slug as value or id as value.
		 *
		 * @since 1.0.0
		 */
		public static function get_dropdown_options( $term_id_as_value = true ) {
			$terms   = self::get_terms();
			$options = array();
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
		 * @param bool $term_id_as_value Whether use slug as value or id as value.
		 *
		 * @since 1.0.0
		 */
		public static function get_grouped_dropdown_options( $term_id_as_value = true ) {
			$terms   = self::get_terms();
			$options = Taxonomy::parse_grouped_terms( $terms, $term_id_as_value );
			return $options;
		}

		// Term Metas start.
		/**
		 * Add Term meta form fields markup.
		 *
		 * @param string $taxonomy Taxonomy name.
		 *
		 * @return void
		 */
		public static function add_form_fields( $taxonomy ) {
			$all_forms     = self::get_term_meta_form_fields();
			$taxonomy_form = isset( $all_forms[ $taxonomy ] ) ? $all_forms[ $taxonomy ] : array();

			if ( is_array( $taxonomy_form ) && count( $taxonomy_form ) > 0 ) {
				$args = array(
					'fields' => $taxonomy_form,
				);
				?>
				<div class="tripzzy-term-meta-field">
					<?php Form::render( $args ); ?>
				</div>
				<?php
			}
		}

		/**
		 * Edit Term meta form fields markup.
		 *
		 * @param object $term Current term object.
		 * @param string $taxonomy Taxonomy name.
		 *
		 * @return void
		 */
		public static function edit_form_fields( $term, $taxonomy ) {
			$all_forms     = self::get_term_meta_form_fields();
			$taxonomy_form = isset( $all_forms[ $taxonomy ] ) ? $all_forms[ $taxonomy ] : array();

			if ( is_array( $taxonomy_form ) && count( $taxonomy_form ) > 0 ) {
				// Add value to the form fields.
				foreach ( $taxonomy_form as $key => $field ) {
					$name                           = $field['name'];
					$meta_value                     = MetaHelpers::get_term_meta( $term->term_id, $field['name'] );
					$taxonomy_form[ $key ]['value'] = $meta_value;
				}
				$args = array(
					'fields' => $taxonomy_form,
				);
				?>
				<tr class="tripzzy-term-meta-field">
					<th colspan="2">
					<?php
					Form::render( $args );
					?>
					</th>
				</tr>
				<?php
			}
		}

		/**
		 * Add Term meta form fields markup.
		 *
		 * @param int $term_id Term id.
		 *
		 * @return void
		 */
		public static function update_form_fields( $term_id ) {
			if ( ! Nonce::verify() ) {
				return;
			}

			$term     = get_term( $term_id );
			$taxonomy = $term->taxonomy;

			$all_forms_fields     = self::get_term_meta_form_fields();
			$taxonomy_form_fields = isset( $all_forms_fields[ $taxonomy ] ) ? $all_forms_fields[ $taxonomy ] : array();

			if ( is_array( $taxonomy_form_fields ) && count( $taxonomy_form_fields ) > 0 ) {

				foreach ( $taxonomy_form_fields as $form_field ) {
					$field_name = $form_field['name'];
					$field_type = $form_field['type'] ?? 'text';
					// Nonce already verified using Nonce::verify method.
					if ( isset( $_POST[ $field_name ] ) ) { // @codingStandardsIgnoreLine
						$value = sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) ); // @codingStandardsIgnoreLine
						MetaHelpers::update_term_meta( $term_id, $field_name, $value );
					}
					if ( 'image' === $field_type ) {
						$field_name .= '_url';
						if ( isset( $_POST[ $field_name ] ) ) { // @codingStandardsIgnoreLine
							$value = Request::sanitize_value( $_POST[ $field_name ] ); // @codingStandardsIgnoreLine
							MetaHelpers::update_term_meta( $term_id, $field_name, $value );
						}
					}
				}
			}
		}

		/**
		 * Get Small thumbnail to display in Taxonomy term list. Just a wrapper method of Image::get_taxonomy_thumbnail().
		 *
		 * This method has fixed image height and width. so may not be feasible for other place.
		 *
		 * @param int $image_id Term image id.
		 * @since 1.0.8
		 * @return string
		 */
		public static function get_thumbnail_small( $image_id ) {
			$args = array(
				'size'   => 'thumbnail-small', // only for default thumbnail size if taxonomy image not exists.
				'width'  => '60',
				'height' => '45',
			);
			return Image::get_taxonomy_thumbnail( $image_id, $args );
		}
	}
}
