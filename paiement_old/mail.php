<?php
define( 'SHORTINIT', true );
require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );
require( ABSPATH . WPINC . '/pluggable.php' );

global $wpdb;

$token = $_GET['q'];

$table_name = $wpdb->prefix . 'devis';
$table_post = $wpdb->prefix . 'posts';

$demande = $wpdb->get_row("SELECT * FROM $table_name WHERE token = '$token'");
$ids = explode("-;-", $demande->destination);
$output = "";

foreach( $ids as $id) :
    if( $id != 0 && $id != "" && $id != NULL ) :
        $post = $wpdb->get_row("SELECT * FROM $table_post WHERE ID = '$id'");
        if($post->post_title) {
            $output .= "<br/>" . $post->post_title;
        }
    endif;

endforeach;

if($output == ""){
    $output = "<br>" . $demande->voyage;
}

$message = "Bonjour,<br/>
        Vous avez reçu une acceptation de devis de la part de <strong>{$demande->civilite} {$demande->prenom} {$demande->nom}</strong>.<br/>
        <br/>
        ---<br/>
        <h2>Récapitulatif de la demande </h2>
        <h3>Le(s) voyage(s)</h3>
        <em>Voyage(s) souhaité(s) :</em>
        <br/>
                {$output}
        <br/>
        <em>Date du départ :</em> {$demande->depart}<br/>
        <em>Date de retour :</em> {$demande->retour}<br/>
        <em>Durée du séjour :</em> {$demande->duree}<br/>
        <em>Nombre de participants :</em> {$demande->adulte} Adultes, {$demande->enfant} Enfants et {$demande->bebe} Bébés<br/>
                <em>Vols inclus :</em> {$demande->vol}<br/>
        <em>Description du voyage :</em><br/>
                {$demande->message}<br/>
        -----------------------------<br/>
        <h3>Coordonnées du client</h3>
        <em>Civilité :</em> {$demande->civilite}<br/>
        <em>Nom :</em> {$demande->nom}<br/>
        <em>Prénom :</em> {$demande->prenom}<br/>
        <em>Lieu de résidence :</em> {$demande->cp} / {$demande->ville}<br/>
        <em>Email :</em> {$demande->email}<br/>
        <em>Téléphone :</em> {$demande->tel}<br/>";
        $headers[] = "From: Rendez-vous avec l'Asie <contact@rdvasie.com>";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "Reply-To: contact@rdvasie.com";

if(wp_mail( "contact@jothomson.info", "Rendez-vous avec l'Asie - Acceptation de Devis", $message, $headers)) {
    echo "Mail envoyé";
}else{
    echo "Erreur mail";
}