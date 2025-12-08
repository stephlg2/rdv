<?php
/**
 * Vue Export
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('devis_pro_settings');

// Traiter l'import CSV
$import_message = '';
if (isset($_POST['import_csv']) && isset($_FILES['csv_file'])) {
    if (!check_admin_referer('devis_pro_import_csv', 'import_nonce')) {
        $import_message = '<div class="notice notice-error"><p>' . __('Erreur de sécurité', 'devis-pro') . '</p></div>';
    } else {
        $result = devis_pro_import_csv($_FILES['csv_file']);
        if ($result['success']) {
            $import_message = '<div class="notice notice-success"><p>' . 
                sprintf(__('%d devis importés avec succès !', 'devis-pro'), $result['count']) . 
                '</p></div>';
            if (!empty($result['errors'])) {
                $import_message .= '<div class="notice notice-warning"><p><strong>' . 
                    __('Avertissements:', 'devis-pro') . '</strong><br>' . 
                    implode('<br>', array_slice($result['errors'], 0, 10)) . 
                    '</p></div>';
            }
        } else {
            $import_message = '<div class="notice notice-error"><p>' . 
                esc_html($result['message']) . 
                '</p></div>';
        }
    }
}

// Fonction d'import CSV
function devis_pro_import_csv($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return array('success' => false, 'message' => __('Erreur lors de l\'upload du fichier', 'devis-pro'));
    }
    
    if (!str_ends_with(strtolower($file['name']), '.csv')) {
        return array('success' => false, 'message' => __('Le fichier doit être au format CSV', 'devis-pro'));
    }
    
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        return array('success' => false, 'message' => __('Impossible de lire le fichier', 'devis-pro'));
    }
    
    // Sauter le BOM UTF-8 si présent
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($handle);
    }
    
    $db = new Devis_Pro_DB();
    $imported = 0;
    $errors = array();
    $line = 0;
    
    // Lire l'en-tête
    $headers = fgetcsv($handle, 0, ';');
    if (!$headers) {
        fclose($handle);
        return array('success' => false, 'message' => __('Fichier CSV vide ou invalide', 'devis-pro'));
    }
    
    // Lire les données
    while (($data = fgetcsv($handle, 0, ';')) !== false) {
        $line++;
        
        if (count($data) < 10) {
            $errors[] = sprintf(__('Ligne %d: données incomplètes', 'devis-pro'), $line);
            continue;
        }
        
        try {
            $devis_data = array(
                'destination' => sanitize_text_field($data[1] ?? ''),
                'voyage' => sanitize_textarea_field($data[2] ?? ''),
                'depart' => sanitize_text_field($data[3] ?? ''),
                'retour' => sanitize_text_field($data[4] ?? ''),
                'duree' => sanitize_text_field($data[5] ?? ''),
                'budget' => floatval($data[6] ?? 0),
                'adulte' => intval($data[7] ?? 1),
                'enfant' => intval($data[8] ?? 0),
                'bebe' => intval($data[9] ?? 0),
                'vol' => sanitize_text_field($data[10] ?? ''),
                'message' => sanitize_textarea_field($data[11] ?? ''),
                'civ' => sanitize_text_field($data[12] ?? ''),
                'nom' => sanitize_text_field($data[13] ?? ''),
                'prenom' => sanitize_text_field($data[14] ?? ''),
                'email' => sanitize_email($data[15] ?? ''),
                'cp' => sanitize_text_field($data[16] ?? ''),
                'ville' => sanitize_text_field($data[17] ?? ''),
                'tel' => sanitize_text_field($data[18] ?? ''),
                'status' => intval($data[19] ?? 0),
                'montant' => floatval($data[20] ?? 0),
                'langue' => sanitize_text_field($data[22] ?? 'fr'),
                'token' => sanitize_text_field($data[23] ?? ''),
                'mac' => sanitize_text_field($data[24] ?? ''),
                'reminders_count' => 0,
                'last_reminder' => null
            );
            
            // Convertir la date
            if (!empty($data[21])) {
                $devis_data['demande'] = date('Y-m-d H:i:s', strtotime($data[21]));
            } else {
                $devis_data['demande'] = current_time('mysql');
            }
            
            $id = $db->insert_devis($devis_data);
            if ($id) {
                $db->add_history($id, 'import', sprintf(__('Devis importé depuis CSV (ligne %d)', 'devis-pro'), $line));
                $imported++;
            }
            
        } catch (Exception $e) {
            $errors[] = sprintf(__('Ligne %d: %s', 'devis-pro'), $line, $e->getMessage());
        }
    }
    
    fclose($handle);
    
    return array(
        'success' => true,
        'count' => $imported,
        'errors' => $errors
    );
}

// Export PDF d'un devis unique
if (isset($_GET['id']) && isset($_GET['format']) && $_GET['format'] === 'pdf') {
    $export = new Devis_Pro_Export();
    $export->export_pdf_single(intval($_GET['id']));
    exit;
}
?>

<div class="wrap devis-pro-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-download"></span>
        <?php _e('Export des devis', 'devis-pro'); ?>
    </h1>
    
    <hr class="wp-header-end">

    <?php if ($import_message): ?>
        <?php echo $import_message; ?>
    <?php endif; ?>

    <div class="devis-export-grid">
        <!-- Export CSV -->
        <div class="devis-card">
            <div class="card-header">
                <h2>
                    <span class="dashicons dashicons-media-spreadsheet"></span>
                    <?php _e('Export CSV', 'devis-pro'); ?>
                </h2>
            </div>
            <div class="card-body">
                <p><?php _e('Exportez vos devis au format CSV pour les analyser dans Excel ou Google Sheets.', 'devis-pro'); ?></p>
                
                <form method="post">
                    <?php wp_nonce_field('devis_pro_export'); ?>
                    <input type="hidden" name="devis_pro_export_csv" value="1">
                    
                    <div class="export-filters">
                        <div class="filter-group">
                            <label for="export_status"><?php _e('Statut', 'devis-pro'); ?></label>
                            <select id="export_status" name="status">
                                <option value=""><?php _e('Tous les statuts', 'devis-pro'); ?></option>
                                <?php foreach ($settings['statuses'] as $key => $status) : ?>
                                    <option value="<?php echo $key; ?>"><?php echo esc_html($status['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="export_date_from"><?php _e('Date début', 'devis-pro'); ?></label>
                            <input type="date" id="export_date_from" name="date_from">
                        </div>
                        
                        <div class="filter-group">
                            <label for="export_date_to"><?php _e('Date fin', 'devis-pro'); ?></label>
                            <input type="date" id="export_date_to" name="date_to">
                        </div>
                    </div>
                    
                    <button type="submit" class="button button-primary button-large">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Télécharger le CSV', 'devis-pro'); ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Statistiques d'export -->
        <div class="devis-card">
            <div class="card-header">
                <h2>
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php _e('Résumé des données', 'devis-pro'); ?>
                </h2>
            </div>
            <div class="card-body">
                <?php
                $db = new Devis_Pro_DB();
                $stats = $db->get_global_stats();
                ?>
                <ul class="export-stats">
                    <li>
                        <span class="stat-label"><?php _e('Total des devis', 'devis-pro'); ?></span>
                        <span class="stat-value"><?php echo number_format($stats['total']); ?></span>
                    </li>
                    <?php foreach ($stats['by_status'] as $status => $count) : 
                        $status_info = $settings['statuses'][$status] ?? array('label' => 'Inconnu', 'color' => '#6c757d');
                    ?>
                        <li>
                            <span class="stat-label">
                                <span class="color-dot" style="background: <?php echo esc_attr($status_info['color']); ?>"></span>
                                <?php echo esc_html($status_info['label']); ?>
                            </span>
                            <span class="stat-value"><?php echo number_format($count); ?></span>
                        </li>
                    <?php endforeach; ?>
                    <li class="stat-highlight">
                        <span class="stat-label"><?php _e('Montant total accepté', 'devis-pro'); ?></span>
                        <span class="stat-value"><?php echo number_format($stats['total_amount'], 0, ',', ' '); ?> €</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Import CSV -->
        <div class="devis-card">
            <div class="card-header">
                <h2>
                    <span class="dashicons dashicons-upload"></span>
                    <?php _e('Import de données', 'devis-pro'); ?>
                </h2>
            </div>
            <div class="card-body">
                <p><?php _e('Importez des devis depuis un fichier CSV exporté depuis votre ancien plugin ou depuis la production.', 'devis-pro'); ?></p>
                
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('devis_pro_import_csv', 'import_nonce'); ?>
                    
                    <div class="import-file-input">
                        <label for="csv_file">
                            <strong><?php _e('Fichier CSV:', 'devis-pro'); ?></strong>
                        </label><br>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" required style="margin: 10px 0;">
                    </div>
                    
                    <button type="submit" name="import_csv" class="button button-secondary">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Importer le CSV', 'devis-pro'); ?>
                    </button>
                </form>
                
                <div style="margin-top: 15px; padding: 10px; background: #f0f6fc; border-left: 3px solid #2271b1;">
                    <p style="margin: 0;">
                        <strong><?php _e('Instructions:', 'devis-pro'); ?></strong><br>
                        <small>
                            1. Sur votre site de production, exportez les devis en CSV<br>
                            2. Sélectionnez le fichier CSV ci-dessus<br>
                            3. Cliquez sur "Importer le CSV"
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

