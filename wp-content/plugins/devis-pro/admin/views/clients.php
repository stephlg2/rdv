<?php
/**
 * Vue Liste des clients
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap devis-pro-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-groups"></span>
        <?php _e('Clients', 'devis-pro'); ?>
    </h1>
    
    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=devis-pro-clients&action=export'), 'export_clients')); ?>" class="page-title-action" style="display: inline-flex; align-items: center; gap: 5px;">
        <span class="dashicons dashicons-download" style="font-size: 18px; width: 18px; height: 18px; line-height: 1;"></span>
        <?php _e('Exporter en CSV', 'devis-pro'); ?>
    </a>
    
    <hr class="wp-header-end">

    <?php if (isset($_GET['archived'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php printf(__('%d client(s) archivé(s) avec succès.', 'devis-pro'), intval($_GET['archived'])); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['unarchived'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php printf(__('%d client(s) désarchivé(s) avec succès.', 'devis-pro'), intval($_GET['unarchived'])); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php printf(__('%d client(s) supprimé(s) définitivement.', 'devis-pro'), intval($_GET['deleted'])); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!isset($clients_table)): ?>
        <div class="notice notice-error">
            <p><?php _e('Erreur : La table des clients n\'a pas pu être initialisée.', 'devis-pro'); ?></p>
        </div>
    <?php else: ?>
        <?php
        // Vérifier s'il y a des clients
        $total_clients = $clients_table->get_pagination_arg('total_items');
        if ($total_clients === 0):
        ?>
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('Aucun client trouvé', 'devis-pro'); ?></strong><br>
                    <?php _e('Les clients apparaîtront ici une fois que des demandes de devis auront été soumises avec des adresses email valides.', 'devis-pro'); ?>
                </p>
            </div>
        <?php endif; ?>
        
        <?php
        // Debug: vérifier les items
        $items_count = is_array($clients_table->items) ? count($clients_table->items) : 0;
        $has_items = method_exists($clients_table, 'has_items') ? $clients_table->has_items() : !empty($clients_table->items);
        
        if ($total_clients > 0 && (!$has_items || $items_count === 0)) {
            echo '<div class="notice notice-warning"><p>';
            echo sprintf(__('Debug: %d clients trouvés mais aucun item dans la table. Vérifiez les logs.', 'devis-pro'), $total_clients);
            echo '<br>';
            echo 'Items count: ' . $items_count;
            echo '<br>';
            echo 'has_items(): ' . ($has_items ? 'true' : 'false');
            echo '<br>';
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo 'Last SQL: ' . esc_html($GLOBALS['wpdb']->last_query);
                echo '<br>';
                echo 'Last Error: ' . esc_html($GLOBALS['wpdb']->last_error);
                echo '<br>';
                if (!empty($clients_table->items) && is_array($clients_table->items)) {
                    echo 'First item: <pre>' . esc_html(print_r($clients_table->items[0], true)) . '</pre>';
                }
            }
            echo '</p></div>';
        }
        ?>
        
        <form method="get" id="clients-filter" action="<?php echo admin_url('admin.php'); ?>">
            <input type="hidden" name="page" value="devis-pro-clients">
            
            <?php
            // Préserver les paramètres GET pour la recherche et la pagination
            if (isset($_GET['show_archived'])) {
                echo '<input type="hidden" name="show_archived" value="' . esc_attr($_GET['show_archived']) . '">';
            }
            
            // Afficher les vues (filtres)
            $clients_table->views();
            
            $clients_table->search_box(__('Rechercher un client', 'devis-pro'), 'client-search');
            
            // Vérifier que les colonnes sont définies avant l'affichage
            if (empty($clients_table->_column_headers)) {
                $columns = $clients_table->get_columns();
                $hidden = array();
                $sortable = $clients_table->get_sortable_columns();
                $clients_table->_column_headers = array($columns, $hidden, $sortable);
            }
            
            $clients_table->display();
            ?>
        </form>
        
        <!-- Formulaire séparé pour les actions groupées en POST -->
        <form method="post" id="clients-bulk-action-form" action="<?php echo admin_url('admin.php?page=devis-pro-clients'); ?>" style="display:none;">
            <input type="hidden" name="page" value="devis-pro-clients">
            <?php wp_nonce_field('bulk-clients', '_wpnonce'); ?>
            <input type="hidden" name="action" id="bulk-action-input" value="">
            <input type="hidden" name="action2" id="bulk-action2-input" value="">
            <div id="bulk-clients-inputs"></div>
        </form>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(function($) {
    // Attendre que tous les scripts soient chargés, puis remplacer les gestionnaires
    setTimeout(function() {
        // Désactiver tous les gestionnaires existants
        $('#doaction, #doaction2').off('click');
        $('.bulkactions').parents('form').off('submit');
        
        // Ajouter notre gestionnaire avec une priorité élevée
        $(document).on('click', '#doaction, #doaction2', function(e) {
            e.stopImmediatePropagation();
            e.preventDefault();
            
            var button = $(this);
            var action = button.prev('select').val();
            var checked = $('input[name="clients[]"]:checked');
            
            if (action === '-1' || !action) {
                alert('<?php echo esc_js(__('Veuillez sélectionner une action.', 'devis-pro')); ?>');
                return false;
            }
            
            if (checked.length === 0) {
                alert('<?php echo esc_js(__('Veuillez sélectionner au moins un client.', 'devis-pro')); ?>');
                return false;
            }
            
            if (action === 'delete') {
                if (!confirm('<?php echo esc_js(__('Êtes-vous sûr de vouloir supprimer définitivement les clients sélectionnés ? Cette action est irréversible.', 'devis-pro')); ?>')) {
                    return false;
                }
            }
            
            if (action === 'archive') {
                if (!confirm('<?php echo esc_js(__('Êtes-vous sûr de vouloir archiver tous les devis des clients sélectionnés ?', 'devis-pro')); ?>')) {
                    return false;
                }
            }
            
            if (action === 'unarchive') {
                if (!confirm('<?php echo esc_js(__('Êtes-vous sûr de vouloir désarchiver tous les devis des clients sélectionnés ?', 'devis-pro')); ?>')) {
                    return false;
                }
            }
            
            // Copier les checkboxes sélectionnées dans le formulaire POST
            var bulkForm = $('#clients-bulk-action-form');
            var inputsContainer = $('#bulk-clients-inputs');
            inputsContainer.empty();
            
            checked.each(function() {
                inputsContainer.append($('<input>').attr({
                    type: 'hidden',
                    name: 'clients[]',
                    value: $(this).val()
                }));
            });
            
            // Définir l'action
            var isAction2 = button.attr('id') === 'doaction2';
            if (isAction2) {
                $('#bulk-action2-input').val(action);
                $('#bulk-action-input').val('');
            } else {
                $('#bulk-action-input').val(action);
                $('#bulk-action2-input').val('');
            }
            
            // Préserver les paramètres GET
            var urlParams = new URLSearchParams(window.location.search);
            var actionUrl = bulkForm.attr('action');
            if (urlParams.get('show_archived')) {
                actionUrl += (actionUrl.indexOf('?') > -1 ? '&' : '?') + 'show_archived=1';
                bulkForm.attr('action', actionUrl);
            }
            
            // Soumettre le formulaire POST
            bulkForm[0].submit();
        });
    }, 100);
});
</script>

<style>
.devis-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
    color: #fff;
}
.devis-badge.badge-success {
    background-color: #46b450;
}
.devis-badge.badge-warning {
    background-color: #ffb900;
}
.devis-badge.badge-info {
    background-color: #2271b1;
}
</style>

