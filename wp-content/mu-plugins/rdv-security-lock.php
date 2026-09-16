<?php
/**
 * RDV emergency security lock
 */
if (!defined('ABSPATH')) {
    exit;
}

// Force registration closed
add_filter('pre_option_users_can_register', '__return_zero');
add_action('login_init', function () {
    if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'register') {
        wp_safe_redirect(home_url('/'));
        exit;
    }
}, 0);

// Kill XML-RPC hard
add_filter('xmlrpc_enabled', '__return_false', 99);
add_filter('xmlrpc_methods', function () {
    return array();
}, 99);
add_action('init', function () {
    if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
        status_header(403);
        exit('XML-RPC disabled');
    }
}, 0);

// Remove users + app password REST routes
add_filter('rest_endpoints', function ($endpoints) {
    foreach (array_keys($endpoints) as $route) {
        if (strpos($route, '/wp/v2/users') === 0) {
            unset($endpoints[$route]);
        }
    }
    return $endpoints;
}, 99);

// Block creating users unless an admin with create_users is doing it in-dashboard
add_filter('wp_pre_insert_user_data', function ($data, $update) {
    if ($update) {
        return $data;
    }
    if (!is_user_logged_in() || !current_user_can('create_users')) {
        return false;
    }
    // Extra: only allow from wp-admin user-new context roughly
    if (!is_admin() && !(defined('REST_REQUEST') && REST_REQUEST && current_user_can('create_users'))) {
        // Allow only classic admin screens
        $script = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
        if (strpos($script, 'user-new.php') === false && strpos($script, 'users.php') === false) {
            return false;
        }
    }
    return $data;
}, 1, 2);

add_action('user_register', function ($user_id) {
    if (!is_user_logged_in() || !current_user_can('create_users')) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        if (function_exists('wp_delete_user')) {
            wp_delete_user($user_id);
        }
    }
}, 0);
