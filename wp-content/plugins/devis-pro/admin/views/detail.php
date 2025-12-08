<?php
/**
 * Vue Détail d'un devis
 */

if (!defined('ABSPATH')) {
    exit;
}

// Récupérer le titre du voyage
$voyage_ids = explode("-;-", $devis->voyage);
$voyage_titles = array();
foreach ($voyage_ids as $id) {
    if (!empty($id) && is_numeric($id)) {
        $title = get_the_title($id);
        if ($title) {
            $voyage_titles[] = $title;
        }
    }
}
$voyage_title = !empty($voyage_titles) ? implode(', ', $voyage_titles) : $devis->voyage;

$current_status = $settings['statuses'][$devis->status] ?? array('label' => 'Inconnu', 'color' => '#6c757d');
?>

<div class="wrap devis-pro-wrap">
    <h1 class="wp-heading-inline">
        <a href="<?php echo admin_url('admin.php?page=devis-pro-list'); ?>" class="back-link">
            <span class="dashicons dashicons-arrow-left-alt"></span>
        </a>
        <?php printf(__('Devis #%d', 'devis-pro'), $devis->id); ?>
        <span class="devis-status" style="background: <?php echo esc_attr($current_status['color']); ?>">
            <?php echo esc_html($current_status['label']); ?>
        </span>
    </h1>
    
    <div class="page-title-actions">
        <a href="#" class="button devis-duplicate" data-id="<?php echo $devis->id; ?>">
            <span class="dashicons dashicons-admin-page"></span>
            <?php _e('Dupliquer', 'devis-pro'); ?>
        </a>
        <?php if ($devis->status == 1 && $devis->montant > 0) : ?>
            <a href="#" class="button devis-reminder" data-id="<?php echo $devis->id; ?>">
                <span class="dashicons dashicons-email-alt"></span>
                <?php _e('Envoyer relance', 'devis-pro'); ?>
            </a>
        <?php endif; ?>
        <a href="<?php echo admin_url('admin.php?page=devis-pro-export&id=' . $devis->id . '&format=pdf'); ?>" class="button">
            <span class="dashicons dashicons-pdf"></span>
            <?php _e('Imprimer', 'devis-pro'); ?>
        </a>
        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=devis-pro-list&action=delete&id=' . $devis->id), 'delete_devis_' . $devis->id); ?>" 
           class="button button-link-delete" onclick="return confirm('<?php _e('Supprimer ce devis ?', 'devis-pro'); ?>');">
            <span class="dashicons dashicons-trash"></span>
            <?php _e('Supprimer', 'devis-pro'); ?>
        </a>
    </div>
    
    <hr class="wp-header-end">

    <?php if (isset($_GET['updated'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Devis mis à jour avec succès.', 'devis-pro'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['created'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Devis créé avec succès.', 'devis-pro'); ?></p>
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
                        <?php _e('Informations client', 'devis-pro'); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <div class="client-info-grid">
                        <div class="info-group">
                            <label><?php _e('Nom complet', 'devis-pro'); ?></label>
                            <p><strong><?php echo esc_html($devis->civ . ' ' . $devis->prenom . ' ' . $devis->nom); ?></strong></p>
                        </div>
                        <div class="info-group">
                            <label><?php _e('Email', 'devis-pro'); ?></label>
                            <p><a href="mailto:<?php echo esc_attr($devis->email); ?>"><?php echo esc_html($devis->email); ?></a></p>
                        </div>
                        <div class="info-group">
                            <label><?php _e('Téléphone', 'devis-pro'); ?></label>
                            <p><a href="tel:<?php echo esc_attr($devis->tel); ?>"><?php echo esc_html($devis->tel); ?></a></p>
                        </div>
                        <div class="info-group">
                            <label><?php _e('Adresse', 'devis-pro'); ?></label>
                            <p><?php echo esc_html($devis->cp . ' ' . $devis->ville); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Détails du voyage -->
            <div class="devis-card">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-airplane"></span>
                        <?php _e('Détails du voyage', 'devis-pro'); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <div class="voyage-info-grid">
                        <div class="info-group full-width">
                            <label><?php _e('Voyage', 'devis-pro'); ?></label>
                            <p><strong><?php echo esc_html($voyage_title); ?></strong></p>
                        </div>
                        <div class="info-group">
                            <label><?php _e('Date de départ', 'devis-pro'); ?></label>
                            <p><?php echo esc_html($devis->depart ?: '-'); ?></p>
                        </div>
                        <div class="info-group">
                            <label><?php _e('Date de retour', 'devis-pro'); ?></label>
                            <p><?php echo esc_html($devis->retour ?: '-'); ?></p>
                        </div>
                        <div class="info-group">
                            <label><?php _e('Durée', 'devis-pro'); ?></label>
                            <p><?php echo esc_html($devis->duree ?: '-'); ?></p>
                        </div>
                        <div class="info-group">
                            <label><?php _e('Vol inclus', 'devis-pro'); ?></label>
                            <p><?php echo esc_html($devis->vol ?: '-'); ?></p>
                        </div>
                        <div class="info-group">
                            <label><?php _e('Participants', 'devis-pro'); ?></label>
                            <p>
                                <?php echo $devis->adulte; ?> <?php _e('adulte(s)', 'devis-pro'); ?>,
                                <?php echo $devis->enfant; ?> <?php _e('enfant(s)', 'devis-pro'); ?>,
                                <?php echo $devis->bebe; ?> <?php _e('bébé(s)', 'devis-pro'); ?>
                            </p>
                        </div>
                        <div class="info-group">
                            <label><?php _e('Budget client', 'devis-pro'); ?></label>
                            <p><?php echo $devis->budget > 0 ? number_format($devis->budget, 0, ',', ' ') . ' €' : '-'; ?></p>
                        </div>
                    </div>
                    
                    <?php if (!empty($devis->message)) : ?>
                        <div class="info-group full-width message-block">
                            <label><?php _e('Message du client', 'devis-pro'); ?></label>
                            <div class="message-content">
                                <?php echo nl2br(esc_html($devis->message)); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notes internes -->
            <div class="devis-card">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-edit"></span>
                        <?php _e('Notes internes', 'devis-pro'); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <div id="notes-container">
                        <?php if (!empty($notes)) : ?>
                            <?php foreach ($notes as $note) : ?>
                                <div class="note-item" data-id="<?php echo $note->id; ?>">
                                    <div class="note-header">
                                        <span class="note-author"><?php echo esc_html($note->author ?: 'Système'); ?></span>
                                        <span class="note-date"><?php echo date('d/m/Y H:i', strtotime($note->created_at)); ?></span>
                                        <button type="button" class="note-delete" data-id="<?php echo $note->id; ?>">
                                            <span class="dashicons dashicons-no-alt"></span>
                                        </button>
                                    </div>
                                    <div class="note-content"><?php echo nl2br(esc_html($note->content)); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p class="no-notes"><?php _e('Aucune note pour le moment.', 'devis-pro'); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <form id="add-note-form" class="add-note-form">
                        <input type="hidden" name="devis_id" value="<?php echo $devis->id; ?>">
                        <textarea name="content" placeholder="<?php _e('Ajouter une note...', 'devis-pro'); ?>" rows="3"></textarea>
                        <button type="submit" class="button button-primary">
                            <?php _e('Ajouter la note', 'devis-pro'); ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Historique -->
            <div class="devis-card">
                <div class="card-header">
                    <h2>
                        <span class="dashicons dashicons-backup"></span>
                        <?php _e('Historique', 'devis-pro'); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($history)) : ?>
                        <ul class="history-list">
                            <?php foreach ($history as $entry) : ?>
                                <li class="history-item">
                                    <span class="history-icon">
                                        <?php
                                        $icon = 'admin-generic';
                                        switch ($entry->action) {
                                            case 'creation': $icon = 'plus-alt'; break;
                                            case 'status_change': $icon = 'update'; break;
                                            case 'amount_change': $icon = 'money-alt'; break;
                                            case 'reminder': $icon = 'email-alt'; break;
                                            case 'auto_reminder': $icon = 'clock'; break;
                                        }
                                        ?>
                                        <span class="dashicons dashicons-<?php echo $icon; ?>"></span>
                                    </span>
                                    <div class="history-content">
                                        <p><?php echo esc_html($entry->description); ?></p>
                                        <span class="history-meta">
                                            <?php echo date('d/m/Y H:i', strtotime($entry->created_at)); ?>
                                            <?php if ($entry->author) : ?>
                                                - <?php echo esc_html($entry->author); ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="no-data"><?php _e('Aucun historique.', 'devis-pro'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="devis-sidebar">
            <!-- Actions et montant -->
            <div class="devis-card card-primary">
                <div class="card-header">
                    <h2><?php _e('Devis', 'devis-pro'); ?></h2>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo admin_url('admin.php'); ?>">
                        <?php wp_nonce_field('devis_pro_update_' . $devis->id); ?>
                        <input type="hidden" name="devis_pro_update" value="1">
                        <input type="hidden" name="devis_id" value="<?php echo $devis->id; ?>">
                        
                        <div class="form-group">
                            <label for="montant"><?php _e('Montant du devis', 'devis-pro'); ?></label>
                            <div class="input-group">
                                <input type="number" id="montant" name="montant" value="<?php echo esc_attr($devis->montant); ?>" step="0.01" min="0">
                                <span class="input-suffix">€</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="status"><?php _e('Statut', 'devis-pro'); ?></label>
                            <select id="status" name="status">
                                <?php foreach ($settings['statuses'] as $key => $status) : ?>
                                    <option value="<?php echo $key; ?>" <?php selected($devis->status, $key); ?>>
                                        <?php echo esc_html($status['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group send-email-option" id="send-email-group" style="display:none;">
                            <label class="checkbox-label">
                                <input type="checkbox" name="send_status_email" value="1" checked>
                                <span class="dashicons dashicons-email-alt"></span>
                                <?php _e('Envoyer un email au client', 'devis-pro'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label for="langue"><?php _e('Langue', 'devis-pro'); ?></label>
                            <select id="langue" name="langue">
                                <option value="fr" <?php selected($devis->langue, 'fr'); ?>><?php _e('Français', 'devis-pro'); ?></option>
                                <option value="en" <?php selected($devis->langue, 'en'); ?>><?php _e('Anglais', 'devis-pro'); ?></option>
                            </select>
                        </div>
                        
                        <button type="submit" class="button button-primary button-large button-block">
                            <?php _e('Mettre à jour', 'devis-pro'); ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Lien de paiement -->
            <?php if ($devis->token && $devis->montant > 0) : ?>
                <div class="devis-card card-success">
                    <div class="card-header">
                        <h2>
                            <span class="dashicons dashicons-admin-links" style="color:#fff;"></span>
                            <?php _e('Lien de paiement', 'devis-pro'); ?>
                        </h2>
                    </div>
                    <div class="card-body">
                        <p class="payment-amount">
                            <strong><?php echo number_format($devis->montant, 2, ',', ' '); ?> €</strong>
                        </p>
                        
                        <div class="payment-url">
                            <input type="text" id="payment-url" value="<?php echo esc_url(home_url('/paiement/?q=' . $devis->token)); ?>" readonly>
                            <button type="button" id="copy-url" class="button" title="<?php _e('Copier', 'devis-pro'); ?>">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                        </div>
                        
                        <div class="form-group" style="margin-top:15px;">
                            <label for="custom-message" style="display:block;margin-bottom:5px;font-weight:500;">
                                <span class="dashicons dashicons-edit" style="font-size:14px;vertical-align:middle;"></span>
                                <?php _e('Message personnalisé (optionnel)', 'devis-pro'); ?>
                            </label>
                            <textarea id="custom-message" rows="4" style="width:100%;border-radius:6px;border:1px solid #ddd;padding:10px;font-size:13px;" placeholder="<?php _e('Ajoutez un message personnalisé qui sera inclus dans l\'email...', 'devis-pro'); ?>"></textarea>
                        </div>
                        
                        <div class="payment-actions">
                            <a href="#" class="button button-primary send-payment-link" data-id="<?php echo $devis->id; ?>" style="display:inline-flex;align-items:center;gap:5px;">
                                <span class="dashicons dashicons-email" style="font-size:16px;width:16px;height:16px;"></span>
                                <?php _e('Envoyer par email', 'devis-pro'); ?>
                            </a>
                        </div>
                        
                        <?php if ($devis->reminders_count > 0) : ?>
                            <p class="reminder-info">
                                <span class="dashicons dashicons-warning"></span>
                                <?php printf(
                                    _n('%d relance envoyée', '%d relances envoyées', $devis->reminders_count, 'devis-pro'),
                                    $devis->reminders_count
                                ); ?>
                                <?php if ($devis->last_reminder) : ?>
                                    <br><small><?php _e('Dernière :', 'devis-pro'); ?> <?php echo date('d/m/Y H:i', strtotime($devis->last_reminder)); ?></small>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Infos demande -->
            <div class="devis-card">
                <div class="card-header">
                    <h2><?php _e('Informations', 'devis-pro'); ?></h2>
                </div>
                <div class="card-body">
                    <ul class="info-list">
                        <li>
                            <span class="label"><?php _e('Date de demande', 'devis-pro'); ?></span>
                            <span class="value"><?php echo date('d/m/Y H:i', strtotime($devis->demande)); ?></span>
                        </li>
                        <li>
                            <span class="label"><?php _e('Référence paiement', 'devis-pro'); ?></span>
                            <span class="value"><?php echo esc_html($devis->mac ?: '-'); ?></span>
                        </li>
                        <?php if ($devis->created_at) : ?>
                            <li>
                                <span class="label"><?php _e('Créé le', 'devis-pro'); ?></span>
                                <span class="value"><?php echo date('d/m/Y H:i', strtotime($devis->created_at)); ?></span>
                            </li>
                        <?php endif; ?>
                        <?php if ($devis->updated_at) : ?>
                            <li>
                                <span class="label"><?php _e('Modifié le', 'devis-pro'); ?></span>
                                <span class="value"><?php echo date('d/m/Y H:i', strtotime($devis->updated_at)); ?></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                    
                    <?php if ($devis->status == 4 && $devis->montant > 0 && $devis->token) : ?>
                        <div class="invoice-download" style="margin-top:15px;padding-top:15px;border-top:1px solid #eee;">
                            <a href="<?php echo home_url('/?facture=1&token=' . $devis->token); ?>" target="_blank" class="button" style="width:100%;text-align:center;">
                                <span class="dashicons dashicons-pdf" style="vertical-align:middle;"></span>
                                <?php _e('Télécharger la facture', 'devis-pro'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion de l'option d'envoi d'email selon le statut
    const statusSelect = document.getElementById('status');
    const sendEmailGroup = document.getElementById('send-email-group');
    const currentStatus = <?php echo $devis->status; ?>;
    
    // Statuts qui peuvent envoyer un email : 2 (Accepté), 4 (Payé), 5 (Annulé)
    const emailStatuses = [2, 4, 5];
    
    function updateEmailOption() {
        const newStatus = parseInt(statusSelect.value);
        // Afficher l'option si on change vers un statut avec email ET que c'est différent du statut actuel
        if (emailStatuses.includes(newStatus) && newStatus !== currentStatus) {
            sendEmailGroup.style.display = 'block';
        } else {
            sendEmailGroup.style.display = 'none';
        }
    }
    
    statusSelect.addEventListener('change', updateEmailOption);
    updateEmailOption(); // Vérifier au chargement
    
    // Copier l'URL de paiement
    const copyBtn = document.getElementById('copy-url');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const input = document.getElementById('payment-url');
            input.select();
            document.execCommand('copy');
            
            const originalHTML = this.innerHTML;
            this.innerHTML = '<span class="dashicons dashicons-yes"></span>';
            setTimeout(() => {
                this.innerHTML = originalHTML;
            }, 2000);
        });
    }
    
    // Ajouter une note
    const noteForm = document.getElementById('add-note-form');
    if (noteForm) {
        noteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const content = this.querySelector('textarea[name="content"]').value.trim();
            if (!content) return;
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = devisProAdmin.strings.loading;
            
            fetch(devisProAdmin.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'devis_pro_add_note',
                    nonce: devisProAdmin.nonce,
                    devis_id: this.querySelector('input[name="devis_id"]').value,
                    content: content
                })
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Ajouter la note';
                
                if (data.success) {
                    // Ajouter la note au DOM
                    const container = document.getElementById('notes-container');
                    const noNotes = container.querySelector('.no-notes');
                    if (noNotes) noNotes.remove();
                    
                    const noteHtml = `
                        <div class="note-item" data-id="${data.data.id}">
                            <div class="note-header">
                                <span class="note-author">${data.data.author}</span>
                                <span class="note-date">${data.data.date}</span>
                                <button type="button" class="note-delete" data-id="${data.data.id}">
                                    <span class="dashicons dashicons-no-alt"></span>
                                </button>
                            </div>
                            <div class="note-content">${data.data.content.replace(/\n/g, '<br>')}</div>
                        </div>
                    `;
                    container.insertAdjacentHTML('afterbegin', noteHtml);
                    
                    // Réinitialiser le formulaire
                    noteForm.querySelector('textarea').value = '';
                    
                    // Attacher l'événement de suppression
                    attachDeleteHandler(container.querySelector('.note-item'));
                } else {
                    alert(data.data || devisProAdmin.strings.error);
                }
            });
        });
    }
    
    // Supprimer une note
    function attachDeleteHandler(noteItem) {
        const deleteBtn = noteItem.querySelector('.note-delete');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function() {
                if (!confirm('Supprimer cette note ?')) return;
                
                fetch(devisProAdmin.ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'devis_pro_delete_note',
                        nonce: devisProAdmin.nonce,
                        note_id: this.dataset.id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        noteItem.remove();
                    }
                });
            });
        }
    }
    
    document.querySelectorAll('.note-item').forEach(attachDeleteHandler);
    
    // Dupliquer
    document.querySelectorAll('.devis-duplicate').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            fetch(devisProAdmin.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'devis_pro_duplicate',
                    nonce: devisProAdmin.nonce,
                    id: this.dataset.id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.data.url;
                }
            });
        });
    });
    
    // Relance
    document.querySelectorAll('.devis-reminder').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Envoyer une relance ?')) return;
            
            fetch(devisProAdmin.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'devis_pro_send_reminder',
                    nonce: devisProAdmin.nonce,
                    id: this.dataset.id
                })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.success ? data.data : (data.data || 'Erreur'));
                if (data.success) location.reload();
            });
        });
    });
    
    // Envoyer le lien de paiement par email
    document.querySelectorAll('.send-payment-link').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const customMessage = document.getElementById('custom-message');
            const messageText = customMessage ? customMessage.value.trim() : '';
            
            let confirmText = 'Envoyer le lien de paiement par email au client ?';
            if (messageText) {
                confirmText += '\n\nMessage personnalisé :\n"' + messageText.substring(0, 100) + (messageText.length > 100 ? '..."' : '"');
            }
            
            if (!confirm(confirmText)) return;
            
            const originalText = this.innerHTML;
            this.innerHTML = '<span class="dashicons dashicons-update spin"></span> Envoi...';
            this.style.pointerEvents = 'none';
            
            fetch(devisProAdmin.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'devis_pro_send_payment_link',
                    nonce: devisProAdmin.nonce,
                    id: this.dataset.id,
                    custom_message: messageText
                })
            })
            .then(response => response.json())
            .then(data => {
                this.innerHTML = originalText;
                this.style.pointerEvents = 'auto';
                
                if (data.success) {
                    alert('✅ ' + data.data);
                    // Vider le message après envoi
                    if (customMessage) customMessage.value = '';
                    location.reload();
                } else {
                    alert('❌ ' + (data.data || 'Erreur lors de l\'envoi'));
                }
            })
            .catch(error => {
                this.innerHTML = originalText;
                this.style.pointerEvents = 'auto';
                alert('❌ Erreur de connexion');
            });
        });
    });
});
</script>

<style>
.spin {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    100% { transform: rotate(360deg); }
}
</style>

