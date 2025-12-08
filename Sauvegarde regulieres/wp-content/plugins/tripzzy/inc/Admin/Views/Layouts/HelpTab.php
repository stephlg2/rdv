<?php
/**
 * Views: Help Tab.
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
 * Admin Help Tab.
 *
 * @since 1.1.5
 */
function tripzzy_set_admin_help_tab() {
	$screen = get_current_screen();
	// Sidebar.
	$screen->set_help_sidebar(
		'<p><strong>' . __( 'Information', 'tripzzy' ) . '</strong></p>' .
		/* translators: %s Tripzzy Version */
		'<p><span class="dashicons dashicons-admin-plugins"></span> ' . sprintf( __( 'Version %s', 'tripzzy' ), TRIPZZY_VERSION ) . '</p>' .
		'<p><span class="dashicons dashicons-wordpress"></span> <a href="https://wordpress.org/plugins/tripzzy/" target="_blank">' . __( 'View details', 'tripzzy' ) . '</a></p>' .
		'<p><i class="fa-solid fa-lightbulb"></i> <a href="edit.php?post_type=tripzzy_booking&page=tripzzy-system-info">' . __( 'System info', 'tripzzy' ) . '</a></p>'
	);
	// Overview tab.
	$screen->add_help_tab(
		array(
			'id'      => 'overview',
			'title'   => __( 'Overview', 'tripzzy' ),
			'content' =>
			'<p><strong>' . __( 'Overview', 'tripzzy' ) . '</strong></p>' .
			'<p>' . __(
				'Tripzzy is a free travel booking WordPress plugin for creating travel and tour packages for tour operators and agencies quickly and easily.',
				'tripzzy'
			) . '</p>' .
					'<p>' . __( 'Please use the Help & Support tab to get in touch should you find yourself requiring assistance.', 'tripzzy' ) . '</p>' .
					'',
		)
	);

	// Help tab.
	$screen->add_help_tab(
		array(
			'id'      => 'help',
			'title'   => __( 'Help & Support', 'tripzzy' ),
			'content' =>
			'<p><strong>' . __( 'Help & Support', 'tripzzy' ) . '</strong></p>' .
			'<p>' . __( 'Tripzzy is always willing to help you with your plugin. We want you to ensure you have a seamless and enjoyable experience while using the Tripzzy Plugin. If you run into any difficulties, there are several places you can find help:', 'tripzzy' ) . '</p>' .
			'<ul>' .
				'<li>' . __( '<a href="https://docs.wptripzzy.com" target="_blank">Documentation</a> <br/>Stuck somewhere, please refer to our official documentation for assistence. It will help you to build a travel website using tripzzy.', 'tripzzy' ) . '</li>' .
				'<li>' . __( '<a href="https://wordpress.org/support/plugin/tripzzy/" target="_blank">Support</a> <br/>If you need assistance in Tripzzy plugin, please submit your query without hesitation. Our support representative will answer your query as soon as possible.', 'tripzzy' ) . '</li>' .
			'</ul>',
		)
	);
}
