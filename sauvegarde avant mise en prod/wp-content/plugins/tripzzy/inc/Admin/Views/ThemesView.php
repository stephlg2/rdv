<?php
/**
 * Views: Tripzzy Themes.
 *
 * @package tripzzy
 */

namespace Tripzzy\Admin\Views;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Helpers\Loading;
use Tripzzy\Core\Helpers\Transient;

if ( ! class_exists( 'Tripzzy\Admin\Views\ThemesView' ) ) {
	/**
	 * ThemesView Class.
	 *
	 * @since 1.1.2
	 */
	class ThemesView {

		/**
		 * Themes page html.
		 *
		 * @since 1.1.2
		 */
		public static function render() {

			$addons_data = Transient::get( 'theme_infos' );
			if ( ! $addons_data ) {
				$response = wp_remote_get( 'https://wptripzzy.com/wp-json/wp/v2/tripzzy-themes' );
				if ( is_wp_error( $response ) ) {
					return;
				}
				if ( isset( $response['body'] ) ) {
					$addons_data = (array) json_decode( $response['body'], true );
				}
				Transient::set( 'theme_infos', $addons_data );
			}
			if ( count( $addons_data ) > 0 ) {
				$comming_soon_url = sprintf( '%sassets/images/coming-soon.png', esc_url( TRIPZZY_PLUGIN_DIR_URL ) );
				?>
				<style>
					#wpcontent{background:#fff}
					.tripzzy-themes-list{
						display:grid;
						grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
						grid-gap: 20px;
					}
					.tripzzy-themes-list li{
						box-shadow:0 0 23px rgba(10, 10, 10, 0.08);
						border:1px solid rgba( 10, 10, 10, 0.15 );
						background:#fff;
						display: flex;
						flex-direction: column;
						justify-content: space-between;
						margin:0;
					}
					.tripzzy-themes-list .tripzzy-themes-list-thumbnail{
						border-bottom:1px solid #dcdcdc;
					}
					.tripzzy-themes-list img{
						max-width:100%;
						box-sizing:border-box;
					}
					.tripzzy-themes-list-title{
						display:flex;
						padding: 0;
						align-items:center;
						justify-content:space-between;
						background:#f1f1f1;
					}
					.tripzzy-themes-list-title h3{
						margin: 0.64em 0;
						color:#3b3b3b;
						text-transform:uppercase;
						width:calc( 100% - 50px );
						box-sizing:border-box;
						padding:0 10px;
						text-align:center;
					}
					li.tripzzy-theme-comming-soon .tripzzy-themes-list-title h3{
						width: 100%;
					}
					.tripzzy-themes-list-title a {
						display: block;
						width: 40px;
						height: 40px;
						line-height: 40px;
						text-align: center;
						background:var( --tripzzy-primary-color );
						color:#fff;
					}
					.tripzzy-themes-list-title a i{
						font-size:20px;
					}
					.tripzzy-themes-list-title a:hover{
						background:var( --tripzzy-accent-color );
					}
				</style>
				<div class="wrap">
				<hr class="wp-header-end">
					<div class="tripzzy-page-wrapper">
						<div id="tripzzy-themes-page" class="tripzzy-page tripzzy-themes-page" >
							<ul class="tripzzy-themes-list">
								<?php
								foreach ( $addons_data as $theme_info ) :
									$title         = $theme_info['title']['rendered'] ?? '';
									$thumbnail_url = $comming_soon_url;

									if ( isset( $theme_info['featured_media'] ) && $theme_info['featured_media'] ) {
										$thumbnail_url = isset( $theme_info['featured_media_url'] ) && $theme_info['featured_media_url'] ? $theme_info['featured_media_url'] : $comming_soon_url;
									}
									?>
								<li>
									<div class="tripzzy-themes-list-thumbnail">
										<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" />
									</div>
									<div class="tripzzy-themes-list-title">
										<h3><?php echo esc_html( $title ); ?></h3>
										<span>
											<a href="<?php echo esc_url( $theme_info['theme_url'] ?? '' ); ?>" target="_blank"><i class="fa-solid fa-download"></i></a>
										</span>
									</div>
								</li>
								<?php endforeach; ?>
								<?php for ( $i = 1;$i <= 3; $i++ ) : ?>
									<li class="tripzzy-theme-comming-soon">
										<div class="tripzzy-themes-list-thumbnail">
											<img src="<?php echo esc_url( $comming_soon_url ); ?>" />
										</div>
										<div class="tripzzy-themes-list-title">
											<h3><?php esc_html_e( 'Coming Soon', 'tripzzy' ); ?></h3>
										</div>
									</li>
								<?php endfor; ?>
							</ul>
						</div>
					</div>
				</div>
				<?php
			}
		}
	}
}
