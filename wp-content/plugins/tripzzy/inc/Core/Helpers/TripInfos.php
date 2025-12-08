<?php
/**
 * Trips.
 *
 * @package tripzzy
 * @since 1.0.0
 * @since 1.2.8 Localize N/A strings.
 */

namespace Tripzzy\Core\Helpers;

use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Traits\TripTrait;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\Wishlists;
use Tripzzy\Core\Helpers\Taxonomy;
use Tripzzy\Core\Helpers\Price;
use Tripzzy\Core\Bases\TaxonomyBase;
use Tripzzy\Core\Helpers\FilterPlus;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\Icon;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\TripInfos' ) ) {

	/**
	 * Our main helper class that provides.
	 *
	 * @since 1.0.0
	 */
	class TripInfos {
		use TripTrait;

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
			self::$all_meta  = get_post_meta( self::$trip->ID );
			self::$trip_meta = MetaHelpers::get_post_meta( self::$trip->ID, 'trip' );
		}

		/**
		 * Get Trip Infos data as per trip id for the frontend.
		 *
		 * @param int   $trip_id Trip id.
		 * @param array $infos List of infos without icon data.
		 *
		 * @since 1.0.0
		 * @since 1.2.2 Added param $infos.
		 *
		 * @return array
		 */
		public static function get( $trip_id, $infos = array() ) {
			if ( ! $trip_id ) {
				return array();
			}
			if ( ! $infos || ! is_array( $infos ) ) {
				$infos = Trip::get_trip_infos( $trip_id ); // Just getting tripzzy_trip_info meta value.
			}
			if ( ! $infos ) {
				return array();
			}
			$enabled_infos     = self::get_enabled_list();
			$enabled_info_keys = array_column( $enabled_infos, 'key' );

			$infos = array_filter(
				$infos,
				function ( $info ) use ( &$enabled_info_keys ) {
					return in_array( $info['key'], $enabled_info_keys, true );
				},
				ARRAY_FILTER_USE_BOTH
			);

			foreach ( $infos as $k => $info ) {

				$selected_info_settings = array(); // Info data from settings. use to fetch icon and dropdown options.
				$selected_info_data     = array_filter(
					$enabled_infos,
					function ( $value ) use ( &$info ) {
						return $value['key'] === $info['key'];
					},
					ARRAY_FILTER_USE_BOTH
				);

				if ( is_array( $selected_info_data ) && count( $selected_info_data ) > 0 ) {
					$selected_info_data     = array_values( $selected_info_data );
					$selected_info_settings = $selected_info_data[0];
					$infos[ $k ]['icon']    = $selected_info_settings['icon'];

					if ( 'dropdown' === $info['type'] ) {
						$options = array();
						if ( isset( $selected_info_settings['options'] ) && is_array( $selected_info_settings['options'] ) ) {
							$options = array_map(
								function ( $option ) {
									$data  = explode( ':', $option );
									$label = count( $data ) >= 2 ? $data[1] : $data[0];
									$value = $data[0];
									return array(
										'label' => $label,
										'value' => $value,
									);
								},
								$selected_info_settings['options']
							);
						}

						$infos[ $k ]['options'] = $options ? $options : array();
					}
				}
			}

			return array_values( $infos );
		}

		/**
		 * Render Trip infos to display it in frontend.
		 *
		 * @param int     $trip_id Trip id.
		 * @param boolean $has_return Render or return the markup.
		 * @param array   $infos List of infos without icon data.
		 * @param boolean $hide_title Whether title need to hide or not.
		 * @since 1.0.0
		 * @since 1.0.9 Display N/A Value for post meta, text, number, textarea field.
		 * @since 1.2.2 Added param $infos, and $hide_title.
		 * @since 1.2.5 Check for the info is main info ($is_main_info) or not.
		 * @return void
		 */
		public static function render( $trip_id = 0, $has_return = false, $infos = array(), $hide_title = false ) {
			if ( ! $trip_id ) {

				global $post;
				if ( ! $post ) {
					return;
				}
				$trip_id = $post->ID;

			}
			$is_main_info = ! ( count( $infos ) > 0 );

			$infos         = self::get( $trip_id, $infos ); // Saved In post meta.
			$trip_data     = self::get_data( $trip_id );
			$trip_metas    = $trip_data['trip_meta']; // for post meta input type.
			$enabled_infos = self::get_enabled_list();
			$content       = '';
			$labels        = Strings::get()['labels'];

			if ( is_array( $infos ) && count( $infos ) > 0 ) {
				$section_titles = Trip::get_section_titles( $trip_id );
				$section_title  = $section_titles['trip_infos'] ?? __( 'Trip Infos', 'tripzzy' );
				ob_start();
				?>
				<div class="tripzzy-section" id="<?php echo esc_attr( $is_main_info ? 'tripzzy-trip-infos-section' : '' ); ?>">
					<?php if ( ! empty( $section_title ) && ! $hide_title ) : ?>
					<h3 class="tripzzy-section-title"><?php echo esc_html( $section_title ); ?></h3>
					<?php endif; ?>
					<div class="tripzzy-section-inner tripzzy-trip-infos">
						<ul>
						<?php
						foreach ( $infos as $info ) {
							$type = $info['type'];
							?>
							<li>
								<span class="info-title" title="<?php echo esc_html( $info['name'] ); ?>">
									<?php Icon::get( $info['icon'] ); ?>		
									<span><?php echo esc_html( $info['name'] ); ?></span>
								</span>
								<span class="info-values">
									<?php
									switch ( $type ) {
										case 'taxonomy':
											$terms = Taxonomy::get_trip_terms( $trip_id, $info['taxonomy'] );
											if ( is_array( $terms ) && count( $terms ) > 0 ) :
												foreach ( $terms as $term ) :
													?>
												<span><a href="<?php echo esc_url( get_term_link( $term ) ); ?>"><?php echo esc_html( $term->name ); ?></a> </span>
													<?php
												endforeach;
											endif;
											break;
										case 'dropdown':
											$selected_info_labels = array();

											if ( isset( $info['options'] ) && is_array( $info['options'] ) && count( $info['options'] ) > 0 ) {
												$options = $info['options'];

												if ( $info['value'] ) {
													$selected_info_labels = array_map(
														function ( $val ) use ( &$options ) {
															$data = array_filter(
																$options,
																function ( $v ) use ( &$val ) {
																	if ( $v['value'] === $val ) {
																		return $v;
																	}
																},
																ARRAY_FILTER_USE_BOTH
															);
															if ( $data ) {
																$new_data = array_values( $data );
																return $new_data[0]['label'];
															}
														},
														$info['value']
													);
												}
											}
											if ( count( $selected_info_labels ) > 0 ) :
												foreach ( $selected_info_labels as $label ) :
													if ( ! $label ) {
														continue;}
													?>
													<span><?php echo esc_html( $label ); ?></span>
													<?php
												endforeach;
											endif;
											break;
										case 'postmeta':
											if ( isset( $trip_metas[ $info['key'] ] ) && $trip_metas[ $info['key'] ] ) {
												echo esc_html( $trip_metas[ $info['key'] ] );
											} else {
												echo esc_html( $labels['na'] );
											}
											break;
										case 'text':
										case 'number':
											echo $info['value'] ? esc_html( $info['value'] ) : esc_html( $labels['na'] );
											break;
										case 'textarea':
											echo $info['value'] ? wp_kses_post( $info['value'] ) : esc_html( $labels['na'] );
											break;
									}
									?>
								</span>
							</li>
							<?php
						}
						?>
						</ul>
						<?php
						// Insert ACF hebergement block just after </ul> and before closing .tripzzy-section-inner
						$acf_hebergement = get_field( 'hebergement', $trip_id );
						if ( ! empty( $acf_hebergement ) ) :
							?>
							<div class="tripzzy-acf-hebergement">
								<h4><?php esc_html_e( 'Hébergement', 'tripzzy' ); ?></h4>
								<div><?php echo wp_kses_post( wpautop( $acf_hebergement ) ); ?></div>
							</div>
						<?php endif; ?>
					</div>
				</div>
				<?php
				$content = ob_get_contents();
				ob_end_clean();
			}
			if ( $has_return ) {
				return $content;
			}
			echo wp_kses_post( $content );
		}

		/**
		 * Get enabled Trip Info data for the Trip edit page.
		 *
		 * @since 1.0.0
		 */
		public static function get_enabled_list() {
			$all_list = self::get_all_list();

			$active_lists = array();
			foreach ( $all_list as $list ) {
				if ( ! $list['enabled'] ) {
					continue;
				}
				$active_lists[] = $list;
			}
			return $active_lists;
		}

		/**
		 * Get All Trip Info data for the settings only.
		 * Note : Just for listing the available fact in settings.
		 *
		 * @since 1.0.0
		 */
		public static function get_all_list() {
			$default  = self::default_data(); // Default Values.
			$settings = MetaHelpers::get_option( 'settings' );
			$infos    = isset( $settings['trip_infos'] ) ? $settings['trip_infos'] : array();
			$infos    = array_merge( $default, $infos );
			return $infos;
		}


		/**
		 * Get All Default Trip Value.
		 *
		 * @since 1.0.0
		 */
		public static function default_data() {

			$taxonomies          = TaxonomyBase::get_args();
			$excluded_taxonomies = self::excluded_taxonomies();
			$custom_taxonomies   = FilterPlus::get();
			$metas               = Trip::infos_meta();
			foreach ( $excluded_taxonomies as $excluded_taxonomy ) {
				unset( $taxonomies[ $excluded_taxonomy ] );
			}
			$info_data = array(
				'accomodation'     => array(
					'type'          => 'text',
					'name'          => __( 'Accomodation', 'tripzzy' ),
					'key'           => 'accomodation', // To use as key.
					'icon'          => array(
						'icon'      => 'fa-solid fa-hotel',
						'icon_type' => 'fa-icon',
					),
					'enabled'       => true,
					'default_field' => false, // Prevent from deleting.
					'default_value' => '',
				),
				'meals'            => array(
					'type'          => 'text',
					'name'          => __( 'Meals', 'tripzzy' ),
					'key'           => 'meals', // To use as key.
					'icon'          => array(
						'icon'      => 'fa-solid fa-utensils',
						'icon_type' => 'fa-icon',
					),
					'enabled'       => true,
					'default_field' => false, // Prevent from deleting.
					'default_value' => '',
				),
				'transportation'   => array(
					'type'          => 'text',
					'name'          => __( 'Transportation', 'tripzzy' ),
					'key'           => 'transportation', // To use as key.
					'icon'          => array(
						'icon'      => 'fa-solid fa-bus',
						'icon_type' => 'fa-icon',
					),
					'enabled'       => true,
					'default_field' => false, // Prevent from deleting.
					'default_value' => '',
				),
				'departure_from'   => array(
					'type'          => 'text',
					'name'          => __( 'Departure from', 'tripzzy' ),
					'key'           => 'departure_from', // To use as key.
					'icon'          => array(
						'icon'      => 'fa-solid fa-plane-departure',
						'icon_type' => 'fa-icon',
					),
					'enabled'       => true,
					'default_field' => false, // Prevent from deleting.
					'default_value' => '',
				),
				'guiding_method'   => array(
					'type'          => 'text',
					'name'          => __( 'Guiding method', 'tripzzy' ),
					'key'           => 'guiding_method', // To use as key.
					'icon'          => array(
						'icon'      => 'fa-solid fa-book-atlas',
						'icon_type' => 'fa-icon',
					),
					'enabled'       => true,
					'default_field' => false, // Prevent from deleting.
					'default_value' => '',
				),
				'best_season'      => array(
					'type'          => 'text',
					'name'          => __( 'Best season', 'tripzzy' ),
					'key'           => 'best_season', // To use as key.
					'icon'          => array(
						'icon'      => 'fa-solid fa-tree',
						'icon_type' => 'fa-icon',
					),
					'enabled'       => true,
					'default_field' => false, // Prevent from deleting.
					'default_value' => '',
				),
				'language'         => array(
					'type'          => 'text',
					'name'          => __( 'Language', 'tripzzy' ),
					'key'           => 'language', // To use as key.
					'icon'          => array(
						'icon'      => 'fa-solid fa-language',
						'icon_type' => 'fa-icon',
					),
					'enabled'       => true,
					'default_field' => false, // Prevent from deleting.
					'default_value' => '',
				),
				'age_requirements' => array( // @since 1.0.2
					'type'          => 'text',
					'name'          => __( 'Age Requirements', 'tripzzy' ),
					'key'           => 'age_requirements', // To use as key.
					'icon'          => array(
						'icon'      => 'fa-solid fa-language',
						'icon_type' => 'fa-icon',
					),
					'enabled'       => true,
					'default_field' => false, // Prevent from deleting.
					'default_value' => '',
				),
			);
			// Metas.
			if ( is_array( $metas ) && count( $metas ) > 0 ) {
				foreach ( $metas as $meta_key ) {

					$icon                   = array(
						'icon'      => 'fa-solid fa-circle-info',
						'icon_type' => 'fa-icon',
					);
					$info_data[ $meta_key ] = array(
						'type'          => 'postmeta',
						'name'          => ucfirst( str_replace( '_', ' ', $meta_key ) ),
						'key'           => $meta_key, // To use as key.
						'icon'          => $icon,
						'enabled'       => true,
						'default_field' => true, // Prevent from deleting.
						'default_value' => '',
					);
				}
			}

			// Taxonomy.
			if ( is_array( $taxonomies ) && count( $taxonomies ) > 0 ) {
				foreach ( $taxonomies as $key => $taxonomy ) {
					$icon = array();
					if ( isset( $taxonomy['icon'] ) && $taxonomy['icon'] ) {
						$icon = array(
							'icon'      => $taxonomy['icon'],
							'icon_type' => 'fa-icon',
						);
					}
					$info_data[ $key ] = array(
						'type'          => 'taxonomy',
						'name'          => $taxonomy['labels']['name'],
						'taxonomy'      => $key, // to use as taxonomy [only exists in taxonomy].
						'key'           => $key, // To use as key.
						'icon'          => $icon,
						'enabled'       => true,
						'default_field' => true, // Prevent from deleting.
						'default_value' => '',
					);
				}
			}

				// Custom Taxonomy.
			if ( is_array( $custom_taxonomies ) && count( $custom_taxonomies ) > 0 ) {
				foreach ( $custom_taxonomies as $key => $taxonomy ) {
					$icon              = array(
						'icon'      => 'fa-solid fa-hashtag',
						'icon_type' => 'fa-icon',
					);
					$info_data[ $key ] = array(
						'type'          => 'taxonomy',
						'name'          => $taxonomy['label'],
						'taxonomy'      => $key, // to use as taxonomy [only exists in taxonomy].
						'key'           => $key, // To use as key.
						'icon'          => $icon,
						'enabled'       => true,
						'default_field' => true, // Prevent from deleting.
						'default_value' => '',
					);
				}
			}

			/**
			 * Default Trip Data. Used to store trip meta.
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'tripzzy_filter_default_trip_infos', $info_data );
		}

		/**
		 * List of excluded taxonomies in infos.
		 *
		 * @since 1.0.4
		 * @return array
		 */
		public static function excluded_taxonomies() {
			return array( 'tripzzy_price_category', 'tripzzy_trip_includes', 'tripzzy_trip_excludes' );
		}
	}
}
