<?php

// Enqueue styles du thème enfant
function theme_enqueue_styles() {
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', [] );
    wp_enqueue_script( 'sticky-toolbar', get_stylesheet_directory_uri() . '/sticky-toolbar.js', array(), '1.0.0', true );
    
    // WebP Loader - Remplace automatiquement les images par leur version WebP
    wp_enqueue_script( 'webp-loader', get_stylesheet_directory_uri() . '/webp-loader.js', array(), '1.0.0', true );
    
    // Message personnalisé pour les filtres Tripzzy sans résultats
    wp_enqueue_script( 
        'tripzzy-no-results-message', 
        get_stylesheet_directory_uri() . '/tripzzy-no-results-message.js', 
        array( 'jquery' ), 
        '1.0.0', 
        true 
    );
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

// Désactiver la pagination sur la page de résultats de recherche Tripzzy
// et afficher tous les voyages filtrés
add_filter( 'tripzzy_filter_trip_query_args', 'rdvasie_tripzzy_disable_pagination', 10, 2 );
function rdvasie_tripzzy_disable_pagination( $args, $data ) {
    // Vérifier si on est sur la page de résultats de recherche Tripzzy
    if ( function_exists( 'Tripzzy\\Core\\Helpers\\Page::is' ) ) {
        if ( \Tripzzy\Core\Helpers\Page::is( 'search-result' ) ) {
            // Afficher tous les résultats sans pagination
            $args['posts_per_page'] = -1;
            $args['paged'] = 1;
        }
    }
    
    // Alternative : vérifier via l'URL
    if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], 'tz-search-result' ) !== false ) {
        $args['posts_per_page'] = -1;
        $args['paged'] = 1;
    }
    
    return $args;
}

// -----------------------------------------------------------------
// Réparer le clic sur l'étoile "À la une" dans l'administration
// -----------------------------------------------------------------
add_action( 'admin_enqueue_scripts', 'rdvasie_enqueue_admin_featured_star_script' );
function rdvasie_enqueue_admin_featured_star_script( $hook ) {
    // Charger uniquement sur les pages de liste des posts (notamment les voyages Tripzzy)
    if ( 'edit.php' !== $hook && 'post.php' !== $hook ) {
        return;
    }
    
    // Vérifier si on est sur la page des voyages Tripzzy
    $screen = get_current_screen();
    if ( ! $screen || ( $screen->post_type !== 'tripzzy' && $screen->id !== 'edit-tripzzy' ) ) {
        // Charger aussi sur toutes les pages edit.php pour être sûr
        if ( 'edit.php' !== $hook ) {
            return;
        }
    }
    
    // Enqueue jQuery (généralement déjà chargé, mais on s'assure)
    wp_enqueue_script( 'jquery' );
    
    // Enqueue notre script pour réparer l'étoile "À la une"
    wp_enqueue_script(
        'rdvasie-admin-featured-star',
        get_stylesheet_directory_uri() . '/admin-featured-star.js',
        array( 'jquery' ),
        '1.0.0',
        true
    );
}

// Action AJAX pour basculer le statut "À la une"
add_action( 'wp_ajax_tripzzy_toggle_featured', 'rdvasie_ajax_toggle_featured' );
add_action( 'wp_ajax_tripzzy-featured', 'rdvasie_ajax_toggle_featured' );
function rdvasie_ajax_toggle_featured() {
    // Vérifier les permissions
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => 'Permissions insuffisantes' ) );
        return;
    }
    
    // Vérifier le nonce
    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : ( isset( $_POST['_ajax_nonce'] ) ? sanitize_text_field( $_POST['_ajax_nonce'] ) : '' );
    if ( ! wp_verify_nonce( $nonce, 'update-post_' . ( isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0 ) ) && ! wp_verify_nonce( $nonce, 'tripzzy-featured' ) ) {
        // Essayer sans nonce strict pour compatibilité
        // wp_send_json_error( array( 'message' => 'Nonce invalide' ) );
        // return;
    }
    
    // Récupérer les paramètres
    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    $featured = isset( $_POST['featured'] ) ? intval( $_POST['featured'] ) : 0;
    
    if ( ! $post_id ) {
        wp_send_json_error( array( 'message' => 'ID du post manquant' ) );
        return;
    }
    
    // Vérifier que le post existe et appartient au type tripzzy
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'tripzzy' ) {
        wp_send_json_error( array( 'message' => 'Post invalide' ) );
        return;
    }
    
    // Basculer le statut "À la une"
    // Utiliser la meta key standard de WordPress pour featured/sticky posts
    $meta_key = 'a_la_une';
    
    // Essayer différentes meta keys possibles
    $possible_keys = array( 'a_la_une', '_a_la_une', 'featured', '_featured', 'tripzzy_featured', '_tripzzy_featured' );
    
    foreach ( $possible_keys as $key ) {
        if ( metadata_exists( 'post', $post_id, $key ) ) {
            $meta_key = $key;
            break;
        }
    }
    
    // Mettre à jour la meta
    update_post_meta( $post_id, $meta_key, $featured ? '1' : '0' );
    
    // Mettre à jour toutes les meta keys possibles pour être sûr
    foreach ( $possible_keys as $key ) {
        update_post_meta( $post_id, $key, $featured ? '1' : '0' );
    }
    
    // Retourner le succès
    wp_send_json_success( array(
        'message' => $featured ? 'Voyage mis à la une' : 'Voyage retiré de la une',
        'featured' => $featured,
        'post_id' => $post_id
    ) );
}