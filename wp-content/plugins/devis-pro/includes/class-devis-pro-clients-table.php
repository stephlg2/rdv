<?php
/**
 * Classe pour la liste des clients (WP_List_Table)
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Devis_Pro_Clients_Table extends WP_List_Table {

    private $clients_handler;

    public function __construct() {
        parent::__construct(array(
            'singular' => 'client',
            'plural' => 'clients',
            'ajax' => true
        ));
        
        $this->clients_handler = new Devis_Pro_Clients();
    }

    /**
     * Colonnes
     */
    public function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />',
            'client' => __('Client', 'devis-pro'),
            'email' => __('Email', 'devis-pro'),
            'contact' => __('Contact', 'devis-pro'),
            'first_devis_date' => __('Premier devis', 'devis-pro'),
            'total_devis' => __('Total devis', 'devis-pro'),
            'devis_acceptes' => __('Acceptés', 'devis-pro'),
            'total_montant' => __('Montant total', 'devis-pro'),
            'newsletter' => __('Newsletter', 'devis-pro'),
            'wp_account' => __('Compte WP', 'devis-pro'),
            'actions' => __('Actions', 'devis-pro')
        );
    }

    /**
     * Colonnes triables
     */
    public function get_sortable_columns() {
        return array(
            'client' => array('nom', false),
            'email' => array('email', false),
            'first_devis_date' => array('first_devis_date', true),
            'total_devis' => array('total_devis', false),
            'total_montant' => array('total_montant', false)
        );
    }

    /**
     * Actions groupées
     */
    public function get_bulk_actions() {
        $actions = array();
        
        // Si on est dans la vue archivés, proposer la désarchivage
        if (isset($_GET['show_archived']) && $_GET['show_archived'] === '1') {
            $actions['unarchive'] = __('Désarchiver', 'devis-pro');
        } else {
            $actions['archive'] = __('Archiver', 'devis-pro');
        }
        
        $actions['delete'] = __('Supprimer définitivement', 'devis-pro');
        
        return $actions;
    }

    /**
     * Colonne checkbox
     */
    public function column_cb($item) {
        if (!is_object($item) || !isset($item->email)) {
            return '';
        }
        return sprintf(
            '<input type="checkbox" name="clients[]" value="%s" />',
            esc_attr($item->email)
        );
    }

    /**
     * Colonne Client
     */
    public function column_client($item) {
        if (!is_object($item) || !isset($item->email)) {
            return '—';
        }
        
        $civ = isset($item->civ) ? $item->civ : '';
        $prenom = isset($item->prenom) ? $item->prenom : '';
        $nom = isset($item->nom) ? $item->nom : '';
        
        $name = trim($civ . ' ' . $prenom . ' ' . $nom);
        if (empty(trim($name))) {
            $name = __('Sans nom', 'devis-pro');
        }
        
        $url = admin_url('admin.php?page=devis-pro-client-detail&email=' . urlencode($item->email));
        
        return sprintf(
            '<strong><a href="%s">%s</a></strong>',
            esc_url($url),
            esc_html($name)
        );
    }

    /**
     * Colonne Email
     */
    public function column_email($item) {
        if (!is_object($item) || !isset($item->email)) {
            return '—';
        }
        return sprintf(
            '<a href="mailto:%s">%s</a>',
            esc_attr($item->email),
            esc_html($item->email)
        );
    }

    /**
     * Colonne Contact
     */
    public function column_contact($item) {
        $contact = array();
        
        if (!empty($item->tel)) {
            $contact[] = sprintf('<a href="tel:%s">%s</a>', esc_attr($item->tel), esc_html($item->tel));
        }
        
        if (!empty($item->cp) || !empty($item->ville)) {
            $address = trim($item->cp . ' ' . $item->ville);
            if (!empty($address)) {
                $contact[] = esc_html($address);
            }
        }
        
        return !empty($contact) ? implode('<br>', $contact) : '—';
    }

    /**
     * Colonne Premier devis
     */
    public function column_first_devis_date($item) {
        if (!is_object($item) || empty($item->first_devis_date)) {
            return '—';
        }
        
        try {
            $date = new DateTime($item->first_devis_date);
            return sprintf(
                '<span title="%s">%s</span>',
                esc_attr($date->format('d/m/Y H:i')),
                esc_html($date->format('d/m/Y'))
            );
        } catch (Exception $e) {
            return esc_html($item->first_devis_date);
        }
    }

    /**
     * Colonne Total devis
     */
    public function column_total_devis($item) {
        if (!is_object($item) || !isset($item->total_devis)) {
            return '—';
        }
        $badge_class = 'info';
        if ($item->total_devis >= 5) {
            $badge_class = 'success';
        } elseif ($item->total_devis >= 3) {
            $badge_class = 'warning';
        }
        
        return sprintf(
            '<span class="devis-badge badge-%s">%d</span>',
            esc_attr($badge_class),
            intval($item->total_devis)
        );
    }

    /**
     * Colonne Devis acceptés
     */
    public function column_devis_acceptes($item) {
        if (!is_object($item) || !isset($item->devis_acceptes)) {
            return '—';
        }
        if ($item->devis_acceptes > 0) {
            return sprintf(
                '<span class="devis-badge badge-success">%d</span>',
                intval($item->devis_acceptes)
            );
        }
        return '—';
    }

    /**
     * Colonne Montant total
     */
    public function column_total_montant($item) {
        if (!is_object($item) || !isset($item->total_montant)) {
            return '—';
        }
        if ($item->total_montant > 0) {
            return number_format($item->total_montant, 2, ',', ' ') . ' €';
        }
        return '—';
    }

    /**
     * Colonne Newsletter
     */
    public function column_newsletter($item) {
        if (!is_object($item) || !isset($item->newsletter)) {
            return '—';
        }
        if ($item->newsletter) {
            return '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>';
        }
        return '<span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span>';
    }

    /**
     * Colonne Compte WordPress
     */
    public function column_wp_account($item) {
        if (!is_object($item) || !isset($item->email)) {
            return '—';
        }
        $wp_user = get_user_by('email', $item->email);
        if ($wp_user) {
            $user_url = admin_url('user-edit.php?user_id=' . $wp_user->ID);
            return sprintf(
                '<a href="%s" title="%s">%s</a>',
                esc_url($user_url),
                esc_attr($wp_user->user_login),
                __('Oui', 'devis-pro')
            );
        }
        return '—';
    }

    /**
     * Colonne Actions
     */
    public function column_actions($item) {
        $actions = array();
        
        $detail_url = admin_url('admin.php?page=devis-pro-client-detail&email=' . urlencode($item->email));
        $actions['view'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url($detail_url),
            __('Voir détails', 'devis-pro')
        );
        
        $devis_url = admin_url('admin.php?page=devis-pro-list&s=' . urlencode($item->email));
        $actions['devis'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url($devis_url),
            __('Voir devis', 'devis-pro')
        );
        
        return $this->row_actions($actions);
    }

    /**
     * Préparer les items
     */
    public function prepare_items() {
        // Traiter les actions groupées
        $this->process_bulk_action();
        
        $per_page = $this->get_items_per_page('clients_per_page', 20);
        $current_page = $this->get_pagenum();
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] === '1';

        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'first_devis_date';
        $order = isset($_GET['order']) ? strtoupper($_GET['order']) : 'DESC';
        
        // Valider orderby
        $allowed_orderby = array('first_devis_date', 'last_devis_date', 'total_devis', 'total_montant', 'email', 'nom', 'prenom');
        if (!in_array($orderby, $allowed_orderby)) {
            $orderby = 'first_devis_date';
        }
        
        // Valider order
        if ($order !== 'ASC' && $order !== 'DESC') {
            $order = 'DESC';
        }

        $args = array(
            'per_page' => $per_page,
            'page' => $current_page,
            'search' => $search,
            'orderby' => $orderby,
            'order' => $order,
            'show_archived' => $show_archived
        );

        $this->items = $this->clients_handler->get_all_clients($args);
        $total_items = $this->clients_handler->count_clients($search, $show_archived);

        // S'assurer que items est un tableau
        if (!is_array($this->items)) {
            $this->items = array();
        }

        // Définir les colonnes APRÈS avoir récupéré les items
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        // Debug: vérifier les résultats
        if (empty($this->items) && $total_items > 0) {
            error_log('Devis Pro Clients: total_items=' . $total_items . ' mais items vide');
            error_log('Devis Pro Clients: args=' . print_r($args, true));
            error_log('Devis Pro Clients: SQL last query=' . $GLOBALS['wpdb']->last_query);
            error_log('Devis Pro Clients: SQL last error=' . $GLOBALS['wpdb']->last_error);
        }

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }
    
    /**
     * Vues (filtres rapides)
     */
    public function get_views() {
        $views = array();
        $current = isset($_GET['show_archived']) && $_GET['show_archived'] === '1' ? 'archived' : 'all';
        
        // Tous les clients (non archivés)
        $total = $this->clients_handler->count_clients('', false);
        $class = ($current === 'all') ? ' class="current"' : '';
        $views['all'] = sprintf(
            '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
            admin_url('admin.php?page=devis-pro-clients'),
            $class,
            __('Tous', 'devis-pro'),
            $total
        );
        
        // Clients archivés
        $archived_count = $this->clients_handler->count_clients('', true);
        if ($archived_count > 0 || $current === 'archived') {
            $class = ($current === 'archived') ? ' class="current"' : '';
            $views['archived'] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
                admin_url('admin.php?page=devis-pro-clients&show_archived=1'),
                $class,
                __('Archivés', 'devis-pro'),
                $archived_count
            );
        }
        
        return $views;
    }
    
    /**
     * Traiter les actions groupées
     */
    public function process_bulk_action() {
        // Vérifier que c'est une requête POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        
        $action = $this->current_action();
        
        if (!$action || $action === '-1') {
            return;
        }
        
        // Vérifier le nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'bulk-clients')) {
            wp_die(__('Action non autorisée', 'devis-pro'));
        }
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires', 'devis-pro'));
        }
        
        // Récupérer les emails sélectionnés
        if (!isset($_POST['clients']) || !is_array($_POST['clients'])) {
            return;
        }
        
        $emails = array_map('sanitize_email', $_POST['clients']);
        $emails = array_filter($emails, 'is_email');
        
        if (empty($emails)) {
            return;
        }
        
        $clients_handler = new Devis_Pro_Clients();
        $processed = 0;
        
        switch ($action) {
            case 'archive':
                foreach ($emails as $email) {
                    if ($clients_handler->archive_client_devis($email)) {
                        $processed++;
                    }
                }
                if ($processed > 0) {
                    $redirect_url = add_query_arg(array(
                        'page' => 'devis-pro-clients',
                        'archived' => $processed
                    ), admin_url('admin.php'));
                    wp_redirect($redirect_url);
                    exit;
                }
                break;
                
            case 'unarchive':
                foreach ($emails as $email) {
                    if ($clients_handler->unarchive_client_devis($email)) {
                        $processed++;
                    }
                }
                if ($processed > 0) {
                    $redirect_url = add_query_arg(array(
                        'page' => 'devis-pro-clients',
                        'show_archived' => '1',
                        'unarchived' => $processed
                    ), admin_url('admin.php'));
                    wp_redirect($redirect_url);
                    exit;
                }
                break;
                
            case 'delete':
                foreach ($emails as $email) {
                    if ($clients_handler->delete_client($email)) {
                        $processed++;
                    }
                }
                if ($processed > 0) {
                    $redirect_url = add_query_arg(array(
                        'page' => 'devis-pro-clients',
                        'deleted' => $processed
                    ), admin_url('admin.php'));
                    wp_redirect($redirect_url);
                    exit;
                }
                break;
        }
    }
    
    /**
     * Vérifier si la table a des items
     */
    public function has_items() {
        return !empty($this->items) && is_array($this->items) && count($this->items) > 0;
    }

    /**
     * Message quand aucun client
     */
    public function no_items() {
        _e('Aucun client trouvé.', 'devis-pro');
    }

    /**
     * Colonne par défaut
     */
    protected function column_default($item, $column_name) {
        // Si la colonne n'a pas de méthode spécifique, retourner une valeur par défaut
        if (!is_object($item)) {
            return '—';
        }
        
        switch ($column_name) {
            case 'first_devis_date':
                return $this->column_first_devis_date($item);
            case 'total_devis':
                return $this->column_total_devis($item);
            case 'devis_acceptes':
                return $this->column_devis_acceptes($item);
            case 'total_montant':
                return $this->column_total_montant($item);
            case 'newsletter':
                return $this->column_newsletter($item);
            case 'wp_account':
                return $this->column_wp_account($item);
            case 'actions':
                return $this->column_actions($item);
            default:
                if (isset($item->$column_name)) {
                    return esc_html($item->$column_name);
                }
                return '—';
        }
    }
}

