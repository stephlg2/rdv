<?php
/**
 * Plugin Name: RDV WebP Converter
 * Plugin URI: https://rdvasie.com
 * Description: Convertit automatiquement les images en WebP, permet de modifier les balises alt et renommer les fichiers.
 * Version: 3.1.0
 * Author: RDV Asie
 * License: GPL v2 or later
 * Text Domain: rdv-webp-converter
 */

if (!defined('ABSPATH')) {
    exit;
}

define('RDV_WEBP_VERSION', '3.1.0');
define('RDV_WEBP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RDV_WEBP_PLUGIN_URL', plugin_dir_url(__FILE__));

class RDV_WebP_Converter {

    private static $instance = null;
    private $options = [];
    private $default_options = [
        'quality' => 80,
        'auto_convert' => true,
        'convert_jpeg' => true,
        'convert_png' => true,
        'convert_gif' => false,
        'delete_originals' => false,
        'serve_webp' => true,
        'convert_thumbnails' => true,
        'backup_originals' => false,
    ];

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->options = wp_parse_args(get_option('rdv_webp_options', []), $this->default_options);

        // Vérifier les prérequis
        if (!$this->check_requirements()) {
            add_action('admin_notices', [$this, 'requirements_notice']);
            return;
        }

        // Hooks Admin
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // AJAX
        add_action('wp_ajax_rdv_webp_convert_bulk', [$this, 'ajax_convert_bulk']);
        add_action('wp_ajax_rdv_webp_convert_single', [$this, 'ajax_convert_single']);
        add_action('wp_ajax_rdv_webp_convert_selected', [$this, 'ajax_convert_selected']);
        add_action('wp_ajax_rdv_webp_delete_webp', [$this, 'ajax_delete_webp']);
        add_action('wp_ajax_rdv_webp_get_stats', [$this, 'ajax_get_stats']);
        add_action('wp_ajax_rdv_webp_get_images', [$this, 'ajax_get_images']);
        add_action('wp_ajax_rdv_webp_restore_original', [$this, 'ajax_restore_original']);
        add_action('wp_ajax_rdv_webp_update_alt', [$this, 'ajax_update_alt']);
        add_action('wp_ajax_rdv_webp_rename_file', [$this, 'ajax_rename_file']);
        add_action('wp_ajax_rdv_webp_get_image_details', [$this, 'ajax_get_image_details']);
        add_action('wp_ajax_rdv_webp_bulk_update', [$this, 'ajax_bulk_update']);

        // Conversion automatique
        if ($this->options['auto_convert']) {
            add_filter('wp_handle_upload', [$this, 'convert_on_upload'], 10, 2);
            if ($this->options['convert_thumbnails']) {
                add_filter('wp_generate_attachment_metadata', [$this, 'convert_thumbnails'], 10, 2);
            }
        }
        
        // Servir les WebP
        if ($this->options['serve_webp']) {
            // Filtres WordPress pour intercepter les URLs d'images
            add_filter('wp_get_attachment_image_src', [$this, 'serve_webp'], 10, 4);
            add_filter('wp_calculate_image_srcset', [$this, 'serve_webp_srcset'], 10, 5);
            add_filter('wp_get_attachment_url', [$this, 'serve_webp_url'], 10, 2);
            add_filter('get_the_post_thumbnail_url', [$this, 'serve_webp_url'], 10, 3);
            
            // Remplacer les URLs dans tout le HTML (y compris backgrounds CSS)
            if (!is_admin()) {
                add_action('template_redirect', [$this, 'start_html_buffer'], 1);
                add_action('shutdown', [$this, 'end_html_buffer'], 999);
                add_filter('the_content', [$this, 'replace_images_in_content'], 999);
                add_filter('post_thumbnail_html', [$this, 'replace_images_in_content'], 999);
                add_filter('widget_text', [$this, 'replace_images_in_content'], 999);
                add_filter('get_header_image_tag', [$this, 'replace_images_in_content'], 999);
                add_filter('wp_get_attachment_image', [$this, 'replace_images_in_content'], 999);
                
                // Filtre spécifique pour Tripzzy qui génère directement le HTML
                add_filter('tripzzy_filter_default_thumbnail_url', [$this, 'serve_webp_url'], 10, 1);
            }
        }
    }

    public function start_html_buffer() {
        if (!$this->browser_supports_webp()) {
            return;
        }
<<<<<<< HEAD
        // Démarrer le buffer seulement si on n'est pas déjà dans un buffer
        // Éviter les conflits avec d'autres plugins
        if (ob_get_level() === 0) {
            ob_start([$this, 'replace_images_in_html']);
        }
    }

    public function end_html_buffer() {
        // Ne rien faire - le buffer se ferme automatiquement
=======
        // Démarrer le buffer - utiliser une priorité élevée pour capturer tout le HTML
        // Ne pas vérifier ob_get_level() car WordPress peut déjà avoir un buffer actif
        // Utiliser un callback qui sera appelé à la fin du buffer
        ob_start([$this, 'replace_images_in_html'], 0, PHP_OUTPUT_HANDLER_REMOVABLE);
    }

    public function end_html_buffer() {
        // Le buffer se ferme automatiquement à la fin du script
        // Cette fonction est là pour compatibilité mais ne fait rien de spécial
>>>>>>> cc2a832e (first commit)
    }

    public function replace_images_in_content($content) {
        if (!$this->browser_supports_webp()) {
            return $content;
        }
        return $this->replace_images_in_html($content);
    }

    public function replace_images_in_html($html) {
<<<<<<< HEAD
        // Protection contre les erreurs
        if (empty($html) || !is_string($html) || !$this->browser_supports_webp()) {
            return $html;
        }

        try {
            $upload_dir = wp_upload_dir();
            if (empty($upload_dir) || isset($upload_dir['error']) && $upload_dir['error']) {
                return $html;
            }
            
            $upload_url = $upload_dir['baseurl'];
            $upload_path = $upload_dir['basedir'];
            $site_url = site_url();

            // Pattern amélioré pour capturer toutes les URLs d'images dans wp-content/uploads
            // Capture les URLs complètes ET relatives, avec ou sans query string
            $pattern = '/(https?:\/\/[^"\'\s\)\>]+\/wp-content\/uploads\/[^"\'\s\)\>]+|(?<!https?:)\/wp-content\/uploads\/[^"\'\s\)\>]+)\.(jpe?g|png|gif)(\?[^"\'\s\)\>]*)?/i';
            
            $html = preg_replace_callback($pattern, function($matches) use ($upload_url, $upload_path, $site_url) {
                try {
                    if (empty($matches) || !isset($matches[1]) || !isset($matches[2])) {
                        return isset($matches[0]) ? $matches[0] : '';
                    }
                    
                    $original_url = $matches[0];
                    $base_url = $matches[1];
                    $extension = $matches[2];
                    $query_string = isset($matches[3]) ? $matches[3] : '';
                    
                    // Convertir URL relative en absolue si nécessaire
                    $is_relative = (strpos($base_url, '/wp-content') === 0);
                    if ($is_relative) {
                        $base_url_full = rtrim($site_url, '/') . $base_url;
                    } else {
                        $base_url_full = $base_url;
                    }
                    
                    // Gérer les miniatures avec dimensions (ex: image-300x200.jpg -> image-300x200.webp)
                    $webp_url = $base_url_full . '.webp';
                    
                    // Construire les chemins possibles pour le fichier WebP
                    $relative_path = preg_replace('#https?://[^/]+#', '', $webp_url);
                    $webp_paths = [
                        str_replace($upload_url, $upload_path, $webp_url),
                        $upload_path . ltrim($relative_path, '/'),
                    ];
                    
                    // Vérifier si au moins un fichier WebP existe
                    $webp_exists = false;
                    foreach ($webp_paths as $webp_path) {
                        if ($webp_path && file_exists($webp_path)) {
                            $webp_exists = true;
                            break;
                        }
                    }
                    
                    if ($webp_exists) {
                        // Si c'était une URL relative, retourner en relatif
                        if ($is_relative) {
                            $relative_webp = '/wp-content/uploads/' . ltrim(str_replace($upload_url . '/', '', $webp_url), '/');
                            return $relative_webp . $query_string;
                        }
                        return $webp_url . $query_string;
                    }
                    
                    return $original_url;
                } catch (Exception $e) {
                    // En cas d'erreur, retourner l'URL originale
                    return isset($matches[0]) ? $matches[0] : '';
                }
            }, $html);

            return $html ? $html : '';
        } catch (Exception $e) {
            // En cas d'erreur fatale, retourner le HTML original
            return $html;
        }
=======
        if (empty($html) || !$this->browser_supports_webp()) {
            return $html;
        }

        $upload_dir = wp_upload_dir();
        $upload_url = $upload_dir['baseurl'];
        $upload_path = $upload_dir['basedir'];
        $site_url = site_url();

        // Pattern amélioré pour capturer toutes les URLs d'images dans wp-content/uploads
        // Capture les URLs complètes ET relatives, avec ou sans query string
        $pattern = '/(https?:\/\/[^"\'\s\)\>]+\/wp-content\/uploads\/[^"\'\s\)\>]+|(?<!https?:)\/wp-content\/uploads\/[^"\'\s\)\>]+)\.(jpe?g|png|gif)(\?[^"\'\s\)\>]*)?/i';
        
        $html = preg_replace_callback($pattern, function($matches) use ($upload_url, $upload_path, $site_url) {
            $original_url = $matches[0];
            $base_url = $matches[1];
            $extension = $matches[2];
            $query_string = isset($matches[3]) ? $matches[3] : '';
            
            // Convertir URL relative en absolue si nécessaire
            $is_relative = (strpos($base_url, '/wp-content') === 0);
            if ($is_relative) {
                $base_url_full = rtrim($site_url, '/') . $base_url;
            } else {
                $base_url_full = $base_url;
            }
            
            // Gérer les miniatures avec dimensions (ex: image-300x200.jpg -> image-300x200.webp)
            $webp_url = $base_url_full . '.webp';
            
            // Construire les chemins possibles pour le fichier WebP
            $relative_path = preg_replace('#https?://[^/]+#', '', $webp_url);
            $webp_paths = [
                str_replace($upload_url, $upload_path, $webp_url),
                $upload_path . ltrim($relative_path, '/'),
                ABSPATH . ltrim($relative_path, '/'),
            ];
            
            // Vérifier si au moins un fichier WebP existe
            $webp_exists = false;
            $found_path = '';
            foreach ($webp_paths as $webp_path) {
                if (file_exists($webp_path)) {
                    $webp_exists = true;
                    $found_path = $webp_path;
                    break;
                }
            }
            
            if ($webp_exists) {
                // Si c'était une URL relative, retourner en relatif
                if ($is_relative) {
                    $relative_webp = '/wp-content/uploads/' . ltrim(str_replace($upload_url . '/', '', $webp_url), '/');
                    return $relative_webp . $query_string;
                }
                return $webp_url . $query_string;
            }
            
            return $original_url;
        }, $html);

        return $html;
>>>>>>> cc2a832e (first commit)
    }

    private function browser_supports_webp() {
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false) {
            return true;
        }
        return false;
    }

    private function check_requirements() {
        return extension_loaded('imagick') || extension_loaded('gd');
    }

    public function requirements_notice() {
        echo '<div class="notice notice-error"><p><strong>RDV WebP Converter</strong> nécessite l\'extension PHP Imagick ou GD pour fonctionner.</p></div>';
    }

    public function add_admin_menu() {
        add_menu_page(
            'WebP Converter',
            'WebP Converter',
            'manage_options',
            'rdv-webp-converter',
            [$this, 'render_admin_page'],
            'dashicons-images-alt2',
            80
        );
    }

    public function register_settings() {
        register_setting('rdv_webp_options', 'rdv_webp_options');
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'rdv-webp-converter') === false) {
            return;
        }

        wp_enqueue_style(
            'rdv-webp-admin',
            RDV_WEBP_PLUGIN_URL . 'assets/admin.css',
            [],
            RDV_WEBP_VERSION
        );

        wp_enqueue_script(
            'rdv-webp-admin',
            RDV_WEBP_PLUGIN_URL . 'assets/admin.js',
            ['jquery'],
            RDV_WEBP_VERSION,
            true
        );

        wp_localize_script('rdv-webp-admin', 'rdvWebp', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rdv_webp_nonce'),
        ]);
    }

    public function render_admin_page() {
        $stats = $this->get_stats();
        ?>
        <div class="wrap rdv-webp-wrap">
            <h1 class="rdv-webp-title">
                <span class="dashicons dashicons-images-alt2"></span>
                RDV WebP Converter
            </h1>

            <!-- Navigation par onglets -->
            <nav class="rdv-webp-tabs">
                <a href="#dashboard" class="tab-link active" data-tab="dashboard">
                    <span class="dashicons dashicons-dashboard"></span> Tableau de bord
                </a>
                <a href="#images" class="tab-link" data-tab="images">
                    <span class="dashicons dashicons-format-gallery"></span> Gérer les images
                </a>
                <a href="#settings" class="tab-link" data-tab="settings">
                    <span class="dashicons dashicons-admin-generic"></span> Paramètres
                </a>
            </nav>

            <!-- Tab Dashboard -->
            <div id="tab-dashboard" class="rdv-webp-tab-content active">
                <div class="rdv-webp-grid">
                    <!-- Stats Cards -->
                    <div class="rdv-webp-card rdv-webp-stats-card clickable" data-filter="all">
                        <div class="stat-icon total">
                            <span class="dashicons dashicons-format-gallery"></span>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $stats['total']; ?></span>
                            <span class="stat-label">Toutes les images</span>
                        </div>
                    </div>

                    <div class="rdv-webp-card rdv-webp-stats-card clickable" data-filter="converted">
                        <div class="stat-icon converted">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $stats['converted']; ?></span>
                            <span class="stat-label">Images converties</span>
                        </div>
                    </div>

                    <div class="rdv-webp-card rdv-webp-stats-card clickable" data-filter="pending">
                        <div class="stat-icon pending">
                            <span class="dashicons dashicons-clock"></span>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $stats['pending']; ?></span>
                            <span class="stat-label">En attente</span>
                        </div>
                    </div>

                    <div class="rdv-webp-card rdv-webp-stats-card">
                        <div class="stat-icon savings">
                            <span class="dashicons dashicons-chart-line"></span>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $stats['savings']; ?></span>
                            <span class="stat-label">Espace économisé</span>
                        </div>
                    </div>
                </div>

                <!-- Conversion en lot -->
                <div class="rdv-webp-card">
                    <h2><span class="dashicons dashicons-update"></span> Conversion en lot</h2>
                    <p>Convertissez toutes les images en attente en un seul clic.</p>
                    
                    <button id="rdv-start-bulk-conversion" class="button button-primary button-hero">
                        <span class="dashicons dashicons-update"></span>
                        Convertir toutes les images en attente
                    </button>

                    <div class="rdv-webp-progress-container" style="display: none;">
                        <div class="rdv-webp-progress-bar">
                            <div class="rdv-webp-progress-fill"></div>
                        </div>
                        <div class="rdv-webp-progress-text">0%</div>
                        <div class="rdv-webp-progress-status"></div>
                    </div>

                    <div class="rdv-webp-results" style="display: none;"></div>
                </div>

                <!-- Statut serveur -->
                <div class="rdv-webp-card">
                    <h2><span class="dashicons dashicons-admin-tools"></span> Statut du serveur</h2>
                    <table class="rdv-webp-status-table">
                        <tr>
                            <td>Extension Imagick</td>
                            <td><?php echo extension_loaded('imagick') ? '<span class="status-ok">✅ Activé</span>' : '<span class="status-no">❌ Non disponible</span>'; ?></td>
                        </tr>
                        <tr>
                            <td>Extension GD</td>
                            <td><?php echo extension_loaded('gd') ? '<span class="status-ok">✅ Activé</span>' : '<span class="status-no">❌ Non disponible</span>'; ?></td>
                        </tr>
                        <tr>
                            <td>Support WebP</td>
                            <td><?php 
                                $webp_support = false;
                                if (extension_loaded('imagick')) {
                                    $imagick = new Imagick();
                                    $formats = $imagick->queryFormats();
                                    $webp_support = in_array('WEBP', $formats);
                                } elseif (extension_loaded('gd')) {
                                    $webp_support = function_exists('imagewebp');
                                }
                                echo $webp_support ? '<span class="status-ok">✅ Supporté</span>' : '<span class="status-no">❌ Non supporté</span>';
                            ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Tab Images -->
            <div id="tab-images" class="rdv-webp-tab-content">
                <!-- Barre de filtres -->
                <div class="rdv-webp-toolbar">
                    <div class="rdv-webp-filters">
                        <select id="rdv-filter-status">
                            <option value="">Tous les statuts</option>
                            <option value="converted">Convertis</option>
                            <option value="pending">En attente</option>
                        </select>
                        <select id="rdv-filter-type">
                            <option value="">Tous les types</option>
                            <option value="jpeg">JPEG</option>
                            <option value="png">PNG</option>
                            <option value="gif">GIF</option>
                        </select>
                        <select id="rdv-filter-alt">
                            <option value="">Tous les alt</option>
                            <option value="missing">Alt manquant</option>
                            <option value="has">Avec alt</option>
                        </select>
                        <select id="rdv-filter-usage">
                            <option value="">Toutes les images</option>
                            <option value="used">Images utilisées</option>
                            <option value="unused">Images non utilisées</option>
                        </select>
                        <select id="rdv-sort-by">
                            <option value="date_desc">Plus récent</option>
                            <option value="date_asc">Plus ancien</option>
                            <option value="name_asc">Nom A-Z</option>
                            <option value="name_desc">Nom Z-A</option>
                            <option value="size_desc">Taille décroissante</option>
                            <option value="size_asc">Taille croissante</option>
                        </select>
                        <input type="text" id="rdv-search" placeholder="Rechercher...">
                        <button id="rdv-filter-apply" class="button">
                            <span class="dashicons dashicons-filter"></span> Filtrer
                        </button>
                        <button id="rdv-filter-reset" class="button">
                            <span class="dashicons dashicons-undo"></span> Réinitialiser
                        </button>
                    </div>
                    <div class="rdv-webp-view-options">
                        <select id="rdv-per-page">
                            <option value="20">20 par page</option>
                            <option value="50">50 par page</option>
                            <option value="100">100 par page</option>
                        </select>
                        <button id="rdv-view-grid" class="button view-btn active" title="Vue grille">
                            <span class="dashicons dashicons-grid-view"></span>
                        </button>
                        <button id="rdv-view-list" class="button view-btn" title="Vue liste">
                            <span class="dashicons dashicons-list-view"></span>
                        </button>
                    </div>
                </div>

                <!-- Barre de sélection -->
                <div class="rdv-webp-selection-bar" style="display: none;">
                    <span><strong id="rdv-selection-count">0</strong> image(s) sélectionnée(s)</span>
                    <button id="rdv-convert-selected" class="button">
                        <span class="dashicons dashicons-update"></span> Convertir la sélection
                    </button>
                    <button id="rdv-edit-selected" class="button">
                        <span class="dashicons dashicons-edit"></span> Modifier la sélection
                    </button>
                    <button id="rdv-clear-selection" class="button">
                        <span class="dashicons dashicons-no-alt"></span> Annuler
                    </button>
                </div>

                <!-- Galerie -->
                <div id="rdv-images-loading" class="rdv-webp-loading">
                    <span class="spinner is-active"></span>
                    <p>Chargement des images...</p>
                </div>
                <div id="rdv-images-gallery" class="rdv-gallery-grid"></div>

                <!-- Pagination -->
                <div class="rdv-webp-pagination">
                    <button id="rdv-prev-page" class="button" disabled>
                        <span class="dashicons dashicons-arrow-left-alt2"></span> Précédent
                    </button>
                    <span id="rdv-page-info">Page 1 sur 1</span>
                    <button id="rdv-next-page" class="button">
                        Suivant <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </button>
                </div>
            </div>

            <!-- Tab Settings -->
            <div id="tab-settings" class="rdv-webp-tab-content">
                <form method="post" action="options.php">
                    <?php settings_fields('rdv_webp_options'); ?>
                    
                    <div class="rdv-webp-card">
                        <h2><span class="dashicons dashicons-admin-settings"></span> Paramètres de conversion</h2>
                        
                        <table class="form-table">
                            <tr>
                                <th>Qualité WebP</th>
                                <td>
                                    <input type="range" name="rdv_webp_options[quality]" 
                                           value="<?php echo esc_attr($this->options['quality']); ?>" 
                                           min="1" max="100" 
                                           oninput="this.nextElementSibling.textContent = this.value + '%'">
                                    <span><?php echo $this->options['quality']; ?>%</span>
                                    <p class="description">Plus la qualité est élevée, plus le fichier sera gros.</p>
                                </td>
                            </tr>
                            <tr>
                                <th>Conversion automatique</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="rdv_webp_options[auto_convert]" value="1" 
                                               <?php checked($this->options['auto_convert']); ?>>
                                        Convertir automatiquement les nouvelles images uploadées
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th>Convertir les miniatures</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="rdv_webp_options[convert_thumbnails]" value="1" 
                                               <?php checked($this->options['convert_thumbnails']); ?>>
                                        Convertir aussi les miniatures générées par WordPress
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th>Servir les WebP</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="rdv_webp_options[serve_webp]" value="1" 
                                               <?php checked($this->options['serve_webp']); ?>>
                                        Remplacer automatiquement les images par leur version WebP
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="rdv-webp-card">
                        <h2><span class="dashicons dashicons-media-default"></span> Types de fichiers</h2>
                        
                        <table class="form-table">
                            <tr>
                                <th>Formats à convertir</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="rdv_webp_options[convert_jpeg]" value="1" 
                                               <?php checked($this->options['convert_jpeg']); ?>>
                                        JPEG / JPG
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="rdv_webp_options[convert_png]" value="1" 
                                               <?php checked($this->options['convert_png']); ?>>
                                        PNG
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="rdv_webp_options[convert_gif]" value="1" 
                                               <?php checked($this->options['convert_gif']); ?>>
                                        GIF (non animé)
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <?php submit_button('Enregistrer les paramètres'); ?>
                </form>
            </div>
        </div>

        <!-- Modal d'édition -->
        <div id="rdv-edit-modal" class="rdv-modal" style="display: none;">
            <div class="rdv-modal-overlay"></div>
            <div class="rdv-modal-content">
                <div class="rdv-modal-header">
                    <h2><span class="dashicons dashicons-edit"></span> Modifier l'image</h2>
                    <button class="rdv-modal-close">&times;</button>
                </div>
                <div class="rdv-modal-body">
                    <div class="rdv-edit-preview">
                        <img id="rdv-edit-image" src="" alt="">
                    </div>
                    <div class="rdv-edit-form">
                        <input type="hidden" id="rdv-edit-id">
                        
                        <div class="rdv-form-group">
                            <label for="rdv-edit-filename">
                                <span class="dashicons dashicons-media-default"></span> Nom du fichier
                            </label>
                            <div class="rdv-input-with-ext">
                                <input type="text" id="rdv-edit-filename" placeholder="nom-du-fichier">
                                <span id="rdv-edit-extension">.jpg</span>
                            </div>
                            <p class="description">Le nom sera automatiquement converti en format SEO-friendly (minuscules, tirets)</p>
                        </div>
                        
                        <div class="rdv-form-group">
                            <label for="rdv-edit-alt">
                                <span class="dashicons dashicons-visibility"></span> Texte alternatif (alt)
                            </label>
                            <input type="text" id="rdv-edit-alt" placeholder="Description de l'image pour le SEO et l'accessibilité">
                            <p class="description">Important pour le SEO et l'accessibilité. Décris ce que montre l'image.</p>
                        </div>
                        
                        <div class="rdv-form-group">
                            <label for="rdv-edit-title">
                                <span class="dashicons dashicons-editor-textcolor"></span> Titre
                            </label>
                            <input type="text" id="rdv-edit-title" placeholder="Titre de l'image">
                        </div>

                        <div class="rdv-edit-info">
                            <div class="info-item">
                                <strong>Type :</strong> <span id="rdv-edit-type">-</span>
                            </div>
                            <div class="info-item">
                                <strong>Taille :</strong> <span id="rdv-edit-size">-</span>
                            </div>
                            <div class="info-item">
                                <strong>Dimensions :</strong> <span id="rdv-edit-dimensions">-</span>
                            </div>
                            <div class="info-item">
                                <strong>WebP :</strong> <span id="rdv-edit-webp-status">-</span>
                            </div>
                        </div>

                        <div class="rdv-edit-usage">
                            <strong><span class="dashicons dashicons-admin-links"></span> Utilisée sur :</strong>
                            <div id="rdv-edit-used-in" class="usage-list"></div>
                        </div>
                    </div>
                </div>
                <div class="rdv-modal-footer">
                    <div class="rdv-convert-options">
                        <label for="rdv-edit-quality">
                            <span class="dashicons dashicons-art"></span> Qualité : <strong id="rdv-quality-value"><?php echo $this->options['quality']; ?>%</strong>
                        </label>
                        <input type="range" id="rdv-edit-quality" min="1" max="100" value="<?php echo $this->options['quality']; ?>" 
                               oninput="document.getElementById('rdv-quality-value').textContent = this.value + '%'" />
                        <button id="rdv-edit-convert" class="button">
                            <span class="dashicons dashicons-update"></span> Convertir en WebP
                        </button>
                        <button id="rdv-edit-revert" class="button" style="display:none; color:#b32d2e; margin-left:10px;">
                            <span class="dashicons dashicons-undo"></span> Annuler WebP
                        </button>
                    </div>
                    <button id="rdv-edit-save" class="button button-primary">
                        <span class="dashicons dashicons-saved"></span> Enregistrer
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal d'édition multiple -->
        <div id="rdv-bulk-edit-modal" class="rdv-modal" style="display: none;">
            <div class="rdv-modal-overlay"></div>
            <div class="rdv-modal-content rdv-modal-large">
                <div class="rdv-modal-header">
                    <h2><span class="dashicons dashicons-images-alt"></span> Modifier plusieurs images</h2>
                    <button class="rdv-modal-close">&times;</button>
                </div>
                <div class="rdv-modal-body">
                    <div id="rdv-bulk-edit-list" class="rdv-bulk-edit-list"></div>
                </div>
                <div class="rdv-modal-footer">
                    <button id="rdv-bulk-convert" class="button">
                        <span class="dashicons dashicons-update"></span> Convertir tout en WebP
                    </button>
                    <button id="rdv-bulk-save" class="button button-primary">
                        <span class="dashicons dashicons-saved"></span> Enregistrer tout
                    </button>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Gestion des onglets
            $('.tab-link').on('click', function(e) {
                e.preventDefault();
                const tab = $(this).data('tab');
                
                $('.tab-link').removeClass('active');
                $(this).addClass('active');
                
                $('.rdv-webp-tab-content').removeClass('active');
                $('#tab-' + tab).addClass('active');
            });
        });
        </script>
        <?php
    }

    private function get_stats() {
        global $wpdb;
        
        $total = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')
        ");

        $converted = 0;
        $total_original = 0;
        $total_webp = 0;

        $attachments = $wpdb->get_results("
            SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')
        ");

        foreach ($attachments as $attachment) {
            $file_path = get_attached_file($attachment->ID);
            if ($file_path && file_exists($file_path)) {
                $webp_path = $this->get_webp_path($file_path);
                if (file_exists($webp_path)) {
                    $converted++;
                    $total_original += filesize($file_path);
                    $total_webp += filesize($webp_path);
                }
            }
        }

        $savings = $total_original > 0 ? $total_original - $total_webp : 0;

        return [
            'total' => (int) $total,
            'converted' => $converted,
            'pending' => (int) $total - $converted,
            'savings' => $this->format_size($savings),
            'savings_percent' => $total_original > 0 ? round(($savings / $total_original) * 100) : 0,
        ];
    }

    private function format_size($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    private function get_webp_path($file_path) {
        $path_info = pathinfo($file_path);
        return $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';
    }

    public function convert_on_upload($upload, $context) {
        $file_path = $upload['file'];
        $mime_type = $upload['type'];

        if (!in_array($mime_type, $this->get_allowed_mime_types())) {
            return $upload;
        }

        $this->convert_to_webp($file_path);

        return $upload;
    }

    public function convert_thumbnails($metadata, $attachment_id) {
        if (empty($metadata['sizes'])) {
            return $metadata;
        }

        $file_path = get_attached_file($attachment_id);
        $dir = dirname($file_path) . '/';

        foreach ($metadata['sizes'] as $size_info) {
            $thumb_path = $dir . $size_info['file'];
            if (file_exists($thumb_path)) {
                $this->convert_to_webp($thumb_path);
            }
        }

        return $metadata;
    }

    public function convert_to_webp($file_path, $quality = null) {
        if (!file_exists($file_path)) {
            return false;
        }

        $path_info = pathinfo($file_path);
        $extension = strtolower($path_info['extension'] ?? '');
        
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            return false;
        }

        $webp_path = $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';
        
        if (file_exists($webp_path)) {
            return true;
        }

        $quality = $quality ?? $this->options['quality'];

        try {
            if (extension_loaded('imagick')) {
                $image = new Imagick($file_path);
                $image->setImageFormat('webp');
                $image->setImageCompressionQuality($quality);
                
                if ($extension === 'png') {
                    $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
                    $image->setBackgroundColor(new ImagickPixel('transparent'));
                }
                
                $image->writeImage($webp_path);
                $image->destroy();
                
                return true;
            } elseif (extension_loaded('gd')) {
                switch ($extension) {
                    case 'jpg':
                    case 'jpeg':
                        $image = imagecreatefromjpeg($file_path);
                        break;
                    case 'png':
                        $image = imagecreatefrompng($file_path);
                        imagepalettetotruecolor($image);
                        imagealphablending($image, true);
                        imagesavealpha($image, true);
                        break;
                    case 'gif':
                        $image = imagecreatefromgif($file_path);
                        break;
                    default:
                        return false;
                }

                if (!$image) {
                    return false;
                }

                $result = imagewebp($image, $webp_path, $quality);
                imagedestroy($image);
                
                return $result;
            }
        } catch (Exception $e) {
            error_log('RDV WebP Converter Error: ' . $e->getMessage());
            return false;
        }

        return false;
    }

    private function get_allowed_mime_types() {
        $types = [];
        if ($this->options['convert_jpeg']) {
            $types[] = 'image/jpeg';
        }
        if ($this->options['convert_png']) {
            $types[] = 'image/png';
        }
        if ($this->options['convert_gif']) {
            $types[] = 'image/gif';
        }
        return $types;
    }

    public function serve_webp($image, $attachment_id, $size, $icon) {
        if (!$image || !is_array($image)) {
            return $image;
        }

        if (!$this->browser_supports_webp()) {
            return $image;
        }

        $url = $image[0];
        $path_info = pathinfo($url);
        
        if (!isset($path_info['extension']) || !in_array(strtolower($path_info['extension']), ['jpg', 'jpeg', 'png', 'gif'])) {
            return $image;
        }

        $upload_dir = wp_upload_dir();
        
        // Gérer les miniatures avec dimensions dans le nom (ex: image-300x200.jpg)
        $webp_url = $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';
        
        // Construire le chemin du fichier WebP de plusieurs façons pour être sûr
        $webp_paths = [];
        
        // Méthode 1 : Remplacement direct de l'URL
        $webp_paths[] = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $webp_url);
        
        // Méthode 2 : Chemin relatif depuis ABSPATH
        $relative_path = str_replace($upload_dir['baseurl'], '', $webp_url);
        $webp_paths[] = $upload_dir['basedir'] . $relative_path;
        
        // Méthode 3 : Si c'est une miniature avec dimensions, essayer aussi le WebP de l'original
        $webp_exists = false;
        foreach ($webp_paths as $webp_path) {
            if (file_exists($webp_path)) {
                $webp_exists = true;
                break;
            }
        }
        
        // Si le WebP de la miniature n'existe pas, utiliser le WebP de l'image originale
        if (!$webp_exists && $attachment_id) {
            $original_file = get_attached_file($attachment_id);
            if ($original_file && file_exists($original_file)) {
                $original_webp = $this->get_webp_path($original_file);
                if (file_exists($original_webp)) {
                    // Utiliser la version WebP de l'image originale
                    $original_url = wp_get_attachment_url($attachment_id);
                    $original_path_info = pathinfo($original_url);
                    $original_webp_url = $original_path_info['dirname'] . '/' . $original_path_info['filename'] . '.webp';
                    $image[0] = $original_webp_url;
                    return $image;
                }
            }
        }
        
        // Si le WebP de la miniature existe, l'utiliser
        if ($webp_exists) {
            $image[0] = $webp_url;
        }

        return $image;
    }

    public function serve_webp_url($url, $attachment_id, $size = null) {
        if (!$url || !$this->browser_supports_webp()) {
            return $url;
        }

        $path_info = pathinfo($url);
        
        if (!isset($path_info['extension']) || !in_array(strtolower($path_info['extension']), ['jpg', 'jpeg', 'png', 'gif'])) {
            return $url;
        }

        $upload_dir = wp_upload_dir();
        
        // Gérer les miniatures avec dimensions dans le nom (ex: image-300x200.jpg)
        $webp_url = $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';
        $webp_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $webp_url);
        
        // Si le fichier WebP de la miniature n'existe pas, utiliser le WebP de l'image originale
        if (!file_exists($webp_path) && $attachment_id) {
            $original_file = get_attached_file($attachment_id);
            if ($original_file && file_exists($original_file)) {
                $original_webp = $this->get_webp_path($original_file);
                if (file_exists($original_webp)) {
                    // Utiliser la version WebP de l'image originale
                    $original_url = wp_get_attachment_url($attachment_id);
                    $original_path_info = pathinfo($original_url);
                    $original_webp_url = $original_path_info['dirname'] . '/' . $original_path_info['filename'] . '.webp';
                    return $original_webp_url;
                }
            }
        }
        
        // Si le WebP de la miniature existe, l'utiliser
        if (file_exists($webp_path)) {
            return $webp_url;
        }

        return $url;
    }

    public function serve_webp_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
        if (!$this->browser_supports_webp() || !is_array($sources)) {
            return $sources;
        }

        $upload_dir = wp_upload_dir();

        foreach ($sources as &$source) {
            if (!isset($source['url'])) {
                continue;
            }
            
            $url = $source['url'];
            $path_info = pathinfo($url);
            
            if (!isset($path_info['extension']) || !in_array(strtolower($path_info['extension']), ['jpg', 'jpeg', 'png', 'gif'])) {
                continue;
            }

            // Gérer les miniatures avec dimensions dans le nom
            $webp_url = $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';
            $webp_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $webp_url);
            
            // Si le WebP de la miniature n'existe pas, essayer avec l'original
            if (!file_exists($webp_path) && $attachment_id) {
                $original_file = get_attached_file($attachment_id);
                if ($original_file) {
                    $original_webp = $this->get_webp_path($original_file);
                    if (file_exists($original_webp)) {
                        // Utiliser la version WebP de l'original
                        $original_url = wp_get_attachment_url($attachment_id);
                        $original_path_info = pathinfo($original_url);
                        $original_webp_url = $original_path_info['dirname'] . '/' . $original_path_info['filename'] . '.webp';
                        $webp_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $original_webp_url);
                        $webp_url = $original_webp_url;
                    }
                }
            }
            
            if (file_exists($webp_path)) {
                $source['url'] = $webp_url;
            }
        }

        return $sources;
    }

    // AJAX Handlers
    public function ajax_get_stats() {
        check_ajax_referer('rdv_webp_nonce', 'nonce');
        wp_send_json_success($this->get_stats());
    }

    public function ajax_convert_bulk() {
        check_ajax_referer('rdv_webp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }

        $offset = isset($_POST['offset']) ? (int) $_POST['offset'] : 0;
        $batch_size = isset($_POST['batch_size']) ? (int) $_POST['batch_size'] : 5;

        global $wpdb;
        
        $attachments = $wpdb->get_results($wpdb->prepare("
            SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')
            ORDER BY ID ASC
            LIMIT %d OFFSET %d
        ", $batch_size, $offset));

        $converted = 0;

        foreach ($attachments as $attachment) {
            $file_path = get_attached_file($attachment->ID);
            
            if (!$file_path || !file_exists($file_path)) {
                continue;
            }

            $webp_path = $this->get_webp_path($file_path);
            
            if (!file_exists($webp_path)) {
                if ($this->convert_to_webp($file_path)) {
                    $converted++;
                }
            }

            if ($this->options['convert_thumbnails']) {
                $metadata = wp_get_attachment_metadata($attachment->ID);
                if (!empty($metadata['sizes'])) {
                    $dir = dirname($file_path) . '/';
                    foreach ($metadata['sizes'] as $size_info) {
                        $thumb_path = $dir . $size_info['file'];
                        if (file_exists($thumb_path)) {
                            $thumb_webp = $this->get_webp_path($thumb_path);
                            if (!file_exists($thumb_webp)) {
                                $this->convert_to_webp($thumb_path);
                            }
                        }
                    }
                }
            }
        }

        $total = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')
        ");

        wp_send_json_success([
            'converted' => $converted,
            'has_more' => ($offset + $batch_size) < $total,
        ]);
    }

    public function ajax_convert_single() {
        check_ajax_referer('rdv_webp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }

        $attachment_id = isset($_POST['attachment_id']) ? (int) $_POST['attachment_id'] : 0;
        $quality = isset($_POST['quality']) ? (int) $_POST['quality'] : $this->options['quality'];
        
        if (!$attachment_id) {
            wp_send_json_error('ID invalide');
        }

        $file_path = get_attached_file($attachment_id);
        
        if (!$file_path || !file_exists($file_path)) {
            wp_send_json_error('Fichier non trouvé');
        }

        $webp_path = $this->get_webp_path($file_path);
        if (file_exists($webp_path)) {
            unlink($webp_path);
        }

        if ($this->convert_to_webp($file_path, $quality)) {
            if ($this->options['convert_thumbnails']) {
                $metadata = wp_get_attachment_metadata($attachment_id);
                if (!empty($metadata['sizes'])) {
                    $dir = dirname($file_path) . '/';
                    foreach ($metadata['sizes'] as $size_info) {
                        $thumb_path = $dir . $size_info['file'];
                        if (file_exists($thumb_path)) {
                            $thumb_webp = $this->get_webp_path($thumb_path);
                            if (file_exists($thumb_webp)) {
                                unlink($thumb_webp);
                            }
                            $this->convert_to_webp($thumb_path, $quality);
                        }
                    }
                }
            }

            $original_size = filesize($file_path);
            $webp_size = file_exists($webp_path) ? filesize($webp_path) : 0;
            $savings = $original_size > 0 ? round((($original_size - $webp_size) / $original_size) * 100) : 0;

            wp_send_json_success([
                'message' => 'Image convertie',
                'webp_size' => $this->format_size($webp_size),
                'savings' => $savings . '%',
            ]);
        } else {
            wp_send_json_error('Erreur lors de la conversion');
        }
    }

    public function ajax_convert_selected() {
        check_ajax_referer('rdv_webp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }

        $ids = isset($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];
        
        if (empty($ids)) {
            wp_send_json_error('Aucune image sélectionnée');
        }

        $converted = 0;

        foreach ($ids as $attachment_id) {
            $file_path = get_attached_file($attachment_id);
            
            if (!$file_path || !file_exists($file_path)) {
                continue;
            }

            if ($this->convert_to_webp($file_path)) {
                $converted++;
                
                if ($this->options['convert_thumbnails']) {
                    $metadata = wp_get_attachment_metadata($attachment_id);
                    if (!empty($metadata['sizes'])) {
                        $dir = dirname($file_path) . '/';
                        foreach ($metadata['sizes'] as $size_info) {
                            $thumb_path = $dir . $size_info['file'];
                            if (file_exists($thumb_path)) {
                                $this->convert_to_webp($thumb_path);
                            }
                        }
                    }
                }
            }
        }

        wp_send_json_success(['converted' => $converted]);
    }

    public function ajax_delete_webp() {
        check_ajax_referer('rdv_webp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }

        $attachment_id = isset($_POST['attachment_id']) ? (int) $_POST['attachment_id'] : 0;
        
        if (!$attachment_id) {
            wp_send_json_error('ID invalide');
        }

        $file_path = get_attached_file($attachment_id);
        $webp_path = $this->get_webp_path($file_path);
        
        if (file_exists($webp_path)) {
            unlink($webp_path);
        }

        $metadata = wp_get_attachment_metadata($attachment_id);
        if (!empty($metadata['sizes'])) {
            $dir = dirname($file_path) . '/';
            foreach ($metadata['sizes'] as $size_info) {
                $thumb_path = $dir . $size_info['file'];
                $thumb_webp = $this->get_webp_path($thumb_path);
                if (file_exists($thumb_webp)) {
                    unlink($thumb_webp);
                }
            }
        }

        wp_send_json_success('WebP supprimé');
    }

    /**
     * Vérifie si une image est utilisée dans le contenu, métadonnées ou comme image à la une
     */
    /**
     * Pré-charge les IDs des images utilisées (optimisation)
     */
    private function preload_used_image_ids() {
        global $wpdb;
        
        // Cache statique pour éviter les requêtes multiples
        static $used_ids = null;
        
        if ($used_ids !== null) {
            return $used_ids;
        }
        
        $used_ids = [];
        
        // 1. Images utilisées comme thumbnail (image à la une)
        $thumbnail_ids = $wpdb->get_col("
            SELECT DISTINCT CAST(pm.meta_value AS UNSIGNED) 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_thumbnail_id' 
            AND pm.meta_value != ''
            AND p.post_status = 'publish'
        ");
        
        foreach ($thumbnail_ids as $id) {
            $used_ids[(int)$id] = true;
        }
        
        // 2. Images référencées par ID dans postmeta (Avada, ACF, etc.)
        $meta_ids = $wpdb->get_col("
            SELECT DISTINCT CAST(pm.meta_value AS UNSIGNED)
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_status = 'publish'
            AND pm.meta_value REGEXP '^[0-9]+$'
            AND CAST(pm.meta_value AS UNSIGNED) > 0
        ");
        
        // Vérifier que ces IDs sont bien des attachments
        if (!empty($meta_ids)) {
            $meta_ids_str = implode(',', array_map('intval', $meta_ids));
            $valid_ids = $wpdb->get_col("
                SELECT ID FROM {$wpdb->posts} 
                WHERE ID IN ($meta_ids_str) 
                AND post_type = 'attachment'
            ");
            foreach ($valid_ids as $id) {
                $used_ids[(int)$id] = true;
            }
        }
        
        // 3. Images référencées dans le contenu par wp-image-XXX
        $content_ids = $wpdb->get_col("
            SELECT DISTINCT 
                CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(post_content, 'wp-image-', -1), '\"', 1) AS UNSIGNED) as img_id
            FROM {$wpdb->posts}
            WHERE post_status = 'publish'
            AND post_content LIKE '%wp-image-%'
        ");
        
        foreach ($content_ids as $id) {
            if ((int)$id > 0) {
                $used_ids[(int)$id] = true;
            }
        }
        
        return $used_ids;
    }
    
    /**
     * Vérifie si une image est utilisée (version rapide avec cache)
     */
    private function is_image_used_fast($attachment_id) {
        $used_ids = $this->preload_used_image_ids();
        return isset($used_ids[(int)$attachment_id]);
    }

    private function is_image_used($attachment_id, $file_path = null) {
        global $wpdb;
        
        if (!$file_path) {
            $file_path = get_attached_file($attachment_id);
        }
        
        $filename = basename($file_path);
        $filename_no_ext = pathinfo($filename, PATHINFO_FILENAME);
        
        // Vérifie si l'image est utilisée dans le contenu d'un post
        $in_content = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_status = 'publish' 
            AND (post_content LIKE %s OR post_content LIKE %s)
        ", '%wp-image-' . $attachment_id . '%', '%' . $wpdb->esc_like($filename) . '%'));
        
        if ($in_content > 0) {
            return true;
        }
        
        // Vérifie si l'image est utilisée comme image à la une
        $as_thumbnail = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_thumbnail_id' 
            AND pm.meta_value = %d
            AND p.post_status = 'publish'
        ", $attachment_id));
        
        if ($as_thumbnail > 0) {
            return true;
        }
        
        // Vérifie dans les métadonnées (Avada, ACF, Elementor, etc.)
        $in_meta = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_status = 'publish'
            AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s OR pm.meta_value = %d)
        ", '%' . $wpdb->esc_like($filename) . '%', '%' . $wpdb->esc_like($filename_no_ext) . '%', $attachment_id));
        
        return $in_meta > 0;
    }

    public function ajax_get_images() {
        check_ajax_referer('rdv_webp_nonce', 'nonce');
        
        global $wpdb;
        
        $page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
        $per_page = isset($_POST['per_page']) ? (int) $_POST['per_page'] : 24;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $alt_filter = isset($_POST['alt_filter']) ? sanitize_text_field($_POST['alt_filter']) : '';
        $usage_filter = isset($_POST['usage_filter']) ? sanitize_text_field($_POST['usage_filter']) : '';
        $sort_by = isset($_POST['sort_by']) ? sanitize_text_field($_POST['sort_by']) : 'date_desc';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        $mime_types = ['image/jpeg', 'image/png', 'image/gif'];
        if ($type === 'jpeg') {
            $mime_types = ['image/jpeg'];
        } elseif ($type === 'png') {
            $mime_types = ['image/png'];
        } elseif ($type === 'gif') {
            $mime_types = ['image/gif'];
        }

        $mime_placeholders = implode(',', array_fill(0, count($mime_types), '%s'));

        $where = "WHERE post_type = 'attachment' AND post_mime_type IN ($mime_placeholders)";
        $params = $mime_types;

        if ($search) {
            $where .= " AND (post_title LIKE %s OR guid LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        switch ($sort_by) {
            case 'date_asc':
                $order = 'ORDER BY post_date ASC';
                break;
            case 'name_asc':
                $order = 'ORDER BY post_title ASC';
                break;
            case 'name_desc':
                $order = 'ORDER BY post_title DESC';
                break;
            default:
                $order = 'ORDER BY post_date DESC';
        }

        // Si des filtres post-SQL sont actifs, on doit charger toutes les images d'abord
        $has_post_filters = !empty($status) || !empty($alt_filter) || !empty($usage_filter);
        
        if ($has_post_filters) {
            // Charger toutes les images correspondant aux critères SQL
            $attachments = $wpdb->get_results($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} $where $order",
                ...$params
            ));
            
            $filtered_images = [];
            
            foreach ($attachments as $attachment) {
                $file_path = get_attached_file($attachment->ID);
                
                if (!$file_path || !file_exists($file_path)) {
                    continue;
                }

                $webp_path = $this->get_webp_path($file_path);
                $is_converted = file_exists($webp_path);

                // Filtre statut WebP
                if ($status === 'converted' && !$is_converted) continue;
                if ($status === 'pending' && $is_converted) continue;

                $alt = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);
                
                // Filtre alt
                if ($alt_filter === 'missing' && !empty($alt)) continue;
                if ($alt_filter === 'has' && empty($alt)) continue;

                // Filtre utilisation (version optimisée)
                if ($usage_filter) {
                    $is_used = $this->is_image_used_fast($attachment->ID);
                    if ($usage_filter === 'used' && !$is_used) continue;
                    if ($usage_filter === 'unused' && $is_used) continue;
                }

                $original_size = filesize($file_path);
                $webp_size = $is_converted ? filesize($webp_path) : 0;
                $savings = $original_size > 0 && $webp_size > 0 ? round((($original_size - $webp_size) / $original_size) * 100) : 0;

                $filtered_images[] = [
                    'id' => $attachment->ID,
                    'filename' => basename($file_path),
                    'thumbnail' => wp_get_attachment_image_url($attachment->ID, 'thumbnail'),
                    'alt' => $alt,
                    'original_size' => $this->format_size($original_size),
                    'webp_size' => $is_converted ? $this->format_size($webp_size) : null,
                    'is_converted' => $is_converted,
                    'savings' => $savings . '%',
                ];
            }
            
            // Pagination manuelle
            $total = count($filtered_images);
            $offset = ($page - 1) * $per_page;
            $images = array_slice($filtered_images, $offset, $per_page);
            
        } else {
            // Pas de filtres post-SQL, pagination SQL classique
            $total = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} $where",
                ...$params
            ));

            $offset = ($page - 1) * $per_page;
            
            $attachments = $wpdb->get_results($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} $where $order LIMIT %d OFFSET %d",
                ...array_merge($params, [$per_page, $offset])
            ));

            $images = [];
            
            foreach ($attachments as $attachment) {
                $file_path = get_attached_file($attachment->ID);
                
                if (!$file_path || !file_exists($file_path)) {
                    continue;
                }

                $webp_path = $this->get_webp_path($file_path);
                $is_converted = file_exists($webp_path);
                $alt = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);

                $original_size = filesize($file_path);
                $webp_size = $is_converted ? filesize($webp_path) : 0;
                $savings = $original_size > 0 && $webp_size > 0 ? round((($original_size - $webp_size) / $original_size) * 100) : 0;

                $images[] = [
                    'id' => $attachment->ID,
                    'filename' => basename($file_path),
                    'thumbnail' => wp_get_attachment_image_url($attachment->ID, 'thumbnail'),
                    'alt' => $alt,
                    'original_size' => $this->format_size($original_size),
                    'webp_size' => $is_converted ? $this->format_size($webp_size) : null,
                    'is_converted' => $is_converted,
                    'savings' => $savings . '%',
                ];
            }
        }

        wp_send_json_success([
            'images' => $images,
            'total' => (int) $total,
            'pages' => max(1, ceil($total / $per_page)),
            'current_page' => $page,
        ]);
    }

    public function ajax_update_alt() {
        check_ajax_referer('rdv_webp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }

        $attachment_id = isset($_POST['attachment_id']) ? (int) $_POST['attachment_id'] : 0;
        $alt = isset($_POST['alt']) ? sanitize_text_field($_POST['alt']) : '';
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        
        if (!$attachment_id) {
            wp_send_json_error('ID invalide');
        }

        update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);
        
        wp_update_post([
            'ID' => $attachment_id,
            'post_title' => $title,
        ]);

        wp_send_json_success('Alt et titre mis à jour');
    }

    public function ajax_rename_file() {
        check_ajax_referer('rdv_webp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }

        $attachment_id = isset($_POST['attachment_id']) ? (int) $_POST['attachment_id'] : 0;
        $new_filename = isset($_POST['filename']) ? sanitize_file_name($_POST['filename']) : '';
        
        if (!$attachment_id || !$new_filename) {
            wp_send_json_error('Paramètres invalides');
        }

        $file_path = get_attached_file($attachment_id);
        
        if (!$file_path || !file_exists($file_path)) {
            wp_send_json_error('Fichier non trouvé');
        }

        $path_info = pathinfo($file_path);
        $current_filename = $path_info['filename'];
        
        if ($current_filename === $new_filename) {
            wp_send_json_success('Aucun changement');
        }

        $new_filename = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '-', $new_filename));
        $new_filename = preg_replace('/-+/', '-', $new_filename);
        $new_filename = trim($new_filename, '-');

        $new_path = $path_info['dirname'] . '/' . $new_filename . '.' . $path_info['extension'];
        
        if (file_exists($new_path)) {
            wp_send_json_error('Un fichier avec ce nom existe déjà');
        }

        if (!rename($file_path, $new_path)) {
            wp_send_json_error('Erreur lors du renommage');
        }

        $webp_path = $this->get_webp_path($file_path);
        if (file_exists($webp_path)) {
            $new_webp_path = $path_info['dirname'] . '/' . $new_filename . '.webp';
            rename($webp_path, $new_webp_path);
        }

        update_attached_file($attachment_id, $new_path);

        $metadata = wp_get_attachment_metadata($attachment_id);
        if ($metadata && isset($metadata['file'])) {
            $metadata['file'] = str_replace($current_filename, $new_filename, $metadata['file']);
            
            if (!empty($metadata['sizes'])) {
                foreach ($metadata['sizes'] as $size => &$size_info) {
                    $old_thumb = $path_info['dirname'] . '/' . $size_info['file'];
                    $new_thumb_name = str_replace($current_filename, $new_filename, $size_info['file']);
                    $new_thumb = $path_info['dirname'] . '/' . $new_thumb_name;
                    
                    if (file_exists($old_thumb)) {
                        rename($old_thumb, $new_thumb);
                        
                        $old_thumb_webp = $this->get_webp_path($old_thumb);
                        if (file_exists($old_thumb_webp)) {
                            $new_thumb_webp = $this->get_webp_path($new_thumb);
                            rename($old_thumb_webp, $new_thumb_webp);
                        }
                    }
                    
                    $size_info['file'] = $new_thumb_name;
                }
            }
            
            wp_update_attachment_metadata($attachment_id, $metadata);
        }

        $upload_dir = wp_upload_dir();
        $new_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $new_path);
        
        global $wpdb;
        $wpdb->update(
            $wpdb->posts,
            ['guid' => $new_url],
            ['ID' => $attachment_id]
        );

        wp_send_json_success('Fichier renommé');
    }

    public function ajax_get_image_details() {
        check_ajax_referer('rdv_webp_nonce', 'nonce');
        
        $attachment_id = isset($_POST['attachment_id']) ? (int) $_POST['attachment_id'] : 0;
        
        if (!$attachment_id) {
            wp_send_json_error('ID invalide');
        }

        $file_path = get_attached_file($attachment_id);
        
        if (!$file_path || !file_exists($file_path)) {
            wp_send_json_error('Fichier non trouvé');
        }

        $path_info = pathinfo($file_path);
        $webp_path = $this->get_webp_path($file_path);
        $is_converted = file_exists($webp_path);

        $original_size = filesize($file_path);
        $webp_size = $is_converted ? filesize($webp_path) : 0;

        $image_size = getimagesize($file_path);
        $dimensions = $image_size ? $image_size[0] . ' × ' . $image_size[1] . ' px' : 'Inconnu';

        global $wpdb;
        $used_in = [];
        $filename = basename($file_path);
        
        $posts = $wpdb->get_results($wpdb->prepare("
            SELECT ID, post_title, post_type 
            FROM {$wpdb->posts} 
            WHERE post_status = 'publish' 
            AND (post_content LIKE %s OR post_content LIKE %s)
        ", '%wp-image-' . $attachment_id . '%', '%' . $wpdb->esc_like($filename) . '%'));

        foreach ($posts as $post) {
            $used_in[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => $post->post_type,
                'url' => get_permalink($post->ID),
                'edit_url' => get_edit_post_link($post->ID, 'raw'),
            ];
        }

        $thumbnail_id_posts = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, p.post_title, p.post_type
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE pm.meta_key = '_thumbnail_id' 
            AND pm.meta_value = %d
            AND p.post_status = 'publish'
        ", $attachment_id));

        foreach ($thumbnail_id_posts as $post) {
            $already_exists = false;
            foreach ($used_in as $existing) {
                if ($existing['id'] == $post->ID) {
                    $already_exists = true;
                    break;
                }
            }
            if (!$already_exists) {
                $used_in[] = [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'type' => $post->post_type,
                    'url' => get_permalink($post->ID),
                    'edit_url' => get_edit_post_link($post->ID, 'raw'),
                ];
            }
        }

        // Recherche dans les métadonnées (Avada, ACF, Elementor, etc.)
        $filename_no_ext = pathinfo($filename, PATHINFO_FILENAME);
        $meta_posts = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT p.ID, p.post_title, p.post_type
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_status = 'publish'
            AND p.post_type NOT IN ('attachment', 'revision', 'nav_menu_item')
            AND (pm.meta_value LIKE %s OR pm.meta_value LIKE %s OR pm.meta_value = %s)
            LIMIT 20
        ", '%' . $wpdb->esc_like($filename) . '%', '%' . $wpdb->esc_like($filename_no_ext) . '%', $attachment_id));

        foreach ($meta_posts as $post) {
            $already_exists = false;
            foreach ($used_in as $existing) {
                if ($existing['id'] == $post->ID) {
                    $already_exists = true;
                    break;
                }
            }
            if (!$already_exists) {
                $used_in[] = [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'type' => $post->post_type,
                    'url' => get_permalink($post->ID),
                    'edit_url' => get_edit_post_link($post->ID, 'raw'),
                ];
            }
        }

        wp_send_json_success([
            'id' => $attachment_id,
            'filename' => $path_info['filename'],
            'extension' => '.' . $path_info['extension'],
            'thumbnail' => wp_get_attachment_image_url($attachment_id, 'medium'),
            'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
            'title' => get_the_title($attachment_id),
            'type' => get_post_mime_type($attachment_id),
            'original_size' => $this->format_size($original_size),
            'webp_size' => $is_converted ? $this->format_size($webp_size) : null,
            'dimensions' => $dimensions,
            'is_converted' => $is_converted,
            'used_in' => $used_in,
        ]);
    }

    public function ajax_bulk_update() {
        check_ajax_referer('rdv_webp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }

        $updates = isset($_POST['updates']) ? $_POST['updates'] : [];
        
        if (empty($updates)) {
            wp_send_json_error('Aucune mise à jour');
        }

        $success = 0;

        foreach ($updates as $update) {
            $id = isset($update['id']) ? (int) $update['id'] : 0;
            if (!$id) continue;

            if (isset($update['alt'])) {
                update_post_meta($id, '_wp_attachment_image_alt', sanitize_text_field($update['alt']));
            }

            if (isset($update['title'])) {
                wp_update_post([
                    'ID' => $id,
                    'post_title' => sanitize_text_field($update['title']),
                ]);
            }

            $success++;
        }

        wp_send_json_success(['success' => $success]);
    }

    /**
     * AJAX : Annule la conversion WebP (supprime les fichiers WebP)
     */
    public function ajax_restore_original() {
        check_ajax_referer('rdv_webp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée');
        }

        $attachment_id = isset($_POST['attachment_id']) ? (int) $_POST['attachment_id'] : 0;
        
        if (!$attachment_id) {
            wp_send_json_error('ID invalide');
        }

        $file_path = get_attached_file($attachment_id);
        
        if (!$file_path || !file_exists($file_path)) {
            wp_send_json_error('Fichier original introuvable');
        }

        $deleted_count = 0;
        $path_info = pathinfo($file_path);
        
        // Supprimer le WebP de l'image principale
        $webp_path = $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';
        if (file_exists($webp_path)) {
            if (unlink($webp_path)) {
                $deleted_count++;
            }
        }
        
        // Supprimer les WebP des miniatures
        $metadata = wp_get_attachment_metadata($attachment_id);
        if (!empty($metadata['sizes'])) {
            $dir = $path_info['dirname'] . '/';
            foreach ($metadata['sizes'] as $size_info) {
                $thumb_info = pathinfo($size_info['file']);
                $thumb_webp_path = $dir . $thumb_info['filename'] . '.webp';
                if (file_exists($thumb_webp_path)) {
                    if (unlink($thumb_webp_path)) {
                        $deleted_count++;
                    }
                }
            }
        }
        
        if ($deleted_count > 0) {
            wp_send_json_success([
                'message' => sprintf('%d fichier(s) WebP supprimé(s)', $deleted_count),
                'deleted' => $deleted_count
            ]);
        } else {
            wp_send_json_error('Aucun fichier WebP trouvé à supprimer');
        }
    }
}

// Initialiser le plugin
add_action('plugins_loaded', function() {
    RDV_WebP_Converter::get_instance();
});

// Activation
register_activation_hook(__FILE__, function() {
    add_option('rdv_webp_options', [
        'quality' => 80,
        'auto_convert' => true,
        'convert_jpeg' => true,
        'convert_png' => true,
        'convert_gif' => false,
        'delete_originals' => false,
        'serve_webp' => true,
        'convert_thumbnails' => true,
        'backup_originals' => false,
    ]);
});
