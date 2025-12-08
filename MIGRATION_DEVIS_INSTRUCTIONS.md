# 📋 Instructions de Migration des Devis

## 🎯 Objectif
Migrer les anciens devis depuis votre site de **production** vers le nouveau plugin **Devis Pro** sur votre site de **preprod**.

---

## 📦 Fichiers nécessaires

Vous trouverez dans le dossier `wp-content/plugins/` :

1. **`devis-OLD-avec-export.zip`** - Ancien plugin avec fonction d'export CSV
2. **`devis-pro-avec-import.zip`** - Nouveau plugin avec fonction d'import CSV

---

## 🚀 Étape 1 : Export depuis la PRODUCTION

### 1.1 - Uploader l'ancien plugin mis à jour

1. Connecte-toi en **FTP** sur ton site de **production**
2. Va dans `/wp-content/plugins/devis/`
3. **Sauvegarde** le fichier `devis.php` actuel (au cas où)
4. Remplace `devis.php` par celui du ZIP `devis-OLD-avec-export.zip`

**OU**

1. Va dans **WordPress Admin > Extensions**
2. Désactive "Gestion de devis"
3. Supprime le plugin (les données resteront dans la base)
4. Va dans **Extensions > Ajouter une extension**
5. Clique sur **Téléverser une extension**
6. Sélectionne `devis-OLD-avec-export.zip`
7. Clique sur **Installer maintenant**
8. Active le plugin

### 1.2 - Exporter les devis

1. Va dans **WordPress Admin > Gestion des devis**
2. Clique sur l'onglet **"Exporter (CSV)"** (nouveau !)
3. Tu verras le nombre de devis à exporter
4. Clique sur **"Télécharger le CSV"**
5. Le fichier `devis_export_YYYY-MM-DD_HH-MM-SS.csv` sera téléchargé
6. **Conserve ce fichier précieusement !**

---

## 📥 Étape 2 : Import dans la PREPROD

### 2.1 - Installer Devis Pro

1. Connecte-toi à ton site de **preprod**
2. Va dans **WordPress Admin > Extensions > Ajouter une extension**
3. Clique sur **Téléverser une extension**
4. Sélectionne `devis-pro-avec-import.zip`
5. Clique sur **Installer maintenant**
6. Active le plugin

### 2.2 - Importer les devis

1. Va dans **Devis Pro > Export**
2. Descends jusqu'à la section **"Import de données"**
3. Clique sur **"Choisir un fichier"**
4. Sélectionne le fichier CSV exporté à l'étape 1.2
5. Clique sur **"Importer le CSV"**
6. Attends la fin de l'import (quelques secondes)
7. Tu verras un message de succès avec le nombre de devis importés

### 2.3 - Vérifier l'import

1. Va dans **Devis Pro > Tous les devis**
2. Vérifie que tous tes devis sont présents
3. Ouvre quelques devis pour vérifier les données

---

## ✅ Étape 3 : Finalisation

### Sur la PRODUCTION (après avoir vérifié que tout fonctionne en preprod)

1. **Désactive** l'ancien plugin "Gestion de devis"
2. **Active** le plugin "Devis Pro"
3. Va dans **Devis Pro > Migration**
4. Clique sur **"Lancer la migration automatique"**
5. Les devis seront copiés automatiquement dans Devis Pro

**OU**

1. Utilise la méthode CSV (comme sur la preprod) si tu préfères

### Synchronisation Tripzzy

Le fichier `functions.php` du thème enfant a été mis à jour pour synchroniser automatiquement les demandes Tripzzy vers Devis Pro.

**Pour l'activer en production** :
1. Via FTP, remplace `/wp-content/themes/Avada-Child-Theme/functions.php`
2. Les nouvelles demandes Tripzzy seront automatiquement ajoutées dans Devis Pro

---

## 🔧 Dépannage

### Erreur "Aucune ancienne table trouvée"
➡️ C'est normal en preprod ! Utilise l'import CSV.

### Erreur lors de l'import CSV
➡️ Vérifie que le fichier CSV n'est pas vide et qu'il provient bien de l'export.

### Erreur "Fichier trop volumineux"
➡️ Augmente `upload_max_filesize` dans le `php.ini` ou contacte ton hébergeur.

### Les devis importés n'apparaissent pas
➡️ Va dans **Devis Pro > Tous les devis** et clique sur "Tous les statuts" dans les filtres.

---

## 📊 Résumé du workflow

```
┌─────────────────────────────────────────────────────────────┐
│                     SITE DE PRODUCTION                       │
│                                                              │
│  1. Installer ancien plugin avec export                     │
│  2. Exporter les devis en CSV                               │
│  3. Télécharger le fichier CSV                              │
└────────────────────────┬────────────────────────────────────┘
                         │
                         │ Transfert du CSV
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                       SITE PREPROD                           │
│                                                              │
│  1. Installer Devis Pro                                     │
│  2. Aller dans Migration                                    │
│  3. Importer le fichier CSV                                 │
│  4. Vérifier les devis importés                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎨 Fichiers additionnels

### WebP Loader (déjà installé en preprod)

Pour la production, uploader également :
- `/wp-content/themes/Avada-Child-Theme/webp-loader.js`
- `/wp-content/themes/Avada-Child-Theme/functions.php` (pour Tripzzy sync)
- `/wp-content/themes/Avada-Child-Theme/style.css` (pour le logo header)

---

## 📞 Support

En cas de problème :
1. Vérifie le `debug.log` WordPress (active `WP_DEBUG` si nécessaire)
2. Vérifie que tu utilises les bons fichiers ZIP
3. Contacte-moi si besoin !

---

**Date de création :** 2 décembre 2025  
**Version Devis Pro :** 2.1.0

