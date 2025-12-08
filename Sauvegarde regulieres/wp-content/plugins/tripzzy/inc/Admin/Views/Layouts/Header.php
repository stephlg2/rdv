<?php
/**
 * Views: Header.
 *
 * @package tripzzy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\Icon;
use Tripzzy\Core\Helpers\Page;
/**
 * Admin Header.
 *
 * @param string $title Header title.
 * @param string $type Category of the title.
 * @since 1.0.0
 * @since 1.1.2 Added Themes page.
 */
function tripzzy_get_admin_header( $title = '', $type = '' ) {
	global $pagenow, $post, $pagename;
	$title     = '';
	$type      = '';
	$labels    = Strings::get()['labels'];
	$post_type = '';
	$is_single = 'post.php' === $pagenow || 'post-new.php' === $pagenow;

	if ( ! is_object( $post ) ) {
		$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	} else {
		$post_type = $post->post_type;
	}
	if ( use_block_editor_for_post_type( $post_type ) && $is_single ) {
		return;
	}
	if ( Page::is( 'site-editor', true ) ) {
		return;
	}
	$page_name     = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$taxonomy_name = isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : '';  // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( ! empty( $page_name ) ) {
		switch ( $page_name ) {
			case 'tripzzy-settings':
				$title = $labels['settings'] ?? '';
				break;
			case 'tripzzy-custom-categories': // custom filters.
				$title = $labels['custom_filters'] ?? '';
				break;
			case 'tripzzy-system-info':
				$title = $labels['system_status'] ?? '';
				break;
			case 'tripzzy-themes':
				$title = $labels['themes'] ?? '';
				break;
		}
	} elseif ( ! empty( $taxonomy_name ) ) {
		$taxonomy = get_taxonomy( $taxonomy_name );
		$title    = $taxonomy->label ?? '';

	} elseif ( ! empty( $post_type ) ) {
		$post_type_obj = get_post_type_object( $post_type );
		$title         = $post_type_obj->label;

		if ( $is_single ) { // single page.
			$type  = $title;
			$title = get_the_title();
		}
	}
	?>
	<div class="tripzzy-page-header-container">
		<div class="tripzzy-page-header">
			<div class="tripzzy-brand-info">
				<h2 class="tripzzy-brand-info-icon"><a href="edit.php?post_type=tripzzy_booking&page=tripzzy-homepage"><img src="<?php Icon::get_svg_icon_base_64( 'brand-white', false ); ?>" height="32" /></a></h2>
			</div>
			<div class="tripzzy-admin-nav">
				<a href="edit.php?post_type=tripzzy_booking" class="<?php echo esc_attr( Page::is( 'bookings', true ) ? 'current-menu-item' : '' ); ?>"><i class="fa-regular fa-rectangle-list"></i><?php esc_html_e( 'Bookings', 'tripzzy' ); ?></a>
				<a href="edit.php?post_type=tripzzy_enquiry" class="<?php echo esc_attr( Page::is( 'enquiry', true ) ? 'current-menu-item' : '' ); ?>"><i class="dashicons dashicons-format-status"></i><?php esc_html_e( 'Enquiries', 'tripzzy' ); ?></a>
				<a href="edit.php?post_type=tripzzy" class="<?php echo esc_attr( Page::is( 'trips', true ) ? 'current-menu-item' : '' ); ?>"><?php Icon::render_svg_icon( 'trip', array( 18, 18 ) ); ?><?php esc_html_e( 'Trips', 'tripzzy' ); ?></a>
			</div>
		</div>
	</div>
	<?php
}
