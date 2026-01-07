<?php
/**
 * Display Plugin upgrade Notices.
 *
 * @package tripzzy
 * @since 1.1.5
 */

namespace Tripzzy\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Traits\SingletonTrait;

if ( ! class_exists( 'Tripzzy\Admin\InPluginUpdate' ) ) {

	/**
	 * Main Class.
	 *
	 * @since 1.1.5
	 */
	class InPluginUpdate {
		use SingletonTrait;

		/**
		 * In Plugin Update Endpoint.
		 *
		 * @since 1.1.5
		 * @var $endpoint
		 */
		public $endpoint = 'https://plugins.svn.wordpress.org/tripzzy/trunk/upgrade-notice.txt';

		/**
		 * Constructor.
		 *
		 * @since 1.1.5
		 */
		public function __construct() {
			add_action( 'in_plugin_update_message-tripzzy/tripzzy.php', array( $this, 'in_plugin_update_message' ), 10, 2 );
		}

		/**
		 * Add update Notice in admin plugins page.
		 *
		 * @param array $args     Plugin info/data.
		 * @param array $response Plugin response data from wordpress.org plugin repo.
		 */
		public function in_plugin_update_message( $args, $response ) {
			if ( ! $args ) {
				return;
			}
			$version        = $response->new_version;
			$transient_name = 'tripzzy_update_message_' . $version;
			$upgrade_notice = get_transient( $transient_name );
			if ( false === $upgrade_notice ) {
				$response = wp_safe_remote_get( $this->endpoint );

				if ( ! is_wp_error( $response ) && ! empty( $response['body'] ) ) {
					$upgrade_notice = $this->parse_update_notice( $response['body'], $version );
					set_transient( $transient_name, $upgrade_notice, DAY_IN_SECONDS );
				}
			}
			echo wp_kses_post( $upgrade_notice );
		}

		/**
		 * Parse Notice.
		 *
		 * @param string $content     Notice content.
		 * @param string $new_version New version in repo.
		 */
		public function parse_update_notice( $content, $new_version ) {

			$version_parts     = explode( '.', $new_version );
			$check_for_notices = array(
				$version_parts[0] . '.0', // Major.
				$version_parts[0] . '.0.0', // Major.
				$version_parts[0] . '.' . $version_parts[1], // Minor.
				$version_parts[0] . '.' . $version_parts[1] . '.' . $version_parts[2], // Patch.
			);

			$notice_regexp     = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote( $new_version ) . '\s*=|$)~Uis'; // @phpcs:ignore
			$upgrade_notice = '';
			foreach ( $check_for_notices as $check_version ) {
				if ( version_compare( TRIPZZY_VERSION, $check_version, '>' ) ) {
					continue;
				}
				$matches = null;
				if ( preg_match( $notice_regexp, $content, $matches ) ) {
					$notices = (array) preg_split( '~[\r\n]+~', trim( $matches[2] ) );
					if ( version_compare( trim( $matches[1] ), $check_version, '=' ) ) {
						$upgrade_notice .= '<br/><br/><span class="tripzzy-plugin-upgrade-notice"><strong>Note: </strong>';

						foreach ( $notices as $index => $line ) {
							$upgrade_notice .= preg_replace( '~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}">${1}</a>', $line );
						}
						$upgrade_notice .= '<span>';
					}
					if ( $upgrade_notice ) {
						break;
					}
				}
			}
			return $upgrade_notice;
		}
	}
}
