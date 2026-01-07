<?php
/**
 * Vue Liste des devis
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap devis-pro-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-clipboard"></span>
        <?php _e('Tous les devis', 'devis-pro'); ?>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=devis-pro-add'); ?>" class="page-title-action">
        <?php _e('Ajouter un devis', 'devis-pro'); ?>
    </a>
    
    <hr class="wp-header-end">

    <?php if (isset($_GET['deleted'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Devis supprimé avec succès.', 'devis-pro'); ?></p>
        </div>
    <?php endif; ?>

    <form method="get">
        <input type="hidden" name="page" value="devis-pro-list">
        
        <?php
        $list_table->views();
        $list_table->search_box(__('Rechercher', 'devis-pro'), 'devis-search');
        $list_table->display();
        ?>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dupliquer un devis
    document.querySelectorAll('.devis-duplicate').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (!confirm(devisProAdmin.strings.confirm_duplicate || 'Dupliquer ce devis ?')) {
                return;
            }
            
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
                } else {
                    alert(data.data || devisProAdmin.strings.error);
                }
            });
        });
    });
    
    // Envoyer une relance
    document.querySelectorAll('.devis-reminder').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Envoyer une relance à ce client ?')) {
                return;
            }
            
            btn.classList.add('loading');
            
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
                btn.classList.remove('loading');
                if (data.success) {
                    alert(data.data);
                } else {
                    alert(data.data || devisProAdmin.strings.error);
                }
            });
        });
    });
});
</script>




