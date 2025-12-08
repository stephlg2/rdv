<?php
/**
 * Vue Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('devis_pro_settings');
?>

<div class="wrap devis-pro-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-clipboard"></span>
        <?php _e('Devis Pro - Dashboard', 'devis-pro'); ?>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=devis-pro-add'); ?>" class="page-title-action">
        <?php _e('Ajouter un devis', 'devis-pro'); ?>
    </a>
    
    <hr class="wp-header-end">

    <!-- Statistiques principales -->
    <div class="devis-stats-grid">
        <div class="stat-card stat-primary">
            <div class="stat-icon">
                <span class="dashicons dashicons-clipboard"></span>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo number_format($data['global']['total']); ?></span>
                <span class="stat-label"><?php _e('Total des devis', 'devis-pro'); ?></span>
            </div>
        </div>
        
        <div class="stat-card stat-success">
            <div class="stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo number_format($data['global']['total_amount'], 0, ',', ' '); ?> €</span>
                <span class="stat-label"><?php _e('Montant total accepté', 'devis-pro'); ?></span>
            </div>
        </div>
        
        <div class="stat-card stat-info">
            <div class="stat-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo $data['global']['conversion_rate']; ?>%</span>
                <span class="stat-label"><?php _e('Taux de conversion', 'devis-pro'); ?></span>
            </div>
        </div>
        
        <div class="stat-card stat-warning">
            <div class="stat-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo number_format($data['month']['count']); ?></span>
                <span class="stat-label"><?php _e('Devis ce mois', 'devis-pro'); ?></span>
            </div>
        </div>
    </div>

    <div class="devis-dashboard-grid">
        <!-- Colonne principale -->
        <div class="devis-main-column">
            <!-- Graphique d'évolution -->
            <div class="devis-card">
                <div class="card-header">
                    <h2><?php _e('Évolution mensuelle', 'devis-pro'); ?></h2>
                    <div class="card-actions">
                        <select id="chart-period">
                            <option value="week"><?php _e('7 derniers jours', 'devis-pro'); ?></option>
                            <option value="month" selected><?php _e('30 derniers jours', 'devis-pro'); ?></option>
                            <option value="year"><?php _e('12 derniers mois', 'devis-pro'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="devis-evolution-chart" height="300"></canvas>
                </div>
            </div>

            <!-- Répartition par statut -->
            <div class="devis-card">
                <div class="card-header">
                    <h2><?php _e('Répartition par statut', 'devis-pro'); ?></h2>
                </div>
                <div class="card-body">
                    <div class="status-chart-container">
                        <canvas id="devis-status-chart" height="200"></canvas>
                        <div class="status-legend">
                            <?php foreach ($data['status_distribution'] as $status) : ?>
                                <div class="legend-item">
                                    <span class="legend-color" style="background: <?php echo esc_attr($status['color']); ?>"></span>
                                    <span class="legend-label"><?php echo esc_html($status['label']); ?></span>
                                    <span class="legend-value"><?php echo $status['count']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Derniers devis -->
            <div class="devis-card">
                <div class="card-header">
                    <h2><?php _e('Derniers devis', 'devis-pro'); ?></h2>
                    <a href="<?php echo admin_url('admin.php?page=devis-pro-list'); ?>" class="button">
                        <?php _e('Voir tous', 'devis-pro'); ?>
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($data['recent'])) : ?>
                        <table class="devis-table">
                            <thead>
                                <tr>
                                    <th><?php _e('ID', 'devis-pro'); ?></th>
                                    <th><?php _e('Client', 'devis-pro'); ?></th>
                                    <th><?php _e('Date', 'devis-pro'); ?></th>
                                    <th><?php _e('Montant', 'devis-pro'); ?></th>
                                    <th><?php _e('Statut', 'devis-pro'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['recent'] as $devis) : 
                                    $status = $settings['statuses'][$devis->status] ?? array('label' => 'Inconnu', 'color' => '#6c757d');
                                ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo admin_url('admin.php?page=devis-pro-detail&id=' . $devis->id); ?>">
                                                <strong>#<?php echo $devis->id; ?></strong>
                                            </a>
                                        </td>
                                        <td><?php echo esc_html($devis->prenom . ' ' . $devis->nom); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($devis->demande)); ?></td>
                                        <td>
                                            <?php echo $devis->montant > 0 
                                                ? number_format($devis->montant, 2, ',', ' ') . ' €' 
                                                : '-'; ?>
                                        </td>
                                        <td>
                                            <span class="devis-status" style="background: <?php echo esc_attr($status['color']); ?>">
                                                <?php echo esc_html($status['label']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p class="no-data"><?php _e('Aucun devis pour le moment.', 'devis-pro'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="devis-sidebar">
            <!-- Devis en attente -->
            <div class="devis-card card-warning">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-warning"></span>
                        <?php _e('En attente de traitement', 'devis-pro'); ?>
                    </h2>
                    <span class="badge"><?php echo count($data['pending']); ?></span>
                </div>
                <div class="card-body">
                    <?php if (!empty($data['pending'])) : ?>
                        <ul class="devis-list">
                            <?php foreach (array_slice($data['pending'], 0, 5) as $devis) : ?>
                                <li>
                                    <a href="<?php echo admin_url('admin.php?page=devis-pro-detail&id=' . $devis->id); ?>">
                                        <strong><?php echo esc_html($devis->prenom . ' ' . $devis->nom); ?></strong>
                                        <span class="date"><?php echo date('d/m/Y H:i', strtotime($devis->demande)); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (count($data['pending']) > 5) : ?>
                            <a href="<?php echo admin_url('admin.php?page=devis-pro-list&status=0'); ?>" class="see-all">
                                <?php printf(__('Voir les %d devis en attente →', 'devis-pro'), count($data['pending'])); ?>
                            </a>
                        <?php endif; ?>
                    <?php else : ?>
                        <p class="no-data success">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('Aucun devis en attente !', 'devis-pro'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- En attente de paiement -->
            <div class="devis-card card-info">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-money-alt"></span>
                        <?php _e('Attente de paiement', 'devis-pro'); ?>
                    </h2>
                    <span class="badge"><?php echo count($data['awaiting_payment']); ?></span>
                </div>
                <div class="card-body">
                    <?php if (!empty($data['awaiting_payment'])) : ?>
                        <ul class="devis-list">
                            <?php foreach (array_slice($data['awaiting_payment'], 0, 5) as $devis) : ?>
                                <li>
                                    <a href="<?php echo admin_url('admin.php?page=devis-pro-detail&id=' . $devis->id); ?>">
                                        <strong><?php echo esc_html($devis->prenom . ' ' . $devis->nom); ?></strong>
                                        <span class="amount"><?php echo number_format($devis->montant, 0, ',', ' '); ?> €</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="no-data"><?php _e('Aucun devis en attente de paiement.', 'devis-pro'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top destinations -->
            <div class="devis-card">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-location-alt"></span>
                        <?php _e('Top destinations', 'devis-pro'); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($data['top_destinations'])) : ?>
                        <ul class="destination-list">
                            <?php foreach ($data['top_destinations'] as $i => $dest) : ?>
                                <li>
                                    <span class="rank"><?php echo $i + 1; ?></span>
                                    <span class="title"><?php echo esc_html($dest['title']); ?></span>
                                    <span class="count"><?php echo $dest['count']; ?> demandes</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="no-data"><?php _e('Pas encore de données.', 'devis-pro'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="devis-card">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-chart-bar"></span>
                        <?php _e('Cette semaine', 'devis-pro'); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <div class="quick-stats">
                        <div class="quick-stat">
                            <span class="value"><?php echo $data['week']['count']; ?></span>
                            <span class="label"><?php _e('Nouveaux devis', 'devis-pro'); ?></span>
                        </div>
                        <div class="quick-stat">
                            <span class="value"><?php echo number_format($data['week']['amount'], 0, ',', ' '); ?> €</span>
                            <span class="label"><?php _e('Montant accepté', 'devis-pro'); ?></span>
                        </div>
                        <div class="quick-stat">
                            <span class="value"><?php echo $data['today']['count']; ?></span>
                            <span class="label"><?php _e("Aujourd'hui", 'devis-pro'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Données pour les graphiques
    const monthlyData = <?php echo json_encode($data['monthly_evolution']); ?>;
    const statusData = <?php echo json_encode($data['status_distribution']); ?>;
    
    // Graphique d'évolution
    if (document.getElementById('devis-evolution-chart') && monthlyData.length > 0) {
        new Chart(document.getElementById('devis-evolution-chart'), {
            type: 'line',
            data: {
                labels: monthlyData.map(d => d.label),
                datasets: [{
                    label: 'Nombre de devis',
                    data: monthlyData.map(d => d.count),
                    borderColor: '#de5b09',
                    backgroundColor: 'rgba(222, 91, 9, 0.1)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Montant (€)',
                    data: monthlyData.map(d => d.amount),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left'
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }
    
    // Graphique de répartition par statut
    if (document.getElementById('devis-status-chart') && statusData.length > 0) {
        new Chart(document.getElementById('devis-status-chart'), {
            type: 'doughnut',
            data: {
                labels: statusData.map(d => d.label),
                datasets: [{
                    data: statusData.map(d => d.count),
                    backgroundColor: statusData.map(d => d.color),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                cutout: '70%'
            }
        });
    }
});
</script>


