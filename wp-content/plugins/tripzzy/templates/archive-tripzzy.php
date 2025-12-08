<?php
/**
 * Archive trip page template.
 *
 * @package tripzzy
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Helpers\TripFilter;

$tripzzy_view_mode = TripFilter::get_view_mode();
$images_url        = sprintf( '%sassets/images', esc_url( TRIPZZY_PLUGIN_DIR_URL ) );
get_header(); ?>

<?php do_action( 'tripzzy_before_main_content' ); ?>

<section class="tripzzy-section">
    <div class="tripzzy-container">
        <div class="tz-row">
            <div class="tz-col tz-cols-3-lg">
                <?php do_action( 'tripzzy_archive_before_content' ); ?>
            </div>
            <div class="tz-col tz-cols-9-lg">
                <?php tripzzy_render_archive_toolbar(); ?>
                <div class="tripzzy-trips <?php echo esc_attr( $tripzzy_view_mode ); ?>-view">
                    <?php do_action( 'tripzzy_archive_before_listing' ); ?>
                    <div id="tripzzy-trip-listings" class="tz-row tripzzy-trip-listings"></div>
                    <?php do_action( 'tripzzy_archive_after_listing' ); ?>
                </div>
            </div>
            <?php do_action( 'tripzzy_archive_after_content' ); ?>
        </div>
    </div>
</section>

<div class="container-fluid texte-bas-acf">
 <div class="row">
    <div class="container">
    <div class="faq-liste">

        <?php 
        // Affichage du texte libre
        $contenu_apres_liste = get_field('contenu_apres_liste', 'term_' . get_queried_object_id());
        if ( !empty($contenu_apres_liste) ) {
            echo do_shortcode( $contenu_apres_liste );
        }

        // Récupération des termes FAQ
        $faq_terms = get_field('faq', 'term_' . get_queried_object_id());

        if ( !empty($faq_terms) ) :
            $term_ids = wp_list_pluck($faq_terms, 'term_id');

            $faq_query = new WP_Query(array(
                'post_type'      => 'avada_faq',
                'posts_per_page' => -1,
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'faq_category',
                        'field'    => 'term_id',
                        'terms'    => $term_ids
                    )
                )
            ));

            if ( $faq_query->have_posts() ) :
        ?>

        <!-- Accordéon simplifié style natif -->
        <div class="faq-accordion">
            <?php while ( $faq_query->have_posts() ) : $faq_query->the_post(); ?>
                <button class="accordion"><?php the_title(); ?></button>
                <div class="panel">
                    <?php the_content(); ?>
                </div>
            <?php endwhile; ?>
        </div>

        <style>
        /* Accordéon natif */
    .accordion {
    background-color: #fff;
    color: #444;
    cursor: pointer;
    padding: 18px;
    width: 100%;
    border: none;
    text-align: left;
    outline: none;
    font-size: 16px;
    transition: 0.4s;
    line-height: 30px;
    margin-bottom: 2px;
    font-weight: bold;
}

        .active, .accordion:hover {
            background-color: #efefef;
        }

        .accordion:after {
    content: '\002B';
    color: #777;
    font-weight: normal;
    float: right;
    font-size: 25px;
    border: 1px solid;
    width: 30px;
    height: 30px;
    text-align: center;
    line-height: 30px;
    border-radius: 50%;
}

        .active:after {
            content: "\2212";
        }

        .panel {
            padding: 0 18px;
            background-color: white;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.2s ease-out;
        }
        </style>

        <script>
        var acc = document.getElementsByClassName("accordion");
        for (var i = 0; i < acc.length; i++) {
            acc[i].addEventListener("click", function() {
                this.classList.toggle("active");
                var panel = this.nextElementSibling;
                if (panel.style.maxHeight) {
                    panel.style.maxHeight = null;
                } else {
                    panel.style.maxHeight = panel.scrollHeight + "px";
                }
            });
        }
        </script>

        <?php 
            endif;
            wp_reset_postdata();
        endif; 
        ?>
</div>
</div>
    </div>
</div>

<?php 
tripzzy_render_archive_list_item_template();
do_action( 'tripzzy_after_main_content' );
get_footer();
?>