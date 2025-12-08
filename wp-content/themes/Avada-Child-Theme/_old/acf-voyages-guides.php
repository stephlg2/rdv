<?php
/**
 * Champ ACF pour associer des guides (pages) aux voyages
 * 
 * @package Avada-Child-Theme
 */

// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enregistre le groupe de champs ACF pour les guides associés aux voyages
 */
function acf_register_voyage_guides_field() {
	// Vérifier que ACF est actif
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
		'key' => 'group_voyage_guides',
		'title' => 'Guides associés',
		'fields' => array(
			array(
				'key' => 'field_guides_associes',
				'label' => 'Guides',
				'name' => 'guides_associes',
				'type' => 'post_object',
				'instructions' => 'Sélectionnez les pages (guides) à associer à ce voyage.',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'post_type' => array(
					0 => 'page',
				),
				'taxonomy' => '',
				'allow_null' => 1,
				'multiple' => 1,
				'return_format' => 'object',
				'ui' => 1,
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'tripzzy',
				),
			),
		),
		'menu_order' => 999,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => true,
		'description' => 'Permet d\'associer des pages (guides) à un voyage.',
	) );
}
add_action( 'acf/init', 'acf_register_voyage_guides_field' );

/**
 * Enregistre le groupe de champs ACF pour le cross-selling
 */
function acf_register_voyage_cross_selling_field() {
	// Vérifier que ACF est actif
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
		'key' => 'group_voyage_cross_selling',
		'title' => 'Cross-selling',
		'fields' => array(
			array(
				'key' => 'field_cross_selling_shortcode',
				'label' => 'Shortcode voyages associés',
				'name' => 'cross_selling_shortcode',
				'type' => 'textarea',
				'instructions' => 'Collez ici votre shortcode Tripzzy pour afficher des voyages associés. Exemple : [TRIPZZY_TRIPS trip_destination="Vietnam" posts_per_page="6"]',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'default_value' => '',
				'placeholder' => '[TRIPZZY_TRIPS trip_destination="Vietnam" posts_per_page="6"]',
				'maxlength' => '',
				'rows' => 3,
				'new_lines' => '',
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'tripzzy',
				),
			),
		),
		'menu_order' => 998,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => true,
		'description' => 'Permet d\'afficher des voyages associés en cross-selling via un shortcode Tripzzy.',
	) );
}
add_action( 'acf/init', 'acf_register_voyage_cross_selling_field' );

/**
 * Affiche les guides associés sur la page single voyage
 */
function afficher_guides_voyage() {
	// Vérifier qu'on est sur une page single voyage
	if ( ! is_singular( 'tripzzy' ) ) {
		return;
	}

	// Récupérer les guides associés
	$guides = get_field( 'guides_associes' );
	
	// Si aucun guide n'est associé, ne rien afficher
	if ( ! $guides || empty( $guides ) ) {
		return;
	}

	// S'assurer que c'est un tableau
	if ( ! is_array( $guides ) ) {
		$guides = array( $guides );
	}

	// Récupérer les destinations du voyage
	$destinations = \Tripzzy\Core\Helpers\Trip::get_destinations( get_the_ID() );
	
	// Construire le nom de la destination (pays)
	$destination_name = '';
	if ( is_array( $destinations ) && ! empty( $destinations ) ) {
		$destination_names = array();
		foreach ( $destinations as $destination ) {
			$destination_names[] = $destination->name;
		}
		$destination_name = implode( ', ', $destination_names );
	}
	
	// Déterminer le texte selon le nombre de guides
	$guides_count = count( $guides );
	$guides_text = ( $guides_count > 1 ) ? 'guides disponibles' : 'guide disponible';
	
	// Construire le texte du span avec la destination
	$span_text = $guides_text;
	if ( ! empty( $destination_name ) ) {
		$span_text = $destination_name . ' : ' . $guides_text;
	}

	// Afficher les guides avec la structure demandée
	echo '<div class="tripzzy-section" id="tripzzy-guides-section">';
	echo '<h3 class="tripzzy-section-title">Guides</h3>';
	echo '<div class="tripzzy-section-inner tripzzy-includes-excludes">';
	echo '<div class="tripzzy-includes">';
	echo '<ul class="tripzzy-includes-list">';
	echo '<li class="tripzzy-includes-category has-child">';
	echo '<i class="fa fa-solid fa-circle-check"></i>';
	echo '<span>' . esc_html( $span_text ) . '</span>';
	echo '<ul class="tripzzy-includes-list-child">';
	
	foreach ( $guides as $guide ) {
		// Récupérer les informations de la page
		$guide_id    = is_object( $guide ) ? $guide->ID : $guide;
		$guide_title = get_the_title( $guide_id );
		$guide_url   = get_permalink( $guide_id );
		
		// Afficher le lien dans la structure de liste
		echo '<li class="tripzzy-includes-category-child has-no-child">';
		echo '<span><a href="' . esc_url( $guide_url ) . '">' . esc_html( $guide_title ) . '</a></span>';
		echo '</li>';
	}
	
	echo '</ul>';
	echo '</li>';
	echo '</ul>';
	echo '</div>';
	echo '</div>';
	echo '</div>';
}
add_action( 'tripzzy_single_page_content', 'afficher_guides_voyage' );

/**
 * Affiche le cross-selling (voyages associés) sur la page single voyage
 * S'affiche en pleine largeur après les colonnes, en dehors de tripzzy-entry-content
 */
function afficher_cross_selling_voyage_after_content() {
	// Vérifier qu'on est sur une page single voyage
	if ( ! is_singular( 'tripzzy' ) ) {
		return;
	}

	// Récupérer le shortcode de cross-selling
	$shortcode = get_field( 'cross_selling_shortcode' );
	
	// Si aucun shortcode n'est défini, ne rien afficher
	if ( empty( $shortcode ) ) {
		return;
	}

	// Nettoyer le shortcode (supprimer les espaces en début/fin)
	$shortcode = trim( $shortcode );
	
	// Si le shortcode est vide après nettoyage, ne rien afficher
	if ( empty( $shortcode ) ) {
		return;
	}

	// Afficher le titre et le shortcode dans un wrapper avec la même structure que tripzzy-entry-content
	echo '<div class="tripzzy-entry-content">';
	echo '<div class="tripzzy-cross-selling-section">';
	echo '<h3 class="tripzzy-section-title">Vous aimerez aussi !</h3>';
	echo do_shortcode( $shortcode );
	echo '</div>';
	echo '</div>';
}
// Utiliser le hook qui s'exécute après le contenu principal
add_action( 'tripzzy_single_after_main_content', 'afficher_cross_selling_voyage_after_content' );

