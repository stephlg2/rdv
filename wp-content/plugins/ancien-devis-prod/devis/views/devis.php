<div role="form" dir="ltr" lang="fr-FR">
    <div class="screen-reader-response"></div>
    <form action="/demande-de-devis" method="post" class="wpcf7-form" novalidate="novalidate">

        <input type="hidden" value="1" name="send"/>
        <h2><span class="numberform">1</span> <?php echo __('[:fr]Votre voyage[:en]Your journey'); ?></h2>
        <div class="devisbloc"><?php echo do_shortcode("[global_enqproducts_list products-list-disp]"); ?></div>
        <div class="formdevis">
            <div class="row row-date">
                <div class="col-lg-4">
                    <p><?php echo __('[:fr]Date de départ (Au plus tôt) :[:en]Departure date (At the earliest):'); ?></p>
                    <p><span class="wpcf7-form-control-wrap date-sejour-depart"><input type="date" name="date-sejour-depart" value="" class="wpcf7-form-control wpcf7-date wpcf7-validates-as-required wpcf7-validates-as-date" aria-required="true" aria-invalid="false"></span></p></div>
                <div class="col-lg-4">
                    <p><?php echo __('[:fr]Date de retour  (Au plus tard) :[:en]Return date (At the latest):'); ?></p>
                    <p><span class="wpcf7-form-control-wrap date-sejour-retour"><input type="date" name="date-sejour-retour" value="" class="wpcf7-form-control wpcf7-date wpcf7-validates-as-required wpcf7-validates-as-date" aria-required="true" aria-invalid="false"></span></p></div>
                <div class="col-lg-4">
                    <p> <?php echo __('[:fr]Durée de votre séjour :[:en]Duration of your stay:'); ?></p>
                    <p><span class="wpcf7-form-control-wrap duree-sejour"><div class="wpcf7-select-parent">
                            <select name="duree-sejour" class="wpcf7-form-control wpcf7-select wpcf7-validates-as-required" aria-required="true" aria-invalid="false">
                                <option value="Durée de votre séjour"><?php echo __('[:fr]Durée de votre séjour [:en]Duration of your stay'); ?></option>
                                <option value="<?php echo __('[:fr]Moins de 7 jours[:en]Less than 7 days'); ?>"> <?php echo __('[:fr]Moins de 7 jours[:en]Less than 7 days'); ?></option>
                                <option value="<?php echo __('[:fr]De 7 à 15 jours[:en]7 to 15 days'); ?>"><?php echo __('[:fr]De 7 à 15 jours[:en]7 to 15 days'); ?> </option>
                                <option value="<?php echo __('[:fr]Plus de 15 jours[:en]More than 15 days'); ?>"><?php echo __('[:fr]Plus de 15 jours[:en]More than 15 days'); ?></option>
                            </select><div class="select-arrow" style="height: 40px; width: 40px; line-height: 40px;"></div></div></span></p>
                </div>
            </div>

            <div class="row row-gris">
                <div class="col-lg-6">
                    <p><?php echo __('[:fr]Budget par personne :[:en]Budget per person:'); ?></p>
                    <p><span class="form-control-wrap budget-sejour"><input type="text" placeholder="<?php echo __('[:fr]Budget par personne [:en]Budget per person'); ?>" name="budget-sejour" size="40" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false"></span></p></div>

                <div class="col-lg-6">
                    <p><?php echo __('[:fr]Vols Inclus : [:en]Flights Included:
'); ?></p>
                    <p><span class="wpcf7-form-control-wrap vols-inclus"><div class="wpcf7-select-parent">
                                <select name="vols-inclus" class="wpcf7-form-control wpcf7-select" aria-invalid="false">
                                    <option value="<?php echo __('[:fr]Oui[:en]Yes'); ?>"><?php echo __('[:fr]Oui[:en]Yes'); ?></option>
                                    <option value="<?php echo __('[:fr]Non[:en]No'); ?>"><?php echo __('[:fr]Non[:en]No'); ?></option>
                                </select><div class="select-arrow" style="height: 40px; width: 40px; line-height: 40px;"></div></div></span></p>
                </div>
            </div>
        </div>
        <div class="row formdevis">
            <div class="col-lg-12">

                <div class="row nbre-participants">
                    <div class="col-lg-12"><p><?php echo __('[:fr]Nombre de participants[:en]Number of participants
'); ?></p></div>
                    <div class="col-lg-4">

                        <p class="txt-adulte"><?php echo __('[:fr]Adultes[:en]Adults'); ?></p>
                        <span class="wpcf7-form-control-wrap nbre-adulte"><input type="number" name="nbre-adulte" value="1" class="wpcf7-form-control wpcf7-number wpcf7-validates-as-required wpcf7-validates-as-number nbr-form" min="1" max="15" aria-required="true" aria-invalid="false"></span>

                    </div>
                    <div class="col-lg-4">
                        <p class="txt-adulte"><?php echo __('[:fr]Enfants (de 2 à 11 ans)[:en]Children (2 to 11 years old)'); ?></p>
                        <span class="wpcf7-form-control-wrap nbre-enfants"><input type="number" name="nbre-enfants" value="0" class="wpcf7-form-control wpcf7-number wpcf7-validates-as-required wpcf7-validates-as-number nbr-form" min="0" max="15" aria-required="true" aria-invalid="false"></span>

                    </div>
                    <div class="col-lg-4">
                        <p class="txt-adulte"> <?php echo __('[:fr]Bébés (moins de 2 ans) (de 2 à 11 ans)[:en]Babies (under 2 years old)'); ?></p>
                        <span class="wpcf7-form-control-wrap nbre-bebes"><input type="number" name="nbre-bebes" value="0" class="wpcf7-form-control wpcf7-number wpcf7-validates-as-required wpcf7-validates-as-number nbr-form" min="0" max="15" aria-required="true" aria-invalid="false"></span>

                    </div>
                </div>
            </div>
        </div>
        <div class="row formdevis">
            <div class="col-lg-12">
                <p><?php echo __('[:fr]Décrivez votre projet de voyage :[:en]Describe your travel plan:'); ?></p>
                <p><span class="wpcf7-form-control-wrap message"><textarea name="message" cols="40" rows="10" class="wpcf7-form-control wpcf7-textarea wpcf7-validates-as-required" aria-required="true" aria-invalid="false"></textarea></span></p></div>
        </div>
        <div class="row formdevis form-coordonnees">
            <div class="col-lg-12">
                <h2><span class="numberform">2</span> <?php echo __('[:fr]Vos coordonnées [:en]Your contact details'); ?></h2>
                <h3 class="all-required"><?php echo $error; ?></h3>
            </div>
            <div class="col-lg-12 marginTopForm"><span class="wpcf7-form-control-wrap civilite"><span class="wpcf7-form-control wpcf7-radio"><span class="wpcf7-list-item first"><span class="wpcf7-list-item-label"><?php echo __('[:fr]Mlle[:en]Ms'); ?></span><input type="radio" name="civilite" value="Mlle" checked="checked"></span><span class="wpcf7-list-item"><span class="wpcf7-list-item-label"><?php echo __('[:fr]Mme[:en]Miss'); ?></span><input type="radio" name="civilite"
                                                                                                                                                                                                                                                                                                                                                                                                                                                                 value="Mme"></span><span
                                class="wpcf7-list-item last"><span class="wpcf7-list-item-label"><?php echo __('[:fr]Mr[:en]Mr'); ?></span><input type="radio" name="civilite" value="Mr"></span></span></span></div>
            <div class="col-lg-4 marginTopForm"><span class="wpcf7-form-control-wrap nom"><input type="text" name="nom" value="" size="40" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false" placeholder="<?php echo __('[:fr]Votre nom[:en]name'); ?>"></span></div>
            <div class="col-lg-4 marginTopForm"><span class="wpcf7-form-control-wrap Prenom"><input type="text" name="prenom" value="" size="40" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false" placeholder="<?php echo __('[:fr]Prénom[:en]First Name'); ?>"></span></div>
            <div class="col-lg-4 marginTopForm"><span class="wpcf7-form-control-wrap email"><input type="email" name="email" value="" size="40" class="wpcf7-form-control wpcf7-text wpcf7-email wpcf7-validates-as-required wpcf7-validates-as-email" aria-required="true" aria-invalid="false" placeholder="Email"></span></div>
            <div class="col-lg-4 marginTopForm"><span class="wpcf7-form-control-wrap CP"><input type="text" name="cp" value="" size="40" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false" placeholder="<?php echo __('[:fr]Code Postal[:en]Postal Code'); ?>"></span></div>
            <div class="col-lg-4 marginTopForm"><span class="wpcf7-form-control-wrap ville"><input type="text" name="ville" value="" size="40" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false" placeholder="<?php echo __('[:fr]Ville[:en]City'); ?>"></span></div>
            <div class="col-lg-4 marginTopForm"><span class="wpcf7-form-control-wrap tel"><input type="tel" name="tel" value="" size="40" class="wpcf7-form-control wpcf7-text wpcf7-tel wpcf7-validates-as-required wpcf7-validates-as-tel" aria-required="true" aria-invalid="false" placeholder="<?php echo __('[:fr]Téléphone[:en]Phone'); ?>"></span></div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <p><span class="wpcf7-form-control-wrap newsletter"><span class="wpcf7-form-control wpcf7-acceptance optional"><span class="wpcf7-list-item"><label><input type="checkbox" name="newsletter" value="1" aria-invalid="false" checked="checked"><span class="wpcf7-list-item-label"><?php echo __('[:fr]Abonnement à la newsletter[:en]Newsletter'); ?></span></label></span></span></span></p>
                <p class="envoyer"><input type="submit" value="<?php echo __('[:fr]Envoyer votre demande de devis[:en]Send your quote request'); ?>" class="wpcf7-form-control wpcf7-submit"></p>
            </div>
        </div>
    </form>
</div>
