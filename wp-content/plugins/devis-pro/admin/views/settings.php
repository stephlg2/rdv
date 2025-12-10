<?php
/**
 * Vue Réglages
 */

if (!defined('ABSPATH')) {
    exit;
}

settings_errors('devis_pro');

// Migration
if (isset($_GET['migrate']) && $_GET['migrate'] == 1) {
    $db = new Devis_Pro_DB();
    $migrated = $db->migrate_from_old_plugin();
    if ($migrated !== false) {
        echo '<div class="notice notice-success"><p>' . sprintf(__('%d devis migrés avec succès !', 'devis-pro'), $migrated) . '</p></div>';
    }
}
?>

<div class="wrap devis-pro-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-settings"></span>
        <?php _e('Réglages', 'devis-pro'); ?>
    </h1>
    
    <hr class="wp-header-end">

    <form method="post">
        <?php wp_nonce_field('devis_pro_settings'); ?>
        <input type="hidden" name="devis_pro_save_settings" value="1">

        <div class="devis-settings-grid">
            <!-- Emails -->
            <div class="devis-card">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-email"></span>
                        <?php _e('Configuration des emails', 'devis-pro'); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="email_admin"><?php _e('Email administrateur', 'devis-pro'); ?></label>
                            </th>
                            <td>
                                <input type="email" id="email_admin" name="email_admin" value="<?php echo esc_attr($settings['email_admin']); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Email qui recevra les notifications de nouvelles demandes.', 'devis-pro'); ?>
                                    <br>
                                    <strong><?php _e('Email actuel configuré :', 'devis-pro'); ?></strong> 
                                    <code style="background:#f0f0f0;padding:2px 6px;border-radius:3px;">
                                        <?php echo esc_html($settings['email_admin'] ?? get_option('admin_email')); ?>
                                    </code>
                                </p>
                                <p style="margin-top:10px;">
                                    <form method="post" style="display:inline;">
                                        <?php wp_nonce_field('devis_pro_test_email'); ?>
                                        <button type="submit" name="devis_pro_send_test_email" class="button button-secondary">
                                            <span class="dashicons dashicons-email-alt"></span>
                                            <?php _e('Envoyer un email test', 'devis-pro'); ?>
                                        </button>
                                    </form>
                                    <span class="description" style="margin-left:10px;">
                                        <?php _e('Envoie un email de test avec le template de notification de nouvelle demande.', 'devis-pro'); ?>
                                    </span>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="email_from_name"><?php _e('Nom expéditeur', 'devis-pro'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="email_from_name" name="email_from_name" value="<?php echo esc_attr($settings['email_from_name']); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="email_from_address"><?php _e('Email expéditeur', 'devis-pro'); ?></label>
                            </th>
                            <td>
                                <input type="email" id="email_from_address" name="email_from_address" value="<?php echo esc_attr($settings['email_from_address']); ?>" class="regular-text">
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Relances automatiques -->
            <div class="devis-card">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-clock"></span>
                        <?php _e('Relances automatiques', 'devis-pro'); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="auto_reminder"><?php _e('Activer les relances', 'devis-pro'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="auto_reminder" name="auto_reminder" value="1" <?php checked($settings['auto_reminder']); ?>>
                                    <?php _e('Envoyer automatiquement des relances pour les devis en attente de paiement', 'devis-pro'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="reminder_days"><?php _e('Délai entre relances', 'devis-pro'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="reminder_days" name="reminder_days" value="<?php echo esc_attr($settings['reminder_days']); ?>" min="1" max="30" class="small-text">
                                <?php _e('jours', 'devis-pro'); ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="max_reminders"><?php _e('Nombre max de relances', 'devis-pro'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="max_reminders" name="max_reminders" value="<?php echo esc_attr($settings['max_reminders']); ?>" min="1" max="10" class="small-text">
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Sécurité -->
            <div class="devis-card">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-shield-alt"></span>
                        <?php _e('Sécurité des formulaires', 'devis-pro'); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <div class="security-status" style="background:#d4edda;border:1px solid #c3e6cb;border-radius:8px;padding:15px;margin-bottom:20px;">
                        <h4 style="margin:0 0 10px;color:#155724;">
                            <span class="dashicons dashicons-yes-alt" style="color:#28a745;"></span>
                            <?php _e('Protections actives', 'devis-pro'); ?>
                        </h4>
                        <ul style="margin:0;padding-left:25px;color:#155724;">
                            <li><strong>Honeypot</strong> - <?php _e('Champs invisibles anti-spam', 'devis-pro'); ?></li>
                            <li><strong>Rate Limiting</strong> - <?php _e('Maximum 5 soumissions/heure par IP', 'devis-pro'); ?></li>
                            <li><strong>Protection Brute Force</strong> - <?php _e('Blocage après 5 tentatives échouées', 'devis-pro'); ?></li>
                            <li><strong>Tokens sécurisés</strong> - <?php _e('Liens d\'accès avec expiration 24h', 'devis-pro'); ?></li>
                            <li><strong>Validation stricte</strong> - <?php _e('Email, téléphone, nom vérifiés', 'devis-pro'); ?></li>
                            <li><strong>Nonce CSRF</strong> - <?php _e('Protection contre les requêtes falsifiées', 'devis-pro'); ?></li>
                        </ul>
                    </div>
                    
                    <h4><?php _e('reCAPTCHA v3 (optionnel)', 'devis-pro'); ?></h4>
                    <p class="description" style="margin-bottom:15px;">
                        <?php _e('Ajoutez une couche de protection supplémentaire avec Google reCAPTCHA v3.', 'devis-pro'); ?>
                        <a href="https://www.google.com/recaptcha/admin" target="_blank"><?php _e('Obtenir les clés', 'devis-pro'); ?></a>
                    </p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="recaptcha_site_key"><?php _e('Clé du site', 'devis-pro'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="recaptcha_site_key" name="recaptcha_site_key" value="<?php echo esc_attr($settings['recaptcha_site_key'] ?? ''); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="recaptcha_secret_key"><?php _e('Clé secrète', 'devis-pro'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="recaptcha_secret_key" name="recaptcha_secret_key" value="<?php echo esc_attr($settings['recaptcha_secret_key'] ?? ''); ?>" class="regular-text">
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Paiement Monetico -->
            <div class="devis-card">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-money-alt"></span>
                        <?php _e('Configuration Monetico CIC', 'devis-pro'); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="monetico_tpe"><?php _e('Numéro TPE', 'devis-pro'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="monetico_tpe" name="monetico_tpe" value="<?php echo esc_attr($settings['monetico_tpe']); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="monetico_cle"><?php _e('Clé de sécurité', 'devis-pro'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="monetico_cle" name="monetico_cle" value="<?php echo esc_attr($settings['monetico_cle']); ?>" class="regular-text">
                                <p class="description"><?php _e('Clé fournie par Monetico/CIC (40 caractères hexadécimaux)', 'devis-pro'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="monetico_societe"><?php _e('Code société', 'devis-pro'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="monetico_societe" name="monetico_societe" value="<?php echo esc_attr($settings['monetico_societe']); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="default_currency"><?php _e('Devise', 'devis-pro'); ?></label>
                            </th>
                            <td>
                                <select id="default_currency" name="default_currency">
                                    <option value="EUR" <?php selected($settings['default_currency'], 'EUR'); ?>>EUR (€)</option>
                                    <option value="USD" <?php selected($settings['default_currency'], 'USD'); ?>>USD ($)</option>
                                    <option value="GBP" <?php selected($settings['default_currency'], 'GBP'); ?>>GBP (£)</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Statuts -->
            <div class="devis-card">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-tag"></span>
                        <?php _e('Statuts des devis', 'devis-pro'); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <table class="status-table status-table-editable" id="status-table">
                        <thead>
                            <tr>
                                <th style="width:60px;"><?php _e('ID', 'devis-pro'); ?></th>
                                <th><?php _e('Label', 'devis-pro'); ?></th>
                                <th style="width:120px;"><?php _e('Couleur', 'devis-pro'); ?></th>
                                <th style="width:80px;"><?php _e('Actions', 'devis-pro'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="status-list">
                            <?php 
                            // Compter les devis par statut
                            $db = new Devis_Pro_DB();
                            $status_counts = array();
                            foreach ($settings['statuses'] as $key => $status) {
                                $status_counts[$key] = $db->count_devis(array('status' => $key));
                            }
                            ?>
                            <?php foreach ($settings['statuses'] as $key => $status) : ?>
                                <tr data-status-id="<?php echo $key; ?>" data-count="<?php echo $status_counts[$key]; ?>">
                                    <td><code><?php echo $key; ?></code></td>
                                    <td>
                                        <input type="text" name="statuses[<?php echo $key; ?>][label]" value="<?php echo esc_attr($status['label']); ?>" class="regular-text status-label-input">
                                    </td>
                                    <td>
                                        <input type="color" name="statuses[<?php echo $key; ?>][color]" value="<?php echo esc_attr($status['color']); ?>" class="status-color-input">
                                    </td>
                                    <td>
                                        <?php if ($key >= 7) : // Seuls les statuts personnalisés peuvent être supprimés ?>
                                        <button type="button" class="button button-small button-link-delete remove-status" data-count="<?php echo $status_counts[$key]; ?>" title="<?php _e('Supprimer', 'devis-pro'); ?>">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                        <?php else : ?>
                                        <span class="dashicons dashicons-lock" style="color:#999;" title="<?php _e('Statut système (0-6)', 'devis-pro'); ?>"></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="status-actions" style="margin-top:15px;">
                        <button type="button" id="add-status" class="button button-secondary">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            <?php _e('Ajouter un statut', 'devis-pro'); ?>
                        </button>
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-saved"></span>
                            <?php _e('Enregistrer', 'devis-pro'); ?>
                        </button>
                    </div>
                    
                    <p class="description" style="margin-top:15px;">
                        <span class="dashicons dashicons-info" style="color:#0073aa;"></span>
                        <?php _e('Les statuts 0-6 sont des statuts système. Vous pouvez modifier leurs labels et couleurs, mais pas les supprimer. Les statuts personnalisés (à partir de 7) peuvent être supprimés s\'ils ne sont pas utilisés.', 'devis-pro'); ?>
                    </p>
                </div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                var nextStatusId = <?php echo max(array_keys($settings['statuses'])) + 1; ?>;
                
                // Ajouter un statut
                $('#add-status').on('click', function() {
                    var newRow = '<tr data-status-id="' + nextStatusId + '">' +
                        '<td><code>' + nextStatusId + '</code></td>' +
                        '<td><input type="text" name="statuses[' + nextStatusId + '][label]" value="Nouveau statut" class="regular-text status-label-input"></td>' +
                        '<td><input type="color" name="statuses[' + nextStatusId + '][color]" value="#6c757d" class="status-color-input"></td>' +
                        '<td><button type="button" class="button button-small button-link-delete remove-status" title="Supprimer"><span class="dashicons dashicons-trash"></span></button></td>' +
                        '</tr>';
                    $('#status-list').append(newRow);
                    nextStatusId++;
                });
                
                // Supprimer un statut
                $(document).on('click', '.remove-status', function() {
                    var count = parseInt($(this).data('count')) || 0;
                    var $row = $(this).closest('tr');
                    var statusId = $row.data('status-id');
                    
                    if (count > 0) {
                        alert('<?php _e('Ce statut est utilisé par', 'devis-pro'); ?> ' + count + ' <?php _e('devis. Vous ne pouvez pas le supprimer.', 'devis-pro'); ?>');
                        return;
                    }
                    
                    if (confirm('<?php _e('Êtes-vous sûr de vouloir supprimer ce statut ?', 'devis-pro'); ?>')) {
                        $row.remove();
                    }
                });
            });
            </script>

            <!-- Shortcodes -->
            <div class="devis-card">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-shortcode"></span>
                        <?php _e('Shortcodes disponibles', 'devis-pro'); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <table class="shortcode-table">
                        <tr>
                            <th><code>[demande-devis-pro]</code></th>
                            <td><?php _e('Formulaire de demande de devis (sidebar)', 'devis-pro'); ?></td>
                        </tr>
                        <tr>
                            <th><code>[demande-devis-complet]</code></th>
                            <td><?php _e('Formulaire complet pleine page avec autocomplétion', 'devis-pro'); ?></td>
                        </tr>
                        <tr>
                            <th><code>[demande-devis-complet voyage="123"]</code></th>
                            <td><?php _e('Formulaire complet pré-rempli avec un voyage', 'devis-pro'); ?></td>
                        </tr>
                        <tr>
                            <th><code>[paiement-devis-pro]</code></th>
                            <td><?php _e('Page de paiement (utiliser avec ?q=TOKEN)', 'devis-pro'); ?></td>
                        </tr>
                        <tr>
                            <th><code>[espace-client-devis]</code></th>
                            <td><?php _e('Espace client pour consulter ses offres de voyage', 'devis-pro'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <p class="submit">
            <button type="submit" class="button button-primary button-large">
                <?php _e('Enregistrer les réglages', 'devis-pro'); ?>
            </button>
        </p>
    </form>
</div>

