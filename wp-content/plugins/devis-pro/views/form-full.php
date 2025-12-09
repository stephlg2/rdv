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
.devis-full-form {
    display: block !important;
    width: 100% !important;
    max-width: 100% !important;
    margin: 40px 0 !important;
    padding: 0 20px !important;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
    float: none !important;
    flex: none !important;
}

.devis-full-form * {
    box-sizing: border-box;
}

.devis-full-form form {
    display: block !important;
    width: 100% !important;
}

.devis-full-form .form-header {
    text-align: center;
    margin-bottom: 40px;
}

.devis-full-form .form-header h2 {
    font-size: 32px !important;
    color: #333 !important;
    margin: 0 0 10px !important;
    font-weight: 700 !important;
}

.devis-full-form .form-header p {
    color: #666;
    font-size: 16px;
    margin: 0;
}

.devis-full-form .form-section {
    background: #fff;
    border-radius: 16px;
    padding: 30px;
    margin-bottom: 25px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.08);
    border: 1px solid #e5e5e5;
}

.devis-full-form .section-title {
    font-size: 20px !important;
    color: #de5b09 !important;
    margin: 0 0 25px !important;
    padding-bottom: 15px !important;
    border-bottom: 2px solid #de5b09 !important;
    font-weight: 600 !important;
    display: flex;
    align-items: center;
    gap: 10px;
}

.devis-full-form .section-title svg {
    width: 24px;
    height: 24px;
    fill: #de5b09;
}

.devis-full-form .form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.devis-full-form .form-row:last-child {
    margin-bottom: 0;
}

.devis-full-form .form-group {
    display: flex;
    flex-direction: column;
}

.devis-full-form .form-group.full-width {
    grid-column: 1 / -1;
}

.devis-full-form label {
    font-weight: 500;
    color: #333;
    margin-bottom: 8px;
    font-size: 14px;
}

.devis-full-form label .required {
    color: #de5b09;
    margin-left: 2px;
}

.devis-full-form input[type="text"],
.devis-full-form input[type="email"],
.devis-full-form input[type="tel"],
.devis-full-form input[type="number"],
.devis-full-form input[type="date"],
.devis-full-form select,
.devis-full-form textarea {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 15px;
    font-family: inherit;
    transition: all 0.2s ease;
    background: #fff;
}

.devis-full-form input:focus,
.devis-full-form select:focus,
.devis-full-form textarea:focus {
    border-color: #de5b09;
    outline: none;
    box-shadow: 0 0 0 4px rgba(222, 91, 9, 0.1);
}

.devis-full-form select {
    appearance: none;
    background-image: url("data:image/svg+xml;utf8,<svg fill='%23de5b09' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/></svg>");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 20px;
    padding-right: 45px;
    cursor: pointer;
}

.devis-full-form textarea {
    min-height: 120px;
    resize: vertical;
}

/* Autocomplétion voyage */
.devis-full-form .voyage-search-wrapper {
    position: relative;
}

.devis-full-form .voyage-search-input {
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' fill='%23999' viewBox='0 0 24 24'><path d='M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z'/></svg>");
    background-repeat: no-repeat;
    background-position: 14px center;
    background-size: 20px;
}

.devis-full-form .voyage-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 2px solid #de5b09;
    border-top: none;
    border-radius: 0 0 10px 10px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.devis-full-form .voyage-results.active {
    display: block;
}
.page-devis .fusion-column-wrapper {
    display: block !important;
    margin: 0;
}
.devis-full-form .voyage-result-item {
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: background 0.15s;
}

.devis-full-form .voyage-result-item:last-child {
    border-bottom: none;
}

.devis-full-form .voyage-result-item:hover {
    background: #fff5f0;
}

.devis-full-form .voyage-result-item.selected {
    background: #de5b09;
    color: #fff;
}

.devis-full-form .voyage-result-thumb {
    width: 60px;
    height: 40px;
    border-radius: 6px;
    object-fit: cover;
    flex-shrink: 0;
}

.devis-full-form .voyage-result-info {
    flex: 1;
}

.devis-full-form .voyage-result-title {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 2px;
}

.devis-full-form .voyage-result-meta {
    font-size: 12px;
    color: #666;
}

.devis-full-form .voyage-result-item.selected .voyage-result-meta {
    color: rgba(255,255,255,0.8);
}

.devis-full-form .voyage-selected {
    display: none;
    background: #fff5f0;
    border: 2px solid #de5b09;
    border-radius: 10px;
    padding: 12px 16px;
    margin-top: 10px;
    align-items: center;
    gap: 12px;
}

.devis-full-form .voyage-selected.active {
    display: flex;
}

.devis-full-form .voyage-selected-info {
    flex: 1;
}

.devis-full-form .voyage-selected-title {
    font-weight: 600;
    color: #333;
}

.devis-full-form .voyage-selected-remove {
    background: none;
    border: none;
    color: #de5b09;
    cursor: pointer;
    padding: 5px;
    font-size: 20px;
    line-height: 1;
}

.devis-full-form .voyage-selected-remove:hover {
    color: #c44d07;
}

/* Voyageur(s) */
.devis-full-form .participants-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.devis-full-form .participant-item {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
}

.devis-full-form .participant-item label {
    display: block;
    margin-bottom: 10px;
    font-size: 13px;
}

.devis-full-form .participant-item input {
    text-align: center;
    font-size: 18px;
    font-weight: 600;
    padding: 10px;
}

/* Civilité */
.devis-full-form .civilite-options {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.devis-full-form .civilite-option {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.devis-full-form .civilite-option input {
    width: 18px;
    height: 18px;
    accent-color: #de5b09;
    cursor: pointer;
}

/* Submit */
.devis-full-form .form-submit {
    text-align: center;
    margin-top: 30px;
}

.devis-full-form .submit-btn {
    background: linear-gradient(135deg, #de5b09 0%, #c44d07 100%);
    color: #fff;
    border: none;
    padding: 18px 60px;
    font-size: 18px;
    font-weight: 600;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(222, 91, 9, 0.3);
}

.devis-full-form .submit-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(222, 91, 9, 0.4);
}

.devis-full-form .form-note {
    text-align: center;
    margin-top: 15px;
    font-size: 13px;
    color: #666;
}

.devis-full-form .error-message {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 25px;
    text-align: center;
}

/* Loading */
.devis-full-form .loading-spinner {
    display: none;
    text-align: center;
    padding: 20px;
    color: #666;
}

.devis-full-form .loading-spinner.active {
    display: block;
}

@media (max-width: 768px) {
    .devis-full-form .form-row {
        grid-template-columns: 1fr;
    }
    
    .devis-full-form .participants-grid {
        grid-template-columns: 1fr;
    }
    
    .devis-full-form .form-section {
        padding: 20px;
    }
}
</style>

<div class="devis-full-form" id="<?php echo $form_id; ?>">
    <div class="form-header">
        <h2><?php _e('Votre aventure en Asie <span style="color:#de5b09;">commence ici !</span>', 'devis-pro'); ?></h2>
        <p><?php _e('Complétez ce formulaire et recevez un devis entièrement personnalisé <strong>dans les 48 heures.</strong>', 'devis-pro'); ?></p>
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
                    <label for="voyage-search"><?php _e('Quel voyage vous intéresse ?', 'devis-pro'); ?></label>
                    <div class="voyage-search-wrapper">
                        <input type="text" 
                               id="voyage-search" 
                               class="voyage-search-input" 
                               placeholder="<?php _e('Recherchez un voyage, une destination...', 'devis-pro'); ?>"
                               autocomplete="off">
                        <div class="voyage-results" id="voyage-results"></div>
                    </div>
                    <div class="voyage-selected" id="voyage-selected">
                        <img src="" alt="" class="voyage-result-thumb" id="voyage-selected-thumb">
                        <div class="voyage-selected-info">
                            <div class="voyage-selected-title" id="voyage-selected-title"></div>
                        </div>
                        <button type="button" class="voyage-selected-remove" id="voyage-remove">&times;</button>
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
                    <input type="tel" id="tel" name="tel" required placeholder="06 12 34 56 78">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="cp"><?php _e('Code postal', 'devis-pro'); ?></label>
                    <input type="text" id="cp" name="cp">
                </div>
                <div class="form-group">
                    <label for="ville"><?php _e('Ville', 'devis-pro'); ?></label>
                    <input type="text" id="ville" name="ville">
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
                <?php _e('Réponse sous 48h · Sans engagement · Devis gratuit', 'devis-pro'); ?>
            </p>
        </div>
    </form>
</div>

<script>
(function() {
    var formWrapper = document.getElementById('<?php echo $form_id; ?>');
    var searchInput = document.getElementById('voyage-search');
    var resultsContainer = document.getElementById('voyage-results');
    var selectedContainer = document.getElementById('voyage-selected');
    var voyageIdInput = document.getElementById('voyage-id');
    var removeBtn = document.getElementById('voyage-remove');
    var searchTimeout;

    // Recherche avec debounce
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        var query = this.value.trim();
        
        if (query.length < 2) {
            resultsContainer.classList.remove('active');
            return;
        }

        searchTimeout = setTimeout(function() {
            fetchVoyages(query);
        }, 300);
    });

    // Fermer les résultats au clic extérieur
    document.addEventListener('click', function(e) {
        if (!formWrapper.contains(e.target)) {
            resultsContainer.classList.remove('active');
        }
    });

    // Focus sur l'input
    searchInput.addEventListener('focus', function() {
        if (this.value.length >= 2) {
            fetchVoyages(this.value);
        }
    });

    // Supprimer la sélection
    removeBtn.addEventListener('click', function() {
        voyageIdInput.value = '';
        selectedContainer.classList.remove('active');
        searchInput.value = '';
        searchInput.style.display = 'block';
    });

    // Fetch voyages via AJAX
    function fetchVoyages(query) {
        resultsContainer.innerHTML = '<div class="loading-spinner active">Recherche...</div>';
        resultsContainer.classList.add('active');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'devis_pro_search_voyages',
                query: query,
                nonce: '<?php echo wp_create_nonce('devis_pro_search'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                renderResults(data.data);
            } else {
                resultsContainer.innerHTML = '<div style="padding:15px;text-align:center;color:#666;">Aucun voyage trouvé</div>';
            }
        })
        .catch(error => {
            resultsContainer.innerHTML = '<div style="padding:15px;text-align:center;color:#dc3545;">Erreur de recherche</div>';
        });
    }

    // Afficher les résultats
    function renderResults(voyages) {
        var html = '';
        voyages.forEach(function(voyage) {
            html += '<div class="voyage-result-item" data-id="' + voyage.id + '" data-title="' + escapeHtml(voyage.title) + '" data-thumb="' + (voyage.thumbnail || '') + '">';
            if (voyage.thumbnail) {
                html += '<img src="' + voyage.thumbnail + '" alt="" class="voyage-result-thumb">';
            }
            html += '<div class="voyage-result-info">';
            html += '<div class="voyage-result-title">' + escapeHtml(voyage.title) + '</div>';
            if (voyage.destination) {
                html += '<div class="voyage-result-meta">' + escapeHtml(voyage.destination) + '</div>';
            }
            html += '</div></div>';
        });
        resultsContainer.innerHTML = html;

        // Event listeners pour les résultats
        resultsContainer.querySelectorAll('.voyage-result-item').forEach(function(item) {
            item.addEventListener('click', function() {
                selectVoyage(this.dataset.id, this.dataset.title, this.dataset.thumb);
            });
        });
    }

    // Sélectionner un voyage
    function selectVoyage(id, title, thumb) {
        voyageIdInput.value = id;
        document.getElementById('voyage-selected-title').textContent = title;
        
        var thumbImg = document.getElementById('voyage-selected-thumb');
        if (thumb) {
            thumbImg.src = thumb;
            thumbImg.style.display = 'block';
        } else {
            thumbImg.style.display = 'none';
        }

        selectedContainer.classList.add('active');
        searchInput.style.display = 'none';
        resultsContainer.classList.remove('active');
    }

    // Escape HTML
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Si voyage pré-sélectionné
    <?php if (!empty($atts['voyage'])) : ?>
    (function() {
        var preselectedId = '<?php echo esc_js($atts['voyage']); ?>';
        // Fetch voyage info
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'devis_pro_get_voyage',
                id: preselectedId,
                nonce: '<?php echo wp_create_nonce('devis_pro_search'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                selectVoyage(data.data.id, data.data.title, data.data.thumbnail);
            }
        });
    })();
    <?php endif; ?>
})();
</script>

<?php if ($has_recaptcha) : ?>
<!-- reCAPTCHA v3 -->
<script src="https://www.google.com/recaptcha/api.js?render=<?php echo esc_attr($recaptcha_site_key); ?>"></script>
<?php endif; ?>

<!-- Script pour soumission AJAX et bouton 3 états -->
<script>
(function() {
    var form = document.getElementById('<?php echo esc_js($form_id); ?>');
    var submitBtn = form.querySelector('.submit-btn');
    var originalBtnText = submitBtn.textContent;
    var isSubmitting = false;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (isSubmitting) {
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
        formData.append('action', 'devis_pro_process_front_form');
        
        if (recaptchaToken) {
            formData.append('recaptcha_token', recaptchaToken);
        }

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Réponse serveur:', data);
            
            if (data.success) {
                // État 3: Succès
                submitBtn.textContent = '✓ DEMANDE ENVOYÉE';
                submitBtn.style.backgroundColor = '#28a745';
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'default';
                
                // Réinitialiser le formulaire après 3 secondes
                setTimeout(function() {
                    form.reset();
                    submitBtn.textContent = originalBtnText;
                    submitBtn.style.backgroundColor = '';
                    submitBtn.style.opacity = '';
                    submitBtn.style.cursor = '';
                    submitBtn.disabled = false;
                    isSubmitting = false;
                    
                    // Réafficher le champ de recherche si un voyage était sélectionné
                    var selectedContainer = document.getElementById('voyage-selected-wrapper');
                    var searchInput = document.getElementById('voyage-search');
                    if (selectedContainer) {
                        selectedContainer.classList.remove('active');
                        searchInput.style.display = 'block';
                        document.getElementById('voyage_id').value = '';
                    }
                }, 3000);
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

