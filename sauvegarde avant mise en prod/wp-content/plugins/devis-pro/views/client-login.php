<?php
/**
 * Formulaire de connexion à l'espace client
 */

if (!defined('ABSPATH')) {
    exit;
}

// Vérifier si le formulaire a été soumis
$form_submitted = isset($form_submitted) ? $form_submitted : false;
$email_sent = isset($email_sent) ? $email_sent : false;
?>

<style>
.client-login-wrapper {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.client-login {
    max-width: 450px;
    margin: 60px auto;
    padding: 40px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    text-align: center;
}

.client-login h2 {
    font-size: 28px !important;
    color: #333 !important;
    margin: 0 0 10px !important;
    font-weight: 600 !important;
}

.client-login .subtitle {
    color: #666;
    margin: 0 0 30px;
    font-size: 15px;
}

.client-login form {
    text-align: left;
}

.client-login label {
    display: block;
    font-weight: 500;
    margin-bottom: 8px;
    color: #333;
    font-size: 14px;
}

.client-login input[type="email"] {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 16px;
    transition: border-color 0.2s;
    box-sizing: border-box;
}

.client-login input[type="email"]:focus {
    border-color: #de5b09;
    outline: none;
}

.client-login .submit-btn {
    width: 100%;
    background: linear-gradient(135deg, #de5b09 0%, #c44d07 100%);
    color: #fff;
    border: none;
    padding: 16px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 10px;
    cursor: pointer;
    margin-top: 20px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.client-login .submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(222, 91, 9, 0.35);
}

.client-login .info {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
    font-size: 14px;
    color: #666;
}

.client-login .success-message {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

.client-login .error-message {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}
</style>

<div class="client-login-wrapper">
    <div class="client-login">
        <h2><?php _e('Espace client', 'devis-pro'); ?></h2>
        <p class="subtitle"><?php _e('Consultez vos offres de voyage et suivez vos demandes', 'devis-pro'); ?></p>

        <?php if ($form_submitted && $email_sent) : ?>
            <div class="success-message">
                ✅ <?php _e('Si des offres de voyage sont associées à cette adresse email, vous recevrez un lien d\'accès dans quelques instants.', 'devis-pro'); ?>
                <br><small><?php _e('Pensez à vérifier vos spams.', 'devis-pro'); ?></small>
            </div>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field('client_access'); ?>
            <?php Devis_Pro_Security::render_honeypot(); ?>
            
            <label for="client_email"><?php _e('Votre adresse email', 'devis-pro'); ?></label>
            <input type="email" id="client_email" name="client_email" required placeholder="exemple@email.com" value="<?php echo isset($_POST['client_email']) ? esc_attr($_POST['client_email']) : ''; ?>">
            
            <button type="submit" class="submit-btn">
                <?php _e('Recevoir le lien d\'accès', 'devis-pro'); ?>
            </button>
        </form>

        <p class="info">
            <?php _e('Vous recevrez un email avec un lien sécurisé pour accéder à votre espace personnel.', 'devis-pro'); ?>
        </p>
    </div>
</div>
