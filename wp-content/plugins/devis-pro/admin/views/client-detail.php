<?php
/**
 * Vue Détail d'un client
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap devis-pro-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-users"></span>
        <?php _e('Détail du client', 'devis-pro'); ?>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=devis-pro-clients'); ?>" class="page-title-action">
        <?php _e('Retour à la liste', 'devis-pro'); ?>
    </a>
    
    <hr class="wp-header-end">

    <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Client mis à jour avec succès.', 'devis-pro'); ?></p>
        </div>
    <?php endif; ?>

    <div class="devis-detail-grid">
        <!-- Colonne principale -->
        <div class="devis-main-column">
            <!-- Informations client -->
            <div class="devis-card">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php _e('Informations personnelles', 'devis-pro'); ?>
                    </h2>
                    <button type="button" class="button button-secondary" id="toggle-edit-client" onclick="toggleClientEdit()">
                        <span class="dashicons dashicons-edit"></span>
                        <?php _e('Modifier', 'devis-pro'); ?>
                    </button>
                </div>
                <div class="card-body">
                    <!-- Vue lecture seule -->
                    <div id="client-view-mode">
                        <div class="client-info-grid">
                            <div class="info-group">
                                <label><?php _e('Nom complet', 'devis-pro'); ?></label>
                                <p><strong><?php echo esc_html(trim($client->civ . ' ' . $client->prenom . ' ' . $client->nom)); ?></strong></p>
                            </div>
                            <div class="info-group">
                                <label><?php _e('Email', 'devis-pro'); ?></label>
                                <p><a href="mailto:<?php echo esc_attr($client->email); ?>"><?php echo esc_html($client->email); ?></a></p>
                            </div>
                            <div class="info-group">
                                <label><?php _e('Téléphone', 'devis-pro'); ?></label>
                                <p><a href="tel:<?php echo esc_attr($client->tel); ?>"><?php echo esc_html($client->tel); ?></a></p>
                            </div>
                            <div class="info-group">
                                <label><?php _e('Adresse', 'devis-pro'); ?></label>
                                <p><?php echo esc_html(trim($client->cp . ' ' . $client->ville)); ?></p>
                            </div>
                            <div class="info-group">
                                <label><?php _e('Date premier devis', 'devis-pro'); ?></label>
                                <p><?php echo esc_html(date_i18n('d/m/Y à H:i', strtotime($client->first_devis_date))); ?></p>
                            </div>
                            <div class="info-group">
                                <label><?php _e('Date dernier devis', 'devis-pro'); ?></label>
                                <p><?php echo esc_html(date_i18n('d/m/Y à H:i', strtotime($client->last_devis_date))); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Formulaire d'édition -->
                    <div id="client-edit-mode" style="display: none;">
                        <form method="post" action="">
                            <?php wp_nonce_field('update_client_' . $client->email, '_wpnonce'); ?>
                            <input type="hidden" name="update_client" value="1">
                            <input type="hidden" name="client_email" value="<?php echo esc_attr($client->email); ?>">
                            
                            <div class="client-info-grid">
                                <div class="info-group">
                                    <label><?php _e('Civilité', 'devis-pro'); ?></label>
                                    <select name="civ" class="regular-text">
                                        <option value="">—</option>
                                        <option value="M." <?php selected($client->civ, 'M.'); ?>>M.</option>
                                        <option value="Mme" <?php selected($client->civ, 'Mme'); ?>>Mme</option>
                                        <option value="Mlle" <?php selected($client->civ, 'Mlle'); ?>>Mlle</option>
                                    </select>
                                </div>
                                <div class="info-group">
                                    <label><?php _e('Prénom', 'devis-pro'); ?></label>
                                    <input type="text" name="prenom" value="<?php echo esc_attr($client->prenom); ?>" class="regular-text">
                                </div>
                                <div class="info-group">
                                    <label><?php _e('Nom', 'devis-pro'); ?></label>
                                    <input type="text" name="nom" value="<?php echo esc_attr($client->nom); ?>" class="regular-text">
                                </div>
                                <div class="info-group">
                                    <label><?php _e('Email', 'devis-pro'); ?></label>
                                    <input type="email" name="new_email" value="<?php echo esc_attr($client->email); ?>" class="regular-text">
                                    <p class="description"><?php _e('Si vous changez l\'email, tous les devis seront mis à jour.', 'devis-pro'); ?></p>
                                </div>
                                <div class="info-group">
                                    <label><?php _e('Téléphone', 'devis-pro'); ?></label>
                                    <input type="tel" name="tel" value="<?php echo esc_attr($client->tel); ?>" class="regular-text">
                                </div>
                                <div class="info-group">
                                    <label><?php _e('Code postal', 'devis-pro'); ?></label>
                                    <input type="text" name="cp" value="<?php echo esc_attr($client->cp); ?>" class="regular-text">
                                </div>
                                <div class="info-group">
                                    <label><?php _e('Ville', 'devis-pro'); ?></label>
                                    <input type="text" name="ville" value="<?php echo esc_attr($client->ville); ?>" class="regular-text">
                                </div>
                            </div>
                            
                            <p class="submit">
                                <button type="submit" class="button button-primary"><?php _e('Enregistrer les modifications', 'devis-pro'); ?></button>
                                <button type="button" class="button button-secondary" onclick="toggleClientEdit()"><?php _e('Annuler', 'devis-pro'); ?></button>
                            </p>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="devis-card">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-chart-bar"></span>
                        <?php _e('Statistiques', 'devis-pro'); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo intval($client->total_devis); ?></div>
                            <div class="stat-label"><?php _e('Total devis', 'devis-pro'); ?></div>
                        </div>
                        <div class="stat-item stat-success">
                            <div class="stat-value"><?php echo intval($client->devis_acceptes); ?></div>
                            <div class="stat-label"><?php _e('Devis acceptés', 'devis-pro'); ?></div>
                        </div>
                        <div class="stat-item stat-warning">
                            <div class="stat-value"><?php echo intval($client->devis_archives); ?></div>
                            <div class="stat-label"><?php _e('Devis archivés', 'devis-pro'); ?></div>
                        </div>
                        <div class="stat-item stat-danger">
                            <div class="stat-value"><?php echo intval($client->devis_refuses); ?></div>
                            <div class="stat-label"><?php _e('Devis refusés', 'devis-pro'); ?></div>
                        </div>
                        <div class="stat-item stat-primary">
                            <div class="stat-value"><?php echo number_format($client->total_montant, 2, ',', ' '); ?> €</div>
                            <div class="stat-label"><?php _e('Montant total', 'devis-pro'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compte WordPress -->
            <?php if ($client->wp_user_id): ?>
            <div class="devis-card">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php _e('Compte WordPress', 'devis-pro'); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <div class="client-info-grid">
                        <div class="info-group">
                            <label><?php _e('ID utilisateur', 'devis-pro'); ?></label>
                            <p><a href="<?php echo admin_url('user-edit.php?user_id=' . $client->wp_user_id); ?>"><?php echo esc_html($client->wp_user_id); ?></a></p>
                        </div>
                        <div class="info-group">
                            <label><?php _e('Nom d\'utilisateur', 'devis-pro'); ?></label>
                            <p><?php echo esc_html($client->wp_user_login); ?></p>
                        </div>
                        <div class="info-group">
                            <label><?php _e('Date de création', 'devis-pro'); ?></label>
                            <p><?php echo esc_html(date_i18n('d/m/Y à H:i', strtotime($client->wp_user_registered))); ?></p>
                        </div>
                        <div class="info-group">
                            <label><?php _e('Rôles', 'devis-pro'); ?></label>
                            <p><?php echo esc_html(implode(', ', $client->wp_user_roles)); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Newsletter -->
            <div class="devis-card">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-email-alt"></span>
                        <?php _e('Newsletter', 'devis-pro'); ?>
                    </h2>
                    <button type="button" class="button button-secondary" id="toggle-edit-newsletter" onclick="toggleNewsletterEdit()">
                        <span class="dashicons dashicons-edit"></span>
                        <?php _e('Modifier', 'devis-pro'); ?>
                    </button>
                </div>
                <div class="card-body">
                    <!-- Vue lecture seule -->
                    <div id="newsletter-view-mode">
                        <p>
                            <?php if ($client->newsletter_subscribed): ?>
                                <span class="dashicons dashicons-yes-alt" style="color: #46b450; font-size: 20px;"></span>
                                <strong><?php _e('Inscrit à la newsletter', 'devis-pro'); ?></strong>
                            <?php else: ?>
                                <span class="dashicons dashicons-dismiss" style="color: #dc3232; font-size: 20px;"></span>
                                <strong><?php _e('Non inscrit à la newsletter', 'devis-pro'); ?></strong>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <!-- Formulaire d'édition -->
                    <div id="newsletter-edit-mode" style="display: none;">
                        <form method="post" action="">
                            <?php wp_nonce_field('update_client_' . $client->email, '_wpnonce'); ?>
                            <input type="hidden" name="update_client" value="1">
                            <input type="hidden" name="client_email" value="<?php echo esc_attr($client->email); ?>">
                            <input type="hidden" name="civ" value="<?php echo esc_attr($client->civ); ?>">
                            <input type="hidden" name="nom" value="<?php echo esc_attr($client->nom); ?>">
                            <input type="hidden" name="prenom" value="<?php echo esc_attr($client->prenom); ?>">
                            <input type="hidden" name="tel" value="<?php echo esc_attr($client->tel); ?>">
                            <input type="hidden" name="cp" value="<?php echo esc_attr($client->cp); ?>">
                            <input type="hidden" name="ville" value="<?php echo esc_attr($client->ville); ?>">
                            
                            <p>
                                <label>
                                    <input type="checkbox" name="newsletter" value="1" <?php checked($client->newsletter_subscribed, true); ?>>
                                    <?php _e('Inscrit à la newsletter', 'devis-pro'); ?>
                                </label>
                            </p>
                            
                            <p class="submit">
                                <button type="submit" class="button button-primary"><?php _e('Enregistrer', 'devis-pro'); ?></button>
                                <button type="button" class="button button-secondary" onclick="toggleNewsletterEdit()"><?php _e('Annuler', 'devis-pro'); ?></button>
                            </p>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Commandes WooCommerce -->
            <?php if (!empty($client->woocommerce_orders)): ?>
            <div class="devis-card">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-cart"></span>
                        <?php _e('Commandes WooCommerce', 'devis-pro'); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <p><strong><?php _e('Total dépensé:', 'devis-pro'); ?></strong> <?php echo number_format($client->woocommerce_total_spent, 2, ',', ' '); ?> €</p>
                    <p><strong><?php _e('Nombre de commandes:', 'devis-pro'); ?></strong> <?php echo count($client->woocommerce_orders); ?></p>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('ID Commande', 'devis-pro'); ?></th>
                                <th><?php _e('Date', 'devis-pro'); ?></th>
                                <th><?php _e('Statut', 'devis-pro'); ?></th>
                                <th><?php _e('Montant', 'devis-pro'); ?></th>
                                <th><?php _e('Articles', 'devis-pro'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($client->woocommerce_orders as $order): ?>
                            <tr>
                                <td><a href="<?php echo admin_url('post.php?post=' . $order['id'] . '&action=edit'); ?>">#<?php echo esc_html($order['id']); ?></a></td>
                                <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($order['date']))); ?></td>
                                <td><?php echo esc_html($order['status']); ?></td>
                                <td><?php echo number_format($order['total'], 2, ',', ' '); ?> €</td>
                                <td><?php echo esc_html($order['items']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Liste des devis -->
            <div class="devis-card">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-clipboard"></span>
                        <?php _e('Tous les devis', 'devis-pro'); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($client->devis)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('ID', 'devis-pro'); ?></th>
                                <th><?php _e('Date', 'devis-pro'); ?></th>
                                <th><?php _e('Destination', 'devis-pro'); ?></th>
                                <th><?php _e('Dates voyage', 'devis-pro'); ?></th>
                                <th><?php _e('Montant', 'devis-pro'); ?></th>
                                <th><?php _e('Statut', 'devis-pro'); ?></th>
                                <th><?php _e('Actions', 'devis-pro'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($client->devis as $devis): ?>
                            <tr>
                                <td><a href="<?php echo admin_url('admin.php?page=devis-pro-detail&id=' . $devis->id); ?>">#<?php echo esc_html($devis->id); ?></a></td>
                                <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($devis->demande))); ?></td>
                                <td><?php echo esc_html($devis->destination); ?></td>
                                <td><?php echo esc_html($devis->depart . ' - ' . $devis->retour); ?></td>
                                <td><?php echo number_format($devis->montant, 2, ',', ' '); ?> €</td>
                                <td>
                                    <?php
                                    $status_labels = array(
                                        0 => __('En attente', 'devis-pro'),
                                        1 => __('Accepté', 'devis-pro'),
                                        2 => __('Archivé', 'devis-pro'),
                                        3 => __('Refusé', 'devis-pro')
                                    );
                                    echo esc_html($status_labels[$devis->status] ?? __('Inconnu', 'devis-pro'));
                                    ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=devis-pro-detail&id=' . $devis->id); ?>"><?php _e('Voir', 'devis-pro'); ?></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p><?php _e('Aucun devis trouvé pour ce client.', 'devis-pro'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-top: 20px;
}
.stat-item {
    text-align: center;
    padding: 20px;
    background: #f5f5f5;
    border-radius: 5px;
}
.stat-item.stat-success {
    background: #e8f5e9;
}
.stat-item.stat-warning {
    background: #fff3e0;
}
.stat-item.stat-danger {
    background: #ffebee;
}
.stat-item.stat-primary {
    background: #e3f2fd;
}
.stat-value {
    font-size: 32px;
    font-weight: bold;
    color: #2271b1;
    margin-bottom: 5px;
}
.stat-success .stat-value {
    color: #46b450;
}
.stat-warning .stat-value {
    color: #ffb900;
}
.stat-danger .stat-value {
    color: #dc3232;
}
.stat-primary .stat-value {
    color: #2271b1;
}
.stat-label {
    font-size: 14px;
    color: #666;
}
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0;
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
}
.card-header h2 {
    margin: 0;
    padding: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 18px;
    font-weight: 600;
    flex: 1;
}
.card-header h2 .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
    line-height: 1;
}
.card-header .button {
    margin: 0;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
}
.card-header .button .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    line-height: 1;
    margin: 0;
}
.client-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}
.info-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    color: #333;
}
.info-group input[type="text"],
.info-group input[type="email"],
.info-group input[type="tel"],
.info-group select {
    width: 100%;
    max-width: 400px;
}
</style>

<script>
function toggleClientEdit() {
    var viewMode = document.getElementById('client-view-mode');
    var editMode = document.getElementById('client-edit-mode');
    var toggleBtn = document.getElementById('toggle-edit-client');
    
    if (viewMode.style.display === 'none') {
        viewMode.style.display = 'block';
        editMode.style.display = 'none';
        toggleBtn.innerHTML = '<span class="dashicons dashicons-edit"></span> <?php echo esc_js(__('Modifier', 'devis-pro')); ?>';
    } else {
        viewMode.style.display = 'none';
        editMode.style.display = 'block';
        toggleBtn.innerHTML = '<span class="dashicons dashicons-no"></span> <?php echo esc_js(__('Annuler', 'devis-pro')); ?>';
    }
}

function toggleNewsletterEdit() {
    var viewMode = document.getElementById('newsletter-view-mode');
    var editMode = document.getElementById('newsletter-edit-mode');
    var toggleBtn = document.getElementById('toggle-edit-newsletter');
    
    if (viewMode.style.display === 'none') {
        viewMode.style.display = 'block';
        editMode.style.display = 'none';
        toggleBtn.innerHTML = '<span class="dashicons dashicons-edit"></span> <?php echo esc_js(__('Modifier', 'devis-pro')); ?>';
    } else {
        viewMode.style.display = 'none';
        editMode.style.display = 'block';
        toggleBtn.innerHTML = '<span class="dashicons dashicons-no"></span> <?php echo esc_js(__('Annuler', 'devis-pro')); ?>';
    }
}
</script>

