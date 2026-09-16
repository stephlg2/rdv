<?php
/**
 * BRIDGE MONETICO - Connexion entre l'URL fixe de la banque et le Plugin WordPress
 */

// 1. Chargement de l'environnement WordPress
// On remonte d'un niveau (..) pour trouver wp-load.php à la racine
$wp_load_path = $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';

if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
} else {
    // Si le chemin est différent (cas rare d'installations exotiques)
    header("HTTP/1.1 500 Internal Server Error");
    die("Erreur de configuration : wp-load.php introuvable.");
}

// 2. Déclenchement du hook personnalisé
// On envoie un signal que votre plugin va intercepter
do_action('monetico_api_callback');

// 3. Fin de l'exécution
exit;
