<?php
/**
 * Trips.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Traits\TripTrait;
use Tripzzy\Core\Traits\DataTrait;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\Trip' ) ) {

	/**
	 * Our main helper class that provides.
	 *
	 * @since 1.0.0
	 */
	class Trip {
		use TripTrait;
		use DataTrait;

		/**
		 * Trip Object.
		 *
		 * @var $trip.
		 */
		public static $trip;

		/**
		 * All Post Metas.
		 *
		 * @var $all_meta.
		 */
		public static $all_meta;

		/**
		 * Only trip Metas.
		 *
		 * @var $trip_meta.
		 */
		public static $trip_meta;

		/**
		 * Trip id.
		 *
		 * @var $trip_id.
		 */
		public $trip_id;

		/**
		 * Trip Title.
		 *
		 * @since 1.2.3
		 * @var $title.
		 */
		public $title;

		/**
		 * Price per.
		 *
		 * @var $price_per.
		 */
		public $price_per = 'person';

		/**
		 * Trip Init.
		 *
		 * @param mixed $trip either trip id or trip object.
		 */
		public function __construct( $trip = null ) {
			if ( is_object( $trip ) ) {
				self::$trip = $trip;
			} elseif ( is_numeric( $trip ) ) {
				self::$trip = get_post( $trip );
			} else {
				self::$trip = get_post( get_the_ID() );
			}
			if ( ! self::$trip ) {
				return; // Prevent error in case of deleted trips.
			}
			self::$all_meta  = get_post_meta( self::$trip->ID );
			self::$trip_meta = MetaHelpers::get_post_meta( self::$trip->ID, 'trip' );

			$this->trip_id   = self::$trip->ID;
			$this->price_per = $this->get_meta( 'price_per', 'person' );
			$this->title     = get_the_title( $this->trip_id );
		}

		/**
		 * Trips dropdown list.
		 */
		public static function get_dropdown_options() {
			// Page Lists.
			$lists     = get_posts(
				array(
					'numberposts' => -1,
					'post_type'   => 'tripzzy',
					'orderby'     => 'title',
					'order'       => 'asc',
				)
			);
			$trip_list = array();
			$i         = 0;
			foreach ( $lists as $trip_data ) {
				$trip_list[ $i ]['label'] = $trip_data->post_title;
				$trip_list[ $i ]['value'] = $trip_data->ID;
				++$i;
			}
			return $trip_list;
		}

		/**
		 * Get All Trip data.
		 *
		 * @param int $trip_id Trip ID.
		 * @since 1.0.0
		 */
		public static function get( $trip_id ) {
			if ( ! $trip_id ) {
				return array();
			}
			$default = self::default_data(); // Default Values.

			$trip_data = self::get_data( $trip_id );

			if ( ! $trip_data ) {
				return;
			}

			$trip_data = $trip_data['trip_meta'];

			$trip_data = $trip_data ? $trip_data : array();
			$trip_data = wp_parse_args( $trip_data, $default );

			$duration_data = self::get_duration( $trip_id );

			// Individual Metas.
			$trip_data['trip_id']         = $trip_id;
			$trip_data['trip_code']       = self::get_code( $trip_id );
			$trip_data['trip_packages']   = self::get_packages( $trip_id );
			$trip_data['fixed_dates']     = self::get_fixed_dates( $trip_id );
			$trip_data['recurring_dates'] = self::get_recurring_dates( $trip_id );
			$trip_data['highlights']      = self::get_highlights( $trip_id );
			$trip_data['itineraries']     = self::get_itineraries( $trip_id );
			$trip_data['faqs']            = self::get_faqs( $trip_id );
			$trip_data['gallery']         = self::get_gallery( $trip_id );
			$trip_data['difficulty']      = self::get_difficulty( $trip_id );
			$trip_data['_thumbnail_id']   = get_post_thumbnail_id( $trip_id );
			$trip_data['duration']        = $duration_data['duration'];
			$trip_data['duration_unit']   = $duration_data['duration_unit'];

			// overriding trip infos.
			$trip_data['trip_infos'] = TripInfos::get( $trip_id );

			return $trip_data;
		}

		/**
		 * Update All Trip data.
		 *
		 * @param int    $trip_id Trip id to save.
		 * @param object $raw_data  Request Payload data.
		 * @since 1.0.0
		 * @since 1.1.3 Recurring fixes and Sticky Trips added.
		 */
		public static function update( $trip_id, $raw_data = array() ) {
			if ( ! $trip_id ) {
				return;
			}

			if ( ! $raw_data ) {
				// This Raw data is sanitized later in the loop below.
				$raw_data = Request::get_payload( true );
			}

			if ( empty( $raw_data ) ) {
				return;
			}

			$raw_data  = (array) $raw_data;
			$trip_data = self::get( $trip_id ); // Trip Data.
			$default   = self::default_data(); // Default Trip Data.
			$trip_code = '';
			unset( $trip_data['is_data_changed'], $trip_data['is_requesting'], $trip_data['is_sticky'] ); // Unset if accidently saved.
			$individual_meta_list        = self::individual_metas();
			$required_wp_keses_meta_list = self::required_wp_keses_metas();
			foreach ( $default as $key => $value ) {
				if ( isset( $raw_data[ $key ] ) ) {
					$wp_kses    = in_array( $key, $required_wp_keses_meta_list, true );
					$raw_value  = $raw_data[ $key ];
					$meta_value = Request::sanitize_data( $raw_value, $wp_kses ); // Sanitized value.
					if ( in_array( $key, $individual_meta_list, true ) ) {
						if ( 'recurring_dates' === $key ) {
							$meta_value = (array) $meta_value;
							if ( isset( $meta_value['bymonth'] ) && count( $meta_value['bymonth'] ) > 0 ) {
								$value                 = array_map( 'intval', $meta_value['bymonth'] );
								$value                 = array_unique( $value );
								$meta_value['bymonth'] = array_values( $value );
							}
							if ( isset( $meta_value['dtstart'] ) && 'invalid date' === strtolower( $meta_value['dtstart'] ) ) {
								$meta_value['dtstart'] = null;
							}
							if ( isset( $meta_value['until'] ) && 'invalid date' === strtolower( $meta_value['until'] ) ) {
								$meta_value['until'] = null;
							}
						}
						MetaHelpers::update_post_meta( $trip_id, $key, $meta_value );
						unset( $trip_data[ $key ] ); // need to fetch this value manually from above get function.
						continue;
					}
					$trip_data[ $key ] = $meta_value;
				}
			}

			// Extract Trip price from available price. to use in price query.
			$instance   = new self( $trip_id );
			$category   = $instance->package_category( $trip_id );
			$trip_price = $category ? $category->get_price() : 0;
			MetaHelpers::update_post_meta( $trip_id, 'trip_price', $trip_price );

			/**
			 * Filter trip data before save.
			 *
			 * @since 1.0.0
			 */
			$trip_data = apply_filters( 'tripzzy_filter_before_save_trip', $trip_data, $raw_data );
			MetaHelpers::update_post_meta( $trip_id, 'trip', $trip_data );
			/**
			 * Filter trip data after save.
			 *
			 * @since 1.0.0
			 */
			$trip_data = apply_filters( 'tripzzy_filter_after_save_trip', $trip_data, $raw_data );

			if ( isset( $raw_data['trip_includes'] ) ) {
				$trip_includes = ArrayHelper::flat_map( $raw_data['trip_includes'], 'term_id', true );
				if ( is_array( $trip_includes ) ) {
					wp_set_object_terms( $trip_id, $trip_includes, 'tripzzy_trip_includes' );
				}
			}

			if ( isset( $raw_data['trip_excludes'] ) ) {
				$trip_excludes = ArrayHelper::flat_map( $raw_data['trip_excludes'], 'term_id', true );
				if ( is_array( $trip_excludes ) ) {
					wp_set_object_terms( $trip_id, $trip_excludes, 'tripzzy_trip_excludes' );
				}
			}
			// Sticky Post @since 1.1.3.
			$sticky_posts = get_option( 'sticky_posts' );
			$key          = array_search( $trip_id, $sticky_posts, true );
			if ( $raw_data['is_sticky'] ) {
				if ( ! in_array( $trip_id, $sticky_posts, true ) ) {
					$sticky_posts[] = $trip_id;
					update_option( 'sticky_posts', $sticky_posts );
				}
			} elseif ( $key ) {
				unset( $sticky_posts[ $key ] );
				update_option( 'sticky_posts', $sticky_posts );
			}
			// @since 1.1.4
			$min_price = MetaHelpers::get_option( 'min_price', 0 );
			$max_price = MetaHelpers::get_option( 'max_price', 0 );
			if ( $trip_price < $min_price ) {
				MetaHelpers::update_option( 'min_price', $trip_price );
			}
			if ( $trip_price > $max_price ) {
				MetaHelpers::update_option( 'max_price', $trip_price );
			}

			// Delete Trip related Transient.
			TripPackages::delete_transient( $trip_id );
			TripDates::delete_transient( $trip_id );
			return $trip_data;
		}
		/**
		 * Get All Default Trip Value.
		 *
		 * @since 1.0.0
		 * @since 1.2.5 Added cut-off time default datas like: enable_cut_off_time, cut_off_time, cut_off_time_unit.
		 */
		public static function default_data() {
			$trip_data = array(
				'trip_code'           => '',
				'difficulty'          => 2,
				'highlights'          => array(),
				'gallery'             => array(),
				'faqs'                => array(),
				'global_faqs'         => array(),
				'itineraries'         => array(),
				'map_type'            => 'iframe',
				'map_iframe'          => '',
				'map_image'           => array(),
				'map_lat'             => '27.673602861598475', // Fallback for Map.
				'map_lng'             => '85.3249204158783', // Fallback for Map.
				'map_markers'         => array(
					array(
						'lat'  => '27.673602861598475',
						'lng'  => '85.3249204158783',
						'loc'  => 'Lalitpur 44600, Nepal',
						'desc' => '',
					),
				),
				'map_zoom'            => 15,
				// Pricing > General.
				'duration'            => array( 0, 0 ),
				'duration_unit'       => array( 'days', 'nights' ),
				'enable_cut_off_time' => false,
				'cut_off_time'        => 0,
				'cut_off_time_unit'   => 'days',
				'trip_includes'       => array(),
				'trip_excludes'       => array(),
				'price_per'           => 'person', // [person | group ].
				'trip_packages'       => array(),
				'group_price'         => '',
				'group_sale_price'    => '',
				'price_categories'    => array(),
				'min_people'          => 1,
				'max_people'          => '',
				'trip_date_type'      => 'fixed_dates', // Options [fixed_dates | recurring_dates].
				'fixed_dates'         => array(),
				'recurring_dates'     => array(
					'freq'       => 'daily', // RRule.MONTHLY, RRule.WEEKLY, RRule.DAILY, RRule.HOURLY, RRule.MINUTELY, RRule.SECONDLY.
					'dtstart'    => null,
					'until'      => null,
					'interval'   => 1, // Set freq = daily and interval = 2 then this is treat as every 2nd day[ i.e. in each 2 days].
					// 'count'      => 30, // no of list/ item to generate.
					'byweekday'  => array(), // SU, MO.
					'bymonth'    => array(), // JAN, FEB.
					'bymonthday' => array(), // Monthday 1, 2... 30,.
				),
				'trip_infos'          => array(),
				'section_titles'      => array( // Section Titles.
					'faqs'          => __( "FAQ's", 'tripzzy' ),
					'gallery'       => __( 'Gallery', 'tripzzy' ),
					'highlights'    => __( 'Highlights', 'tripzzy' ),
					'itineraries'   => __( 'Itineraries', 'tripzzy' ),
					'map'           => __( 'Map', 'tripzzy' ),
					'trip_date'     => __( 'Availability', 'tripzzy' ),
					'trip_includes' => __( 'Trip Includes & Excludes', 'tripzzy' ),
					'trip_infos'    => __( 'Trip Infos', 'tripzzy' ),
				),
				'is_sticky'           => false,
			);
			/**
			 * Default Trip Data. Used to store trip meta.
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'tripzzy_filter_default_trip_data', $trip_data );
		}

		/**
		 * Get section title.
		 *
		 * @param int $trip_id Trip id of trip.
		 * @since 1.0.0
		 */
		public static function get_section_titles( $trip_id = null ) {
			$default        = self::default_data();
			$defalut_titles = $default['section_titles'];
			$all_data       = self::get_data( $trip_id );
			if ( ! $all_data ) {
				return $defalut_titles;
			}
			$trip_meta      = $all_data['trip_meta'] ?? array();
			$section_titles = $trip_meta['section_titles'] ?? array();
			return array_merge( $defalut_titles, $section_titles );
		}

		/**
		 * List of meta key which data need to save each meta individually.
		 *
		 * Like trip_code is savee as tripzzy_trip_code meta and trip difficulty meta is save as tripzzy_difficulty respectively.
		 *
		 * @since 1.0.0
		 */
		public static function individual_metas() {
			$list = array( 'trip_code', 'difficulty', 'duration', 'duration_unit', 'trip_packages', 'highlights', 'itineraries', 'fixed_dates', 'recurring_dates', 'trip_infos', 'faqs', 'gallery' );
			return $list;
		}

		/**
		 * List of meta key which data need to save as wp_kses to support tags and shortcodes.
		 *
		 * @since 1.0.0
		 * @since 1.0.4 Added itineraries.
		 */
		private static function required_wp_keses_metas() {
		$list = array( 'overview', 'map_iframe', 'map_image', 'itineraries', 'faqs' );
			return $list;
		}

		/**
		 * Post Meta list to use it in Trip infos.
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public static function infos_meta() {
			$infos = array( 'min_people', 'max_people' );
			return $infos;
		}

		/**
		 * Get Trip ID by Trip Code.
		 *
		 * @param string $trip_code Trip code to check.
		 * @since 1.0.0
		 */
		public static function get_trip_id_by_code( $trip_code ) {
			if ( ! $trip_code ) {
				return false;
			}
			global $wpdb;

			$meta_key = 'tripzzy_trip_code';

			$sql = $wpdb->prepare(
				"
				SELECT post_id
				FROM $wpdb->postmeta
				WHERE meta_key = %s
				AND meta_value = %s
			",
				$meta_key,
				esc_sql( $trip_code )
			);

			$results = $wpdb->get_results( $sql ); // @phpcs:ignore

			if ( empty( $results ) ) {
				return false;
			}

			return $results['0']->post_id;
		}

		// All Trip section data render starts.
		/**
		 * Get trip code.
		 *
		 * @param int $trip_id Trip id of trip.
		 * @since 1.0.0
		 */
		public static function get_code( $trip_id = null ) {

			$trip_data = self::get_data( $trip_id );

			if ( ! $trip_data ) {
				return;
			}

			$trip_id  = $trip_data['trip_id'];
			$all_meta = $trip_data['all_meta'];

			$trip_code = isset( $all_meta['tripzzy_trip_code'] ) ? $all_meta['tripzzy_trip_code'] : '';
			$prefix    = apply_filters( 'tripzzy_filter_trip_code_prefix', 'TZ', $trip_id );
			if ( ! $trip_code ) {
				$trip_code = sprintf( '%1$s-%2$s', $prefix, $trip_id );
			}
			/**
			 * Filter to change trip code as per your need.
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'tripzzy_filter_trip_code', $trip_code, $trip_id );
		}

		/**
		 * Get trip features.
		 *
		 * @param int $trip_id Trip id of trip.
		 * @since 1.0.0
		 */
		public static function get_features( $trip_id = null ) {

			$trip_data = self::get_data( $trip_id );

			if ( ! $trip_data ) {
				return;
			}

			$trip_id  = $trip_data['trip_id'];
			$settings = Settings::get();
			$features = $settings['trip_features'] ?? array();
			/**
			 * Filter to change trip features as per your need.
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'tripzzy_filter_trip_features', $features, $trip_id );
		}

		/**
		 * This will return only trip meta saved as seprate key rather than data in one array.
		 *
		 * @param int    $trip_id Trip id of trip.
		 * @param string $key Meta key.
		 * @param bool   $single Either value is string (single) or array.
		 * @since 1.0.0
		 */
		public static function get_individual_meta( $trip_id = null, $key = '', $single = true ) {
			$trip_data = self::get_data( $trip_id );
			if ( ! $trip_data || ! $key ) {
				return $single ? '' : array();
			}
			$trip_id  = $trip_data['trip_id'];
			$all_meta = $trip_data['all_meta'];

			$meta = $all_meta[ MetaHelpers::get_prefix( $key ) ] ?? '';
			if ( ! $meta ) {
				$meta = $single ? '' : array();
			}
			$meta = self::json_to_data( $meta );
			return $meta;
		}

		/**
		 * Get trip Packages.
		 *
		 * @param int $trip_id Trip id of trip.
		 * @since 1.0.0
		 */
		public static function get_packages( $trip_id = null ) {

			$trip_packages = self::get_individual_meta( $trip_id, 'trip_packages', false );
			if ( is_array( $trip_packages ) && count( $trip_packages ) > 0 ) {
				foreach ( $trip_packages as $index => $trip_package ) {
					$categories = $trip_package['package_categories'] ?? array();
					foreach ( $categories as $cat_index => $category ) {
						$cat_id = $category['id'];
						if ( ! term_exists( absint( $cat_id ) ) ) {
							unset( $categories[ $cat_index ] );
						}
					}
					$trip_packages[ $index ]['package_categories'] = $categories;

				}
			}
			/**
			 * Filter to change trip packages as per your need.
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'tripzzy_filter_trip_packages', $trip_packages, $trip_id );
		}

		/**
		 * Get trip fixed dates.
		 *
		 * @param int $trip_id Trip id of trip.
		 * @since 1.0.0
		 */
		public static function get_fixed_dates( $trip_id = null ) {

			$fixed_dates = self::get_individual_meta( $trip_id, 'fixed_dates', false );
			/**
			 * Filter to change trip fixed_dates as per your need.
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'tripzzy_filter_fixed_dates', $fixed_dates, $trip_id );
		}

		/**
		 * Get trip recurring dates.
		 *
		 * @param int $trip_id Trip id of trip.
		 * @since 1.0.0
		 */
		public static function get_recurring_dates( $trip_id = null ) {
			$defalut         = self::default_data();
			$recurring_dates = self::get_individual_meta( $trip_id, 'recurring_dates', false );
			// Fixes for saved empty data in trip by just publish without entering any data. issue only exist multi dimentional data with default key value as array.
			if ( is_array( $recurring_dates ) && empty( $recurring_dates ) ) {
				$recurring_dates = $defalut['recurring_dates'];
			}
			/**
			 * Filter to change trip recurring_dates as per your need.
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'tripzzy_filter_recurring_dates', $recurring_dates, $trip_id );
		}

		/**
		 * Get trip infos.
		 *
		 * @param int $trip_id Trip id of trip.
		 * @since 1.0.0
		 */
		public static function get_trip_infos( $trip_id = null ) {

			$trip_infos = self::get_individual_meta( $trip_id, 'trip_infos', false );
			/**
			 * Filter to change trip infos as per your need.
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'tripzzy_filter_trip_infos', $trip_infos, $trip_id );
		}

		/**
		 * Get trip gallery.
		 *
		 * @param int $trip_id Trip id of trip.
		 * @since 1.0.0
		 */
		public static function get_gallery( $trip_id = null ) {
			return self::get_individual_meta( $trip_id, 'gallery', false );
		}

		/**
		 * Get Trip Overview.
		 *
		 * @param int $trip_id Trip id of trip.
		 * @since 1.0.0
		 */
		public static function get_overview( $trip_id = null ) {
			$trip_data = self::get_data( $trip_id );
			if ( ! $trip_data ) {
				return;
			}

			$trip_id = $trip_data['trip_id'];
			$value   = get_the_content( $trip_id );
			return $value;
		}

		/**
		 * Get trip difficulty.
		 *
		 * @param int $trip_id Trip id of trip.
		 * @since 1.0.0
		 */
		public static function get_difficulty( $trip_id = null ) {
			$defalut    = self::default_data();
			$difficulty = self::get_individual_meta( $trip_id, 'difficulty', false );
			$value      = $difficulty ? $difficulty : $defalut['difficulty'];
			return $value;
		}

		/**
		 * Get trip duration.
		 *
		 * @param int  $trip_id Trip id of trip.
		 * @param bool $return_duration_key Either return key or label.
		 * @since 1.0.0
		 * @since 1.1.8 Added $return_duration_key Param.
		 */
		public static function get_duration( $trip_id = null, $return_duration_key = true ) {
			$defalut       = self::default_data();
			$duration      = self::get_individual_meta( $trip_id, 'duration', false );
			$duration_unit = self::get_individual_meta( $trip_id, 'duration_unit', false );
			// Fixes for saved empty data in trip by just publish without entering any data. issue only exist multi dimentional data with default key value as array.
			if ( is_array( $duration ) && empty( $duration ) ) {
				$duration = $defalut['duration'];
			}
			// Fixes for saved empty data in trip by just publish without entering any data. issue only exist multi dimentional data with default key value as array.
			if ( is_array( $duration_unit ) && empty( $duration_unit ) ) {
				$duration_unit = $defalut['duration_unit'];
			}
			if ( ! $return_duration_key ) {
				$duration_labels = self::duration_labels();
				$duration_unit   = array_map(
					function ( $unit_key ) use ( $duration_labels ) {
						return $duration_labels[ $unit_key ] ?? $unit_key;
					},
					$duration_unit
				);
			}
			return array(
				'duration'      => $duration,
				'duration_unit' => $duration_unit,
			);
		}

		/**
		 * Labels for Trip Duration.
		 *
		 * @since 1.1.8
		 * @return array
		 */
		private static function duration_labels() {
			return array(
				'days'    => __( 'Days', 'tripzzy' ),
				'nights'  => __( 'Nights', 'tripzzy' ),
				'hours'   => __( 'Hours', 'tripzzy' ),
				'minutes' => __( 'Minutes', 'tripzzy' ),
			);
		}

		/**
		 * Get trip highlights.
		 *
		 * @param int $trip_id Trip id of trip.
		 * @since 1.0.0
		 */
		public static function get_highlights( $trip_id = null ) {
			$highlights = self::get_individual_meta( $trip_id, 'highlights', false );
			/**
			 * Filter to change trip highlights as per your need.
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'tripzzy_filter_highlights', $highlights, $trip_id );
		}

		/**
		 * Get trip FAQs.
		 *
		 * @param int $trip_id Trip id of trip.
		 * @since 1.0.0
		 */
		public static function get_faqs( $trip_id = null ) {
			$value = self::get_individual_meta( $trip_id, 'faqs', false );
			if ( ! $value ) {
				return array();
			}
			$settings       = Settings::get();
			$global_faqs    = $settings['faqs'];
			$global_faq_ids = array_keys( $global_faqs );

			$faqs = array();
			foreach ( $value as $k => $faq ) {
				if ( ! $faq['question'] ) {
					continue;
				}
				if ( $faq['isGlobal'] ) {
					$faq_id = $faq['faq_id'];
					if ( ! in_array( (int) $faq_id, $global_faq_ids, true ) ) {
						continue;
					}
					$faq['question'] = $global_faqs[ $faq_id ]['question'];
					$faq['answer']   = $global_faqs[ $faq_id ]['answer'];
				}
				$faqs[] = $faq;
			}
			return $faqs;
		}

		/**
		 * Get trip Itineraries.
		 *
		 * @param int $trip_id Trip id of trip.
		 * @since 1.0.0
		 */
		public static function get_itineraries( $trip_id = null ) {
			$itineraries = self::get_individual_meta( $trip_id, 'itineraries', false );
			if ( ! $itineraries ) {
				$itineraries = array();
			}
			/**
			 * Filter to change trip itineraries as per your need.
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'tripzzy_filter_itineraries', $itineraries, $trip_id );
		}

		/**
		 * Get trip Map data.
		 *
		 * @param int $trip_id Trip id of trip.
		 * @since 1.0.0
		 */
		public static function get_map( $trip_id = null ) {
			$trip_data = self::get_data( $trip_id );
			if ( ! $trip_data ) {
				return;
			}
			$trip_id   = $trip_data['trip_id'];
			$trip_meta = $trip_data['trip_meta'];
			$map       = array(
				'map_type'    => $trip_meta['map_type'] ?? 'iframe',
				'map_iframe'  => $trip_meta['map_iframe'] ?? '',
				'map_image'   => $trip_meta['map_image'] ?? array(),
				// Google map.
				'map_lat'     => $trip_meta['map_lat'] ?? '27.673602861598475',
				'map_lng'     => $trip_meta['map_lng'] ?? '85.3249204158783',
				'map_zoom'    => $trip_meta['map_zoom'] ?? 15,
				'map_markers' => $trip_meta['map_markers'] ?? array(
					array(
						'lat'  => '27.673602861598475',
						'lng'  => '85.3249204158783',
						'loc'  => 'Lalitpur 44600, Nepal',
						'desc' => '',
					),
				),
			);
			return $map;
		}

		/**
		 * Get trip destination.
		 *
		 * @param int $trip_id Trip id of trip.
		 * @since 1.0.0
		 */
		public static function get_destinations( $trip_id = null ) {
			$trip_data = self::get_data( $trip_id );
			if ( ! $trip_data ) {
				return;
			}
			$trip_id = $trip_data['trip_id'];
			return Taxonomy::get_trip_terms( $trip_id, 'trip_destination' );
		}

		/**
		 * Get trip type.
		 *
		 * @param int $trip_id Trip id of trip.
		 * @since 1.0.0
		 */
		public static function get_types( $trip_id = null ) {
			$trip_data = self::get_data( $trip_id );
			if ( ! $trip_data ) {
				return;
			}
			$trip_id = $trip_data['trip_id'];
			return Taxonomy::get_trip_terms( $trip_id, 'trip_type' );
		}

		/**
		 * Get trip extras.
		 *
		 * @param int $trip_id Trip id of trip.
		 * @since 1.2.9
		 */
		public static function get_extras( $trip_id = null ) {
			if ( ! $trip_id ) {
				$trip_id = self::$trip->ID;
			}
			$extras = apply_filters( 'tripzzy_filter_trip_extras', array(), $trip_id );
			if ( ! $extras ) {
				return;
			}
			return $extras;
		}
		/**
		 * Check whether current trip is in wishlists or not.
		 *
		 * @param number $trip_id Current trip id.
		 * @since 1.0.0
		 */
		public static function in_wishlists( $trip_id = null ) {
			$trip_data = self::get_data( $trip_id );
			if ( ! $trip_data ) {
				return;
			}
			$trip_id = $trip_data['trip_id'];

			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				return false;
			}

			$wishlists = Wishlists::get( $user_id );
			return in_array( $trip_id, $wishlists ); // @phpcs:ignore
		}

		/**
		 * Get Sticky Tab data.
		 *
		 * @param number $trip_id Current trip id.
		 * @since 1.0.2
		 */
		public static function get_sticky_tab_items( $trip_id = null ) {
			$trip_data = self::get_data( $trip_id );
			if ( ! $trip_data ) {
				return;
			}
			$trip_id  = $trip_data['trip_id'];
			$settings = Settings::get();
			return $settings['sticky_tab_items'] ?? array();
		}

		/**
		 * Gets Trip Id.
		 * Do not call this directly. need to call by using trip instance.
		 */
		public function get_id() {
			return self::$trip->ID;
		}

		/**
		 * Return if trip is featured.
		 *
		 * @return boolean
		 */
		public function is_featured() {
			return (bool) $this->get_meta( 'featured', false );
		}

		/**
		 * Return Price per string.
		 *
		 * @return string
		 */
		public function get_price_per() {
			$key = $this->price_per;

			$labels = array(
				'person' => __( 'Person', 'tripzzy' ),
				'group'  => __( 'Group', 'tripzzy' ),
			);

			return isset( $labels[ $key ] ) ? $labels[ $key ] : $labels['person'];
		}


		/**
		 * Gets Meta by key.
		 * Do not call this directly. need to call by using trip instance.
		 *
		 * @param string $key Meta key.
		 * @param string $default_value Default value.
		 */
		public function get_meta( $key, $default_value = '' ) {
			$trip_data = self::get_data( self::$trip->ID );

			if ( ! $trip_data ) {
				return $default_value;
			}
			// Check first in individual meta. Individual meta has prefix so need to prefix the key first.
			$prefixed_key = MetaHelpers::get_prefix( $key );
			if ( isset( $trip_data['all_meta'][ $prefixed_key ] ) ) {
				return self::json_to_data( $trip_data['all_meta'][ $prefixed_key ] ); // Note conversion only for ALL metas.
			}
			// Check in main meta which content all trip metas except individually saved.
			if ( isset( $trip_data['trip_meta'][ $key ] ) ) {
				return $trip_data['trip_meta'][ $key ];
			}
			return $default_value;
		}

		/**
		 * Gets Packages.
		 * Need trip class initialization before use it.
		 *
		 * @param int   $trip_id Optional Trip id.
		 * @param array $args Arguments for Trip Packages like date.
		 * @since 1.0.0
		 * @since 1.1.7 Added $args param.
		 */
		public function packages( $trip_id = null, $args = array() ) {
			if ( ! $trip_id ) {
				if ( self::$trip ) {
					$trip_id = self::$trip->ID;
				}
			}
			return new TripPackages( $trip_id, $args );
		}

		/**
		 * Get Package category as per args provided.
		 *
		 * @param mixed $args Category args.
		 * @since 1.0.0
		 * @return object
		 */
		public function package_category( $args = null ) {
			$category    = null;
			$trip_id     = $args;
			$package_id  = 0;
			$category_id = 0;
			if ( is_array( $args ) ) {
				$trip_id     = isset( $args['trip_id'] ) ? $args['trip_id'] : 0;
				$package_id  = isset( $args['package_id'] ) ? $args['package_id'] : 0;
				$category_id = isset( $args['category_id'] ) ? $args['category_id'] : 0;
			}
			$trip_data = self::get_data( $trip_id );
			$trip_id   = $trip_data['trip_id']; // final trip id.

			// Get All Packages.
			$packages = self::packages( $trip_id );
			if ( $packages->total() ) {
				$package = $packages->get_package( $package_id );
				if ( $package->total() ) {
					$category = $package->get_category( $category_id );
				}
			}
			return $category;
		}

		/**
		 * Gets Dates.
		 * Need trip class initialization before use it.
		 *
		 * @param int $trip_id Optional Trip id.
		 */
		public function dates( $trip_id = null ) {
			if ( ! $trip_id ) {
				$trip_id = self::$trip->ID;
			}
			return new TripDates( $trip_id );
		}

		/**
		 * Conditional Method to check whether seasonal pricing is enabled or not.
		 *
		 * @since 1.1.7
		 * @return boolean
		 */
		public function has_seasonal_pricing() {
			return apply_filters( 'tripzzy_filter_enable_seasonal_pricing', false, $this );
		}
	}
}
