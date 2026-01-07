<?php
/**
 * Tripzzy Admin Custom Fields.
 *
 * Ajoute la gestion du champ "Hébergement" dans l'admin Tripzzy.
 *
 * @package tripzzy
 */

namespace Tripzzy\Admin;

defined( 'ABSPATH' ) || exit;

class TripzzyAdminCustomFields {

    public function __construct() {
        // Sauvegarde du champ hébergement.
        add_action( 'save_post_tripzzy', [ $this, 'save_hebergement_info' ] );

        // Ajout onglet hébergement dans l'admin Tripzzy
        add_filter( 'tripzzy_filter_admin_tabs', [ $this, 'add_hebergement_tab' ] );

        // Affichage contenu onglet hébergement
        add_action( 'tripzzy_action_admin_tab_content_hebergement', [ $this, 'render_hebergement_tab' ] );
    }

    /**
     * Ajoute l’onglet Hébergement dans le tableau de bord admin Tripzzy.
     *
     * @param array $tabs Onglets existants.
     * @return array Onglets modifiés.
     */
    public function add_hebergement_tab( $tabs ) {
        $tabs['hebergement'] = [
            'icon'  => 'dashicons-admin-home',
            'label' => __( 'Hébergement', 'tripzzy' ),
        ];
        return $tabs;
    }

    /**
     * Affiche le contenu de l’onglet Hébergement.
     *
     * @param int $post_id ID du voyage en cours.
     */
    public function render_hebergement_tab( $post_id ) {
        $value = get_post_meta( $post_id, '_hebergement_info', true );
        ?>
        <div class="form-container">
            <h2><?php esc_html_e( 'Informations Hébergement', 'tripzzy' ); ?></h2>
            <textarea 
                name="hebergement_info" 
                id="hebergement_info" 
                rows="10" 
                style="width: 100%;"
                ><?php echo esc_textarea( $value ); ?></textarea>
            <p class="description"><?php esc_html_e( 'Saisissez ici les informations relatives à l’hébergement.', 'tripzzy' ); ?></p>
        </div>
        <?php
    }

    /**
     * Sauvegarde le champ hébergement.
     *
     * @param int $post_id ID du voyage.
     */
    public function save_hebergement_info( $post_id ) {
        // Vérifications sécurité
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['hebergement_info'] ) ) {
            update_post_meta(
                $post_id,
                '_hebergement_info',
                sanitize_textarea_field( wp_unslash( $_POST['hebergement_info'] ) )
            );
        }
    }
}

new TripzzyAdminCustomFields();