<?php
/**
 * Message de confirmation après envoi du formulaire
 * Version sidebar - compact
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<style>
.devis-success-sidebar {
    text-align: center;
    padding: 30px 20px;
    background: #f6efe6;
    border-radius: 12px;
    border: 1px solid #cecac1;
}

.devis-success-sidebar .success-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #de5b09 0%, #ff7a2e 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    box-shadow: 0 4px 15px rgba(222, 91, 9, 0.3);
}

.devis-success-sidebar .success-icon svg {
    width: 30px;
    height: 30px;
    fill: #fff;
}

.devis-success-sidebar h3 {
    font-size: 22px !important;
    color: #333 !important;
    margin: 0 0 15px !important;
    font-weight: 600 !important;
}

.devis-success-sidebar p {
    color: #555;
    font-size: 14px;
    line-height: 1.6;
    margin: 0 0 12px;
}

.devis-success-sidebar p:last-of-type {
    margin-bottom: 0;
}

.devis-success-sidebar .email-info {
    background: #fff;
    border: 1px solid #cecac1;
    border-radius: 8px;
    padding: 12px;
    margin-top: 15px;
    font-size: 13px;
    color: #555;
}

.devis-success-sidebar .email-info strong {
    display: block;
    margin-bottom: 5px;
    color: #de5b09;
}
</style>

<div class="devis-success-sidebar">
    <div class="success-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
        </svg>
    </div>
    
    <h3><?php _e('Demande envoyée !', 'devis-pro'); ?></h3>
    
    <p>
        <?php _e('Merci pour votre demande. Notre équipe va l\'étudier et vous recontactera très rapidement.', 'devis-pro'); ?>
    </p>
    
    <div class="email-info">
        <strong>📧 <?php _e('Confirmation envoyée', 'devis-pro'); ?></strong>
        <?php _e('Vérifiez votre boîte mail (et vos spams).', 'devis-pro'); ?>
    </div>
</div>

