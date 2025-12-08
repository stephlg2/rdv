<style>
    form {
        padding: 15px;
    }

    .select-arrow {
        height: 48px !important;
        width: 40px !important;
        line-height: 49px !important;
        border: none !important;
        background: none !important;
    }

    p {
        margin-top: 10px !important;
        margin-bottom: 5px !important;
    }

    .marginTopForm {
        margin: 15px 0px;
    }

    .titre {
        font-size: 25px;
        font-weight: 600;
    }

    select,
    textarea {
        width: 100%;
        padding-left: 10px;
        margin: 10px auto;
    }

    .envoyer {
        margin: 20px 0;
    }

    .envoyer label {
        margin-bottom: 15px;
        display: block;
    }

    /* Champs de formulaire  */
    form input,
    form select,
    form textarea {
        width: 100%;
        padding: 12px 14px;
        border-radius: 8px;
        background: #fff;
        font-size: 16px;
        font-family: inherit;
        transition: all 0.25s ease;
    }

    /* Focus state */
    form input:focus,
    form select:focus,
    form textarea:focus {
        border-color: #de5b09;
        outline: none;
    }

    /* Select  */
    form select {
        appearance: none;
        background: url("data:image/svg+xml;utf8,<svg fill='%23de5b09' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/></svg>") no-repeat right 12px center/18px 18px;
        padding-right: 40px;
        cursor: pointer;
    }

    /* Datepicker (input[type=date]) */
    form input[type="date"] {
        cursor: pointer;
    }

    /* Boutons radio et cases à cocher  */
    form input[type="radio"],
    form input[type="checkbox"] {
        appearance: none;
        width: 20px;
        height: 20px;
        border: 2px solid #de5b09;
        border-radius: 4px;
        margin-right: 8px;
        cursor: pointer;
        position: relative;
        transition: all 0.25s ease;
        padding: 11px;
        top: -5px;
        text-align: center;
        position: relative;
    }

    /* Checkbox cochée */
    form input[type="checkbox"]:checked {
        background-color: #de5b09;
        border-color: #de5b09;
    }

    form input[type="checkbox"]:checked::after {
        content: "✔";
        position: absolute;
        top: 1px;
        left: 5px;
        font-size: 14px;
        color: #fff;
    }

    /* Radio bouton rond */
    form input[type="radio"] {
        border-radius: 50%;
    }

    form input[type="radio"]:checked {
        background-color: #de5b09;
        box-shadow: inset 0 0 0 5px #fff;
    }

    /* Bouton submit */
    form input[type="submit"] {
        background: #de5b09;
        border: none;
        color: #fff;
        font-size: 18px;
        padding: 14px 24px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.25s ease;
    }

    form input[type="submit"]:hover {
        background: #bf4b07;
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(222, 91, 9, 0.3);
    }

    /* Étoile obligatoire en orange */
    .required-star {
        color: #de5b09;
        font-weight: bold;
    }

    /* Mention champs obligatoires */
    .required-mention {
        font-size: 12px;
        color: #666;
        margin-top: 15px;
    }

    .required-mention .required-star {
        color: #de5b09;
    }

    /* Message d'erreur de validation */
    .field-error {
        color: #e00;
        font-size: 12px;
        margin-top: 4px;
        display: none;
    }

    .field-error.visible {
        display: block;
    }

    /* Bordure rouge pour champ invalide */
    .invalid-field {
        border-color: #e00 !important;
    }
</style>

<div role="form" dir="ltr" lang="fr-FR">
    <form action="/demande-de-devis" method="post" novalidate="novalidate" id="form-devis">
        <input type="hidden" name="voyage" value="<?php echo $atts['voyage']; ?>" />
        <input type="hidden" value="1" name="send" />

        <p class="titre">Votre voyage</p>
        <div class="devisbloc"></div>
        <div class="formdevis">
            <div class="champ row-date">
                <div>
                    <p>Date de départ (Au plus tôt)<span class="required-star">*</span> :</p>
                    <input type="date" name="date-sejour-depart" value="" aria-required="true" required>
                    <div class="field-error">Veuillez remplir ce champ</div>
                </div>
                <div>
                    <p>Date de retour (Au plus tard)<span class="required-star">*</span> :</p>
                    <input type="date" name="date-sejour-retour" value="" aria-required="true" required>
                    <div class="field-error">Veuillez remplir ce champ</div>
                </div>
                <div>
                    <p>Durée de votre séjour<span class="required-star">*</span> :</p>

                    <select name="duree-sejour" aria-required="true" required>
                        <option value="">Durée de votre séjour</option>
                        <option value="De 7 à 15 jours">De 7 à 15 jours</option>
                        <option value="Plus de 15 jours">Plus de 15 jours</option>
                    </select>
                    <div class="field-error">Veuillez remplir ce champ</div>

                </div>
            </div>

            <div class="champ row-gris">
                <div>
                    <input type="text" placeholder="Budget par personne" name="budget-sejour" size="40">
                </div>
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
                    <p class="txt-adulte">Adultes<span class="required-star">*</span></p>
                    <input type="number" name="nbre-adulte" value="1" min="1" max="15" aria-required="true" required>
                    <div class="field-error">Veuillez remplir ce champ</div>
                </div>
                <div>
                    <p class="txt-adulte">Enfants (de 2 à 11 ans)</p>
                    <input type="number" name="nbre-enfants" value="0" min="0" max="15">
                </div>
                <div>
                    <p class="txt-adulte">Bébés (moins de 2 ans)</p>
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
            <div>
                <p class="titre">Vos coordonnées</p>
                <h3 class="all-required"><?php echo $error; ?></h3>
            </div>
            <div class="marginTopForm">
                <label><input type="radio" name="civilite" value="Mlle" checked="checked"> Mlle</label>
                <label><input type="radio" name="civilite" value="Mme"> Mme</label>
                <label><input type="radio" name="civilite" value="Mr"> Mr</label>
            </div>

            <div class="marginTopForm">
                <input type="text" name="nom" size="40" placeholder="Votre nom*" aria-required="true" required>
                <div class="field-error">Veuillez remplir ce champ</div>
            </div>
            <div class="marginTopForm">
                <input type="text" name="prenom" size="40" placeholder="Prénom*" aria-required="true" required>
                <div class="field-error">Veuillez remplir ce champ</div>
            </div>
            <div class="marginTopForm">
                <input type="email" name="email" size="40" placeholder="Email*" aria-required="true" required>
                <div class="field-error">Veuillez remplir ce champ</div>
            </div>
            <div class="marginTopForm">
                <input type="text" name="cp" size="40" placeholder="Code Postal*" aria-required="true" required>
                <div class="field-error">Veuillez remplir ce champ</div>
            </div>
            <div class="marginTopForm">
                <input type="text" name="ville" size="40" placeholder="Ville*" aria-required="true" required>
                <div class="field-error">Veuillez remplir ce champ</div>
            </div>
            <div class="marginTopForm">
                <input type="tel" name="tel" size="40" placeholder="Téléphone*" aria-required="true" required>
                <div class="field-error">Veuillez remplir ce champ</div>
            </div>
        </div>

        <div class="envoyer">
            <div>
                <label><input type="checkbox" name="newsletter" value="1"> Abonnement à la newsletter</label>
                <input type="submit" value="Envoyer votre demande de devis" class="fusion-button button-flat button-xlarge button-default fusion-button-default button-1 fusion-button-default-span bouton-form form-form-submit button-default">
                <p class="required-mention"><span class="required-star">*</span> Champs obligatoires</p>
            </div>
        </div>
    </form>
</div>

<script>
(function() {
    var form = document.getElementById('form-devis');
    if (!form) return;

    // Validation au submit
    form.addEventListener('submit', function(e) {
        var isValid = true;
        var requiredFields = form.querySelectorAll('[required]');

        requiredFields.forEach(function(field) {
            var errorDiv = field.parentNode.querySelector('.field-error');
            var value = field.value.trim();

            // Pour les selects, vérifier que la valeur n'est pas vide
            if (field.tagName === 'SELECT' && value === '') {
                isValid = false;
                field.classList.add('invalid-field');
                if (errorDiv) errorDiv.classList.add('visible');
            } else if (field.tagName !== 'SELECT' && value === '') {
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
            // Scroll vers le premier champ en erreur
            var firstError = form.querySelector('.invalid-field');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
        }
    });

    // Retirer l'erreur quand l'utilisateur remplit le champ
    form.querySelectorAll('[required]').forEach(function(field) {
        field.addEventListener('input', function() {
            var errorDiv = field.parentNode.querySelector('.field-error');
            if (field.value.trim() !== '') {
                field.classList.remove('invalid-field');
                if (errorDiv) errorDiv.classList.remove('visible');
            }
        });

        field.addEventListener('change', function() {
            var errorDiv = field.parentNode.querySelector('.field-error');
            if (field.value.trim() !== '') {
                field.classList.remove('invalid-field');
                if (errorDiv) errorDiv.classList.remove('visible');
            }
        });
    });
})();
</script>
