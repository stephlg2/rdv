<?php
/**
 * Page de paiement
 */

if (!defined('ABSPATH')) {
    exit;
}

// Récupérer le titre du voyage
$voyage_ids = explode("-;-", $devis->voyage);
$voyage_titles = array();
foreach ($voyage_ids as $id) {
    if (!empty($id) && is_numeric($id)) {
        $title = get_the_title($id);
        if ($title) {
            $voyage_titles[] = $title;
        }
    }
}
$voyage_title = !empty($voyage_titles) ? implode(', ', $voyage_titles) : ($devis->destination ?: __('Votre voyage', 'devis-pro'));
?>

<style>
/* Reset des styles du thème */
.devis-payment-wrapper {
    all: initial;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif !important;
    display: block;
}

.devis-payment-wrapper * {
    box-sizing: border-box;
}

.devis-payment {
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
    color: #333;
    line-height: 1.6;
}

.devis-payment h1,
.devis-payment h2,
.devis-payment h3 {
    font-family: inherit;
    line-height: 1.3;
    margin: 0;
}

.devis-payment p {
    margin: 0 0 15px;
}

.payment-header {
    text-align: center;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 3px solid #de5b09;
}

.payment-header h1 {
    font-size: 32px !important;
    color: #333 !important;
    margin: 0 0 10px !important;
    font-weight: 700 !important;
}

.payment-header p {
    color: #666;
    font-size: 18px;
    margin: 0;
}

.payment-content {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 30px;
}

@media (max-width: 768px) {
    .payment-content {
        grid-template-columns: 1fr;
    }
}

.payment-summary {
    background: #fff;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.1);
    border: 1px solid #e5e5e5;
}

.payment-summary h2 {
    font-size: 20px !important;
    color: #333 !important;
    margin: 0 0 20px !important;
    padding-bottom: 15px !important;
    border-bottom: 2px solid #de5b09 !important;
    font-weight: 600 !important;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    padding: 14px 0;
    border-bottom: 1px solid #eee;
}

.summary-item:last-child {
    border-bottom: none;
}

.summary-item .label {
    color: #666;
    font-size: 14px;
}

.summary-item .value {
    font-weight: 600;
    color: #333;
    text-align: right;
    max-width: 60%;
}

.client-info {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    margin-top: 25px;
}

.client-info h3 {
    font-size: 16px !important;
    color: #333 !important;
    margin: 0 0 15px !important;
    font-weight: 600 !important;
}

.client-info p {
    margin: 8px 0;
    font-size: 14px;
    color: #555;
}

.client-info p strong {
    color: #333;
}

.payment-action {
    background: linear-gradient(135deg, #de5b09 0%, #c44d07 100%);
    border-radius: 16px;
    padding: 40px;
    text-align: center;
    color: #fff;
    box-shadow: 0 10px 30px rgba(222, 91, 9, 0.3);
}

.payment-action p {
    color: rgba(255,255,255,0.9);
    font-size: 16px;
    margin: 0 0 10px;
}

.payment-amount {
    font-size: 52px !important;
    font-weight: 700 !important;
    margin: 20px 0 !important;
    color: #fff !important;
}

.payment-amount small {
    font-size: 28px;
    opacity: 0.9;
}

.payment-form {
    margin-top: 30px;
}

.payment-btn {
    background: #fff !important;
    color: #de5b09 !important;
    border: none !important;
    padding: 18px 60px !important;
    font-size: 20px !important;
    font-weight: 700 !important;
    border-radius: 12px !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    display: inline-block !important;
    text-decoration: none !important;
}

.payment-btn:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 15px 35px rgba(0,0,0,0.25) !important;
}

.payment-secure {
    margin-top: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-size: 14px;
    color: rgba(255,255,255,0.9);
}

.payment-secure svg {
    width: 22px;
    height: 22px;
    fill: currentColor;
}

.payment-logo {
    margin-top: 25px;
}

.payment-logo img {
    max-width: 200px;
    opacity: 0.95;
}

.payment-processed {
    background: #fff;
    border-radius: 16px;
    padding: 50px 40px;
    text-align: center;
    box-shadow: 0 5px 25px rgba(0,0,0,0.1);
    border: 1px solid #e5e5e5;
}

.payment-processed h2 {
    color: #333 !important;
    font-size: 24px !important;
    margin: 0 0 15px !important;
    font-weight: 600 !important;
}

.payment-processed p {
    color: #666;
    font-size: 16px;
    margin: 0 0 20px;
}

.payment-processed .contact-info {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 25px;
    margin-top: 25px;
}

.payment-processed .contact-info a {
    color: #de5b09;
    text-decoration: none;
    font-weight: 600;
}

.payment-processed .contact-info a:hover {
    text-decoration: underline;
}
</style>

<div class="devis-payment-wrapper">
    <div class="devis-payment">
        <div class="payment-header">
            <h1><?php _e('Règlement de votre voyage', 'devis-pro'); ?></h1>
            <p><?php echo esc_html($voyage_title); ?></p>
        </div>

        <div class="payment-content">
            <!-- Résumé -->
            <div class="payment-summary">
                <h2><?php _e('Récapitulatif', 'devis-pro'); ?></h2>
                
                <div class="summary-item">
                    <span class="label"><?php _e('Destination', 'devis-pro'); ?></span>
                    <span class="value"><?php echo esc_html($voyage_title); ?></span>
                </div>
                
                <div class="summary-item">
                    <span class="label"><?php _e('Date de demande', 'devis-pro'); ?></span>
                    <span class="value"><?php echo $devis->demande ? date('d/m/Y', strtotime($devis->demande)) : '-'; ?></span>
                </div>
                
                <?php if (!empty($devis->depart)) : ?>
                <div class="summary-item">
                    <span class="label"><?php _e('Date de départ', 'devis-pro'); ?></span>
                    <span class="value"><?php echo esc_html($devis->depart); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($devis->retour)) : ?>
                <div class="summary-item">
                    <span class="label"><?php _e('Date de retour', 'devis-pro'); ?></span>
                    <span class="value"><?php echo esc_html($devis->retour); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($devis->adulte || $devis->enfant || $devis->bebe) : ?>
                <div class="summary-item">
                    <span class="label"><?php _e('Participants', 'devis-pro'); ?></span>
                    <span class="value">
                        <?php 
                        $parts = array();
                        if ($devis->adulte) $parts[] = $devis->adulte . ' ' . _n('adulte', 'adultes', $devis->adulte, 'devis-pro');
                        if ($devis->enfant) $parts[] = $devis->enfant . ' ' . _n('enfant', 'enfants', $devis->enfant, 'devis-pro');
                        if ($devis->bebe) $parts[] = $devis->bebe . ' ' . _n('bébé', 'bébés', $devis->bebe, 'devis-pro');
                        echo implode(', ', $parts);
                        ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <?php 
                // Calculer les détails tarifaires
                $total_participants = intval($devis->adulte) + intval($devis->enfant);
                $prix_par_personne = $total_participants > 0 ? $devis->montant / $total_participants : $devis->montant;
                ?>
                
                <?php if ($devis->montant > 0 && $total_participants > 0) : ?>
                <div class="pricing-details" style="background:#f8f9fa;border-radius:12px;padding:20px;margin-top:20px;">
                    <h3 style="font-size:16px;color:#333;margin:0 0 15px;font-weight:600;"><?php _e('Détail du tarif', 'devis-pro'); ?></h3>
                    
                    <?php if ($devis->adulte > 0) : ?>
                    <div class="pricing-line" style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e9ecef;">
                        <span style="color:#666;"><?php echo $devis->adulte; ?> <?php echo _n('adulte', 'adultes', $devis->adulte, 'devis-pro'); ?> × <?php echo number_format($prix_par_personne, 0, ',', ' '); ?> €</span>
                        <span style="font-weight:600;color:#333;"><?php echo number_format($devis->adulte * $prix_par_personne, 0, ',', ' '); ?> €</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($devis->enfant > 0) : ?>
                    <div class="pricing-line" style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e9ecef;">
                        <span style="color:#666;"><?php echo $devis->enfant; ?> <?php echo _n('enfant', 'enfants', $devis->enfant, 'devis-pro'); ?> × <?php echo number_format($prix_par_personne, 0, ',', ' '); ?> €</span>
                        <span style="font-weight:600;color:#333;"><?php echo number_format($devis->enfant * $prix_par_personne, 0, ',', ' '); ?> €</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($devis->bebe > 0) : ?>
                    <div class="pricing-line" style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e9ecef;">
                        <span style="color:#666;"><?php echo $devis->bebe; ?> <?php echo _n('bébé', 'bébés', $devis->bebe, 'devis-pro'); ?></span>
                        <span style="font-weight:600;color:#28a745;"><?php _e('Gratuit', 'devis-pro'); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="pricing-total" style="display:flex;justify-content:space-between;padding:12px 0 0;margin-top:8px;border-top:2px solid #de5b09;">
                        <span style="font-weight:700;color:#333;font-size:16px;"><?php _e('Total', 'devis-pro'); ?></span>
                        <span style="font-weight:700;color:#de5b09;font-size:20px;"><?php echo number_format($devis->montant, 0, ',', ' '); ?> €</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="client-info">
                    <h3><?php _e('Vos coordonnées', 'devis-pro'); ?></h3>
                    <p><strong><?php echo esc_html(trim($devis->civ . ' ' . $devis->prenom . ' ' . $devis->nom)); ?></strong></p>
                    <?php if (!empty($devis->email)) : ?>
                    <p><?php echo esc_html($devis->email); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($devis->tel)) : ?>
                    <p><?php echo esc_html($devis->tel); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action de paiement -->
            <?php if ($payment_data) : ?>
                <div class="payment-action">
                    <p><?php _e('Montant à régler', 'devis-pro'); ?></p>
                    
                    <div class="payment-amount">
                        <?php echo number_format($devis->montant, 0, ',', ' '); ?><small> €</small>
                    </div>
                    
                    <form method="post" action="https://p.monetico-services.com/paiement.cgi" class="payment-form">
                        <input type="hidden" name="version" value="3.0">
                        <input type="hidden" name="TPE" value="<?php echo esc_attr($payment_data['tpe']); ?>">
                        <input type="hidden" name="date" value="<?php echo esc_attr($payment_data['date']); ?>">
                        <input type="hidden" name="montant" value="<?php echo esc_attr($payment_data['montant']); ?>">
                        <input type="hidden" name="reference" value="<?php echo esc_attr($payment_data['reference']); ?>">
                        <input type="hidden" name="MAC" value="<?php echo esc_attr($payment_data['mac']); ?>">
                        <input type="hidden" name="url_retour" value="<?php echo home_url(); ?>">
                        <input type="hidden" name="url_retour_ok" value="<?php echo home_url('/paiement-accepte/'); ?>">
                        <input type="hidden" name="url_retour_err" value="<?php echo home_url('/paiement-annule/'); ?>">
                        <input type="hidden" name="lgue" value="FR">
                        <input type="hidden" name="societe" value="<?php echo esc_attr($payment_data['societe']); ?>">
                        <input type="hidden" name="texte-libre" value="Rendez-vous avec l'Asie">
                        <input type="hidden" name="mail" value="<?php echo esc_attr($payment_data['email']); ?>">
                        
                        <button type="submit" class="payment-btn">
                            <?php _e('Procéder au paiement', 'devis-pro'); ?>
                        </button>
                    </form>
                    
                    <div class="payment-secure">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                        </svg>
                        <?php _e('Paiement sécurisé via Monetico CIC', 'devis-pro'); ?>
                    </div>
                    
                    <div class="payment-logo">
                        <img src="https://www.rdvasie.com/wp-content/uploads/2019/01/paiement-securise.png" alt="Paiement sécurisé">
                    </div>
                </div>
            <?php else : ?>
                <div class="payment-processed">
                    <?php if ($devis->status == 4) : ?>
                        <h2>✅ <?php _e('Paiement déjà effectué', 'devis-pro'); ?></h2>
                        <p><?php _e('Ce devis a déjà été réglé. Merci pour votre confiance !', 'devis-pro'); ?></p>
                    <?php elseif ($devis->status != 1) : ?>
                        <h2><?php _e('Paiement non disponible', 'devis-pro'); ?></h2>
                        <p><?php _e('Ce devis n\'est pas encore prêt pour le paiement. Notre équipe vous contactera bientôt.', 'devis-pro'); ?></p>
                    <?php else : ?>
                        <h2><?php _e('Paiement temporairement indisponible', 'devis-pro'); ?></h2>
                        <p><?php _e('Le système de paiement est en cours de configuration. Veuillez nous contacter pour finaliser votre réservation.', 'devis-pro'); ?></p>
                    <?php endif; ?>
                    
                    <div class="contact-info">
                        <p><?php _e('Pour toute question, contactez-nous :', 'devis-pro'); ?></p>
                        <p>
                            <a href="tel:0214001253">02 14 00 12 53</a><br>
                            <a href="mailto:contact@rdvasie.com">contact@rdvasie.com</a>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
