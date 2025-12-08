<?php
/**
 * Trip Schema Helper
 *
 * @package tripzzy
 * @since 1.2.3
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;

/**
 * Custom Categories.
 *
 * @since 1.2.3
 */
if ( ! class_exists( 'Tripzzy\Core\Helpers\Schema' ) ) {
	/**
	 * Custom Categories Class.
	 */
	class Schema {
		use SingletonTrait;

		/**
		 * Trip object.
		 *
		 * @var object $trip
		 */
		public static $trip;

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'wp_head', array( __CLASS__, 'render' ) );
		}

		/**
		 * Render Tripzzy Trip schemas.
		 *
		 * @since 1.2.3
		 */
		public static function render() {

			if ( Page::is( 'trip' ) ) {
				$trip       = new Trip( get_the_ID() );
				self::$trip = $trip;
			}
			$settings = Settings::get();
			if ( ! $settings['enable_schema'] ) {
				return;
			}
			if ( $settings['enable_itinerary_schema'] ) {
				self::render_itineraries_schema();
			}
			if ( $settings['enable_faqs_schema'] ) {
				self::render_faqs_schema();
			}
		}

		/**
		 * Render ld+json Schema for trip.
		 *
		 * @since 1.2.3
		 * @return string
		 */
		public static function render_itineraries_schema() {
			if ( ! is_object( self::$trip ) ) {
				return;
			}
			$trip        = self::$trip;
			$schema      = array();
			$itineraries = $trip->get_itineraries();
			if ( is_array( $itineraries ) && count( $itineraries ) > 0 ) {
				$item_list_element = array();
				foreach ( $itineraries as $i => $itinerary ) {
					$item_list_element[] = array(
						'@type'    => 'ListItem',
						'position' => (int) ( $i + 1 ),
						'item'     => array(
							'@type'       => 'TouristAttraction', // Fixed.
							'name'        => $itinerary['title'] ?? '',
							'description' => $itinerary['description'] ?? '',
						),
					);
					++$i;
				}
				$schema = array(
					'@context'  => 'https://schema.org',
					'@type'     => 'Trip',
					'name'      => ucwords( $trip->title ?? '' ),
					'itinerary' => array(
						'@type'           => 'ItemList',
						'numberOfItems'   => count( $itineraries ),
						'itemListElement' => $item_list_element,
					),
				);
			}

			/**
			 * Trip schema structure.
			 *
			 * @param array  $schema Schema data for trip itineraries.
			 * @param object $trip Trip object.
			 * @since 1.2.3
			 */
			$schema = apply_filters( 'tripzzy_filter_trip_itineraries_schema', $schema, $trip );
			self::render_schema( $schema );
		}

		/**
		 * Render ld+json Schema for Faqs(rich result).
		 *
		 * @since 1.2.3
		 * @return string
		 */
		public static function render_faqs_schema() {
			if ( ! is_object( self::$trip ) ) {
				return;
			}
			$trip = self::$trip;

			$schema = array();
			$faqs   = $trip->get_faqs();
			if ( is_array( $faqs ) && count( $faqs ) > 0 ) {
				$main_entity = array();
				foreach ( $faqs as $i => $faq ) {
					$main_entity[] = array(
						'@type'          => 'Question',
						'name'           => $faq['question'],
						'acceptedAnswer' => array(
							'@type' => 'Answer',
							'text'  => $faq['answer'],
						),
					);
					++$i;
				}
				$schema = array(
					'@context'   => 'https://schema.org',
					'@type'      => 'FAQPage',
					'name'       => ucwords( $trip->title ?? '' ),
					'mainEntity' => $main_entity,
				);
			}

			/**
			 * Trip schema structure.
			 *
			 * @param array  $schema Schema data for trip.
			 * @param object $trip Trip object.
			 * @since 1.2.3
			 */
			$schema = apply_filters( 'tripzzy_filter_trip_faqs_schema', $schema, $trip );
			self::render_schema( $schema, 'rich_results' );
		}

		/**
		 * Render schema as per $schema array.
		 *
		 * @since 1.2.3
		 * @param array  $schema Schema structure array.
		 * @param string $context Schema context.
		 * @return void
		 */
		public static function render_schema( $schema = array(), $context = 'general' ) {
			if ( ! $schema ) {
				return;
			}
			$schema_structure = '';
			if ( 'general' === $context ) : ?>
			<!-- Tripzzy General Schema v<?php echo esc_html( TRIPZZY_VERSION ); ?> -->
			<?php else : ?>
			<!-- Tripzzy Google-Supported Schema v<?php echo esc_html( TRIPZZY_VERSION ); ?> --> 
			<?php endif; ?>
			<script type="application/ld+json"><?php echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE ); ?></script>
			<?php
		}
	}
}
