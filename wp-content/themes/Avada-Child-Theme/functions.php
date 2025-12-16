<?php

// Enqueue styles du thème enfant
function theme_enqueue_styles() {
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', [] );
    wp_enqueue_script( 'sticky-toolbar', get_stylesheet_directory_uri() . '/sticky-toolbar.js', array(), '1.0.0', true );
    
    // WebP Loader - Remplace automatiquement les images par leur version WebP
    wp_enqueue_script( 'webp-loader', get_stylesheet_directory_uri() . '/webp-loader.js', array(), '1.0.0', true );
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
// Tripzzy : personnalisation du format de prix
// -----------------------------------------------------------------

// Modifier le format de prix : "2650.00 €" au lieu de "€2,650.00"
add_filter( 'tripzzy_filter_settings', 'rdvasie_tripzzy_price_format' );
function rdvasie_tripzzy_price_format( $settings ) {
    // Format : montant suivi du symbole (ex: 2650.00 €)
    $settings['amount_display_format'] = '%DISPLAY_AMOUNT% %CURRENCY_SYMBOL%';
    // Pas de séparateur de milliers
    $settings['thousand_separator'] = '';
    return $settings;
}

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

// Ajouter automatiquement le paramètre gutenberg-editor aux liens d'édition des voyages
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

// Charger le fichier de configuration ACF pour les guides des voyages
require_once get_stylesheet_directory() . '/acf-voyages-guides.php';

// -----------------------------------------------------------------
// Synchroniser les demandes Tripzzy vers Devis Pro
// -----------------------------------------------------------------
add_action( 'tripzzy_after_enquiry', 'sync_tripzzy_enquiry_to_devis_pro', 20, 2 );
function sync_tripzzy_enquiry_to_devis_pro( $enquiry_id, $data ) {
    // Vérifier que Devis Pro est actif
    if ( ! class_exists( 'Devis_Pro' ) ) {
        return;
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'devis_pro';
    
    // Vérifier que la table existe
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
        return;
    }
    
    // Extraire le nom et prénom du full_name
    $full_name = isset( $data['full_name'] ) ? $data['full_name'] : '';
    $name_parts = explode( ' ', $full_name, 2 );
    $prenom = isset( $name_parts[0] ) ? $name_parts[0] : '';
    $nom = isset( $name_parts[1] ) ? $name_parts[1] : '';
    
    // Récupérer l'ID du voyage depuis les métadonnées
    $trip_id = get_post_meta( $enquiry_id, 'tripzzy_trip_id', true );
    
    // Préparer les données pour Devis Pro
    $devis_data = array(
        'voyage'      => $trip_id ? $trip_id : '',
        'destination' => '',
        'depart'      => isset( $data['trip_date'] ) ? $data['trip_date'] : '',
        'retour'      => '',
        'duree'       => '',
        'budget'      => 0,
        'adulte'      => isset( $data['no_of_adults'] ) ? intval( $data['no_of_adults'] ) : 1,
        'enfant'      => isset( $data['no_of_children'] ) ? intval( $data['no_of_children'] ) : 0,
        'bebe'        => 0,
        'vol'         => '',
        'message'     => isset( $data['message'] ) ? $data['message'] : '',
        'civ'         => '',
        'nom'         => $nom,
        'prenom'      => $prenom,
        'email'       => isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '',
        'cp'          => isset( $data['cp'] ) ? sanitize_text_field( $data['cp'] ) : '',
        'ville'       => isset( $data['ville'] ) ? sanitize_text_field( $data['ville'] ) : '',
        'tel'         => isset( $data['phone'] ) ? $data['phone'] : '',
        'status'      => 0,
        'montant'     => 0,
        'demande'     => current_time( 'mysql' ),
        'langue'      => 'fr',
        'token'       => '',
        'mac'         => ''
    );
    
    // Insérer dans Devis Pro
    $wpdb->insert( $table, $devis_data );
    $devis_id = $wpdb->insert_id;
    
    // Ajouter à l'historique si la table existe
    $history_table = $wpdb->prefix . 'devis_pro_history';
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$history_table'" ) === $history_table ) {
        $wpdb->insert( $history_table, array(
            'devis_id'    => $devis_id,
            'action'      => 'creation',
            'description' => 'Demande reçue via Tripzzy (Enquiry #' . $enquiry_id . ')',
            'user_id'     => null,
            'created_at'  => current_time( 'mysql' )
        ));
    }
    
    // Envoyer les notifications par email via Devis Pro
    if ( class_exists( 'Devis_Pro_Email' ) && class_exists( 'Devis_Pro_DB' ) ) {
        $db = new Devis_Pro_DB();
        $devis = $db->get_devis( $devis_id );
        if ( $devis ) {
            $email = new Devis_Pro_Email();
            $email->send_new_request_notification( $devis );
        }
    }
}

// -----------------------------------------------------------------
// Traiter les shortcodes dans le header secondaire
// -----------------------------------------------------------------
// Traiter les shortcodes dans le header secondaire
add_filter( 'avada_secondary_header_content', 'rdvasie_process_header_shortcodes', 10, 3 );
function rdvasie_process_header_shortcodes( $content, $content_area, $content_to_display ) {
    // Traiter les shortcodes dans le contenu existant
    if ( is_string( $content ) && ! empty( $content ) ) {
        $content = do_shortcode( $content );
    }
    return $content;
}

// Traiter les shortcodes dans les titres de menu WordPress
add_filter( 'wp_setup_nav_menu_item', 'rdvasie_process_menu_item_shortcodes' );
function rdvasie_process_menu_item_shortcodes( $menu_item ) {
    if ( isset( $menu_item->title ) && ! empty( $menu_item->title ) ) {
        // Vérifier si le titre contient un shortcode
        if ( has_shortcode( $menu_item->title, 'rdvasie_rating' ) || 
             has_shortcode( $menu_item->title, 'rdvasie_reviews' ) ||
             preg_match( '/\[.*?\]/', $menu_item->title ) ) {
            $menu_item->title = do_shortcode( $menu_item->title );
        }
    }
    return $menu_item;
}

// Traiter les shortcodes dans le HTML du menu (pour les walkers personnalisés)
add_filter( 'walker_nav_menu_start_el', 'rdvasie_process_menu_output_shortcodes', 10, 4 );
function rdvasie_process_menu_output_shortcodes( $item_output, $item, $depth, $args ) {
    // Traiter les shortcodes dans le HTML du menu
    if ( ! empty( $item_output ) && ( has_shortcode( $item_output, 'rdvasie_rating' ) || 
                                       has_shortcode( $item_output, 'rdvasie_reviews' ) ||
                                       preg_match( '/\[.*?\]/', $item_output ) ) ) {
        $item_output = do_shortcode( $item_output );
    }
    return $item_output;
}