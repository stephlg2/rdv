<?php
/**
 * Permalinks settings
 *
 * To add new fields in permalinks.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Traits\SingletonTrait;

if ( ! class_exists( 'Tripzzy\Admin\Permalinks' ) ) {

	/**
	 * Our main helper class that provides.
	 *
	 * @type `static`
	 * @since 1.0.0
	 */
	class Permalinks {
		use SingletonTrait;

		/**
		 * Permalinks fields.
		 *
		 * @since 1.0.0
		 * @var $fields
		 */
		public $fields = array();

		/**
		 * Permalinks Values.
		 *
		 * @var $permalinks
		 */
		public static $permalinks = array();

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->fields = self::permalinks_fields(); // init fields.
			// Add own settings section.
			if ( count( $this->fields ) > 0 ) {
				$this->add_settings_section();
				self::$permalinks = self::get();
				$this->permalinks_base();
			}
		}

		/**
		 * All fields.
		 *
		 * Need to add array and its callback to add new permalink field.
		 *
		 * @since 1.0.0
		 */
		private static function permalinks_fields() {
			$fields = array(
				'tripzzy_base'                  => array(
					'label'    => __( 'Trip base', 'tripzzy' ),
					'default'  => 'trips',
					'callback' => array( __CLASS__, 'tripzzy_base_callback' ),
				),
				'tripzzy_trip_type_base'        => array(
					'label'    => __( 'Trip type base', 'tripzzy' ),
					'default'  => 'trip-types',
					'callback' => array( __CLASS__, 'trip_type_base_callback' ),
				),
				'tripzzy_trip_destination_base' => array(
					'label'    => __( 'Trip destination base', 'tripzzy' ),
					'default'  => 'trip-destinations',
					'callback' => array( __CLASS__, 'trip_destination_base_callback' ),
				),
				'tripzzy_trip_activities_base'  => array(
					'label'    => __( 'Trip activities base', 'tripzzy' ),
					'default'  => 'trip-activities',
					'callback' => array( __CLASS__, 'trip_activities_base_callback' ),
				),
				'tripzzy_trip_keyword_base'     => array(
					'label'    => __( 'Trip Keyword base', 'tripzzy' ),
					'default'  => 'trip-keywords',
					'callback' => array( __CLASS__, 'trip_keyword_base_callback' ),
				),
			);
			return $fields;
		}

		// Callbacks.
		/**
		 * Callbacks for trip base.
		 *
		 * @since 1.0.0
		 */
		public static function tripzzy_base_callback() {
			$value = untrailingslashit( self::$permalinks['tripzzy_base'] );
			?>
				<input name="tripzzy_base" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr_x( 'tripzzy', 'slug', 'tripzzy' ); ?>" />
			<?php
		}
		/**
		 * Callbacks for trip type base.
		 *
		 * @since 1.0.0
		 */
		public static function trip_type_base_callback() {
			$value = untrailingslashit( self::$permalinks['tripzzy_trip_type_base'] );
			?>
				<input name="tripzzy_trip_type_base" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr_x( 'trip-type', 'slug', 'tripzzy' ); ?>" />
			<?php
		}

		/**
		 * Callbacks for trip destination base.
		 *
		 * @since 1.0.0
		 */
		public static function trip_destination_base_callback() {
			$value = untrailingslashit( self::$permalinks['tripzzy_trip_destination_base'] );
			?>
				<input name="tripzzy_trip_destination_base" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr_x( 'trip-destination', 'slug', 'tripzzy' ); ?>" />
			<?php
		}

		/**
		 * Callbacks for trip activities base.
		 *
		 * @since 1.0.0
		 */
		public static function trip_activities_base_callback() {
			$value = untrailingslashit( self::$permalinks['tripzzy_trip_activities_base'] );
			?>
				<input name="tripzzy_trip_activities_base" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr_x( 'trip-activities', 'slug', 'tripzzy' ); ?>" />
			<?php
		}

		/**
		 * Callbacks for trip keyword base.
		 *
		 * @since 1.0.0
		 */
		public static function trip_keyword_base_callback() {
			$value = untrailingslashit( self::$permalinks['tripzzy_trip_keyword_base'] );
			?>
				<input name="tripzzy_trip_keyword_base" type="text" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr_x( 'trip-keyword', 'slug', 'tripzzy' ); ?>" />
			<?php
		}
		// End of callbacks.

		/**
		 * Get All Default Permalinks Value.
		 *
		 * @since 1.0.0
		 */
		public static function get() {

			$default_permalinks = self::default_permalinks(); // Default Values.
			$permalinks         = MetaHelpers::get_option( 'permalinks', array() ); // Saved Values.

			return array_merge( $default_permalinks, $permalinks );
		}

		/**
		 * Sections
		 *
		 * @since 1.0.0
		 */
		public function add_settings_section() {
			if ( ! is_admin() ) {
				return;
			}
			\add_settings_section(
				'tripzzy_permalinks_section',
				__( 'Tripzzy', 'tripzzy' ),
				array( $this, 'settings_section_callback' ),
				'permalink'
			);
		}

		/**
		 * Section callback.
		 *
		 * @since 1.0.0
		 */
		public function settings_section_callback() {
			global $wp_settings_fields;

			// Settings fields already added by WP so just checking.
			if ( ! isset( $wp_settings_fields['permalink']['tripzzy_permalinks_section'] ) ) {
				?>
				<table class="form-table" role="presentation">
					<?php do_settings_fields( 'permalink', 'tripzzy_permalinks_section' ); ?>
				</table>
				<?php
			}
		}

		/**
		 * Generate Permalinks fields.
		 *
		 * @since 1.0.0
		 */
		private function permalinks_base() {
			if ( ! is_admin() ) {
				return;
			}
			if ( ! empty( $this->fields ) ) {
				foreach ( $this->fields as $field_name => $field_data ) {
					if ( ! isset( $field_data['callback'] ) ) {
						return;
					}
					$callback    = $field_data['callback'];
					$label       = isset( $field_data['label'] ) ? $field_data['label'] : '';
					$parent_page = isset( $field_data['parent_page'] ) ? isset( $field_data['parent_page'] ) : 'permalink';
					$section     = isset( $field_data['section'] ) ? isset( $field_data['section'] ) : 'tripzzy_permalinks_section';

					add_settings_field( $field_name, $label, $callback, $parent_page, $section );
				}
			}

			// Save Settings.
			if ( isset( $_POST['permalink_structure'] ) ) {
				check_admin_referer( 'update-permalink' );

				$permalinks = (array) MetaHelpers::get_option( 'permalinks', array() );

				$fields = self::default_permalinks();
				foreach ( $fields as $key => $v ) {
					$value = isset( $_POST[ $key ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ) : '';
					if ( ! empty( $value ) ) {
						$permalinks[ $key ] = preg_replace( '#/+#', '', '/' . str_replace( '#', '', $value ) );
					}
				}

				MetaHelpers::update_option( 'permalinks', $permalinks );
			}
		}

		/**
		 * Get All Default Permalinks Value.
		 *
		 * @since 1.0.0
		 */
		private static function default_permalinks() {
			$permalinks = array_map(
				function ( $array_values ) {
					return $array_values['default'];
				},
				self::permalinks_fields()
			);
			return $permalinks;
		}
	}
}
