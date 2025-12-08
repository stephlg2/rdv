<?php
/**
 * Vue Migration
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$old_table = $wpdb->prefix . 'devis';
$migrated = get_option('devis_pro_migrated', false);
$migration_count = get_option('devis_pro_migration_count', 0);
$migration_errors = get_option('devis_pro_migration_errors', array());

// Traiter l'import CSV
$import_success = false;
$import_message = '';
if (isset($_POST['import_csv']) && isset($_FILES['csv_file'])) {
    if (!check_admin_referer('devis_pro_import_csv', 'import_nonce')) {
        $import_message = '<div class="notice notice-error"><p>' . __('Erreur de sécurité', 'devis-pro') . '</p></div>';
    } else {
        $result = devis_pro_import_csv($_FILES['csv_file']);
        if ($result['success']) {
            $import_success = true;
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

// Vérifier si l'ancienne table existe
$old_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$old_table'") === $old_table;
$old_count = 0;
if ($old_table_exists) {
    $old_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $old_table");
}
?>

<div class="wrap devis-pro-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-database-import"></span>
        <?php _e('Migration des anciens devis', 'devis-pro'); ?>
    </h1>
    
    <hr class="wp-header-end">

    <?php if ($import_message): ?>
        <?php echo $import_message; ?>
    <?php endif; ?>

    <div class="devis-migration-container">
        <?php if (!$old_table_exists): ?>
            <!-- Aucune ancienne table trouvée -->
            <div class="devis-card">
                <div class="card-body">
                    <div class="notice notice-info">
                        <p>
                            <strong><?php _e('Aucune ancienne table trouvée', 'devis-pro'); ?></strong><br>
                            <?php _e('La table "wp_devis" de l\'ancien plugin n\'existe pas dans votre base de données.', 'devis-pro'); ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php elseif ($migrated): ?>
            <!-- Migration déjà effectuée -->
            <div class="devis-card">
                <div class="card-body">
                    <div class="notice notice-success">
                        <p>
                            <strong><?php _e('Migration déjà effectuée', 'devis-pro'); ?></strong><br>
                            <?php printf(__('%d devis ont été migrés avec succès depuis l\'ancien plugin.', 'devis-pro'), $migration_count); ?>
                        </p>
                    </div>
                    
                    <?php if (!empty($migration_errors)): ?>
                        <div class="notice notice-warning" style="margin-top: 15px;">
                            <p><strong><?php _e('Erreurs rencontrées lors de la migration:', 'devis-pro'); ?></strong></p>
                            <ul style="margin-left: 20px;">
                                <?php foreach ($migration_errors as $error): ?>
                                    <li><?php echo esc_html($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <p style="margin-top: 20px;">
                        <a href="<?php echo admin_url('admin.php?page=devis-pro-list'); ?>" class="button button-primary">
                            <?php _e('Voir les devis migrés', 'devis-pro'); ?>
                        </a>
                    </p>
                </div>
            </div>
        <?php else: ?>
            <!-- Migration à effectuer -->
            <div class="devis-card">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-info"></span>
                        <?php _e('Informations sur la migration', 'devis-pro'); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <p>
                        <?php printf(__('Cette page vous permet de migrer les devis de l\'ancien plugin "Gestion de devis" vers "Devis Pro".', 'devis-pro')); ?>
                    </p>
                    <p>
                        <strong><?php _e('Devis à migrer:', 'devis-pro'); ?></strong> 
                        <span class="devis-count-badge"><?php echo $old_count; ?></span>
                    </p>
                    <div class="notice notice-warning">
                        <p>
                            <strong><?php _e('Important:', 'devis-pro'); ?></strong><br>
                            <?php _e('La migration va copier tous les devis de l\'ancienne table vers la nouvelle. Les données de l\'ancienne table ne seront pas supprimées.', 'devis-pro'); ?><br>
                            <?php _e('Assurez-vous d\'avoir fait une sauvegarde de votre base de données avant de procéder.', 'devis-pro'); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="devis-card">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-database-import"></span>
                        <?php _e('Migration automatique', 'devis-pro'); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <div id="migration-status" style="display: none;">
                        <div class="migration-progress">
                            <div class="migration-progress-bar">
                                <div class="migration-progress-fill" id="migration-progress-fill"></div>
                            </div>
                            <p class="migration-status-text" id="migration-status-text">
                                <?php _e('Préparation de la migration...', 'devis-pro'); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div id="migration-result" style="display: none;"></div>
                    
                    <div id="migration-actions">
                        <button type="button" id="start-migration" class="button button-primary button-large">
                            <span class="dashicons dashicons-database-import"></span>
                            <?php _e('Lancer la migration automatique', 'devis-pro'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <div class="devis-card">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Import depuis un fichier CSV', 'devis-pro'); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <p>
                        <?php _e('Importez des devis depuis un fichier CSV exporté depuis votre site de production.', 'devis-pro'); ?>
                    </p>
                    
                    <form method="post" enctype="multipart/form-data" id="csv-import-form">
                        <?php wp_nonce_field('devis_pro_import_csv', 'import_nonce'); ?>
                        
                        <p>
                            <label for="csv_file">
                                <strong><?php _e('Fichier CSV:', 'devis-pro'); ?></strong>
                            </label><br>
                            <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                        </p>
                        
                        <p>
                            <button type="submit" name="import_csv" class="button button-secondary">
                                <span class="dashicons dashicons-upload"></span>
                                <?php _e('Importer le CSV', 'devis-pro'); ?>
                            </button>
                        </p>
                    </form>
                    
                    <div class="notice notice-info inline" style="margin-top: 15px;">
                        <p>
                            <strong><?php _e('Instructions:', 'devis-pro'); ?></strong><br>
                            1. Sur votre site de production, allez dans <strong>Gestion des devis > Exporter (CSV)</strong><br>
                            2. Téléchargez le fichier CSV<br>
                            3. Uploadez-le ici pour l'importer
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.devis-migration-container {
    max-width: 900px;
}

.devis-count-badge {
    display: inline-block;
    background: #2271b1;
    color: #fff;
    padding: 4px 12px;
    border-radius: 3px;
    font-weight: bold;
    margin-left: 10px;
}

.migration-progress {
    margin: 20px 0;
}

.migration-progress-bar {
    width: 100%;
    height: 30px;
    background: #f0f0f0;
    border-radius: 15px;
    overflow: hidden;
    margin-bottom: 10px;
}

.migration-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #2271b1, #135e96);
    width: 0%;
    transition: width 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: bold;
    font-size: 12px;
}

.migration-status-text {
    text-align: center;
    font-weight: 500;
    color: #2271b1;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#start-migration').on('click', function() {
        var $button = $(this);
        var $status = $('#migration-status');
        var $result = $('#migration-result');
        var $actions = $('#migration-actions');
        var $progressFill = $('#migration-progress-fill');
        var $statusText = $('#migration-status-text');
        
        // Désactiver le bouton
        $button.prop('disabled', true);
        
        // Afficher le statut
        $status.show();
        $actions.hide();
        $result.hide();
        
        // Mettre à jour le statut
        $statusText.text('Migration en cours...');
        $progressFill.css('width', '10%');
        
        // Lancer la migration via AJAX
        $.ajax({
            url: typeof devisProAdmin !== 'undefined' ? devisProAdmin.ajaxurl : ajaxurl,
            type: 'POST',
            data: {
                action: 'devis_pro_migrate',
                nonce: typeof devisProAdmin !== 'undefined' ? devisProAdmin.nonce : '<?php echo wp_create_nonce('devis_pro_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $progressFill.css('width', '100%');
                    $statusText.text('Migration terminée !');
                    
                    setTimeout(function() {
                        $status.hide();
                        $result.html(
                            '<div class="notice notice-success"><p><strong>' + 
                            response.data.message + 
                            '</strong></p></div>' +
                            '<p><a href="<?php echo admin_url('admin.php?page=devis-pro-list'); ?>" class="button button-primary">' +
                            'Voir les devis migrés</a></p>'
                        ).show();
                    }, 1000);
                } else {
                    $status.hide();
                    $result.html(
                        '<div class="notice notice-error"><p><strong>Erreur:</strong> ' + 
                        (response.data && response.data.message ? response.data.message : 'Erreur inconnue') + 
                        '</p></div>'
                    ).show();
                    $actions.show();
                    $button.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                $status.hide();
                $result.html(
                    '<div class="notice notice-error"><p><strong>Erreur:</strong> ' + 
                    'Une erreur est survenue lors de la migration. Veuillez réessayer.</p></div>'
                ).show();
                $actions.show();
                $button.prop('disabled', false);
            }
        });
    });
});
</script>

