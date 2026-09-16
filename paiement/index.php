<?php
/**
 * Bridge WordPress pour le dossier /paiement/
 * Permet de lever l'erreur 403 et de laisser le plugin gérer les requêtes.
 */

// 1. On définit qu'on veut charger le moteur de thèmes
define('WP_USE_THEMES', true);

// 2. On charge le fichier blog-header qui lance tout le routing WordPress
// On remonte d'un niveau (../) pour trouver la racine
$wp_header = dirname(__FILE__) . '/../wp-blog-header.php';

if (file_exists($wp_header)) {
    require_once($wp_header);
} else {
    // Sécurité si le chemin est mauvais
    header("HTTP/1.1 500 Internal Server Error");
    die("Erreur de bridge : wp-blog-header.php introuvable.");
}
