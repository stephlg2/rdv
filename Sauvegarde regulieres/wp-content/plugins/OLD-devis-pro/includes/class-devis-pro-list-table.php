<?php
/**
 * Classe pour la liste des devis (WP_List_Table)
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Devis_Pro_List_Table extends WP_List_Table {

    private $db;
    private $settings;

    public function __construct() {
        parent::__construct(array(
            'singular' => 'devis',
            'plural' => 'devis',
            'ajax' => true
        ));
        
        $this->db = new Devis_Pro_DB();
        $this->settings = get_option('devis_pro_settings');
    }

    /**
     * Colonnes
     */
    public function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />',
            'id' => __('ID', 'devis-pro'),
            'demande' => __('Date', 'devis-pro'),
            'client' => __('Client', 'devis-pro'),
            'destination' => __('Destination', 'devis-pro'),
            'dates' => __('Dates voyage', 'devis-pro'),
            'participants' => __('Participants', 'devis-pro'),
            'montant' => __('Montant', 'devis-pro'),
            'status' => __('Statut', 'devis-pro'),
            'actions' => __('Actions', 'devis-pro')
        );
    }

    /**
     * Colonnes triables
     */
    public function get_sortable_columns() {
        return array(
            'id' => array('id', false),
            'demande' => array('demande', true),
            'client' => array('nom', false),
            'montant' => array('montant', false),
            'status' => array('status', false)
        );
    }

    /**
     * Actions groupées
     */
    public function get_bulk_actions() {
        return array(
            'archive' => __('Archiver', 'devis-pro'),
            'mark_sent' => __('Marquer comme envoyé', 'devis-pro'),
            'send_reminder' => __('Envoyer une relance', 'devis-pro'),
            'delete' => __('Supprimer définitivement', 'devis-pro')
        );
    }

    /**
     * Colonne checkbox
     */
    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="devis[]" value="%d" />',
            $item->id
        );
    }

    /**
     * Colonne ID
     */
    public function column_id($item) {
        return sprintf(
            '<strong><a href="%s">#%d</a></strong>',
            admin_url('admin.php?page=devis-pro-detail&id=' . $item->id),
            $item->id
        );
    }

    /**
     * Colonne Date
     */
    public function column_demande($item) {
        $date = new DateTime($item->demande);
        return sprintf(
            '<span title="%s">%s</span>',
            $date->format('d/m/Y H:i'),
            $date->format('d/m/Y')
        );
    }

    /**
     * Colonne Client
     */
    public function column_client($item) {
        $output = sprintf(
            '<strong>%s %s %s</strong><br>',
            esc_html($item->civ),
            esc_html($item->prenom),
            esc_html($item->nom)
        );
        $output .= sprintf(
            '<a href="mailto:%s">%s</a><br>',
            esc_attr($item->email),
            esc_html($item->email)
        );
        $output .= sprintf(
            '<a href="tel:%s">%s</a>',
            esc_attr($item->tel),
            esc_html($item->tel)
        );
        return $output;
    }

    /**
     * Colonne Destination
     */
    public function column_destination($item) {
        $voyage_ids = explode("-;-", $item->voyage);
        $output = '';
        
        foreach ($voyage_ids as $id) {
            if (!empty($id) && is_numeric($id)) {
                $title = get_the_title($id);
                if ($title) {
                    $output .= esc_html($title) . '<br>';
                }
            }
        }
        
        if (empty($output) && !empty($item->destination)) {
            $output = esc_html($item->destination);
        }
        
        return $output ?: '-';
    }

    /**
     * Colonne Dates voyage
     */
    public function column_dates($item) {
        $depart = !empty($item->depart) ? esc_html($item->depart) : '-';
        $retour = !empty($item->retour) ? esc_html($item->retour) : '-';
        $duree = !empty($item->duree) ? esc_html($item->duree) : '';
        
        return sprintf(
            '%s → %s%s',
            $depart,
            $retour,
            $duree ? '<br><small>' . $duree . '</small>' : ''
        );
    }

    /**
     * Colonne Participants
     */
    public function column_participants($item) {
        $parts = array();
        
        if ($item->adulte > 0) {
            $parts[] = $item->adulte . ' ' . _n('adulte', 'adultes', $item->adulte, 'devis-pro');
        }
        if ($item->enfant > 0) {
            $parts[] = $item->enfant . ' ' . _n('enfant', 'enfants', $item->enfant, 'devis-pro');
        }
        if ($item->bebe > 0) {
            $parts[] = $item->bebe . ' ' . _n('bébé', 'bébés', $item->bebe, 'devis-pro');
        }
        
        return implode('<br>', $parts) ?: '-';
    }

    /**
     * Colonne Montant
     */
    public function column_montant($item) {
        if ($item->montant > 0) {
            return sprintf(
                '<strong style="color: #28a745;">%s €</strong>',
                number_format($item->montant, 2, ',', ' ')
            );
        }
        return '<span style="color: #999;">-</span>';
    }

    /**
     * Colonne Statut
     */
    public function column_status($item) {
        $status = $this->settings['statuses'][$item->status] ?? array(
            'label' => __('Inconnu', 'devis-pro'),
            'color' => '#6c757d'
        );
        
        return sprintf(
            '<span class="devis-status" style="background-color: %s;">%s</span>',
            esc_attr($status['color']),
            esc_html($status['label'])
        );
    }

    /**
     * Colonne Actions
     */
    public function column_actions($item) {
        $actions = array();
        
        // Voir
        $actions[] = sprintf(
            '<a href="%s" class="button button-small" title="%s"><span class="dashicons dashicons-visibility"></span></a>',
            admin_url('admin.php?page=devis-pro-detail&id=' . $item->id),
            __('Voir', 'devis-pro')
        );
        
        // Dupliquer
        $actions[] = sprintf(
            '<a href="#" class="button button-small devis-duplicate" data-id="%d" title="%s"><span class="dashicons dashicons-admin-page"></span></a>',
            $item->id,
            __('Dupliquer', 'devis-pro')
        );
        
        // Relance (si devis envoyé)
        if ($item->status == 1 && $item->montant > 0) {
            $actions[] = sprintf(
                '<a href="#" class="button button-small devis-reminder" data-id="%d" title="%s"><span class="dashicons dashicons-email-alt"></span></a>',
                $item->id,
                __('Envoyer une relance', 'devis-pro')
            );
        }
        
        // Archiver (si pas déjà archivé)
        if ($item->status != 6) {
            $actions[] = sprintf(
                '<a href="#" class="button button-small devis-archive" data-id="%d" title="%s"><span class="dashicons dashicons-archive"></span></a>',
                $item->id,
                __('Archiver', 'devis-pro')
            );
        } else {
            // Restaurer (si archivé)
            $actions[] = sprintf(
                '<a href="#" class="button button-small devis-restore" data-id="%d" title="%s"><span class="dashicons dashicons-undo"></span></a>',
                $item->id,
                __('Restaurer', 'devis-pro')
            );
        }
        
        // Supprimer définitivement (seulement pour archivés)
        if ($item->status == 6) {
            $actions[] = sprintf(
                '<a href="%s" class="button button-small button-link-delete" onclick="return confirm(\'%s\');" title="%s"><span class="dashicons dashicons-trash"></span></a>',
                wp_nonce_url(
                    admin_url('admin.php?page=devis-pro-list&action=delete&id=' . $item->id),
                    'delete_devis_' . $item->id
                ),
                esc_js(__('Supprimer définitivement ce devis ?', 'devis-pro')),
                __('Supprimer', 'devis-pro')
            );
        }
        
        return '<div class="devis-actions">' . implode('', $actions) . '</div>';
    }

    /**
     * Préparer les éléments
     */
    public function prepare_items() {
        $per_page = 20;
        $current_page = $this->get_pagenum();
        
        // Colonnes
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        // Filtres
        $status_filter = isset($_REQUEST['status']) && $_REQUEST['status'] !== '' ? intval($_REQUEST['status']) : null;
        $show_archived = isset($_REQUEST['show_archived']);
        
        $args = array(
            'status' => $status_filter,
            'search' => isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '',
            'date_from' => isset($_REQUEST['date_from']) ? sanitize_text_field($_REQUEST['date_from']) : '',
            'date_to' => isset($_REQUEST['date_to']) ? sanitize_text_field($_REQUEST['date_to']) : '',
            'orderby' => isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'demande',
            'order' => isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'DESC',
            'per_page' => $per_page,
            'offset' => ($current_page - 1) * $per_page,
            'exclude_archived' => ($status_filter === null && !$show_archived) // Exclure les archivés sauf si filtre spécifique
        );
        
        // Récupérer les données
        $this->items = $this->db->get_all_devis($args);
        $total_items = $this->db->count_devis($args);
        
        // Pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }

    /**
     * Message quand pas de données
     */
    public function no_items() {
        _e('Aucun devis trouvé.', 'devis-pro');
    }

    /**
     * Filtres supplémentaires
     */
    public function extra_tablenav($which) {
        if ($which !== 'top') {
            return;
        }
        ?>
        <div class="alignleft actions">
            <select name="status">
                <option value=""><?php _e('Tous les statuts', 'devis-pro'); ?></option>
                <?php foreach ($this->settings['statuses'] as $key => $status) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected(isset($_REQUEST['status']) ? $_REQUEST['status'] : '', $key); ?>>
                        <?php echo esc_html($status['label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="date" name="date_from" value="<?php echo esc_attr($_REQUEST['date_from'] ?? ''); ?>" placeholder="<?php _e('Date début', 'devis-pro'); ?>">
            <input type="date" name="date_to" value="<?php echo esc_attr($_REQUEST['date_to'] ?? ''); ?>" placeholder="<?php _e('Date fin', 'devis-pro'); ?>">
            
            <input type="submit" class="button" value="<?php _e('Filtrer', 'devis-pro'); ?>">
            
            <?php if (!empty($_REQUEST['status']) || !empty($_REQUEST['date_from']) || !empty($_REQUEST['date_to']) || !empty($_REQUEST['s'])) : ?>
                <a href="<?php echo admin_url('admin.php?page=devis-pro-list'); ?>" class="button"><?php _e('Réinitialiser', 'devis-pro'); ?></a>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Vues (filtres rapides par statut)
     */
    public function get_views() {
        $views = array();
        $current = isset($_REQUEST['status']) ? $_REQUEST['status'] : '';
        $show_archived = isset($_REQUEST['show_archived']);
        
        // Total (sans archivés)
        $total = $this->db->count_devis(array('exclude_archived' => true));
        $class = (empty($current) && !$show_archived) ? ' class="current"' : '';
        $views['all'] = sprintf(
            '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
            admin_url('admin.php?page=devis-pro-list'),
            $class,
            __('Tous', 'devis-pro'),
            $total
        );
        
        // Par statut (sauf archivé qui a son propre onglet)
        foreach ($this->settings['statuses'] as $key => $status) {
            if ($key == 6) continue; // Skip "Archivé" - traité séparément
            
            $count = $this->db->count_devis(array('status' => $key));
            if ($count > 0) {
                $class = ($current === (string)$key) ? ' class="current"' : '';
                $views['status_' . $key] = sprintf(
                    '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
                    admin_url('admin.php?page=devis-pro-list&status=' . $key),
                    $class,
                    esc_html($status['label']),
                    $count
                );
            }
        }
        
        // Archivés
        $archived_count = $this->db->count_devis(array('status' => 6));
        if ($archived_count > 0 || $show_archived) {
            $class = $show_archived ? ' class="current"' : '';
            $views['archived'] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
                admin_url('admin.php?page=devis-pro-list&status=6&show_archived=1'),
                $class,
                __('Archivés', 'devis-pro'),
                $archived_count
            );
        }
        
        return $views;
    }
}



