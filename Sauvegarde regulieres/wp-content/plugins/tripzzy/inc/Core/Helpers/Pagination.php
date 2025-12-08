<?php
/**
 * Pagination Class
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Helpers\Page;
use Tripzzy\Core\Ajax\TripAjax;
if ( ! class_exists( 'Tripzzy\Core\Helpers\Pagination' ) ) {
	/**
	 * Class For Pagination.
	 *
	 * @since 1.0.0
	 * @since 1.0.6 Added search result page condition check.
	 */
	class Pagination {
		/**
		 * Initialize pagination.
		 *
		 * @since 1.0.0
		 * @since 1.2.1 Added fallback argument 'query' to render callback.
		 * @since 1.2.2 Added is_trips and is_taxonomy args in default query args.
		 */
		public static function init() {
			if ( Page::is( 'search-result' ) && Nonce::verify() ) {
				$data  = Request::sanitize_data( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$query = TripAjax::get_trips_query( $data );
				return self::render(
					array(
						'pages' => $query->max_num_pages,
					)
				);
			}
			// $args is required to check for the sticky posts in query args.
			$args  = array(
				'is_trips'    => Page::is( 'trips' ),
				'is_taxonomy' => Page::is( 'taxonomy' ),
			);
			$query = TripAjax::get_trips_query( $args );
			return self::render( compact( 'query' ) );
		}

		/**
		 * Pagination for archive pages
		 *
		 * @param array $args Pagination args.
		 *
		 * @since 1.0.0
		 * @since 1.0.6 Pagination vars changed to array args.
		 * @since 1.2.2 Fixed broken link for current page.
		 * @since 1.2.8 Fixed Taxonomy page pagination has wrong page number value.
		 */
		public static function render( $args = array() ) {
			// Pagination vars.
			$range      = $args['range'] ?? 2;
			$pages      = $args['pages'] ?? '';
			$query      = $args['query'] ?? null;
			$hashlink   = $args['hashlink'] ?? '';
			$has_return = (bool) ( $args['has_return'] ?? false );

			$showitems = ( $range * 2 ) + 1;

			global $paged;
			if ( empty( $paged ) ) {
				$paged = 1; // @phpcs:ignore
			}

			if ( '' === $pages ) {
				if ( $query && ! Page::is( 'taxonomy' ) ) {
					$pages = $query->max_num_pages;
				} else {
					global $wp_query;
					$pages = $wp_query->max_num_pages;
					if ( ! $pages ) {
						$pages = 1;
					}
				}
			}
			$pagination = '';
			ob_start();
			if ( 1 !== (int) $pages ) {
				?>
				<nav class="tripzzy-pagination" id="tripzzy-pagination">
					<ul class="wp-page-numbers">
						<?php
						if ( $paged > 1 && $showitems < $pages ) {
							?>
							<li><a class="prev wp-page-numbers" href="<?php echo esc_url( get_pagenum_link( $paged - 1 ) . $hashlink ); ?>">&laquo; </a></li>'
							<?php
						}
						for ( $i = 1; $i <= $pages; $i++ ) {
							if ( 1 !== (int) $pages && ( ! ( $i >= $paged + $range + 1 || $i <= $paged - $range - 1 ) || $pages <= $showitems ) ) {
								if ( $paged === $i ) {
									?>
									<li><a class="wp-page-numbers current-page-item"><?php echo esc_html( $i ); ?></a></li>
									<?php
								} else {
									?>
									<li><a class="wp-page-numbers" href="<?php echo esc_url( get_pagenum_link( $i ) . $hashlink ); ?>"><?php echo esc_html( $i ); ?></a></li>
									<?php
								}
							}
						}
						if ( $paged < $pages && $showitems < $pages ) {
							?>
							<li><a class="next wp-page-numbers" href="<?php echo esc_url( get_pagenum_link( $paged + 1 ) . $hashlink ); ?>">&raquo; </a></li>
							<?php
						}
						?>
					</ul>
				</nav>
				<?php
			}
			$pagination = ob_get_clean();
			if ( $has_return ) {
				return $pagination;
			}
			echo wp_kses_post( $pagination );
		}
	}
}
