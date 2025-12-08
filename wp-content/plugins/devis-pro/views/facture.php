<?php
/**
 * Template de facture PDF
 */

if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les infos de l'entreprise
$settings = get_option('devis_pro_settings');

// Récupérer le titre du voyage
$voyage_ids = explode("-;-", $devis->voyage);
$voyage_title = '';
foreach ($voyage_ids as $id) {
    if (!empty($id) && is_numeric($id)) {
        $title = get_the_title($id);
        if ($title) {
            $voyage_title = $title;
            break;
        }
    }
}
if (empty($voyage_title)) {
    $voyage_title = $devis->destination ?: 'Voyage en Asie';
}

// Numéro de facture
$invoice_number = 'RDVASIE-' . str_pad($devis->id, 5, '0', STR_PAD_LEFT);

// Dates
$invoice_date = date('d/m/Y');
$devis_date = date('d/m/Y', strtotime($devis->demande));

// Calculer les détails
$total_participants = intval($devis->adulte) + intval($devis->enfant);
$prix_par_personne = $total_participants > 0 ? $devis->montant / $total_participants : $devis->montant;

// Statut du devis
$status_labels = array(
    0 => 'En attente',
    1 => 'Devis envoyé',
    2 => 'Accepté',
    3 => 'Refusé',
    4 => 'Payé',
    5 => 'Annulé'
);
$status_label = $status_labels[$devis->status] ?? 'En cours';
$is_paid = ($devis->status == 4);
$document_type = 'FACTURE'; // Toujours "FACTURE" car accessible uniquement pour les devis payés
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $document_type; ?> <?php echo $invoice_number; ?> - Rendez-vous avec l'Asie</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            color: #333;
            background: #f5f5f5;
            padding: 10px;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .invoice-header {
            background: linear-gradient(135deg, #de5b09 0%, #c44d07 100%);
            color: #fff;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .company-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .company-logo {
            height: 45px;
            width: auto;
        }
        
        .company-logo-print {
            display: none;
            height: 45px;
            width: auto;
        }
        
        .company-info h1 {
            font-size: 20px;
            margin-bottom: 2px;
        }
        
        .company-info p {
            font-size: 11px;
            opacity: 0.9;
        }
        
        .invoice-title {
            text-align: right;
        }
        
        .invoice-title h2 {
            font-size: 24px;
            font-weight: 700;
        }
        
        .invoice-title .invoice-number {
            font-size: 12px;
            opacity: 0.9;
            margin-top: 3px;
        }
        
        .invoice-body {
            padding: 20px 25px;
        }
        
        .invoice-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            gap: 20px;
        }
        
        .meta-block {
            flex: 1;
        }
        
        .meta-block h3 {
            font-size: 10px;
            text-transform: uppercase;
            color: #999;
            margin-bottom: 6px;
            letter-spacing: 1px;
        }
        
        .meta-block p {
            margin-bottom: 2px;
            color: #333;
            font-size: 11px;
            line-height: 1.4;
        }
        
        .meta-block strong {
            color: #de5b09;
        }
        
        .client-info {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
        }
        
        .voyage-info {
            background: #fff5f0;
            border: 2px solid #de5b09;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 15px;
        }
        
        .voyage-info h3 {
            color: #de5b09;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .voyage-details {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }
        
        .voyage-detail-item {
            text-align: center;
            padding: 6px;
            background: #fff;
            border-radius: 4px;
        }
        
        .voyage-detail-item .label {
            font-size: 9px;
            color: #999;
            text-transform: uppercase;
        }
        
        .voyage-detail-item .value {
            font-size: 11px;
            font-weight: 600;
            color: #333;
            margin-top: 2px;
        }
        
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .invoice-table th {
            background: #f8f9fa;
            padding: 8px 10px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
            border-bottom: 2px solid #de5b09;
        }
        
        .invoice-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
            font-size: 11px;
        }
        
        .invoice-table .text-right {
            text-align: right;
        }
        
        .invoice-table .text-center {
            text-align: center;
        }
        
        .invoice-table tbody tr:hover {
            background: #fafafa;
        }
        
        .invoice-total {
            display: flex;
            justify-content: flex-end;
        }
        
        .total-table {
            width: 250px;
        }
        
        .total-table tr td {
            padding: 6px 10px;
            font-size: 11px;
        }
        
        .total-table tr td:last-child {
            text-align: right;
            font-weight: 600;
        }
        
        .total-table .grand-total {
            background: #de5b09;
            color: #fff;
            font-size: 14px;
        }
        
        .total-table .grand-total td {
            padding: 10px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 11px;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .invoice-footer {
            background: #f8f9fa;
            padding: 15px 25px;
            text-align: center;
            border-top: 1px solid #eee;
        }
        
        .invoice-footer p {
            color: #666;
            font-size: 11px;
            margin-bottom: 5px;
        }
        
        .invoice-footer .legal {
            font-size: 9px;
            color: #999;
            margin-top: 10px;
            line-height: 1.4;
        }
        
        .print-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #de5b09;
            color: #fff;
            border: none;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(222, 91, 9, 0.4);
        }
        
        .print-btn:hover {
            background: #c44d07;
        }
        
        @media print {
            body {
                background: #fff;
                padding: 0;
                font-size: 11px;
            }
            
            .invoice-container {
                box-shadow: none;
            }
            
            .print-btn {
                display: none;
            }
            
            .invoice-body {
                padding: 15px 20px;
            }
            
            .invoice-header {
                padding: 12px 20px;
            }
            
            .invoice-footer {
                padding: 12px 20px;
            }
            
            .company-logo {
                display: none;
            }
            
            .company-logo-print {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <div class="company-info">
                <img src="https://preprod.rdvasie.com/wp-content/uploads/2025/07/rdv-asie-bmanc-homepage.png" alt="Logo" class="company-logo">
                <img src="https://preprod.rdvasie.com/wp-content/uploads/2025/11/voyage-rendez-vous-avec-l-asie-logo.png" alt="Logo" class="company-logo-print">
                <div>
                    <h1>Rendez-vous avec l'Asie</h1>
                    <p>Agence de voyages spécialiste de l'Asie</p>
                </div>
            </div>
            <div class="invoice-title">
                <h2><?php echo $document_type; ?></h2>
                <div class="invoice-number">N° <?php echo $invoice_number; ?></div>
            </div>
        </div>
        
        <div class="invoice-body">
            <div class="invoice-meta">
                <div class="meta-block">
                    <h3>Émetteur</h3>
                    <p><strong>RENDEZ-VOUS AVEC L'ASIE</strong></p>
                    <p>SAS au capital de 8 000 €</p>
                    <p>12 Avenue Carnot</p>
                    <p>44000 NANTES</p>
                    <p>Tél : 02 72 64 40 34</p>
                    <p>Email : contact@rdvasie.com</p>
                    <p style="margin-top: 10px; font-size: 12px; color: #666;">
                        SIRET : 84294849900012<br>
                        N° TVA : FR25842948499<br>
                        RCS : NANTES 842948499<br>
                        Immatriculation : IM044190003
                    </p>
                </div>
                
                <div class="meta-block client-info">
                    <h3>Client</h3>
                    <p><strong><?php echo esc_html(trim($devis->civ . ' ' . $devis->prenom . ' ' . $devis->nom)); ?></strong></p>
                    <?php if ($devis->cp || $devis->ville) : ?>
                    <p><?php echo esc_html($devis->cp . ' ' . $devis->ville); ?></p>
                    <?php endif; ?>
                    <p><?php echo esc_html($devis->email); ?></p>
                    <p><?php echo esc_html($devis->tel); ?></p>
                    <?php if ($is_paid) : ?>
                    <p style="margin-top: 15px;">
                        <span class="status-badge status-paid">✓ PAYÉ</span>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="invoice-meta" style="margin-bottom: 20px;">
                <div class="meta-block">
                    <p><strong>Date du document :</strong> <?php echo $invoice_date; ?></p>
                    <p><strong>Date de demande :</strong> <?php echo $devis_date; ?></p>
                </div>
            </div>
            
            <div class="voyage-info">
                <h3>🌏 <?php echo esc_html($voyage_title); ?></h3>
                <div class="voyage-details">
                    <?php if ($devis->depart) : ?>
                    <div class="voyage-detail-item">
                        <div class="label">Date de départ</div>
                        <div class="value"><?php echo esc_html($devis->depart); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($devis->retour) : ?>
                    <div class="voyage-detail-item">
                        <div class="label">Date de retour</div>
                        <div class="value"><?php echo esc_html($devis->retour); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($devis->duree) : ?>
                    <div class="voyage-detail-item">
                        <div class="label">Durée</div>
                        <div class="value"><?php echo esc_html($devis->duree); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($devis->vol) : ?>
                    <div class="voyage-detail-item">
                        <div class="label">Vol inclus</div>
                        <div class="value"><?php echo esc_html($devis->vol); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-center">Quantité</th>
                        <th class="text-right">Prix unitaire</th>
                        <th class="text-right">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($devis->adulte > 0) : ?>
                    <tr>
                        <td>
                            <strong>Voyage - <?php echo esc_html($voyage_title); ?></strong><br>
                            <span style="color: #666; font-size: 13px;">Participant adulte</span>
                        </td>
                        <td class="text-center"><?php echo $devis->adulte; ?></td>
                        <td class="text-right"><?php echo number_format($prix_par_personne, 2, ',', ' '); ?> €</td>
                        <td class="text-right"><?php echo number_format($devis->adulte * $prix_par_personne, 2, ',', ' '); ?> €</td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if ($devis->enfant > 0) : ?>
                    <tr>
                        <td>
                            <strong>Voyage - <?php echo esc_html($voyage_title); ?></strong><br>
                            <span style="color: #666; font-size: 13px;">Participant enfant (2-11 ans)</span>
                        </td>
                        <td class="text-center"><?php echo $devis->enfant; ?></td>
                        <td class="text-right"><?php echo number_format($prix_par_personne, 2, ',', ' '); ?> €</td>
                        <td class="text-right"><?php echo number_format($devis->enfant * $prix_par_personne, 2, ',', ' '); ?> €</td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if ($devis->bebe > 0) : ?>
                    <tr>
                        <td>
                            <strong>Voyage - <?php echo esc_html($voyage_title); ?></strong><br>
                            <span style="color: #666; font-size: 13px;">Bébé (moins de 2 ans)</span>
                        </td>
                        <td class="text-center"><?php echo $devis->bebe; ?></td>
                        <td class="text-right">Gratuit</td>
                        <td class="text-right">0,00 €</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="invoice-total">
                <table class="total-table">
                    <tr>
                        <td>Sous-total HT</td>
                        <td><?php echo number_format($devis->montant, 2, ',', ' '); ?> €</td>
                    </tr>
                    <tr>
                        <td>TVA (0%)</td>
                        <td>0,00 €</td>
                    </tr>
                    <tr class="grand-total">
                        <td><strong>TOTAL TTC</strong></td>
                        <td><strong><?php echo number_format($devis->montant, 2, ',', ' '); ?> €</strong></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="invoice-footer">
            <p>Merci pour votre confiance !</p>
            <p>
                <strong>Rendez-vous avec l'Asie</strong><br>
                12 Avenue Carnot, 44000 NANTES<br>
                Tél : 02 72 64 40 34 • Email : contact@rdvasie.com<br>
                www.rdvasie.com
            </p>
            <p class="legal">
                SAS au capital de 8 000 € • SIRET 84294849900012 • RCS NANTES 842948499<br>
                N° TVA FR25842948499 • Code APE 7912Z • Immatriculation Atout France IM044190003<br>
                Garantie financière APST • Assurance RCP HISCOX
            </p>
        </div>
    </div>
    
    <button class="print-btn" onclick="window.print()">
        📄 Imprimer / Télécharger PDF
    </button>
</body>
</html>

