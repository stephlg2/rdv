<?php
/**
 * Force homepage SEO title + meta (exact). Homepage only.
 */
if (!defined('ABSPATH')) {
    exit;
}

const RDV_HOME_SEO_TITLE = 'Voyage Asie sur mesure : circuits privés & petits groupes | RDV Asie';
const RDV_HOME_SEO_DESC  = 'Agence française spécialisée Asie. Circuits privés et petits groupes au Vietnam, Japon, Inde, Cambodge… Conseillers experts. Devis gratuit sous 48 h.';

add_filter('pre_get_document_title', function ($title) {
    if (is_front_page() && !is_paged()) {
        return RDV_HOME_SEO_TITLE;
    }
    return $title;
}, 99999);

add_filter('wpseo_title', function ($title) {
    if (is_front_page() && !is_paged()) {
        return RDV_HOME_SEO_TITLE;
    }
    return $title;
}, 99999);

add_filter('wpseo_metadesc', function ($desc) {
    if (is_front_page() && !is_paged()) {
        return RDV_HOME_SEO_DESC;
    }
    return $desc;
}, 99999);

add_filter('wpseo_opengraph_title', function ($t) {
    if (is_front_page() && !is_paged()) {
        return RDV_HOME_SEO_TITLE;
    }
    return $t;
}, 99999);

add_filter('wpseo_opengraph_desc', function ($d) {
    if (is_front_page() && !is_paged()) {
        return RDV_HOME_SEO_DESC;
    }
    return $d;
}, 99999);

add_filter('wpseo_twitter_title', function ($t) {
    if (is_front_page() && !is_paged()) {
        return RDV_HOME_SEO_TITLE;
    }
    return $t;
}, 99999);

add_filter('wpseo_twitter_description', function ($d) {
    if (is_front_page() && !is_paged()) {
        return RDV_HOME_SEO_DESC;
    }
    return $d;
}, 99999);

add_filter('wpseo_schema_webpage', function ($data) {
    if (is_front_page() && !is_paged() && is_array($data)) {
        $data['name'] = RDV_HOME_SEO_TITLE;
        $data['description'] = RDV_HOME_SEO_DESC;
    }
    return $data;
}, 99999);

// Critical: Google was using WebSite.name "RDV Asie" as the SERP blue title
add_filter('wpseo_schema_website', function ($data) {
    if (!is_array($data)) {
        return $data;
    }
    $data['name'] = RDV_HOME_SEO_TITLE;
    $data['description'] = RDV_HOME_SEO_DESC;
    return $data;
}, 99999);

add_filter('wpseo_schema_organization', function ($data) {
    if (!is_array($data)) {
        return $data;
    }
    // Keep legal brand, add alternateName matching SEO brand
    $data['alternateName'] = ['RDV Asie', RDV_HOME_SEO_TITLE];
    if (empty($data['description'])) {
        $data['description'] = RDV_HOME_SEO_DESC;
    }
    return $data;
}, 99999);