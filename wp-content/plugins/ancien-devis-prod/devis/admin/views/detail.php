<div class="wrap">
   <h1 class="wp-heading-inline" style="background: #fff;padding: 10px 15px;width: auto;display: block;margin-bottom: 15px;"><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <hr class="wp-header-end">
 <h2>Voyage du client :</h2>
    Date de la demande : <?php echo $demande->demande; ?><br/>
    Destination(s) :<strong> <?php echo $output; ?><br/></strong>
    Date de départ souhaitée : <?php echo $demande->depart; ?><br/>
    Date de retour souhaitée : <?php echo $demande->retour; ?><br/>
    Durée du séjour : <?php echo $demande->duree; ?><br/>
    Budget : <?php echo $demande->budget; ?><br/>
    Nombre d'adulte(s) : <?php echo $demande->adulte; ?><br/>
    Nombre d'enfant(s) : <?php echo $demande->enfant; ?><br/>
    Nombre de bébé(s) : <?php echo $demande->bebe; ?><br/>
    Vol compris : <?php echo $demande->vol; ?><br/>
    <hr>
   <h2>Coordonnées du client :</h2>
    <div style="background: #f1f1f1; padding: 10px 15px; display: inline-block; border: 1px solid #ddd; margin-bottom: 15px;box-shadow: 2px 2px 4px 0px #00000024;"><?php echo $demande->civ; ?> <?php echo $demande->nom; ?> <?php echo $demande->prenom; ?><br/>
    Adresse : <?php echo $demande->cp; ?> <?php echo $demande->ville; ?><br/>
    Téléphone : <?php echo $demande->tel; ?><br/>
    Email : <?php echo $demande->email; ?><br/>


    Commentaire : <br/><?php echo $demande->message; ?></div>
    <br class="clear">
     <hr>
    <form action="?page=devis-submit" method="post">
        <input type="hidden" value="<?php echo $demande->id; ?>" name="devis" />
        Montant du devis : <input type="text" name="montant" value="<?php echo $demande->montant; ?>" placeholder="Montant du devis" />
        <br/>
        Langue du devis : <select name="langue"><option value="fr" <?php echo $demande->langue == "fr" ? "selected" : ""; ?>>Français</option><option value="en" <?php echo $demande->langue == "en" ? "selected" : ""; ?>>Anglais</option></select>
        <br/>

        <input style="background: #000;color: #fff;border: none;text-transform: uppercase;padding: 10px 15px;margin: 15px 0;width: 240px; cursor: pointer;" type="submit" value="Valider" />
    </form>
  <hr>
<h2>URL de paiement à copier :</h2>
   <div style="    background: #de5b09;color: #fff;padding: 15px;font-size: 16px;text-align: center;"> 
   	
   	<?php if($demande->montant) : ?>
    https://www.rdvasie.com/paiement/?q=<?php echo $demande->token; ?>
    <?php endif; ?></div>
</div>
