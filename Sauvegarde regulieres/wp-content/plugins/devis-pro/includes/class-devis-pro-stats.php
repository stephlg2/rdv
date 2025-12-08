<?php
/**
 * Classe de gestion des statistiques
 */

if (!defined('ABSPATH')) {
    exit;
}

class Devis_Pro_Stats {

    private $db;
    private $settings;

    public function __construct() {
        $this->db = new Devis_Pro_DB();
        $this->settings = get_option('devis_pro_settings');
    }

    /**
     * Obtenir les données du dashboard
     */
    public function get_dashboard_data() {
        global $wpdb;
        $table = $wpdb->prefix . DEVIS_PRO_TABLE;

        $data = array();

        // Stats globales
        $data['global'] = $this->db->get_global_stats();

        // Stats du mois en cours
        $first_day = date('Y-m-01 00:00:00');
        $data['month'] = array(
            'count' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE demande >= %s",
                $first_day
            )),
            'amount' => $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(montant) FROM $table WHERE demande >= %s AND status IN (2, 4)",
                $first_day
            )) ?: 0
        );

        // Stats de la semaine
        $week_start = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $data['week'] = array(
            'count' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE demande >= %s",
                $week_start
            )),
            'amount' => $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(montant) FROM $table WHERE demande >= %s AND status IN (2, 4)",
                $week_start
            )) ?: 0
        );

        // Stats d'aujourd'hui
        $today = date('Y-m-d 00:00:00');
        $data['today'] = array(
            'count' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE demande >= %s",
                $today
            ))
        );

        // Derniers devis
        $data['recent'] = $wpdb->get_results(
            "SELECT * FROM $table ORDER BY demande DESC LIMIT 5"
        );

        // Devis en attente de traitement
        $data['pending'] = $wpdb->get_results(
            "SELECT * FROM $table WHERE status = 0 ORDER BY demande ASC LIMIT 10"
        );

        // Devis en attente de paiement
        $data['awaiting_payment'] = $wpdb->get_results(
            "SELECT * FROM $table WHERE status = 1 AND montant > 0 ORDER BY demande DESC LIMIT 10"
        );

        // Top destinations
        $data['top_destinations'] = $this->get_top_destinations(5);

        // Évolution mensuelle (12 derniers mois)
        $data['monthly_evolution'] = $this->get_monthly_evolution(12);

        // Répartition par statut
        $data['status_distribution'] = array();
        foreach ($data['global']['by_status'] as $status => $count) {
            $data['status_distribution'][] = array(
                'status' => $status,
                'label' => $this->settings['statuses'][$status]['label'] ?? 'Inconnu',
                'color' => $this->settings['statuses'][$status]['color'] ?? '#6c757d',
                'count' => $count
            );
        }

        return $data;
    }

    /**
     * Obtenir les données pour les graphiques
     */
    public function get_chart_data($period = 'month') {
        return $this->db->get_chart_data($period);
    }

    /**
     * Top destinations
     */
    private function get_top_destinations($limit = 5) {
        global $wpdb;
        $table = $wpdb->prefix . DEVIS_PRO_TABLE;

        $results = $wpdb->get_results(
            "SELECT voyage, COUNT(*) as count 
            FROM $table 
            WHERE voyage != '' 
            GROUP BY voyage 
            ORDER BY count DESC 
            LIMIT $limit"
        );

        $destinations = array();
        foreach ($results as $row) {
            $title = $this->get_voyage_title($row->voyage);
            $destinations[] = array(
                'title' => $title,
                'count' => $row->count
            );
        }

        return $destinations;
    }

    /**
     * Évolution mensuelle
     */
    private function get_monthly_evolution($months = 12) {
        global $wpdb;
        $table = $wpdb->prefix . DEVIS_PRO_TABLE;

        $start_date = date('Y-m-01', strtotime("-$months months"));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE_FORMAT(demande, '%%Y-%%m') as month,
                COUNT(*) as count,
                SUM(CASE WHEN status IN (2, 4) THEN montant ELSE 0 END) as amount,
                SUM(CASE WHEN status IN (2, 4) THEN 1 ELSE 0 END) as converted
            FROM $table 
            WHERE demande >= %s
            GROUP BY DATE_FORMAT(demande, '%%Y-%%m')
            ORDER BY month ASC",
            $start_date
        ));

        $evolution = array();
        foreach ($results as $row) {
            $date = DateTime::createFromFormat('Y-m', $row->month);
            $evolution[] = array(
                'month' => $row->month,
                'label' => $date ? $date->format('M Y') : $row->month,
                'count' => (int) $row->count,
                'amount' => (float) $row->amount,
                'converted' => (int) $row->converted,
                'rate' => $row->count > 0 ? round(($row->converted / $row->count) * 100, 1) : 0
            );
        }

        return $evolution;
    }

    /**
     * Statistiques de conversion
     */
    public function get_conversion_stats($period = 'all') {
        global $wpdb;
        $table = $wpdb->prefix . DEVIS_PRO_TABLE;

        $where = '1=1';
        if ($period === 'month') {
            $where = "demande >= '" . date('Y-m-01 00:00:00') . "'";
        } elseif ($period === 'year') {
            $where = "demande >= '" . date('Y-01-01 00:00:00') . "'";
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where");
        $sent = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where AND status >= 1");
        $accepted = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where AND status IN (2, 4)");
        $paid = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where AND status = 4");

        return array(
            'total' => (int) $total,
            'sent' => (int) $sent,
            'accepted' => (int) $accepted,
            'paid' => (int) $paid,
            'sent_rate' => $total > 0 ? round(($sent / $total) * 100, 1) : 0,
            'acceptance_rate' => $sent > 0 ? round(($accepted / $sent) * 100, 1) : 0,
            'payment_rate' => $accepted > 0 ? round(($paid / $accepted) * 100, 1) : 0,
            'global_conversion' => $total > 0 ? round(($paid / $total) * 100, 1) : 0
        );
    }

    /**
     * Temps moyen de traitement
     */
    public function get_average_processing_time() {
        global $wpdb;
        $table = $wpdb->prefix . DEVIS_PRO_TABLE;
        $history_table = $wpdb->prefix . DEVIS_PRO_HISTORY_TABLE;

        // Cette requête nécessite l'historique
        $result = $wpdb->get_var(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, d.demande, h.created_at))
            FROM $table d
            INNER JOIN $history_table h ON d.id = h.devis_id
            WHERE h.action = 'status_change' 
            AND d.status >= 1
            GROUP BY d.id
            LIMIT 100"
        );

        return $result ? round($result, 1) : null;
    }

    /**
     * Obtenir le titre du voyage
     */
    private function get_voyage_title($voyage_data) {
        $ids = explode("-;-", $voyage_data);
        $titles = array();

        foreach ($ids as $id) {
            if (!empty($id) && is_numeric($id)) {
                $title = get_the_title($id);
                if ($title) {
                    $titles[] = $title;
                }
            }
        }

        return !empty($titles) ? implode(', ', $titles) : ($voyage_data ?: 'Non spécifié');
    }
}




