<?php
/**
 * Vue Ajouter un devis
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap devis-pro-wrap">
    <h1 class="wp-heading-inline">
        <a href="<?php echo admin_url('admin.php?page=devis-pro-list'); ?>" class="back-link">
            <span class="dashicons dashicons-arrow-left-alt"></span>
        </a>
        <?php _e('Ajouter un devis', 'devis-pro'); ?>
    </h1>
    
    <hr class="wp-header-end">

    <form method="post" action="<?php echo admin_url('admin.php'); ?>" class="devis-add-form">
        <?php wp_nonce_field('devis_pro_add'); ?>
        <input type="hidden" name="devis_pro_add" value="1">

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
                        <div class="form-row">
                            <div class="form-group form-group-small">
                                <label for="civ"><?php _e('Civilité', 'devis-pro'); ?></label>
                                <select id="civ" name="civ">
                                    <option value="Mr">Mr</option>
                                    <option value="Mme">Mme</option>
                                    <option value="Mlle">Mlle</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="prenom"><?php _e('Prénom', 'devis-pro'); ?> *</label>
                                <input type="text" id="prenom" name="prenom" required>
                            </div>
                            <div class="form-group">
                                <label for="nom"><?php _e('Nom', 'devis-pro'); ?> *</label>
                                <input type="text" id="nom" name="nom" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email"><?php _e('Email', 'devis-pro'); ?> *</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="tel"><?php _e('Téléphone', 'devis-pro'); ?> *</label>
                                <input type="tel" id="tel" name="tel" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group form-group-small">
                                <label for="cp"><?php _e('Code postal', 'devis-pro'); ?></label>
                                <input type="text" id="cp" name="cp">
                            </div>
                            <div class="form-group">
                                <label for="ville"><?php _e('Ville', 'devis-pro'); ?></label>
                                <input type="text" id="ville" name="ville">
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
                        <div class="form-row">
                            <div class="form-group form-group-full">
                                <label for="destination"><?php _e('Destination', 'devis-pro'); ?></label>
                                <input type="text" id="destination" name="destination">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group form-group-full">
                                <label for="voyage-search"><?php _e('Voyage', 'devis-pro'); ?></label>
                                <div class="voyage-autocomplete-wrapper">
                                    <input type="text" id="voyage-search" class="voyage-search-input" placeholder="<?php _e('Rechercher un voyage...', 'devis-pro'); ?>" autocomplete="off">
                                    <input type="hidden" id="voyage" name="voyage" value="">
                                    <div id="voyage-suggestions" class="voyage-suggestions"></div>
                                    <div id="voyage-selected" class="voyage-selected-admin" style="display:none;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="depart"><?php _e('Date de départ', 'devis-pro'); ?></label>
                                <input type="date" id="depart" name="depart">
                            </div>
                            <div class="form-group">
                                <label for="retour"><?php _e('Date de retour', 'devis-pro'); ?></label>
                                <input type="date" id="retour" name="retour">
                            </div>
                            <div class="form-group">
                                <label for="duree"><?php _e('Durée', 'devis-pro'); ?></label>
                                <select id="duree" name="duree">
                                    <option value="">--</option>
                                    <option value="De 7 à 15 jours">De 7 à 15 jours</option>
                                    <option value="Plus de 15 jours">Plus de 15 jours</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="adulte"><?php _e('Adultes', 'devis-pro'); ?></label>
                                <input type="number" id="adulte" name="adulte" value="1" min="0">
                            </div>
                            <div class="form-group">
                                <label for="enfant"><?php _e('Enfants', 'devis-pro'); ?></label>
                                <input type="number" id="enfant" name="enfant" value="0" min="0">
                            </div>
                            <div class="form-group">
                                <label for="bebe"><?php _e('Bébés', 'devis-pro'); ?></label>
                                <input type="number" id="bebe" name="bebe" value="0" min="0">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="budget"><?php _e('Budget client', 'devis-pro'); ?></label>
                                <div class="input-group">
                                    <input type="number" id="budget" name="budget" step="0.01" min="0">
                                    <span class="input-suffix">€</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="vol"><?php _e('Vol inclus', 'devis-pro'); ?></label>
                                <select id="vol" name="vol">
                                    <option value="">--</option>
                                    <option value="Oui">Oui</option>
                                    <option value="Non">Non</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group form-group-full">
                                <label for="message"><?php _e('Message', 'devis-pro'); ?></label>
                                <textarea id="message" name="message" rows="5"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="devis-sidebar">
                <div class="devis-card card-primary">
                    <div class="card-header">
                        <h2><?php _e('Devis', 'devis-pro'); ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="montant"><?php _e('Montant', 'devis-pro'); ?></label>
                            <div class="input-group">
                                <input type="number" id="montant" name="montant" step="0.01" min="0" value="0">
                                <span class="input-suffix">€</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="langue"><?php _e('Langue', 'devis-pro'); ?></label>
                            <select id="langue" name="langue">
                                <option value="fr">Français</option>
                                <option value="en">Anglais</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="button button-primary button-large button-block">
                            <?php _e('Créer le devis', 'devis-pro'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.voyage-autocomplete-wrapper {
    position: relative;
}

.voyage-search-input {
    width: 100%;
}

.voyage-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 6px 6px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.voyage-suggestion-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
    transition: background 0.2s;
}

.voyage-suggestion-item:hover {
    background: #f5f5f5;
}

.voyage-suggestion-item:last-child {
    border-bottom: none;
}

.voyage-suggestion-image {
    width: 60px;
    height: 45px;
    object-fit: cover;
    border-radius: 4px;
    flex-shrink: 0;
}

.voyage-suggestion-info {
    flex: 1;
    min-width: 0;
}

.voyage-suggestion-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.voyage-suggestion-meta {
    font-size: 12px;
    color: #666;
}

.voyage-selected-admin {
    margin-top: 10px;
    padding: 10px 12px;
    background: #f0f7ff;
    border: 1px solid #0073aa;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.voyage-selected-admin img {
    width: 50px;
    height: 38px;
    object-fit: cover;
    border-radius: 4px;
}

.voyage-selected-admin .voyage-info {
    flex: 1;
}

.voyage-selected-admin .voyage-title {
    font-weight: 600;
    color: #333;
}

.voyage-selected-admin .voyage-meta {
    font-size: 12px;
    color: #666;
}

.voyage-selected-admin .remove-voyage {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    padding: 5px;
    font-size: 18px;
    line-height: 1;
}

.voyage-selected-admin .remove-voyage:hover {
    color: #a71d2a;
}

.voyage-no-results {
    padding: 15px;
    text-align: center;
    color: #666;
}
</style>

<script>
jQuery(document).ready(function($) {
    var searchTimeout;
    var $searchInput = $('#voyage-search');
    var $hiddenInput = $('#voyage');
    var $suggestions = $('#voyage-suggestions');
    var $selected = $('#voyage-selected');
    
    // Recherche de voyages
    $searchInput.on('input', function() {
        var query = $(this).val().trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            $suggestions.hide().empty();
            return;
        }
        
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: devisProAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'devis_pro_search_voyages',
                    nonce: '<?php echo wp_create_nonce('devis_pro_search'); ?>',
                    query: query
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var html = '';
                        response.data.forEach(function(voyage) {
                            html += '<div class="voyage-suggestion-item" data-id="' + voyage.id + '" data-title="' + voyage.title + '" data-image="' + (voyage.image || '') + '" data-destination="' + (voyage.destination || '') + '" data-duration="' + (voyage.duration || '') + '">';
                            if (voyage.image) {
                                html += '<img src="' + voyage.image + '" class="voyage-suggestion-image" alt="">';
                            }
                            html += '<div class="voyage-suggestion-info">';
                            html += '<div class="voyage-suggestion-title">' + voyage.title + '</div>';
                            html += '<div class="voyage-suggestion-meta">';
                            if (voyage.destination) html += voyage.destination;
                            if (voyage.duration) html += ' • ' + voyage.duration;
                            html += '</div>';
                            html += '</div>';
                            html += '</div>';
                        });
                        $suggestions.html(html).show();
                    } else {
                        $suggestions.html('<div class="voyage-no-results">Aucun voyage trouvé</div>').show();
                    }
                }
            });
        }, 300);
    });
    
    // Sélection d'un voyage
    $(document).on('click', '.voyage-suggestion-item', function() {
        var id = $(this).data('id');
        var title = $(this).data('title');
        var image = $(this).data('image');
        var destination = $(this).data('destination');
        var duration = $(this).data('duration');
        
        $hiddenInput.val(id);
        $searchInput.val('').hide();
        $suggestions.hide().empty();
        
        var html = '';
        if (image) {
            html += '<img src="' + image + '" alt="">';
        }
        html += '<div class="voyage-info">';
        html += '<div class="voyage-title">' + title + '</div>';
        html += '<div class="voyage-meta">';
        if (destination) html += destination;
        if (duration) html += ' • ' + duration;
        html += '</div>';
        html += '</div>';
        html += '<button type="button" class="remove-voyage" title="Supprimer">×</button>';
        
        $selected.html(html).show();
        
        // Remplir la destination si vide
        if (!$('#destination').val() && destination) {
            $('#destination').val(destination);
        }
    });
    
    // Supprimer la sélection
    $(document).on('click', '.remove-voyage', function() {
        $hiddenInput.val('');
        $selected.hide().empty();
        $searchInput.show().focus();
    });
    
    // Fermer les suggestions si clic ailleurs
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.voyage-autocomplete-wrapper').length) {
            $suggestions.hide();
        }
    });
});
</script>

