<?php
/**
 * Classe de gestion de la base de données
 */

if (!defined('ABSPATH')) {
    exit;
}

class Devis_Pro_DB {

    private $table_devis;
    private $table_notes;
    private $table_history;

    public function __construct() {
        global $wpdb;
        $this->table_devis = $wpdb->prefix . DEVIS_PRO_TABLE;
        $this->table_notes = $wpdb->prefix . DEVIS_PRO_NOTES_TABLE;
        $this->table_history = $wpdb->prefix . DEVIS_PRO_HISTORY_TABLE;
    }

    /**
     * Créer les tables
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Table des devis
        $sql_devis = "CREATE TABLE {$this->table_devis} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            destination varchar(200) NOT NULL,
            voyage text NOT NULL,
            depart varchar(20) NOT NULL,
            retour varchar(20) NOT NULL,
            duree varchar(50) NOT NULL,
            budget decimal(10,2) NOT NULL DEFAULT 0,
            adulte smallint(2) NOT NULL DEFAULT 1,
            enfant smallint(2) NOT NULL DEFAULT 0,
            bebe smallint(2) NOT NULL DEFAULT 0,
            vol varchar(10) NOT NULL,
            message text NOT NULL,
            civ varchar(10) NOT NULL,
            nom varchar(200) NOT NULL,
            prenom varchar(200) NOT NULL,
            email varchar(300) NOT NULL,
            cp varchar(10) NOT NULL,
            ville varchar(200) NOT NULL,
            tel varchar(50) NOT NULL,
            status tinyint(1) NOT NULL DEFAULT 0,
            montant decimal(10,2) NOT NULL DEFAULT 0,
            demande datetime NOT NULL,
            langue varchar(10) NOT NULL DEFAULT 'fr',
            token varchar(300) NOT NULL DEFAULT '',
            mac varchar(300) NOT NULL DEFAULT '',
            reminders_count int(11) NOT NULL DEFAULT 0,
            last_reminder datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_email (email),
            KEY idx_demande (demande),
            KEY idx_token (token)
        ) $charset_collate;";

        // Table des notes
        $sql_notes = "CREATE TABLE {$this->table_notes} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            devis_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            content text NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_devis_id (devis_id)
        ) $charset_collate;";

        // Table de l'historique
        $sql_history = "CREATE TABLE {$this->table_history} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            devis_id bigint(20) NOT NULL,
            action varchar(50) NOT NULL,
            description text NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_devis_id (devis_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_devis);
        dbDelta($sql_notes);
        dbDelta($sql_history);
    }

    /**
     * Migrer depuis l'ancien plugin
     */
    public function migrate_from_old_plugin() {
        global $wpdb;
        
        $old_table = $wpdb->prefix . 'devis';
        
        // Vérifier que l'ancienne table existe
        if (!$wpdb->get_var("SHOW TABLES LIKE '$old_table'")) {
            return false;
        }
        
        // Vérifier si la migration a déjà été effectuée
        if (get_option('devis_pro_migrated')) {
            return false;
        }

        // Récupérer tous les devis de l'ancienne table
        $old_data = $wpdb->get_results("SELECT * FROM $old_table ORDER BY id ASC");
        
        if (empty($old_data)) {
            update_option('devis_pro_migrated', true);
            return 0;
        }
        
        $migrated_count = 0;
        $errors = array();
        
        foreach ($old_data as $row) {
            try {
                // Convertir l'objet en tableau
                $old_data_array = (array) $row;
                
                // Préparer les données pour la nouvelle table
                $new_data = array(
                    'destination' => isset($old_data_array['destination']) ? sanitize_text_field($old_data_array['destination']) : '',
                    'voyage' => isset($old_data_array['voyage']) ? sanitize_textarea_field($old_data_array['voyage']) : '',
                    'depart' => isset($old_data_array['depart']) ? sanitize_text_field($old_data_array['depart']) : '',
                    'retour' => isset($old_data_array['retour']) ? sanitize_text_field($old_data_array['retour']) : '',
                    'duree' => isset($old_data_array['duree']) ? sanitize_text_field($old_data_array['duree']) : '',
                    'budget' => isset($old_data_array['budget']) ? floatval($old_data_array['budget']) : 0,
                    'adulte' => isset($old_data_array['adulte']) ? intval($old_data_array['adulte']) : 1,
                    'enfant' => isset($old_data_array['enfant']) ? intval($old_data_array['enfant']) : 0,
                    'bebe' => isset($old_data_array['bebe']) ? intval($old_data_array['bebe']) : 0,
                    'vol' => isset($old_data_array['vol']) ? sanitize_text_field($old_data_array['vol']) : '',
                    'message' => isset($old_data_array['message']) ? sanitize_textarea_field($old_data_array['message']) : '',
                    'civ' => isset($old_data_array['civ']) ? sanitize_text_field($old_data_array['civ']) : '',
                    'nom' => isset($old_data_array['nom']) ? sanitize_text_field($old_data_array['nom']) : '',
                    'prenom' => isset($old_data_array['prenom']) ? sanitize_text_field($old_data_array['prenom']) : '',
                    'email' => isset($old_data_array['email']) ? sanitize_email($old_data_array['email']) : '',
                    'cp' => isset($old_data_array['cp']) ? sanitize_text_field($old_data_array['cp']) : '',
                    'ville' => isset($old_data_array['ville']) ? sanitize_text_field($old_data_array['ville']) : '',
                    'tel' => isset($old_data_array['tel']) ? sanitize_text_field($old_data_array['tel']) : '',
                    'status' => isset($old_data_array['status']) ? intval($old_data_array['status']) : 0,
                    'montant' => isset($old_data_array['montant']) ? floatval($old_data_array['montant']) : 0,
                    'langue' => isset($old_data_array['langue']) ? sanitize_text_field($old_data_array['langue']) : 'fr',
                    'token' => isset($old_data_array['token']) ? sanitize_text_field($old_data_array['token']) : '',
                    'mac' => isset($old_data_array['mac']) ? sanitize_text_field($old_data_array['mac']) : '',
                    'reminders_count' => 0,
                    'last_reminder' => null
                );
                
                // Convertir la date de demande
                if (isset($old_data_array['demande']) && !empty($old_data_array['demande'])) {
                    // Si c'est déjà au format datetime, utiliser tel quel
                    if (strlen($old_data_array['demande']) > 10) {
                        $new_data['demande'] = $old_data_array['demande'];
                    } else {
                        // Sinon, convertir depuis le format date simple
                        $new_data['demande'] = date('Y-m-d H:i:s', strtotime($old_data_array['demande']));
                    }
                } else {
                    $new_data['demande'] = current_time('mysql');
                }
                
                // Vérifier si le devis existe déjà (par email + date de demande)
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$this->table_devis} WHERE email = %s AND demande = %s LIMIT 1",
                    $new_data['email'],
                    $new_data['demande']
                ));
                
                if ($existing) {
                    // Déjà migré, on passe
                    continue;
                }
                
                // Insérer dans la nouvelle table
                $inserted = $wpdb->insert($this->table_devis, $new_data);
                
                if ($inserted) {
                    $new_id = $wpdb->insert_id;
                    
                    // Ajouter une entrée dans l'historique
                    $this->add_history(
                        $new_id,
                        'migration',
                        sprintf(__('Devis migré depuis l\'ancien plugin (ID original: %d)', 'devis-pro'), $old_data_array['id'])
                    );
                    
                    $migrated_count++;
                } else {
                    $errors[] = sprintf(__('Erreur lors de la migration du devis ID %d: %s', 'devis-pro'), 
                        $old_data_array['id'], 
                        $wpdb->last_error
                    );
                }
                
            } catch (Exception $e) {
                $errors[] = sprintf(__('Exception lors de la migration du devis ID %d: %s', 'devis-pro'), 
                    isset($old_data_array['id']) ? $old_data_array['id'] : '?', 
                    $e->getMessage()
                );
            }
        }
        
        // Marquer la migration comme effectuée
        update_option('devis_pro_migrated', true);
        update_option('devis_pro_migration_count', $migrated_count);
        update_option('devis_pro_migration_errors', $errors);
        
        return $migrated_count;
    }

    /**
     * Insérer un devis
     */
    public function insert_devis($data) {
        global $wpdb;
        
        $wpdb->insert($this->table_devis, $data);
        return $wpdb->insert_id;
    }

    /**
     * Mettre à jour un devis
     */
    public function update_devis($id, $data) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_devis,
            $data,
            array('id' => $id),
            null,
            array('%d')
        );
    }

    /**
     * Supprimer un devis
     */
    public function delete_devis($id) {
        global $wpdb;
        
        // Supprimer les notes associées
        $wpdb->delete($this->table_notes, array('devis_id' => $id), array('%d'));
        
        // Supprimer l'historique associé
        $wpdb->delete($this->table_history, array('devis_id' => $id), array('%d'));
        
        // Supprimer le devis
        return $wpdb->delete($this->table_devis, array('id' => $id), array('%d'));
    }

    /**
     * Obtenir un devis par ID
     */
    public function get_devis($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_devis} WHERE id = %d",
            $id
        ));
    }

    /**
     * Obtenir un devis par token
     */
    public function get_devis_by_token($token) {
        global $wpdb;
        
        // Chercher d'abord dans la nouvelle table
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_devis} WHERE token = %s",
            $token
        ));
        
        // Si non trouvé, chercher dans l'ancienne table (compatibilité)
        if (!$result) {
            $old_table = $wpdb->prefix . 'devis';
            if ($wpdb->get_var("SHOW TABLES LIKE '$old_table'")) {
                $result = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $old_table WHERE token = %s",
                    $token
                ));
            }
        }
        
        return $result;
    }

    /**
     * Obtenir les devis par email
     */
    public function get_devis_by_email($email) {
        global $wpdb;
        
        // Chercher dans la nouvelle table
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_devis} WHERE email = %s ORDER BY demande DESC",
            $email
        ));
        
        // Si pas de résultats, chercher dans l'ancienne table (compatibilité)
        if (empty($results)) {
            $old_table = $wpdb->prefix . 'devis';
            if ($wpdb->get_var("SHOW TABLES LIKE '$old_table'")) {
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $old_table WHERE email = %s ORDER BY demande DESC",
                    $email
                ));
            }
        }
        
        return $results;
    }

    /**
     * Obtenir tous les devis (avec filtres)
     */
    public function get_all_devis($args = array()) {
        global $wpdb;

        $defaults = array(
            'status' => null,
            'search' => '',
            'date_from' => '',
            'date_to' => '',
            'orderby' => 'demande',
            'order' => 'DESC',
            'per_page' => 20,
            'offset' => 0,
            'exclude_archived' => false
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if ($args['status'] !== null && $args['status'] !== '') {
            $where[] = 'status = %d';
            $values[] = $args['status'];
        }
        
        // Exclure les archivés si demandé
        if ($args['exclude_archived'] && ($args['status'] === null || $args['status'] === '')) {
            $where[] = 'status != 6';
        }

        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = '(nom LIKE %s OR prenom LIKE %s OR email LIKE %s OR tel LIKE %s OR destination LIKE %s)';
            $values = array_merge($values, array($search, $search, $search, $search, $search));
        }

        if (!empty($args['date_from'])) {
            $where[] = 'demande >= %s';
            $values[] = $args['date_from'] . ' 00:00:00';
        }

        if (!empty($args['date_to'])) {
            $where[] = 'demande <= %s';
            $values[] = $args['date_to'] . ' 23:59:59';
        }

        $where_clause = implode(' AND ', $where);

        // Sécuriser orderby et order
        $allowed_orderby = array('id', 'demande', 'nom', 'prenom', 'status', 'montant', 'destination');
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'demande';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT * FROM {$this->table_devis} WHERE $where_clause ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $values[] = $args['per_page'];
        $values[] = $args['offset'];

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Compter les devis
     */
    public function count_devis($args = array()) {
        global $wpdb;

        // Vérifier si la table existe
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_devis}'") != $this->table_devis) {
            // Essayer l'ancienne table
            $old_table = $wpdb->prefix . 'devis';
            if ($wpdb->get_var("SHOW TABLES LIKE '$old_table'") != $old_table) {
                return 0;
            }
            $table = $old_table;
        } else {
            $table = $this->table_devis;
        }

        $where = array('1=1');
        $values = array();

        if (isset($args['status']) && $args['status'] !== null && $args['status'] !== '') {
            $where[] = 'status = %d';
            $values[] = $args['status'];
        }
        
        // Exclure les archivés si demandé
        if (!empty($args['exclude_archived']) && (!isset($args['status']) || $args['status'] === null || $args['status'] === '')) {
            $where[] = 'status != 6';
        }

        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = '(nom LIKE %s OR prenom LIKE %s OR email LIKE %s OR tel LIKE %s)';
            $values = array_merge($values, array($search, $search, $search, $search));
        }

        $where_clause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM $table WHERE $where_clause";

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Obtenir les devis pour relance
     */
    public function get_devis_for_reminder($days, $max_reminders) {
        global $wpdb;

        $date_limit = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_devis} 
            WHERE status = 1 
            AND montant > 0 
            AND reminders_count < %d 
            AND (last_reminder IS NULL OR last_reminder < %s)
            AND demande < %s",
            $max_reminders,
            $date_limit,
            $date_limit
        ));
    }

    /**
     * Incrémenter le compteur de relances
     */
    public function increment_reminders($id) {
        global $wpdb;
        
        return $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_devis} 
            SET reminders_count = reminders_count + 1, 
                last_reminder = %s 
            WHERE id = %d",
            current_time('mysql'),
            $id
        ));
    }

    /**
     * Ajouter une note
     */
    public function add_note($devis_id, $content, $user_id) {
        global $wpdb;
        
        $wpdb->insert($this->table_notes, array(
            'devis_id' => $devis_id,
            'content' => $content,
            'user_id' => $user_id
        ));
        
        return $wpdb->insert_id;
    }

    /**
     * Obtenir les notes d'un devis
     */
    public function get_notes($devis_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT n.*, u.display_name as author 
            FROM {$this->table_notes} n 
            LEFT JOIN {$wpdb->users} u ON n.user_id = u.ID 
            WHERE n.devis_id = %d 
            ORDER BY n.created_at DESC",
            $devis_id
        ));
    }

    /**
     * Supprimer une note
     */
    public function delete_note($note_id) {
        global $wpdb;
        
        return $wpdb->delete($this->table_notes, array('id' => $note_id), array('%d'));
    }

    /**
     * Ajouter une entrée dans l'historique
     */
    public function add_history($devis_id, $action, $description) {
        global $wpdb;
        
        return $wpdb->insert($this->table_history, array(
            'devis_id' => $devis_id,
            'action' => $action,
            'description' => $description,
            'user_id' => get_current_user_id()
        ));
    }

    /**
     * Obtenir l'historique d'un devis
     */
    public function get_history($devis_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT h.*, u.display_name as author 
            FROM {$this->table_history} h 
            LEFT JOIN {$wpdb->users} u ON h.user_id = u.ID 
            WHERE h.devis_id = %d 
            ORDER BY h.created_at DESC",
            $devis_id
        ));
    }

    /**
     * Obtenir les statistiques globales
     */
    public function get_global_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total des devis
        $stats['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_devis}");
        
        // Par statut
        $status_counts = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$this->table_devis} GROUP BY status",
            OBJECT_K
        );
        $stats['by_status'] = array();
        foreach ($status_counts as $status => $row) {
            $stats['by_status'][$status] = (int) $row->count;
        }
        
        // Montant total des devis acceptés/payés
        $stats['total_amount'] = (float) $wpdb->get_var(
            "SELECT SUM(montant) FROM {$this->table_devis} WHERE status IN (2, 4)"
        );
        
        // Devis ce mois
        $stats['this_month'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_devis} WHERE demande >= %s",
            date('Y-m-01 00:00:00')
        ));
        
        // Montant moyen
        $stats['avg_amount'] = (float) $wpdb->get_var(
            "SELECT AVG(montant) FROM {$this->table_devis} WHERE montant > 0"
        );
        
        // Taux de conversion
        $accepted = isset($stats['by_status'][2]) ? $stats['by_status'][2] : 0;
        $paid = isset($stats['by_status'][4]) ? $stats['by_status'][4] : 0;
        $stats['conversion_rate'] = $stats['total'] > 0 
            ? round((($accepted + $paid) / $stats['total']) * 100, 1) 
            : 0;
        
        return $stats;
    }

    /**
     * Obtenir les données pour les graphiques
     */
    public function get_chart_data($period = 'month') {
        global $wpdb;
        
        switch ($period) {
            case 'week':
                $days = 7;
                $format = '%Y-%m-%d';
                break;
            case 'year':
                $days = 365;
                $format = '%Y-%m';
                break;
            case 'month':
            default:
                $days = 30;
                $format = '%Y-%m-%d';
                break;
        }
        
        $date_start = date('Y-m-d', strtotime("-$days days"));
        
        // Devis par jour/mois
        $devis_data = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE_FORMAT(demande, %s) as date, COUNT(*) as count, SUM(montant) as amount
            FROM {$this->table_devis}
            WHERE demande >= %s
            GROUP BY DATE_FORMAT(demande, %s)
            ORDER BY date ASC",
            $format, $date_start, $format
        ));
        
        // Répartition par statut
        $status_data = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) as count
            FROM {$this->table_devis}
            WHERE demande >= %s
            GROUP BY status",
            $date_start
        ));
        
        return array(
            'timeline' => $devis_data,
            'status' => $status_data
        );
    }
}

