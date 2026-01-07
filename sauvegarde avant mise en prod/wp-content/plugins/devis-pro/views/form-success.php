<?php
/**
 * Message de confirmation après envoi du formulaire
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<style>
.devis-success-sidebar {
    text-align: center;
    padding: 25px 15px;
}

.devis-success-sidebar .success-icon {
    width: 70px;
    height: 70px;
    background: #de5b09;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.devis-success-sidebar .success-icon svg {
    width: 35px;
    height: 35px;
    fill: #fff;
}

.devis-success-sidebar h3 {
    font-size: 20px !important;
    color: #333 !important;
    margin: 0 0 12px !important;
    font-weight: 700 !important;
}

.devis-success-sidebar .main-text {
    color: #666;
    font-size: 14px;
    line-height: 1.5;
    margin: 0 0 20px;
}

.devis-success-sidebar .email-info {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 15px;
    border-top: 1px solid #eee;
    font-size: 13px;
    color: #de5b09;
    font-weight: 500;
}

.devis-success-sidebar .email-info .check-icon {
    width: 18px;
    height: 18px;
    background: #28a745;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.devis-success-sidebar .email-info .check-icon svg {
    width: 10px;
    height: 10px;
    fill: #fff;
}

.devis-success-sidebar .email-subtext {
    font-size: 12px;
    color: #999;
    margin-top: 8px;
}

.devis-success-sidebar .close-btn {
    display: inline-block;
    background: #de5b09;
    color: #fff;
    border: none;
    padding: 12px 40px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
    margin-top: 20px;
    transition: all 0.2s ease;
}

.devis-success-sidebar .close-btn:hover {
    background: #c44d07;
    transform: translateY(-1px);
}
</style>

<div class="devis-success-sidebar">
    <div class="success-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
        </svg>
    </div>
    
    <h3>Demande envoyée !</h3>
    
    <p class="main-text">
        Merci pour votre demande. Notre équipe va l'étudier et vous recontactera très rapidement.
    </p>
    
    <div class="email-info">
        <span class="check-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
            </svg>
        </span>
        Confirmation envoyée
    </div>
    <p class="email-subtext">Vérifiez votre boîte mail (et vos spams).</p>
    
    <button type="button" class="close-btn" onclick="document.querySelector('.tripzzy-drawer__close').click();">OK</button>
</div>
