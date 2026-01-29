<?php
/**
 * Plugin Name: RDV Pop-up Devis
 * Plugin URI: https://www.rdvasie.com/
 * Description: Pop-up intelligent avec bouton vers le formulaire de demande de devis. S'affiche selon plusieurs conditions (temps, pages vues, scroll).
 * Version: 1.0.0
 * Author: RDV Asie
 * Author URI: https://www.rdvasie.com/
 * Text Domain: rdv-popup-devis
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('RDV_POPUP_VERSION', '1.0.0');
define('RDV_POPUP_PATH', plugin_dir_path(__FILE__));
define('RDV_POPUP_URL', plugin_dir_url(__FILE__));

class RDV_Popup_Devis {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    public function init() {
        // Enqueue scripts et styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Ajouter le pop-up dans le footer
        add_action('wp_footer', array($this, 'render_popup'));
        
        // Admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // AJAX pour sauvegarder les paramètres
        add_action('wp_ajax_rdv_popup_save_settings', array($this, 'ajax_save_settings'));
    }
    
    /**
     * Enqueue les assets (CSS et JS)
     */
    public function enqueue_assets() {
        // CSS
        wp_enqueue_style(
            'rdv-popup-devis',
            RDV_POPUP_URL . 'assets/popup.css',
            array(),
            RDV_POPUP_VERSION
        );
        
        // JS - S'assurer que jQuery est chargé en premier
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'rdv-popup-devis',
            RDV_POPUP_URL . 'assets/popup.js',
            array('jquery'),
            RDV_POPUP_VERSION,
            true
        );
        
        // Passer les paramètres au JS
        $settings = $this->get_settings();
        
        // Vérifier si le pop-up peut s'afficher sur cette page
        $can_display = $this->can_display_on_current_page($settings);
        
        // Désactiver "pages_viewed" si mode "selected" avec sélection
        $display_mode = isset($settings['display_mode']) ? $settings['display_mode'] : 'all';
        $has_selection = !empty($settings['display_pages']) || !empty($settings['display_categories']) 
            || !empty($settings['display_posts']) || !empty($settings['display_trip_destinations']) 
            || !empty($settings['display_trip_types']) || !empty($settings['display_trips']);
        $pages_viewed = ($display_mode === 'selected' && $has_selection) ? 0 : intval($settings['pages_viewed']);
        
        // Vérifier que le script est bien enregistré avant de localiser
        if (wp_script_is('rdv-popup-devis', 'registered')) {
            wp_localize_script('rdv-popup-devis', 'rdvPopup', array(
                'enabled' => $settings['enabled'] ? 1 : 0, // Forcer 0 ou 1 (nombre, pas chaîne)
                'time_delay' => intval($settings['time_delay']), // en secondes
                'pages_viewed' => $pages_viewed, // Désactivé si mode "selected" avec sélection
                'scroll_percent' => intval($settings['scroll_percent']),
                'cookie_duration' => intval($settings['cookie_duration']), // en jours
                'devis_url' => esc_url($settings['devis_url']),
                'title' => esc_html($settings['title']),
                'message' => esc_html($settings['message']),
                'button_text' => esc_html($settings['button_text']),
                'close_text' => esc_html($settings['close_text']),
                'debug_mode' => isset($settings['debug_mode']) && $settings['debug_mode'] ? 1 : 0,
                'can_display_on_page' => $can_display ? 1 : 0, // Indiquer si la page est valide
            ));
        } else {
            // Fallback : ajouter les paramètres directement dans le HTML
            add_action('wp_footer', function() use ($settings) {
                ?>
                <script type="text/javascript">
                if (typeof rdvPopup === 'undefined') {
                    window.rdvPopup = <?php 
                    $can_display = $this->can_display_on_current_page($settings);
                    $display_mode = isset($settings['display_mode']) ? $settings['display_mode'] : 'all';
                    $has_selection = !empty($settings['display_pages']) || !empty($settings['display_categories']) 
                        || !empty($settings['display_posts']) || !empty($settings['display_trip_destinations']) 
                        || !empty($settings['display_trip_types']) || !empty($settings['display_trips']);
                    $pages_viewed = ($display_mode === 'selected' && $has_selection) ? 0 : intval($settings['pages_viewed']);
                    echo json_encode(array(
                        'enabled' => $settings['enabled'] ? 1 : 0,
                        'time_delay' => intval($settings['time_delay']),
                        'pages_viewed' => $pages_viewed,
                        'scroll_percent' => intval($settings['scroll_percent']),
                        'cookie_duration' => intval($settings['cookie_duration']),
                        'devis_url' => esc_url($settings['devis_url']),
                        'title' => esc_html($settings['title']),
                        'message' => esc_html($settings['message']),
                        'button_text' => esc_html($settings['button_text']),
                        'close_text' => esc_html($settings['close_text']),
                        'debug_mode' => isset($settings['debug_mode']) && $settings['debug_mode'] ? 1 : 0,
                        'can_display_on_page' => $can_display ? 1 : 0,
                    )); ?>;
                    console.log('[RDV Popup] ✅ Paramètres chargés via fallback');
                }
                </script>
                <?php
            }, 5);
        }
    }
    
    /**
     * Rendre le pop-up dans le footer
     */
    public function render_popup() {
        $settings = $this->get_settings();
        
        if (!$settings['enabled']) {
            return;
        }
        
        // Toujours rendre le HTML, la vérification de page se fera côté JavaScript
        // Cela permet d'avoir le HTML disponible même si la vérification PHP échoue
        ?>
        <div id="rdv-popup-devis" class="rdv-popup-devis" style="display: none;">
            <div class="rdv-popup-overlay"></div>
            <div class="rdv-popup-container">
                <button class="rdv-popup-close" aria-label="Fermer">
                    <span>×</span>
                </button>
                <div class="rdv-popup-content">
                    <?php if (!empty($settings['title'])) : ?>
                        <h3 class="rdv-popup-title"><?php echo esc_html($settings['title']); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($settings['message'])) : ?>
                        <p class="rdv-popup-message"><?php echo esc_html($settings['message']); ?></p>
                    <?php endif; ?>
                    <div class="rdv-popup-actions">
                        <a href="<?php echo esc_url($settings['devis_url']); ?>" class="rdv-popup-button">
                            <?php echo esc_html($settings['button_text']); ?>
                        </a>
                        <button class="rdv-popup-close-text"><?php echo esc_html($settings['close_text']); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Ajouter le menu admin
     */
    public function add_admin_menu() {
        add_options_page(
            'Pop-up Devis',
            'Pop-up Devis',
            'manage_options',
            'rdv-popup-devis',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Enregistrer les paramètres
     */
    public function register_settings() {
        register_setting(
            'rdv_popup_settings',
            'rdv_popup_devis_settings',
            array(
                'sanitize_callback' => array($this, 'sanitize_settings'),
            )
        );
    }
    
    /**
     * Sanitizer pour les paramètres
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Enabled (checkbox)
        $sanitized['enabled'] = isset($input['enabled']) && $input['enabled'] == '1' ? 1 : 0;
        
        // URL
        $sanitized['devis_url'] = isset($input['devis_url']) ? esc_url_raw($input['devis_url']) : home_url('/demande-de-devis/');
        
        // Conditions (nombres)
        $sanitized['time_delay'] = isset($input['time_delay']) ? absint($input['time_delay']) : 45;
        $sanitized['pages_viewed'] = isset($input['pages_viewed']) ? absint($input['pages_viewed']) : 2;
        $sanitized['scroll_percent'] = isset($input['scroll_percent']) ? absint($input['scroll_percent']) : 50;
        if ($sanitized['scroll_percent'] > 100) {
            $sanitized['scroll_percent'] = 100;
        }
        $sanitized['cookie_duration'] = isset($input['cookie_duration']) ? absint($input['cookie_duration']) : 7;
        
        // Contenu (texte)
        $sanitized['title'] = isset($input['title']) ? sanitize_text_field($input['title']) : '';
        $sanitized['message'] = isset($input['message']) ? sanitize_textarea_field($input['message']) : '';
        $sanitized['button_text'] = isset($input['button_text']) ? sanitize_text_field($input['button_text']) : '';
        $sanitized['close_text'] = isset($input['close_text']) ? sanitize_text_field($input['close_text']) : '';
        
        // Debug
        $sanitized['debug_mode'] = isset($input['debug_mode']) && $input['debug_mode'] == '1' ? 1 : 0;
        
        // Pages d'affichage
        $sanitized['display_pages'] = isset($input['display_pages']) && is_array($input['display_pages']) 
            ? array_map('absint', $input['display_pages']) 
            : array();
        $sanitized['display_categories'] = isset($input['display_categories']) && is_array($input['display_categories']) 
            ? array_map('absint', $input['display_categories']) 
            : array();
        $sanitized['display_posts'] = isset($input['display_posts']) && is_array($input['display_posts']) 
            ? array_map('absint', $input['display_posts']) 
            : array();
        $sanitized['display_trip_destinations'] = isset($input['display_trip_destinations']) && is_array($input['display_trip_destinations']) 
            ? array_map('absint', $input['display_trip_destinations']) 
            : array();
        $sanitized['display_trip_types'] = isset($input['display_trip_types']) && is_array($input['display_trip_types']) 
            ? array_map('absint', $input['display_trip_types']) 
            : array();
        $sanitized['display_trips'] = isset($input['display_trips']) && is_array($input['display_trips']) 
            ? array_map('absint', $input['display_trips']) 
            : array();
        $sanitized['display_mode'] = isset($input['display_mode']) && in_array($input['display_mode'], array('all', 'selected', 'all_except'))
            ? $input['display_mode']
            : 'all';
        
        return $sanitized;
    }
    
    /**
     * Rendre la page admin
     */
    public function render_admin_page() {
        $settings = $this->get_settings();
        ?>
        <div class="wrap rdv-popup-admin">
            <h1>⚙️ Configuration du Pop-up Devis</h1>
            
            <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') : ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>✅ Paramètres enregistrés avec succès !</strong></p>
                    <p>Si le pop-up ne s'affiche pas, activez le mode débogage et vérifiez la console du navigateur (F12).</p>
                </div>
            <?php endif; ?>
            
            <form id="rdv-popup-settings-form" method="post" action="options.php">
                <?php 
                settings_fields('rdv_popup_settings');
                do_settings_sections('rdv_popup_settings');
                ?>
                
                <div class="rdv-popup-admin-card">
                    <h2>📋 Paramètres généraux</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="enabled">Activer le pop-up</label>
                            </th>
                            <td>
                                <input type="checkbox" id="enabled" name="rdv_popup_devis_settings[enabled]" value="1" <?php checked($settings['enabled'], 1); ?>>
                                <label for="enabled">Activer le pop-up sur le site</label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="debug_mode">Mode débogage</label>
                            </th>
                            <td>
                                <input type="checkbox" id="debug_mode" name="rdv_popup_devis_settings[debug_mode]" value="1" <?php checked($settings['debug_mode'] ?? 0, 1); ?>>
                                <label for="debug_mode">Afficher les logs dans la console du navigateur (F12)</label>
                                <p class="description">Cochez cette case pour voir les messages de débogage dans la console du navigateur</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="devis_url">URL du formulaire de devis</label>
                            </th>
                            <td>
                                <input type="url" id="devis_url" name="rdv_popup_devis_settings[devis_url]" value="<?php echo esc_attr($settings['devis_url']); ?>" class="regular-text" required>
                                <p class="description">URL complète vers votre formulaire de demande de devis (ex: https://www.rdvasie.com/demande-de-devis/)</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="rdv-popup-admin-card">
                    <h2>📍 Pages d'affichage</h2>
                    <p class="description">Choisissez sur quelles pages, catégories ou articles le pop-up peut s'afficher. Si aucune sélection, le pop-up s'affichera partout (sauf pages exclues par défaut).</p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label>Pages</label>
                            </th>
                            <td>
                                <?php
                                $pages = get_pages(array('sort_column' => 'post_title', 'sort_order' => 'ASC'));
                                $selected_pages = isset($settings['display_pages']) ? (array) $settings['display_pages'] : array();
                                ?>
                                <div class="rdv-popup-select-container">
                                    <input type="text" class="rdv-popup-search" placeholder="Rechercher une page..." data-target="pages">
                                    <div class="rdv-popup-checkboxes" id="pages-checkboxes" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-top: 5px;">
                                        <?php foreach ($pages as $page) : ?>
                                            <label style="display: block; padding: 5px 0;">
                                                <input type="checkbox" name="rdv_popup_devis_settings[display_pages][]" value="<?php echo esc_attr($page->ID); ?>" 
                                                    <?php checked(in_array($page->ID, $selected_pages)); ?>>
                                                <?php echo esc_html($page->post_title); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="description">Cocher les pages où le pop-up peut s'afficher</p>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label>Catégories</label>
                            </th>
                            <td>
                                <?php
                                $categories = get_categories(array('hide_empty' => false));
                                $selected_cats = isset($settings['display_categories']) ? (array) $settings['display_categories'] : array();
                                ?>
                                <div class="rdv-popup-select-container">
                                    <input type="text" class="rdv-popup-search" placeholder="Rechercher une catégorie..." data-target="categories">
                                    <div class="rdv-popup-checkboxes" id="categories-checkboxes" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-top: 5px;">
                                        <?php foreach ($categories as $cat) : ?>
                                            <label style="display: block; padding: 5px 0;">
                                                <input type="checkbox" name="rdv_popup_devis_settings[display_categories][]" value="<?php echo esc_attr($cat->term_id); ?>" 
                                                    <?php checked(in_array($cat->term_id, $selected_cats)); ?>>
                                                <?php echo esc_html($cat->name); ?> (<?php echo $cat->count; ?>)
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="description">Cocher les catégories où le pop-up peut s'afficher</p>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label>Articles</label>
                            </th>
                            <td>
                                <?php
                                $posts = get_posts(array('numberposts' => 100, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC'));
                                $selected_posts = isset($settings['display_posts']) ? (array) $settings['display_posts'] : array();
                                ?>
                                <div class="rdv-popup-select-container">
                                    <input type="text" class="rdv-popup-search" placeholder="Rechercher un article..." data-target="posts">
                                    <div class="rdv-popup-checkboxes" id="posts-checkboxes" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-top: 5px;">
                                        <?php foreach ($posts as $post) : ?>
                                            <label style="display: block; padding: 5px 0;">
                                                <input type="checkbox" name="rdv_popup_devis_settings[display_posts][]" value="<?php echo esc_attr($post->ID); ?>" 
                                                    <?php checked(in_array($post->ID, $selected_posts)); ?>>
                                                <?php echo esc_html($post->post_title); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="description">Cocher les articles où le pop-up peut s'afficher (limité aux 100 premiers articles)</p>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label>Destinations Tripzzy</label>
                            </th>
                            <td>
                                <?php
                                $trip_destinations = get_terms(array(
                                    'taxonomy' => 'tripzzy_trip_destination',
                                    'hide_empty' => false,
                                ));
                                $selected_destinations = isset($settings['display_trip_destinations']) ? (array) $settings['display_trip_destinations'] : array();
                                ?>
                                <?php if (!is_wp_error($trip_destinations) && !empty($trip_destinations)) : ?>
                                    <div class="rdv-popup-select-container">
                                        <input type="text" class="rdv-popup-search" placeholder="Rechercher une destination..." data-target="trip-destinations">
                                        <div class="rdv-popup-checkboxes" id="trip-destinations-checkboxes" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-top: 5px;">
                                            <?php foreach ($trip_destinations as $dest) : ?>
                                                <label style="display: block; padding: 5px 0;">
                                                    <input type="checkbox" name="rdv_popup_devis_settings[display_trip_destinations][]" value="<?php echo esc_attr($dest->term_id); ?>" 
                                                        <?php checked(in_array($dest->term_id, $selected_destinations)); ?>>
                                                    <?php echo esc_html($dest->name); ?> (<?php echo $dest->count; ?>)
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                        <p class="description">Cocher les destinations de voyage où le pop-up peut s'afficher</p>
                                    </div>
                                <?php else : ?>
                                    <p class="description">Aucune destination Tripzzy trouvée</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label>Types de voyage Tripzzy</label>
                            </th>
                            <td>
                                <?php
                                $trip_types = get_terms(array(
                                    'taxonomy' => 'tripzzy_trip_type',
                                    'hide_empty' => false,
                                ));
                                $selected_trip_types = isset($settings['display_trip_types']) ? (array) $settings['display_trip_types'] : array();
                                ?>
                                <?php if (!is_wp_error($trip_types) && !empty($trip_types)) : ?>
                                    <div class="rdv-popup-select-container">
                                        <input type="text" class="rdv-popup-search" placeholder="Rechercher un type de voyage..." data-target="trip-types">
                                        <div class="rdv-popup-checkboxes" id="trip-types-checkboxes" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-top: 5px;">
                                            <?php foreach ($trip_types as $type) : ?>
                                                <label style="display: block; padding: 5px 0;">
                                                    <input type="checkbox" name="rdv_popup_devis_settings[display_trip_types][]" value="<?php echo esc_attr($type->term_id); ?>" 
                                                        <?php checked(in_array($type->term_id, $selected_trip_types)); ?>>
                                                    <?php echo esc_html($type->name); ?> (<?php echo $type->count; ?>)
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                        <p class="description">Cocher les types de voyage où le pop-up peut s'afficher</p>
                                    </div>
                                <?php else : ?>
                                    <p class="description">Aucun type de voyage Tripzzy trouvé</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label>Voyages Tripzzy</label>
                            </th>
                            <td>
                                <?php
                                $trips = get_posts(array(
                                    'post_type' => 'tripzzy',
                                    'numberposts' => 100,
                                    'post_status' => 'publish',
                                    'orderby' => 'title',
                                    'order' => 'ASC',
                                ));
                                $selected_trips = isset($settings['display_trips']) ? (array) $settings['display_trips'] : array();
                                ?>
                                <?php if (!empty($trips)) : ?>
                                    <div class="rdv-popup-select-container">
                                        <input type="text" class="rdv-popup-search" placeholder="Rechercher un voyage..." data-target="trips">
                                        <div class="rdv-popup-checkboxes" id="trips-checkboxes" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-top: 5px;">
                                            <?php foreach ($trips as $trip) : ?>
                                                <label style="display: block; padding: 5px 0;">
                                                    <input type="checkbox" name="rdv_popup_devis_settings[display_trips][]" value="<?php echo esc_attr($trip->ID); ?>" 
                                                        <?php checked(in_array($trip->ID, $selected_trips)); ?>>
                                                    <?php echo esc_html($trip->post_title); ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                        <p class="description">Cocher les voyages individuels où le pop-up peut s'afficher (limité aux 100 premiers voyages)</p>
                                    </div>
                                <?php else : ?>
                                    <p class="description">Aucun voyage Tripzzy trouvé</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label>Mode d'affichage</label>
                            </th>
                            <td>
                                <select name="rdv_popup_devis_settings[display_mode]">
                                    <option value="all" <?php selected($settings['display_mode'] ?? 'all', 'all'); ?>>Afficher sur toutes les pages</option>
                                    <option value="selected" <?php selected($settings['display_mode'] ?? 'all', 'selected'); ?>>Afficher uniquement sur les pages sélectionnées</option>
                                    <option value="all_except" <?php selected($settings['display_mode'] ?? 'all', 'all_except'); ?>>Afficher partout sauf les pages sélectionnées</option>
                                </select>
                                <p class="description">Choisissez le mode d'affichage du pop-up</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="rdv-popup-admin-card">
                    <h2>⏱️ Conditions d'affichage</h2>
                    <p class="description">Le pop-up s'affichera si <strong>au moins une</strong> de ces conditions est remplie :</p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="time_delay">Temps sur la page (secondes)</label>
                            </th>
                            <td>
                                <input type="number" id="time_delay" name="rdv_popup_devis_settings[time_delay]" value="<?php echo esc_attr($settings['time_delay']); ?>" min="0" step="1" class="small-text">
                                <p class="description">Afficher le pop-up après X secondes passées sur la page (0 pour désactiver)</p>
                            </td>
                        </tr>
                        <tr id="pages-viewed-row" <?php 
                            $display_mode = $settings['display_mode'] ?? 'all';
                            $has_selection = !empty($settings['display_pages']) || !empty($settings['display_categories']) 
                                || !empty($settings['display_posts']) || !empty($settings['display_trip_destinations']) 
                                || !empty($settings['display_trip_types']) || !empty($settings['display_trips']);
                            $is_selected_mode = ($display_mode === 'selected' && $has_selection);
                            echo $is_selected_mode ? 'style="display: none;"' : '';
                        ?>>
                            <th scope="row">
                                <label for="pages_viewed">Nombre de pages vues</label>
                            </th>
                            <td>
                                <input type="number" id="pages_viewed" name="rdv_popup_devis_settings[pages_viewed]" 
                                    value="<?php echo esc_attr($settings['pages_viewed']); ?>" min="0" step="1" class="small-text">
                                <p class="description">Afficher le pop-up après avoir visité X pages (0 pour désactiver)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="scroll_percent">Pourcentage de scroll (%)</label>
                            </th>
                            <td>
                                <input type="number" id="scroll_percent" name="rdv_popup_devis_settings[scroll_percent]" value="<?php echo esc_attr($settings['scroll_percent']); ?>" min="0" max="100" step="1" class="small-text">
                                <p class="description">Afficher le pop-up après avoir scrollé X% de la page (0 pour désactiver)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="cookie_duration">Durée du cookie (jours)</label>
                            </th>
                            <td>
                                <input type="number" id="cookie_duration" name="rdv_popup_devis_settings[cookie_duration]" value="<?php echo esc_attr($settings['cookie_duration']); ?>" min="0" step="1" class="small-text">
                                <p class="description">Ne pas réafficher le pop-up pendant X jours après fermeture (0 pour toujours afficher)</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="rdv-popup-admin-card">
                    <h2>✏️ Contenu du pop-up</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="title">Titre</label>
                            </th>
                            <td>
                                <input type="text" id="title" name="rdv_popup_devis_settings[title]" value="<?php echo esc_attr($settings['title']); ?>" class="regular-text">
                                <p class="description">Titre affiché dans le pop-up</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="message">Message</label>
                            </th>
                            <td>
                                <textarea id="message" name="rdv_popup_devis_settings[message]" rows="3" class="large-text"><?php echo esc_textarea($settings['message']); ?></textarea>
                                <p class="description">Message affiché dans le pop-up</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="button_text">Texte du bouton</label>
                            </th>
                            <td>
                                <input type="text" id="button_text" name="rdv_popup_devis_settings[button_text]" value="<?php echo esc_attr($settings['button_text']); ?>" class="regular-text">
                                <p class="description">Texte du bouton principal</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="close_text">Texte de fermeture</label>
                            </th>
                            <td>
                                <input type="text" id="close_text" name="rdv_popup_devis_settings[close_text]" value="<?php echo esc_attr($settings['close_text']); ?>" class="regular-text">
                                <p class="description">Texte du lien de fermeture</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button('Enregistrer les paramètres'); ?>
            </form>
            
            <div class="rdv-popup-admin-card" style="background: #f0f8ff; border-left: 4px solid #0073aa;">
                <h2>🔍 Guide de débogage</h2>
                <p><strong>Le pop-up ne s'affiche pas ?</strong></p>
                <ol>
                    <li><strong>Activez le mode débogage</strong> ci-dessus et enregistrez</li>
                    <li><strong>Ouvrez la console du navigateur</strong> (F12 ou Cmd+Option+I sur Mac)</li>
                    <li><strong>Rechargez la page</strong> et regardez les messages dans la console</li>
                    <li><strong>Vérifiez que :</strong>
                        <ul>
                            <li>Le pop-up est activé (case cochée)</li>
                            <li>Au moins une condition est configurée (temps, pages ou scroll)</li>
                            <li>Le mode d'affichage est correct</li>
                            <li>Le HTML du pop-up existe dans la page (inspecter avec F12)</li>
                        </ul>
                    </li>
                </ol>
                <p><strong>Test rapide :</strong> Mettez le temps à 2 secondes, activez le débogage, et regardez la console.</p>
            </div>
        </div>
        
        <style>
        .rdv-popup-admin {
            max-width: 1200px;
        }
        .rdv-popup-admin-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            padding: 20px;
            margin: 20px 0;
        }
        .rdv-popup-admin-card h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .rdv-popup-search {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 5px;
        }
        .rdv-popup-checkboxes label {
            cursor: pointer;
        }
        .rdv-popup-checkboxes label:hover {
            background: #f5f5f5;
        }
        .rdv-popup-checkboxes input[type="checkbox"] {
            margin-right: 8px;
        }
        </style>
        <script>
        jQuery(document).ready(function($) {
            // Recherche dans les checkboxes
            $('.rdv-popup-search').on('keyup', function() {
                var search = $(this).val().toLowerCase();
                var target = $(this).data('target');
                var container = $('#' + target + '-checkboxes');
                
                if (container.length === 0) {
                    // Essayer avec un tiret au lieu d'un underscore
                    container = $('#' + target.replace('-', '-') + '-checkboxes');
                }
                
                container.find('label').each(function() {
                    var text = $(this).text().toLowerCase();
                    if (text.indexOf(search) !== -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
            
            // Masquer "Nombre de pages vues" quand mode "selected" avec sélection
            function updatePagesViewedField() {
                var displayMode = $('select[name="rdv_popup_devis_settings[display_mode]"]').val();
                var hasSelection = false;
                
                // Vérifier si au moins une sélection existe
                if (displayMode === 'selected') {
                    hasSelection = $('input[name^="rdv_popup_devis_settings[display_pages]"]:checked').length > 0 ||
                                   $('input[name^="rdv_popup_devis_settings[display_categories]"]:checked').length > 0 ||
                                   $('input[name^="rdv_popup_devis_settings[display_posts]"]:checked').length > 0 ||
                                   $('input[name^="rdv_popup_devis_settings[display_trip_destinations]"]:checked').length > 0 ||
                                   $('input[name^="rdv_popup_devis_settings[display_trip_types]"]:checked').length > 0 ||
                                   $('input[name^="rdv_popup_devis_settings[display_trips]"]:checked').length > 0;
                }
                
                var $pagesViewedRow = $('#pages-viewed-row');
                
                if (displayMode === 'selected' && hasSelection) {
                    $pagesViewedRow.hide();
                    $('#pages_viewed').val(0);
                } else {
                    $pagesViewedRow.show();
                }
            }
            
            // Écouter les changements de mode d'affichage
            $('select[name="rdv_popup_devis_settings[display_mode]"]').on('change', updatePagesViewedField);
            
            // Écouter les changements de sélection
            $('input[name^="rdv_popup_devis_settings[display_"]').on('change', updatePagesViewedField);
            
            // Initialiser au chargement
            updatePagesViewedField();
        });
        </script>
        <?php
        
        // Afficher un message de succès si les paramètres ont été sauvegardés
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
            echo '<div class="notice notice-success is-dismissible"><p>Paramètres enregistrés avec succès !</p></div>';
        }
    }
    
    /**
     * Récupérer les paramètres
     */
    private function get_settings() {
        $defaults = array(
            'enabled' => 1,
            'time_delay' => 45,
            'pages_viewed' => 2,
            'scroll_percent' => 50,
            'cookie_duration' => 7,
            'devis_url' => home_url('/demande-de-devis/'),
            'title' => 'Besoin d\'un devis personnalisé ?',
            'message' => 'Créez votre voyage sur mesure en Asie selon vos envies et votre budget.',
            'button_text' => 'Demander un devis gratuit',
            'close_text' => 'Plus tard',
            'display_pages' => array(),
            'display_categories' => array(),
            'display_posts' => array(),
            'display_trip_destinations' => array(),
            'display_trip_types' => array(),
            'display_trips' => array(),
            'display_mode' => 'all',
        );
        
        $settings = get_option('rdv_popup_devis_settings', $defaults);
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Vérifier si le pop-up peut s'afficher sur la page actuelle
     */
    private function can_display_on_current_page($settings) {
        $display_pages = isset($settings['display_pages']) ? (array) $settings['display_pages'] : array();
        $display_categories = isset($settings['display_categories']) ? (array) $settings['display_categories'] : array();
        $display_posts = isset($settings['display_posts']) ? (array) $settings['display_posts'] : array();
        $display_trip_destinations = isset($settings['display_trip_destinations']) ? (array) $settings['display_trip_destinations'] : array();
        $display_trip_types = isset($settings['display_trip_types']) ? (array) $settings['display_trip_types'] : array();
        $display_trips = isset($settings['display_trips']) ? (array) $settings['display_trips'] : array();
        $display_mode = isset($settings['display_mode']) ? $settings['display_mode'] : 'all';
        
        // Mode "toutes les pages" : afficher partout (sauf pages exclues par défaut)
        if ($display_mode === 'all') {
            return true;
        }
        
        // Si aucune sélection, selon le mode
        $has_selection = !empty($display_pages) || !empty($display_categories) || !empty($display_posts) 
            || !empty($display_trip_destinations) || !empty($display_trip_types) || !empty($display_trips);
        
        if (!$has_selection) {
            // Si mode "selected" sans sélection = ne pas afficher
            // Si mode "all_except" sans sélection = afficher partout
            return $display_mode === 'all_except';
        }
        
        $is_selected = false;
        
        // Page d'accueil
        if (is_front_page()) {
            $front_page_id = get_option('page_on_front');
            if ($front_page_id && in_array($front_page_id, $display_pages)) {
                $is_selected = true;
            } elseif (!$front_page_id && in_array(0, $display_pages)) {
                // Page d'accueil = posts (ID 0 ou -1)
                $is_selected = true;
            }
        }
        // Page WooCommerce Shop (doit être vérifiée avant is_page() car is_shop() peut retourner true même si c'est une archive)
        elseif (function_exists('is_shop') && is_shop()) {
            $shop_page_id = function_exists('wc_get_page_id') ? wc_get_page_id('shop') : false;
            if ($shop_page_id && $shop_page_id > 0 && in_array($shop_page_id, $display_pages)) {
                $is_selected = true;
            }
        }
        // Page statique (y compris page shop si elle est aussi détectée comme page)
        elseif (is_page()) {
            $current_id = get_queried_object_id();
            // Vérifier aussi si c'est la page shop WooCommerce
            if (function_exists('wc_get_page_id')) {
                $shop_page_id = wc_get_page_id('shop');
                if ($shop_page_id > 0 && $current_id == $shop_page_id && in_array($shop_page_id, $display_pages)) {
                    $is_selected = true;
                }
            }
            // Vérifier si la page est dans la liste des pages sélectionnées
            if (!$is_selected && in_array($current_id, $display_pages)) {
                $is_selected = true;
            }
        }
        // Catégorie
        elseif (is_category()) {
            $current_id = get_queried_object_id();
            if (in_array($current_id, $display_categories)) {
                $is_selected = true;
            }
        }
        // Article ou Voyage Tripzzy
        elseif (is_single()) {
            $current_id = get_queried_object_id();
            $post_type = get_post_type($current_id);
            
            // Si c'est un voyage Tripzzy
            if ($post_type === 'tripzzy') {
                // Vérifier si le voyage est directement sélectionné
                if (in_array($current_id, $display_trips)) {
                    $is_selected = true;
                } else {
                    // Vérifier les destinations Tripzzy
                    $trip_destinations = wp_get_post_terms($current_id, 'tripzzy_trip_destination', array('fields' => 'ids'));
                    foreach ($trip_destinations as $dest_id) {
                        if (in_array($dest_id, $display_trip_destinations)) {
                            $is_selected = true;
                            break;
                        }
                    }
                    
                    // Vérifier les types de voyage Tripzzy
                    if (!$is_selected) {
                        $trip_types = wp_get_post_terms($current_id, 'tripzzy_trip_type', array('fields' => 'ids'));
                        foreach ($trip_types as $type_id) {
                            if (in_array($type_id, $display_trip_types)) {
                                $is_selected = true;
                                break;
                            }
                        }
                    }
                }
            } else {
                // Article normal
                // Vérifier si l'article est directement sélectionné
                if (in_array($current_id, $display_posts)) {
                    $is_selected = true;
                } else {
                    // Vérifier si l'article appartient à une catégorie sélectionnée
                    $post_categories = wp_get_post_categories($current_id);
                    foreach ($post_categories as $cat_id) {
                        if (in_array($cat_id, $display_categories)) {
                            $is_selected = true;
                            break;
                        }
                    }
                }
            }
        }
        // Archive de destinations Tripzzy
        elseif (is_tax('tripzzy_trip_destination')) {
            $current_id = get_queried_object_id();
            if (in_array($current_id, $display_trip_destinations)) {
                $is_selected = true;
            }
        }
        // Archive de types de voyage Tripzzy
        elseif (is_tax('tripzzy_trip_type')) {
            $current_id = get_queried_object_id();
            if (in_array($current_id, $display_trip_types)) {
                $is_selected = true;
            }
        }
        // Archive (blog, etc.)
        elseif (is_archive() || is_home()) {
            // Pour les archives, on peut considérer qu'elles sont sélectionnées si aucune restriction
            // ou si une catégorie de l'archive est sélectionnée
            // Pour simplifier, on considère que les archives ne sont pas sélectionnées par défaut
            // sauf si explicitement géré
        }
        
        // Appliquer le mode
        if ($display_mode === 'selected') {
            return $is_selected; // Afficher uniquement si sélectionné
        } else {
            return !$is_selected; // Afficher partout SAUF si sélectionné
        }
    }
    
    /**
     * AJAX: Sauvegarder les paramètres
     */
    public function ajax_save_settings() {
        check_ajax_referer('rdv_popup_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }
        
        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        $clean_settings = array_map('sanitize_text_field', $settings);
        $clean_settings['enabled'] = isset($settings['enabled']) ? 1 : 0;
        
        update_option('rdv_popup_devis_settings', $clean_settings);
        
        wp_send_json_success(array('message' => 'Paramètres sauvegardés !'));
    }
}

// Initialiser le plugin
RDV_Popup_Devis::get_instance();
