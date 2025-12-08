<?php
/**
 * Archive trip page template.
 *
 * @package tripzzy
 * @since   1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Helpers\TripFilter;

$tripzzy_view_mode = TripFilter::get_view_mode();
$images_url        = sprintf( '%sassets/images', esc_url( TRIPZZY_PLUGIN_DIR_URL ) );
get_header(); ?>
<?php do_action( 'tripzzy_before_main_content' ); ?>
<section class="tripzzy-section">
	<div class="tripzzy-container"><!-- Main Wrapper element for Tripzzy -->
		<div class="tz-row">
			<div class="tz-col tz-cols-3-lg">
				<?php do_action( 'tripzzy_archive_before_content' ); ?>
			</div>
			<div class="tz-col tz-cols-9-lg">
				<?php tripzzy_render_archive_toolbar(); ?>
				<div class="tripzzy-trips <?php echo esc_attr( $tripzzy_view_mode ); ?>-view">
					<?php do_action( 'tripzzy_archive_before_listing' ); ?>
					<!-- ID: tripzzy-trip-listings is required -->
					<div id="tripzzy-trip-listings" class="tz-row tripzzy-trip-listings">
						<?php
						if ( have_posts() ) :
							while ( have_posts() ) :
								the_post();
								// Render the trip item template
								Tripzzy\Core\Template::get_template_part( 'content', 'archive-tripzzy' );
							endwhile;
						else :
							// No trips found message
							?>
							<div class="tz-col tz-cols-12-lg">
								<p><?php esc_html_e( 'No trips found.', 'tripzzy' ); ?></p>
							</div>
							<?php
						endif;
						?>
					</div>
					<?php do_action( 'tripzzy_archive_after_listing' ); ?>
				</div><!-- /tripzzy-trips -->
				
				<?php
				// Affichage du contenu ACF "Contenu après liste"
				if ( is_tax() ) {
					$term = get_queried_object();
					if ( $term && function_exists('get_field') ) {
						$contenu_apres_liste = get_field('contenu_apres_liste', $term);
						if ( $contenu_apres_liste ) {
							echo '<div class="contenu-apres-liste">' . wp_kses_post( $contenu_apres_liste ) . '</div>';
						}
						
						// Affichage des FAQs
						$faq_terms = get_field('faq', $term);
						if ( $faq_terms ) {
							$args = array(
								'post_type' => 'avada_faq',
								'posts_per_page' => -1,
								'tax_query' => array(
									array(
										'taxonomy' => 'faq_category',
										'field'    => 'term_id',
										'terms'    => wp_list_pluck( $faq_terms, 'term_id' ),
									),
								),
							);
							
							$faq_query = new WP_Query( $args );
							
							if ( $faq_query->have_posts() ) {
								echo '<div class="faq-section"><h2>FAQ</h2>';
								echo '<div class="fusion-accordian">';
								
								while ( $faq_query->have_posts() ) {
									$faq_query->the_post();
									$answer = get_the_content();
									?>
									<div class="fusion-panel panel-default">
										<div class="panel-heading">
											<h4 class="panel-title toggle">
												<a href="#" data-toggle="collapse"><?php the_title(); ?></a>
											</h4>
										</div>
										<div class="panel-collapse collapse">
											<div class="panel-body toggle-content">
												<?php echo wp_kses_post( $answer ); ?>
											</div>
										</div>
									</div>
									<?php
								}
								
								echo '</div></div>';
								
								// Initialiser les accordéons Avada
								?>
								<script type="text/javascript">
								jQuery(document).ready(function($) {
									$('.faq-section .fusion-accordian .panel-title a').off('click').on('click', function(e) {
										e.preventDefault();
										var $panel = $(this).closest('.fusion-panel');
										var $collapse = $panel.find('.panel-collapse');
										
										// Toggle current panel
										$collapse.slideToggle(300);
										$panel.toggleClass('fusion-toggle-no-divider');
										$(this).toggleClass('active');
										
										// Close other panels
										$panel.siblings('.fusion-panel').find('.panel-collapse').slideUp(300);
										$panel.siblings('.fusion-panel').removeClass('fusion-toggle-no-divider');
										$panel.siblings('.fusion-panel').find('.panel-title a').removeClass('active');
									});
								});
								</script>
								<?php
								
								wp_reset_postdata();
							}
						}
					}
				}
				?>
			</div>
			<?php do_action( 'tripzzy_archive_after_content' ); ?>
		</div>
	</div>
	
</section>

<?php
tripzzy_render_archive_list_item_template();
do_action( 'tripzzy_after_main_content' );
get_footer();
