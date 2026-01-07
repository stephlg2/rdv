<?php
/**
 * Classe de gestion des clients
 */

if (!defined('ABSPATH')) {
    exit;
}

class Devis_Pro_Clients {

    private $db;
    private $table_devis;

    public function __construct() {
        global $wpdb;
        $this->db = new Devis_Pro_DB();
        $this->table_devis = $wpdb->prefix . DEVIS_PRO_TABLE;
    }

    /**
     * Récupérer tous les clients uniques
     */
    public function get_all_clients($args = array()) {
        global $wpdb;

        $defaults = array(
            'orderby' => 'first_devis_date',
            'order' => 'DESC',
            'search' => '',
            'per_page' => 20,
            'page' => 1,
            'show_archived' => false
        );

        $args = wp_parse_args($args, $defaults);

        // Requête pour récupérer les clients uniques avec leurs statistiques
        $sql = "SELECT 
            email,
            COALESCE(MIN(nom), '') as nom,
            COALESCE(MIN(prenom), '') as prenom,
            COALESCE(MIN(civ), '') as civ,
            COALESCE(MIN(tel), '') as tel,
            COALESCE(MIN(cp), '') as cp,
            COALESCE(MIN(ville), '') as ville,
            MIN(demande) as first_devis_date,
            MAX(demande) as last_devis_date,
            COUNT(*) as total_devis,
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as devis_acceptes,
            SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as devis_archives,
            SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as devis_refuses,
            COALESCE(MAX(newsletter), 0) as newsletter,
            COALESCE(SUM(montant), 0) as total_montant,
            CASE WHEN COUNT(*) = SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) THEN 1 ELSE 0 END as is_archived
        FROM {$this->table_devis}
        WHERE email != '' AND email IS NOT NULL";

        // Recherche
        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $sql .= $wpdb->prepare(" AND (email LIKE %s OR nom LIKE %s OR prenom LIKE %s OR tel LIKE %s)", 
                $search, $search, $search, $search);
        }

        $sql .= " GROUP BY email";
        
        // Filtrer les clients archivés
        if ($args['show_archived'] === true || $args['show_archived'] === '1') {
            // Afficher uniquement les clients dont TOUS les devis sont archivés
            $sql .= " HAVING COUNT(*) = SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END)";
        } else {
            // Exclure les clients archivés (afficher ceux qui ont au moins un devis non archivé)
            $sql .= " HAVING COUNT(*) > SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END)";
        }

        // Tri
        $orderby_field = sanitize_text_field($args['orderby']);
        $order_direction = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        // Mapping des champs pour le tri
        $allowed_orderby = array('first_devis_date', 'last_devis_date', 'total_devis', 'total_montant', 'email', 'nom', 'prenom');
        if (in_array($orderby_field, $allowed_orderby)) {
            $sql .= " ORDER BY " . esc_sql($orderby_field) . " " . $order_direction;
        } else {
            $sql .= " ORDER BY first_devis_date DESC";
        }

        // Pagination - utiliser des valeurs entières sécurisées
        $limit = absint($args['per_page']);
        $offset = absint(($args['page'] - 1) * $args['per_page']);
        
        // Si per_page est -1 ou très grand, ne pas limiter
        if ($limit > 0 && $limit < 999999) {
            $sql .= sprintf(" LIMIT %d OFFSET %d", $limit, $offset);
        }

        $clients = $wpdb->get_results($sql, OBJECT);

        // Debug: vérifier si on a des résultats
        if ($wpdb->last_error) {
            error_log('Devis Pro Clients SQL Error: ' . $wpdb->last_error);
            error_log('Devis Pro Clients SQL: ' . $sql);
        }

        // S'assurer qu'on retourne un tableau d'objets
        if (!is_array($clients)) {
            $clients = array();
        }

        // Vérifier que chaque élément est un objet
        foreach ($clients as $key => $client) {
            if (!is_object($client)) {
                unset($clients[$key]);
            }
        }

        return $clients;
    }

    /**
     * Compter le nombre total de clients
     */
    public function count_clients($search = '', $show_archived = false) {
        global $wpdb;

        $sql = "SELECT COUNT(DISTINCT email) FROM {$this->table_devis} WHERE email != '' AND email IS NOT NULL";

        if (!empty($search)) {
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $sql .= $wpdb->prepare(" AND (email LIKE %s OR nom LIKE %s OR prenom LIKE %s OR tel LIKE %s)", 
                $search_term, $search_term, $search_term, $search_term);
        }
        
        // Filtrer selon le statut archivé
        if ($show_archived === true || $show_archived === '1') {
            // Compter uniquement les clients dont TOUS les devis sont archivés
            $sql = "SELECT COUNT(DISTINCT email) 
                    FROM (
                        SELECT email, COUNT(*) as total, SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as archived
                        FROM {$this->table_devis}
                        WHERE email != '' AND email IS NOT NULL";
            if (!empty($search)) {
                $search_term = '%' . $wpdb->esc_like($search) . '%';
                $sql .= $wpdb->prepare(" AND (email LIKE %s OR nom LIKE %s OR prenom LIKE %s OR tel LIKE %s)", 
                    $search_term, $search_term, $search_term, $search_term);
            }
            $sql .= " GROUP BY email HAVING total = archived) as archived_clients";
        } else {
            // Compter les clients qui ont au moins un devis non archivé
            $sql = "SELECT COUNT(DISTINCT email) 
                    FROM (
                        SELECT email, COUNT(*) as total, SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as archived
                        FROM {$this->table_devis}
                        WHERE email != '' AND email IS NOT NULL";
            if (!empty($search)) {
                $search_term = '%' . $wpdb->esc_like($search) . '%';
                $sql .= $wpdb->prepare(" AND (email LIKE %s OR nom LIKE %s OR prenom LIKE %s OR tel LIKE %s)", 
                    $search_term, $search_term, $search_term, $search_term);
            }
            $sql .= " GROUP BY email HAVING total > archived) as active_clients";
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Récupérer les détails d'un client par email
     */
    public function get_client_details($email) {
        global $wpdb;

        if (empty($email) || !is_email($email)) {
            return false;
        }

        // Informations de base depuis les devis
        $sql = "SELECT 
            email,
            MIN(nom) as nom,
            MIN(prenom) as prenom,
            MIN(civ) as civ,
            MIN(tel) as tel,
            MIN(cp) as cp,
            MIN(ville) as ville,
            MIN(demande) as first_devis_date,
            MAX(demande) as last_devis_date,
            COUNT(*) as total_devis,
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as devis_acceptes,
            SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as devis_archives,
            SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as devis_refuses,
            MAX(newsletter) as newsletter,
            SUM(montant) as total_montant
        FROM {$this->table_devis}
        WHERE email = %s
        GROUP BY email";

        $client = $wpdb->get_row($wpdb->prepare($sql, $email));

        if (!$client) {
            return false;
        }

        // Récupérer tous les devis du client
        $client->devis = $this->db->get_devis_by_email($email);

        // Vérifier si c'est un utilisateur WordPress
        $wp_user = get_user_by('email', $email);
        if ($wp_user) {
            $client->wp_user_id = $wp_user->ID;
            $client->wp_user_registered = $wp_user->user_registered;
            $client->wp_user_login = $wp_user->user_login;
            $client->wp_user_roles = $wp_user->roles;
        } else {
            $client->wp_user_id = null;
            $client->wp_user_registered = null;
            $client->wp_user_login = null;
            $client->wp_user_roles = array();
        }

        // Vérifier l'inscription à la newsletter (si vous utilisez un plugin de newsletter)
        $client->newsletter_subscribed = $this->check_newsletter_subscription($email);

        // Vérifier les commandes WooCommerce si WooCommerce est actif
        if (class_exists('WooCommerce')) {
            $client->woocommerce_orders = $this->get_woocommerce_orders($email);
            $client->woocommerce_total_spent = $this->get_woocommerce_total_spent($email);
        } else {
            $client->woocommerce_orders = array();
            $client->woocommerce_total_spent = 0;
        }

        return $client;
    }

    /**
     * Récupérer les devis d'un client par email
     */
    public function get_client_devis($email) {
        return $this->db->get_devis_by_email($email);
    }

    /**
     * Vérifier l'inscription à la newsletter
     */
    private function check_newsletter_subscription($email) {
        // Vérifier dans la table des devis
        global $wpdb;
        $newsletter_in_devis = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(newsletter) FROM {$this->table_devis} WHERE email = %s",
            $email
        ));

        // Vérifier dans les plugins de newsletter courants
        $subscribed = false;

        // Mailchimp
        if (function_exists('mailchimp_get_subscriber')) {
            $subscriber = mailchimp_get_subscriber($email);
            if ($subscriber && isset($subscriber['status']) && $subscriber['status'] === 'subscribed') {
                $subscribed = true;
            }
        }

        // Newsletter plugin
        if (class_exists('Newsletter')) {
            $newsletter = Newsletter::instance();
            $user = $newsletter->get_user($email);
            if ($user && $user->status === 'C') {
                $subscribed = true;
            }
        }

        // Si pas de plugin spécifique, utiliser la valeur de la table devis
        if (!$subscribed && $newsletter_in_devis) {
            $subscribed = true;
        }

        return $subscribed;
    }

    /**
     * Récupérer les commandes WooCommerce d'un client
     */
    private function get_woocommerce_orders($email) {
        if (!class_exists('WooCommerce')) {
            return array();
        }

        $orders = wc_get_orders(array(
            'billing_email' => $email,
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        $orders_data = array();
        foreach ($orders as $order) {
            $orders_data[] = array(
                'id' => $order->get_id(),
                'date' => $order->get_date_created()->date('Y-m-d H:i:s'),
                'status' => $order->get_status(),
                'total' => $order->get_total(),
                'items' => count($order->get_items())
            );
        }

        return $orders_data;
    }

    /**
     * Récupérer le total dépensé sur WooCommerce
     */
    private function get_woocommerce_total_spent($email) {
        if (!class_exists('WooCommerce')) {
            return 0;
        }

        $orders = wc_get_orders(array(
            'billing_email' => $email,
            'status' => array('wc-completed', 'wc-processing'),
            'limit' => -1
        ));

        $total = 0;
        foreach ($orders as $order) {
            $total += $order->get_total();
        }

        return $total;
    }

    /**
     * Mettre à jour les coordonnées d'un client
     */
    public function update_client_info($email, $data) {
        global $wpdb;
        
        if (empty($email) || !is_email($email)) {
            return false;
        }
        
        // Préparer les données à mettre à jour
        $update_data = array();
        
        if (isset($data['civ'])) {
            $update_data['civ'] = sanitize_text_field($data['civ']);
        }
        if (isset($data['nom'])) {
            $update_data['nom'] = sanitize_text_field($data['nom']);
        }
        if (isset($data['prenom'])) {
            $update_data['prenom'] = sanitize_text_field($data['prenom']);
        }
        if (isset($data['tel'])) {
            $update_data['tel'] = sanitize_text_field($data['tel']);
        }
        if (isset($data['cp'])) {
            $update_data['cp'] = sanitize_text_field($data['cp']);
        }
        if (isset($data['ville'])) {
            $update_data['ville'] = sanitize_text_field($data['ville']);
        }
        if (isset($data['email']) && is_email($data['email']) && $data['email'] !== $email) {
            // Si l'email change, mettre à jour tous les devis avec le nouvel email
            $update_data['email'] = sanitize_email($data['email']);
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        // Mettre à jour tous les devis du client
        $result = $wpdb->update(
            $this->table_devis,
            $update_data,
            array('email' => $email),
            null,
            array('%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Mettre à jour l'abonnement newsletter d'un client
     */
    public function update_client_newsletter($email, $newsletter_status) {
        global $wpdb;
        
        if (empty($email) || !is_email($email)) {
            return false;
        }
        
        $newsletter_value = $newsletter_status ? 1 : 0;
        
        // Mettre à jour tous les devis du client
        $result = $wpdb->update(
            $this->table_devis,
            array('newsletter' => $newsletter_value),
            array('email' => $email),
            array('%d'),
            array('%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Archiver tous les devis d'un client
     */
    public function archive_client_devis($email) {
        global $wpdb;
        
        if (empty($email) || !is_email($email)) {
            return false;
        }
        
        // Archiver tous les devis du client (status = 2)
        $result = $wpdb->update(
            $this->table_devis,
            array('status' => 2),
            array('email' => $email),
            array('%d'),
            array('%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Désarchiver tous les devis d'un client
     */
    public function unarchive_client_devis($email) {
        global $wpdb;
        
        if (empty($email) || !is_email($email)) {
            return false;
        }
        
        // Désarchiver tous les devis du client (status = 0 = En attente)
        $result = $wpdb->update(
            $this->table_devis,
            array('status' => 0),
            array('email' => $email, 'status' => 2), // Seulement ceux qui sont archivés
            array('%d'),
            array('%s', '%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Supprimer définitivement un client et tous ses devis
     */
    public function delete_client($email) {
        global $wpdb;
        
        if (empty($email) || !is_email($email)) {
            return false;
        }
        
        // Récupérer tous les devis du client pour supprimer les notes et l'historique
        $devis_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$this->table_devis} WHERE email = %s",
            $email
        ));
        
        if (!empty($devis_ids)) {
            // Supprimer les notes associées
            $placeholders = implode(',', array_fill(0, count($devis_ids), '%d'));
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}devis_pro_notes WHERE devis_id IN ($placeholders)",
                ...$devis_ids
            ));
            
            // Supprimer l'historique associé
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}devis_pro_history WHERE devis_id IN ($placeholders)",
                ...$devis_ids
            ));
        }
        
        // Supprimer tous les devis du client
        $result = $wpdb->delete(
            $this->table_devis,
            array('email' => $email),
            array('%s')
        );
        
        return $result !== false;
    }

    /**
     * Exporter tous les clients en CSV
     */
    public function export_clients_csv() {
        // Désactiver la compression et les buffers
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Désactiver l'affichage des erreurs
        @ini_set('display_errors', 0);
        
        // Récupérer tous les clients (sans limite)
        $clients = $this->get_all_clients(array(
            'per_page' => 999999,
            'page' => 1,
            'orderby' => 'first_devis_date',
            'order' => 'DESC'
        ));

        // Envoyer les headers AVANT tout autre contenu
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=clients-' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        
        // Ouvrir le flux de sortie
        $output = fopen('php://output', 'w');
        
        // BOM pour Excel (UTF-8)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // En-têtes
        fputcsv($output, array(
            'Email',
            'Civilité',
            'Prénom',
            'Nom',
            'Téléphone',
            'Code postal',
            'Ville',
            'Date premier devis',
            'Date dernier devis',
            'Total devis',
            'Devis acceptés',
            'Devis archivés',
            'Devis refusés',
            'Total montant',
            'Newsletter'
        ), ';');

        // Données
        foreach ($clients as $client) {
            fputcsv($output, array(
                $client->email,
                $client->civ ?? '',
                $client->prenom ?? '',
                $client->nom ?? '',
                $client->tel ?? '',
                $client->cp ?? '',
                $client->ville ?? '',
                $client->first_devis_date ?? '',
                $client->last_devis_date ?? '',
                $client->total_devis ?? 0,
                $client->devis_acceptes ?? 0,
                $client->devis_archives ?? 0,
                $client->devis_refuses ?? 0,
                number_format($client->total_montant ?? 0, 2, ',', ' '),
                ($client->newsletter ?? 0) ? 'Oui' : 'Non'
            ), ';');
        }

        fclose($output);
        
        // Sortir immédiatement sans exécuter le reste de WordPress
        exit;
    }
}

