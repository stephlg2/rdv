<?php
/**
 * Page Actualités / Nos articles — listing dynamique + sommaire article.
 *
 * Gestion côté cliente (édition d'un article) :
 * - Catégories WordPress → filtres thématiques du bandeau (dynamiques :
 *   créer une catégorie + y publier un article → elle apparaît dans le menu)
 * - Taxonomie « Pays » (colonne à droite) → destination(s) de l'article
 *
 * Shortcode : [rdv_articles]
 * Paramètres URL :
 * - /actualites/                         → tous les articles
 * - /actualites/?view=par-pays           → vue par pays (A→Z, 3 articles/pays)
 * - /actualites/?categorie=gastronomie     → filtre thématique
 * - /actualites/?pays=japon                → tous les articles d'un pays
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RDV_ARTICLES_PER_PAGE', 12 );
define( 'RDV_ARTICLES_PER_COUNTRY', 3 );
// Page « pays » (ex: ?pays=chine) : chargement progressif.
define( 'RDV_ARTICLES_PER_COUNTRY_PAGE', 9 );

/**
 * Taxonomie destination à utiliser pour le filtre « Par pays ».
 * - Si Tripzzy est actif : réutilise `tripzzy_trip_destination` (source de vérité du site)
 * - Sinon : utilise la taxonomie fallback `rdv_pays`
 *
 * IMPORTANT : on ne touche pas au plugin Tripzzy.
 *
 * @return string
 */
function rdv_articles_destination_taxonomy() {
	return taxonomy_exists( 'tripzzy_trip_destination' ) ? 'tripzzy_trip_destination' : 'rdv_pays';
}

/**
 * Attache la taxonomie Tripzzy aux articles, sans modifier le plugin.
 * (Tripzzy l'enregistre seulement sur le CPT `tripzzy`.)
 */
add_action( 'init', function () {
	if ( taxonomy_exists( 'tripzzy_trip_destination' ) ) {
		register_taxonomy_for_object_type( 'tripzzy_trip_destination', 'post' );
	}
}, 30 );

/**
 * Les archives « Destinations » Tripzzy ne doivent lister que des circuits (tripzzy),
 * pas les articles auxquels on a aussi attaché la taxonomie destination.
 */
add_action( 'pre_get_posts', 'rdv_articles_limit_destination_archives_to_trips', 20 );
function rdv_articles_limit_destination_archives_to_trips( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->is_tax( 'tripzzy_trip_destination' ) ) {
		$query->set( 'post_type', 'tripzzy' );
	}
}

// -----------------------------------------------------------------
// Taxonomie Pays
// -----------------------------------------------------------------

add_action( 'init', 'rdv_articles_register_pays_taxonomy' );
function rdv_articles_register_pays_taxonomy() {
	// Si Tripzzy est disponible, on ne crée pas de taxonomie parallèle.
	// On garde néanmoins le fallback pour les environnements sans Tripzzy.
	if ( taxonomy_exists( 'tripzzy_trip_destination' ) ) {
		return;
	}

	register_taxonomy(
		'rdv_pays',
		'post',
		array(
			'labels'            => array(
				'name'          => 'Pays',
				'singular_name' => 'Pays',
				'search_items'  => 'Rechercher un pays',
				'all_items'     => 'Tous les pays',
				'edit_item'     => 'Modifier le pays',
				'update_item'   => 'Mettre à jour le pays',
				'add_new_item'  => 'Ajouter un pays',
				'new_item_name' => 'Nouveau pays',
				'menu_name'     => 'Pays',
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'pays',
				'with_front' => false,
			),
		)
	);
}

/**
 * Crée les pays du menu destinations (une seule fois).
 */
add_action( 'init', 'rdv_articles_seed_pays_terms', 20 );
function rdv_articles_seed_pays_terms() {
	// Si Tripzzy est disponible, on utilise ses destinations : pas de seeding.
	if ( taxonomy_exists( 'tripzzy_trip_destination' ) ) {
		return;
	}

	if ( get_option( 'rdv_pays_terms_seeded' ) ) {
		return;
	}

	$destinations = rdv_articles_get_destinations_list();

	foreach ( $destinations as $name ) {
		if ( ! term_exists( $name, 'rdv_pays' ) ) {
			wp_insert_term( $name, 'rdv_pays', array( 'slug' => sanitize_title( $name ) ) );
		}
	}

	update_option( 'rdv_pays_terms_seeded', 1 );
}

/**
 * Ordre alphabétique des destinations (aligné sur le menu du site).
 *
 * @return string[]
 */
function rdv_articles_get_destinations_list() {
	return array(
		'Bhoutan',
		'Birmanie',
		'Cambodge',
		'Chine',
		'Corée du Sud',
		'Inde',
		'Indonésie',
		'Japon',
		'Laos',
		'Maldives',
		'Népal',
		'Sri Lanka',
		'Thaïlande',
		'Vietnam',
	);
}

/**
 * Slugs exclus du menu thématique Actualités
 * (guides, méta, catégories techniques / legacy vides).
 *
 * @return string[]
 */
function rdv_articles_get_excluded_thematic_slugs() {
	$excluded = array(
		'guide-et-conseils-voyage-asie',
		'tous-nos-articles',
		'non-categorise',
		'uncategorized',
		// Anciennes catégories remplacées (conservées en alias uniquement).
		'cuisine',
		'culture',
		'que-faire-au-vietnam',
		'inspiration',
		'inspirations',
		'inspirations-experiences',
	);

	/**
	 * Filtre les slugs exclus du bandeau Actualités.
	 *
	 * @param string[] $excluded
	 */
	return apply_filters( 'rdv_articles_excluded_thematic_slugs', $excluded );
}

/**
 * Catégories thématiques affichées dans les filtres (dynamique).
 * Toute catégorie WP non exclue apparaît ici ; le bandeau n'affiche
 * ensuite que celles qui ont au moins un article publié.
 *
 * @return array<string, string> slug => libellé
 */
function rdv_articles_get_thematic_filters() {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$excluded = rdv_articles_get_excluded_thematic_slugs();
	$terms    = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	$filters = array();

	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			if ( in_array( $term->slug, $excluded, true ) ) {
				continue;
			}
			$filters[ $term->slug ] = html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
		}
	}

	/**
	 * Filtre la liste des catégories du bandeau Actualités.
	 *
	 * @param array<string, string> $filters slug => label
	 */
	$cache = apply_filters( 'rdv_articles_thematic_filters', $filters );

	return $cache;
}

/**
 * Catégories thématiques ayant au moins un article publié (hors guides).
 *
 * @return array<string, string> slug => libellé
 */
function rdv_articles_get_thematic_filters_with_articles() {
	$filters = array();

	foreach ( rdv_articles_get_thematic_filters() as $slug => $label ) {
		if ( rdv_articles_count_posts_for_category_slug( $slug ) < 1 ) {
			continue;
		}
		$filters[ $slug ] = $label;
	}

	return $filters;
}

/**
 * Compte les articles publiés pour une catégorie thématique (hors guides).
 *
 * @param string $slug Slug logique du filtre (ex. activites-et-experiences).
 * @return int
 */
function rdv_articles_count_posts_for_category_slug( $slug ) {
	$query = rdv_articles_get_query( 'category', $slug, '', 0, array(), 1 );
	$count = (int) $query->found_posts;
	wp_reset_postdata();

	return $count;
}

/**
 * Slugs de secours si les catégories n'ont pas encore été renommées.
 *
 * @param string $slug
 * @return string[]
 */
function rdv_articles_resolve_category_slugs( $slug ) {
	$aliases = array(
		'culture-evenements'       => array( 'culture-evenements', 'culture' ),
		'gastronomie'              => array( 'gastronomie', 'cuisine' ),
		'activites-et-experiences' => array(
			'activites-et-experiences',
			'inspirations-experiences',
			'inspiration',
			'inspirations',
		),
		// Ancien slug de filtre (URLs bookmarkées).
		'inspirations-experiences' => array(
			'activites-et-experiences',
			'inspirations-experiences',
			'inspiration',
			'inspirations',
		),
	);

	/**
	 * Filtre les alias de slugs de catégories thématiques.
	 *
	 * @param array<string, string[]> $aliases
	 * @param string                  $slug
	 */
	$aliases = apply_filters( 'rdv_articles_category_slug_aliases', $aliases, $slug );

	return isset( $aliases[ $slug ] ) ? $aliases[ $slug ] : array( $slug );
}

/**
 * Normalise un slug d'URL vers la clé de filtre thématique courante.
 *
 * @param string $slug
 * @return string
 */
function rdv_articles_canonicalize_category_slug( $slug ) {
	if ( '' === $slug ) {
		return '';
	}

	foreach ( array_keys( rdv_articles_get_thematic_filters() ) as $key ) {
		if ( $slug === $key || in_array( $slug, rdv_articles_resolve_category_slugs( $key ), true ) ) {
			return $key;
		}
	}

	// Ancien filtre « inspirations » → catégorie actuelle si elle existe.
	if ( in_array( $slug, array( 'inspirations-experiences', 'inspiration', 'inspirations' ), true ) ) {
		$filters = rdv_articles_get_thematic_filters();
		if ( isset( $filters['activites-et-experiences'] ) ) {
			return 'activites-et-experiences';
		}
	}

	return $slug;
}

// -----------------------------------------------------------------
// Assets
// -----------------------------------------------------------------

add_action( 'wp_enqueue_scripts', 'rdv_articles_enqueue_assets' );
function rdv_articles_enqueue_assets() {
	if ( ! rdv_articles_page_has_shortcode() && ! is_singular( 'post' ) ) {
		return;
	}

	wp_enqueue_script(
		'rdv-articles',
		get_stylesheet_directory_uri() . '/rdv-articles.js',
		array(),
		'1.4.6',
		true
	);

	wp_localize_script(
		'rdv-articles',
		'rdvArticles',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'rdv_articles_load_more' ),
			'i18n'    => array(
				'loading'  => __( 'Chargement…', 'Avada' ),
				'loadMore' => __( 'Voir plus', 'Avada' ),
			),
		)
	);

	if ( rdv_articles_page_has_shortcode() ) {
		add_action( 'wp_footer', 'rdv_articles_hub_header_inline_css', 999 );
	}
}

/**
 * CSS header hub articles — injecté en footer pour passer après Avada.
 */
function rdv_articles_hub_header_inline_css() {
	?>
	<style id="rdv-articles-hub-header">
		body.rdv-articles-hub {
			--page_title_height: 280px;
			--page_title_mobile_height: 100px;
		}

		@media screen and (min-width: 993px) {
			body.rdv-articles-hub section.avada-page-titlebar-wrapper {
				position: relative !important;
				inset: auto !important;
				width: 100% !important;
				height: 280px !important;
				min-height: 280px !important;
				max-height: 280px !important;
				padding: 0 !important;
				margin: 0 !important;
				overflow: hidden !important;
			}

			body.rdv-articles-hub .fusion-page-title-bar {
				height: 280px !important;
				min-height: 280px !important;
				max-height: 280px !important;
				background-attachment: scroll !important;
				background-size: cover !important;
				padding: 0 !important;
				margin: 0 !important;
			}
		}

		body.rdv-articles-hub .fusion-page-title-row,
		body.rdv-articles-hub .fusion-page-title-wrapper,
		body.rdv-articles-hub .fusion-page-title-captions {
			height: 100% !important;
			min-height: 0 !important;
			width: 100% !important;
			padding: 0 !important;
			margin: 0 !important;
		}

		body.rdv-articles-hub .fusion-page-title-wrapper,
		body.rdv-articles-hub .fusion-page-title-captions {
			display: flex !important;
			align-items: center !important;
			justify-content: center !important;
			position: relative !important;
		}

		body.rdv-articles-hub .fusion-page-title-secondary,
		body.rdv-articles-hub .fusion-breadcrumbs,
		body.rdv-articles-hub .tripzzy-duration {
			display: none !important;
		}

		body.rdv-articles-hub.header_classique .degrades-image-post {
			display: block !important;
			position: absolute !important;
			inset: 0 !important;
			width: 100% !important;
			height: 100% !important;
			z-index: 0 !important;
		}

		body.rdv-articles-hub .separator-image-post {
			z-index: 3 !important;
		}

		@media screen and (max-width: 992px) {
			body.rdv-articles-hub section.avada-page-titlebar-wrapper,
			body.rdv-articles-hub .fusion-page-title-bar,
			body.rdv-articles-hub .fusion-page-title-row,
			body.rdv-articles-hub .fusion-page-title-wrapper,
			body.rdv-articles-hub .fusion-page-title-captions {
				position: relative !important;
				inset: auto !important;
				width: 100% !important;
				height: 100px !important;
				min-height: 100px !important;
				max-height: 100px !important;
				padding: 0 !important;
				padding-top: 0 !important;
				margin: 0 !important;
				margin-bottom: 0 !important;
				overflow: hidden !important;
				background-size: cover !important;
			}

			body.rdv-articles-hub .degrades-image-post,
			body.rdv-articles-hub.header_classique .degrades-image-post {
				height: 100px !important;
				max-height: 100px !important;
			}

			body.rdv-articles-hub .hundred-percent-height,
			body.rdv-articles-hub .hundred-percent-fullwidth.hundred-percent-height {
				height: 50vh !important;
				min-height: 50vh !important;
				max-height: 50vh !important;
				padding-top: 0 !important;
				padding-bottom: 0 !important;
				position: relative !important;
			}

			body.rdv-articles-hub .hundred-percent-height .fusion-fullwidth-center-content {
				position: absolute !important;
				top: 0 !important;
				right: 0 !important;
				bottom: 0 !important;
				left: 0 !important;
				height: 100% !important;
				min-height: 100% !important;
				max-height: 100% !important;
				display: flex !important;
				align-items: center !important;
				justify-content: center !important;
				padding-top: 0 !important;
				padding-bottom: 0 !important;
			}

			body.rdv-articles-hub .hundred-percent-height .fusion-builder-row {
				width: 100% !important;
				max-width: 100% !important;
				margin: 0 !important;
				height: auto !important;
			}

			body.rdv-articles-hub .hundred-percent-height .fusion-column-content-centered,
			body.rdv-articles-hub .hundred-percent-height .fusion-column-content {
				display: block !important;
				height: auto !important;
			}

			body.rdv-articles-hub .hundred-percent-height .fusion-builder-row,
			body.rdv-articles-hub .hundred-percent-height .fusion-layout-column,
			body.rdv-articles-hub .hundred-percent-height .fusion-column-wrapper,
			body.rdv-articles-hub .hundred-percent-height .fusion-column-content-centered,
			body.rdv-articles-hub .hundred-percent-height .fusion-column-content,
			body.rdv-articles-hub .hundred-percent-height .fusion-title.Titre-Homepage,
			body.rdv-articles-hub .hundred-percent-height .Titre-Homepage {
				padding-top: 0 !important;
				padding-bottom: 0 !important;
				margin-top: 0 !important;
				margin-bottom: 0 !important;
				--awb-margin-bottom: 0px !important;
				position: absolute !important;
				top: 50% !important;
				left: 0 !important;
				right: 0 !important;
				width: 100% !important;
				transform: translateY(-50%) !important;
				z-index: 2 !important;
			}

			body.rdv-articles-hub .separator-image-post {
				height: 28px !important;
				background-size: 100% 100%;
			}
		}

		.rdv-articles-wrapper .rdv-card__tag,
		.rdv-articles-wrapper .rdv-card__tag span,
		.rdv-articles-wrapper .rdv-card__tag .fa,
		.rdv-articles-wrapper .rdv-card__tag i {
			color: #9a8f84 !important;
		}
	</style>
	<?php
}

function rdv_articles_page_has_shortcode() {
	if ( ! is_singular() ) {
		return false;
	}

	global $post;

	if ( ! $post ) {
		return false;
	}

	if ( has_shortcode( $post->post_content, 'rdv_articles' ) ) {
		return true;
	}

	return in_array( $post->post_name, array( 'nos-articles', 'actualites' ), true );
}

/**
 * Classe body pour styles hub articles (header compact, etc.).
 *
 * @param string[] $classes
 * @return string[]
 */
add_filter( 'body_class', 'rdv_articles_body_class' );
function rdv_articles_body_class( $classes ) {
	if ( rdv_articles_page_has_shortcode() ) {
		$classes[] = 'rdv-articles-hub';
	}

	return $classes;
}

// -----------------------------------------------------------------
// Shortcode [rdv_articles]
// -----------------------------------------------------------------

add_shortcode( 'rdv_articles', 'rdv_articles_shortcode' );
function rdv_articles_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'show_filters' => 'yes',
		),
		$atts,
		'rdv_articles'
	);

	$view      = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'all';
	$categorie = isset( $_GET['categorie'] ) ? sanitize_title( wp_unslash( $_GET['categorie'] ) ) : '';
	$pays      = isset( $_GET['pays'] ) ? sanitize_title( wp_unslash( $_GET['pays'] ) ) : '';
	$categorie = rdv_articles_canonicalize_category_slug( $categorie );

	if ( 'par-pays' === $view ) {
		$html = rdv_articles_render_by_country();
	} elseif ( $pays ) {
		$html = rdv_articles_render_country_all( $pays );
	} elseif ( $categorie ) {
		$thematic = rdv_articles_get_thematic_filters_with_articles();
		if ( ! isset( $thematic[ $categorie ] ) ) {
			$categorie = '';
			$html      = rdv_articles_render_all();
		} else {
			$html = rdv_articles_render_by_category( $categorie );
		}
	} else {
		$html = rdv_articles_render_all();
	}

	$output = '<div class="rdv-articles-wrapper" id="rdv-articles-app">';

	if ( 'yes' === $atts['show_filters'] ) {
		$output .= '<div class="rdv-articles-sticky-nav">';
		$output .= '<div class="rdv-nav-card">';
		$output .= rdv_articles_render_filters( $view, $categorie, $pays );
		if ( 'par-pays' === $view && ! $pays ) {
			$output .= '<div class="rdv-nav-divider" aria-hidden="true"></div>';
			$output .= rdv_articles_render_country_strip();
		}
		$output .= '</div>';
		$output .= '</div>';
	}

	$output .= '<div class="rdv-articles-page-body">';
	$output .= $html;
	$output .= '</div>';
	$output .= '</div>';

	return $output;
}

/**
 * URL de base de la page Actualités / Nos articles (page qui porte [rdv_articles]).
 */
function rdv_articles_get_page_url() {
	global $post;

	// Page courante si elle contient le shortcode (hub réel).
	if ( $post instanceof WP_Post && has_shortcode( (string) $post->post_content, 'rdv_articles' ) ) {
		return get_permalink( $post );
	}

	$paths = array(
		'articles-blog-voyage-asie',
		'actualites',
		'nos-articles',
	);

	foreach ( $paths as $path ) {
		$page = get_page_by_path( $path );
		if ( ! $page ) {
			continue;
		}

		// Ne pas prendre une page « nos-articles » sans le shortcode
		// (évite l’ancien article homonyme).
		if ( 'nos-articles' === $page->post_name && ! has_shortcode( (string) $page->post_content, 'rdv_articles' ) ) {
			continue;
		}

		return get_permalink( $page );
	}

	if ( is_singular( 'page' ) ) {
		return get_permalink();
	}

	return home_url( '/articles-blog-voyage-asie/' );
}

/**
 * URL de la page mère des guides.
 */
function rdv_articles_get_guides_page_url() {
	$paths = array(
		'guide-et-conseils-voyage-asie/nos-guides-voyage-asie',
		'nos-guides-voyage-asie',
	);

	foreach ( $paths as $path ) {
		$page = get_page_by_path( $path );
		if ( $page ) {
			return get_permalink( $page );
		}
	}

	return home_url( '/guide-et-conseils-voyage-asie/nos-guides-voyage-asie/' );
}

/**
 * Configuration des filtres (libellés + icônes).
 *
 * @return array<int, array{key: string, label: string, url: string, icon: string}>
 */
function rdv_articles_get_filters_config( $view, $categorie, $pays ) {
	$base_url = rdv_articles_get_page_url();
	$active   = $pays ? 'pays-detail' : ( 'par-pays' === $view ? 'par-pays' : ( $categorie ? $categorie : 'all' ) );

	$filters = array(
		array(
			'key'   => 'all',
			'label' => __( 'Tous les articles', 'Avada' ),
			'url'   => $base_url,
			'icon'  => 'fa-newspaper',
		),
		array(
			'key'   => 'par-pays',
			'label' => __( 'Par pays', 'Avada' ),
			'url'   => add_query_arg( 'view', 'par-pays', $base_url ),
			'icon'  => 'fa-globe-asia',
		),
	);

	foreach ( rdv_articles_get_thematic_filters_with_articles() as $slug => $label ) {
		$filters[] = array(
			'key'   => $slug,
			'label' => $label,
			'url'   => add_query_arg( 'categorie', $slug, $base_url ),
			'icon'  => rdv_articles_get_filter_icon_for_slug( $slug ),
		);
	}

	foreach ( $filters as &$filter ) {
		$filter['is_active'] = ( $filter['key'] === $active );
	}
	unset( $filter );

	return apply_filters( 'rdv_articles_filters_config', $filters, $view, $categorie, $pays );
}

/**
 * Terme catégorie WP pour un slug logique de filtre hub articles.
 *
 * @param string $slug
 * @return WP_Term|null
 */
function rdv_articles_get_category_term_for_slug( $slug ) {
	foreach ( rdv_articles_resolve_category_slugs( $slug ) as $try_slug ) {
		$term = get_category_by_slug( $try_slug );
		if ( $term && ! is_wp_error( $term ) ) {
			return $term;
		}
	}

	return null;
}

/**
 * Icône Font Awesome par filtre thématique.
 */
function rdv_articles_get_filter_icon_for_slug( $slug ) {
	$term = rdv_articles_get_category_term_for_slug( $slug );
	if ( $term && function_exists( 'rdv_category_icon_get' ) ) {
		$icon = rdv_category_icon_get( $term );
		if ( '' !== $icon ) {
			return $icon;
		}
	}

	$icons = array(
		'activites-et-experiences' => 'fa-compass',
		'inspirations-experiences' => 'fa-compass',
		'culture-evenements'       => 'fa-landmark',
		'gastronomie'              => 'fa-utensils',
	);

	return isset( $icons[ $slug ] ) ? $icons[ $slug ] : 'fa-tag';
}

/**
 * Barre de filtres — onglets texte (modèle UX).
 */
function rdv_articles_render_filters( $view, $categorie, $pays ) {
	$filters = rdv_articles_get_filters_config( $view, $categorie, $pays );

	ob_start();
	?>
	<div class="rdv-nav-mobile" aria-label="<?php esc_attr_e( 'Filtrer les articles (mobile)', 'Avada' ); ?>">
		<label class="rdv-nav-mobile__label" for="rdv-nav-filter-select"><?php esc_html_e( 'Afficher', 'Avada' ); ?></label>
		<select class="rdv-nav-mobile__select" id="rdv-nav-filter-select">
			<?php foreach ( $filters as $filter ) : ?>
				<option value="<?php echo esc_url( $filter['url'] ); ?>" <?php selected( ! empty( $filter['is_active'] ) ); ?>>
					<?php echo esc_html( $filter['label'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>

	<nav class="rdv-nav-filters" aria-label="<?php esc_attr_e( 'Filtrer les articles', 'Avada' ); ?>">
		<?php foreach ( $filters as $filter ) : ?>
			<a class="rdv-nav-filter<?php echo ! empty( $filter['is_active'] ) ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( $filter['url'] ); ?>">
				<?php if ( ! empty( $filter['icon'] ) ) : ?>
					<i class="fa <?php echo esc_attr( $filter['icon'] ); ?>" aria-hidden="true"></i>
				<?php endif; ?>
				<span><?php echo esc_html( $filter['label'] ); ?></span>
			</a>
		<?php endforeach; ?>
	</nav>
	<?php
	return ob_get_clean();
}

/**
 * Bandeau horizontal des pays (vue « Par pays »).
 */
function rdv_articles_render_country_strip() {
	$countries = rdv_articles_get_countries_with_articles();

	if ( empty( $countries ) ) {
		return '';
	}

	ob_start();
	?>
	<div class="rdv-nav-mobile rdv-nav-mobile--countries" aria-label="<?php esc_attr_e( 'Pays (mobile)', 'Avada' ); ?>">
		<label class="rdv-nav-mobile__label" for="rdv-nav-country-select"><?php esc_html_e( 'Pays', 'Avada' ); ?></label>
		<select class="rdv-nav-mobile__select" id="rdv-nav-country-select">
			<?php foreach ( $countries as $country ) : ?>
				<option value="#rdv-pays-<?php echo esc_attr( $country['slug'] ); ?>">
					<?php echo esc_html( $country['name'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>

	<div class="rdv-country-strip-wrap" aria-label="<?php esc_attr_e( 'Pays', 'Avada' ); ?>">
		<button type="button" class="rdv-country-strip__arrow rdv-country-strip__arrow--left" aria-label="<?php esc_attr_e( 'Défiler vers la gauche', 'Avada' ); ?>">
			<span aria-hidden="true">‹</span>
		</button>
		<div class="rdv-nav-countries rdv-country-strip" role="navigation">
			<?php foreach ( $countries as $country ) : ?>
				<a class="rdv-country-pill" href="#rdv-pays-<?php echo esc_attr( $country['slug'] ); ?>">
					<?php echo esc_html( $country['name'] ); ?>
				</a>
			<?php endforeach; ?>
		</div>
		<button type="button" class="rdv-country-strip__arrow rdv-country-strip__arrow--right" aria-label="<?php esc_attr_e( 'Défiler vers la droite', 'Avada' ); ?>">
			<span aria-hidden="true">›</span>
		</button>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Pays ayant au moins un article (ordre menu destinations).
 *
 * @return array<int, array{slug: string, name: string, count: int}>
 */
function rdv_articles_get_countries_with_articles() {
	$tax           = rdv_articles_destination_taxonomy();
	$ordered_names = rdv_articles_get_destinations_list();
	$countries     = array();

	foreach ( $ordered_names as $name ) {
		$slug = sanitize_title( $name );
		$term = get_term_by( 'slug', $slug, $tax );

		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}

		$count = rdv_articles_count_posts_for_term( $term->term_id, $tax );
		if ( $count < 1 ) {
			continue;
		}

		$countries[] = array(
			'slug'  => $term->slug,
			'name'  => $term->name,
			'count' => $count,
		);
	}

	return $countries;
}

/**
 * Compte les articles publiés pour un pays (hors guides).
 */
function rdv_articles_count_posts_for_term( $term_id, $tax ) {
	$query = new WP_Query(
		rdv_articles_exclude_guides_from_query_args(
			array(
				'post_type'              => 'post',
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'tax_query'              => array(
					array(
						'taxonomy' => $tax,
						'field'    => 'term_id',
						'terms'    => (int) $term_id,
					),
				),
			)
		)
	);

	$count = (int) $query->found_posts;
	wp_reset_postdata();

	return $count;
}

/**
 * Terme de catégorie principale (hors guide).
 *
 * @param int $post_id
 * @return WP_Term|null
 */
function rdv_articles_get_primary_category_term( $post_id ) {
	$categories = get_the_category( $post_id );

	if ( empty( $categories ) ) {
		return null;
	}

	foreach ( $categories as $cat ) {
		if ( 'guide-et-conseils-voyage-asie' === $cat->slug || 'tous-nos-articles' === $cat->slug ) {
			continue;
		}
		return $cat;
	}

	return $categories[0];
}

/**
 * Libellé de catégorie principale (hors guide).
 */
function rdv_articles_get_primary_category_label( $post_id ) {
	$term = rdv_articles_get_primary_category_term( $post_id );

	return $term ? $term->name : '';
}

/**
 * Icône Font Awesome de la catégorie principale.
 */
function rdv_articles_get_primary_category_icon( $post_id ) {
	$term = rdv_articles_get_primary_category_term( $post_id );

	if ( ! $term ) {
		return '';
	}

	return rdv_articles_get_filter_icon_for_slug( $term->slug );
}

/**
 * Estimation du temps de lecture.
 */
function rdv_articles_get_reading_time( $post_id ) {
	$content = get_post_field( 'post_content', $post_id );
	$words   = str_word_count( wp_strip_all_tags( $content ) );
	$minutes = max( 1, (int) ceil( $words / 200 ) );

	return sprintf(
		/* translators: %d: minutes */
		_n( '%d min de lecture', '%d min de lecture', $minutes, 'Avada' ),
		$minutes
	);
}

/**
 * Premier pays associé à l'article.
 */
function rdv_articles_get_primary_country_label( $post_id ) {
	$tax   = rdv_articles_destination_taxonomy();
	$terms = get_the_terms( $post_id, $tax );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return '';
	}

	return $terms[0]->name;
}

/**
 * Exclut les guides du listing articles.
 *
 * @param array $args
 * @return array
 */
function rdv_articles_exclude_guides_from_query_args( $args ) {
	$guide = get_category_by_slug( 'guide-et-conseils-voyage-asie' );

	if ( $guide && ! is_wp_error( $guide ) ) {
		$exclude   = isset( $args['category__not_in'] ) ? (array) $args['category__not_in'] : array();
		$exclude[] = (int) $guide->term_id;
		$args['category__not_in'] = array_unique( array_filter( $exclude ) );
	}

	return $args;
}

// -----------------------------------------------------------------
// Vues
// -----------------------------------------------------------------

function rdv_articles_render_all( $offset = 0 ) {
	$html         = '';
	$exclude_ids  = array();
	$spotlight_id = 0;

	if ( 0 === (int) $offset ) {
		$spotlight_query = rdv_articles_get_query( 'all', '', '', 0, array(), 1 );
		if ( $spotlight_query->have_posts() ) {
			$spotlight_query->the_post();
			$spotlight_id = get_the_ID();
			$html        .= rdv_articles_render_spotlight( $spotlight_id );
			$exclude_ids[] = $spotlight_id;
			wp_reset_postdata();
		}
	}

	$query = rdv_articles_get_query( 'all', '', '', $offset, $exclude_ids );
	$html .= rdv_articles_render_grid_response( $query, 'all', array( 'exclude' => $exclude_ids ), $offset, 'rdv-articles-grid--3' );

	if ( 0 === (int) $offset ) {
		$html .= rdv_articles_render_guides_promo_block();
	}

	return $html;
}

function rdv_articles_render_by_category( $slug, $offset = 0 ) {
	$query = rdv_articles_get_query( 'category', $slug, '', $offset );

	$filters = rdv_articles_get_thematic_filters();
	$title   = isset( $filters[ $slug ] ) ? $filters[ $slug ] : ucfirst( str_replace( '-', ' ', $slug ) );

	$html  = '<div class="rdv-articles-section rdv-articles-section--category country-section">';
	$html .= '<div class="country-header">';
	$html .= '<h2 class="country-name">' . esc_html( $title ) . '</h2>';
	$html .= '</div>';
	$html .= rdv_articles_render_grid_response( $query, 'category', array( 'categorie' => $slug ), $offset, 'rdv-articles-grid--3' );
	$html .= '</div>';

	return $html;
}

function rdv_articles_render_by_country() {
	$countries = rdv_articles_get_countries_with_articles();

	if ( empty( $countries ) ) {
		return '<p class="rdv-articles-empty">' . esc_html__( 'Aucun article associé à un pays pour le moment. Assignez une destination à vos articles dans l’édition.', 'Avada' ) . '</p>';
	}

	$tax = rdv_articles_destination_taxonomy();
	$html = '';

	foreach ( $countries as $country ) {
		$term = get_term_by( 'slug', $country['slug'], $tax );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}

		$count = (int) $country['count'];

		$query = new WP_Query(
			rdv_articles_exclude_guides_from_query_args(
				array(
					'post_type'              => 'post',
					'post_status'            => 'publish',
					'posts_per_page'         => RDV_ARTICLES_PER_COUNTRY,
					'orderby'                => 'date',
					'order'                  => 'DESC',
					'tax_query'              => array(
						array(
							'taxonomy' => $tax,
							'field'    => 'term_id',
							'terms'    => $term->term_id,
						),
					),
					'ignore_sticky_posts'    => true,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => true,
				)
			)
		);

		if ( ! $query->have_posts() ) {
			continue;
		}

		$voir_tous_url = add_query_arg(
			array(
				'pays' => $term->slug,
			),
			rdv_articles_get_page_url()
		);

		$grid_class = RDV_ARTICLES_PER_COUNTRY >= 4 ? 'rdv-articles-grid--featured' : 'rdv-articles-grid--3';

		$html .= '<section class="country-section" id="rdv-pays-' . esc_attr( $term->slug ) . '">';
		$html .= '<div class="country-header">';
		$html .= '<h2 class="country-name">' . esc_html( $term->name );
		$html .= ' <span class="country-count">' . esc_html( sprintf( _n( '%d article', '%d articles', $count, 'Avada' ), $count ) ) . '</span>';
		$html .= '</h2>';
		if ( $count > RDV_ARTICLES_PER_COUNTRY ) {
			/* translators: %s: country name */
			$see_all_label = sprintf( __( 'Voir tous les articles sur %s', 'Avada' ), $term->name );
			$html         .= '<a class="country-see-all" href="' . esc_url( $voir_tous_url ) . '">' . esc_html( $see_all_label ) . '</a>';
		}
		$html .= '</div>';
		$html .= '<div class="rdv-articles-grid rdv-articles-grid--preview ' . esc_attr( $grid_class ) . '">';
		$html .= rdv_articles_render_cards( $query, array( 'show_country' => false ) );
		$html .= '</div>';
		wp_reset_postdata();
		$html .= '</section>';
	}

	if ( '' === $html ) {
		return '<p class="rdv-articles-empty">' . esc_html__( 'Aucun article associé à un pays pour le moment. Assignez une destination à vos articles dans l’édition.', 'Avada' ) . '</p>';
	}

	return $html;
}

function rdv_articles_render_country_all( $pays_slug, $offset = 0 ) {
	$tax  = rdv_articles_destination_taxonomy();
	$term = get_term_by( 'slug', $pays_slug, $tax );

	if ( ! $term || is_wp_error( $term ) ) {
		return '<p class="rdv-articles-empty">' . esc_html__( 'Pays introuvable.', 'Avada' ) . '</p>';
	}

	$query = rdv_articles_get_query( 'pays', '', $pays_slug, $offset );

	$back_url = add_query_arg( 'view', 'par-pays', rdv_articles_get_page_url() );

	$html  = '<div class="rdv-articles-section country-section">';
	$html .= '<p class="rdv-articles-back"><a href="' . esc_url( $back_url ) . '">← ' . esc_html__( 'Retour', 'Avada' ) . '</a></p>';
	$html .= '<div class="country-header">';
	$html .= '<h2 class="country-name">' . esc_html( $term->name ) . '</h2>';
	$html .= '</div>';
	$html .= '<div class="rdv-articles-country-listing">';
	$html .= rdv_articles_render_grid_response( $query, 'pays', array( 'pays' => $pays_slug ), $offset, 'rdv-articles-grid--3' );
	$html .= '</div>';
	$html .= rdv_articles_render_country_circuits_cta( $term );
	$html .= '</div>';

	return $html;
}

// -----------------------------------------------------------------
// Rendu cartes + pagination « Voir plus »
// -----------------------------------------------------------------

/**
 * Bloc CTA guides (vue « Tous les articles »).
 */
function rdv_articles_render_guides_promo_block() {
	$url = rdv_articles_get_guides_page_url();

	ob_start();
	?>
	<aside class="rdv-articles-promo rdv-articles-promo--guides">
		<div class="rdv-articles-promo__inner">
			<h2 class="rdv-articles-promo__title"><?php esc_html_e( 'Découvrez nos guides de voyages', 'Avada' ); ?></h2>
			<p class="rdv-articles-promo__text">
				<?php esc_html_e( 'Préparez votre voyage avec nos guides complets : conseils pratiques, itinéraires et bonnes adresses.', 'Avada' ); ?>
			</p>
			<a class="rdv-articles-promo__cta tz-btn tz-btn-solid" href="<?php echo esc_url( $url ); ?>">
				<?php esc_html_e( 'Voir nos guides', 'Avada' ); ?>
			</a>
		</div>
	</aside>
	<?php
	return ob_get_clean();
}

/**
 * Prépositions françaises par slug de pays asiatique (slug = sanitize_title du nom WP).
 *
 * @return array<string, string> slug => en|au|aux|à
 */
function rdv_articles_get_asia_country_prepositions() {
	return array(
		// Asie centrale
		'afghanistan'       => 'en',
		'kazakhstan'        => 'au',
		'kirghizistan'      => 'au',
		'ouzbekistan'       => 'en',
		'tadjikistan'       => 'au',
		'turkmenistan'      => 'au',

		// Asie de l'Est
		'chine'             => 'en',
		'coree'             => 'en',
		'coree-du-nord'     => 'en',
		'coree-du-sud'      => 'en',
		'hong-kong'         => 'à',
		'japon'             => 'au',
		'macao'             => 'à',
		'macau'             => 'à',
		'mongolie'          => 'en',
		'taiwan'            => 'à',
		'tibet'             => 'au',

		// Asie du Sud
		'bangladesh'        => 'au',
		'bhoutan'           => 'au',
		'inde'              => 'en',
		'maldives'          => 'aux',
		'nepal'             => 'au',
		'pakistan'          => 'au',
		'sri-lanka'         => 'au',

		// Asie du Sud-Est
		'birmanie'          => 'en',
		'brunei'            => 'au',
		'burma'             => 'en',
		'cambodge'          => 'au',
		'indonesie'         => 'en',
		'laos'              => 'au',
		'malaisie'          => 'en',
		'myanmar'           => 'au',
		'philippines'       => 'aux',
		'singapour'         => 'à',
		'thailande'         => 'en',
		'timor-leste'       => 'au',
		'timor-oriental'    => 'au',
		'vietnam'           => 'au',
		'viet-nam'          => 'au',

		// Caucase
		'armenie'           => 'en',
		'azerbaidjan'       => 'en',
		'georgie'           => 'en',

		// Proche & Moyen-Orient
		'arabie-saoudite'   => 'en',
		'bahrein'           => 'à',
		'bahrain'           => 'à',
		'chypre'            => 'à',
		'cyprus'            => 'à',
		'emirats-arabes-unis' => 'aux',
		'emirats'           => 'aux',
		'iran'              => 'en',
		'irak'              => 'en',
		'israel'            => 'en',
		'jordanie'          => 'en',
		'koweit'            => 'au',
		'kuwait'            => 'au',
		'liban'             => 'au',
		'oman'              => 'à',
		'palestine'         => 'en',
		'qatar'             => 'au',
		'russie'            => 'en',
		'syrie'             => 'en',
		'turquie'           => 'en',
		'yemen'             => 'au',
	);
}

/**
 * Préposition de repli si le slug n'est pas dans la carte (nouveau pays Tripzzy).
 *
 * @param string $slug
 * @param string $name
 * @return string en|au|aux|à
 */
function rdv_articles_guess_country_preposition( $slug, $name ) {
	$plural_slugs = array( 'maldives', 'philippines', 'emirats-arabes-unis', 'emirats' );
	if ( in_array( $slug, $plural_slugs, true ) ) {
		return 'aux';
	}

	$a_slugs = array( 'singapour', 'bahrein', 'bahrain', 'oman', 'hong-kong', 'macao', 'macau', 'taiwan', 'chypre', 'cyprus' );
	if ( in_array( $slug, $a_slugs, true ) ) {
		return 'à';
	}

	$en_slugs = array( 'afghanistan', 'iran', 'irak', 'israel', 'palestine', 'syrie', 'jordanie', 'armenie', 'azerbaidjan', 'georgie', 'ouzbekistan', 'mongolie', 'turquie', 'russie', 'arabie-saoudite' );
	if ( in_array( $slug, $en_slugs, true ) ) {
		return 'en';
	}

	// Pays féminins en -e (Chine, Inde, Birmanie, Thaïlande, Corée…).
	if ( preg_match( '/e$/ui', trim( $name ) ) ) {
		return 'en';
	}

	return 'au';
}

/**
 * Préposition française + nom de pays (en Chine, au Japon, aux Maldives…).
 *
 * @param WP_Term|string $term Terme destination ou nom du pays.
 * @return string Ex. « en Chine », « au Japon »
 */
function rdv_articles_get_country_with_preposition( $term ) {
	$slug = '';
	$name = '';

	if ( $term instanceof WP_Term ) {
		$slug = $term->slug;
		$name = $term->name;
	} else {
		$name = (string) $term;
		$slug = sanitize_title( $name );
	}

	$prepositions = rdv_articles_get_asia_country_prepositions();
	$prep         = isset( $prepositions[ $slug ] )
		? $prepositions[ $slug ]
		: rdv_articles_guess_country_preposition( $slug, $name );

	return $prep . ' ' . $name;
}

/**
 * Valeur trip_destination pour [TRIPZZY_TRIPS] (nom du pays, comme sur les guides).
 *
 * @param WP_Term $term
 * @return string
 */
function rdv_articles_get_trip_shortcode_destination( $term ) {
	return $term->name;
}

/**
 * Cartes circuits en fin de page pays (shortcode Tripzzy).
 *
 * @param WP_Term $term
 */
function rdv_articles_render_country_circuits_cta( $term ) {
	if ( ! $term || is_wp_error( $term ) ) {
		return '';
	}

	if ( ! shortcode_exists( 'TRIPZZY_TRIPS' ) ) {
		return '';
	}

	$destination = rdv_articles_get_trip_shortcode_destination( $term );
	$shortcode   = sprintf(
		'[TRIPZZY_TRIPS trip_destination="%s" posts_per_page="6"]',
		esc_attr( $destination )
	);

	$trips_html = do_shortcode( $shortcode );
	if ( '' === trim( wp_strip_all_tags( $trips_html ) ) ) {
		return '';
	}

	ob_start();
	?>
	<section class="rdv-articles-trips-section tripzzy-cross-selling-section">
		<h2 class="rdv-articles-trips-section__title tripzzy-section-title">
			<?php esc_html_e( 'Découvrez nos voyages', 'Avada' ); ?>
			<span class="orange">
				<?php
				echo esc_html(
					' ' . rdv_articles_get_country_with_preposition( $term ) . ' !'
				);
				?>
			</span>
		</h2>
		<?php echo $trips_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Bannière article à la une (vue « Tous les articles »).
 */
function rdv_articles_render_spotlight( $post_id ) {
	$permalink = get_permalink( $post_id );
	$title     = get_the_title( $post_id );
	$excerpt   = wp_trim_words( get_the_excerpt( $post_id ), 40, '…' );
	$country   = rdv_articles_get_primary_country_label( $post_id );
	$eyebrow   = __( 'À la une', 'Avada' );
	$eyebrow_country = $country ? $country : '';

	$thumb = get_the_post_thumbnail(
		$post_id,
		'large',
		array(
			'class'   => 'rdv-spotlight__img',
			'loading' => 'eager',
			'alt'     => $title,
		)
	);

	if ( ! $thumb ) {
		$thumb = '<div class="rdv-spotlight__img rdv-spotlight__img--placeholder" aria-hidden="true"></div>';
	}

	ob_start();
	?>
	<article class="rdv-spotlight">
		<a class="rdv-spotlight__media" href="<?php echo esc_url( $permalink ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
		<div class="rdv-spotlight__content">
			<p class="rdv-spotlight__eyebrow">
				<span><?php echo esc_html( $eyebrow ); ?></span>
				<?php if ( $eyebrow_country ) : ?>
					<span class="rdv-spotlight__eyebrow-sep" aria-hidden="true"> · </span>
					<span class="rdv-spotlight__country">
						<i class="fa fa-map-marker-alt" aria-hidden="true"></i>
						<?php echo esc_html( $eyebrow_country ); ?>
					</span>
				<?php endif; ?>
			</p>
			<h2 class="rdv-spotlight__title">
				<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
			</h2>
			<?php if ( $excerpt ) : ?>
				<p class="rdv-spotlight__desc"><?php echo esc_html( $excerpt ); ?></p>
			<?php endif; ?>
			<a class="rdv-spotlight__link" href="<?php echo esc_url( $permalink ); ?>">
				<?php esc_html_e( 'Lire l\'article', 'Avada' ); ?>
			</a>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

function rdv_articles_render_grid_response( WP_Query $query, $view, $args, $offset, $grid_class = 'rdv-articles-grid--3' ) {
	$card_args = array(
		'show_country' => ! in_array( $view, array( 'par-pays', 'pays' ), true ),
	);

	$html = '<div class="rdv-articles-grid ' . esc_attr( $grid_class ) . '" data-rdv-grid>';

	if ( $query->have_posts() ) {
		$html .= rdv_articles_render_cards( $query, $card_args );
	} else {
		$html .= '<p class="rdv-articles-empty">' . esc_html__( 'Aucun article trouvé.', 'Avada' ) . '</p>';
	}

	$html .= '</div>';

	$total     = (int) $query->found_posts;
	$loaded    = (int) $offset + (int) $query->post_count;
	$has_more  = $loaded < $total;

	if ( $has_more && $query->have_posts() ) {
		$html .= rdv_articles_render_load_more_button( $view, $args, $loaded, $total );
	}

	wp_reset_postdata();

	return $html;
}

function rdv_articles_render_cards( WP_Query $query, $args = array() ) {
	$html = '';

	while ( $query->have_posts() ) {
		$query->the_post();
		$html .= rdv_articles_render_card( get_the_ID(), $args );
	}

	return $html;
}

function rdv_articles_render_card( $post_id, $args = array() ) {
	$show_country = ! empty( $args['show_country'] );
	$permalink    = get_permalink( $post_id );
	$title        = get_the_title( $post_id );
	$tag          = rdv_articles_get_primary_category_label( $post_id );
	$tag_icon     = rdv_articles_get_primary_category_icon( $post_id );
	$date         = get_the_date( 'j F Y', $post_id );
	$country      = $show_country ? rdv_articles_get_primary_country_label( $post_id ) : '';
	$thumb     = get_the_post_thumbnail(
		$post_id,
		'medium_large',
		array(
			'class'   => 'rdv-card__img',
			'loading' => 'lazy',
			'alt'     => $title,
		)
	);

	if ( ! $thumb ) {
		$thumb = '<div class="rdv-card__img rdv-card__img--placeholder" aria-hidden="true"></div>';
	}

	ob_start();
	?>
	<a class="rdv-card" href="<?php echo esc_url( $permalink ); ?>">
		<?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<div class="rdv-card__body">
			<?php if ( $tag ) : ?>
				<p class="rdv-card__tag">
					<?php if ( $tag_icon ) : ?>
						<i class="fa <?php echo esc_attr( $tag_icon ); ?>" aria-hidden="true"></i>
					<?php endif; ?>
					<span><?php echo esc_html( $tag ); ?></span>
				</p>
			<?php endif; ?>
			<h3 class="rdv-card__title"><?php echo esc_html( $title ); ?></h3>
			<p class="rdv-card__meta">
				<?php if ( $country ) : ?>
					<span class="rdv-card__country">
						<i class="fa fa-map-marker-alt" aria-hidden="true"></i>
						<?php echo esc_html( $country ); ?>
					</span>
					<span class="rdv-card__meta-sep" aria-hidden="true">·</span>
				<?php endif; ?>
				<span class="rdv-card__date"><?php echo esc_html( $date ); ?></span>
			</p>
		</div>
	</a>
	<?php
	return ob_get_clean();
}

function rdv_articles_get_load_more_label( $view ) {
	if ( in_array( $view, array( 'pays', 'category' ), true ) ) {
		return __( 'Plus d\'articles', 'Avada' );
	}

	return __( 'Voir plus', 'Avada' );
}

function rdv_articles_render_load_more_button( $view, $args, $offset, $total ) {
	$label      = rdv_articles_get_load_more_label( $view );
	$data_attrs = array(
		'data-view'            => esc_attr( $view ),
		'data-offset'          => (int) $offset,
		'data-total'           => (int) $total,
		'data-load-more-label' => esc_attr( $label ),
	);

	if ( ! empty( $args['categorie'] ) ) {
		$data_attrs['data-categorie'] = esc_attr( $args['categorie'] );
	}
	if ( ! empty( $args['pays'] ) ) {
		$data_attrs['data-pays'] = esc_attr( $args['pays'] );
	}
	if ( ! empty( $args['exclude'] ) ) {
		$data_attrs['data-exclude'] = esc_attr( implode( ',', array_map( 'intval', (array) $args['exclude'] ) ) );
	}

	$attr_string = '';
	foreach ( $data_attrs as $key => $value ) {
		$attr_string .= ' ' . $key . '="' . $value . '"';
	}

	return '<div class="rdv-articles-load-more-wrap"><button type="button" class="rdv-articles-load-more tz-btn tz-btn-solid"' . $attr_string . '>' . esc_html( $label ) . '</button></div>';
}

// -----------------------------------------------------------------
// AJAX — Voir plus
// -----------------------------------------------------------------

add_action( 'wp_ajax_rdv_articles_load_more', 'rdv_articles_ajax_load_more' );
add_action( 'wp_ajax_nopriv_rdv_articles_load_more', 'rdv_articles_ajax_load_more' );
function rdv_articles_ajax_load_more() {
	check_ajax_referer( 'rdv_articles_load_more', 'nonce' );

	$view      = isset( $_POST['view'] ) ? sanitize_key( wp_unslash( $_POST['view'] ) ) : 'all';
	$offset    = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
	$categorie = isset( $_POST['categorie'] ) ? sanitize_title( wp_unslash( $_POST['categorie'] ) ) : '';
	$pays      = isset( $_POST['pays'] ) ? sanitize_title( wp_unslash( $_POST['pays'] ) ) : '';
	$categorie = rdv_articles_canonicalize_category_slug( $categorie );
	$exclude   = array();

	if ( ! empty( $_POST['exclude'] ) ) {
		$exclude = array_filter( array_map( 'absint', explode( ',', wp_unslash( $_POST['exclude'] ) ) ) );
	}

	$query = rdv_articles_get_query( $view, $categorie, $pays, $offset, $exclude );

	if ( ! $query->have_posts() ) {
		wp_send_json_success(
			array(
				'html'     => '',
				'button'   => '',
				'has_more' => false,
			)
		);
	}

	$card_args = array(
		'show_country' => ! in_array( $view, array( 'par-pays', 'pays' ), true ),
	);
	$cards     = rdv_articles_render_cards( $query, $card_args );
	$loaded  = $offset + (int) $query->post_count;
	$total   = (int) $query->found_posts;
	$args    = array();
	$button  = '';

	if ( $categorie ) {
		$args['categorie'] = $categorie;
	}
	if ( $pays ) {
		$args['pays'] = $pays;
	}
	if ( 'all' === $view && ! empty( $exclude ) ) {
		$args['exclude'] = $exclude;
	}

	if ( $loaded < $total ) {
		$button = rdv_articles_render_load_more_button( $view, $args, $loaded, $total );
	}

	wp_reset_postdata();

	wp_send_json_success(
		array(
			'html'     => $cards,
			'button'   => $button,
			'has_more' => $loaded < $total,
		)
	);
}

/**
 * Construit la requête selon la vue active.
 *
 * @param string   $view
 * @param string   $categorie
 * @param string   $pays
 * @param int      $offset
 * @param int[]    $exclude_ids
 * @param int|null $per_page
 * @return WP_Query
 */
function rdv_articles_get_query( $view, $categorie, $pays, $offset = 0, $exclude_ids = array(), $per_page = null ) {
	// Règles de pagination par vue.
	if ( null === $per_page && 'pays' === $view ) {
		$per_page = RDV_ARTICLES_PER_COUNTRY_PAGE;
	}

	$args = array(
		'post_type'              => 'post',
		'post_status'            => 'publish',
		'posts_per_page'         => null !== $per_page ? (int) $per_page : RDV_ARTICLES_PER_PAGE,
		'offset'                 => (int) $offset,
		'orderby'                => 'date',
		'order'                  => 'DESC',
		'ignore_sticky_posts'    => true,
		'no_found_rows'          => false,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => true,
	);

	if ( ! empty( $exclude_ids ) ) {
		$args['post__not_in'] = array_map( 'intval', $exclude_ids );
	}

	if ( 'pays' === $view && $pays ) {
		$tax  = rdv_articles_destination_taxonomy();
		$term = get_term_by( 'slug', $pays, $tax );
		if ( $term && ! is_wp_error( $term ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => $tax,
					'field'    => 'term_id',
					'terms'    => $term->term_id,
				),
			);
		}
	} elseif ( 'category' === $view && $categorie ) {
		$args['category_name'] = implode( ',', rdv_articles_resolve_category_slugs( $categorie ) );
	}

	$args = rdv_articles_exclude_guides_from_query_args( $args );

	return new WP_Query( $args );
}

// -----------------------------------------------------------------
// Réglages sommaire (global + par article ACF)
// -----------------------------------------------------------------

add_action( 'init', 'rdv_articles_init_toc_defaults', 5 );
function rdv_articles_init_toc_defaults() {
	if ( false === get_option( 'rdv_articles_toc_enabled', false ) ) {
		add_option( 'rdv_articles_toc_enabled', '0' );
	}
	if ( false === get_option( 'rdv_articles_toc_position', false ) ) {
		add_option( 'rdv_articles_toc_position', 'left' );
	}
}

/**
 * Le sommaire est-il activé dans les réglages globaux ?
 */
function rdv_articles_is_toc_globally_enabled() {
	$value = get_option( 'rdv_articles_toc_enabled', '0' );

	if ( false === $value || '' === $value || null === $value ) {
		return false;
	}

	return '1' === (string) $value;
}

add_action( 'admin_init', 'rdv_articles_register_toc_settings' );
function rdv_articles_register_toc_settings() {
	register_setting(
		'rdv_articles_toc',
		'rdv_articles_toc_enabled',
		array(
			'type'              => 'string',
			'sanitize_callback' => function ( $value ) {
				return '1' === $value ? '1' : '0';
			},
			'default'           => '0',
		)
	);

	register_setting(
		'rdv_articles_toc',
		'rdv_articles_toc_position',
		array(
			'type'              => 'string',
			'sanitize_callback' => function ( $value ) {
				return in_array( $value, array( 'left', 'right' ), true ) ? $value : 'left';
			},
			'default'           => 'left',
		)
	);
}

add_action( 'admin_menu', 'rdv_articles_add_toc_settings_page' );
function rdv_articles_add_toc_settings_page() {
	add_options_page(
		__( 'Sommaire articles', 'Avada' ),
		__( 'Sommaire articles', 'Avada' ),
		'manage_options',
		'rdv-articles-toc',
		'rdv_articles_render_toc_settings_page'
	);
}

function rdv_articles_render_toc_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$enabled  = rdv_articles_is_toc_globally_enabled();
	$position = get_option( 'rdv_articles_toc_position', 'left' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Sommaire des articles', 'Avada' ); ?></h1>
		<p><?php esc_html_e( 'Par défaut, le sommaire est désactivé. Activez-le globalement ici ou article par article.', 'Avada' ); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'rdv_articles_toc' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Activer le sommaire', 'Avada' ); ?></th>
					<td>
						<input type="hidden" name="rdv_articles_toc_enabled" value="0" />
						<label>
							<input type="checkbox" name="rdv_articles_toc_enabled" value="1" <?php checked( $enabled ); ?> />
							<?php esc_html_e( 'Afficher le sommaire sur les articles par défaut', 'Avada' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Position du sommaire', 'Avada' ); ?></th>
					<td>
						<select name="rdv_articles_toc_position">
							<option value="left" <?php selected( $position, 'left' ); ?>><?php esc_html_e( 'À gauche', 'Avada' ); ?></option>
							<option value="right" <?php selected( $position, 'right' ); ?>><?php esc_html_e( 'À droite', 'Avada' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Sur mobile, le sommaire s’affiche toujours en haut (menu repliable).', 'Avada' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<hr>
		<p>
			<?php esc_html_e( 'Pour afficher le sommaire sur un article : éditez l’article → colonne « Sommaire de l’article » → « Oui, afficher le sommaire ».', 'Avada' ); ?>
		</p>
	</div>
	<?php
}

add_action( 'acf/init', 'rdv_articles_register_toc_acf_fields' );
function rdv_articles_register_toc_acf_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'                   => 'group_rdv_article_sommaire',
			'title'                 => 'Sommaire de l\'article',
			'fields'                => array(
				array(
					'key'           => 'field_rdv_sommaire_actif',
					'label'         => 'Afficher le sommaire',
					'name'          => 'rdv_sommaire_actif',
					'type'          => 'select',
					'instructions'  => 'Par défaut, le sommaire est masqué. Choisissez « Oui » pour l’afficher sur cet article.',
					'choices'       => array(
						''    => 'Non (par défaut)',
						'oui' => 'Oui, afficher le sommaire',
						'non' => 'Non, ne pas afficher',
					),
					'default_value' => '',
					'allow_null'    => 0,
					'ui'            => 1,
				),
				array(
					'key'           => 'field_rdv_sommaire_position',
					'label'         => 'Position du sommaire',
					'name'          => 'rdv_sommaire_position',
					'type'          => 'select',
					'instructions'  => 'Uniquement sur ordinateur. Sur mobile, le sommaire reste en haut.',
					'choices'       => array(
						''       => 'Par défaut (réglages globaux)',
						'gauche' => 'À gauche',
						'droite' => 'À droite',
					),
					'default_value' => '',
					'allow_null'    => 0,
					'ui'            => 1,
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'post',
					),
				),
			),
			'menu_order'            => 5,
			'position'              => 'side',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		)
	);
}

/**
 * Récupère les réglages effectifs du sommaire pour un article.
 *
 * @param int|null $post_id
 * @return array{enabled: bool, position: string}
 */
function rdv_articles_get_toc_settings( $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();

	$enabled  = rdv_articles_is_toc_globally_enabled();
	$position = get_option( 'rdv_articles_toc_position', 'left' );

	if ( $post_id && function_exists( 'get_field' ) ) {
		$post_enabled = get_field( 'rdv_sommaire_actif', $post_id );

		if ( 'oui' === $post_enabled ) {
			$enabled = true;
		} elseif ( 'non' === $post_enabled ) {
			$enabled = false;
		}

		$post_position = get_field( 'rdv_sommaire_position', $post_id );
		if ( 'gauche' === $post_position ) {
			$position = 'left';
		} elseif ( 'droite' === $post_position ) {
			$position = 'right';
		}
	}

	$settings = array(
		'enabled'  => (bool) apply_filters( 'rdv_articles_toc_enabled', $enabled, $post_id ),
		'position' => apply_filters( 'rdv_articles_toc_position', $position, $post_id ),
	);

	if ( ! in_array( $settings['position'], array( 'left', 'right' ), true ) ) {
		$settings['position'] = 'left';
	}

	return $settings;
}

// -----------------------------------------------------------------
// Sommaire sticky sur les articles (single post)
// -----------------------------------------------------------------

/**
 * Détermine si un article est un guide (pas de sommaire auto).
 *
 * @param int|null $post_id
 * @return bool
 */
function rdv_articles_is_guide( $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	if ( ! $post_id ) {
		return false;
	}

	$guide_category_slugs = array( 'guide-et-conseils-voyage-asie' );

	/**
	 * Permet d'ajouter d'autres slugs de catégories « guide ».
	 *
	 * @param string[] $guide_category_slugs
	 * @param int      $post_id
	 */
	$guide_category_slugs = apply_filters( 'rdv_articles_guide_category_slugs', $guide_category_slugs, $post_id );

	foreach ( $guide_category_slugs as $slug ) {
		if ( has_category( $slug, $post_id ) ) {
			return true;
		}
	}

	return (bool) apply_filters( 'rdv_articles_is_guide', false, $post_id );
}

/**
 * Titre H2 d'une section cross-sell voyages (ex. « Découvrez nos voyages en Chine ! »).
 *
 * @param string $text
 * @return bool
 */
function rdv_articles_is_cross_sell_section_heading( $text ) {
	$text = wp_strip_all_tags( $text );

	return (bool) preg_match( '/découvrez nos voyages/i', $text );
}

/**
 * Détermine si un titre ne doit pas apparaître dans le sommaire.
 *
 * @param int    $level
 * @param string $text
 * @param string $attrs
 * @param bool   $in_cross_sell_section
 * @return bool
 */
function rdv_articles_should_skip_toc_heading( $level, $text, $attrs, $in_cross_sell_section ) {
	// Sous-titres des voyages dans un bloc cross-sell : exclus du sommaire.
	if ( $in_cross_sell_section && $level >= 3 ) {
		return true;
	}

	// Titres générés par Tripzzy / cartes voyages.
	if ( preg_match( '/class=["\'][^"\']*(?:tripzzy-trip-title|tripzzy-cross-selling|tripzzy-trip-listings|fusion-post-cards)/i', $attrs ) ) {
		return true;
	}

	return (bool) apply_filters( 'rdv_articles_skip_toc_heading', false, $level, $text, $attrs, $in_cross_sell_section );
}

add_filter( 'the_content', 'rdv_articles_single_toc', 12 );
function rdv_articles_single_toc( $content ) {
	if ( ! is_singular( 'post' ) ) {
		return $content;
	}

	static $toc_rendered_for = array();

	$post_id = get_the_ID();
	if ( ! $post_id || isset( $toc_rendered_for[ $post_id ] ) ) {
		return $content;
	}

	// N'injecter qu'une fois sur le contenu principal de l'article affiché.
	if ( (int) $post_id !== (int) get_queried_object_id() ) {
		return $content;
	}

	if ( rdv_articles_is_guide( $post_id ) ) {
		return $content;
	}

	$toc_settings = rdv_articles_get_toc_settings( $post_id );
	if ( ! $toc_settings['enabled'] ) {
		return $content;
	}

	if ( false === strpos( $content, '<h2' ) && false === strpos( $content, '<h3' ) ) {
		return $content;
	}

	$toc_rendered_for[ $post_id ] = true;

	$used_ids              = array();
	$toc                   = array();
	$in_cross_sell_section = false;

	$content = preg_replace_callback(
		'/<h([23])([^>]*)>(.*?)<\/h\1>/is',
		function ( $matches ) use ( &$used_ids, &$toc, &$in_cross_sell_section ) {
			$level = (int) $matches[1];
			$attrs = $matches[2];
			$text  = wp_strip_all_tags( $matches[3] );
			$id    = sanitize_title( $text );

			if ( '' === $id ) {
				return $matches[0];
			}

			if ( 2 === $level ) {
				$in_cross_sell_section = rdv_articles_is_cross_sell_section_heading( $text );
			}

			$skip_toc = rdv_articles_should_skip_toc_heading( $level, $text, $attrs, $in_cross_sell_section );

			$base_id = $id;
			$counter = 2;
			while ( in_array( $id, $used_ids, true ) ) {
				$id = $base_id . '-' . $counter;
				++$counter;
			}
			$used_ids[] = $id;

			if ( ! $skip_toc ) {
				$toc[] = array(
					'id'    => $id,
					'text'  => $text,
					'level' => $level,
				);
			}

			if ( preg_match( '/\sid=["\'][^"\']*["\']/', $attrs ) ) {
				$attrs = preg_replace( '/\sid=["\'][^"\']*["\']/', ' id="' . esc_attr( $id ) . '"', $attrs );
			} else {
				$attrs .= ' id="' . esc_attr( $id ) . '"';
			}

			return '<h' . $level . $attrs . '>' . $matches[3] . '</h' . $level . '>';
		},
		$content
	);

	if ( empty( $toc ) ) {
		return $content;
	}

	$post_id   = get_the_ID();
	$panel_id  = 'rdv-toc-panel-' . $post_id;
	$toc_html  = '<aside class="toc-sidebar rdv-toc-sidebar" aria-label="' . esc_attr__( 'Sommaire', 'Avada' ) . '">';
	$toc_html .= '<button type="button" class="toc-sidebar__toggle" aria-expanded="false" aria-controls="' . esc_attr( $panel_id ) . '">';
	$toc_html .= '<span class="toc-sidebar__label">' . esc_html__( 'Sommaire', 'Avada' ) . '</span>';
	$toc_html .= '<span class="toc-sidebar__chevron" aria-hidden="true"></span>';
	$toc_html .= '</button>';
	$toc_html .= '<div class="toc-sidebar__panel" id="' . esc_attr( $panel_id ) . '">';
	$toc_html .= '<ul class="toc-sidebar__list">';

	$is_first = true;
	foreach ( $toc as $item ) {
		$link_class = 'toc-sidebar__link';
		if ( 3 === $item['level'] ) {
			$link_class .= ' toc-sidebar__link--sub';
		}
		if ( $is_first ) {
			$link_class .= ' toc-sidebar__link--active';
			$is_first     = false;
		}

		$toc_html .= '<li class="toc-sidebar__item">';
		$toc_html .= '<a class="' . esc_attr( $link_class ) . '" href="#' . esc_attr( $item['id'] ) . '">';
		$toc_html .= esc_html( $item['text'] );
		$toc_html .= '</a></li>';
	}

	$toc_html .= '</ul></div></aside>';

	$layout_class = 'rdv-single-article-layout';
	if ( 'right' === $toc_settings['position'] ) {
		$layout_class .= ' rdv-single-article-layout--toc-right';
	}

	return '<div class="' . esc_attr( $layout_class ) . '">' . $toc_html . '<div class="rdv-single-article-content">' . $content . '</div></div>';
}
