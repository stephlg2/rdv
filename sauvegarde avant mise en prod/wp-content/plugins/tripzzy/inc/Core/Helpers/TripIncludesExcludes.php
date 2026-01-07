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

if ( ! class_exists( 'Tripzzy\Core\Helpers\TripIncludesExcludes' ) ) {

	/**
	 * Our main helper class that provides.
	 *
	 * @since 1.0.0
	 */
	class TripIncludesExcludes {
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
		 * Get Trip Includes data as per trip id for the frontend.
		 *
		 * @param int $trip_id Trip id.
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public static function get_includes( $trip_id ) {
			if ( ! $trip_id ) {
				return array();
			}

			$trip_data = self::get_data( $trip_id );

			if ( ! $trip_data ) {
				return;
			}
			$trip_id   = $trip_data['trip_id'];
			$trip_meta = $trip_data['trip_meta'];
			$value     = isset( $trip_meta['trip_includes'] ) ? $trip_meta['trip_includes'] : array();
			return $value;
		}

		/**
		 * Get Trip Excludes data as per trip id for the frontend.
		 *
		 * @param int $trip_id Trip id.
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public static function get_excludes( $trip_id ) {
			if ( ! $trip_id ) {
				return array();
			}

			$trip_data = self::get_data( $trip_id );

			if ( ! $trip_data ) {
				return;
			}
			$trip_id   = $trip_data['trip_id'];
			$trip_meta = $trip_data['trip_meta'];
			$value     = isset( $trip_meta['trip_excludes'] ) ? $trip_meta['trip_excludes'] : array();
			return $value;
		}

		/**
		 * Render Trip infos to display it in frontend.
		 *
		 * @param int     $trip_id Trip id.
		 * @param boolean $display Deiplay or return the markup.
		 * @return void
		 */
		public static function render( $trip_id = 0, $display = true ) {
			if ( ! $trip_id ) {

				global $post;
				if ( ! $post ) {
					return;
				}
				$trip_id = $post->ID;

			}
			$content  = '';
			$includes = self::get_includes( $trip_id ); // Saved In post meta.
			if ( is_array( $includes ) && count( $includes ) > 0 ) {
				$excludes       = self::get_excludes( $trip_id ); // Saved In post meta.
				$section_titles = Trip::get_section_titles( $trip_id );
				$section_title  = $section_titles['trip_includes'] ?? __( 'Trip Includes & Excludes', 'tripzzy' );
				ob_start();
				?>
				<div class="tripzzy-section" id="tripzzy-includes-excludes-section">
					<?php if ( ! empty( $section_title ) ) : ?>
					<h3 class="tripzzy-section-title"><?php echo esc_html( $section_title ); ?></h3>
					<?php endif; ?>
					<div class="tripzzy-section-inner tripzzy-includes-excludes">

						<div class="tripzzy-includes">
							<?php self::render_includes( $includes ); ?>
						</div>
						<div class="tripzzy-excludes">
							<?php self::render_excludes( $excludes ); ?>
						</div>
					</div>
				</div>
				<?php
				$content = ob_get_contents();
				ob_end_clean();
			}

			if ( ! $display ) {
				return $content;
			}
			echo wp_kses_post( $content );
		}

		/**
		 * Render Trip includes to display it in frontend.
		 *
		 * @param array   $includes Trip includes array list.
		 * @param boolean $is_children either current element is parent or child.
		 * @return void
		 */
		public static function render_includes( $includes, $is_children = false ) {
			if ( ! $includes ) {
				return;
			}

			if ( is_array( $includes ) && count( $includes ) > 0 ) {
				$list_class      = 'tripzzy-includes-list';
				$list_item_class = 'tripzzy-includes-category';
				if ( $is_children ) {
					$list_class      .= '-child';
					$list_item_class .= '-child';
				}
				?>
				<ul class="<?php echo esc_attr( $list_class ); ?>" >
					<?php
					foreach ( $includes as $include ) {
						$term_id = $include['term_id'];
						$term    = get_term( $term_id );
						$icon    = MetaHelpers::get_term_meta( $term_id, 'fa_class', true );

						$has_children = isset( $include['children'] ) && is_array( $include['children'] ) && count( $include['children'] ) > 0;
						$parent_class = ' has-no-child ';
						if ( $has_children ) {
							$parent_class = ' has-child ';
						}
						?>
						<li class="<?php echo esc_attr( $list_item_class ); ?> <?php echo esc_attr( $parent_class ); ?>" >
							<?php if ( ! $is_children ) : ?>
								<i class="fa <?php echo esc_attr( $icon ? $icon : ' fa-home' ); ?>" ></i>
							<?php endif; ?>
							<span><?php echo esc_html( $term->name ); ?></span>
							<?php
							if ( isset( $include['children'] ) && is_array( $include['children'] ) && count( $include['children'] ) > 0 ) {
								self::render_includes( $include['children'], true );
							}
							?>
						</li>
						<?php
					}
					?>
				</ul>
						<?php
			}
		}

		/**
		 * Render Trip excludes to display it in frontend.
		 *
		 * @param array   $excludes Trip excludes array list.
		 * @param boolean $is_children either current element is parent or child.
		 * @return void
		 */
		public static function render_excludes( $excludes, $is_children = false ) {
			if ( ! $excludes ) {
				return;
			}

			if ( is_array( $excludes ) && count( $excludes ) > 0 ) {
				$list_class      = 'tripzzy-excludes-list';
				$list_item_class = 'tripzzy-excludes-category';
				if ( $is_children ) {
					$list_class      .= '-child';
					$list_item_class .= '-child';
				}
				?>
				<ul class="<?php echo esc_attr( $list_class ); ?>" >
					<?php
					foreach ( $excludes as $exclude ) {
						$term_id = $exclude['term_id'];
						$term    = get_term( $term_id );
						$icon    = MetaHelpers::get_term_meta( $term_id, 'fa_class', true );

						if ( ! isset( $term->name ) ) {
							continue;
						}
						$has_children = isset( $exclude['children'] ) && is_array( $exclude['children'] ) && count( $exclude['children'] ) > 0;
						$parent_class = ' has-no-child ';
						if ( $has_children ) {
							$parent_class = ' has-child ';
						}
						?>
						<li class="<?php echo esc_attr( $list_item_class ); ?> <?php echo esc_attr( $parent_class ); ?>" >
							<?php if ( ! $is_children ) : ?>
								<i class="fa <?php echo esc_attr( $icon ? $icon : ' fa-home' ); ?>" ></i>
							<?php endif; ?>
							<span><?php echo esc_html( $term->name ); ?></span>
							<?php
							if ( $has_children ) {
								self::render_excludes( $exclude['children'], true );
							}
							?>
						</li>
						<?php
					}
					?>
				</ul>
				<?php
			}
		}
	}
}
