<?php
/**
 * Remplace le shortcode [rdvasie_reviews] (plugin custom figé) par le widget
 * Trustindex déjà utilisé sur l'accueil — synchronisé avec Google.
 *
 * @package Avada-Child-Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RDV_TRUSTINDEX_CSS_URL', 'https://www.rdvasie.com/wp-content/uploads/trustindex-google-widget.css' );
define( 'RDV_TRUSTINDEX_TEMPLATE_ID', 'trustindex-google-widget-html-avis' );

add_action( 'plugins_loaded', 'rdv_reviews_override_shortcode', 20 );
/**
 * Priorité 20 : après le plugin rdvasie-google-reviews.
 */
function rdv_reviews_override_shortcode() {
	remove_shortcode( 'rdvasie_reviews' );
	add_shortcode( 'rdvasie_reviews', 'rdv_reviews_trustindex_shortcode' );
}

/**
 * Shortcode : widget Trustindex (même source que la page d'accueil).
 *
 * @param array $atts Attributs shortcode (ignorés — compatibilité).
 * @return string
 */
function rdv_reviews_trustindex_shortcode( $atts = array() ) {
	rdv_reviews_trustindex_enqueue_assets();

	$embed_path = get_stylesheet_directory() . '/trustindex-reviews-embed.html';
	if ( ! is_readable( $embed_path ) ) {
		return '<!-- rdv-reviews: trustindex embed missing -->';
	}

	$embed = file_get_contents( $embed_path );
	if ( ! is_string( $embed ) || '' === $embed ) {
		return '<!-- rdv-reviews: trustindex embed empty -->';
	}

	$embed = str_replace(
		'trustindex-google-widget-html',
		RDV_TRUSTINDEX_TEMPLATE_ID,
		$embed
	);

	$css_url = RDV_TRUSTINDEX_CSS_URL;
	if ( false === strpos( $css_url, '?' ) ) {
		$css_url .= '?rdv=' . filemtime( $embed_path );
	}

	$embed = preg_replace(
		'/data-css-url="[^"]*"/',
		'data-css-url="' . esc_url( $css_url ) . '"',
		$embed,
		1
	);

	$embed = rdv_reviews_sort_trustindex_html( $embed );
	return rdv_reviews_humanize_review_dates_html( $embed );
}

/**
 * Charge le script Trustindex une seule fois.
 */
function rdv_reviews_trustindex_enqueue_assets() {
	static $enqueued = false;
	if ( $enqueued ) {
		return;
	}
	$enqueued = true;

	wp_enqueue_script(
		'trustindex-loader',
		'https://cdn.trustindex.io/loader.js',
		array(),
		'1.0',
		array(
			'strategy'  => 'async',
			'in_footer' => true,
		)
	);
}

add_action( 'wp_enqueue_scripts', 'rdv_reviews_trustindex_maybe_enqueue', 25 );
/**
 * Pré-charge le script si la page contient le shortcode (hors menu).
 */
function rdv_reviews_trustindex_maybe_enqueue() {
	if ( ! is_singular() ) {
		return;
	}

	global $post;
	if ( $post && has_shortcode( $post->post_content, 'rdvasie_reviews' ) ) {
		rdv_reviews_trustindex_enqueue_assets();
	}
}

add_filter( 'do_shortcode_tag', 'rdv_reviews_sort_shortcode_output', 25, 4 );
/**
 * Trie les avis du plus récent au plus ancien (plugin rdvasie ou Trustindex).
 *
 * @param string $output HTML du shortcode.
 * @param string $tag    Nom du shortcode.
 * @return string
 */
function rdv_reviews_sort_shortcode_output( $output, $tag, $attr, $m ) {
	if ( 'rdvasie_reviews' !== $tag ) {
		return $output;
	}
	if ( false !== strpos( $output, 'rdvasie-review-card' ) ) {
		$output = rdv_reviews_sort_rdvasie_html( $output );
	}
	if ( false !== strpos( $output, 'ti-review-item' ) ) {
		$output = rdv_reviews_sort_trustindex_html( $output );
	}
	return rdv_reviews_humanize_review_dates_html( $output );
}

/**
 * Convertit un libellé d'avis (absolu ou relatif) en secondes écoulées.
 * Plus la valeur est faible, plus l'avis est récent.
 *
 * @param string $text Date brute.
 * @return int
 */
function rdv_reviews_parse_review_text_to_seconds_ago( $text ) {
	$text = trim( wp_strip_all_tags( $text ) );
	if ( '' === $text ) {
		return PHP_INT_MAX;
	}

	if ( preg_match( '/^(\d{2})\/(\d{2})\/(\d{4})$/', $text, $m ) ) {
		$ts = gmmktime( 0, 0, 0, (int) $m[2], (int) $m[1], (int) $m[3] );
		return max( 0, time() - $ts );
	}

	$lower = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
	$lower = str_replace( array( '’', '´', '`' ), "'", $lower );

	if ( false !== strpos( $lower, "aujourd'hui" ) ) {
		return 12 * HOUR_IN_SECONDS;
	}
	if ( false !== strpos( $lower, 'hier' ) ) {
		return DAY_IN_SECONDS;
	}
	if ( preg_match( '/instant|à l\'instant|a l\'instant/u', $lower ) ) {
		return MINUTE_IN_SECONDS;
	}

	if ( preg_match(
		'/il y a\s+(?:environ\s+)?(?:(\d+)|(?:un|une))\s*(minute|minutes|min|heure|heures|jour|jours|semaine|semaines|mois|an|ans|année|années)/u',
		$lower,
		$m
	) ) {
		$n    = '' !== $m[1] ? (int) $m[1] : 1;
		$unit = $m[2];

		if ( preg_match( '/minute|min/u', $unit ) ) {
			return $n * MINUTE_IN_SECONDS;
		}
		if ( preg_match( '/heure/u', $unit ) ) {
			return $n * HOUR_IN_SECONDS;
		}
		if ( preg_match( '/jour/u', $unit ) ) {
			return $n * DAY_IN_SECONDS;
		}
		if ( preg_match( '/semaine/u', $unit ) ) {
			return $n * WEEK_IN_SECONDS;
		}
		if ( preg_match( '/mois/u', $unit ) ) {
			return $n * 30 * DAY_IN_SECONDS;
		}
		if ( preg_match( '/an|année/u', $unit ) ) {
			return $n * YEAR_IN_SECONDS;
		}
	}

	return PHP_INT_MAX;
}

/**
 * Formate une ancienneté en libellé Google (« il y a 2 semaines »).
 *
 * @param int $seconds_ago Secondes écoulées.
 * @return string
 */
function rdv_reviews_seconds_ago_to_relative_fr( $seconds_ago ) {
	$units = array(
		array( 'an', 'ans', YEAR_IN_SECONDS ),
		array( 'mois', 'mois', 30 * DAY_IN_SECONDS ),
		array( 'semaine', 'semaines', WEEK_IN_SECONDS ),
		array( 'jour', 'jours', DAY_IN_SECONDS ),
		array( 'heure', 'heures', HOUR_IN_SECONDS ),
		array( 'minute', 'minutes', MINUTE_IN_SECONDS ),
	);

	if ( $seconds_ago < MINUTE_IN_SECONDS ) {
		return "à l'instant";
	}

	foreach ( $units as $unit ) {
		$count = (int) floor( $seconds_ago / $unit[2] );
		if ( $count >= 1 ) {
			return sprintf( 'il y a %d %s', $count, $count > 1 ? $unit[1] : $unit[0] );
		}
	}

	return "à l'instant";
}

/**
 * Remplace les dates absolues par un libellé relatif façon Google.
 *
 * @param string $html HTML des avis.
 * @return string
 */
function rdv_reviews_humanize_review_dates_html( $html ) {
	return preg_replace_callback(
		'/(<(?:span|div)[^>]*class="(?:rdvasie-review-date|ti-date)"[^>]*>)\s*([^<]+?)(\s*<\/(?:span|div)>)/',
		function ( $matches ) {
			$raw = trim( $matches[2] );
			if ( ! preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', $raw ) ) {
				return $matches[0];
			}

			$relative = rdv_reviews_seconds_ago_to_relative_fr(
				rdv_reviews_parse_review_text_to_seconds_ago( $raw )
			);

			return $matches[1] . esc_html( $relative ) . $matches[3];
		},
		$html
	);
}

/**
 * Extrait l'ancienneté d'une carte HTML via la classe de la date.
 *
 * @param string $html  Fragment HTML.
 * @param string $class Classe CSS de l'élément date.
 * @return int
 */
function rdv_reviews_extract_age_seconds_from_html( $html, $class ) {
	$pattern = '/<(?:span|div)[^>]*class="' . preg_quote( $class, '/' ) . '"[^>]*>\s*([^<]+)/';
	if ( preg_match( $pattern, $html, $m ) ) {
		return rdv_reviews_parse_review_text_to_seconds_ago( $m[1] );
	}
	return PHP_INT_MAX;
}

/**
 * Trie les cartes du plugin rdvasie-google-reviews.
 *
 * @param string $html HTML complet du shortcode.
 * @return string
 */
function rdv_reviews_sort_rdvasie_html( $html ) {
	if ( false === strpos( $html, 'rdvasie-review-card' ) ) {
		return $html;
	}

	$card_pattern = '/<div class="rdvasie-review-card">.*?<div class="rdvasie-review-text">.*?<\/div>\s*<\/div>/s';
	if ( ! preg_match_all( $card_pattern, $html, $matches, PREG_OFFSET_CAPTURE ) ) {
		return $html;
	}

	$cards = $matches[0];
	if ( count( $cards ) < 2 ) {
		return $html;
	}

	// Plage document à remplacer : min/max AVANT le tri
	// (sinon usort mélange les offsets et on duplique les cartes).
	$first_offset = $cards[0][1];
	$last_end     = 0;
	foreach ( $cards as $card ) {
		$first_offset = min( $first_offset, $card[1] );
		$last_end     = max( $last_end, $card[1] + strlen( $card[0] ) );
	}

	// Dédupliquer au cas où le HTML source contient déjà des copies.
	$unique = array();
	$seen   = array();
	foreach ( $cards as $card ) {
		$key = md5( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $card[0] ) ) );
		if ( isset( $seen[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;
		$unique[]     = $card;
	}
	$cards = $unique;

	if ( count( $cards ) < 1 ) {
		return $html;
	}

	usort(
		$cards,
		function ( $a, $b ) {
			$age_a = rdv_reviews_extract_age_seconds_from_html( $a[0], 'rdvasie-review-date' );
			$age_b = rdv_reviews_extract_age_seconds_from_html( $b[0], 'rdvasie-review-date' );
			return $age_a <=> $age_b;
		}
	);

	$list_start = strrpos( substr( $html, 0, $first_offset ), '<div class="rdvasie-reviews-list' );
	if ( false === $list_start ) {
		return $html;
	}

	$list_open_end = strpos( $html, '>', $list_start );
	if ( false === $list_open_end ) {
		return $html;
	}
	$list_open_end++;

	$prefix       = substr( $html, 0, $list_open_end );
	$leading      = substr( $html, $list_open_end, $first_offset - $list_open_end );
	$suffix       = substr( $html, $last_end );
	$sorted_cards = implode(
		'',
		array_map(
			function ( $card ) {
				return $card[0];
			},
			$cards
		)
	);

	return $prefix . $leading . $sorted_cards . $suffix;
}

/**
 * Trie les avis Trustindex dans le wrapper du slider.
 *
 * @param string $html HTML de l'embed Trustindex.
 * @return string
 */
function rdv_reviews_sort_trustindex_html( $html ) {
	$pattern = '/(<div class="ti-reviews-container-wrapper">\s*)(.*?)(\s*<\/div>\s*<div class="ti-controls-line")/s';
	if ( ! preg_match( $pattern, $html, $matches ) ) {
		return $html;
	}

	$prefix = $matches[1];
	$inner  = $matches[2];
	$suffix = $matches[3];
	$items  = array();

	$parts = preg_split( '/(?=<div data-empty="\d+" class="ti-review-item)/', $inner, -1, PREG_SPLIT_NO_EMPTY );
	foreach ( $parts as $part ) {
		if ( preg_match( '/^<div data-empty="\d+" class="ti-review-item/', trim( $part ) ) ) {
			$items[] = $part;
		}
	}

	if ( count( $items ) < 2 ) {
		return $html;
	}

	usort(
		$items,
		function ( $a, $b ) {
			$age_a = rdv_reviews_extract_age_seconds_from_html( $a, 'ti-date' );
			$age_b = rdv_reviews_extract_age_seconds_from_html( $b, 'ti-date' );
			return $age_a <=> $age_b;
		}
	);

	return preg_replace( $pattern, $prefix . implode( ' ', $items ) . $suffix, $html, 1 );
}

add_action( 'wp_enqueue_scripts', 'rdv_reviews_enqueue_sort_script', 30 );
/**
 * Tri côté client (filet de sécurité après chargement Trustindex).
 */
function rdv_reviews_enqueue_sort_script() {
	if ( ! is_page( 'avis-clients' ) ) {
		return;
	}

	$path = get_stylesheet_directory() . '/rdv-reviews-sort.js';
	if ( ! is_readable( $path ) ) {
		return;
	}

	wp_enqueue_script(
		'rdv-reviews-sort',
		get_stylesheet_directory_uri() . '/rdv-reviews-sort.js',
		array(),
		(string) filemtime( $path ),
		true
	);
}
