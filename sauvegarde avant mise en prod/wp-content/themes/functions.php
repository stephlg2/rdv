<?php

// Enqueue styles du thème enfant
function theme_enqueue_styles() {
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', [] );
}
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles', 20 );

// Chargement des traductions du thème enfant
function avada_lang_setup() {
    $lang = get_stylesheet_directory() . '/languages';
    load_child_theme_textdomain( 'Avada', $lang );
}
add_action( 'after_setup_theme', 'avada_lang_setup' );

// Personnalisation des URLs des logos selon la page
function steph_custom_logo_url( $logo_url ) {
    if ( is_front_page() ) {
        return get_stylesheet_directory_uri() . '/images/rdv-asie-bmanc-homepage.png';
    }
    return get_stylesheet_directory_uri() . '/images/voyage-rendez-vous-avec-l-asie-logo.webp';
}

add_filter( 'avada_logo_url', 'steph_custom_logo_url', 1 );
add_filter( 'avada_logo_retina_url', 'steph_custom_logo_url', 1 );
add_filter( 'avada_logo_light_url', 'steph_custom_logo_url', 1 );
add_filter( 'avada_logo_dark_url', 'steph_custom_logo_url', 1 );
add_filter( 'avada_logo_sticky_url', 'steph_custom_logo_url', 1 );
add_filter( 'avada_logo_mobile_url', 'steph_custom_logo_url', 1 );

// Personnalisation du srcset des logos
function steph_custom_logo_srcset( $srcset ) {
    if ( is_front_page() ) {
        $logo_url = get_stylesheet_directory_uri() . '/images/rdv-asie-bmanc-homepage.png';
        return $logo_url . ' 1x';
    }
    $default_logo = get_stylesheet_directory_uri() . '/images/voyage-rendez-vous-avec-l-asie-logo.webp';
    return $default_logo . ' 1x';
}

add_filter( 'avada_logo_srcset', 'steph_custom_logo_srcset', 1 );
add_filter( 'avada_logo_retina_srcset', 'steph_custom_logo_srcset', 1 );

// Masquer le logo jusqu'à ce que le script remplace l'image (éviter flash)
function steph_hide_logo_until_ready() {
    echo '<style>.fusion-logo .fusion-standard-logo { visibility: hidden; }</style>';
}
add_action( 'wp_head', 'steph_hide_logo_until_ready' );

// Remplacement du logo avec JS selon la page
function steph_replace_logo_js() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var logo = document.querySelector('.fusion-logo .fusion-standard-logo');
        if (!logo) return;

        var homepageLogo = '<?php echo get_stylesheet_directory_uri(); ?>/images/rdv-asie-bmanc-homepage.png';
        var defaultLogo = '<?php echo get_stylesheet_directory_uri(); ?>/images/voyage-rendez-vous-avec-l-asie-logo.webp';

        var isHomepage = document.body.classList.contains('home');
        logo.src = isHomepage ? homepageLogo : defaultLogo;
        logo.srcset = isHomepage ? homepageLogo + ' 1x' : defaultLogo + ' 1x';

        logo.style.visibility = 'visible';
    });
    </script>
    <?php
}
add_action( 'wp_footer', 'steph_replace_logo_js' );

// Affiche le post type en haut de l'éditeur d'article
add_action( 'edit_form_after_title', function() {
    global $post;
    echo '<div style="padding:10px;background:#eee;margin-bottom:10px;">Post type : <strong>' . esc_html( $post->post_type ) . '</strong></div>';
});

// Désactive Gutenberg sur le post type tripzzy_trip
add_filter( 'use_block_editor_for_post_type', function( $use_block_editor, $post_type ) {
    if ( 'tripzzy_trip' === $post_type ) {
        return false;
    }
    return $use_block_editor;
}, 10, 2 );

// Retire le support editor (Gutenberg) au cas où
add_action( 'init', function() {
    remove_post_type_support( 'tripzzy_trip', 'editor' );
}, 20 );

// Active Avada Builder sur tripzzy_trip
add_filter( 'fusion_builder_post_types', function( $post_types ) {
    if ( ! in_array( 'tripzzy_trip', $post_types ) ) {
        $post_types[] = 'tripzzy_trip';
    }
    return $post_types;
});
add_action( 'init', function() {
    add_post_type_support( 'tripzzy_trip', 'fusion-builder' );
});

?>