╔═══════════════════════════════════════════════════════════════╗
║     ACTIVER L'IMPORT CSV SUR LA PAGE EXPORT                  ║
╚═══════════════════════════════════════════════════════════════╝

NOUVELLE APPROCHE :
L'import CSV est maintenant activé sur la page "Export" (plus logique !)
Vous allez activer la fonction d'import qui était désactivée.

═══════════════════════════════════════════════════════════════

SOLUTION SIMPLE VIA FTP :

1. Connecte-toi en FTP à ton site preprod
   
2. Va dans le dossier :
   /wp-content/plugins/devis-pro/admin/views/

3. SAUVEGARDE le fichier actuel :
   - Télécharge "export.php" sur ton ordinateur
   - Renomme-le "export.php.OLD" (au cas où)

4. UPLOAD le nouveau fichier :
   - Prends le fichier "export.php" de ce dossier
   - Upload-le dans /wp-content/plugins/devis-pro/admin/views/
   - Remplace l'ancien fichier

5. VIDE TOUS LES CACHES :
   - WordPress : Purger le cache
   - Navigateur : Cmd+Shift+R (ou Ctrl+Shift+R)

6. RETOURNE sur Devis Pro > Export

═══════════════════════════════════════════════════════════════

RÉSULTAT ATTENDU :

Sur la page "Devis Pro > Export", tu verras TROIS sections :

┌─────────────────────────────────────────────────────────────┐
│  📊 Export CSV                                               │
│                                                              │
│  Statut : [Tous les statuts ▼]                             │
│  Date début : [jj/mm/aaaa]                                 │
│  Date fin : [jj/mm/aaaa]                                   │
│                                                              │
│  [Télécharger le CSV]                                       │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  📈 Résumé des données                                       │
│                                                              │
│  Total des devis : XX                                        │
│  • En attente : XX                                          │
│  • Devis envoyé : XX                                        │
│  etc...                                                      │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  📤 Import de données  ← ACTIVÉ !                           │
│                                                              │
│  Importez des devis depuis un fichier CSV...                │
│                                                              │
│  Fichier CSV : [Choisir un fichier]                        │
│                                                              │
│  [Importer le CSV]  ← Plus de "disabled" !                 │
│                                                              │
│  📝 Instructions :                                           │
│  1. Sur la prod, exportez les devis en CSV                 │
│  2. Sélectionnez le fichier CSV ci-dessus                  │
│  3. Cliquez sur "Importer le CSV"                          │
└─────────────────────────────────────────────────────────────┘

═══════════════════════════════════════════════════════════════

CHEMIN COMPLET DU FICHIER À REMPLACER :
/wp-content/plugins/devis-pro/admin/views/export.php

═══════════════════════════════════════════════════════════════

COMMENT L'UTILISER :

1. Sur ton site de PRODUCTION :
   - Va dans "Gestion des devis > Exporter (CSV)"
   - Télécharge le fichier CSV

2. Sur ton site de PREPROD :
   - Va dans "Devis Pro > Export"
   - Scroll jusqu'à la section "Import de données"
   - Clique sur "Choisir un fichier"
   - Sélectionne le CSV de la prod
   - Clique sur "Importer le CSV"
   - Attends quelques secondes
   - Tu verras un message de succès !

3. Vérifie dans "Devis Pro > Tous les devis"

═══════════════════════════════════════════════════════════════

FICHIERS INCLUS DANS CE DOSSIER :

- export.php (12K) - Active l'import CSV sur la page Export
- migration.php (16K) - Optionnel, pour la migration automatique
- README_UPLOAD.txt - Ce fichier !

═══════════════════════════════════════════════════════════════

Si ça ne marche toujours pas :

1. Vérifie que tu es bien connecté en tant qu'administrateur
2. Désactive tous les plugins de cache
3. Vérifie les permissions du fichier (644 ou 755)
4. Regarde le debug.log WordPress pour des erreurs PHP

═══════════════════════════════════════════════════════════════

