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
        
        // Rewrite rules pour les sitemaps
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_sitemap_request'));
        add_filter('robots_txt', array($this, 'filter_robots_txt'), 10, 2);
        
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
     * Paramètres par défaut
     */
    private function get_default_settings() {
        return array(
            'enable_xml' => true,
            'enable_html' => true,
            'enable_llms_txt' => true,
            'enable_images' => true,
            'auto_ping' => true,
            'robots_manage' => true,
            'robots_add_sitemap' => true,
            'robots_extra_rules' => '',
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
                $this->render_llms_txt();
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
        echo '<?xml-stylesheet type="text/xsl" href="' . RDV_SITEMAP_URL . 'assets/sitemap-style.xsl"?>' . "\n";
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
        echo '<?xml-stylesheet type="text/xsl" href="' . RDV_SITEMAP_URL . 'assets/sitemap-style.xsl"?>' . "\n";
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
                    echo '    <image:title>' . esc_html($image['title']) . '</image:title>' . "\n";
                }
                if (!empty($image['alt'])) {
                    echo '    <image:caption>' . esc_html($image['alt']) . '</image:caption>' . "\n";
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
        echo '<?xml-stylesheet type="text/xsl" href="' . RDV_SITEMAP_URL . 'assets/sitemap-style.xsl"?>' . "\n";
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
            echo '    <image:title>' . esc_html($image->post_title) . '</image:title>' . "\n";
            if ($alt) {
                echo '    <image:caption>' . esc_html($alt) . '</image:caption>' . "\n";
            }
            echo '  </image:image>' . "\n";
            echo '</url>' . "\n";
        }

        echo '</urlset>';
    }

    /**
     * llms.txt - Fichier optimisé pour les LLM (ChatGPT, Perplexity, etc.)
     */
    private function render_llms_txt() {
        header('Content-Type: text/plain; charset=utf-8');
        
        $site_name = get_bloginfo('name');
        $site_desc = get_bloginfo('description');
        
        echo "# " . $site_name . "\n";
        echo "> " . $site_desc . "\n\n";
        
        echo "## À propos\n";
        echo "Rendez-vous avec l'Asie est une agence de voyage spécialisée dans les circuits sur mesure en Asie.\n";
        echo "Nous proposons des voyages personnalisés en Chine, Vietnam, Cambodge, Laos, Thaïlande, Birmanie, Japon, Corée et plus encore.\n\n";
        
        echo "## Nos destinations\n";
        $destinations = get_terms(array('taxonomy' => 'tripzzy_trip_destination', 'hide_empty' => true));
        if (!is_wp_error($destinations)) {
            foreach ($destinations as $dest) {
                echo "- [" . $dest->name . "](" . get_term_link($dest) . "): " . $dest->count . " voyage(s)\n";
            }
        }
        echo "\n";
        
        echo "## Types de voyages\n";
        $types = get_terms(array('taxonomy' => 'tripzzy_trip_type', 'hide_empty' => true));
        if (!is_wp_error($types)) {
            foreach ($types as $type) {
                echo "- " . $type->name . "\n";
            }
        }
        echo "\n";
        
        echo "## Voyages populaires\n";
        $voyages = get_posts(array(
            'post_type' => 'tripzzy',
            'posts_per_page' => 20,
            'post_status' => 'publish',
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ));
        
        foreach ($voyages as $voyage) {
            $destinations = wp_get_post_terms($voyage->ID, 'tripzzy_trip_destination');
            $dest_name = !empty($destinations) ? $destinations[0]->name : '';
            echo "- [" . $voyage->post_title . "](" . get_permalink($voyage) . ")";
            if ($dest_name) {
                echo " - " . $dest_name;
            }
            echo "\n";
        }
        echo "\n";
        
        echo "## Questions fréquentes (FAQ)\n";
        $faqs = get_posts(array(
            'post_type' => 'avada_faq',
            'posts_per_page' => 30,
            'post_status' => 'publish',
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ));
        
        foreach ($faqs as $faq) {
            // Le titre est la question
            echo "### " . $faq->post_title . "\n";
            // Le contenu est la réponse (version courte)
            $answer = wp_strip_all_tags($faq->post_content);
            $answer = preg_replace('/\s+/', ' ', $answer); // Nettoyer les espaces
            $answer = mb_substr($answer, 0, 300); // Limiter à 300 caractères
            if (strlen($faq->post_content) > 300) {
                $answer .= '...';
            }
            echo $answer . "\n";
            echo "[En savoir plus](" . get_permalink($faq) . ")\n\n";
        }
        
        echo "## Contact\n";
        echo "- Site web: " . home_url('/') . "\n";
        echo "- Email: contact@rdvasie.com\n";
        echo "- Téléphone: 02 14 00 12 53\n";
        echo "- Demande de devis: " . home_url('/demande-de-devis/') . "\n\n";
        
        echo "## Pages importantes\n";
        echo "- [Accueil](" . home_url('/') . ")\n";
        echo "- [Tous les voyages](" . home_url('/voyages/') . ")\n";
        echo "- [FAQ](" . home_url('/faq/') . ")\n";
        echo "- [Blog](" . home_url('/blog/') . ")\n";
        echo "- [Demande de devis](" . home_url('/demande-de-devis/') . ")\n";
        echo "- [Contact](" . home_url('/contact/') . ")\n";
        echo "- [Plan du site](" . home_url('/plan-du-site/') . ")\n";
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
                .rdv-sitemap-html { font-family: inherit; }
                .rdv-sitemap-section { margin-bottom: 40px; }
                .rdv-sitemap-section h2 { 
                    color: #de5b09; 
                    border-bottom: 2px solid #de5b09; 
                    padding-bottom: 10px;
                    margin-bottom: 20px;
                }
                .rdv-sitemap-section h3 {
                    color: #333;
                    margin: 20px 0 10px;
                }
                .rdv-sitemap-list { 
                    list-style: none; 
                    padding: 0; 
                    margin: 0;
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                    gap: 10px;
                }
                .rdv-sitemap-list li { 
                    padding: 8px 0;
                    border-bottom: 1px solid #eee;
                }
                .rdv-sitemap-list a { 
                    color: #333; 
                    text-decoration: none;
                    transition: color 0.2s;
                }
                .rdv-sitemap-list a:hover { 
                    color: #de5b09; 
                }
                .rdv-sitemap-count {
                    color: #999;
                    font-size: 0.85em;
                    margin-left: 5px;
                }
                .rdv-sitemap-destinations {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                    gap: 15px;
                }
                .rdv-sitemap-dest-card {
                    background: #f9f9f9;
                    border-radius: 8px;
                    padding: 15px;
                    text-align: center;
                    transition: transform 0.2s, box-shadow 0.2s;
                }
                .rdv-sitemap-dest-card:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                }
                .rdv-sitemap-dest-card a {
                    color: #333;
                    text-decoration: none;
                    font-weight: 600;
                }
                .rdv-sitemap-dest-card .count {
                    display: block;
                    color: #de5b09;
                    font-size: 0.9em;
                    margin-top: 5px;
                }
            </style>
            
            <?php if ($atts['show_destinations'] === 'yes') : ?>
            <section class="rdv-sitemap-section">
                <h2>🌏 Nos destinations</h2>
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
                            <span class="count"><?php echo $dest->count; ?> voyage(s)</span>
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
                <h2>✈️ Tous nos voyages</h2>
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
                <?php 
                        endif;
                    endforeach;
                endif; 
                ?>
            </section>
            <?php endif; ?>
            
            <?php if ($atts['show_articles'] === 'yes') : ?>
            <section class="rdv-sitemap-section">
                <h2>📝 Articles du blog</h2>
                <?php
                $categories = get_categories(array('hide_empty' => true));
                foreach ($categories as $cat) :
                    $posts = get_posts(array(
                        'category' => $cat->term_id,
                        'posts_per_page' => -1,
                    ));
                    
                    if (!empty($posts)) :
                ?>
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
                <?php 
                    endif;
                endforeach; 
                ?>
            </section>
            <?php endif; ?>
            
            <?php if ($atts['show_pages'] === 'yes') : ?>
            <section class="rdv-sitemap-section">
                <h2>📄 Pages</h2>
                <ul class="rdv-sitemap-list">
                    <?php
                    $pages = get_pages(array(
                        'sort_column' => 'menu_order',
                        'post_status' => 'publish',
                    ));
                    foreach ($pages as $page) :
                        // Exclure la page sitemap elle-même
                        if ($page->post_name === 'plan-du-site') continue;
                    ?>
                        <li>
                            <a href="<?php echo get_permalink($page); ?>">
                                <?php echo esc_html($page->post_title); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
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
    }

    /**
     * Assets admin
     */
    public function admin_assets($hook) {
        if ($hook !== 'toplevel_page_rdv-sitemap-pro') {
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
     * robots.txt - Ajout des règles liées au sitemap
     */
    public function filter_robots_txt($output, $public) {
        // Ne rien faire si la gestion robots.txt est désactivée
        if (empty($this->settings['robots_manage'])) {
            return $output;
        }

        // S'assurer que la sortie se termine par un saut de ligne
        if (!empty($output) && substr($output, -1) !== "\n") {
            $output .= "\n";
        }

        // Ajouter la ligne Sitemap si demandé
        if (!empty($this->settings['robots_add_sitemap'])) {
            $sitemap_line = 'Sitemap: ' . home_url('/sitemap.xml');

            // Éviter les doublons si l'utilisateur a déjà ajouté la ligne
            if (strpos($output, $sitemap_line) === false) {
                $output .= $sitemap_line . "\n";
            }
        }

        // Ajouter des règles supplémentaires si définies
        if (!empty($this->settings['robots_extra_rules'])) {
            $extra = trim($this->settings['robots_extra_rules']);
            if ($extra !== '') {
                $output .= "\n" . $extra . "\n";
            }
        }

        return $output;
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
        $clean_settings['robots_manage'] = !empty($settings['robots_manage']);
        $clean_settings['robots_add_sitemap'] = !empty($settings['robots_add_sitemap']);
        $clean_settings['robots_extra_rules'] = isset($settings['robots_extra_rules']) ? wp_kses_post($settings['robots_extra_rules']) : '';
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
}

// Initialiser le plugin
new RDV_Sitemap_Pro();

