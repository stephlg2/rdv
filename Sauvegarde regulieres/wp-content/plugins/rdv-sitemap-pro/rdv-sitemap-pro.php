<?php
/**
 * Plugin Name: RDV Sitemap Pro
 * Description: Sitemap XML/HTML optimisé pour les sites de voyage + compatible LLM (ChatGPT, Perplexity...)
 * Version: 1.0.0
 * Author: RDV Asie
 * Text Domain: rdv-sitemap-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

define('RDV_SITEMAP_VERSION', '1.0.0');
define('RDV_SITEMAP_PATH', plugin_dir_path(__FILE__));
define('RDV_SITEMAP_URL', plugin_dir_url(__FILE__));

class RDV_Sitemap_Pro {

    private $settings;
    private $post_types = [];
    private $taxonomies = [];

    public function __construct() {
        $this->settings = get_option('rdv_sitemap_settings', $this->get_default_settings());
        
        // Hooks
        add_action('init', array($this, 'init'), 20);
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));
        add_action('wp_ajax_rdv_sitemap_regenerate', array($this, 'ajax_regenerate'));
        add_action('wp_ajax_rdv_sitemap_ping', array($this, 'ajax_ping_search_engines'));
        add_action('wp_ajax_rdv_sitemap_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_rdv_sitemap_get_urls', array($this, 'ajax_get_urls'));
        add_action('wp_ajax_rdv_sitemap_save_exclusions', array($this, 'ajax_save_exclusions'));
        add_action('wp_ajax_rdv_sitemap_save_llm_settings', array($this, 'ajax_save_llm_settings'));
        add_action('wp_ajax_rdv_sitemap_preview_llms', array($this, 'ajax_preview_llms'));
        
        // Schema.org JSON-LD pour les voyages
        add_action('wp_head', array($this, 'output_schema_jsonld'));
        
        // Rewrite rules pour les sitemaps
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_sitemap_request'));
        
        // Shortcode pour sitemap HTML
        add_shortcode('rdv-sitemap', array($this, 'shortcode_sitemap_html'));
        
        // Auto-ping après publication
        add_action('publish_post', array($this, 'auto_ping_on_publish'));
        add_action('publish_page', array($this, 'auto_ping_on_publish'));
        add_action('publish_tripzzy', array($this, 'auto_ping_on_publish'));
        add_action('publish_avada_faq', array($this, 'auto_ping_on_publish'));
        
        // Cron hebdomadaire
        add_action('rdv_sitemap_weekly_cron', array($this, 'weekly_regenerate'));
        
        // Activation/Désactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Échappe une chaîne pour XML (pas HTML !)
     */
    private function esc_xml($string) {
        // Décoder les entités HTML d'abord (pour éviter &rsquo; etc.)
        $string = html_entity_decode($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Échapper pour XML
        return htmlspecialchars($string, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Paramètres par défaut
     */
    private function get_default_settings() {
        return array(
            'enable_xml' => true,
            'enable_html' => true,
            'enable_llms_txt' => true,
            'enable_images' => true,
            'auto_ping' => true,
            // Paramètres LLM Optimizer
            'llm' => array(
                'enable_llms_txt' => true,
                'enable_llms_full' => true,
                'enable_schema' => true,
                'enable_robots_hint' => true,
                'company_name' => 'Rendez-vous avec l\'Asie',
                'company_description' => 'Agence de voyage spécialisée dans les circuits sur mesure en Asie.',
                'company_email' => 'contact@rdvasie.com',
                'company_phone' => '02 14 00 12 53',
                'include_voyages' => true,
                'include_destinations' => true,
                'include_faq' => true,
                'include_articles' => true,
                'include_prices' => true,
                'include_durations' => true,
                'include_descriptions' => true,
                'voyages_limit' => 50,
                'faq_limit' => 50,
                'articles_limit' => 20,
                'description_length' => 500,
            ),
            'post_types' => array(
                'tripzzy' => array('enabled' => true, 'priority' => '0.9', 'changefreq' => 'weekly'),
                'avada_faq' => array('enabled' => true, 'priority' => '0.8', 'changefreq' => 'monthly'),
                'post' => array('enabled' => true, 'priority' => '0.7', 'changefreq' => 'weekly'),
                'page' => array('enabled' => true, 'priority' => '0.8', 'changefreq' => 'monthly'),
            ),
            'taxonomies' => array(
                'tripzzy_trip_destination' => array('enabled' => true, 'priority' => '0.8'),
                'tripzzy_trip_type' => array('enabled' => true, 'priority' => '0.7'),
                'category' => array('enabled' => true, 'priority' => '0.6'),
            ),
            'excluded_ids' => array(),
            'homepage_priority' => '1.0',
            'max_urls_per_sitemap' => 1000,
        );
    }

    /**
     * Initialisation
     */
    public function init() {
        // Récupérer les types de contenu disponibles
        $this->post_types = array(
            'tripzzy' => 'Voyages (Tripzzy)',
            'avada_faq' => 'FAQ',
            'post' => 'Articles',
            'page' => 'Pages',
        );
        
        $this->taxonomies = array(
            'tripzzy_trip_destination' => 'Destinations',
            'tripzzy_trip_type' => 'Types de voyage',
            'tripzzy_trip_activities' => 'Activités',
            'category' => 'Catégories',
            'post_tag' => 'Tags',
        );
    }

    /**
     * Activation du plugin
     */
    public function activate() {
        $this->add_rewrite_rules();
        flush_rewrite_rules();
        
        // Créer la page sitemap HTML si elle n'existe pas
        if (!get_page_by_path('plan-du-site')) {
            wp_insert_post(array(
                'post_title' => 'Plan du site',
                'post_name' => 'plan-du-site',
                'post_content' => '[rdv-sitemap]',
                'post_status' => 'publish',
                'post_type' => 'page',
            ));
        }
        
        // Programmer le cron hebdomadaire
        if (!wp_next_scheduled('rdv_sitemap_weekly_cron')) {
            wp_schedule_event(time(), 'weekly', 'rdv_sitemap_weekly_cron');
        }
    }

    /**
     * Désactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
        
        // Supprimer le cron
        $timestamp = wp_next_scheduled('rdv_sitemap_weekly_cron');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'rdv_sitemap_weekly_cron');
        }
    }
    
    /**
     * Régénération hebdomadaire automatique
     */
    public function weekly_regenerate() {
        // Flush les règles de réécriture
        flush_rewrite_rules();
        
        // Ping les moteurs de recherche
        $this->ping_search_engines();
        
        // Vider le cache WP Super Cache si actif
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }
        
        // Vider le cache W3 Total Cache si actif
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }
        
        // Vider le cache LiteSpeed si actif
        if (class_exists('LiteSpeed_Cache_API')) {
            LiteSpeed_Cache_API::purge_all();
        }
        
        // Log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[RDV Sitemap Pro] Régénération hebdomadaire effectuée');
        }
        
        // Sauvegarder la date de dernière régénération
        update_option('rdv_sitemap_last_regenerate', current_time('mysql'));
    }

    /**
     * Règles de réécriture
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^sitemap\.xml$', 'index.php?rdv_sitemap=index', 'top');
        add_rewrite_rule('^sitemap-([a-z0-9_-]+)\.xml$', 'index.php?rdv_sitemap=$matches[1]', 'top');
        add_rewrite_rule('^llms\.txt$', 'index.php?rdv_sitemap=llms', 'top');
        add_rewrite_rule('^llms-full\.txt$', 'index.php?rdv_sitemap=llms-full', 'top');
        add_rewrite_rule('^.well-known/llms\.txt$', 'index.php?rdv_sitemap=llms', 'top');
    }

    public function add_query_vars($vars) {
        $vars[] = 'rdv_sitemap';
        return $vars;
    }

    /**
     * Gérer les requêtes sitemap
     */
    public function handle_sitemap_request() {
        $sitemap_type = get_query_var('rdv_sitemap');
        
        if (empty($sitemap_type)) {
            return;
        }

        // Désactiver le cache
        nocache_headers();

        switch ($sitemap_type) {
            case 'index':
                $this->render_sitemap_index();
                break;
            case 'llms':
                $this->render_llms_txt(false);
                break;
            case 'llms-full':
                $this->render_llms_txt(true);
                break;
            case 'images':
                $this->render_images_sitemap();
                break;
            default:
                // Sitemap par type de contenu
                $this->render_sitemap($sitemap_type);
                break;
        }
        exit;
    }

    /**
     * Sitemap Index
     */
    private function render_sitemap_index() {
        header('Content-Type: application/xml; charset=utf-8');
        
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Sitemap pour chaque type de contenu activé
        foreach ($this->settings['post_types'] as $type => $config) {
            if (!empty($config['enabled'])) {
                $count = wp_count_posts($type);
                if (isset($count->publish) && $count->publish > 0) {
                    echo '<sitemap>' . "\n";
                    echo '  <loc>' . home_url('/sitemap-' . $type . '.xml') . '</loc>' . "\n";
                    echo '  <lastmod>' . $this->get_last_modified($type) . '</lastmod>' . "\n";
                    echo '</sitemap>' . "\n";
                }
            }
        }

        // Sitemap pour les taxonomies
        foreach ($this->settings['taxonomies'] as $tax => $config) {
            if (!empty($config['enabled']) && taxonomy_exists($tax)) {
                $terms = get_terms(array('taxonomy' => $tax, 'hide_empty' => true));
                if (!is_wp_error($terms) && count($terms) > 0) {
                    echo '<sitemap>' . "\n";
                    echo '  <loc>' . home_url('/sitemap-tax-' . $tax . '.xml') . '</loc>' . "\n";
                    echo '  <lastmod>' . date('c') . '</lastmod>' . "\n";
                    echo '</sitemap>' . "\n";
                }
            }
        }

        // Sitemap images
        if ($this->settings['enable_images']) {
            echo '<sitemap>' . "\n";
            echo '  <loc>' . home_url('/sitemap-images.xml') . '</loc>' . "\n";
            echo '  <lastmod>' . date('c') . '</lastmod>' . "\n";
            echo '</sitemap>' . "\n";
        }

        echo '</sitemapindex>';
    }

    /**
     * Sitemap par type de contenu
     */
    private function render_sitemap($type) {
        header('Content-Type: application/xml; charset=utf-8');
        
        // Vérifier si c'est une taxonomie
        if (strpos($type, 'tax-') === 0) {
            $this->render_taxonomy_sitemap(str_replace('tax-', '', $type));
            return;
        }

        $config = $this->settings['post_types'][$type] ?? array('priority' => '0.5', 'changefreq' => 'weekly');
        
        $args = array(
            'post_type' => $type,
            'post_status' => 'publish',
            'posts_per_page' => $this->settings['max_urls_per_sitemap'],
            'orderby' => 'modified',
            'order' => 'DESC',
        );

        // Exclure certains IDs
        if (!empty($this->settings['excluded_ids'])) {
            $args['post__not_in'] = $this->settings['excluded_ids'];
        }

        $posts = get_posts($args);

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        // Page d'accueil dans le sitemap des pages
        if ($type === 'page') {
            echo '<url>' . "\n";
            echo '  <loc>' . home_url('/') . '</loc>' . "\n";
            echo '  <lastmod>' . date('c') . '</lastmod>' . "\n";
            echo '  <changefreq>daily</changefreq>' . "\n";
            echo '  <priority>' . $this->settings['homepage_priority'] . '</priority>' . "\n";
            echo '</url>' . "\n";
        }

        foreach ($posts as $post) {
            echo '<url>' . "\n";
            echo '  <loc>' . get_permalink($post) . '</loc>' . "\n";
            echo '  <lastmod>' . get_the_modified_date('c', $post) . '</lastmod>' . "\n";
            echo '  <changefreq>' . $config['changefreq'] . '</changefreq>' . "\n";
            echo '  <priority>' . $config['priority'] . '</priority>' . "\n";
            
            // Images associées (pour les voyages notamment)
            $images = $this->get_post_images($post->ID);
            foreach ($images as $image) {
                echo '  <image:image>' . "\n";
                echo '    <image:loc>' . esc_url($image['url']) . '</image:loc>' . "\n";
                if (!empty($image['title'])) {
                    echo '    <image:title>' . $this->esc_xml($image['title']) . '</image:title>' . "\n";
                }
                if (!empty($image['alt'])) {
                    echo '    <image:caption>' . $this->esc_xml($image['alt']) . '</image:caption>' . "\n";
                }
                echo '  </image:image>' . "\n";
            }
            
            echo '</url>' . "\n";
        }

        echo '</urlset>';
    }

    /**
     * Sitemap taxonomies
     */
    private function render_taxonomy_sitemap($taxonomy) {
        $config = $this->settings['taxonomies'][$taxonomy] ?? array('priority' => '0.6');
        
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
            'number' => $this->settings['max_urls_per_sitemap'],
        ));

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                echo '<url>' . "\n";
                echo '  <loc>' . get_term_link($term) . '</loc>' . "\n";
                echo '  <changefreq>weekly</changefreq>' . "\n";
                echo '  <priority>' . $config['priority'] . '</priority>' . "\n";
                echo '</url>' . "\n";
            }
        }

        echo '</urlset>';
    }

    /**
     * Sitemap images
     */
    private function render_images_sitemap() {
        header('Content-Type: application/xml; charset=utf-8');
        
        global $wpdb;
        
        $images = $wpdb->get_results("
            SELECT ID, post_title, post_modified 
            FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
            AND post_status = 'inherit'
            ORDER BY post_modified DESC
            LIMIT 1000
        ");

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        foreach ($images as $image) {
            $url = wp_get_attachment_url($image->ID);
            $alt = get_post_meta($image->ID, '_wp_attachment_image_alt', true);
            
            echo '<url>' . "\n";
            echo '  <loc>' . esc_url($url) . '</loc>' . "\n";
            echo '  <lastmod>' . date('c', strtotime($image->post_modified)) . '</lastmod>' . "\n";
            echo '  <image:image>' . "\n";
            echo '    <image:loc>' . esc_url($url) . '</image:loc>' . "\n";
            echo '    <image:title>' . $this->esc_xml($image->post_title) . '</image:title>' . "\n";
            if ($alt) {
                echo '    <image:caption>' . $this->esc_xml($alt) . '</image:caption>' . "\n";
            }
            echo '  </image:image>' . "\n";
            echo '</url>' . "\n";
        }

        echo '</urlset>';
    }

    /**
     * llms.txt - Fichier optimisé pour les LLM (ChatGPT, Perplexity, etc.)
     */
    /**
     * Génère le fichier llms.txt (standard ou full)
     */
    private function render_llms_txt($full = false) {
        header('Content-Type: text/plain; charset=utf-8');
        
        $llm = $this->settings['llm'] ?? array();
        $company_name = $llm['company_name'] ?? 'Rendez-vous avec l\'Asie';
        $company_desc = $llm['company_description'] ?? 'Agence de voyage spécialisée dans les circuits sur mesure en Asie.';
        $company_email = $llm['company_email'] ?? 'contact@rdvasie.com';
        $company_phone = $llm['company_phone'] ?? '02 14 00 12 53';
        $desc_length = $full ? 1000 : ($llm['description_length'] ?? 300);
        $voyages_limit = $full ? -1 : ($llm['voyages_limit'] ?? 50);
        $faq_limit = $full ? -1 : ($llm['faq_limit'] ?? 50);
        $articles_limit = $full ? -1 : ($llm['articles_limit'] ?? 20);
        
        // En-tête
        echo "# " . $company_name . "\n";
        echo "> " . $company_desc . "\n\n";
        
        if ($full) {
            echo "---\n";
            echo "Ce fichier contient toutes les informations détaillées pour les assistants IA.\n";
            echo "Version: " . date('Y-m-d H:i:s') . "\n";
            echo "---\n\n";
        }
        
        // À propos
        echo "## À propos\n";
        echo $company_name . " est une agence de voyage française spécialisée dans les circuits sur mesure en Asie.\n";
        echo "Nous proposons des voyages personnalisés en Chine, Vietnam, Cambodge, Laos, Thaïlande, Birmanie, Indonésie, Japon, Corée, Inde, Sri Lanka, Népal et plus encore.\n";
        echo "Chaque voyage est conçu sur mesure selon vos envies, votre budget et votre rythme.\n\n";
        
        // Destinations
        if (!empty($llm['include_destinations'])) {
            echo "## Nos destinations\n";
            $destinations = get_terms(array('taxonomy' => 'tripzzy_trip_destination', 'hide_empty' => true));
            if (!is_wp_error($destinations)) {
                foreach ($destinations as $dest) {
                    echo "### " . $dest->name . "\n";
                    echo "- URL: " . get_term_link($dest) . "\n";
                    echo "- Voyages disponibles: " . $dest->count . "\n";
                    if ($full && !empty($dest->description)) {
                        echo "- Description: " . wp_strip_all_tags($dest->description) . "\n";
                    }
                    echo "\n";
                }
            }
        }
        
        // Types de voyages
        echo "## Types de voyages proposés\n";
        $types = get_terms(array('taxonomy' => 'tripzzy_trip_type', 'hide_empty' => true));
        if (!is_wp_error($types)) {
            foreach ($types as $type) {
                echo "- " . $type->name;
                if ($full && !empty($type->description)) {
                    echo ": " . wp_strip_all_tags($type->description);
                }
                echo "\n";
            }
        }
        echo "\n";
        
        // Voyages
        if (!empty($llm['include_voyages'])) {
            echo "## Nos voyages\n\n";
            $voyages = get_posts(array(
                'post_type' => 'tripzzy',
                'posts_per_page' => $voyages_limit,
                'post_status' => 'publish',
                'orderby' => 'menu_order',
                'order' => 'ASC',
            ));
            
            foreach ($voyages as $voyage) {
                echo "### " . $voyage->post_title . "\n";
                echo "- URL: " . get_permalink($voyage) . "\n";
                
                // Destination
                $dest_terms = wp_get_post_terms($voyage->ID, 'tripzzy_trip_destination');
                if (!empty($dest_terms)) {
                    echo "- Destination: " . $dest_terms[0]->name . "\n";
                }
                
                // Type
                $type_terms = wp_get_post_terms($voyage->ID, 'tripzzy_trip_type');
                if (!empty($type_terms)) {
                    echo "- Type: " . $type_terms[0]->name . "\n";
                }
                
                // Prix (via Tripzzy meta)
                if (!empty($llm['include_prices'])) {
                    $price = get_post_meta($voyage->ID, 'tripzzy_trip_price', true);
                    if (!$price) {
                        $price = get_post_meta($voyage->ID, '_tripzzy_trip_price', true);
                    }
                    if ($price) {
                        echo "- Prix: à partir de " . number_format($price, 0, ',', ' ') . " €\n";
                    }
                }
                
                // Durée
                if (!empty($llm['include_durations'])) {
                    $duration = get_post_meta($voyage->ID, 'tripzzy_trip_duration', true);
                    if (!$duration) {
                        $duration = get_post_meta($voyage->ID, '_tripzzy_duration', true);
                    }
                    if ($duration) {
                        echo "- Durée: " . $duration . " jours\n";
                    }
                }
                
                // Description
                if (!empty($llm['include_descriptions'])) {
                    $excerpt = $voyage->post_excerpt;
                    if (empty($excerpt)) {
                        $excerpt = wp_strip_all_tags($voyage->post_content);
                    }
                    $excerpt = preg_replace('/\s+/', ' ', trim($excerpt));
                    if (mb_strlen($excerpt) > $desc_length) {
                        $excerpt = mb_substr($excerpt, 0, $desc_length) . '...';
                    }
                    if (!empty($excerpt)) {
                        echo "- Description: " . $excerpt . "\n";
                    }
                }
                
                echo "\n";
            }
        }
        
        // FAQ
        if (!empty($llm['include_faq'])) {
            echo "## Questions fréquentes (FAQ)\n\n";
            $faqs = get_posts(array(
                'post_type' => 'avada_faq',
                'posts_per_page' => $faq_limit,
                'post_status' => 'publish',
                'orderby' => 'menu_order',
                'order' => 'ASC',
            ));
            
            foreach ($faqs as $faq) {
                echo "### " . $faq->post_title . "\n";
                $answer = wp_strip_all_tags($faq->post_content);
                $answer = preg_replace('/\s+/', ' ', trim($answer));
                if (!$full && mb_strlen($answer) > $desc_length) {
                    $answer = mb_substr($answer, 0, $desc_length) . '...';
                }
                echo $answer . "\n";
                echo "En savoir plus: " . get_permalink($faq) . "\n\n";
            }
        }
        
        // Articles de blog
        if (!empty($llm['include_articles'])) {
            echo "## Articles du blog\n\n";
            $articles = get_posts(array(
                'post_type' => 'post',
                'posts_per_page' => $articles_limit,
                'post_status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC',
            ));
            
            foreach ($articles as $article) {
                echo "### " . $article->post_title . "\n";
                echo "- URL: " . get_permalink($article) . "\n";
                echo "- Date: " . get_the_date('d/m/Y', $article) . "\n";
                
                $cats = get_the_category($article->ID);
                if (!empty($cats)) {
                    echo "- Catégorie: " . $cats[0]->name . "\n";
                }
                
                if ($full || !empty($llm['include_descriptions'])) {
                    $excerpt = $article->post_excerpt;
                    if (empty($excerpt)) {
                        $excerpt = wp_strip_all_tags($article->post_content);
                    }
                    $excerpt = preg_replace('/\s+/', ' ', trim($excerpt));
                    if (mb_strlen($excerpt) > $desc_length) {
                        $excerpt = mb_substr($excerpt, 0, $desc_length) . '...';
                    }
                    if (!empty($excerpt)) {
                        echo "- Résumé: " . $excerpt . "\n";
                    }
                }
                echo "\n";
            }
        }
        
        // Contact
        echo "## Contact\n";
        echo "- Site web: " . home_url('/') . "\n";
        echo "- Email: " . $company_email . "\n";
        echo "- Téléphone: " . $company_phone . "\n";
        echo "- Demande de devis: " . home_url('/demande-de-devis/') . "\n\n";
        
        // Pages importantes
        echo "## Pages importantes\n";
        echo "- Accueil: " . home_url('/') . "\n";
        echo "- Tous les voyages: " . home_url('/voyage-en-asie/') . "\n";
        echo "- FAQ: " . home_url('/faq/') . "\n";
        echo "- Blog: " . home_url('/blog/') . "\n";
        echo "- Demande de devis: " . home_url('/demande-de-devis/') . "\n";
        echo "- Contact: " . home_url('/contact/') . "\n";
        echo "- Plan du site: " . home_url('/plan-du-site/') . "\n";
        echo "- Espace client: " . home_url('/espace-client/') . "\n\n";
        
        // Liens vers autres ressources
        echo "## Autres ressources\n";
        echo "- Sitemap XML: " . home_url('/sitemap.xml') . "\n";
        if ($full) {
            echo "- Version courte: " . home_url('/llms.txt') . "\n";
        } else {
            echo "- Version complète: " . home_url('/llms-full.txt') . "\n";
        }
        echo "- Plan du site HTML: " . home_url('/plan-du-site/') . "\n";
    }

    /**
     * Shortcode Sitemap HTML
     */
    public function shortcode_sitemap_html($atts) {
        $atts = shortcode_atts(array(
            'show_voyages' => 'yes',
            'show_destinations' => 'yes',
            'show_articles' => 'yes',
            'show_pages' => 'yes',
        ), $atts);
        
        ob_start();
        ?>
        <div class="rdv-sitemap-html">
            <style>
                @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap');
                
                .rdv-sitemap-html {
                    font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
                    max-width: 1200px;
                    margin: 0 auto;
                    padding: 20px 0;
                    color: #2d3436;
                }
                
                /* En-tête de section */
                .rdv-sitemap-section {
                    margin-bottom: 60px;
                    position: relative;
                }
                
                .rdv-sitemap-section-header {
                    display: flex;
                    align-items: center;
                    gap: 16px;
                    margin-bottom: 32px;
                    padding-bottom: 20px;
                    border-bottom: 1px solid #e8e8e8;
                }
                
                .rdv-sitemap-section-icon {
                    width: 48px;
                    height: 48px;
                    background: linear-gradient(135deg, #e85d04 0%, #f48c06 100%);
                    border-radius: 12px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                }
                
                .rdv-sitemap-section-icon svg {
                    width: 24px;
                    height: 24px;
                    fill: white;
                }
                
                .rdv-sitemap-section h2 {
                    font-size: 1.75rem;
                    font-weight: 700;
                    color: #1a1a2e;
                    margin: 0;
                    letter-spacing: -0.02em;
                }
                
                .rdv-sitemap-section h2 span {
                    color: #e85d04;
                }
                
                /* Cartes destinations */
                .rdv-sitemap-destinations {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                    gap: 16px;
                }
                
                .rdv-sitemap-dest-card {
                    background: #fff;
                    border: 1px solid #e8e8e8;
                    border-radius: 16px;
                    padding: 24px 20px;
                    text-align: center;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    position: relative;
                    overflow: hidden;
                }
                
                .rdv-sitemap-dest-card::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 4px;
                    background: linear-gradient(90deg, #e85d04, #f48c06);
                    opacity: 0;
                    transition: opacity 0.3s ease;
                }
                
                .rdv-sitemap-dest-card:hover {
                    transform: translateY(-4px);
                    box-shadow: 0 12px 40px rgba(232, 93, 4, 0.15);
                    border-color: transparent;
                }
                
                .rdv-sitemap-dest-card:hover::before {
                    opacity: 1;
                }
                
                .rdv-sitemap-dest-card a {
                    color: #1a1a2e;
                    text-decoration: none;
                    font-weight: 600;
                    font-size: 1.05rem;
                    display: block;
                    transition: color 0.2s ease;
                }
                
                .rdv-sitemap-dest-card:hover a {
                    color: #e85d04;
                }
                
                .rdv-sitemap-dest-card .count {
                    display: inline-block;
                    margin-top: 12px;
                    padding: 6px 14px;
                    background: #fef3e7;
                    color: #e85d04;
                    font-size: 0.85rem;
                    font-weight: 500;
                    border-radius: 20px;
                }
                
                /* Sous-sections voyages */
                .rdv-sitemap-subsection {
                    margin-bottom: 40px;
                }
                
                .rdv-sitemap-subsection h3 {
                    font-size: 1.2rem;
                    font-weight: 600;
                    color: #1a1a2e;
                    margin: 0 0 20px 0;
                    padding-left: 16px;
                    border-left: 3px solid #e85d04;
                }
                
                /* Liste de liens */
                .rdv-sitemap-list {
                    list-style: none;
                    padding: 0;
                    margin: 0;
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                    gap: 8px 24px;
                }
                
                .rdv-sitemap-list li {
                    position: relative;
                }
                
                .rdv-sitemap-list a {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    padding: 12px 16px;
                    color: #4a4a5a;
                    text-decoration: none;
                    font-size: 0.95rem;
                    border-radius: 8px;
                    transition: all 0.2s ease;
                    background: transparent;
                }
                
                .rdv-sitemap-list a::before {
                    content: '';
                    width: 6px;
                    height: 6px;
                    background: #d1d1d1;
                    border-radius: 50%;
                    flex-shrink: 0;
                    transition: all 0.2s ease;
                }
                
                .rdv-sitemap-list a:hover {
                    background: #fef8f3;
                    color: #e85d04;
                }
                
                .rdv-sitemap-list a:hover::before {
                    background: #e85d04;
                    transform: scale(1.3);
                }
                
                /* Grille de pages */
                .rdv-sitemap-pages-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                    gap: 12px;
                }
                
                .rdv-sitemap-page-item {
                    background: #fff;
                    border: 1px solid #e8e8e8;
                    border-radius: 10px;
                    overflow: hidden;
                    transition: all 0.2s ease;
                }
                
                .rdv-sitemap-page-item:hover {
                    border-color: #e85d04;
                    box-shadow: 0 4px 20px rgba(232, 93, 4, 0.1);
                }
                
                .rdv-sitemap-page-item a {
                    display: block;
                    padding: 16px 20px;
                    color: #4a4a5a;
                    text-decoration: none;
                    font-size: 0.95rem;
                    font-weight: 500;
                    transition: color 0.2s ease;
                }
                
                .rdv-sitemap-page-item:hover a {
                    color: #e85d04;
                }
                
                /* Responsive */
                @media (max-width: 768px) {
                    .rdv-sitemap-html {
                        padding: 10px;
                    }
                    
                    .rdv-sitemap-section {
                        margin-bottom: 40px;
                    }
                    
                    .rdv-sitemap-section h2 {
                        font-size: 1.4rem;
                    }
                    
                    .rdv-sitemap-section-icon {
                        width: 40px;
                        height: 40px;
                    }
                    
                    .rdv-sitemap-destinations {
                        grid-template-columns: repeat(2, 1fr);
                        gap: 12px;
                    }
                    
                    .rdv-sitemap-dest-card {
                        padding: 16px 12px;
                    }
                    
                    .rdv-sitemap-list {
                        grid-template-columns: 1fr;
                    }
                    
                    .rdv-sitemap-pages-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>
            
            <?php if ($atts['show_destinations'] === 'yes') : ?>
            <section class="rdv-sitemap-section">
                <div class="rdv-sitemap-section-header">
                    <div class="rdv-sitemap-section-icon">
                        <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                    </div>
                    <h2>Toutes nos <span>destinations</span> en Asie</h2>
                </div>
                <div class="rdv-sitemap-destinations">
                    <?php
                    $destinations = get_terms(array(
                        'taxonomy' => 'tripzzy_trip_destination',
                        'hide_empty' => true,
                        'parent' => 0,
                    ));
                    if (!is_wp_error($destinations)) :
                        foreach ($destinations as $dest) :
                    ?>
                        <div class="rdv-sitemap-dest-card">
                            <a href="<?php echo get_term_link($dest); ?>">
                                <?php echo esc_html($dest->name); ?>
                            </a>
                            <span class="count"><?php echo $dest->count; ?> voyage<?php echo $dest->count > 1 ? 's' : ''; ?></span>
                        </div>
                    <?php 
                        endforeach;
                    endif; 
                    ?>
                </div>
            </section>
            <?php endif; ?>
            
            <?php if ($atts['show_voyages'] === 'yes') : ?>
            <section class="rdv-sitemap-section">
                <div class="rdv-sitemap-section-header">
                    <div class="rdv-sitemap-section-icon">
                        <svg viewBox="0 0 24 24"><path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/></svg>
                    </div>
                    <h2>Tous nos <span>voyages</span></h2>
                </div>
                <?php
                $destinations = get_terms(array(
                    'taxonomy' => 'tripzzy_trip_destination',
                    'hide_empty' => true,
                ));
                
                if (!is_wp_error($destinations)) :
                    foreach ($destinations as $dest) :
                        $voyages = get_posts(array(
                            'post_type' => 'tripzzy',
                            'posts_per_page' => -1,
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'tripzzy_trip_destination',
                                    'terms' => $dest->term_id,
                                ),
                            ),
                        ));
                        
                        if (!empty($voyages)) :
                ?>
                    <div class="rdv-sitemap-subsection">
                        <h3><?php echo esc_html($dest->name); ?></h3>
                        <ul class="rdv-sitemap-list">
                            <?php foreach ($voyages as $voyage) : ?>
                                <li>
                                    <a href="<?php echo get_permalink($voyage); ?>">
                                        <?php echo esc_html($voyage->post_title); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php 
                        endif;
                    endforeach;
                endif; 
                ?>
            </section>
            <?php endif; ?>
            
            <?php if ($atts['show_articles'] === 'yes') : ?>
            <section class="rdv-sitemap-section">
                <div class="rdv-sitemap-section-header">
                    <div class="rdv-sitemap-section-icon">
                        <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                    </div>
                    <h2>Articles du <span>blog</span></h2>
                </div>
                <?php
                $categories = get_categories(array('hide_empty' => true));
                foreach ($categories as $cat) :
                    $posts = get_posts(array(
                        'category' => $cat->term_id,
                        'posts_per_page' => -1,
                    ));
                    
                    if (!empty($posts)) :
                ?>
                    <div class="rdv-sitemap-subsection">
                        <h3><?php echo esc_html($cat->name); ?></h3>
                        <ul class="rdv-sitemap-list">
                            <?php foreach ($posts as $post) : ?>
                                <li>
                                    <a href="<?php echo get_permalink($post); ?>">
                                        <?php echo esc_html($post->post_title); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </section>
            <?php endif; ?>
            
            <?php if ($atts['show_pages'] === 'yes') : ?>
            <section class="rdv-sitemap-section">
                <div class="rdv-sitemap-section-header">
                    <div class="rdv-sitemap-section-icon">
                        <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                    </div>
                    <h2>Toutes les <span>pages</span></h2>
                </div>
                <div class="rdv-sitemap-pages-grid">
                    <?php
                    $pages = get_pages(array(
                        'sort_column' => 'menu_order',
                        'post_status' => 'publish',
                    ));
                    foreach ($pages as $page) :
                        // Exclure la page sitemap elle-même
                        if ($page->post_name === 'plan-du-site') continue;
                    ?>
                        <div class="rdv-sitemap-page-item">
                            <a href="<?php echo get_permalink($page); ?>">
                                <?php echo esc_html($page->post_title); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Menu admin
     */
    public function add_admin_menu() {
        add_menu_page(
            'RDV Sitemap Pro',
            'Sitemap Pro',
            'manage_options',
            'rdv-sitemap-pro',
            array($this, 'render_admin_page'),
            'dashicons-networking',
            81
        );
        
        add_submenu_page(
            'rdv-sitemap-pro',
            'Sitemap & SEO',
            'Sitemap & SEO',
            'manage_options',
            'rdv-sitemap-pro',
            array($this, 'render_admin_page')
        );
        
        add_submenu_page(
            'rdv-sitemap-pro',
            'LLM Optimizer',
            '🤖 LLM Optimizer',
            'manage_options',
            'rdv-sitemap-llm',
            array($this, 'render_llm_page')
        );
    }

    /**
     * Assets admin
     */
    public function admin_assets($hook) {
        // Charger sur les pages du plugin (sitemap et LLM)
        if (!in_array($hook, array('toplevel_page_rdv-sitemap-pro', 'sitemap-pro_page_rdv-sitemap-llm'))) {
            return;
        }

        wp_enqueue_style('rdv-sitemap-admin', RDV_SITEMAP_URL . 'assets/admin.css', array(), RDV_SITEMAP_VERSION);
        wp_enqueue_script('rdv-sitemap-admin', RDV_SITEMAP_URL . 'assets/admin.js', array('jquery'), RDV_SITEMAP_VERSION, true);
        wp_localize_script('rdv-sitemap-admin', 'rdvSitemap', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rdv_sitemap_nonce'),
        ));
    }

    /**
     * Page admin
     */
    public function render_admin_page() {
        // Statistiques
        $stats = $this->get_stats();
        
        include RDV_SITEMAP_PATH . 'admin/dashboard.php';
    }

    /**
     * Render priority options
     */
    public function render_priority_options($selected) {
        $options = array('1.0', '0.9', '0.8', '0.7', '0.6', '0.5', '0.4', '0.3', '0.2', '0.1');
        foreach ($options as $opt) {
            echo '<option value="' . $opt . '"' . selected($selected, $opt, false) . '>' . $opt . '</option>';
        }
    }

    /**
     * Render changefreq options
     */
    public function render_changefreq_options($selected) {
        $options = array(
            'always' => 'Toujours',
            'hourly' => 'Toutes les heures',
            'daily' => 'Quotidien',
            'weekly' => 'Hebdomadaire',
            'monthly' => 'Mensuel',
            'yearly' => 'Annuel',
            'never' => 'Jamais',
        );
        foreach ($options as $val => $label) {
            echo '<option value="' . $val . '"' . selected($selected, $val, false) . '>' . $label . '</option>';
        }
    }

    /**
     * Obtenir les statistiques
     */
    private function get_stats() {
        $stats = array(
            'total_urls' => 0,
            'voyages' => 0,
            'faqs' => 0,
            'articles' => 0,
            'pages' => 0,
            'destinations' => 0,
            'images' => 0,
        );

        // Compter les voyages
        $count = wp_count_posts('tripzzy');
        $stats['voyages'] = isset($count->publish) ? $count->publish : 0;
        $stats['total_urls'] += $stats['voyages'];

        // Compter les FAQ
        $count = wp_count_posts('avada_faq');
        $stats['faqs'] = isset($count->publish) ? $count->publish : 0;
        $stats['total_urls'] += $stats['faqs'];

        // Compter les articles
        $count = wp_count_posts('post');
        $stats['articles'] = isset($count->publish) ? $count->publish : 0;
        $stats['total_urls'] += $stats['articles'];

        // Compter les pages
        $count = wp_count_posts('page');
        $stats['pages'] = isset($count->publish) ? $count->publish : 0;
        $stats['total_urls'] += $stats['pages'];

        // Compter les destinations
        $destinations = get_terms(array('taxonomy' => 'tripzzy_trip_destination', 'hide_empty' => true));
        $stats['destinations'] = !is_wp_error($destinations) ? count($destinations) : 0;
        $stats['total_urls'] += $stats['destinations'];

        // Compter les images
        global $wpdb;
        $stats['images'] = (int) $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
        ");

        return $stats;
    }

    /**
     * Obtenir les images d'un post
     */
    private function get_post_images($post_id) {
        $images = array();
        
        // Image à la une
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            $images[] = array(
                'url' => wp_get_attachment_url($thumbnail_id),
                'title' => get_the_title($thumbnail_id),
                'alt' => get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true),
            );
        }
        
        // Images dans le contenu (limité à 5)
        $post = get_post($post_id);
        if ($post) {
            preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $post->post_content, $matches);
            $count = 0;
            foreach ($matches[1] as $url) {
                if ($count >= 5) break;
                $images[] = array('url' => $url, 'title' => '', 'alt' => '');
                $count++;
            }
        }
        
        return $images;
    }

    /**
     * Obtenir la dernière modification
     */
    private function get_last_modified($post_type) {
        $post = get_posts(array(
            'post_type' => $post_type,
            'posts_per_page' => 1,
            'orderby' => 'modified',
            'order' => 'DESC',
        ));
        
        if (!empty($post)) {
            return get_the_modified_date('c', $post[0]);
        }
        
        return date('c');
    }

    /**
     * AJAX: Régénérer le sitemap
     */
    public function ajax_regenerate() {
        check_ajax_referer('rdv_sitemap_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }
        
        // Flush les règles de réécriture
        flush_rewrite_rules();
        
        wp_send_json_success(array(
            'message' => 'Sitemap régénéré avec succès !',
            'stats' => $this->get_stats(),
        ));
    }

    /**
     * AJAX: Ping les moteurs de recherche
     */
    public function ajax_ping_search_engines() {
        check_ajax_referer('rdv_sitemap_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }
        
        $results = $this->ping_search_engines();
        
        wp_send_json_success($results);
    }

    /**
     * Ping les moteurs de recherche
     */
    private function ping_search_engines() {
        $sitemap_url = urlencode(home_url('/sitemap.xml'));
        $results = array();
        
        // Google
        $google_url = 'https://www.google.com/ping?sitemap=' . $sitemap_url;
        $response = wp_remote_get($google_url, array('timeout' => 10));
        $results['google'] = !is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200;
        
        // Bing
        $bing_url = 'https://www.bing.com/ping?sitemap=' . $sitemap_url;
        $response = wp_remote_get($bing_url, array('timeout' => 10));
        $results['bing'] = !is_wp_error($response);
        
        return $results;
    }

    /**
     * Auto-ping après publication
     */
    public function auto_ping_on_publish($post_id) {
        if (!$this->settings['auto_ping']) {
            return;
        }
        
        // Éviter les ping multiples
        if (get_transient('rdv_sitemap_ping_lock')) {
            return;
        }
        
        set_transient('rdv_sitemap_ping_lock', true, 300); // 5 min
        
        $this->ping_search_engines();
    }

    /**
     * AJAX: Sauvegarder les paramètres
     */
    public function ajax_save_settings() {
        check_ajax_referer('rdv_sitemap_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }
        
        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        
        // Nettoyer et valider les paramètres
        $clean_settings = $this->get_default_settings();
        
        $clean_settings['enable_xml'] = !empty($settings['enable_xml']);
        $clean_settings['enable_html'] = !empty($settings['enable_html']);
        $clean_settings['enable_llms_txt'] = !empty($settings['enable_llms_txt']);
        $clean_settings['enable_images'] = !empty($settings['enable_images']);
        $clean_settings['auto_ping'] = !empty($settings['auto_ping']);
        $clean_settings['homepage_priority'] = sanitize_text_field($settings['homepage_priority'] ?? '1.0');
        $clean_settings['max_urls_per_sitemap'] = intval($settings['max_urls_per_sitemap'] ?? 1000);
        
        // Post types
        if (!empty($settings['post_types']) && is_array($settings['post_types'])) {
            foreach ($settings['post_types'] as $type => $config) {
                if (isset($clean_settings['post_types'][$type])) {
                    $clean_settings['post_types'][$type]['enabled'] = !empty($config['enabled']);
                    $clean_settings['post_types'][$type]['priority'] = sanitize_text_field($config['priority'] ?? '0.5');
                    $clean_settings['post_types'][$type]['changefreq'] = sanitize_text_field($config['changefreq'] ?? 'weekly');
                }
            }
        }
        
        // Taxonomies
        if (!empty($settings['taxonomies']) && is_array($settings['taxonomies'])) {
            foreach ($settings['taxonomies'] as $tax => $config) {
                if (isset($clean_settings['taxonomies'][$tax])) {
                    $clean_settings['taxonomies'][$tax]['enabled'] = !empty($config['enabled']);
                    $clean_settings['taxonomies'][$tax]['priority'] = sanitize_text_field($config['priority'] ?? '0.6');
                }
            }
        }
        
        // Exclusions
        if (!empty($settings['excluded_ids'])) {
            $ids = explode(',', $settings['excluded_ids']);
            $clean_settings['excluded_ids'] = array_map('intval', array_filter($ids));
        }
        
        update_option('rdv_sitemap_settings', $clean_settings);
        $this->settings = $clean_settings;
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        wp_send_json_success(array(
            'message' => 'Paramètres sauvegardés !',
            'stats' => $this->get_stats(),
        ));
    }

    /**
     * AJAX: Récupérer la liste des URLs par type
     */
    public function ajax_get_urls() {
        check_ajax_referer('rdv_sitemap_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }
        
        $type = sanitize_text_field($_POST['type'] ?? '');
        $is_taxonomy = !empty($_POST['taxonomy']);
        
        if (empty($type)) {
            wp_send_json_error('Type manquant');
        }
        
        $excluded_ids = $this->settings['excluded_ids'] ?? array();
        $urls = array();
        
        if ($is_taxonomy) {
            // Taxonomie (destinations, etc.)
            $terms = get_terms(array(
                'taxonomy' => $type,
                'hide_empty' => true,
            ));
            
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $urls[] = array(
                        'id' => $term->term_id,
                        'title' => $term->name,
                        'url' => get_term_link($term),
                        'count' => $term->count . ' voyage(s)',
                        'included' => !in_array($term->term_id, $excluded_ids),
                        'type' => 'term',
                    );
                }
            }
        } else {
            // Post type
            $posts = get_posts(array(
                'post_type' => $type,
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
            ));
            
            foreach ($posts as $post) {
                $urls[] = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => get_permalink($post),
                    'date' => get_the_modified_date('d/m/Y', $post),
                    'included' => !in_array($post->ID, $excluded_ids),
                    'type' => 'post',
                );
            }
        }
        
        wp_send_json_success(array(
            'urls' => $urls,
            'total' => count($urls),
            'excluded_count' => count(array_filter($urls, function($u) { return !$u['included']; })),
        ));
    }

    /**
     * AJAX: Sauvegarder les exclusions
     */
    public function ajax_save_exclusions() {
        check_ajax_referer('rdv_sitemap_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }
        
        // IDs à ajouter aux exclusions
        $to_exclude = isset($_POST['to_exclude']) ? $_POST['to_exclude'] : array();
        // IDs à retirer des exclusions
        $to_include = isset($_POST['to_include']) ? $_POST['to_include'] : array();
        
        // Nettoyer les IDs
        if (is_array($to_exclude)) {
            $to_exclude = array_map('intval', array_filter($to_exclude));
        } else {
            $to_exclude = array();
        }
        
        if (is_array($to_include)) {
            $to_include = array_map('intval', array_filter($to_include));
        } else {
            $to_include = array();
        }
        
        // Récupérer les exclusions existantes
        $current_excluded = $this->settings['excluded_ids'] ?? array();
        
        // Fusionner : ajouter les nouveaux exclus, retirer ceux à inclure
        $current_excluded = array_merge($current_excluded, $to_exclude);
        $current_excluded = array_diff($current_excluded, $to_include);
        $current_excluded = array_unique(array_values($current_excluded));
        
        // Mettre à jour les settings
        $this->settings['excluded_ids'] = $current_excluded;
        update_option('rdv_sitemap_settings', $this->settings);
        
        wp_send_json_success(array(
            'message' => 'Exclusions sauvegardées !',
            'excluded_count' => count($current_excluded),
            'stats' => $this->get_stats(),
        ));
    }

    /**
     * Render la page LLM Optimizer
     */
    public function render_llm_page() {
        $llm = $this->settings['llm'] ?? array();
        ?>
        <div class="wrap rdv-sitemap-wrap">
            <div class="rdv-sitemap-header llm-header">
                <h1>
                    <span class="dashicons dashicons-superhero"></span>
                    LLM Optimizer
                </h1>
                <p class="description">Optimisez votre site pour ChatGPT, Claude, Perplexity et autres assistants IA</p>
            </div>

            <!-- Statut actuel -->
            <div class="llm-status-cards">
                <div class="llm-status-card active">
                    <div class="status-icon">✅</div>
                    <div class="status-content">
                        <h3>llms.txt</h3>
                        <p>Fichier standard pour les IA</p>
                        <a href="<?php echo home_url('/llms.txt'); ?>" target="_blank" class="status-link">
                            Voir le fichier <span class="dashicons dashicons-external"></span>
                        </a>
                    </div>
                </div>
                
                <div class="llm-status-card active">
                    <div class="status-icon">✅</div>
                    <div class="status-content">
                        <h3>llms-full.txt</h3>
                        <p>Version complète détaillée</p>
                        <a href="<?php echo home_url('/llms-full.txt'); ?>" target="_blank" class="status-link">
                            Voir le fichier <span class="dashicons dashicons-external"></span>
                        </a>
                    </div>
                </div>
                
                <div class="llm-status-card <?php echo !empty($llm['enable_schema']) ? 'active' : 'inactive'; ?>">
                    <div class="status-icon"><?php echo !empty($llm['enable_schema']) ? '✅' : '⏸️'; ?></div>
                    <div class="status-content">
                        <h3>Schema.org</h3>
                        <p>Données structurées JSON-LD</p>
                        <span class="status-badge"><?php echo !empty($llm['enable_schema']) ? 'Actif' : 'Inactif'; ?></span>
                    </div>
                </div>
                
                <div class="llm-status-card info">
                    <div class="status-icon">📊</div>
                    <div class="status-content">
                        <h3>Contenu indexé</h3>
                        <p>
                            <?php echo wp_count_posts('tripzzy')->publish; ?> voyages, 
                            <?php echo wp_count_posts('avada_faq')->publish; ?> FAQ,
                            <?php echo wp_count_posts('post')->publish; ?> articles
                        </p>
                    </div>
                </div>
            </div>

            <!-- Formulaire de paramètres -->
            <div class="rdv-sitemap-card">
                <div class="card-header">
                    <h2><span class="dashicons dashicons-admin-settings"></span> Configuration</h2>
                </div>
                <div class="card-body">
                    <form id="llm-settings-form">
                        <div class="llm-settings-grid">
                            <!-- Informations de l'entreprise -->
                            <div class="settings-section">
                                <h3>🏢 Informations de l'entreprise</h3>
                                
                                <div class="form-group">
                                    <label for="company_name">Nom de l'entreprise</label>
                                    <input type="text" id="company_name" name="company_name" 
                                        value="<?php echo esc_attr($llm['company_name'] ?? 'Rendez-vous avec l\'Asie'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="company_description">Description courte</label>
                                    <textarea id="company_description" name="company_description" rows="2"><?php echo esc_textarea($llm['company_description'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="company_email">Email</label>
                                        <input type="email" id="company_email" name="company_email" 
                                            value="<?php echo esc_attr($llm['company_email'] ?? 'contact@rdvasie.com'); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="company_phone">Téléphone</label>
                                        <input type="text" id="company_phone" name="company_phone" 
                                            value="<?php echo esc_attr($llm['company_phone'] ?? '02 14 00 12 53'); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Contenu à inclure -->
                            <div class="settings-section">
                                <h3>📝 Contenu à indexer</h3>
                                
                                <label class="toggle-option">
                                    <input type="checkbox" name="include_voyages" value="1" <?php checked($llm['include_voyages'] ?? true); ?>>
                                    <span class="toggle-slider"></span>
                                    <span class="toggle-label">Inclure les voyages</span>
                                </label>
                                
                                <label class="toggle-option">
                                    <input type="checkbox" name="include_destinations" value="1" <?php checked($llm['include_destinations'] ?? true); ?>>
                                    <span class="toggle-slider"></span>
                                    <span class="toggle-label">Inclure les destinations</span>
                                </label>
                                
                                <label class="toggle-option">
                                    <input type="checkbox" name="include_faq" value="1" <?php checked($llm['include_faq'] ?? true); ?>>
                                    <span class="toggle-slider"></span>
                                    <span class="toggle-label">Inclure les FAQ</span>
                                </label>
                                
                                <label class="toggle-option">
                                    <input type="checkbox" name="include_articles" value="1" <?php checked($llm['include_articles'] ?? true); ?>>
                                    <span class="toggle-slider"></span>
                                    <span class="toggle-label">Inclure les articles du blog</span>
                                </label>
                            </div>

                            <!-- Détails à afficher -->
                            <div class="settings-section">
                                <h3>📋 Détails à afficher</h3>
                                
                                <label class="toggle-option">
                                    <input type="checkbox" name="include_prices" value="1" <?php checked($llm['include_prices'] ?? true); ?>>
                                    <span class="toggle-slider"></span>
                                    <span class="toggle-label">Afficher les prix</span>
                                </label>
                                
                                <label class="toggle-option">
                                    <input type="checkbox" name="include_durations" value="1" <?php checked($llm['include_durations'] ?? true); ?>>
                                    <span class="toggle-slider"></span>
                                    <span class="toggle-label">Afficher les durées</span>
                                </label>
                                
                                <label class="toggle-option">
                                    <input type="checkbox" name="include_descriptions" value="1" <?php checked($llm['include_descriptions'] ?? true); ?>>
                                    <span class="toggle-slider"></span>
                                    <span class="toggle-label">Afficher les descriptions</span>
                                </label>
                                
                                <div class="form-group" style="margin-top:15px;">
                                    <label for="description_length">Longueur max des descriptions</label>
                                    <select id="description_length" name="description_length">
                                        <option value="200" <?php selected($llm['description_length'] ?? 300, 200); ?>>200 caractères</option>
                                        <option value="300" <?php selected($llm['description_length'] ?? 300, 300); ?>>300 caractères</option>
                                        <option value="500" <?php selected($llm['description_length'] ?? 300, 500); ?>>500 caractères</option>
                                        <option value="1000" <?php selected($llm['description_length'] ?? 300, 1000); ?>>1000 caractères</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Limites -->
                            <div class="settings-section">
                                <h3>🔢 Limites</h3>
                                
                                <div class="form-group">
                                    <label for="voyages_limit">Nombre de voyages (llms.txt)</label>
                                    <select id="voyages_limit" name="voyages_limit">
                                        <option value="20" <?php selected($llm['voyages_limit'] ?? 50, 20); ?>>20</option>
                                        <option value="50" <?php selected($llm['voyages_limit'] ?? 50, 50); ?>>50</option>
                                        <option value="100" <?php selected($llm['voyages_limit'] ?? 50, 100); ?>>100</option>
                                        <option value="-1" <?php selected($llm['voyages_limit'] ?? 50, -1); ?>>Tous</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="faq_limit">Nombre de FAQ</label>
                                    <select id="faq_limit" name="faq_limit">
                                        <option value="20" <?php selected($llm['faq_limit'] ?? 50, 20); ?>>20</option>
                                        <option value="50" <?php selected($llm['faq_limit'] ?? 50, 50); ?>>50</option>
                                        <option value="100" <?php selected($llm['faq_limit'] ?? 50, 100); ?>>100</option>
                                        <option value="-1" <?php selected($llm['faq_limit'] ?? 50, -1); ?>>Toutes</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="articles_limit">Nombre d'articles</label>
                                    <select id="articles_limit" name="articles_limit">
                                        <option value="10" <?php selected($llm['articles_limit'] ?? 20, 10); ?>>10</option>
                                        <option value="20" <?php selected($llm['articles_limit'] ?? 20, 20); ?>>20</option>
                                        <option value="50" <?php selected($llm['articles_limit'] ?? 20, 50); ?>>50</option>
                                        <option value="-1" <?php selected($llm['articles_limit'] ?? 20, -1); ?>>Tous</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Fonctionnalités avancées -->
                            <div class="settings-section full-width">
                                <h3>⚡ Fonctionnalités avancées</h3>
                                
                                <div class="advanced-options">
                                    <label class="toggle-option">
                                        <input type="checkbox" name="enable_schema" value="1" <?php checked($llm['enable_schema'] ?? true); ?>>
                                        <span class="toggle-slider"></span>
                                        <span class="toggle-label">
                                            <strong>Schema.org JSON-LD</strong>
                                            <small>Ajoute des données structurées sur les pages de voyage (meilleur référencement)</small>
                                        </span>
                                    </label>
                                    
                                    <label class="toggle-option">
                                        <input type="checkbox" name="enable_robots_hint" value="1" <?php checked($llm['enable_robots_hint'] ?? true); ?>>
                                        <span class="toggle-slider"></span>
                                        <span class="toggle-label">
                                            <strong>Ajouter référence dans robots.txt</strong>
                                            <small>Ajoute automatiquement la référence au llms.txt dans robots.txt</small>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-actions">
                            <button type="submit" class="button button-primary button-large">
                                <span class="dashicons dashicons-saved"></span>
                                Enregistrer
                            </button>
                            <button type="button" id="preview-llms" class="button button-secondary button-large">
                                <span class="dashicons dashicons-visibility"></span>
                                Prévisualiser
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Aperçu -->
            <div class="rdv-sitemap-card" id="preview-card" style="display:none;">
                <div class="card-header">
                    <h2><span class="dashicons dashicons-visibility"></span> Aperçu du llms.txt</h2>
                </div>
                <div class="card-body">
                    <pre id="llms-preview" class="llms-preview"></pre>
                </div>
            </div>

            <!-- Guide -->
            <div class="rdv-sitemap-card">
                <div class="card-header">
                    <h2><span class="dashicons dashicons-info"></span> Comment ça marche ?</h2>
                </div>
                <div class="card-body">
                    <div class="guide-content">
                        <div class="guide-item">
                            <h4>📄 llms.txt</h4>
                            <p>Un fichier texte standardisé que les IA (ChatGPT, Claude, Perplexity...) peuvent lire pour comprendre votre site. 
                            Il contient une description de votre activité, vos destinations, voyages et FAQ.</p>
                            <code><?php echo home_url('/llms.txt'); ?></code>
                        </div>
                        
                        <div class="guide-item">
                            <h4>📚 llms-full.txt</h4>
                            <p>Version complète avec TOUS les contenus et descriptions détaillées. Idéal pour les IA qui veulent des informations exhaustives.</p>
                            <code><?php echo home_url('/llms-full.txt'); ?></code>
                        </div>
                        
                        <div class="guide-item">
                            <h4>🔗 Schema.org</h4>
                            <p>Des données structurées JSON-LD ajoutées automatiquement sur vos pages de voyage. 
                            Améliore le SEO et permet aux moteurs de recherche et IA de mieux comprendre vos offres.</p>
                        </div>
                        
                        <div class="guide-item">
                            <h4>🤖 Compatible avec</h4>
                            <p>ChatGPT, Claude, Perplexity, Google SGE, Bing Chat, et tous les assistants IA modernes.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Sauvegarder les paramètres LLM
            $('#llm-settings-form').on('submit', function(e) {
                e.preventDefault();
                
                var $btn = $(this).find('button[type="submit"]');
                var originalHTML = $btn.html();
                $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Sauvegarde...');
                
                var formData = {
                    company_name: $('#company_name').val(),
                    company_description: $('#company_description').val(),
                    company_email: $('#company_email').val(),
                    company_phone: $('#company_phone').val(),
                    include_voyages: $('input[name="include_voyages"]').is(':checked'),
                    include_destinations: $('input[name="include_destinations"]').is(':checked'),
                    include_faq: $('input[name="include_faq"]').is(':checked'),
                    include_articles: $('input[name="include_articles"]').is(':checked'),
                    include_prices: $('input[name="include_prices"]').is(':checked'),
                    include_durations: $('input[name="include_durations"]').is(':checked'),
                    include_descriptions: $('input[name="include_descriptions"]').is(':checked'),
                    description_length: $('#description_length').val(),
                    voyages_limit: $('#voyages_limit').val(),
                    faq_limit: $('#faq_limit').val(),
                    articles_limit: $('#articles_limit').val(),
                    enable_schema: $('input[name="enable_schema"]').is(':checked'),
                    enable_robots_hint: $('input[name="enable_robots_hint"]').is(':checked')
                };
                
                $.post(ajaxurl, {
                    action: 'rdv_sitemap_save_llm_settings',
                    nonce: '<?php echo wp_create_nonce('rdv_sitemap_nonce'); ?>',
                    settings: formData
                }, function(response) {
                    $btn.prop('disabled', false).html(originalHTML);
                    if (response.success) {
                        alert('✅ ' + response.data.message);
                        location.reload();
                    } else {
                        alert('❌ ' + (response.data || 'Erreur'));
                    }
                });
            });
            
            // Prévisualiser
            $('#preview-llms').on('click', function() {
                var $card = $('#preview-card');
                var $preview = $('#llms-preview');
                
                $preview.html('<span class="dashicons dashicons-update spin"></span> Chargement...');
                $card.slideDown();
                
                $.post(ajaxurl, {
                    action: 'rdv_sitemap_preview_llms',
                    nonce: '<?php echo wp_create_nonce('rdv_sitemap_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $preview.text(response.data.content);
                    } else {
                        $preview.text('Erreur: ' + response.data);
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Sauvegarder les paramètres LLM
     */
    public function ajax_save_llm_settings() {
        check_ajax_referer('rdv_sitemap_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }
        
        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        
        $this->settings['llm'] = array(
            'enable_llms_txt' => true,
            'enable_llms_full' => true,
            'enable_schema' => !empty($settings['enable_schema']),
            'enable_robots_hint' => !empty($settings['enable_robots_hint']),
            'company_name' => sanitize_text_field($settings['company_name'] ?? ''),
            'company_description' => sanitize_textarea_field($settings['company_description'] ?? ''),
            'company_email' => sanitize_email($settings['company_email'] ?? ''),
            'company_phone' => sanitize_text_field($settings['company_phone'] ?? ''),
            'include_voyages' => !empty($settings['include_voyages']),
            'include_destinations' => !empty($settings['include_destinations']),
            'include_faq' => !empty($settings['include_faq']),
            'include_articles' => !empty($settings['include_articles']),
            'include_prices' => !empty($settings['include_prices']),
            'include_durations' => !empty($settings['include_durations']),
            'include_descriptions' => !empty($settings['include_descriptions']),
            'voyages_limit' => intval($settings['voyages_limit'] ?? 50),
            'faq_limit' => intval($settings['faq_limit'] ?? 50),
            'articles_limit' => intval($settings['articles_limit'] ?? 20),
            'description_length' => intval($settings['description_length'] ?? 300),
        );
        
        update_option('rdv_sitemap_settings', $this->settings);
        
        // Mettre à jour robots.txt si demandé
        if (!empty($settings['enable_robots_hint'])) {
            $this->update_robots_txt();
        }
        
        wp_send_json_success(array('message' => 'Paramètres LLM sauvegardés !'));
    }

    /**
     * AJAX: Prévisualiser le llms.txt
     */
    public function ajax_preview_llms() {
        check_ajax_referer('rdv_sitemap_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }
        
        ob_start();
        $this->render_llms_txt(false);
        $content = ob_get_clean();
        
        wp_send_json_success(array('content' => $content));
    }

    /**
     * Met à jour le robots.txt virtuellement
     */
    private function update_robots_txt() {
        // WordPress utilise un robots.txt virtuel, on va ajouter nos lignes via le filtre
        add_filter('robots_txt', array($this, 'add_llms_to_robots'), 10, 2);
    }

    /**
     * Filtre pour ajouter llms.txt au robots.txt
     */
    public function add_llms_to_robots($output, $public) {
        $llm = $this->settings['llm'] ?? array();
        
        if (!empty($llm['enable_robots_hint'])) {
            $output .= "\n# LLM Optimization\n";
            $output .= "# Pour les assistants IA (ChatGPT, Claude, Perplexity...)\n";
            $output .= "Sitemap: " . home_url('/llms.txt') . "\n";
            $output .= "Sitemap: " . home_url('/llms-full.txt') . "\n";
        }
        
        return $output;
    }

    /**
     * Output Schema.org JSON-LD pour les voyages
     */
    public function output_schema_jsonld() {
        $llm = $this->settings['llm'] ?? array();
        
        if (empty($llm['enable_schema'])) {
            return;
        }
        
        // Seulement sur les pages de voyage
        if (!is_singular('tripzzy')) {
            return;
        }
        
        global $post;
        
        $company_name = $llm['company_name'] ?? 'Rendez-vous avec l\'Asie';
        $company_email = $llm['company_email'] ?? 'contact@rdvasie.com';
        $company_phone = $llm['company_phone'] ?? '02 14 00 12 53';
        
        // Récupérer les données du voyage
        $destinations = wp_get_post_terms($post->ID, 'tripzzy_trip_destination');
        $dest_name = !empty($destinations) ? $destinations[0]->name : '';
        
        $price = get_post_meta($post->ID, 'tripzzy_trip_price', true);
        if (!$price) $price = get_post_meta($post->ID, '_tripzzy_trip_price', true);
        
        $duration = get_post_meta($post->ID, 'tripzzy_trip_duration', true);
        if (!$duration) $duration = get_post_meta($post->ID, '_tripzzy_duration', true);
        
        $thumbnail = get_the_post_thumbnail_url($post->ID, 'large');
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'TouristTrip',
            'name' => get_the_title(),
            'description' => wp_strip_all_tags(get_the_excerpt()),
            'url' => get_permalink(),
            'touristType' => 'Adventure tourism',
            'provider' => array(
                '@type' => 'TravelAgency',
                'name' => $company_name,
                'url' => home_url('/'),
                'email' => $company_email,
                'telephone' => $company_phone,
            ),
        );
        
        if ($dest_name) {
            $schema['itinerary'] = array(
                '@type' => 'ItemList',
                'name' => 'Destinations',
                'itemListElement' => array(
                    array(
                        '@type' => 'Place',
                        'name' => $dest_name,
                    ),
                ),
            );
        }
        
        if ($price) {
            $schema['offers'] = array(
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => 'EUR',
                'availability' => 'https://schema.org/InStock',
            );
        }
        
        if ($duration) {
            $schema['duration'] = 'P' . intval($duration) . 'D';
        }
        
        if ($thumbnail) {
            $schema['image'] = $thumbnail;
        }
        
        echo '<script type="application/ld+json">' . "\n";
        echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        echo "\n</script>\n";
    }
}

// Ajouter le filtre robots.txt au chargement
add_action('init', function() {
    $settings = get_option('rdv_sitemap_settings', array());
    $llm = $settings['llm'] ?? array();
    
    if (!empty($llm['enable_robots_hint'])) {
        add_filter('robots_txt', function($output, $public) use ($llm) {
            $output .= "\n# LLM Optimization\n";
            $output .= "# Pour les assistants IA (ChatGPT, Claude, Perplexity...)\n";
            $output .= "Sitemap: " . home_url('/llms.txt') . "\n";
            $output .= "Sitemap: " . home_url('/llms-full.txt') . "\n";
            return $output;
        }, 10, 2);
    }
});

// Initialiser le plugin
new RDV_Sitemap_Pro();

