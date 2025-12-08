<?php

// Activer les shortcodes dans les menus de façon sécurisée
add_filter('wp_nav_menu_items', function($items) {
    // Éviter d'appliquer do_shortcode sur les data-attributes et href
    // On applique seulement sur le texte visible
    return preg_replace_callback('/>(.*?)</', function($matches) {
        return '>' . do_shortcode($matches[1]) . '<';
    }, $items);
}, 99);

// Enqueue styles du thème enfant
function theme_enqueue_styles() {
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', [] );
}
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles', 20 );

// Fix pour sprintf manquant dans Tripzzy admin
function fix_tripzzy_sprintf_issue() {
    if ( is_admin() && function_exists('get_current_screen') ) {
        $screen = get_current_screen();
        if ( $screen && $screen->post_type === 'tripzzy' ) {
            // S'assurer que wp-i18n est chargé avec sprintf
            wp_enqueue_script('wp-i18n');
            wp_add_inline_script('wp-i18n', '
                if (typeof window.wp === "undefined") { window.wp = {}; }
                if (typeof window.wp.i18n === "undefined") { window.wp.i18n = {}; }
                if (typeof window.wp.i18n.sprintf === "undefined") {
                    // Fallback sprintf simple
                    window.wp.i18n.sprintf = function(str) {
                        var args = Array.prototype.slice.call(arguments, 1);
                        return str.replace(/%[sd]/g, function() {
                            return args.shift();
                        });
                    };
                }
                // Exposer sprintf globalement pour les scripts qui en ont besoin
                if (typeof window.sprintf === "undefined") {
                    window.sprintf = window.wp.i18n.sprintf;
                }
            ', 'before');
        }
    }
}
add_action('admin_enqueue_scripts', 'fix_tripzzy_sprintf_issue', 5);

// Forcer l'éditeur Gutenberg pour les voyages Tripzzy
add_filter( 'get_edit_post_link', function( $link, $post_id, $context ) {
    if ( ! $post_id ) {
        return $link;
    }
    
    $post_type = get_post_type( $post_id );
    if ( 'tripzzy' === $post_type ) {
        // Ajouter le paramètre gutenberg-editor si ce n'est pas déjà présent
        if ( strpos( $link, 'gutenberg-editor' ) === false ) {
            $link = add_query_arg( 'gutenberg-editor', '', $link );
        }
    }
    
    return $link;
}, 10, 3 );

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

// Ajout d'une classe CSS personnalisée depuis ACF au body
add_filter( 'body_class', 'rdvasie_add_header_classique' );
function rdvasie_add_header_classique( $classes ) {
    $current_id = get_queried_object_id();

    $custom_class = get_field( 'header_classique', $current_id );
    $page_class = get_field( 'page_classique', $current_id );
    if ( $custom_class ) {
        $classes[] = sanitize_html_class( $custom_class );
    }
    if ( $page_class ) {
        $classes[] = sanitize_html_class( $page_class );
    }

    return $classes;
}

// Forcer page_classique pour les pages de recherche, blog et archives
add_filter( 'body_class', 'rdvasie_add_page_classique_to_body' );
function rdvasie_add_page_classique_to_body( $classes ) {
    // Ajouter page_classique pour les pages de recherche, blog et archives
    if ( is_search() || is_home() || is_archive() ) {
        $classes[] = 'page_classique';
        
        // Supprimer header_classique si présent
        $classes = array_diff( $classes, array( 'header_classique' ) );
    }
    
    return $classes;
}

// Shortcode pour FAQ par slug
function faq_by_slug_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'slug' => ''
    ), $atts, 'faq_slug' );

    if ( empty( $atts['slug'] ) ) return '';

    $faq_post = get_page_by_path( $atts['slug'], OBJECT, 'faq' );
    if ( ! $faq_post ) return '';

    return do_shortcode('[faq id="' . intval($faq_post->ID) . '"]');
}
add_shortcode('faq_slug', 'faq_by_slug_shortcode');

// Forcer le chargement et l’initialisation des scripts FAQ Avada
add_action('wp_footer', function() {
    global $post;
    if ( isset($post->post_content) && has_shortcode( $post->post_content, 'fusion_faq' ) ) {
        if ( function_exists('fusion_load_faq_scripts') ) {
            fusion_load_faq_scripts();
        }
        if ( function_exists('fusion_faq_shortcode_render') ) {
            fusion_faq_shortcode_render();
        }
    }
}, 999);

// -----------------------------------------------------------------
// Tripzzy : bloquer mises à jour et personnaliser affichage
// -----------------------------------------------------------------

// Bloquer la détection des mises à jour
function disable_tripzzy_updates( $value ) {
    if ( isset( $value->response['tripzzy/tripzzy.php'] ) ) {
        unset( $value->response['tripzzy/tripzzy.php'] );
    }
    return $value;
}
add_filter( 'site_transient_update_plugins', 'disable_tripzzy_updates' );

// Bloquer les mises à jour automatiques
add_filter( 'auto_update_plugin', function( $update, $item ) {
    if ( $item->slug === 'tripzzy' ) return false;
    return $update;
}, 10, 2 );

// Modifier le nom, description et auteur dans la liste des plugins
add_filter( 'all_plugins', function( $plugins ) {
    if ( isset( $plugins['tripzzy/tripzzy.php'] ) ) {
        $plugins['tripzzy/tripzzy.php']['Name']        = 'Tripzzy – Version Personnalisée';
        $plugins['tripzzy/tripzzy.php']['Description'] = 'Tripzzy – Plugin personnalisé pour gérer les réservations de RDV Asie.';
        $plugins['tripzzy/tripzzy.php']['Version']     = '1.0.0';
        $plugins['tripzzy/tripzzy.php']['Author']      = 'Steph';
    }
    return $plugins;
});

// Forcer le format français pour l'affichage des montants (€ après le chiffre)
add_filter('tripzzy_filter_settings', function($settings) {
    $settings['amount_display_format'] = '%DISPLAY_AMOUNT% %CURRENCY_SYMBOL%';
    return $settings;
}, 99);