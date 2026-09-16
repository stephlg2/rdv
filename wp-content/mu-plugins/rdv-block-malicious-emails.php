<?php
/**
 * Block known malicious / disposable attack email domains (e.g. smaqt.com GSC hijack bots).
 */
if (!defined('ABSPATH')) {
    exit;
}

function rdv_malicious_email_domains() {
    return apply_filters('rdv_malicious_email_domains', [
        'smaqt.com',
        'smaqt.net',
        'smaqt.org',
    ]);
}

function rdv_email_is_malicious($email) {
    $email = strtolower(trim((string) $email));
    if ($email === '' || strpos($email, '@') === false) {
        return false;
    }
    $domain = substr(strrchr($email, '@'), 1);
    return in_array($domain, rdv_malicious_email_domains(), true);
}

function rdv_reject_malicious_email($email) {
    if (rdv_email_is_malicious($email)) {
        return new WP_Error(
            'rdv_blocked_email_domain',
            'Cette adresse e-mail n’est pas autorisée.'
        );
    }
    return true;
}

// WP registration / profile email
add_filter('registration_errors', function ($errors, $sanitized_user_login, $user_email) {
    $check = rdv_reject_malicious_email($user_email);
    if (is_wp_error($check)) {
        $errors->add($check->get_error_code(), $check->get_error_message());
    }
    return $errors;
}, 10, 3);

add_action('user_profile_update_errors', function ($errors, $update, $user) {
    if (!empty($user->user_email) && rdv_email_is_malicious($user->user_email)) {
        $errors->add('rdv_blocked_email_domain', 'Cette adresse e-mail n’est pas autorisée.');
    }
}, 10, 3);

// Comments
add_filter('pre_comment_approved', function ($approved, $commentdata) {
    if (!empty($commentdata['comment_author_email']) && rdv_email_is_malicious($commentdata['comment_author_email'])) {
        return 'spam';
    }
    return $approved;
}, 10, 2);

// Contact Form 7
add_filter('wpcf7_validate_email', 'rdv_cf7_block_malicious_email', 20, 2);
add_filter('wpcf7_validate_email*', 'rdv_cf7_block_malicious_email', 20, 2);
function rdv_cf7_block_malicious_email($result, $tag) {
    $name = $tag->name;
    $value = isset($_POST[$name]) ? wp_unslash($_POST[$name]) : '';
    if (rdv_email_is_malicious($value)) {
        $result->invalidate($tag, 'Cette adresse e-mail n’est pas autorisée.');
    }
    return $result;
}

// Devis Pro / generic POST email fields
add_action('init', function () {
    if (empty($_POST) || is_admin()) {
        return;
    }
    $candidates = [];
    foreach (['email', 'your-email', 'mail', 'e-mail', 'user_email', 'devis_email', 'contact_email'] as $k) {
        if (!empty($_POST[$k]) && is_string($_POST[$k])) {
            $candidates[] = $_POST[$k];
        }
    }
    // nested arrays (devis forms)
    foreach ($_POST as $v) {
        if (is_array($v) && !empty($v['email']) && is_string($v['email'])) {
            $candidates[] = $v['email'];
        }
    }
    foreach ($candidates as $email) {
        if (rdv_email_is_malicious($email)) {
            status_header(403);
            wp_die('Cette adresse e-mail n’est pas autorisée.', 'Accès refusé', ['response' => 403]);
        }
    }
}, 0);

// Block wp_mail TO/FROM these domains (defense in depth)
add_filter('pre_wp_mail', function ($null, $atts) {
    $to = isset($atts['to']) ? (array) $atts['to'] : [];
    foreach ($to as $addr) {
        if (rdv_email_is_malicious($addr)) {
            return false;
        }
    }
    return $null;
}, 10, 2);
