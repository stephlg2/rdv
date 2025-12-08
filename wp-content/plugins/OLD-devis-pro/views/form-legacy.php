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

<div class="devis-form-wrapper" role="form" dir="ltr" lang="fr-FR">
    <?php if (!empty($error)) : ?>
        <div class="error-message"><?php echo esc_html($error); ?></div>
    <?php endif; ?>

    <form method="post" novalidate="novalidate" id="form-devis-pro">
        <input type="hidden" name="voyage" value="<?php echo esc_attr($atts['voyage']); ?>" />
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
    var form = document.getElementById('form-devis-pro');
    if (!form) return;
    form.addEventListener('submit', function(e) {
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
            e.preventDefault();
            var firstError = form.querySelector('.invalid-field');
            if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
    form.querySelectorAll('[required]').forEach(function(field) {
        field.addEventListener('input', function() {
            var errorDiv = field.parentNode.querySelector('.field-error');
            if (field.value.trim() !== '') {
                field.classList.remove('invalid-field');
                if (errorDiv) errorDiv.classList.remove('visible');
            }
        });
    });
})();
</script>
