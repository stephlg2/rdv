<div>
    <div>
        <div>
            <div>

                <h1><?php echo $titre[$langue]; ?></h1>


            </div>


        </div>
    </div>
</div>

<div id="main" role="main" class="clearfix " style="">
    <div class="fusion-row" style="">
        <section id="content" style="width: 100%;">
            <div id="post-47" class="post-47 page type-page status-publish hentry">

                <div class="fusion-row">
                    <div class="col-lg-4" style="border: 1px dashed #ddd; padding: 15px 15px 35px 15px; margin-top: 25px;">
                        <div role="form" dir="ltr" lang="fr-FR">
                            <div class="screen-reader-response"></div>
                            <h2><?php echo $voyage[$langue]; ?></h2>

                            <strong><?php echo $dest[$langue]; ?> :</strong> <?php echo $output; ?><br/><br/>
                            <?php echo $devis[$langue]; ?> : <?php echo $demande->demande; ?><br/>
                            <?php echo $depart[$langue]; ?> : <?php echo $demande->depart; ?><br/>
                            <?php echo $retour[$langue]; ?> : <?php echo $demande->retour; ?><br/>
                            <!-- Durée du séjour : <?php echo $demande->duree; ?>-->
                            <!-- Budget : <?php echo $demande->budget; ?><br/>-->
                            <strong><?php echo $montant[$langue]; ?> :</strong> <?php echo $demande->montant; ?> €
                            <!-- Nombre d'adulte(s) : <?php echo $demande->adulte; ?>-->
                            <!--Nombre d'enfant(s) : <?php echo $demande->enfant; ?>-->
                            <!--  <?php echo $demande->vol; ?>-->
                            <div class="coordonnees">
                                <h2 style="margin-top: 0"><?php echo $coord[$langue]; ?></h2>
                                <strong><?php echo $demande->civ; ?> <?php echo $demande->nom; ?> <?php echo $demande->prenom; ?></strong><br/>
                                <!--  Adresse : <?php echo $demande->cp; ?> <?php echo $demande->ville; ?>-->
                                <?php echo $tel[$langue]; ?> : <?php echo $demande->tel; ?><br/>

                            </div>
                            <!--Commentaire : <?php echo $demande->message; ?>--></div>
                    </div>

                    <div class="col-lg-8" style="text-align: center">
                        <?php if ($demande->status == 1) : ?>
                            <p style="font-size: 20px"><?php echo $montant[$langue]; ?> : <span style="color: #de5a09;
    display: block;
    font-size: 33px;
    margin: 10px 0 0 0;"><?php echo $demande->montant; ?>  €</span></p>

                            <form method="post" name="MoneticoFormulaire" target="_top" action="https://p.monetico-services.com/paiement.cgi">
                                <input type="hidden" name="version" value="3.0">
                                <input type="hidden" name="TPE" value="7466577">
                                <input type="hidden" name="date" value="<?php echo $date; ?>">
                                <input type="hidden" name="montant" value="<?php echo $demande->montant; ?>EUR">
                                <input type="hidden" name="reference" value="<?php echo $reference; ?>">
                                <input type="hidden" name="MAC" value="<?php echo $key; ?>">
                                <input type="hidden" name="url_retour" value="https://www.rdvasie.com">
                                <input type="hidden" name="url_retour_ok" value="https://www.rdvasie.com/paiement_accepte">
                                <input type="hidden" name="url_retour_err" value="https://www.rdvasie.com/paiement_annule">
                                <input type="hidden" name="lgue" value="FR">
                                <input type="hidden" name="societe" value="agencedevo">
                                <input type="hidden" name="texte-libre" value="Rendez-vous avec l'Asie">
                                <input type="hidden" name="mail" value="<?php echo $demande->email; ?>">
                                <div class="col-xs-12 col-sm-offset-3 col-sm-6">
                                    <div class="triangle-down"></div>
                                </div>
                                <div class="text-center">
                                    <input class="btn" style="border:none; background: #e05a00;padding: 10px 15px; margin: 0 auto;font-weight: normal; color: #fff;font-size: 22px; cursor: pointer;" type="submit" name="bouton" value="<?php echo $acces[$langue]; ?>">
                                </div>

                            </form>


                            <h3><?php echo $cic[$langue]; ?></h3>
                            <img src="https://www.rdvasie.com/wp-content/uploads/2019/01/paiement-securise.png" style="width: 200px; margin:0 auto">
                        <?php else : ?>
                            <h2><?php echo $traite[$langue]; ?>.</h2>
                            <p><?php echo $erreur[$langue]; ?></p>
                        <?php endif; ?>
                    </div>


                </div>
                <div class="fusion-fullwidth fullwidth-box nonhundred-percent-fullwidth non-hundred-percent-height-scrolling" style="background-color: rgba(255,255,255,0);background-position: center center;background-repeat: no-repeat;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;">
                    <div class="fusion-builder-row fusion-row ">
                        <div class="fusion-layout-column fusion_builder_column fusion_builder_column_1_1  fusion-one-full fusion-column-first fusion-column-last 1_1" style="margin-top:0px;margin-bottom:20px;">
                            <div class="fusion-column-wrapper" style="padding: 0px 0px 0px 0px;background-position:left top;background-repeat:no-repeat;-webkit-background-size:cover;-moz-background-size:cover;-o-background-size:cover;background-size:cover;" data-bg-url="">
                                <div class="fusion-text">
                                </div>
                                <div class="fusion-clearfix"></div>

                            </div>
                        </div>
                    </div>
                </div>
        </section>

    </div>  <!-- fusion-row -->
</div>  <!-- #main -->
