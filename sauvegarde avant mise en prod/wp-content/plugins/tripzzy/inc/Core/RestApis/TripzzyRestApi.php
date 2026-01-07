<?php
/**
 * Additonal Data for Rest API.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\RestApis;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\RestApiBase;
use Tripzzy\Core\Helpers\SearchForm;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\Amount;
use Tripzzy\Core\Helpers\Reviews;
use Tripzzy\Admin\Permalinks;
use Tripzzy\Core\Image;

if ( ! class_exists( 'Tripzzy\Core\RestApis\TripzzyRestApi' ) ) {
	/**
	 * Search Form Rest API.
	 *
	 * @since 1.0.0
	 */
	class TripzzyRestApi extends RestApiBase {
		/**
		 * Post Type.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $post_type = 'tripzzy';

		/**
		 * Constructor.
		 */
		public function __construct() {
			// Add additional fields as response in Rest API.
			add_filter( 'rest_prepare_' . self::$post_type, array( $this, 'additional_rest_fields' ), 10, 2 );

			// Modify WP Query args as per value passed as query param in Rest API.
			add_filter( 'rest_' . self::$post_type . '_query', array( $this, 'modify_rest_query' ), 10, 2 );
		}

		/**
		 * Additional Custom Data to add in WP Rest API.
		 *
		 * @param array  $data API Data.
		 * @param object $post WP Post object.
		 * @since 1.3.0 Added Packages and dates.
		 * @since 1.0.9 Added Price per field in array.
		 * @since 1.0.7 Added media image full size.
		 * @since 1.0.0
		 * @return array
		 */
		public function additional_rest_fields( $data, $post ) {
			$settings = Settings::get();

			$trip_id       = $post->ID;
			$trip          = new Trip( $trip_id );
			$category      = $trip->package_category();
			$has_sale      = false;
			$price         = 0;
			$regular_price = 0;
			$sale_percent  = 0;
			if ( $category ) {
				$has_sale      = $category->has_sale();
				$price         = $category->get_price();
				$regular_price = $category->get_regular_price();
				$sale_percent  = $category->get_sale_percent();
			}
			$price_markup         = Amount::display( $price );
			$regular_price_markup = Amount::display( $regular_price );
			$image_url            = sprintf( '%sassets/images/sprite.svg', esc_url( TRIPZZY_PLUGIN_DIR_URL ) );
			// Difficulties.
			$difficulty        = $trip::get_difficulty();
			$difficulty_levels = $settings['trip_difficulties'];
			$difficulty_index  = $difficulty ? ( absint( $difficulty ) ) - 1 : 1;
			$difficulty_level  = isset( $difficulty_levels[ $difficulty_index ]['label'] ) ? $difficulty_levels[ $difficulty_index ]['label'] : '';
			$has_difficulties  = (bool) isset( $settings['enable_trip_difficulties'] ) && $settings['enable_trip_difficulties'];
			$dates             = $trip->dates();

			$data->data['tripzzy_thumbnail_url_full']          = Image::get_thumbnail_url( $trip_id, 'full' ); // @since 1.0.7
			$data->data['tripzzy_thumbnail_url']               = Image::get_thumbnail_url( $trip_id );
			$data->data['tripzzy_is_featured']                 = $trip->is_featured();
			$data->data['tripzzy_has_sale']                    = $has_sale;
			$data->data['tripzzy_sale_percent']                = $sale_percent;
			$data->data['tripzzy_price_per']                   = $trip->price_per;
			$data->data['tripzzy_packages']                    = $trip->get_packages();
			$data->data['tripzzy_dates']                       = $dates->get_dates();
			$data->data['tripzzy_price']                       = $price;
			$data->data['tripzzy_regular_price']               = $regular_price;
			$data->data['tripzzy_price_markup']                = $price_markup;
			$data->data['tripzzy_regular_price_markup']        = $regular_price_markup;
			$data->data['tripzzy_trip_ratings_average_markup'] = Reviews::ratings_average_html( Reviews::get_trip_ratings_average( $trip_id ), false );
			$data->data['tripzzy_sprite_image_url']            = $image_url;
			$data->data['tripzzy_tax_destination']             = $trip->get_destinations();
			$data->data['tripzzy_trip_duration']               = $trip->get_duration();
			$data->data['tripzzy_has_difficulties']            = $has_difficulties;
			$data->data['tripzzy_difficulty']                  = $difficulty_level;

			return $data;
		}

		/**
		 * Modify WP Rest API query as per request. For meta query or any other custom query.
		 *
		 * @param array  $args Query args.
		 * @param object $request Request data.
		 * @since 1.0.0
		 * @since 1.0.7 Check whether tax query param available or not.
		 * @return array
		 */
		public function modify_rest_query( $args, $request ) {

			// Meta Query.
			$featured = 'true' === $request->get_param( 'featured' );
			if ( $featured ) {
				$args['meta_query'][] = array(
					'key'     => 'tripzzy_featured',
					'value'   => '1',
					'compare' => '=',
				);
			}
			// Taxonomy Query.
			$tax_query = $request->get_param( 'tax_query' );
			if ( $tax_query ) {
				$tax_query = json_decode( $tax_query );
				if ( is_array( $tax_query ) && count( $tax_query ) > 0 ) {
					foreach ( $tax_query as $tq ) {
						$args['tax_query'][] = (array) $tq;
					}
				}
			}
			return $args;
		}
	}
}
