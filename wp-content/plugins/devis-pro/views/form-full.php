<?php
/**
 * Formulaire de demande de devis - Version complète pleine page
 * Avec autocomplétion pour le choix du voyage
 */

if (!defined('ABSPATH')) {
    exit;
}

$error = isset($error) ? $error : '';
$form_id = 'devis-form-full-' . uniqid();

// Récupérer les settings pour reCAPTCHA
$settings = get_option('devis_pro_settings');
$recaptcha_site_key = $settings['recaptcha_site_key'] ?? '';
$has_recaptcha = !empty($recaptcha_site_key) && !empty($settings['recaptcha_secret_key'] ?? '');
?>

<style>
/* Menu déroulant destinations multiples */
.devis-full-form .destinations-tags-wrapper {
    position: relative;
    width: 100%;
    min-height: 50px;
    border: 2px solid #e2e8f0 !important;
    border-radius: 10px !important;
    background: #fff !important;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 0px 12px !important;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    box-sizing: border-box !important;
    outline: none !important;
    margin: 0 !important;
}

/* Supprimer toute bordure sur les éléments enfants */
.devis-full-form .destinations-tags-wrapper * {
    box-sizing: border-box;
    border: none !important;
}

/* Exception pour les tags qui ont leur propre style */
.devis-full-form .destinations-tags-wrapper .destination-tag {
    border: none !important;
}

.devis-full-form .destinations-tags-wrapper:focus-within {
    border-color: #de5b09;
    box-shadow: 0 0 0 4px rgba(222, 91, 9, 0.1);
}

.devis-full-form .destinations-tags-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    flex: 1;
    min-width: 0;
}

.devis-full-form .destinations-tags-wrapper.has-tags .destinations-display-input {
    display: none;
}

.devis-full-form .destinations-tags-wrapper:not(.has-tags) .destinations-tags-container {
    display: none;
}

.devis-full-form .destination-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #de5b09;
    color: #fff;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    line-height: 1.4;
    border: none;
}

.devis-full-form .destination-tag-remove {
    background: rgba(255, 255, 255, 0.3);
    border: none;
    color: #fff;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    line-height: 1;
    padding: 0;
    transition: background 0.2s;
    flex-shrink: 0;
}

.devis-full-form .destination-tag-remove:hover {
    background: rgba(255, 255, 255, 0.5);
}

.devis-full-form .destinations-display-input {
    flex: 1;
    min-width: 200px;
    border: none !important;
    border-width: 0 !important;
    padding: 8px 4px;
    font-size: 15px;
    font-family: inherit;
    background: transparent !important;
    outline: none !important;
    cursor: pointer;
    box-sizing: border-box;
    box-shadow: none !important;
    margin: 0 !important;
}

/* Supprimer toute bordure sur les éléments enfants */
.devis-full-form .destinations-tags-wrapper .destinations-tags-container,
.devis-full-form .destinations-tags-wrapper .destinations-display-input {
    border: none !important;
    border-width: 0 !important;
}

.devis-full-form .destinations-display-input::placeholder {
    color: #999;
}

.devis-full-form .destinations-dropdown {
    position: relative;
    margin-top: 10px;
}

.devis-full-form .destinations-grid {
    display: none;
}

/* Container principal du dropdown actif avec ombre */
.devis-full-form .destinations-dropdown.active {
    /* Ombre portée sur le container principal */
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    border-radius: 10px;
    background: #fff;
    padding: 0;
    margin-top: 5px;
    position: absolute;
    width: 100%;
    z-index: 1000;
    box-sizing: border-box;
}

.devis-full-form .destinations-dropdown.active .destinations-grid {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr); /* 3 colonnes sur PC */
    gap: 12px;
    background: #fff;
    /* Suppression de la bordure orange */
    border: none;
    border-radius: 10px 10px 0 0;
    padding: 20px;
    margin: 0;
    max-height: 400px;
    overflow-y: auto;
    position: relative;
    width: 100%;
    box-sizing: border-box;
}

/* Wrapper "Autre" dans le dropdown actif - masqué par défaut */
.devis-full-form .destinations-dropdown.active .destination-other-wrapper {
    /* Masqué par défaut, le JavaScript gère l'affichage via style.display */
    background: #fff;
    border-radius: 0 0 10px 10px;
    border-top: 1px solid #e2e8f0;
    border-bottom: none;
    border-left: none;
    border-right: none;
    margin: 0;
    position: relative;
    width: 100%;
    box-sizing: border-box;
}

.devis-full-form .destination-checkbox-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.15s;
}

.devis-full-form .destination-checkbox-item:hover {
    background: #fff5f0;
}

.devis-full-form .destination-checkbox-item input[type="checkbox"] {
    width: 20px;
    height: 20px;
    accent-color: #de5b09;
    cursor: pointer;
    flex-shrink: 0;
}

.devis-full-form .destination-checkbox-item span {
    font-size: 14px;
    color: #333;
    user-select: none;
    margin-left: 5px;
}

.devis-full-form .destinations-loading {
    padding: 20px;
    text-align: center;
    color: #666;
}

.devis-full-form .destination-other-wrapper {
    padding: 15px 20px;
    border-top: 1px solid #e2e8f0;
    margin-top: 0;
    background: #fff;
    /* S'assurer que le wrapper est dans le dropdown */
    position: relative;
    width: 100%;
    box-sizing: border-box;
}

.devis-full-form label {
    font-weight: 500;
    color: #333;
    margin-bottom: 8px;
    font-size: 14px;
    display: inline-flex;
    align-items: end;
}

/* Label retiré, le placeholder fait office de label */

.devis-full-form .destination-other-input-wrapper {
    display: flex;
    gap: 8px;
    align-items: center;
}

.devis-full-form .destination-other-input {
    flex: 1;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 15px;
    font-family: inherit;
    transition: all 0.2s ease;
    background: #fff;
}

.devis-full-form .destination-other-ok-btn {
    padding: 12px 24px;
    background: #de5b09;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
    font-family: inherit;
}

.devis-full-form .destination-other-ok-btn:hover {
    background: #c04f08;
}

.devis-full-form .destination-other-ok-btn:active {
    transform: scale(0.98);
}

.devis-full-form .destination-other-input:focus {
    border-color: #de5b09;
    outline: none;
    box-shadow: 0 0 0 4px rgba(222, 91, 9, 0.1);
}

.devis-full-form .destination-other-checkbox {
    grid-column: 1 / -1;
    border-top: 1px solid #e2e8f0;
    padding-top: 15px;
    margin-top: 5px;
}

@media (max-width: 768px) {
    .devis-full-form .destinations-dropdown.active .destinations-grid {
        grid-template-columns: 1fr; /* 1 colonne sur mobile */
    }
}
</style>

<div class="devis-full-form" id="<?php echo $form_id; ?>">
    <div class="form-header">
        <h2><?php _e('Votre aventure en Asie <span style="color:#de5b09;">commence ici !</span>', 'devis-pro'); ?></h2>
        <p><?php _e('Complétez ce formulaire et recevez un devis entièrement personnalisé !', 'devis-pro'); ?></p>
    </div>

    <?php if (!empty($error)) : ?>
        <div class="error-message"><?php echo esc_html($error); ?></div>
    <?php endif; ?>

    <form method="post" id="<?php echo $form_id; ?>-form" novalidate>
        <input type="hidden" name="devis_pro_submit" value="1">
        <input type="hidden" name="voyage" id="voyage-id" value="<?php echo esc_attr($atts['voyage'] ?? ''); ?>">
        <?php wp_nonce_field('devis_pro_form', 'devis_pro_nonce'); ?>
        
        <?php // Honeypot anti-spam ?>
        <?php Devis_Pro_Security::render_honeypot(); ?>

        <!-- Section Voyage -->
        <div class="form-section">
            <h3 class="section-title">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/></svg>
                <?php _e('Votre voyage', 'devis-pro'); ?>
            </h3>

            <div class="form-row">
                <div class="form-group full-width">
                    <label for="destinations-display"><?php _e('Quelle(s) destination(s) vous intéresse(nt) ?', 'devis-pro'); ?> <span class="required">*</span></label>
                    <div class="destinations-tags-wrapper" id="destinations-tags-wrapper" data-required="true">
                        <div class="destinations-tags-container" id="destinations-tags-container"></div>
                        <input type="text" 
                               id="destinations-display" 
                               class="destinations-display-input" 
                               placeholder="<?php _e('Sélectionnez une ou plusieurs destinations...', 'devis-pro'); ?>"
                               readonly>
                    </div>
                    <input type="hidden" id="destinations-values" name="destinations" value="">
                    <input type="hidden" id="destination-other" name="destination_other" value="">
                    <div class="destinations-dropdown" id="destinations-dropdown">
                        <div class="destinations-loading">Chargement des destinations...</div>
                        <div class="destinations-grid" id="destinations-grid"></div>
                        <div class="destination-other-wrapper" id="destination-other-wrapper" style="display:none;">
                            <div class="destination-other-input-wrapper">
                                <input type="text" 
                                       id="destination-other-input" 
                                       class="destination-other-input" 
                                       placeholder="<?php _e('Précisez vos autres destinations (séparées par des virgules)', 'devis-pro'); ?>">
                                <button type="button" class="destination-other-ok-btn" id="destination-other-ok-btn">
                                    <?php _e('OK', 'devis-pro'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="depart"><?php _e('Date de départ souhaitée', 'devis-pro'); ?> <span class="required">*</span></label>
                    <input type="date" id="depart" name="depart" required>
                </div>
                <div class="form-group">
                    <label for="retour"><?php _e('Date de retour souhaitée', 'devis-pro'); ?> <span class="required">*</span></label>
                    <input type="date" id="retour" name="retour" required>
                </div>
                <div class="form-group">
                    <label for="duree"><?php _e('Durée souhaitée', 'devis-pro'); ?> <span class="required">*</span></label>
                    <select id="duree" name="duree" required>
                        <option value=""><?php _e('Sélectionnez...', 'devis-pro'); ?></option>
                        <option value="7-10 jours">7-10 jours</option>
                        <option value="10-15 jours">10-15 jours</option>
                        <option value="15-20 jours">15-20 jours</option>
                        <option value="Plus de 20 jours">Plus de 20 jours</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="budget"><?php _e('Budget par personne (€)', 'devis-pro'); ?></label>
                    <input type="text" id="budget" name="budget" placeholder="<?php _e('Ex: 2500', 'devis-pro'); ?>">
                </div>
                <div class="form-group">
                    <label for="vol"><?php _e('Vol inclus ?', 'devis-pro'); ?> <span class="required">*</span></label>
                    <select id="vol" name="vol" required>
                        <option value=""><?php _e('Sélectionnez...', 'devis-pro'); ?></option>
                        <option value="Oui"><?php _e('Oui, je souhaite les vols', 'devis-pro'); ?></option>
                        <option value="Non"><?php _e('Non, je gère mes vols', 'devis-pro'); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Section Voyageur(s) -->
        <div class="form-section">
            <h3 class="section-title">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                <?php _e('Voyageur(s)', 'devis-pro'); ?>
            </h3>

            <div class="participants-grid">
                <div class="participant-item">
                    <label for="adulte"><?php _e('Adultes', 'devis-pro'); ?> <span class="required">*</span></label>
                    <input type="number" id="adulte" name="adulte" value="2" min="1" max="20" required>
                </div>
                <div class="participant-item">
                    <label for="enfant"><?php _e('Enfants (2-11 ans)', 'devis-pro'); ?></label>
                    <input type="number" id="enfant" name="enfant" value="0" min="0" max="20">
                </div>
                <div class="participant-item">
                    <label for="bebe"><?php _e('Bébés (- 2 ans)', 'devis-pro'); ?></label>
                    <input type="number" id="bebe" name="bebe" value="0" min="0" max="10">
                </div>
            </div>
        </div>

        <!-- Section Message -->
        <div class="form-section">
            <h3 class="section-title">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>
                <?php _e('Votre projet', 'devis-pro'); ?>
            </h3>

            <div class="form-row">
                <div class="form-group full-width">
                    <label for="message"><?php _e('Décrivez votre projet de voyage', 'devis-pro'); ?></label>
                    <textarea id="message" name="message" placeholder="<?php _e('Vos envies, centres d\'intérêt, étapes souhaitées, type d\'hébergement préféré...', 'devis-pro'); ?>"></textarea>
                </div>
            </div>
        </div>

        <!-- Section Coordonnées -->
        <div class="form-section">
            <h3 class="section-title">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                <?php _e('Vos coordonnées', 'devis-pro'); ?>
            </h3>

            <div class="form-row">
                <div class="form-group full-width">
                    <label><?php _e('Civilité', 'devis-pro'); ?></label>
                    <div class="civilite-options">
                        <label class="civilite-option">
                            <input type="radio" name="civ" value="Mme" checked>
                            <?php _e('Mme', 'devis-pro'); ?>
                        </label>
                        <label class="civilite-option">
                            <input type="radio" name="civ" value="M.">
                            <?php _e('M.', 'devis-pro'); ?>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="prenom"><?php _e('Prénom', 'devis-pro'); ?> <span class="required">*</span></label>
                    <input type="text" id="prenom" name="prenom" required>
                </div>
                <div class="form-group">
                    <label for="nom"><?php _e('Nom', 'devis-pro'); ?> <span class="required">*</span></label>
                    <input type="text" id="nom" name="nom" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email"><?php _e('Email', 'devis-pro'); ?> <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="tel"><?php _e('Téléphone', 'devis-pro'); ?> <span class="required">*</span></label>
                    <input type="tel" id="tel" name="tel" required >
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="cp"><?php _e('Code postal', 'devis-pro'); ?> <span class="required">*</span></label>
                    <input type="text" id="cp" name="cp" required>
                </div>
                <div class="form-group">
                    <label for="ville"><?php _e('Ville', 'devis-pro'); ?> <span class="required">*</span></label>
                    <input type="text" id="ville" name="ville" required>
                </div>
            </div>
            
            <!-- Checkbox Newsletter -->
            <div class="newsletter-checkbox-wrapper">
                <label class="newsletter-checkbox-label">
                    <input type="checkbox" id="newsletter" name="newsletter" value="1">
                    <span><?php _e('Je souhaite recevoir la newsletter', 'devis-pro'); ?></span>
                </label>
            </div>
        </div>

        <div class="form-submit">
            <button type="submit" class="submit-btn">
                <?php _e('Envoyer ma demande de devis', 'devis-pro'); ?>
            </button>
            <p class="form-note">
                <?php _e('Sans engagement · Devis gratuit', 'devis-pro'); ?>
            </p>
        </div>
    </form>
</div>

<script>
(function() {
    var formWrapper = document.getElementById('<?php echo $form_id; ?>');
    var displayInput = document.getElementById('destinations-display');
    var hiddenInput = document.getElementById('destinations-values');
    var dropdown = document.getElementById('destinations-dropdown');
    var grid = document.getElementById('destinations-grid');
    var tagsWrapper = document.getElementById('destinations-tags-wrapper');
    var selectedDestinations = [];
    var allDestinations = [];

    // Charger les destinations au chargement de la page
    function loadDestinations() {
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'devis_pro_get_destinations',
                nonce: '<?php echo wp_create_nonce('devis_pro_search'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                allDestinations = data.data;
                renderDestinations();
                var loadingEl = document.querySelector('.destinations-loading');
                if (loadingEl) {
                    loadingEl.style.display = 'none';
                }
                // S'assurer que le dropdown est fermé au chargement
                if (dropdown) {
                    dropdown.classList.remove('active');
                }
                if (grid) {
                    grid.style.display = 'none';
                }
            } else {
                document.querySelector('.destinations-loading').textContent = 'Aucune destination disponible';
            }
        })
        .catch(error => {
            document.querySelector('.destinations-loading').textContent = 'Erreur de chargement';
        });
    }

    // Afficher les destinations dans la grille
    function renderDestinations() {
        var html = '';
        allDestinations.forEach(function(dest) {
            var isChecked = selectedDestinations.some(function(sel) {
                return sel.id === dest.id;
            });
            html += '<label class="destination-checkbox-item">';
            html += '<input type="checkbox" value="' + dest.id + '" data-name="' + escapeHtml(dest.name) + '" ' + (isChecked ? 'checked' : '') + '>';
            html += '<span>' + escapeHtml(dest.name) + '</span>';
            html += '</label>';
        });
        
        // Ajouter la case "Autre"
        var hasOther = selectedDestinations.some(function(sel) {
            return sel.id === 'other';
        });
        html += '<label class="destination-checkbox-item destination-other-checkbox">';
        html += '<input type="checkbox" value="other" id="destination-other-checkbox" ' + (hasOther ? 'checked' : '') + '>';
        html += '<span><?php _e('Autre', 'devis-pro'); ?></span>';
        html += '</label>';
        
        grid.innerHTML = html;

        // Event listeners pour les checkboxes
        grid.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
            checkbox.addEventListener('change', function(e) {
                e.stopPropagation(); // Empêcher la propagation pour éviter la fermeture immédiate
                if (this.value === 'other') {
                    toggleOther(this.checked);
                    // Ne pas fermer si on coche "Autre" car il faut pouvoir saisir le texte
                } else {
                    toggleDestination(this);
                    // Fermer après sélection d'une destination normale
                    closeDropdownAfterSelection();
                }
            });
            
            // Empêcher la fermeture au clic sur le label
            var label = checkbox.closest('label');
            if (label) {
                label.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });
        
        // Event listener pour le champ "Autre"
        var otherInput = document.getElementById('destination-other-input');
        if (otherInput) {
            otherInput.addEventListener('input', function() {
                updateOtherDestination(this.value);
            });
            
            // Valider avec la touche Entrée
            otherInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    validateOtherDestination();
                }
            });
            
            // Fermer le dropdown quand on perd le focus du champ "Autre" (si du texte a été saisi)
            otherInput.addEventListener('blur', function() {
                setTimeout(function() {
                    // Vérifier si le focus n'est pas passé sur un autre élément du dropdown
                    var activeElement = document.activeElement;
                    var isFocusInDropdown = dropdown && dropdown.contains(activeElement);
                    if (!isFocusInDropdown) {
                        closeDropdown();
                    }
                }, 150);
            });
        }
        
        // Event listener pour le bouton OK
        var otherOkBtn = document.getElementById('destination-other-ok-btn');
        if (otherOkBtn) {
            otherOkBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                validateOtherDestination();
            });
        }
    }

    // Toggle une destination
    function toggleDestination(checkbox) {
        var destId = parseInt(checkbox.value);
        var destName = checkbox.dataset.name;

        if (checkbox.checked) {
            selectedDestinations.push({
                id: destId,
                name: destName
            });
        } else {
            selectedDestinations = selectedDestinations.filter(function(dest) {
                return dest.id !== destId;
            });
        }

        updateDisplay();
    }

    // Fonction pour valider la destination "Autre"
    function validateOtherDestination() {
        var otherInput = document.getElementById('destination-other-input');
        var otherHiddenInput = document.getElementById('destination-other');
        var otherCheckbox = document.getElementById('destination-other-checkbox');
        
        if (!otherInput || !otherCheckbox || !otherCheckbox.checked) {
            return;
        }
        
        var otherValue = otherInput.value.trim();
        
        if (!otherValue) {
            // Si le champ est vide, ne rien faire
            return;
        }
        
        // Mettre à jour le champ caché
        if (otherHiddenInput) {
            otherHiddenInput.value = otherValue;
        }
        
        // Séparer les destinations par des virgules et les ajouter
        var destinations = otherValue.split(',').map(function(dest) {
            return dest.trim();
        }).filter(function(dest) {
            return dest.length > 0;
        });
        
        // Retirer l'ancienne entrée "Autre" si elle existe
        selectedDestinations = selectedDestinations.filter(function(dest) {
            return dest.id !== 'other';
        });
        
        // Ajouter chaque destination "Autre" séparément
        destinations.forEach(function(dest) {
            selectedDestinations.push({
                id: 'other',
                name: dest
            });
        });
        
        // Vider le champ après validation
        otherInput.value = '';
        
        // Mettre à jour l'affichage et fermer le dropdown
        updateDisplay();
        closeDropdown();
    }

    // Toggle la case "Autre"
    function toggleOther(checked) {
        var otherWrapper = document.getElementById('destination-other-wrapper');
        var otherInput = document.getElementById('destination-other-input');
        var otherHiddenInput = document.getElementById('destination-other');
        
        if (checked) {
            otherWrapper.style.display = 'block';
            // Ajouter "Autre" aux destinations sélectionnées
            var otherValue = otherInput ? otherInput.value.trim() : '';
            if (otherValue) {
                selectedDestinations.push({
                    id: 'other',
                    name: otherValue
                });
            } else {
                selectedDestinations.push({
                    id: 'other',
                    name: 'Autre'
                });
            }
            // Focus sur le champ après un court délai pour permettre l'affichage
            setTimeout(function() {
                if (otherInput) {
                    otherInput.focus();
                }
            }, 100);
        } else {
            otherWrapper.style.display = 'none';
            if (otherInput) {
                otherInput.value = '';
            }
            if (otherHiddenInput) {
                otherHiddenInput.value = '';
            }
            // Retirer "Autre" des destinations sélectionnées
            selectedDestinations = selectedDestinations.filter(function(dest) {
                return dest.id !== 'other';
            });
            // Fermer le dropdown si on décoche "Autre"
            closeDropdown();
        }
        updateDisplay();
    }
    
    // Mettre à jour la destination "Autre" (en temps réel, sans validation)
    function updateOtherDestination(value) {
        var otherHiddenInput = document.getElementById('destination-other');
        if (otherHiddenInput) {
            otherHiddenInput.value = value.trim();
        }
        
        // Ne pas mettre à jour selectedDestinations en temps réel
        // On attend la validation avec le bouton OK ou Entrée
    }

    // Mettre à jour l'affichage avec des tags
    function updateDisplay() {
        var tagsContainer = document.getElementById('destinations-tags-container');
        var tagsWrapper = document.getElementById('destinations-tags-wrapper');
        
        if (!tagsContainer || !tagsWrapper) return;
        
        // Vider le conteneur de tags
        tagsContainer.innerHTML = '';
        
        if (selectedDestinations.length === 0) {
            displayInput.value = '';
            hiddenInput.value = '';
            tagsWrapper.classList.remove('has-tags');
        } else {
            // Créer un tag pour chaque destination sélectionnée
            selectedDestinations.forEach(function(dest) {
                var tag = document.createElement('div');
                tag.className = 'destination-tag';
                tag.dataset.destId = dest.id;
                
                var tagText = document.createElement('span');
                tagText.textContent = dest.name;
                tag.appendChild(tagText);
                
                var removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'destination-tag-remove';
                removeBtn.innerHTML = '×';
                removeBtn.setAttribute('aria-label', 'Supprimer ' + dest.name);
                removeBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    removeDestination(dest.id);
                });
                tag.appendChild(removeBtn);
                
                tagsContainer.appendChild(tag);
            });
            
            // Pour les IDs, on ne garde que les IDs numériques (pas "other")
            var ids = selectedDestinations.filter(function(dest) {
                return dest.id !== 'other';
            }).map(function(dest) {
                return dest.id;
            }).join(',');

            hiddenInput.value = ids;
            tagsWrapper.classList.add('has-tags');
        }
        
        // Retirer l'erreur visuelle si une destination est sélectionnée
        if (selectedDestinations.length > 0) {
            tagsWrapper.style.borderColor = '';
            tagsWrapper.style.boxShadow = '';
        }
    }
    
    // Supprimer une destination
    function removeDestination(destId) {
        // Retirer de la liste
        selectedDestinations = selectedDestinations.filter(function(dest) {
            return dest.id !== destId;
        });
        
        // Décocher la checkbox correspondante
        if (destId === 'other') {
            var otherCheckbox = document.getElementById('destination-other-checkbox');
            if (otherCheckbox) {
                otherCheckbox.checked = false;
            }
            var otherWrapper = document.getElementById('destination-other-wrapper');
            if (otherWrapper) {
                otherWrapper.style.display = 'none';
            }
            var otherInput = document.getElementById('destination-other-input');
            if (otherInput) {
                otherInput.value = '';
            }
            var otherHiddenInput = document.getElementById('destination-other');
            if (otherHiddenInput) {
                otherHiddenInput.value = '';
            }
        } else {
            var checkbox = grid.querySelector('input[type="checkbox"][value="' + destId + '"]');
            if (checkbox) {
                checkbox.checked = false;
            }
        }
        
        updateDisplay();
    }

    // Ouvrir/fermer le dropdown
    var tagsWrapper = document.getElementById('destinations-tags-wrapper');
    if (tagsWrapper && dropdown && grid) {
        tagsWrapper.addEventListener('click', function(e) {
            // Ne pas ouvrir si on clique sur un bouton de suppression
            if (e.target.classList.contains('destination-tag-remove') || e.target.closest('.destination-tag-remove')) {
                return;
            }
            
            e.preventDefault();
            e.stopPropagation();
            
            var isActive = dropdown.classList.contains('active');
            if (isActive) {
                // Si déjà ouvert, fermer
                closeDropdown();
            } else {
                // Sinon, ouvrir
                openDropdown();
            }
        });
    }
    
    // Fonction pour ouvrir le dropdown
    function openDropdown() {
        if (dropdown && grid) {
            dropdown.classList.add('active');
            grid.style.display = 'grid';
        }
    }
    
    // Fonction pour fermer le dropdown
    function closeDropdown() {
        if (dropdown) {
            dropdown.classList.remove('active');
        }
        if (grid) {
            grid.style.display = 'none';
        }
    }

    // Fermer le dropdown au clic extérieur
    if (dropdown && tagsWrapper) {
        document.addEventListener('click', function(e) {
            // Vérifier si le clic est en dehors du dropdown et du wrapper de tags
            var clickedInsideDropdown = dropdown.contains(e.target);
            var clickedOnTagsWrapper = tagsWrapper.contains(e.target);
            
            // Ne pas fermer si on clique sur un élément du dropdown ou du wrapper de tags
            if (!clickedInsideDropdown && !clickedOnTagsWrapper) {
                closeDropdown();
            }
        });
    }
    
    // Fermer le dropdown après sélection (sauf pour "Autre")
    function closeDropdownAfterSelection() {
        setTimeout(function() {
            // Ne fermer que si on n'est pas en train de taper dans le champ "Autre"
            var otherInput = document.getElementById('destination-other-input');
            var isOtherInputFocused = otherInput && document.activeElement === otherInput;
            if (!isOtherInputFocused) {
                closeDropdown();
            }
        }, 200);
    }

    // Escape HTML
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Charger les destinations au chargement
    loadDestinations();
    
    // S'assurer que le dropdown est fermé au démarrage
    if (dropdown) {
        dropdown.classList.remove('active');
    }
    if (grid) {
        grid.style.display = 'none';
    }
    
    // Exposer selectedDestinations pour la réinitialisation
    window.selectedDestinations = selectedDestinations;
})();
</script>

<?php if ($has_recaptcha) : ?>
<!-- reCAPTCHA v3 -->
<script src="https://www.google.com/recaptcha/api.js?render=<?php echo esc_attr($recaptcha_site_key); ?>"></script>
<?php endif; ?>

<!-- Script pour soumission AJAX et bouton 3 états -->
<script>
(function() {
    var form = document.getElementById('<?php echo esc_js($form_id); ?>-form');
    if (!form) {
        console.error('Formulaire non trouvé');
        return;
    }
    var submitBtn = form.querySelector('.submit-btn');
    if (!submitBtn) {
        console.error('Bouton submit non trouvé');
        return;
    }
    var originalBtnText = submitBtn.textContent;
    var isSubmitting = false;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (isSubmitting) {
            return;
        }

        // Valider le champ de destination (obligatoire)
        var destinationsInput = document.getElementById('destinations-values');
        var destinationOtherInput = document.getElementById('destination-other');
        var destinationsWrapper = document.getElementById('destinations-tags-wrapper');
        var hasDestinations = false;
        
        if (destinationsInput && destinationsInput.value.trim() !== '') {
            hasDestinations = true;
        }
        if (destinationOtherInput && destinationOtherInput.value.trim() !== '') {
            hasDestinations = true;
        }
        
        if (!hasDestinations) {
            // Afficher une erreur visuelle
            if (destinationsWrapper) {
                destinationsWrapper.style.borderColor = '#e00';
                destinationsWrapper.style.boxShadow = '0 0 0 4px rgba(238, 0, 0, 0.1)';
            }
            alert('Veuillez sélectionner au moins une destination');
            // Scroll vers le champ de destination
            if (destinationsWrapper) {
                destinationsWrapper.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        } else {
            // Retirer l'erreur visuelle si présente
            if (destinationsWrapper) {
                destinationsWrapper.style.borderColor = '';
                destinationsWrapper.style.boxShadow = '';
            }
        }

        // Valider le formulaire avant envoi
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        console.log('Formulaire soumis');
        isSubmitting = true;

        // État 2: Envoi en cours
        submitBtn.textContent = 'Envoi en cours...';
        submitBtn.style.opacity = '0.7';
        submitBtn.style.cursor = 'not-allowed';
        submitBtn.disabled = true;

        <?php if ($has_recaptcha) : ?>
        grecaptcha.ready(function() {
            grecaptcha.execute('<?php echo esc_js($recaptcha_site_key); ?>', {action: 'devis_submit'}).then(function(token) {
                submitFormAjax(token);
            });
        });
        <?php else : ?>
        submitFormAjax('');
        <?php endif; ?>
    });

    function submitFormAjax(recaptchaToken) {
        var formData = new FormData(form);
        formData.append('action', 'devis_pro_submit_form');
        
        // Envoyer l'URL actuelle pour déterminer si on doit rediriger
        formData.append('current_url', window.location.href);
        
        // S'assurer que les destinations sont bien envoyées
        var destinationsInput = document.getElementById('destinations-values');
        if (destinationsInput && destinationsInput.value) {
            formData.set('destinations', destinationsInput.value);
        }
        
        // S'assurer que la destination "Autre" est bien envoyée
        var destinationOtherInput = document.getElementById('destination-other');
        if (destinationOtherInput && destinationOtherInput.value) {
            formData.set('destination_other', destinationOtherInput.value);
        }
        
        if (recaptchaToken) {
            formData.append('recaptcha_token', recaptchaToken);
        }

        console.log('Envoi du formulaire...');
        // Debug: afficher les données envoyées
        for (var pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Réponse brute:', response);
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Réponse serveur:', data);
            
            if (data.success) {
                // État 3: Succès
                submitBtn.textContent = '✓ DEMANDE ENVOYÉE';
                submitBtn.style.backgroundColor = '#28a745';
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'default';
                
                // Rediriger uniquement si on est sur la page demande-de-devis
                if (data.data && data.data.redirect) {
                    // Redirection pour la page demande-de-devis
                    setTimeout(function() {
                        window.location.href = data.data.redirect;
                    }, 1000);
                } else if (data.data && data.data.html) {
                    // Pour les formulaires des fiches voyage, afficher le message de succès
                    // Le formulaire reste dans la sidebar/modal
                    var formWrapper = form.closest('.devis-full-form') || form.parentElement;
                    if (formWrapper) {
                        formWrapper.innerHTML = data.data.html;
                    }
                }
            } else {
                // Erreur
                alert(data.data?.message || 'Erreur lors de l\'envoi du formulaire');
                resetButton();
            }
        })
        .catch(error => {
            console.error('Erreur AJAX:', error);
            alert('Erreur lors de l\'envoi du formulaire');
            resetButton();
        });
    }

    function resetButton() {
        submitBtn.textContent = originalBtnText;
        submitBtn.style.backgroundColor = '';
        submitBtn.style.opacity = '';
        submitBtn.style.cursor = '';
        submitBtn.disabled = false;
        isSubmitting = false;
    }
})();
</script>