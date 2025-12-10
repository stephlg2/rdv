<?php
/**
 * Plugin Name: Devis Pro - Gestion Avancée des Devis
 * Plugin URI: https://rdvasie.com
 * Description: Gestion professionnelle des demandes de devis avec dashboard, statistiques, exports, relances automatiques et paiement sécurisé.
 * Version: 2.1.0
 * Author: RDV Asie
 * Author URI: https://rdvasie.com
 * Text Domain: devis-pro
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Constantes du plugin
define('DEVIS_PRO_VERSION', '2.0.0');
define('DEVIS_PRO_PATH', plugin_dir_path(__FILE__));
define('DEVIS_PRO_URL', plugin_dir_url(__FILE__));
define('DEVIS_PRO_TABLE', 'devis_pro');
define('DEVIS_PRO_NOTES_TABLE', 'devis_pro_notes');
define('DEVIS_PRO_HISTORY_TABLE', 'devis_pro_history');

// Inclure les fichiers nécessaires
require_once DEVIS_PRO_PATH . 'includes/class-devis-pro-db.php';
require_once DEVIS_PRO_PATH . 'includes/class-devis-pro-list-table.php';
require_once DEVIS_PRO_PATH . 'includes/class-devis-pro-email.php';
require_once DEVIS_PRO_PATH . 'includes/class-devis-pro-export.php';
require_once DEVIS_PRO_PATH . 'includes/class-devis-pro-stats.php';
require_once DEVIS_PRO_PATH . 'includes/class-devis-pro-security.php';

/**
 * Classe principale du plugin
 */
class Devis_Pro {

    private static $instance = null;
    private $db;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->db = new Devis_Pro_DB();
        
        // Hooks d'activation/désactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Actions admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
        
        // AJAX handlers
        add_action('wp_ajax_devis_pro_update_status', array($this, 'ajax_update_status'));
        add_action('wp_ajax_devis_pro_add_note', array($this, 'ajax_add_note'));
        add_action('wp_ajax_devis_pro_delete_note', array($this, 'ajax_delete_note'));
        add_action('wp_ajax_devis_pro_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_devis_pro_bulk_action', array($this, 'ajax_bulk_action'));
        add_action('wp_ajax_devis_pro_send_reminder', array($this, 'ajax_send_reminder'));
        add_action('wp_ajax_devis_pro_duplicate', array($this, 'ajax_duplicate'));
        add_action('wp_ajax_devis_pro_send_payment_link', array($this, 'ajax_send_payment_link'));
        add_action('wp_ajax_devis_pro_migrate', array($this, 'ajax_migrate'));
        
        // Shortcodes
        add_shortcode('demande-devis-pro', array($this, 'shortcode_form'));
        add_shortcode('demande-devis', array($this, 'shortcode_form')); // Compatibilité ancien plugin
        add_shortcode('demande-devis-complet', array($this, 'shortcode_form_full')); // Formulaire complet pleine page
        add_shortcode('paiement-devis-pro', array($this, 'shortcode_paiement'));
        add_shortcode('paiement-devis', array($this, 'shortcode_paiement')); // Compatibilité ancien plugin
        add_shortcode('espace-client-devis', array($this, 'shortcode_espace_client'));
        
        // AJAX pour recherche de voyages (autocomplétion)
        add_action('wp_ajax_devis_pro_search_voyages', array($this, 'ajax_search_voyages'));
        add_action('wp_ajax_nopriv_devis_pro_search_voyages', array($this, 'ajax_search_voyages'));
        add_action('wp_ajax_devis_pro_get_voyage', array($this, 'ajax_get_voyage'));
        add_action('wp_ajax_nopriv_devis_pro_get_voyage', array($this, 'ajax_get_voyage'));
        
        // AJAX pour soumission formulaire front-end (sidebar)
        add_action('wp_ajax_devis_pro_submit_form', array($this, 'ajax_submit_form'));
        add_action('wp_ajax_nopriv_devis_pro_submit_form', array($this, 'ajax_submit_form'));
        
        // AJAX pour archiver/restaurer un devis
        add_action('wp_ajax_devis_pro_archive', array($this, 'ajax_archive_devis'));
        add_action('wp_ajax_devis_pro_restore', array($this, 'ajax_restore_devis'));
        
        // Shortcode facture
        add_shortcode('facture-devis', array($this, 'shortcode_facture'));
        
        // Handler pour afficher la facture
        add_action('init', array($this, 'handle_facture_display'));
        
        // Hooks pour les paiements
        add_action('init', array($this, 'handle_payment_callback'));
        
        // Cron pour les relances automatiques
        add_action('devis_pro_daily_cron', array($this, 'process_automatic_reminders'));
        
        // Admin bar menu
        add_action('admin_bar_menu', array($this, 'add_admin_bar_menu'), 100);
        add_action('wp_head', array($this, 'admin_bar_styles'));
        add_action('admin_head', array($this, 'admin_bar_styles'));
        
        // Migration depuis l'ancien plugin
        add_action('admin_notices', array($this, 'migration_notice'));
    }

    /**
     * Activation du plugin
     */
    public function activate() {
        $this->db->create_tables();
        
        // Planifier le cron pour les relances
        if (!wp_next_scheduled('devis_pro_daily_cron')) {
            wp_schedule_event(time(), 'daily', 'devis_pro_daily_cron');
        }
        
        // Options par défaut
        $default_options = array(
            'email_admin' => get_option('admin_email'),
            'email_from_name' => get_bloginfo('name'),
            'email_from_address' => 'devis@' . parse_url(home_url(), PHP_URL_HOST),
            'reminder_days' => 7,
            'auto_reminder' => false,
            'max_reminders' => 3,
            'monetico_tpe' => '7466577',
            'monetico_cle' => '',
            'monetico_societe' => 'agencedevo',
            'default_currency' => 'EUR',
            'statuses' => array(
                0 => array('label' => 'En attente', 'color' => '#ffc107'),
                1 => array('label' => 'Devis envoyé', 'color' => '#17a2b8'),
                2 => array('label' => 'Accepté', 'color' => '#28a745'),
                3 => array('label' => 'Refusé', 'color' => '#dc3545'),
                4 => array('label' => 'Payé', 'color' => '#28a745'),
                5 => array('label' => 'Annulé', 'color' => '#6c757d'),
                6 => array('label' => 'Archivé', 'color' => '#adb5bd')
            )
        );
        
        if (!get_option('devis_pro_settings')) {
            update_option('devis_pro_settings', $default_options);
        }
        
        flush_rewrite_rules();
    }

    /**
     * Désactivation du plugin
     */
    public function deactivate() {
        wp_clear_scheduled_hook('devis_pro_daily_cron');
        flush_rewrite_rules();
    }

    /**
     * Ajouter les menus admin
     */
    public function add_admin_menu() {
        // Menu principal
        add_menu_page(
            'Devis Pro',
            'Devis Pro',
            'manage_options',
            'devis-pro',
            array($this, 'page_dashboard'),
            'dashicons-clipboard',
            26
        );
        
        // Sous-menus
        add_submenu_page(
            'devis-pro',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'devis-pro',
            array($this, 'page_dashboard')
        );
        
        add_submenu_page(
            'devis-pro',
            'Tous les devis',
            'Tous les devis',
            'manage_options',
            'devis-pro-list',
            array($this, 'page_list')
        );
        
        add_submenu_page(
            'devis-pro',
            'Ajouter un devis',
            'Ajouter',
            'manage_options',
            'devis-pro-add',
            array($this, 'page_add')
        );
        
        add_submenu_page(
            'devis-pro',
            'Export',
            'Export',
            'manage_options',
            'devis-pro-export',
            array($this, 'page_export')
        );
        
        add_submenu_page(
            'devis-pro',
            'Réglages',
            'Réglages',
            'manage_options',
            'devis-pro-settings',
            array($this, 'page_settings')
        );
        
        add_submenu_page(
            'devis-pro',
            'Migration',
            'Migration',
            'manage_options',
            'devis-pro-migration',
            array($this, 'page_migration')
        );
        
        // Pages cachées
        add_submenu_page(
            null,
            'Détail du devis',
            'Détail',
            'manage_options',
            'devis-pro-detail',
            array($this, 'page_detail')
        );
    }

    /**
     * Charger les assets admin
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'devis-pro') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'devis-pro-admin',
            DEVIS_PRO_URL . 'assets/css/admin.css',
            array(),
            DEVIS_PRO_VERSION
        );
        
        // Chart.js pour les graphiques (uniquement sur le dashboard)
        if ($hook === 'toplevel_page_devis-pro') {
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
                array(),
                '4.4.0',
                true
            );
        }
        
        // JS
        wp_enqueue_script(
            'devis-pro-admin',
            DEVIS_PRO_URL . 'assets/js/admin.js',
            array('jquery'),
            DEVIS_PRO_VERSION,
            true
        );
        
        wp_localize_script('devis-pro-admin', 'devisProAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('devis_pro_nonce'),
            'strings' => array(
                'confirm_delete' => __('Êtes-vous sûr de vouloir supprimer ce devis ?', 'devis-pro'),
                'confirm_bulk_delete' => __('Êtes-vous sûr de vouloir supprimer les devis sélectionnés ?', 'devis-pro'),
                'note_added' => __('Note ajoutée avec succès', 'devis-pro'),
                'error' => __('Une erreur est survenue', 'devis-pro'),
                'loading' => __('Chargement...', 'devis-pro')
            )
        ));
    }

    /**
     * Page Dashboard
     */
    public function page_dashboard() {
        $stats = new Devis_Pro_Stats();
        $data = $stats->get_dashboard_data();
        include DEVIS_PRO_PATH . 'admin/views/dashboard.php';
    }

    /**
     * Page Liste des devis
     */
    public function page_list() {
        $list_table = new Devis_Pro_List_Table();
        $list_table->prepare_items();
        include DEVIS_PRO_PATH . 'admin/views/list.php';
    }

    /**
     * Page Ajouter un devis
     */
    public function page_add() {
        include DEVIS_PRO_PATH . 'admin/views/add.php';
    }

    /**
     * Page Détail d'un devis
     */
    public function page_detail() {
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            wp_die(__('Devis non trouvé', 'devis-pro'));
        }
        
        $devis_id = intval($_GET['id']);
        $devis = $this->db->get_devis($devis_id);
        
        if (!$devis) {
            wp_die(__('Devis non trouvé', 'devis-pro'));
        }
        
        $notes = $this->db->get_notes($devis_id);
        $history = $this->db->get_history($devis_id);
        $settings = get_option('devis_pro_settings');
        
        include DEVIS_PRO_PATH . 'admin/views/detail.php';
    }

    /**
     * Page Export
     */
    public function page_export() {
        include DEVIS_PRO_PATH . 'admin/views/export.php';
    }

    /**
     * Page Réglages
     */
    public function page_settings() {
        if (isset($_POST['devis_pro_save_settings']) && check_admin_referer('devis_pro_settings')) {
            $this->save_settings();
        }
        
        $settings = get_option('devis_pro_settings');
        include DEVIS_PRO_PATH . 'admin/views/settings.php';
    }

    /**
     * Page Migration
     */
    public function page_migration() {
        include DEVIS_PRO_PATH . 'admin/views/migration.php';
    }

    /**
     * Sauvegarder les réglages
     */
    private function save_settings() {
        // Traiter les statuts
        $statuses = array();
        if (isset($_POST['statuses']) && is_array($_POST['statuses'])) {
            foreach ($_POST['statuses'] as $key => $status) {
                $statuses[intval($key)] = array(
                    'label' => sanitize_text_field($status['label']),
                    'color' => sanitize_hex_color($status['color']) ?: '#6c757d'
                );
            }
            // Trier par clé
            ksort($statuses);
        } else {
            // Fallback aux statuts existants
            $statuses = get_option('devis_pro_settings')['statuses'];
        }
        
        $settings = array(
            'email_admin' => sanitize_email($_POST['email_admin']),
            'email_from_name' => sanitize_text_field($_POST['email_from_name']),
            'email_from_address' => sanitize_email($_POST['email_from_address']),
            'reminder_days' => intval($_POST['reminder_days']),
            'auto_reminder' => isset($_POST['auto_reminder']),
            'max_reminders' => intval($_POST['max_reminders']),
            'monetico_tpe' => sanitize_text_field($_POST['monetico_tpe']),
            'monetico_cle' => sanitize_text_field($_POST['monetico_cle']),
            'monetico_societe' => sanitize_text_field($_POST['monetico_societe']),
            'default_currency' => sanitize_text_field($_POST['default_currency']),
            'recaptcha_site_key' => sanitize_text_field($_POST['recaptcha_site_key'] ?? ''),
            'recaptcha_secret_key' => sanitize_text_field($_POST['recaptcha_secret_key'] ?? ''),
            'statuses' => $statuses
        );
        
        update_option('devis_pro_settings', $settings);
        add_settings_error('devis_pro', 'settings_updated', __('Réglages sauvegardés', 'devis-pro'), 'updated');
    }

    /**
     * Gérer les actions admin
     */
    public function handle_admin_actions() {
        // Envoyer un email test
        if (isset($_POST['devis_pro_send_test_email']) && check_admin_referer('devis_pro_test_email')) {
            $this->send_test_email();
        }
        
        // Suppression d'un devis
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_devis_' . $_GET['id'])) {
                wp_die(__('Action non autorisée', 'devis-pro'));
            }
            
            $this->db->delete_devis(intval($_GET['id']));
            wp_redirect(admin_url('admin.php?page=devis-pro-list&deleted=1'));
            exit;
        }
        
        // Création d'un devis depuis l'admin
        if (isset($_POST['devis_pro_add']) && check_admin_referer('devis_pro_add')) {
            $this->create_devis_from_admin();
        }
        
        // Mise à jour d'un devis
        if (isset($_POST['devis_pro_update']) && check_admin_referer('devis_pro_update_' . $_POST['devis_id'])) {
            $this->update_devis_from_admin();
        }
        
        // Export CSV
        if (isset($_POST['devis_pro_export_csv']) && check_admin_referer('devis_pro_export')) {
            $export = new Devis_Pro_Export();
            $export->export_csv($_POST);
        }
    }

    /**
     * Créer un devis depuis l'admin
     */
    private function create_devis_from_admin() {
        $data = $this->sanitize_devis_data($_POST);
        $data['demande'] = current_time('mysql');
        $data['status'] = 0;
        
        $id = $this->db->insert_devis($data);
        
        if ($id) {
            $this->db->add_history($id, 'creation', __('Devis créé depuis l\'administration', 'devis-pro'));
            wp_redirect(admin_url('admin.php?page=devis-pro-detail&id=' . $id . '&created=1'));
            exit;
        }
    }

    /**
     * Mettre à jour un devis depuis l'admin
     */
    private function update_devis_from_admin() {
        $id = intval($_POST['devis_id']);
        $old_data = $this->db->get_devis($id);
        
        $data = array(
            'montant' => floatval($_POST['montant']),
            'status' => intval($_POST['status']),
            'langue' => sanitize_text_field($_POST['langue'])
        );
        
        // Générer le token si montant défini et statut = devis envoyé
        if ($data['montant'] > 0 && $data['status'] == 1 && empty($old_data->token)) {
            $data['token'] = $this->generate_payment_token($old_data);
        }
        
        $this->db->update_devis($id, $data);
        
        // Logger les changements
        $status_changed = ($old_data->status != $data['status']);
        if ($status_changed) {
            $settings = get_option('devis_pro_settings');
            $old_status = $settings['statuses'][$old_data->status]['label'] ?? 'Inconnu';
            $new_status = $settings['statuses'][$data['status']]['label'] ?? 'Inconnu';
            $this->db->add_history($id, 'status_change', sprintf(__('Statut modifié : %s → %s', 'devis-pro'), $old_status, $new_status));
        }
        
        if ($old_data->montant != $data['montant']) {
            $this->db->add_history($id, 'amount_change', sprintf(__('Montant modifié : %s€ → %s€', 'devis-pro'), $old_data->montant, $data['montant']));
        }
        
        // Envoyer un email si demandé et si le statut a changé
        $send_email = isset($_POST['send_status_email']) && $status_changed;
        if ($send_email) {
            $updated_devis = $this->db->get_devis($id);
            $email = new Devis_Pro_Email();
            $email_sent = false;
            
            switch ($data['status']) {
                case 2: // Accepté
                    $email_sent = $email->send_status_accepted($updated_devis);
                    if ($email_sent) {
                        $this->db->add_history($id, 'email', __('Email "Accepté" envoyé au client', 'devis-pro'));
                    }
                    break;
                    
                case 4: // Payé
                    $email_sent = $email->send_payment_confirmation($updated_devis);
                    if ($email_sent) {
                        $this->db->add_history($id, 'email', __('Email "Paiement confirmé" envoyé au client', 'devis-pro'));
                    }
                    break;
                    
                case 5: // Annulé
                    $email_sent = $email->send_status_cancelled($updated_devis);
                    if ($email_sent) {
                        $this->db->add_history($id, 'email', __('Email "Annulé" envoyé au client', 'devis-pro'));
                    }
                    break;
            }
        }
        
        wp_redirect(admin_url('admin.php?page=devis-pro-detail&id=' . $id . '&updated=1'));
        exit;
    }

    /**
     * Générer un token de paiement
     */
    private function generate_payment_token($devis) {
        return sha1($devis->id . $devis->nom . $devis->prenom . $devis->tel . $devis->demande . time());
    }

    /**
     * Sanitizer les données du devis
     */
    private function sanitize_devis_data($post) {
        $data = array(
            'destination' => sanitize_text_field($post['destination'] ?? ''),
            'voyage' => sanitize_text_field($post['voyage'] ?? ''),
            'depart' => sanitize_text_field($post['depart'] ?? ''),
            'retour' => sanitize_text_field($post['retour'] ?? ''),
            'duree' => sanitize_text_field($post['duree'] ?? ''),
            'budget' => floatval($post['budget'] ?? 0),
            'adulte' => intval($post['adulte'] ?? 1),
            'enfant' => intval($post['enfant'] ?? 0),
            'bebe' => intval($post['bebe'] ?? 0),
            'vol' => sanitize_text_field($post['vol'] ?? ''),
            'message' => sanitize_textarea_field($post['message'] ?? ''),
            'civ' => sanitize_text_field($post['civ'] ?? ''),
            'nom' => sanitize_text_field($post['nom'] ?? ''),
            'prenom' => sanitize_text_field($post['prenom'] ?? ''),
            'email' => sanitize_email($post['email'] ?? ''),
            'cp' => sanitize_text_field($post['cp'] ?? ''),
            'ville' => sanitize_text_field($post['ville'] ?? ''),
            'tel' => sanitize_text_field($post['tel'] ?? ''),
            'montant' => floatval($post['montant'] ?? 0),
            'langue' => sanitize_text_field($post['langue'] ?? 'fr'),
            'newsletter' => isset($post['newsletter']) && $post['newsletter'] == '1' ? 1 : 0
        );
        
        return $data;
    }

    /**
     * AJAX: Mettre à jour le statut
     */
    public function ajax_update_status() {
        check_ajax_referer('devis_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Non autorisé', 'devis-pro'));
        }
        
        $id = intval($_POST['id']);
        $status = intval($_POST['status']);
        
        $old_data = $this->db->get_devis($id);
        $this->db->update_devis($id, array('status' => $status));
        
        $settings = get_option('devis_pro_settings');
        $old_status = $settings['statuses'][$old_data->status]['label'] ?? 'Inconnu';
        $new_status = $settings['statuses'][$status]['label'] ?? 'Inconnu';
        $this->db->add_history($id, 'status_change', sprintf(__('Statut modifié : %s → %s', 'devis-pro'), $old_status, $new_status));
        
        wp_send_json_success(array(
            'message' => __('Statut mis à jour', 'devis-pro'),
            'status_label' => $new_status,
            'status_color' => $settings['statuses'][$status]['color'] ?? '#6c757d'
        ));
    }

    /**
     * AJAX: Ajouter une note
     */
    public function ajax_add_note() {
        check_ajax_referer('devis_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Non autorisé', 'devis-pro'));
        }
        
        $devis_id = intval($_POST['devis_id']);
        $content = sanitize_textarea_field($_POST['content']);
        
        if (empty($content)) {
            wp_send_json_error(__('La note ne peut pas être vide', 'devis-pro'));
        }
        
        $note_id = $this->db->add_note($devis_id, $content, get_current_user_id());
        
        if ($note_id) {
            $user = wp_get_current_user();
            wp_send_json_success(array(
                'id' => $note_id,
                'content' => $content,
                'author' => $user->display_name,
                'date' => current_time('d/m/Y H:i')
            ));
        } else {
            wp_send_json_error(__('Erreur lors de l\'ajout de la note', 'devis-pro'));
        }
    }

    /**
     * AJAX: Supprimer une note
     */
    public function ajax_delete_note() {
        check_ajax_referer('devis_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Non autorisé', 'devis-pro'));
        }
        
        $note_id = intval($_POST['note_id']);
        $this->db->delete_note($note_id);
        
        wp_send_json_success(__('Note supprimée', 'devis-pro'));
    }

    /**
     * AJAX: Actions groupées
     */
    public function ajax_bulk_action() {
        // Nettoyer le buffer pour éviter que les warnings PHP ne corrompent le JSON
        if (ob_get_level()) {
            ob_clean();
        }
        
        check_ajax_referer('devis_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Non autorisé', 'devis-pro'));
        }
        
        $action = sanitize_text_field($_POST['bulk_action']);
        $ids = array_map('intval', $_POST['ids']);
        
        switch ($action) {
            case 'delete':
                foreach ($ids as $id) {
                    $this->db->delete_devis($id);
                }
                wp_send_json_success(sprintf(__('%d devis supprimés', 'devis-pro'), count($ids)));
                break;
                
            case 'archive':
                foreach ($ids as $id) {
                    $this->db->update_devis($id, array('status' => 6));
                    $this->db->add_history($id, 'archive', __('Devis archivé (action groupée)', 'devis-pro'));
                }
                wp_send_json_success(sprintf(__('%d devis archivés', 'devis-pro'), count($ids)));
                break;
                
            case 'mark_sent':
                foreach ($ids as $id) {
                    $this->db->update_devis($id, array('status' => 1));
                }
                wp_send_json_success(sprintf(__('%d devis marqués comme envoyés', 'devis-pro'), count($ids)));
                break;
                
            case 'send_reminder':
                // Désactiver l'affichage des erreurs pendant l'envoi
                $display_errors = ini_get('display_errors');
                @ini_set('display_errors', 0);
                
                $email = new Devis_Pro_Email();
                $sent = 0;
                foreach ($ids as $id) {
                    $devis = $this->db->get_devis($id);
                    if ($devis && $email->send_reminder($devis)) {
                        $sent++;
                        $this->db->add_history($id, 'reminder', __('Relance envoyée', 'devis-pro'));
                    }
                }
                
                // Restaurer l'affichage des erreurs
                @ini_set('display_errors', $display_errors);
                
                // Nettoyer le buffer
                if (ob_get_level()) {
                    ob_clean();
                }
                
                if ($sent > 0) {
                    wp_send_json_success(sprintf(__('%d relances envoyées', 'devis-pro'), $sent));
                } else {
                    wp_send_json_error(__('Aucune relance envoyée. Vérifiez la configuration SMTP.', 'devis-pro'));
                }
                break;
                
            default:
                wp_send_json_error(__('Action non reconnue', 'devis-pro'));
        }
    }

    /**
     * AJAX: Envoyer une relance
     */
    public function ajax_send_reminder() {
        // Nettoyer le buffer pour éviter que les warnings PHP ne corrompent le JSON
        if (ob_get_level()) {
            ob_clean();
        }
        
        check_ajax_referer('devis_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Non autorisé', 'devis-pro'));
        }
        
        $id = intval($_POST['id']);
        $devis = $this->db->get_devis($id);
        
        if (!$devis) {
            wp_send_json_error(__('Devis non trouvé', 'devis-pro'));
        }
        
        // Désactiver l'affichage des erreurs pendant l'envoi
        $display_errors = ini_get('display_errors');
        @ini_set('display_errors', 0);
        
        $email = new Devis_Pro_Email();
        $result = $email->send_reminder($devis);
        
        // Restaurer l'affichage des erreurs
        @ini_set('display_errors', $display_errors);
        
        // Nettoyer à nouveau le buffer
        if (ob_get_level()) {
            ob_clean();
        }
        
        if ($result) {
            $this->db->increment_reminders($id);
            $this->db->add_history($id, 'reminder', __('Relance envoyée manuellement', 'devis-pro'));
            wp_send_json_success(__('Relance envoyée avec succès', 'devis-pro'));
        } else {
            wp_send_json_error(__('Erreur lors de l\'envoi de la relance. Vérifiez la configuration SMTP.', 'devis-pro'));
        }
    }

    /**
     * AJAX: Dupliquer un devis
     */
    public function ajax_duplicate() {
        check_ajax_referer('devis_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Non autorisé', 'devis-pro'));
        }
        
        $id = intval($_POST['id']);
        $original = $this->db->get_devis($id);
        
        if (!$original) {
            wp_send_json_error(__('Devis non trouvé', 'devis-pro'));
        }
        
        $data = (array) $original;
        unset($data['id']);
        $data['demande'] = current_time('mysql');
        $data['status'] = 0;
        $data['montant'] = 0;
        $data['token'] = '';
        $data['mac'] = '';
        $data['reminders_count'] = 0;
        $data['last_reminder'] = null;
        
        $new_id = $this->db->insert_devis($data);
        
        if ($new_id) {
            $this->db->add_history($new_id, 'creation', sprintf(__('Devis dupliqué depuis #%d', 'devis-pro'), $id));
            wp_send_json_success(array(
                'id' => $new_id,
                'url' => admin_url('admin.php?page=devis-pro-detail&id=' . $new_id)
            ));
        } else {
            wp_send_json_error(__('Erreur lors de la duplication', 'devis-pro'));
        }
    }

    /**
     * AJAX: Envoyer le lien de paiement par email
     */
    public function ajax_migrate() {
        check_ajax_referer('devis_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Non autorisé', 'devis-pro'));
        }
        
        $result = $this->db->migrate_from_old_plugin();
        
        if ($result === false) {
            wp_send_json_error(__('Aucune table ancienne trouvée ou migration déjà effectuée', 'devis-pro'));
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d devis migrés avec succès', 'devis-pro'), $result),
            'count' => $result
        ));
    }

    public function ajax_send_payment_link() {
        // Nettoyer le buffer pour éviter que les warnings PHP ne corrompent le JSON
        if (ob_get_level()) {
            ob_clean();
        }
        
        check_ajax_referer('devis_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Non autorisé', 'devis-pro'));
        }
        
        $id = intval($_POST['id']);
        $custom_message = isset($_POST['custom_message']) ? sanitize_textarea_field($_POST['custom_message']) : '';
        $devis = $this->db->get_devis($id);
        
        if (!$devis) {
            wp_send_json_error(__('Devis non trouvé', 'devis-pro'));
        }
        
        if (empty($devis->token)) {
            wp_send_json_error(__('Le devis n\'a pas de token de paiement. Mettez à jour le montant et le statut d\'abord.', 'devis-pro'));
        }
        
        if ($devis->montant <= 0) {
            wp_send_json_error(__('Le montant du devis doit être supérieur à 0', 'devis-pro'));
        }
        
        // Capturer les erreurs potentielles
        $email = new Devis_Pro_Email();
        
        // Désactiver l'affichage des erreurs pendant l'envoi
        $display_errors = ini_get('display_errors');
        @ini_set('display_errors', 0);
        
        $result = $email->send_payment_link($devis, $custom_message);
        
        // Restaurer l'affichage des erreurs
        @ini_set('display_errors', $display_errors);
        
        // Nettoyer à nouveau le buffer au cas où des erreurs se seraient affichées
        if (ob_get_level()) {
            ob_clean();
        }
        
        if ($result) {
            $history_message = __('Lien de paiement envoyé par email', 'devis-pro');
            if (!empty($custom_message)) {
                $history_message .= ' (avec message personnalisé)';
            }
            $this->db->add_history($id, 'email', $history_message);
            wp_send_json_success(__('Email envoyé avec succès à ', 'devis-pro') . $devis->email);
        } else {
            // Message d'erreur plus détaillé
            $error_message = __('Erreur lors de l\'envoi de l\'email.', 'devis-pro');
            $error_message .= "\n\n" . __('Solutions possibles :', 'devis-pro');
            $error_message .= "\n• " . __('Installer le plugin WP Mail SMTP', 'devis-pro');
            $error_message .= "\n• " . __('Configurer les paramètres SMTP', 'devis-pro');
            $error_message .= "\n• " . __('Vérifier que l\'email du destinataire est valide', 'devis-pro');
            
            wp_send_json_error($error_message);
        }
    }

    /**
     * AJAX: Obtenir les statistiques
     */
    public function ajax_get_stats() {
        check_ajax_referer('devis_pro_nonce', 'nonce');
        
        $stats = new Devis_Pro_Stats();
        $period = sanitize_text_field($_POST['period'] ?? 'month');
        
        wp_send_json_success($stats->get_chart_data($period));
    }

    /**
     * Traiter les relances automatiques (cron)
     */
    public function process_automatic_reminders() {
        $settings = get_option('devis_pro_settings');
        
        if (!$settings['auto_reminder']) {
            return;
        }
        
        $devis_list = $this->db->get_devis_for_reminder(
            $settings['reminder_days'],
            $settings['max_reminders']
        );
        
        $email = new Devis_Pro_Email();
        
        foreach ($devis_list as $devis) {
            if ($email->send_reminder($devis)) {
                $this->db->increment_reminders($devis->id);
                $this->db->add_history($devis->id, 'auto_reminder', __('Relance automatique envoyée', 'devis-pro'));
            }
        }
    }

    /**
     * Shortcode: Formulaire de demande de devis
     */
    public function shortcode_form($atts) {
        $atts = shortcode_atts(array(
            'voyage' => '',
            'destination' => ''
        ), $atts);
        
        $error = '';
        
        // Note: Le formulaire est maintenant soumis en AJAX
        // Le traitement se fait via ajax_submit_form()
        // On garde le fallback pour les navigateurs sans JS
        if (!wp_doing_ajax() && isset($_POST['devis_pro_submit']) && isset($_POST['devis_pro_nonce']) && wp_verify_nonce($_POST['devis_pro_nonce'], 'devis_pro_form')) {
            $result = $this->process_front_form($_POST, $atts);
            if ($result['success']) {
                ob_start();
                include DEVIS_PRO_PATH . 'views/form-success.php';
                return ob_get_clean();
            }
            $error = $result['error'];
        }
        
        ob_start();
        include DEVIS_PRO_PATH . 'views/form-legacy.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulaire complet pleine page avec autocomplétion
     */
    public function shortcode_form_full($atts) {
        $atts = shortcode_atts(array(
            'voyage' => '',
            'destination' => ''
        ), $atts);
        
        // Traitement du formulaire
        if (isset($_POST['devis_pro_submit']) && isset($_POST['devis_pro_nonce']) && wp_verify_nonce($_POST['devis_pro_nonce'], 'devis_pro_form')) {
            $result = $this->process_front_form($_POST, $atts);
            if ($result['success']) {
                ob_start();
                include DEVIS_PRO_PATH . 'views/form-success.php';
                return ob_get_clean();
            }
            $error = $result['error'];
        }
        
        ob_start();
        include DEVIS_PRO_PATH . 'views/form-full.php';
        return ob_get_clean();
    }

    /**
     * AJAX: Rechercher des voyages Tripzzy (autocomplétion)
     */
    public function ajax_search_voyages() {
        check_ajax_referer('devis_pro_search', 'nonce');
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        
        if (strlen($query) < 2) {
            wp_send_json_success(array());
        }
        
        // Rechercher dans les voyages Tripzzy
        $args = array(
            'post_type' => 'tripzzy',
            'post_status' => 'publish',
            's' => $query,
            'posts_per_page' => 10,
            'orderby' => 'relevance'
        );
        
        $posts = get_posts($args);
        
        $results = array();
        foreach ($posts as $post) {
            $thumbnail = get_the_post_thumbnail_url($post->ID, 'thumbnail');
            $destination = '';
            $duration = '';
            
            // Récupérer la destination Tripzzy
            $terms = get_the_terms($post->ID, 'tripzzy_trip_destination');
            if ($terms && !is_wp_error($terms)) {
                $destinations = array();
                foreach ($terms as $term) {
                    $destinations[] = $term->name;
                }
                $destination = implode(', ', $destinations);
            }
            
            // Récupérer la durée depuis les métadonnées Tripzzy
            $trip_duration = get_post_meta($post->ID, 'tripzzy_trip_duration', true);
            if ($trip_duration) {
                $duration = $trip_duration . ' jours';
            }
            
            $meta_info = array();
            if ($destination) $meta_info[] = $destination;
            if ($duration) $meta_info[] = $duration;
            
            $results[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'thumbnail' => $thumbnail ?: '',
                'destination' => implode(' • ', $meta_info)
            );
        }
        
        wp_send_json_success($results);
    }

    /**
     * AJAX: Obtenir les infos d'un voyage
     */
    public function ajax_get_voyage() {
        check_ajax_referer('devis_pro_search', 'nonce');
        
        $id = intval($_POST['id'] ?? 0);
        
        if (!$id) {
            wp_send_json_error('ID invalide');
        }
        
        $post = get_post($id);
        
        if (!$post) {
            wp_send_json_error('Voyage non trouvé');
        }
        
        $thumbnail = get_the_post_thumbnail_url($id, 'thumbnail');
        
        wp_send_json_success(array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'thumbnail' => $thumbnail ?: ''
        ));
    }
    
    /**
     * Traiter l'ancien formulaire (compatibilité)
     */
    private function process_old_form($post, $atts) {
        // Mapper les anciens noms de champs vers les nouveaux
        $mapped = array(
            'voyage' => $post['voyage'] ?? $atts['voyage'] ?? '',
            'destination' => $post['products'] ?? $atts['destination'] ?? '',
            'depart' => $post['date-sejour-depart'] ?? '',
            'retour' => $post['date-sejour-retour'] ?? '',
            'duree' => $post['duree-sejour'] ?? '',
            'budget' => floatval($post['budget-sejour'] ?? 0),
            'adulte' => intval($post['nbre-adulte'] ?? 1),
            'enfant' => intval($post['nbre-enfants'] ?? 0),
            'bebe' => intval($post['nbre-bebes'] ?? 0),
            'vol' => $post['vols-inclus'] ?? '',
            'message' => sanitize_textarea_field($post['message'] ?? ''),
            'civ' => sanitize_text_field($post['civilite'] ?? ''),
            'nom' => sanitize_text_field($post['nom'] ?? ''),
            'prenom' => sanitize_text_field($post['prenom'] ?? ''),
            'email' => sanitize_email($post['email'] ?? ''),
            'cp' => sanitize_text_field($post['cp'] ?? ''),
            'ville' => sanitize_text_field($post['ville'] ?? ''),
            'tel' => sanitize_text_field($post['tel'] ?? ''),
            'montant' => 0,
            'langue' => 'fr',
            'status' => 0,
            'demande' => current_time('mysql'),
            'token' => '',
            'mac' => ''
        );
        
        // Ajouter newsletter 
        $mapped['newsletter'] = isset($post['newsletter']) && $post['newsletter'] == '1' ? 1 : 0;
        
        // Validation
        if (empty($mapped['email']) || !is_email($mapped['email'])) {
            return array('success' => false, 'error' => 'Email invalide');
        }
        
        if (empty($mapped['tel'])) {
            return array('success' => false, 'error' => 'Téléphone requis');
        }
        
        // Insérer dans la base de données
        $id = $this->db->insert_devis($mapped);
        
        if ($id) {
            // Récupérer le titre de la page actuelle
            $page_title = get_the_title();
            $history_message = $page_title 
                ? sprintf('Demande reçue via le formulaire de la page "%s"', $page_title)
                : 'Demande reçue via le formulaire';
            $this->db->add_history($id, 'creation', $history_message);
            
            // Envoyer les emails (avec gestion des erreurs silencieuse)
            $devis = $this->db->get_devis($id);
            if ($devis) {
                // Désactiver l'affichage des erreurs pendant l'envoi
                $display_errors = ini_get('display_errors');
                @ini_set('display_errors', 0);
                
                try {
                    $email = new Devis_Pro_Email();
                    $email->send_new_request_notification($devis);
                    $email->send_confirmation_to_client($devis);
                } catch (Exception $e) {
                    // Logger l'erreur mais ne pas bloquer
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('[Devis Pro] Erreur envoi email: ' . $e->getMessage());
                    }
                }
                
                // Restaurer l'affichage des erreurs
                @ini_set('display_errors', $display_errors);
                
                // Inscription newsletter Mailchimp si cochée
                if (!empty($post['newsletter'])) {
                    $this->subscribe_to_mailchimp($mapped['email'], $mapped['prenom'], $mapped['nom']);
                }
            }
            
            return array('success' => true, 'id' => $id);
        }
        
        return array('success' => false, 'error' => 'Erreur lors de l\'enregistrement');
    }

    /**
     * Traiter le formulaire legacy via AJAX
     */
    private function process_old_form_ajax($post, $atts) {
        // Mapper les anciens noms de champs vers les nouveaux
        $mapped = array(
            'voyage' => $post['voyage'] ?? $atts['voyage'] ?? '',
            'destination' => $post['products'] ?? $atts['destination'] ?? '',
            'depart' => $post['date-sejour-depart'] ?? '',
            'retour' => $post['date-sejour-retour'] ?? '',
            'duree' => $post['duree-sejour'] ?? '',
            'budget' => floatval($post['budget-sejour'] ?? 0),
            'adulte' => intval($post['nbre-adulte'] ?? 1),
            'enfant' => intval($post['nbre-enfants'] ?? 0),
            'bebe' => intval($post['nbre-bebes'] ?? 0),
            'vol' => $post['vols-inclus'] ?? '',
            'message' => sanitize_textarea_field($post['message'] ?? ''),
            'civ' => sanitize_text_field($post['civilite'] ?? ''),
            'nom' => sanitize_text_field($post['nom'] ?? ''),
            'prenom' => sanitize_text_field($post['prenom'] ?? ''),
            'email' => sanitize_email($post['email'] ?? ''),
            'cp' => sanitize_text_field($post['cp'] ?? ''),
            'ville' => sanitize_text_field($post['ville'] ?? ''),
            'tel' => sanitize_text_field($post['tel'] ?? ''),
            'montant' => 0,
            'langue' => 'fr',
            'status' => 0,
            'demande' => current_time('mysql'),
            'token' => '',
            'mac' => ''
        );
        
        // Ajouter newsletter
        $mapped['newsletter'] = isset($post['newsletter']) && $post['newsletter'] == '1' ? 1 : 0;
        
        // Validation avec sécurité
        $email = Devis_Pro_Security::validate_email($mapped['email']);
        if ($email === false) {
            return array('success' => false, 'error' => __('Adresse email invalide', 'devis-pro'));
        }
        $mapped['email'] = $email;
        
        $tel = Devis_Pro_Security::validate_phone($mapped['tel']);
        if ($tel === false) {
            return array('success' => false, 'error' => __('Numéro de téléphone invalide', 'devis-pro'));
        }
        $mapped['tel'] = $tel;
        
        // Insérer dans la base de données
        $id = $this->db->insert_devis($mapped);
        
        if ($id) {
            // Historique avec le titre de la page
            $page_title = $atts['page_title'] ?: 'Formulaire';
            $history_message = sprintf(__('Demande reçue via le formulaire de la page "%s"', 'devis-pro'), $page_title);
            $this->db->add_history($id, 'creation', $history_message);
            
            // Envoyer les emails
            $devis = $this->db->get_devis($id);
            if ($devis) {
                try {
                    $email_handler = new Devis_Pro_Email();
                    $email_handler->send_new_request_notification($devis);
                    $email_handler->send_confirmation_to_client($devis);
                } catch (Exception $e) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('[Devis Pro] Erreur envoi email: ' . $e->getMessage());
                    }
                }
            }
            
            // Inscription newsletter Mailchimp si cochée
            if (!empty($post['newsletter'])) {
                $this->subscribe_to_mailchimp($mapped['email'], $mapped['prenom'], $mapped['nom']);
            }
            
            return array('success' => true, 'id' => $id);
        }
        
        return array('success' => false, 'error' => __('Erreur lors de l\'enregistrement', 'devis-pro'));
    }

    /**
     * Traiter le formulaire front-end
     */
    private function process_front_form($post, $atts) {
        // ========================================
        // VÉRIFICATIONS DE SÉCURITÉ
        // ========================================
        
        // Vérification globale (honeypot, rate limit, brute force)
        $security_check = Devis_Pro_Security::validate_form_submission($post);
        
        if (!$security_check['valid']) {
            // Si c'est un bot, retourner succès silencieux
            if (!empty($security_check['is_bot'])) {
                return array('success' => true, 'id' => 0); // Fake success pour les bots
            }
            return array('success' => false, 'error' => $security_check['error']);
        }
        
        // Vérification reCAPTCHA (si configuré)
        $settings = get_option('devis_pro_settings');
        $recaptcha_secret = $settings['recaptcha_secret_key'] ?? '';
        
        if (!empty($recaptcha_secret) && !empty($post['recaptcha_token'])) {
            if (!Devis_Pro_Security::verify_recaptcha($post['recaptcha_token'], $recaptcha_secret)) {
                return array('success' => false, 'error' => __('Vérification de sécurité échouée. Veuillez réessayer.', 'devis-pro'));
            }
        }
        
        // ========================================
        // VALIDATION DES DONNÉES
        // ========================================
        
        // Email
        $email = Devis_Pro_Security::validate_email($post['email'] ?? '');
        if ($email === false) {
            return array('success' => false, 'error' => __('Adresse email invalide', 'devis-pro'));
        }
        
        // Téléphone
        $tel = Devis_Pro_Security::validate_phone($post['tel'] ?? '');
        if ($tel === false) {
            return array('success' => false, 'error' => __('Numéro de téléphone invalide (10 chiffres minimum)', 'devis-pro'));
        }
        
        // Nom et prénom
        $nom = Devis_Pro_Security::validate_name($post['nom'] ?? '');
        $prenom = Devis_Pro_Security::validate_name($post['prenom'] ?? '');
        if ($nom === false || $prenom === false) {
            return array('success' => false, 'error' => __('Nom ou prénom invalide', 'devis-pro'));
        }
        
        // ========================================
        // TRAITEMENT DES DONNÉES
        // ========================================
        
        $data = $this->sanitize_devis_data($post);
        
        // Utiliser les données validées
        $data['email'] = $email;
        $data['tel'] = $tel;
        $data['nom'] = $nom;
        $data['prenom'] = $prenom;
        
        $data['voyage'] = $atts['voyage'] ?: $data['voyage'];
        $data['destination'] = $atts['destination'] ?: $data['destination'];
        $data['demande'] = current_time('mysql');
        $data['status'] = 0;
        
        $id = $this->db->insert_devis($data);
        
        if ($id) {
            // Récupérer le titre de la page actuelle
            $page_title = get_the_title();
            $history_message = $page_title 
                ? sprintf(__('Demande reçue via le formulaire de la page "%s"', 'devis-pro'), $page_title)
                : __('Demande reçue via le formulaire', 'devis-pro');
            $this->db->add_history($id, 'creation', $history_message);
            
            // Envoyer les emails
            $email_handler = new Devis_Pro_Email();
            $devis = $this->db->get_devis($id);
            $email_handler->send_new_request_notification($devis);
            $email_handler->send_confirmation_to_client($devis);
            
            return array('success' => true, 'id' => $id);
        }
        
        return array('success' => false, 'error' => __('Erreur lors de l\'enregistrement', 'devis-pro'));
    }

    /**
     * AJAX: Soumission du formulaire front-end (sidebar)
     */
    public function ajax_submit_form() {
        // Vérifier le nonce
        if (!check_ajax_referer('devis_pro_form', 'devis_pro_nonce', false)) {
            wp_send_json_error(__('Session expirée. Veuillez recharger la page.', 'devis-pro'));
        }
        
        // Vérifier honeypot
        if (!Devis_Pro_Security::check_honeypot($_POST)) {
            // Bot détecté - faux succès
            ob_start();
            include DEVIS_PRO_PATH . 'views/form-success.php';
            $html = ob_get_clean();
            wp_send_json_success(array('html' => $html));
        }
        
        // Vérifier rate limit
        if (!Devis_Pro_Security::check_rate_limit('form_submit')) {
            wp_send_json_error(__('Trop de demandes. Veuillez réessayer plus tard.', 'devis-pro'));
        }
        
        // Préparer les attributs
        $atts = array(
            'voyage' => sanitize_text_field($_POST['voyage'] ?? ''),
            'destination' => sanitize_text_field($_POST['destination'] ?? ''),
            'page_title' => sanitize_text_field($_POST['page_title'] ?? '')
        );
        
        // Déterminer quel format de formulaire (legacy ou nouveau)
        if (isset($_POST['send']) && $_POST['send'] == '1') {
            // Format legacy
            $result = $this->process_old_form_ajax($_POST, $atts);
        } else {
            // Format nouveau
            $result = $this->process_front_form_ajax($_POST, $atts);
        }
        
        if ($result['success']) {
            // Retourner le HTML du message de succès
            ob_start();
            include DEVIS_PRO_PATH . 'views/form-success.php';
            $html = ob_get_clean();
            
            wp_send_json_success(array(
                'message' => __('Demande envoyée avec succès !', 'devis-pro'),
                'html' => $html
            ));
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * Traitement du formulaire via AJAX
     */
    private function process_front_form_ajax($post, $atts) {
        // Vérifications de sécurité
        $security_check = Devis_Pro_Security::validate_form_submission($post);
        
        if (!$security_check['valid']) {
            if (!empty($security_check['is_bot'])) {
                return array('success' => true, 'id' => 0);
            }
            return array('success' => false, 'error' => $security_check['error']);
        }
        
        // Vérification reCAPTCHA (si configuré)
        $settings = get_option('devis_pro_settings');
        $recaptcha_secret = $settings['recaptcha_secret_key'] ?? '';
        
        if (!empty($recaptcha_secret) && !empty($post['recaptcha_token'])) {
            if (!Devis_Pro_Security::verify_recaptcha($post['recaptcha_token'], $recaptcha_secret)) {
                return array('success' => false, 'error' => __('Vérification de sécurité échouée.', 'devis-pro'));
            }
        }
        
        // Validation des données
        $email = Devis_Pro_Security::validate_email($post['email'] ?? '');
        if ($email === false) {
            return array('success' => false, 'error' => __('Adresse email invalide', 'devis-pro'));
        }
        
        $tel = Devis_Pro_Security::validate_phone($post['tel'] ?? '');
        if ($tel === false) {
            return array('success' => false, 'error' => __('Numéro de téléphone invalide', 'devis-pro'));
        }
        
        $nom = Devis_Pro_Security::validate_name($post['nom'] ?? '');
        $prenom = Devis_Pro_Security::validate_name($post['prenom'] ?? '');
        if ($nom === false || $prenom === false) {
            return array('success' => false, 'error' => __('Nom ou prénom invalide', 'devis-pro'));
        }
        
        // Traitement des données
        $data = $this->sanitize_devis_data($post);
        $data['email'] = $email;
        $data['tel'] = $tel;
        $data['nom'] = $nom;
        $data['prenom'] = $prenom;
        $data['voyage'] = $atts['voyage'] ?: $data['voyage'];
        $data['destination'] = $atts['destination'] ?: $data['destination'];
        $data['demande'] = current_time('mysql');
        $data['status'] = 0;
        
        $id = $this->db->insert_devis($data);
        
        if ($id) {
            // Historique avec le titre de la page
            $page_title = $atts['page_title'] ?: 'Formulaire';
            $history_message = sprintf(__('Demande reçue via le formulaire de la page "%s"', 'devis-pro'), $page_title);
            $this->db->add_history($id, 'creation', $history_message);
            
            // Envoyer les emails
            $email_handler = new Devis_Pro_Email();
            $devis = $this->db->get_devis($id);
            $email_handler->send_new_request_notification($devis);
            $email_handler->send_confirmation_to_client($devis);
            
            // Newsletter Mailchimp            
            return array('success' => true, 'id' => $id);
        }
        
        return array('success' => false, 'error' => __('Erreur lors de l\'enregistrement', 'devis-pro'));
    }

    /**
     * Inscription à la newsletter Mailchimp
     */
    private function subscribe_to_mailchimp($email, $prenom = '', $nom = '') {
        // Configuration Mailchimp
        $api_key = 'a3f93d63cb54bbc41ac09b517556d2f7-us7';
        $list_id = '431ec50da9';
        
        // Extraire le datacenter de la clé API
        $datacenter = substr($api_key, strpos($api_key, '-') + 1);
        
        $url = 'https://' . $datacenter . '.api.mailchimp.com/3.0/lists/' . $list_id . '/members';
        
        $data = array(
            'email_address' => $email,
            'status' => 'subscribed',
            'merge_fields' => array(
                'FNAME' => $prenom,
                'LNAME' => $nom
            )
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode('user:' . $api_key),
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 15
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Devis Pro] Erreur Mailchimp: ' . $response->get_error_message());
            }
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        // 200 = inscrit, 400 avec "Member Exists" = déjà inscrit (OK)
        if ($response_code == 200 || ($response_code == 400 && strpos($response_body['title'] ?? '', 'Member Exists') !== false)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Devis Pro] Mailchimp: ' . $email . ' inscrit avec succès');
            }
            return true;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Devis Pro] Erreur Mailchimp (' . $response_code . '): ' . json_encode($response_body));
        }
        
        return false;
    }

    /**
     * Shortcode: Page de paiement
     */
    public function shortcode_paiement($atts) {
        if (!isset($_GET['q'])) {
            return '<div style="text-align:center;padding:40px;"><p>' . __('Lien de paiement invalide', 'devis-pro') . '</p></div>';
        }
        
        $token = sanitize_text_field($_GET['q']);
        $devis = $this->db->get_devis_by_token($token);
        
        if (!$devis) {
            // Debug: log le token recherché
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Devis Pro - Token non trouvé: ' . $token);
            }
            return '<div style="text-align:center;padding:40px;"><p>' . __('Devis non trouvé. Veuillez contacter notre équipe.', 'devis-pro') . '</p><p><a href="mailto:contact@rdvasie.com">contact@rdvasie.com</a> | <a href="tel:0214001253">02 14 00 12 53</a></p></div>';
        }
        
        $settings = get_option('devis_pro_settings');
        
        // Générer les données Monetico
        $payment_data = $this->generate_monetico_data($devis, $settings);
        
        ob_start();
        include DEVIS_PRO_PATH . 'views/paiement/index.php';
        return ob_get_clean();
    }

    /**
     * Générer les données Monetico
     */
    private function generate_monetico_data($devis, $settings) {
        if ($devis->status != 1 || $devis->montant <= 0) {
            return null;
        }
        
        // Vérifier que la clé Monetico est configurée
        if (empty($settings['monetico_cle']) || strlen($settings['monetico_cle']) < 40) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Devis Pro] Clé Monetico non configurée ou invalide');
            }
            return null;
        }
        
        $cle = $this->get_monetico_key($settings['monetico_cle']);
        $date = wp_date("d/m/Y:H:i:s");
        $reference = "RDVASIE-" . str_pad($devis->id, 5, "0", STR_PAD_LEFT);
        
        $data_to_sign = sprintf(
            "%s*%s*%s%s*%s*%s*%s*%s*%s*%s*%s**********",
            $settings['monetico_tpe'],
            $date,
            $devis->montant,
            $settings['default_currency'],
            $reference,
            "Rendez-vous avec l'Asie",
            "3.0",
            "FR",
            $settings['monetico_societe'],
            $devis->email
        );
        
        $mac = hash_hmac("sha1", $data_to_sign, $cle);
        
        // Sauvegarder la référence
        $this->db->update_devis($devis->id, array('mac' => $reference));
        
        return array(
            'tpe' => $settings['monetico_tpe'],
            'date' => $date,
            'montant' => $devis->montant . $settings['default_currency'],
            'reference' => $reference,
            'mac' => $mac,
            'societe' => $settings['monetico_societe'],
            'email' => $devis->email
        );
    }

    /**
     * Décoder la clé Monetico
     */
    private function get_monetico_key($cle) {
        // Vérification de sécurité
        if (empty($cle) || strlen($cle) < 40) {
            return '';
        }
        
        $hexStrKey = substr($cle, 0, 38);
        $hexFinal = substr($cle, 38, 2) . "00";
        
        if (empty($hexFinal) || strlen($hexFinal) < 1) {
            return '';
        }
        
        $cca0 = ord($hexFinal[0]);
        
        if ($cca0 > 70 && $cca0 < 97) {
            $hexStrKey .= chr($cca0 - 23) . substr($hexFinal, 1, 1);
        } else {
            if (strlen($hexFinal) > 1 && $hexFinal[1] == "M") {
                $hexStrKey .= $hexFinal[0] . "0";
            } else {
                $hexStrKey .= substr($hexFinal, 0, 2);
            }
        }
        
        return pack("H*", $hexStrKey);
    }

    /**
     * Shortcode: Espace client
     */
    public function shortcode_espace_client($atts) {
        $form_submitted = false;
        $email_sent = false;
        
        // ========================================
        // VÉRIFICATION SÉCURITÉ
        // ========================================
        
        // Vérifier si l'IP est bloquée (protection brute force)
        if (Devis_Pro_Security::is_ip_blocked()) {
            return '<div style="text-align:center;padding:40px;"><p style="color:#dc3545;">⚠️ ' . __('Trop de tentatives. Veuillez réessayer dans 15 minutes.', 'devis-pro') . '</p></div>';
        }
        
        // Traiter le formulaire de connexion
        if (isset($_POST['client_email']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'client_access')) {
            
            // Vérifier le rate limit
            if (!Devis_Pro_Security::check_rate_limit('client_login')) {
                return '<div style="text-align:center;padding:40px;"><p style="color:#dc3545;">⚠️ ' . __('Trop de demandes. Veuillez réessayer plus tard.', 'devis-pro') . '</p></div>';
            }
            
            // Vérifier le honeypot
            if (!Devis_Pro_Security::check_honeypot($_POST)) {
                // Bot détecté - retourner un faux succès
                $form_submitted = true;
                $email_sent = true;
                ob_start();
                include DEVIS_PRO_PATH . 'views/client-login.php';
                return ob_get_clean();
            }
            
            $email = Devis_Pro_Security::validate_email($_POST['client_email'] ?? '');
            $form_submitted = true;
            
            if ($email) {
                $email_handler = new Devis_Pro_Email();
                $email_sent = $email_handler->send_client_access_link($email);
                
                // Log pour debug
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[Devis Pro] Espace client - Email: ' . $email . ' - Envoyé: ' . ($email_sent ? 'OUI' : 'NON'));
                }
            }
            
            // Afficher le formulaire avec le message de succès
            ob_start();
            include DEVIS_PRO_PATH . 'views/client-login.php';
            return ob_get_clean();
        }
        
        // Vérifier si accès via token
        if (isset($_GET['email']) && isset($_GET['token'])) {
            $email = sanitize_email($_GET['email']);
            $token = sanitize_text_field($_GET['token']);
            
            // Vérifier le token avec le nouveau système sécurisé
            $is_valid = Devis_Pro_Security::validate_access_token($token, $email);
            
            // Fallback: vérifier aussi l'ancien système (MD5) pour compatibilité
            if (!$is_valid) {
                $legacy_token = md5($email . wp_salt());
                $is_valid = ($token === $legacy_token);
            }
            
            if (!$is_valid) {
                Devis_Pro_Security::record_failed_attempt('client_access');
                return '<div style="text-align:center;padding:40px;"><p style="color:#dc3545;">❌ ' . __('Lien invalide ou expiré. Veuillez demander un nouveau lien.', 'devis-pro') . '</p></div>';
            }
            
            // Réinitialiser les tentatives échouées
            Devis_Pro_Security::reset_failed_attempts('client_access');
            
            $devis_list = $this->db->get_devis_by_email($email);
            
            ob_start();
            include DEVIS_PRO_PATH . 'views/client-dashboard.php';
            return ob_get_clean();
        }
        
        // Afficher le formulaire de connexion
        ob_start();
        include DEVIS_PRO_PATH . 'views/client-login.php';
        return ob_get_clean();
    }

    /**
     * Gérer les callbacks de paiement
     */
    public function handle_payment_callback() {
        // Détecter les retours Monetico
        if (isset($_GET['code-retour']) || isset($_POST['code-retour'])) {
            $code_retour = isset($_GET['code-retour']) ? sanitize_text_field($_GET['code-retour']) : sanitize_text_field($_POST['code-retour']);
            $reference = isset($_GET['reference']) ? sanitize_text_field($_GET['reference']) : sanitize_text_field($_POST['reference'] ?? '');
            
            if ($reference && strpos($reference, 'RDVASIE-') === 0) {
                $devis_id = intval(str_replace('RDVASIE-', '', $reference));
                $devis = $this->db->get_devis($devis_id);
                
                if ($devis) {
                    // Code-retour: "paiement_accepte" ou "paiement_annule"
                    if ($code_retour === 'paiement_accepte' || $code_retour === 'paiement-effectue') {
                        // Mettre à jour le statut
                        $this->db->update_devis($devis_id, array('status' => 4)); // Payé
                        $this->db->add_history($devis_id, 'payment', __('Paiement accepté', 'devis-pro'));
                        
                        // Envoyer email de confirmation
                        $email = new Devis_Pro_Email();
                        $email->send_payment_confirmation($devis);
                    }
                }
            }
        }
    }

    /**
     * Gérer l'affichage de la facture
     */
    public function handle_facture_display() {
        if (isset($_GET['facture']) && isset($_GET['token'])) {
            $token = sanitize_text_field($_GET['token']);
            $devis = $this->db->get_devis_by_token($token);
            
            if ($devis && $devis->montant > 0) {
                include DEVIS_PRO_PATH . 'views/facture.php';
                exit;
            }
        }
    }

    /**
     * Shortcode: Afficher la facture
     */
    public function shortcode_facture($atts) {
        if (!isset($_GET['token'])) {
            return '<p>' . __('Facture non trouvée', 'devis-pro') . '</p>';
        }
        
        $token = sanitize_text_field($_GET['token']);
        $devis = $this->db->get_devis_by_token($token);
        
        if (!$devis) {
            return '<p>' . __('Facture non trouvée', 'devis-pro') . '</p>';
        }
        
        ob_start();
        include DEVIS_PRO_PATH . 'views/facture.php';
        return ob_get_clean();
    }

    /**
     * AJAX: Archiver un devis
     */
    public function ajax_archive_devis() {
        check_ajax_referer('devis_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Non autorisé', 'devis-pro'));
        }
        
        $id = intval($_POST['id']);
        $devis = $this->db->get_devis($id);
        
        if (!$devis) {
            wp_send_json_error(__('Devis non trouvé', 'devis-pro'));
        }
        
        // Archiver = statut 6
        $this->db->update_devis($id, array('status' => 6));
        $this->db->add_history($id, 'archive', __('Devis archivé', 'devis-pro'));
        
        wp_send_json_success(__('Devis archivé avec succès', 'devis-pro'));
    }

    /**
     * AJAX: Restaurer un devis archivé
     */
    public function ajax_restore_devis() {
        check_ajax_referer('devis_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Non autorisé', 'devis-pro'));
        }
        
        $id = intval($_POST['id']);
        $devis = $this->db->get_devis($id);
        
        if (!$devis) {
            wp_send_json_error(__('Devis non trouvé', 'devis-pro'));
        }
        
        // Restaurer = statut 0 (En attente)
        $this->db->update_devis($id, array('status' => 0));
        $this->db->add_history($id, 'restore', __('Devis restauré depuis les archives', 'devis-pro'));
        
        wp_send_json_success(__('Devis restauré avec succès', 'devis-pro'));
    }

    /**
     * Ajouter le menu dans la barre admin
     */
    public function add_admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Compter les devis en attente (avec protection)
        $pending_count = 0;
        try {
            $pending_count = $this->db->count_devis(array('status' => 0));
        } catch (Exception $e) {
            // Table pas encore créée
        }
        $badge = $pending_count > 0 ? '<span class="devis-pro-badge">' . $pending_count . '</span>' : '';

        // Menu principal
        $wp_admin_bar->add_node(array(
            'id' => 'devis-pro',
            'title' => '<span class="ab-icon dashicons dashicons-clipboard"></span><span class="ab-label">Devis Pro</span>' . $badge,
            'href' => admin_url('admin.php?page=devis-pro'),
            'meta' => array(
                'class' => 'devis-pro-admin-bar',
                'title' => __('Gestion des devis', 'devis-pro')
            )
        ));

        // Sous-menu : Dashboard
        $wp_admin_bar->add_node(array(
            'id' => 'devis-pro-dashboard',
            'parent' => 'devis-pro',
            'title' => __('Dashboard', 'devis-pro'),
            'href' => admin_url('admin.php?page=devis-pro')
        ));

        // Sous-menu : Tous les devis
        $wp_admin_bar->add_node(array(
            'id' => 'devis-pro-list',
            'parent' => 'devis-pro',
            'title' => __('Tous les devis', 'devis-pro'),
            'href' => admin_url('admin.php?page=devis-pro-list')
        ));

        // Sous-menu : En attente (avec badge)
        if ($pending_count > 0) {
            $wp_admin_bar->add_node(array(
                'id' => 'devis-pro-pending',
                'parent' => 'devis-pro',
                'title' => __('En attente', 'devis-pro') . ' <span class="devis-pro-badge-inline">' . $pending_count . '</span>',
                'href' => admin_url('admin.php?page=devis-pro-list&status=0')
            ));
        }

        // Sous-menu : Ajouter
        $wp_admin_bar->add_node(array(
            'id' => 'devis-pro-add',
            'parent' => 'devis-pro',
            'title' => __('Ajouter un devis', 'devis-pro'),
            'href' => admin_url('admin.php?page=devis-pro-add')
        ));

        // Sous-menu : Export
        $wp_admin_bar->add_node(array(
            'id' => 'devis-pro-export',
            'parent' => 'devis-pro',
            'title' => __('Export', 'devis-pro'),
            'href' => admin_url('admin.php?page=devis-pro-export')
        ));

        // Sous-menu : Réglages
        $wp_admin_bar->add_node(array(
            'id' => 'devis-pro-settings',
            'parent' => 'devis-pro',
            'title' => __('Réglages', 'devis-pro'),
            'href' => admin_url('admin.php?page=devis-pro-settings')
        ));
    }

    /**
     * Styles pour la barre admin
     */
    public function admin_bar_styles() {
        if (!is_admin_bar_showing() || !current_user_can('manage_options')) {
            return;
        }
        ?>
        <style>
            #wpadminbar .devis-pro-admin-bar > .ab-item {
                background: linear-gradient(135deg, #de5b09 0%, #c44d07 100%) !important;
                color: #fff !important;
            }
            #wpadminbar .devis-pro-admin-bar:hover > .ab-item {
                background: linear-gradient(135deg, #c44d07 0%, #a33d05 100%) !important;
            }
            #wpadminbar .devis-pro-admin-bar .ab-icon {
                margin-right: 6px !important;
            }
            #wpadminbar .devis-pro-admin-bar .ab-icon:before {
                color: #fff !important;
                top: 3px;
            }
            #wpadminbar .devis-pro-admin-bar .ab-label {
                color: #fff !important;
            }
           .devis-pro-badge {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    background: #dc3545 !important;
    color: #fff !important;
    font-size: 9px !important;
    font-weight: bold !important;
    border-radius: 0 !important;
    margin-left: 6px !important;
    min-width: 18px !important;
    height: 16px !important;
    padding: 0 5px !important;
    line-height: 1 !important;
    box-sizing: border-box !important;
}
            .devis-pro-badge-inline {
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                background: #dc3545 !important;
                color: #fff !important;
                font-size: 9px !important;
                font-weight: bold !important;
               
                min-width: 16px !important;
                height: 16px !important;
                padding: 0 8px !important;
                margin-left: 5px !important;
                line-height: 1 !important;
                box-sizing: border-box !important;
            }
            #wpadminbar .devis-pro-admin-bar .ab-submenu .dashicons {
                font-size: 14px;
                width: 14px;
                height: 14px;
                margin-right: 6px;
                vertical-align: middle;
            }
            #wpadminbar .devis-pro-admin-bar .ab-submenu .dashicons:before {
                font-size: 14px;
            }
        </style>
        <?php
    }

    /**
     * Notice de migration
     */
    public function migration_notice() {
        global $wpdb;
        
        // Vérifier si l'ancienne table existe
        $old_table = $wpdb->prefix . 'devis';
        $new_table = $wpdb->prefix . DEVIS_PRO_TABLE;
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$old_table'") && !get_option('devis_pro_migrated')) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong>Devis Pro :</strong> 
                    <?php _e('Des données de l\'ancien plugin "Gestion de devis" ont été détectées.', 'devis-pro'); ?>
                    <a href="<?php echo admin_url('admin.php?page=devis-pro-settings&migrate=1'); ?>" class="button button-primary">
                        <?php _e('Migrer les données', 'devis-pro'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Envoyer un email test
     */
    private function send_test_email() {
        $settings = get_option('devis_pro_settings');
        $admin_email = $settings['email_admin'] ?? get_option('admin_email');
        
        // Créer un objet devis fictif pour le test
        $test_devis = (object) array(
            'id' => 999,
            'civ' => 'Mme',
            'prenom' => 'Sophie',
            'nom' => 'Dupont',
            'email' => 'test@example.com',
            'tel' => '06 12 34 56 78',
            'voyage' => '1',
            'depart' => '15/06/2025',
            'retour' => '30/06/2025',
            'duree' => '15 jours',
            'adulte' => 2,
            'enfant' => 1,
            'bebe' => 0,
            'vol' => 'Oui',
            'message' => 'Ceci est un message de test pour vérifier que l\'email fonctionne correctement.',
            'montant' => 2500.00,
            'status' => 0
        );
        
        try {
            $email = new Devis_Pro_Email();
            $result = $email->send_new_request_notification($test_devis);
            
            if ($result) {
                add_settings_error(
                    'devis_pro',
                    'test_email_sent',
                    sprintf(__('✅ Email test envoyé avec succès à %s !', 'devis-pro'), $admin_email),
                    'success'
                );
            } else {
                add_settings_error(
                    'devis_pro',
                    'test_email_failed',
                    __('❌ Erreur lors de l\'envoi de l\'email test. Vérifiez votre configuration SMTP.', 'devis-pro'),
                    'error'
                );
            }
        } catch (Exception $e) {
            add_settings_error(
                'devis_pro',
                'test_email_error',
                sprintf(__('❌ Erreur : %s', 'devis-pro'), $e->getMessage()),
                'error'
            );
        }
        
        // Rediriger vers la page des réglages
        wp_redirect(admin_url('admin.php?page=devis-pro-settings&test_email=1'));
        exit;
    }
}

// Initialiser le plugin
Devis_Pro::get_instance();

