<?php
/**
 * Champ icône Font Awesome sur les catégories d'articles.
 *
 * @package Avada-Child-Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RDV_CATEGORY_FA_ICON_META', 'rdv_fa_icon' );

add_action( 'init', 'rdv_category_icon_register_meta' );
/**
 * Enregistre la meta terme pour l'icône FA.
 */
function rdv_category_icon_register_meta() {
	register_term_meta(
		'category',
		RDV_CATEGORY_FA_ICON_META,
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'rdv_category_icon_sanitize',
			'auth_callback'     => function () {
				return current_user_can( 'manage_categories' );
			},
		)
	);
}

/**
 * Normalise une classe FA en « fa-nom-icon » (format hub articles).
 *
 * @param string $value
 * @return string
 */
function rdv_category_icon_sanitize( $value ) {
	$value = sanitize_text_field( (string) $value );
	$value = preg_replace( '/\s+/', ' ', trim( $value ) );

	if ( '' === $value ) {
		return '';
	}

	// Accepte « fas fa-compass », « fa-compass », « compass ».
	if ( preg_match( '/\bfa-([a-z0-9-]+)\b/i', $value, $matches ) ) {
		return 'fa-' . strtolower( $matches[1] );
	}

	$value = ltrim( strtolower( $value ), 'fa-' );

	return 'fa-' . preg_replace( '/[^a-z0-9-]/', '', $value );
}

/**
 * Récupère l'icône FA d'une catégorie (terme WP).
 *
 * @param int|WP_Term $term
 * @return string Ex. « fa-compass »
 */
function rdv_category_icon_get( $term ) {
	if ( is_numeric( $term ) ) {
		$term = get_term( (int) $term, 'category' );
	}

	if ( ! $term || is_wp_error( $term ) ) {
		return '';
	}

	return (string) get_term_meta( $term->term_id, RDV_CATEGORY_FA_ICON_META, true );
}

add_action( 'admin_enqueue_scripts', 'rdv_category_icon_admin_assets' );
/**
 * Scripts / styles admin (écran catégories uniquement).
 *
 * @param string $hook
 */
function rdv_category_icon_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'category' !== $screen->taxonomy ) {
		return;
	}

	wp_enqueue_style(
		'fontawesome-6',
		'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css',
		array(),
		'6.5.2'
	);

	wp_enqueue_style(
		'rdv-category-icon',
		get_stylesheet_directory_uri() . '/rdv-category-icon.css',
		array( 'fontawesome-6' ),
		'1.0.0'
	);

	wp_enqueue_script(
		'rdv-category-icon',
		get_stylesheet_directory_uri() . '/rdv-category-icon.js',
		array(),
		'1.1.0',
		true
	);

	wp_localize_script(
		'rdv-category-icon',
		'rdvCategoryIcon',
		array(
			'metadataUrl' => get_stylesheet_directory_uri() . '/rdv-fa-icons-metadata.json',
			'i18n'        => array(
				'modalTitle'   => __( 'Bibliothèque Font Awesome', 'Avada' ),
				'search'       => __( 'Rechercher une icône…', 'Avada' ),
				'choose'       => __( 'Parcourir les icônes', 'Avada' ),
				'clear'        => __( 'Effacer', 'Avada' ),
				'close'        => __( 'Fermer', 'Avada' ),
				'noResults'    => __( 'Aucune icône trouvée.', 'Avada' ),
				'loading'      => __( 'Chargement des icônes…', 'Avada' ),
				'loadError'    => __( 'Impossible de charger la bibliothèque d’icônes. Rechargez la page.', 'Avada' ),
				'previewEmpty' => __( 'Aucune icône sélectionnée', 'Avada' ),
			),
		)
	);
}

add_action( 'category_add_form_fields', 'rdv_category_icon_add_form_field' );
/**
 * Champ à la création d'une catégorie.
 */
function rdv_category_icon_add_form_field() {
	rdv_category_icon_render_field( '' );
}

add_action( 'category_edit_form_fields', 'rdv_category_icon_edit_form_field' );
/**
 * Champ à l'édition d'une catégorie.
 *
 * @param WP_Term $term
 */
function rdv_category_icon_edit_form_field( $term ) {
	$icon = rdv_category_icon_get( $term );
	?>
	<tr class="form-field term-rdv-fa-icon-wrap">
		<th scope="row">
			<label for="rdv-fa-icon-input"><?php esc_html_e( 'Icône', 'Avada' ); ?></label>
		</th>
		<td>
			<?php rdv_category_icon_render_field( $icon, false ); ?>
		</td>
	</tr>
	<?php
}

/**
 * Markup du champ icône.
 *
 * @param string $icon
 * @param bool   $wrap_table_row Sur formulaire « ajouter », le champ est dans un div.
 */
function rdv_category_icon_render_field( $icon, $wrap_table_row = true ) {
	$icon = rdv_category_icon_sanitize( $icon );
	$icon_name = preg_replace( '/^fa-/', '', $icon );

	if ( $wrap_table_row ) {
		echo '<div class="form-field term-rdv-fa-icon-wrap">';
		echo '<label for="rdv-fa-icon-input">' . esc_html__( 'Icône', 'Avada' ) . '</label>';
	}

	rdv_category_icon_render_controls( $icon, $icon_name );

	if ( $wrap_table_row ) {
		echo '<p class="description">' . esc_html__( 'Icône affichée dans les filtres de la page Nos articles. Compatible Font Awesome (solid).', 'Avada' ) . '</p>';
		echo '</div>';
	} else {
		echo '<p class="description">' . esc_html__( 'Icône affichée dans les filtres de la page Nos articles. Compatible Font Awesome (solid).', 'Avada' ) . '</p>';
	}
}

/**
 * Contrôles input + aperçu + boutons.
 *
 * @param string $icon
 * @param string $icon_name
 */
function rdv_category_icon_render_controls( $icon, $icon_name ) {
	$has_icon = '' !== $icon;
	?>
	<div class="rdv-fa-icon-field" data-rdv-fa-icon-field>
		<div class="rdv-fa-icon-field__preview" data-rdv-fa-icon-preview>
			<?php if ( $has_icon ) : ?>
				<i class="fas <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></i>
			<?php else : ?>
				<span class="rdv-fa-icon-field__placeholder"><?php esc_html_e( 'Aucune icône', 'Avada' ); ?></span>
			<?php endif; ?>
		</div>
		<div class="rdv-fa-icon-field__controls">
			<input
				type="text"
				id="rdv-fa-icon-input"
				name="<?php echo esc_attr( RDV_CATEGORY_FA_ICON_META ); ?>"
				class="rdv-fa-icon-field__input"
				value="<?php echo esc_attr( $icon ); ?>"
				placeholder="fa-compass"
				autocomplete="off"
				data-rdv-fa-icon-input
			/>
			<button type="button" class="button" data-rdv-fa-icon-browse>
				<?php esc_html_e( 'Parcourir les icônes', 'Avada' ); ?>
			</button>
			<button type="button" class="button-link-delete" data-rdv-fa-icon-clear <?php echo $has_icon ? '' : 'hidden'; ?>>
				<?php esc_html_e( 'Effacer', 'Avada' ); ?>
			</button>
		</div>
	</div>
	<?php
}

add_action( 'created_category', 'rdv_category_icon_save_term_meta' );
add_action( 'edited_category', 'rdv_category_icon_save_term_meta' );
/**
 * Sauvegarde la meta icône.
 *
 * @param int $term_id
 */
function rdv_category_icon_save_term_meta( $term_id ) {
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}

	if ( ! isset( $_POST[ RDV_CATEGORY_FA_ICON_META ] ) ) {
		return;
	}

	$icon = rdv_category_icon_sanitize( wp_unslash( $_POST[ RDV_CATEGORY_FA_ICON_META ] ) );
	update_term_meta( $term_id, RDV_CATEGORY_FA_ICON_META, $icon );
}
