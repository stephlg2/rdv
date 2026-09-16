<?php
define( 'SHORTINIT', true );
require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );
require( ABSPATH . WPINC . '/pluggable.php' );

function _get_key($cle){
    $hexStrKey  = substr($cle, 0, 38);
    $hexFinal   = "" . substr($cle, 38, 2) . "00";

    $cca0=ord($hexFinal);

    if ($cca0>70 && $cca0<97)
        $hexStrKey .= chr($cca0-23) . substr($hexFinal, 1, 1);
    else {
        if (substr($hexFinal, 1, 1)=="M")
            $hexStrKey .= substr($hexFinal, 0, 1) . "0";
        else
            $hexStrKey .= substr($hexFinal, 0, 2);
    }


    return pack("H*", $hexStrKey);
}

global $wpdb;

$tpe = $_POST['TPE'];
$date = $_POST['date'];
$montant = $_POST['montant'];
$reference = $_POST['reference'];
$texte = $_POST['texte-libre'];
$code_retour = $_POST['code-retour'];
$cvx = $_POST['cvx'];
$vld = $_POST['vld'];
$brand = $_POST['brand'];
$status3ds = $_POST['status3ds'];
$numauto = $_POST['numauto'];
$motifrefus = $_POST['motifrefus'];
$originecb = $_POST['originecb'];
$bincb = $_POST['bincb'];
$hpancb = $_POST['hpancb'];
$ipclient = $_POST['ipclient'];
$originetr = $_POST['originetr'];
$veres = $_POST['veres'];
$pares = $_POST['pares'];

$mac = $_POST['MAC'];

$cle = _get_key("ED12A476000FDE6358BFF3B398B3998673F15495");

$key = hash_hmac("sha1", "{$tpe}*{$date}*{$montant}*{$reference}*{$texte}*3.0*{$code_retour}*{$cvx}*{$vld}*{$brand}*{$status3ds}*{$numauto}*{$motifrefus}*{$originecb}*{$bincb}*{$hpancb}*{$ipclient}*{$originetr}*{$veres}*{$pares}*", $cle);

$debug = fopen("debug.txt", "w+");

fwrite($debug, "$reference\n");

if(strtoupper($key) == $mac){
    $table_name = $wpdb->prefix . 'devis';
    $table_post = $wpdb->prefix . 'posts';

    $demande = $wpdb->get_row("SELECT * FROM $table_name WHERE mac = '$reference'");
    if($code_retour != "Annulation") {

        $sql = $wpdb->get_row("UPDATE $table_name SET status = 2 WHERE id = {$demande->id}");
        $wpdb->query($sql);

        $ids = explode("-;-", $demande->destination);
        $output = "";

        foreach( $ids as $id) :
            if( $id != 0 && $id != "" && $id != NULL ) :
                $post = $wpdb->get_row("SELECT * FROM $table_post WHERE ID = '$id'");
                if($post->post_title) {
                    $output .= $post->post_title . "<br/>";
                }
            endif;

        endforeach;

        if($output == ""){
            $output = $demande->voyage;
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
        $headers[] = "From: {$demande->email} <contact@rdvasie.com>";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "Reply-To: {$demande->email}";

        wp_mail( "contact@rdvasie.com", "Rendez-vous avec l'Asie - Acceptation de Devis", $message, $headers);

        $message = "Bonjour,<br/>
        Vous avez effectué un paiement sur rdvasie.com pour l'agence de voyage Rendez-vous avec L'Asie et nous vous en remercions !<br/>
        Votre paiement a bien été pris en compte, nous reviendrons vers vous rapidement pour la confirmation des prestations réservées.<br/>
        Merci pour votre confiance et à très bientôt !<br/>
        <a href=\"https://www.rdvasie.com\"><img src=\"https://www.rdvasie.com/wp-content/uploads/2018/11/rdv-asie.png\"></a><br/>
        contact@rdvasie.com / www.rdvasie.com";
        $headers[] = "From: Rendez-vous avec l'Asie <contact@rdvasie.com>";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "Reply-To: contact@rdvasie.com";

        wp_mail( $demande->email, "Confirmation de paiement sur rdvasie.com", $message, $headers);

    }else{
        $sql = $wpdb->get_row("UPDATE $table_name SET status = 3 WHERE id = {$demande->id}");
        $wpdb->query($sql);
    }

    echo "version=2\ncdr=0";
}else{
    echo "version=2\ncdr=1";
}

fclose($debug);
