<?php
/**
 * Kill unauthorized Google Search Console HTML verification files.
 * Whitelist only the known legitimate file.
 */
if (!defined('ABSPATH')) {
    exit;
}

const RDV_GSC_ALLOW = [
    'google101bac414596211f.html',
];

add_action('plugins_loaded', function () {
    $root = ABSPATH;
    foreach (glob($root . 'google*.html') ?: [] as $file) {
        $base = basename($file);
        if (!in_array($base, RDV_GSC_ALLOW, true)) {
            @unlink($file);
            error_log('[rdv-protect-gsc] removed unauthorized verify file: ' . $base);
        }
    }
}, 0);

// Also run on shutdown in case dropped mid-request
add_action('shutdown', function () {
    $root = ABSPATH;
    foreach (glob($root . 'google*.html') ?: [] as $file) {
        $base = basename($file);
        if (!in_array($base, RDV_GSC_ALLOW, true)) {
            @unlink($file);
        }
    }
}, 0);
