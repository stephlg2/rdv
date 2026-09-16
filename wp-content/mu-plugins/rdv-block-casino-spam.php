<?php
if (!defined('ABSPATH')) exit;

add_filter('wp_insert_post_data', function ($data, $postarr) {
    if (($data['post_type'] ?? '') !== 'post') return $data;
    $hay = strtolower(($data['post_title'] ?? '') . ' ' . ($data['post_name'] ?? '') . ' ' . ($data['post_content'] ?? ''));
    $bad = ['casino','jackpot','blackjack','roulette','neteller','skrill','cashlib','bookmaker','paris sportif','tours gratuits','sans dépôt','argent réel','bonus casino'];
    foreach ($bad as $b) {
        if (strpos($hay, $b) !== false) {
            $data['post_status'] = 'trash';
            error_log('[rdv-block-casino] blocked: ' . ($data['post_title'] ?? ''));
            break;
        }
    }
    // Block ghost high author IDs
    if (!empty($data['post_author']) && (int)$data['post_author'] >= 1000) {
        $data['post_author'] = 1;
        $data['post_status'] = 'trash';
        error_log('[rdv-block-casino] blocked ghost author');
    }
    return $data;
}, 1, 2);
