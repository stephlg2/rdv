<?php
/**
 * Dashboard espace client
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('devis_pro_settings');
?>

<style>
.client-dashboard {
    max-width: 900px;
    margin: 40px auto;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.client-header {
    text-align: center;
    margin-bottom: 40px;
}

.client-header h1 {
    font-size: 32px;
    color: #333;
    margin: 0 0 10px;
}

.client-header p {
    color: #666;
    font-size: 16px;
}

.devis-list-client {
    display: grid;
    gap: 20px;
}

.devis-card-client {
    background: #fff;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 20px;
    align-items: center;
}

@media (max-width: 600px) {
    .devis-card-client {
        grid-template-columns: 1fr;
    }
}

.devis-info h3 {
    font-size: 18px;
    color: #333;
    margin: 0 0 10px;
}

.devis-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    color: #666;
    font-size: 14px;
}

.devis-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.devis-actions-client {
    text-align: right;
}

.devis-status-badge {
    display: inline-block;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    color: #fff;
    margin-bottom: 10px;
}

.devis-amount {
    font-size: 24px;
    font-weight: 700;
    color: #333;
}

.pay-btn {
    display: inline-block;
    background: linear-gradient(135deg, #de5b09 0%, #c44d07 100%);
    color: #fff;
    padding: 12px 25px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    margin-top: 10px;
    transition: transform 0.2s;
}

.pay-btn:hover {
    transform: translateY(-2px);
    color: #fff;
}

.no-devis {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.no-devis p {
    color: #666;
    font-size: 18px;
}

.no-devis a {
    display: inline-block;
    background: #de5b09;
    color: #fff;
    padding: 14px 30px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    margin-top: 20px;
}

.contact-box {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    margin-top: 40px;
}

.contact-box h3 {
    margin: 0 0 10px;
    color: #333;
}

.contact-box p {
    color: #666;
    margin: 0;
}

.contact-box a {
    color: #de5b09;
    text-decoration: none;
}

.view-voyage-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #de5b09;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    margin-top: 0;
    transition: color 0.2s;
}

.view-voyage-link:hover {
    color: #c44d07;
    text-decoration: underline;
}

.view-voyage-link svg {
    width: 14px;
    height: 14px;
}

.download-invoice-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #28a745;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: color 0.2s;
}

.download-invoice-link:hover {
    color: #1e7e34;
    text-decoration: underline;
}

.download-invoice-link svg {
    width: 14px;
    height: 14px;
}

.devis-links {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 15px;
    margin-top: 12px;
}
</style>

<div class="client-dashboard">
    <div class="client-header">
        <h1><?php _e('Vos devis', 'devis-pro'); ?></h1>
        <p><?php echo esc_html($email); ?></p>
    </div>

    <?php if (!empty($devis_list)) : ?>
        <div class="devis-list-client">
            <?php foreach ($devis_list as $devis) : 
                $status = $settings['statuses'][$devis->status] ?? array('label' => 'En cours', 'color' => '#6c757d');
                
                // Récupérer le titre et l'URL du voyage
                $voyage_ids = explode("-;-", $devis->voyage);
                $voyage_title = '';
                $voyage_url = '';
                foreach ($voyage_ids as $id) {
                    if (!empty($id) && is_numeric($id)) {
                        $title = get_the_title($id);
                        if ($title) {
                            $voyage_title = $title;
                            $voyage_url = get_permalink($id);
                            break;
                        }
                    }
                }
                if (empty($voyage_title)) {
                    $voyage_title = $devis->destination ?: __('Voyage en Asie', 'devis-pro');
                }
            ?>
                <div class="devis-card-client">
                    <div class="devis-info">
                        <h3><?php echo esc_html($voyage_title); ?></h3>
                        <div class="devis-meta">
                            <span>
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1z"/>
                                    <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                                </svg>
                                <?php echo date('d/m/Y', strtotime($devis->demande)); ?>
                            </span>
                            <?php if ($devis->depart) : ?>
                            <span>
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M6 3a.5.5 0 0 1 .5.5v2h4a.5.5 0 0 1 0 1h-4v2a.5.5 0 0 1-1 0V3.5A.5.5 0 0 1 6 3z"/>
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                </svg>
                                <?php _e('Départ', 'devis-pro'); ?> : <?php echo esc_html($devis->depart); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="devis-links">
                            <?php if ($voyage_url) : ?>
                            <a href="<?php echo esc_url($voyage_url); ?>" target="_blank" class="view-voyage-link">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                <?php _e('Voir le voyage', 'devis-pro'); ?>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($devis->status == 4 && $devis->montant > 0 && $devis->token) : ?>
                            <a href="<?php echo home_url('/?facture=1&token=' . $devis->token); ?>" target="_blank" class="download-invoice-link">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <?php _e('Télécharger ma facture', 'devis-pro'); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="devis-actions-client">
                        <span class="devis-status-badge" style="background: <?php echo esc_attr($status['color']); ?>">
                            <?php echo esc_html($status['label']); ?>
                        </span>
                        
                        <?php if ($devis->montant > 0) : ?>
                            <div class="devis-amount">
                                <?php echo number_format($devis->montant, 0, ',', ' '); ?> €
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($devis->status == 1 && $devis->montant > 0 && $devis->token) : ?>
                            <a href="<?php echo home_url('/paiement/?q=' . $devis->token); ?>" class="pay-btn">
                                <?php _e('Payer maintenant', 'devis-pro'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="no-devis">
            <p><?php _e('Vous n\'avez pas encore de devis.', 'devis-pro'); ?></p>
            <a href="<?php echo home_url('/demande-de-devis/'); ?>">
                <?php _e('Demander un devis', 'devis-pro'); ?>
            </a>
        </div>
    <?php endif; ?>

    <div class="contact-box">
        <h3><?php _e('Une question ?', 'devis-pro'); ?></h3>
        <p>
            <?php _e('Notre équipe est à votre disposition', 'devis-pro'); ?><br>
            <a href="tel:0272644034">02 72 64 40 34</a> • 
            <a href="mailto:contact@rdvasie.com">contact@rdvasie.com</a>
        </p>
    </div>
</div>

