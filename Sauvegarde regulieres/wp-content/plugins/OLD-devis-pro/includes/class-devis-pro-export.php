<?php
/**
 * Classe d'export des données
 */

if (!defined('ABSPATH')) {
    exit;
}

class Devis_Pro_Export {

    private $db;
    private $settings;

    public function __construct() {
        $this->db = new Devis_Pro_DB();
        $this->settings = get_option('devis_pro_settings');
    }

    /**
     * Exporter en CSV
     */
    public function export_csv($filters = array()) {
        $args = array(
            'status' => isset($filters['status']) && $filters['status'] !== '' ? intval($filters['status']) : null,
            'date_from' => isset($filters['date_from']) ? sanitize_text_field($filters['date_from']) : '',
            'date_to' => isset($filters['date_to']) ? sanitize_text_field($filters['date_to']) : '',
            'per_page' => 10000,
            'offset' => 0
        );

        $devis = $this->db->get_all_devis($args);

        // Headers CSV
        $filename = 'devis-export-' . date('Y-m-d-His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        
        // BOM UTF-8 pour Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // En-têtes
        $headers = array(
            'ID',
            'Date demande',
            'Civilité',
            'Nom',
            'Prénom',
            'Email',
            'Téléphone',
            'Code postal',
            'Ville',
            'Destination',
            'Voyage',
            'Date départ',
            'Date retour',
            'Durée',
            'Budget client',
            'Adultes',
            'Enfants',
            'Bébés',
            'Vol inclus',
            'Message',
            'Statut',
            'Montant devis',
            'Langue',
            'Relances envoyées',
            'Dernière relance'
        );
        fputcsv($output, $headers, ';');

        // Données
        foreach ($devis as $d) {
            $status_label = isset($this->settings['statuses'][$d->status]) 
                ? $this->settings['statuses'][$d->status]['label'] 
                : 'Inconnu';

            $voyage_title = $this->get_voyage_title($d->voyage);

            $row = array(
                $d->id,
                $d->demande,
                $d->civ,
                $d->nom,
                $d->prenom,
                $d->email,
                $d->tel,
                $d->cp,
                $d->ville,
                $d->destination,
                $voyage_title,
                $d->depart,
                $d->retour,
                $d->duree,
                $d->budget,
                $d->adulte,
                $d->enfant,
                $d->bebe,
                $d->vol,
                $d->message,
                $status_label,
                $d->montant,
                $d->langue,
                $d->reminders_count,
                $d->last_reminder
            );
            fputcsv($output, $row, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Exporter en PDF (via HTML)
     */
    public function export_pdf_single($devis_id) {
        $devis = $this->db->get_devis($devis_id);
        
        if (!$devis) {
            return false;
        }

        $voyage_title = $this->get_voyage_title($devis->voyage);
        $status_label = isset($this->settings['statuses'][$devis->status]) 
            ? $this->settings['statuses'][$devis->status]['label'] 
            : 'Inconnu';

        // Générer le HTML du devis
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Devis #<?php echo $devis->id; ?></title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 14px; color: #333; margin: 40px; }
                .header { text-align: center; margin-bottom: 40px; border-bottom: 3px solid #de5b09; padding-bottom: 20px; }
                .header h1 { color: #de5b09; margin: 0; }
                .header p { color: #666; margin: 5px 0 0; }
                .section { margin-bottom: 30px; }
                .section h2 { color: #de5b09; font-size: 18px; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
                table { width: 100%; border-collapse: collapse; }
                table th, table td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
                table th { background: #f9f9f9; width: 180px; }
                .amount { font-size: 24px; color: #de5b09; text-align: center; padding: 20px; background: #fff5f0; border-radius: 10px; margin: 20px 0; }
                .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 12px; }
                .status { display: inline-block; padding: 5px 15px; border-radius: 20px; color: #fff; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Rendez-vous avec l'Asie</h1>
                <p>Devis #<?php echo $devis->id; ?> - <?php echo date('d/m/Y', strtotime($devis->demande)); ?></p>
            </div>

            <div class="section">
                <h2>Informations client</h2>
                <table>
                    <tr>
                        <th>Nom</th>
                        <td><?php echo esc_html($devis->civ . ' ' . $devis->prenom . ' ' . $devis->nom); ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><?php echo esc_html($devis->email); ?></td>
                    </tr>
                    <tr>
                        <th>Téléphone</th>
                        <td><?php echo esc_html($devis->tel); ?></td>
                    </tr>
                    <tr>
                        <th>Adresse</th>
                        <td><?php echo esc_html($devis->cp . ' ' . $devis->ville); ?></td>
                    </tr>
                </table>
            </div>

            <div class="section">
                <h2>Détails du voyage</h2>
                <table>
                    <tr>
                        <th>Voyage</th>
                        <td><?php echo esc_html($voyage_title); ?></td>
                    </tr>
                    <tr>
                        <th>Date de départ</th>
                        <td><?php echo esc_html($devis->depart); ?></td>
                    </tr>
                    <tr>
                        <th>Date de retour</th>
                        <td><?php echo esc_html($devis->retour); ?></td>
                    </tr>
                    <tr>
                        <th>Durée</th>
                        <td><?php echo esc_html($devis->duree); ?></td>
                    </tr>
                    <tr>
                        <th>Participants</th>
                        <td>
                            <?php echo $devis->adulte; ?> adulte(s), 
                            <?php echo $devis->enfant; ?> enfant(s), 
                            <?php echo $devis->bebe; ?> bébé(s)
                        </td>
                    </tr>
                    <tr>
                        <th>Vol inclus</th>
                        <td><?php echo esc_html($devis->vol); ?></td>
                    </tr>
                    <tr>
                        <th>Budget client</th>
                        <td><?php echo $devis->budget ? number_format($devis->budget, 2, ',', ' ') . ' €' : '-'; ?></td>
                    </tr>
                </table>
            </div>

            <?php if ($devis->message) : ?>
            <div class="section">
                <h2>Message du client</h2>
                <p><?php echo nl2br(esc_html($devis->message)); ?></p>
            </div>
            <?php endif; ?>

            <?php if ($devis->montant > 0) : ?>
            <div class="amount">
                <strong>Montant du devis :</strong><br>
                <?php echo number_format($devis->montant, 2, ',', ' '); ?> €
            </div>
            <?php endif; ?>

            <div class="section">
                <h2>Statut</h2>
                <p><span class="status" style="background-color: <?php echo esc_attr($this->settings['statuses'][$devis->status]['color'] ?? '#6c757d'); ?>">
                    <?php echo esc_html($status_label); ?>
                </span></p>
            </div>

            <div class="footer">
                <p>
                    Rendez-vous avec l'Asie<br>
                    contact@rdvasie.com | www.rdvasie.com
                </p>
            </div>
        </body>
        </html>
        <?php
        $html = ob_get_clean();

        // Pour un vrai PDF, il faudrait utiliser TCPDF ou mPDF
        // Pour l'instant, on génère un HTML imprimable
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
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

        return !empty($titles) ? implode(', ', $titles) : $voyage_data;
    }
}



