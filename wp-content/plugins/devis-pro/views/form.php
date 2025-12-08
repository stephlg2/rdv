<?php
/**
 * Formulaire de demande de devis (front-end)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les settings pour reCAPTCHA
$settings = get_option('devis_pro_settings');
$recaptcha_site_key = $settings['recaptcha_site_key'] ?? '';
$has_recaptcha = !empty($recaptcha_site_key) && !empty($settings['recaptcha_secret_key'] ?? '');
$form_unique_id = 'devis-pro-form-' . uniqid();
?>

<style>
.devis-pro-form {
    max-width: 800px;
    margin: 0 auto;
    font-family: inherit;
}

.devis-pro-form .form-section {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.devis-pro-form .form-section-title {
    font-size: 20px;
    font-weight: 600;
    color: #333;
    margin: 0 0 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #de5b09;
}

.devis-pro-form .form-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.devis-pro-form .form-row > div {
    flex: 1;
}

.devis-pro-form .form-row-3 > div {
    flex: 1;
}

.devis-pro-form label {
    display: block;
    font-weight: 500;
    margin-bottom: 6px;
    color: #333;
    font-size: 14px;
}

.devis-pro-form .required {
    color: #de5b09;
}

.devis-pro-form input[type="text"],
.devis-pro-form input[type="email"],
.devis-pro-form input[type="tel"],
.devis-pro-form input[type="date"],
.devis-pro-form input[type="number"],
.devis-pro-form select,
.devis-pro-form textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 15px;
    font-family: inherit;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}

.devis-pro-form input:focus,
.devis-pro-form select:focus,
.devis-pro-form textarea:focus {
    border-color: #de5b09;
    outline: none;
    box-shadow: 0 0 0 3px rgba(222, 91, 9, 0.1);
}

.devis-pro-form select {
    appearance: none;
    background: url("data:image/svg+xml;utf8,<svg fill='%23de5b09' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/></svg>") no-repeat right 12px center;
    background-color: #fff;
    padding-right: 40px;
    cursor: pointer;
}

.devis-pro-form textarea {
    resize: vertical;
    min-height: 100px;
}

.devis-pro-form .radio-group {
    display: flex;
    gap: 20px;
    padding: 10px 0;
}

.devis-pro-form .radio-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: normal;
    cursor: pointer;
}

.devis-pro-form .radio-group input[type="radio"] {
    width: 18px;
    height: 18px;
    accent-color: #de5b09;
}

.devis-pro-form .checkbox-group label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: normal;
    cursor: pointer;
}

.devis-pro-form .checkbox-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #de5b09;
}

.devis-pro-form .submit-section {
    text-align: center;
    padding: 20px 0;
}

.devis-pro-form .submit-btn {
    background: linear-gradient(135deg, #de5b09 0%, #c44d07 100%);
    color: #fff;
    border: none;
    padding: 16px 50px;
    font-size: 18px;
    font-weight: 600;
    border-radius: 10px;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}

.devis-pro-form .submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(222, 91, 9, 0.35);
}

.devis-pro-form .required-notice {
    text-align: center;
    color: #666;
    font-size: 13px;
    margin-top: 15px;
}

.devis-pro-form .error-message {
    background: #fff5f5;
    border: 1px solid #dc3545;
    color: #dc3545;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
}

.devis-pro-form .field-error {
    color: #dc3545;
    font-size: 12px;
    margin-top: 5px;
    display: none;
}

.devis-pro-form .field-error.visible {
    display: block;
}

.devis-pro-form input.invalid,
.devis-pro-form select.invalid {
    border-color: #dc3545;
}

@media (max-width: 600px) {
    .devis-pro-form .form-row {
        flex-direction: column;
    }
    
    .devis-pro-form .form-section {
        padding: 20px 15px;
    }
}
</style>

<div class="devis-pro-form">
    <?php if (!empty($error)) : ?>
        <div class="error-message"><?php echo esc_html($error); ?></div>
    <?php endif; ?>

    <form id="<?php echo esc_attr($form_unique_id); ?>" novalidate onsubmit="return false;">
        <?php wp_nonce_field('devis_pro_form', 'devis_pro_nonce'); ?>
        <input type="hidden" name="devis_pro_submit" value="1">
        <input type="hidden" name="voyage" value="<?php echo esc_attr($atts['voyage']); ?>">
        <input type="hidden" name="destination" value="<?php echo esc_attr($atts['destination']); ?>">
        <input type="hidden" name="page_title" value="<?php echo esc_attr(get_the_title()); ?>">
        
        <?php // Honeypot anti-spam ?>
        <?php Devis_Pro_Security::render_honeypot(); ?>

        <!-- Section Voyage -->
        <div class="form-section">
            <h3 class="form-section-title"><?php _e('Votre voyage', 'devis-pro'); ?></h3>
            
            <div class="form-row">
                <div>
                    <label for="depart"><?php _e('Date de départ', 'devis-pro'); ?> <span class="required">*</span></label>
                    <input type="date" id="depart" name="depart" required>
                    <div class="field-error"><?php _e('Ce champ est requis', 'devis-pro'); ?></div>
                </div>
                <div>
                    <label for="retour"><?php _e('Date de retour', 'devis-pro'); ?> <span class="required">*</span></label>
                    <input type="date" id="retour" name="retour" required>
                    <div class="field-error"><?php _e('Ce champ est requis', 'devis-pro'); ?></div>
                </div>
                <div>
                    <label for="duree"><?php _e('Durée du séjour', 'devis-pro'); ?> <span class="required">*</span></label>
                    <select id="duree" name="duree" required>
                        <option value=""><?php _e('Choisir...', 'devis-pro'); ?></option>
                        <option value="De 7 à 15 jours"><?php _e('De 7 à 15 jours', 'devis-pro'); ?></option>
                        <option value="Plus de 15 jours"><?php _e('Plus de 15 jours', 'devis-pro'); ?></option>
                    </select>
                    <div class="field-error"><?php _e('Ce champ est requis', 'devis-pro'); ?></div>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label for="budget"><?php _e('Budget par personne (€)', 'devis-pro'); ?></label>
                    <input type="text" id="budget" name="budget" placeholder="<?php _e('Ex: 2000', 'devis-pro'); ?>">
                </div>
                <div>
                    <label for="vol"><?php _e('Vol inclus', 'devis-pro'); ?> <span class="required">*</span></label>
                    <select id="vol" name="vol" required>
                        <option value=""><?php _e('Choisir...', 'devis-pro'); ?></option>
                        <option value="Oui"><?php _e('Oui', 'devis-pro'); ?></option>
                        <option value="Non"><?php _e('Non', 'devis-pro'); ?></option>
                    </select>
                    <div class="field-error"><?php _e('Ce champ est requis', 'devis-pro'); ?></div>
                </div>
            </div>
        </div>

        <!-- Section Voyageur(s) -->
        <div class="form-section">
            <h3 class="form-section-title"><?php _e('Voyageur(s)', 'devis-pro'); ?></h3>
            
            <div class="form-row form-row-3">
                <div>
                    <label for="adulte"><?php _e('Adultes', 'devis-pro'); ?> <span class="required">*</span></label>
                    <input type="number" id="adulte" name="adulte" value="1" min="1" max="20" required>
                </div>
                <div>
                    <label for="enfant"><?php _e('Enfants (2-11 ans)', 'devis-pro'); ?></label>
                    <input type="number" id="enfant" name="enfant" value="0" min="0" max="20">
                </div>
                <div>
                    <label for="bebe"><?php _e('Bébés (< 2 ans)', 'devis-pro'); ?></label>
                    <input type="number" id="bebe" name="bebe" value="0" min="0" max="10">
                </div>
            </div>
        </div>

        <!-- Section Message -->
        <div class="form-section">
            <h3 class="form-section-title"><?php _e('Votre projet', 'devis-pro'); ?></h3>
            
            <div>
                <label for="message"><?php _e('Décrivez votre projet de voyage', 'devis-pro'); ?></label>
                <textarea id="message" name="message" rows="5" placeholder="<?php _e('Vos envies, vos attentes, vos contraintes...', 'devis-pro'); ?>"></textarea>
            </div>
        </div>

        <!-- Section Coordonnées -->
        <div class="form-section">
            <h3 class="form-section-title"><?php _e('Vos coordonnées', 'devis-pro'); ?></h3>
            
            <div class="radio-group">
                <label><input type="radio" name="civ" value="Mlle" checked> <?php _e('Mlle', 'devis-pro'); ?></label>
                <label><input type="radio" name="civ" value="Mme"> <?php _e('Mme', 'devis-pro'); ?></label>
                <label><input type="radio" name="civ" value="Mr"> <?php _e('Mr', 'devis-pro'); ?></label>
            </div>

            <div class="form-row">
                <div>
                    <label for="prenom"><?php _e('Prénom', 'devis-pro'); ?> <span class="required">*</span></label>
                    <input type="text" id="prenom" name="prenom" required>
                    <div class="field-error"><?php _e('Ce champ est requis', 'devis-pro'); ?></div>
                </div>
                <div>
                    <label for="nom"><?php _e('Nom', 'devis-pro'); ?> <span class="required">*</span></label>
                    <input type="text" id="nom" name="nom" required>
                    <div class="field-error"><?php _e('Ce champ est requis', 'devis-pro'); ?></div>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label for="email"><?php _e('Email', 'devis-pro'); ?> <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required>
                    <div class="field-error"><?php _e('Email valide requis', 'devis-pro'); ?></div>
                </div>
                <div>
                    <label for="tel"><?php _e('Téléphone', 'devis-pro'); ?> <span class="required">*</span></label>
                    <input type="tel" id="tel" name="tel" required>
                    <div class="field-error"><?php _e('Ce champ est requis', 'devis-pro'); ?></div>
                </div>
            </div>

            <div class="form-row">
                <div style="flex: 0 0 120px;">
                    <label for="cp"><?php _e('Code postal', 'devis-pro'); ?> <span class="required">*</span></label>
                    <input type="text" id="cp" name="cp" required>
                    <div class="field-error"><?php _e('Requis', 'devis-pro'); ?></div>
                </div>
                <div>
                    <label for="ville"><?php _e('Ville', 'devis-pro'); ?> <span class="required">*</span></label>
                    <input type="text" id="ville" name="ville" required>
                    <div class="field-error"><?php _e('Ce champ est requis', 'devis-pro'); ?></div>
                </div>
            </div>

            <div class="checkbox-group" style="margin-top: 15px;">
                <label>
                    <input type="checkbox" name="newsletter" value="1">
                    <?php _e('Je souhaite recevoir la newsletter', 'devis-pro'); ?>
                </label>
            </div>
        </div>

        <!-- Submit -->
        <div class="submit-section">
            <button type="submit" class="submit-btn">
                <?php _e('Envoyer ma demande de devis', 'devis-pro'); ?>
            </button>
            <p class="required-notice">
                <span class="required">*</span> <?php _e('Champs obligatoires', 'devis-pro'); ?>
            </p>
        </div>
    </form>
</div>

<script>
(function() {
    var form = document.getElementById('<?php echo esc_js($form_unique_id); ?>');
    if (!form) return;
    
    var formContainer = form.closest('.devis-pro-form');
    var submitBtn = form.querySelector('.submit-btn');
    var originalBtnText = submitBtn ? submitBtn.innerHTML : '';
    
    // Validation du formulaire
    function validateForm() {
        var isValid = true;
        var firstError = null;

        form.querySelectorAll('[required]').forEach(function(field) {
            var error = field.parentNode.querySelector('.field-error');
            var value = field.value.trim();

            if (!value || (field.tagName === 'SELECT' && !value)) {
                isValid = false;
                field.classList.add('invalid');
                if (error) error.classList.add('visible');
                if (!firstError) firstError = field;
            } else {
                field.classList.remove('invalid');
                if (error) error.classList.remove('visible');
            }
        });

        // Validation email
        var emailField = form.querySelector('input[type="email"]');
        if (emailField && emailField.value) {
            var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(emailField.value)) {
                isValid = false;
                emailField.classList.add('invalid');
                var error = emailField.parentNode.querySelector('.field-error');
                if (error) error.classList.add('visible');
                if (!firstError) firstError = emailField;
            }
        }

        if (!isValid && firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstError.focus();
        }
        
        return isValid;
    }
    
    // Soumission AJAX
    function submitFormAjax(recaptchaToken) {
        if (!validateForm()) return;
        
        // Désactiver le bouton
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span style="display:inline-flex;align-items:center;gap:8px;"><svg width="16" height="16" viewBox="0 0 24 24" style="animation:spin 1s linear infinite;"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="32" stroke-linecap="round"/></svg> Envoi en cours...</span>';
        }
        
        var formData = new FormData(form);
        formData.append('action', 'devis_pro_submit_form');
        if (recaptchaToken) {
            formData.append('recaptcha_token', recaptchaToken);
        }
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                // Remplacer le formulaire par le message de succès
                if (formContainer) {
                    formContainer.innerHTML = data.data.html;
                } else {
                    form.innerHTML = data.data.html;
                }
            } else {
                // Afficher l'erreur
                alert(data.data || 'Une erreur est survenue');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            }
        })
        .catch(function(error) {
            console.error('Erreur:', error);
            alert('Erreur de connexion. Veuillez réessayer.');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });
    }
    
    // Gérer la soumission via le bouton
    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            <?php if ($has_recaptcha) : ?>
            // Avec reCAPTCHA
            grecaptcha.ready(function() {
                grecaptcha.execute('<?php echo esc_js($recaptcha_site_key); ?>', {action: 'devis_submit'}).then(function(token) {
                    submitFormAjax(token);
                });
            });
            <?php else : ?>
            // Sans reCAPTCHA
            submitFormAjax(null);
            <?php endif; ?>
        });
    }

    // Reset errors on input
    form.querySelectorAll('[required]').forEach(function(field) {
        field.addEventListener('input', function() {
            var error = this.parentNode.querySelector('.field-error');
            if (this.value.trim()) {
                this.classList.remove('invalid');
                if (error) error.classList.remove('visible');
            }
        });
    });
})();
</script>

<?php if ($has_recaptcha) : ?>
<script src="https://www.google.com/recaptcha/api.js?render=<?php echo esc_attr($recaptcha_site_key); ?>"></script>
<?php endif; ?>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

