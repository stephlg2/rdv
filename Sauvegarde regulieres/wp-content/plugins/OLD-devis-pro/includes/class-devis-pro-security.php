<?php
/**
 * Classe de sécurité pour Devis Pro
 * Protection des formulaires et authentification
 */

if (!defined('ABSPATH')) {
    exit;
}

class Devis_Pro_Security {

    private $settings;
    
    // Constantes de sécurité
    const MAX_SUBMISSIONS_PER_HOUR = 5;
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOCKOUT_DURATION = 900; // 15 minutes
    const TOKEN_EXPIRY = 86400; // 24 heures
    
    public function __construct() {
        $this->settings = get_option('devis_pro_settings');
    }

    /**
     * =============================================
     * HONEYPOT - Anti-spam invisible
     * =============================================
     */
    
    /**
     * Générer le champ honeypot (à inclure dans les formulaires)
     */
    public static function render_honeypot() {
        $field_name = self::get_honeypot_field_name();
        ?>
        <div style="position:absolute;left:-9999px;top:-9999px;opacity:0;height:0;width:0;overflow:hidden;" aria-hidden="true">
            <label for="<?php echo esc_attr($field_name); ?>">Ne pas remplir ce champ</label>
            <input type="text" name="<?php echo esc_attr($field_name); ?>" id="<?php echo esc_attr($field_name); ?>" value="" tabindex="-1" autocomplete="off">
        </div>
        <?php
    }
    
    /**
     * Obtenir le nom du champ honeypot (change selon le jour pour éviter le fingerprinting)
     */
    private static function get_honeypot_field_name() {
        $base = 'website_url_' . substr(md5(date('Y-m-d') . wp_salt()), 0, 8);
        return $base;
    }
    
    /**
     * Vérifier si le honeypot a été rempli (si oui = bot)
     */
    public static function check_honeypot($post_data) {
        $field_name = self::get_honeypot_field_name();
        
        // Si le champ honeypot est rempli, c'est un bot
        if (isset($post_data[$field_name]) && !empty($post_data[$field_name])) {
            self::log_security_event('honeypot_triggered', array(
                'ip' => self::get_client_ip(),
                'value' => $post_data[$field_name]
            ));
            return false; // Bot détecté
        }
        
        return true; // OK
    }

    /**
     * =============================================
     * RATE LIMITING - Limite les soumissions par IP
     * =============================================
     */
    
    /**
     * Vérifier le rate limiting
     */
    public static function check_rate_limit($action = 'form_submit') {
        $ip = self::get_client_ip();
        $transient_key = 'rdv_rate_' . $action . '_' . md5($ip);
        
        $attempts = get_transient($transient_key);
        
        if ($attempts === false) {
            $attempts = 0;
        }
        
        if ($attempts >= self::MAX_SUBMISSIONS_PER_HOUR) {
            self::log_security_event('rate_limit_exceeded', array(
                'ip' => $ip,
                'action' => $action,
                'attempts' => $attempts
            ));
            return false;
        }
        
        // Incrémenter le compteur
        set_transient($transient_key, $attempts + 1, HOUR_IN_SECONDS);
        
        return true;
    }
    
    /**
     * Réinitialiser le rate limit pour une IP (après succès par exemple)
     */
    public static function reset_rate_limit($action = 'form_submit') {
        $ip = self::get_client_ip();
        $transient_key = 'rdv_rate_' . $action . '_' . md5($ip);
        delete_transient($transient_key);
    }

    /**
     * =============================================
     * PROTECTION BRUTE FORCE - Espace client
     * =============================================
     */
    
    /**
     * Vérifier si l'IP est bloquée
     */
    public static function is_ip_blocked() {
        $ip = self::get_client_ip();
        $lockout_key = 'rdv_lockout_' . md5($ip);
        
        $lockout = get_transient($lockout_key);
        
        if ($lockout !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Enregistrer une tentative d'accès échouée
     */
    public static function record_failed_attempt($action = 'client_login') {
        $ip = self::get_client_ip();
        $attempts_key = 'rdv_attempts_' . $action . '_' . md5($ip);
        
        $attempts = get_transient($attempts_key);
        
        if ($attempts === false) {
            $attempts = 0;
        }
        
        $attempts++;
        
        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            // Bloquer l'IP
            $lockout_key = 'rdv_lockout_' . md5($ip);
            set_transient($lockout_key, true, self::LOCKOUT_DURATION);
            
            self::log_security_event('ip_blocked', array(
                'ip' => $ip,
                'action' => $action,
                'attempts' => $attempts
            ));
            
            // Réinitialiser le compteur
            delete_transient($attempts_key);
        } else {
            set_transient($attempts_key, $attempts, self::LOCKOUT_DURATION);
        }
    }
    
    /**
     * Réinitialiser les tentatives après succès
     */
    public static function reset_failed_attempts($action = 'client_login') {
        $ip = self::get_client_ip();
        $attempts_key = 'rdv_attempts_' . $action . '_' . md5($ip);
        delete_transient($attempts_key);
    }

    /**
     * =============================================
     * TOKENS SÉCURISÉS - Accès espace client
     * =============================================
     */
    
    /**
     * Générer un token d'accès sécurisé
     */
    public static function generate_access_token($email) {
        // Token unique avec composants aléatoires
        $random = bin2hex(random_bytes(16));
        $timestamp = time();
        $token_data = $email . $random . $timestamp . wp_salt('auth');
        
        $token = hash('sha256', $token_data);
        
        // Stocker le token avec expiration
        $token_key = 'rdv_token_' . $token;
        set_transient($token_key, array(
            'email' => $email,
            'created' => $timestamp,
            'ip' => self::get_client_ip()
        ), self::TOKEN_EXPIRY);
        
        return $token;
    }
    
    /**
     * Valider un token d'accès
     */
    public static function validate_access_token($token, $email) {
        if (empty($token) || strlen($token) !== 64) {
            return false;
        }
        
        $token = sanitize_text_field($token);
        $token_key = 'rdv_token_' . $token;
        
        $token_data = get_transient($token_key);
        
        if ($token_data === false) {
            self::record_failed_attempt('token_validation');
            return false;
        }
        
        // Vérifier que l'email correspond
        if ($token_data['email'] !== $email) {
            self::record_failed_attempt('token_validation');
            return false;
        }
        
        // Token valide - réinitialiser les tentatives
        self::reset_failed_attempts('token_validation');
        
        return true;
    }
    
    /**
     * Invalider un token (après utilisation ou déconnexion)
     */
    public static function invalidate_token($token) {
        $token_key = 'rdv_token_' . sanitize_text_field($token);
        delete_transient($token_key);
    }

    /**
     * =============================================
     * VALIDATION DES DONNÉES
     * =============================================
     */
    
    /**
     * Valider et nettoyer un numéro de téléphone
     */
    public static function validate_phone($phone) {
        // Supprimer tout sauf chiffres et +
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);
        
        // Vérifier la longueur (min 10 chiffres)
        if (strlen(preg_replace('/[^0-9]/', '', $cleaned)) < 10) {
            return false;
        }
        
        return $cleaned;
    }
    
    /**
     * Valider un nom/prénom (pas de caractères suspects)
     */
    public static function validate_name($name) {
        // Supprimer les balises HTML
        $cleaned = wp_strip_all_tags($name);
        
        // Vérifier les caractères suspects (injection SQL, XSS)
        if (preg_match('/[<>{}|\[\]\\\\]/', $cleaned)) {
            return false;
        }
        
        // Longueur raisonnable
        if (strlen($cleaned) < 2 || strlen($cleaned) > 50) {
            return false;
        }
        
        return sanitize_text_field($cleaned);
    }
    
    /**
     * Valider un code postal français
     */
    public static function validate_postal_code($cp) {
        $cleaned = preg_replace('/[^0-9]/', '', $cp);
        
        // Code postal français = 5 chiffres
        if (strlen($cleaned) !== 5) {
            return false;
        }
        
        return $cleaned;
    }
    
    /**
     * Valider un email de manière stricte
     */
    public static function validate_email($email) {
        $email = sanitize_email($email);
        
        if (!is_email($email)) {
            return false;
        }
        
        // Vérifier que le domaine existe (DNS MX)
        $domain = substr($email, strpos($email, '@') + 1);
        if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
            return false;
        }
        
        // Liste noire de domaines jetables (optionnel)
        $disposable_domains = array(
            'tempmail.com', 'throwaway.email', 'guerrillamail.com',
            'mailinator.com', 'yopmail.com', '10minutemail.com'
        );
        
        if (in_array($domain, $disposable_domains)) {
            return false;
        }
        
        return $email;
    }
    
    /**
     * Nettoyer un message (anti-XSS)
     */
    public static function sanitize_message($message) {
        // Supprimer les balises HTML
        $cleaned = wp_strip_all_tags($message);
        
        // Échapper les caractères spéciaux
        $cleaned = htmlspecialchars($cleaned, ENT_QUOTES, 'UTF-8');
        
        // Limiter la longueur
        if (strlen($cleaned) > 5000) {
            $cleaned = substr($cleaned, 0, 5000);
        }
        
        return $cleaned;
    }

    /**
     * =============================================
     * VÉRIFICATION GLOBALE DU FORMULAIRE
     * =============================================
     */
    
    /**
     * Effectuer toutes les vérifications de sécurité
     */
    public static function validate_form_submission($post_data) {
        $errors = array();
        
        // 1. Vérifier si l'IP est bloquée
        if (self::is_ip_blocked()) {
            return array(
                'valid' => false,
                'error' => __('Trop de tentatives. Veuillez réessayer dans 15 minutes.', 'devis-pro')
            );
        }
        
        // 2. Vérifier le honeypot
        if (!self::check_honeypot($post_data)) {
            // Retourner un succès faux pour ne pas alerter le bot
            return array(
                'valid' => false,
                'error' => '',
                'is_bot' => true
            );
        }
        
        // 3. Vérifier le rate limiting
        if (!self::check_rate_limit('form_submit')) {
            return array(
                'valid' => false,
                'error' => __('Vous avez soumis trop de demandes. Veuillez réessayer plus tard.', 'devis-pro')
            );
        }
        
        // 4. Valider l'email
        if (!empty($post_data['email'])) {
            $email = self::validate_email($post_data['email']);
            if ($email === false) {
                $errors['email'] = __('Adresse email invalide', 'devis-pro');
            }
        }
        
        // 5. Valider le téléphone
        if (!empty($post_data['tel'])) {
            $phone = self::validate_phone($post_data['tel']);
            if ($phone === false) {
                $errors['tel'] = __('Numéro de téléphone invalide', 'devis-pro');
            }
        }
        
        // 6. Valider le nom et prénom
        if (!empty($post_data['nom'])) {
            $nom = self::validate_name($post_data['nom']);
            if ($nom === false) {
                $errors['nom'] = __('Nom invalide', 'devis-pro');
            }
        }
        
        if (!empty($post_data['prenom'])) {
            $prenom = self::validate_name($post_data['prenom']);
            if ($prenom === false) {
                $errors['prenom'] = __('Prénom invalide', 'devis-pro');
            }
        }
        
        // 7. Valider le code postal (si fourni)
        if (!empty($post_data['cp'])) {
            $cp = self::validate_postal_code($post_data['cp']);
            if ($cp === false) {
                $errors['cp'] = __('Code postal invalide', 'devis-pro');
            }
        }
        
        if (!empty($errors)) {
            return array(
                'valid' => false,
                'errors' => $errors,
                'error' => implode(', ', $errors)
            );
        }
        
        return array('valid' => true);
    }

    /**
     * =============================================
     * RECAPTCHA (Optionnel)
     * =============================================
     */
    
    /**
     * Vérifier reCAPTCHA v3
     */
    public static function verify_recaptcha($token, $secret_key) {
        if (empty($secret_key)) {
            return true; // reCAPTCHA non configuré
        }
        
        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
            'body' => array(
                'secret' => $secret_key,
                'response' => $token,
                'remoteip' => self::get_client_ip()
            )
        ));
        
        if (is_wp_error($response)) {
            return true; // En cas d'erreur, on laisse passer
        }
        
        $result = json_decode(wp_remote_retrieve_body($response), true);
        
        // Score minimum de 0.5 pour reCAPTCHA v3
        if ($result['success'] && isset($result['score']) && $result['score'] >= 0.5) {
            return true;
        }
        
        self::log_security_event('recaptcha_failed', array(
            'ip' => self::get_client_ip(),
            'score' => $result['score'] ?? 0
        ));
        
        return false;
    }

    /**
     * =============================================
     * UTILITAIRES
     * =============================================
     */
    
    /**
     * Obtenir l'IP du client (avec gestion des proxies)
     */
    public static function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            // Cloudflare
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Proxy
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // Valider l'IP
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Logger un événement de sécurité
     */
    private static function log_security_event($event, $data = array()) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $log_data = array_merge(array(
            'event' => $event,
            'timestamp' => current_time('mysql'),
            'ip' => self::get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ), $data);
        
        error_log('[Devis Pro Security] ' . json_encode($log_data));
    }
    
    /**
     * Ajouter les headers de sécurité
     */
    public static function add_security_headers() {
        if (headers_sent()) {
            return;
        }
        
        // Protection XSS
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        
        // Protection clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}

