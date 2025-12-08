<?php
/**
 * Vue Export
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('devis_pro_settings');

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

        <!-- Import (optionnel) -->
        <div class="devis-card">
            <div class="card-header">
                <h2>
                    <span class="dashicons dashicons-upload"></span>
                    <?php _e('Import de données', 'devis-pro'); ?>
                </h2>
            </div>
            <div class="card-body">
                <p><?php _e('Fonctionnalité à venir : importez des devis depuis un fichier CSV.', 'devis-pro'); ?></p>
                <button class="button" disabled>
                    <span class="dashicons dashicons-upload"></span>
                    <?php _e('Importer un CSV', 'devis-pro'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

