<?php
/**
 * Formulaire de demande de devis - Version legacy (compatibilité)
 */

if (!defined('ABSPATH')) {
    exit;
}

$error = isset($error) ? $error : '';
?>
<style>
    .devis-form-wrapper form { padding: 15px; }
    .devis-form-wrapper p { margin-top: 10px !important; margin-bottom: 5px !important; }
    .devis-form-wrapper .marginTopForm { margin: 15px 0px; }
    .devis-form-wrapper .titre { font-size: 25px; font-weight: 600; }
    .devis-form-wrapper select, .devis-form-wrapper textarea { width: 100%; padding-left: 10px; margin: 10px auto; }
    .devis-form-wrapper .envoyer { margin: 20px 0; }
    .devis-form-wrapper .envoyer label { margin-bottom: 15px; display: block; }
    .devis-form-wrapper form input, .devis-form-wrapper form select, .devis-form-wrapper form textarea {
        width: 100%; padding: 12px 14px; border-radius: 8px; background: #fff;
        font-size: 16px; font-family: inherit; transition: all 0.25s ease; border: 1px solid #ddd;
    }
    .devis-form-wrapper form input:focus, .devis-form-wrapper form select:focus, .devis-form-wrapper form textarea:focus {
        border-color: #de5b09; outline: none;
    }
    .devis-form-wrapper form select {
        appearance: none;
        background: url("data:image/svg+xml;utf8,<svg fill='%23de5b09' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/></svg>") no-repeat right 12px center/18px 18px;
        padding-right: 40px; cursor: pointer;
    }
    .devis-form-wrapper form input[type="date"] { cursor: pointer; }
    .devis-form-wrapper form input[type="radio"], .devis-form-wrapper form input[type="checkbox"] {
        appearance: none; width: 20px; height: 20px; border: 2px solid #de5b09;
        border-radius: 4px; margin-right: 8px; cursor: pointer; position: relative;
        transition: all 0.25s ease; padding: 11px; top: -5px;
    }
    .devis-form-wrapper form input[type="checkbox"]:checked { background-color: #de5b09; border-color: #de5b09; }
    .devis-form-wrapper form input[type="checkbox"]:checked::after {
        content: "✔"; position: absolute; top: 1px; left: 5px; font-size: 14px; color: #fff;
    }
    .devis-form-wrapper form input[type="radio"] { border-radius: 50%; }
    .devis-form-wrapper form input[type="radio"]:checked { background-color: #de5b09; box-shadow: inset 0 0 0 5px #fff; }
    .devis-form-wrapper form input[type="submit"] {
        background: #de5b09; border: none; color: #fff; font-size: 18px;
        padding: 14px 24px; border-radius: 10px; cursor: pointer; transition: all 0.25s ease;
    }
    .devis-form-wrapper form input[type="submit"]:hover {
        background: #bf4b07; transform: translateY(-2px); box-shadow: 0 6px 14px rgba(222, 91, 9, 0.3);
    }
    .devis-form-wrapper .required-star { color: #de5b09; font-weight: bold; }
    .devis-form-wrapper .required-mention { font-size: 12px; color: #666; margin-top: 15px; }
    .devis-form-wrapper .field-error { color: #e00; font-size: 12px; margin-top: 4px; display: none; }
    .devis-form-wrapper .field-error.visible { display: block; }
    .devis-form-wrapper .invalid-field { border-color: #e00 !important; }
    .devis-form-wrapper .error-message { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
</style>

<?php
$form_unique_id = 'form-devis-pro-' . uniqid();
?>
<div class="devis-form-wrapper" role="form" dir="ltr" lang="fr-FR">
    <?php if (!empty($error)) : ?>
        <div class="error-message"><?php echo esc_html($error); ?></div>
    <?php endif; ?>

    <form novalidate="novalidate" id="<?php echo esc_attr($form_unique_id); ?>" onsubmit="return false;">
        <?php wp_nonce_field('devis_pro_form', 'devis_pro_nonce'); ?>
        <?php Devis_Pro_Security::render_honeypot(); ?>
        <input type="hidden" name="voyage" value="<?php echo esc_attr($atts['voyage']); ?>" />
        <input type="hidden" name="destination" value="<?php echo esc_attr($atts['destination'] ?? ''); ?>" />
        <input type="hidden" name="page_title" value="<?php echo esc_attr(get_the_title()); ?>" />
        <input type="hidden" value="1" name="send" />

        <p class="titre">Votre voyage</p>
        <div class="formdevis">
            <div class="champ row-date">
                <div>
                    <p>Date de départ (Au plus tôt)<span class="required-star">*</span> :</p>
                    <input type="date" name="date-sejour-depart" value="" required>
                    <div class="field-error">Veuillez remplir ce champ</div>
                </div>
                <div>
                    <p>Date de retour (Au plus tard)<span class="required-star">*</span> :</p>
                    <input type="date" name="date-sejour-retour" value="" required>
                    <div class="field-error">Veuillez remplir ce champ</div>
                </div>
                <div>
                    <p>Durée de votre séjour<span class="required-star">*</span> :</p>
                    <select name="duree-sejour" required>
                        <option value="">Durée de votre séjour</option>
                        <option value="De 7 à 15 jours">De 7 à 15 jours</option>
                        <option value="Plus de 15 jours">Plus de 15 jours</option>
                    </select>
                    <div class="field-error">Veuillez remplir ce champ</div>
                </div>
            </div>

            <div class="champ row-gris">
                <div><input type="text" placeholder="Budget par personne" name="budget-sejour" size="40"></div>
                <div>
                    <select name="vols-inclus" required>
                        <option value="">Vol inclus<span class="required-star">*</span></option>
                        <option value="Oui">Oui</option>
                        <option value="Non">Non</option>
                    </select>
                    <div class="field-error">Veuillez remplir ce champ</div>
                </div>
            </div>
        </div>

        <div class="champ formdevis">
            <div class="champ nbre-participants">
                <p class="titre">Nombre de participants</p>
                <div>
                    <p>Adultes<span class="required-star">*</span></p>
                    <input type="number" name="nbre-adulte" value="1" min="1" max="15" required>
                    <div class="field-error">Veuillez remplir ce champ</div>
                </div>
                <div>
                    <p>Enfants (de 2 à 11 ans)</p>
                    <input type="number" name="nbre-enfants" value="0" min="0" max="15">
                </div>
                <div>
                    <p>Bébés (moins de 2 ans)</p>
                    <input type="number" name="nbre-bebes" value="0" min="0" max="15">
                </div>
            </div>
        </div>

        <div class="champ formdevis">
            <div>
                <p>Décrivez votre projet de voyage :</p>
                <textarea name="message" cols="40" rows="10"></textarea>
            </div>
        </div>

        <div class="champ formdevis form-coordonnees">
            <p class="titre">Vos coordonnées</p>
            <div class="marginTopForm">
                <label><input type="radio" name="civilite" value="Mlle" checked> Mlle</label>
                <label><input type="radio" name="civilite" value="Mme"> Mme</label>
                <label><input type="radio" name="civilite" value="Mr"> Mr</label>
            </div>
            <div class="marginTopForm">
                <input type="text" name="nom" placeholder="Votre nom*" required>
                <div class="field-error">Veuillez remplir ce champ</div>
            </div>
            <div class="marginTopForm">
                <input type="text" name="prenom" placeholder="Prénom*" required>
                <div class="field-error">Veuillez remplir ce champ</div>
            </div>
            <div class="marginTopForm">
                <input type="email" name="email" placeholder="Email*" required>
                <div class="field-error">Veuillez remplir ce champ</div>
            </div>
            <div class="marginTopForm">
                <input type="text" name="cp" placeholder="Code Postal*" required>
                <div class="field-error">Veuillez remplir ce champ</div>
            </div>
            <div class="marginTopForm">
                <input type="text" name="ville" placeholder="Ville*" required>
                <div class="field-error">Veuillez remplir ce champ</div>
            </div>
            <div class="marginTopForm">
                <input type="tel" name="tel" placeholder="Téléphone*" required>
                <div class="field-error">Veuillez remplir ce champ</div>
            </div>
        </div>

        <div class="envoyer">
            <label><input type="checkbox" name="newsletter" value="1"> Abonnement à la newsletter</label>
            <input type="submit" value="Envoyer votre demande de devis">
            <p class="required-mention"><span class="required-star">*</span> Champs obligatoires</p>
        </div>
    </form>
</div>

<script>
(function() {
    // Fonction d'initialisation du formulaire
    function initForm(form) {
        // Éviter la double initialisation
        if (form.dataset.initialized) {
            console.log('Formulaire déjà initialisé');
            return;
        }
        form.dataset.initialized = 'true';
        
        console.log('Initialisation du formulaire:', form.id);
        
        var formWrapper = form.closest('.devis-form-wrapper');
        var submitBtn = form.querySelector('input[type="submit"]');
        var originalBtnValue = submitBtn ? submitBtn.value : '';
        
        // Validation
        function validateForm() {
            var isValid = true;
            form.querySelectorAll('[required]').forEach(function(field) {
                var errorDiv = field.parentNode.querySelector('.field-error');
                var value = field.value.trim();
                if (value === '') {
                    isValid = false;
                    field.classList.add('invalid-field');
                    if (errorDiv) errorDiv.classList.add('visible');
                } else {
                    field.classList.remove('invalid-field');
                    if (errorDiv) errorDiv.classList.remove('visible');
                }
            });
            if (!isValid) {
                var firstError = form.querySelector('.invalid-field');
                if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return isValid;
        }
        
        // Soumission AJAX
        function submitFormAjax() {
            if (!validateForm()) return;
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.value = 'Envoi en cours...';
                submitBtn.style.opacity = '0.7';
            }
            
            var formData = new FormData(form);
            formData.append('action', 'devis_pro_submit_form');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    // Remplacer par le message de succès
                    if (formWrapper) {
                        formWrapper.innerHTML = data.data.html;
                    } else {
                        form.innerHTML = data.data.html;
                    }
                } else {
                    alert(data.data || 'Une erreur est survenue');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.value = originalBtnValue;
                        submitBtn.style.opacity = '1';
                    }
                }
            })
            .catch(function(error) {
                console.error('Erreur:', error);
                alert('Erreur de connexion. Veuillez réessayer.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.value = originalBtnValue;
                    submitBtn.style.opacity = '1';
                }
            });
        }
        
        // Click sur le bouton submit
        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                submitFormAjax();
            });
        }
        
        // Reset erreurs sur saisie
        form.querySelectorAll('[required]').forEach(function(field) {
            field.addEventListener('input', function() {
                var errorDiv = field.parentNode.querySelector('.field-error');
                if (field.value.trim() !== '') {
                    field.classList.remove('invalid-field');
                    if (errorDiv) errorDiv.classList.remove('visible');
                }
            });
        });
    }
    
    // Initialiser le formulaire immédiatement s'il existe
    var form = document.getElementById('<?php echo esc_js($form_unique_id); ?>');
    if (form) {
        initForm(form);
    }
    
    // Observer les changements du DOM pour les formulaires chargés dynamiquement (modals Tripzzy)
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    // Chercher le formulaire dans les nœuds ajoutés
                    var dynamicForm = node.id === '<?php echo esc_js($form_unique_id); ?>' 
                        ? node 
                        : node.querySelector('#<?php echo esc_js($form_unique_id); ?>');
                    
                    if (dynamicForm && !dynamicForm.dataset.initialized) {
                        console.log('Formulaire détecté dynamiquement');
                        setTimeout(function() {
                            initForm(dynamicForm);
                        }, 100);
                    }
                }
            });
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Listener pour les boutons "Demander un devis" de Tripzzy
    document.addEventListener('click', function(e) {
        if (e.target.closest('.tripzzy-enquiry-button')) {
            console.log('Bouton Tripzzy cliqué, attente du formulaire...');
            setTimeout(function() {
                var modalForm = document.getElementById('<?php echo esc_js($form_unique_id); ?>');
                if (modalForm && !modalForm.dataset.initialized) {
                    console.log('Réinitialisation du formulaire dans la modal');
                    initForm(modalForm);
                }
            }, 500);
        }
    });
})();
</script>
