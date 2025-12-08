<?php
/**
 * Script temporaire pour ajouter la colonne newsletter
 * À exécuter une seule fois puis supprimer
 */

// Charger WordPress
require_once('../../../../wp-load.php');

// Vérifier les permissions
if (!current_user_can('manage_options')) {
    die('Accès refusé');
}

global $wpdb;
$table_name = $wpdb->prefix . 'devis_pro';

echo '<h1>Migration : Ajout colonne Newsletter</h1>';
echo '<hr>';

// Vérifier si la colonne existe
$column_exists = $wpdb->get_results(
    "SHOW COLUMNS FROM {$table_name} LIKE 'newsletter'"
);

if (empty($column_exists)) {
    echo '<p>📊 Ajout de la colonne newsletter...</p>';
    
    // Ajouter la colonne
    $result = $wpdb->query(
        "ALTER TABLE {$table_name} 
        ADD COLUMN newsletter tinyint(1) NOT NULL DEFAULT 0 
        AFTER last_reminder"
    );
    
    if ($result !== false) {
        echo '<p style="color:green;font-size:18px;font-weight:bold;">✅ Colonne "newsletter" ajoutée avec succès !</p>';
        echo '<p>✨ Vous pouvez maintenant tester le formulaire.</p>';
        echo '<p style="background:#fff3cd;padding:15px;border-left:4px solid #ffc107;"><strong>⚠️ IMPORTANT :</strong> Supprimez ce fichier <code>add-newsletter-column.php</code> par sécurité.</p>';
    } else {
        echo '<p style="color:red;">❌ Erreur lors de l\'ajout de la colonne : ' . $wpdb->last_error . '</p>';
    }
} else {
    echo '<p style="color:blue;font-size:18px;">ℹ️ La colonne "newsletter" existe déjà dans la table.</p>';
    echo '<p>Tout est en ordre ! Le formulaire devrait fonctionner.</p>';
}

echo '<br><p><a href="' . admin_url('admin.php?page=devis-pro') . '" style="background:#de5b09;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;">← Retour au tableau de bord Devis Pro</a></p>';

