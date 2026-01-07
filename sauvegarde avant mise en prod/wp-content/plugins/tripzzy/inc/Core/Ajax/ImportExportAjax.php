<?php
/**
 * Import/Export Ajax class.
 *
 * @since 1.0.8
 * @package tripzzy
 */

namespace Tripzzy\Core\Ajax;

use Tripzzy\Core\Bases\PostTypeBase;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Traits\SingletonTrait;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Ajax\ImportExportAjax' ) ) {

	/**
	 * Import/Export Ajax class.
	 *
	 * @since 1.0.8
	 */
	class ImportExportAjax {

		use SingletonTrait;

		/**
		 * All available strings.
		 *
		 * @var array
		 * @since 1.0.8
		 */
		private $strings;

		/**
		 * Initialize class.
		 *
		 * @since 1.0.8
		 */
		public function __construct() {
			$this->strings = Strings::messages();

			add_action( 'wp_ajax_tripzzy_import_trips', array( $this, 'import' ) );
			add_action( 'wp_ajax_tripzzy_export_trips', array( $this, 'export' ) );
		}

		/**
		 * Handle content import.
		 *
		 * @since 1.0.8
		 * @return void
		 */
		public function import() {
			if ( ! Nonce::verify() ) {
				$message = array(
					'message' => $this->strings['nonce_verification_failed'],
				);
				wp_send_json_error( $message );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Access denied.', 'tripzzy' ),
					)
				);
			}

			$payload = Request::get_payload();

			if ( empty( $payload['post_type'] ) && empty( $payload['content'] ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Maybe invalid file. Contents empty.', 'tripzzy' ),
					)
				);
			}

			if ( ! class_exists( 'WP_Import' ) ) {
				require_once trailingslashit( TRIPZZY_ABSPATH ) . 'inc/Lib/wordpress-importer/wordpress-importer.php';
			}

			$filename = wp_tempnam( "tripzzy-{$payload['post_type']}" );
			file_put_contents( $filename, base64_decode( $payload['content'] ) ); // @phpcs:ignore

			ob_start();
			$wp_import                    = new \WP_Import();
			$wp_import->fetch_attachments = true;
			$wp_import->import( $filename );
			$result = ob_get_clean();

			if ( file_exists( $filename ) && is_readable( $filename ) ) {
				unlink( $filename ); // @phpcs:ignore
			}

			wp_send_json_success( $result );
		}

		/**
		 * Handle content exports.
		 *
		 * @since 1.0.8
		 * @return void
		 */
		public function export() {
			if ( ! Nonce::verify() ) {
				$message = array(
					'message' => $this->strings['nonce_verification_failed'],
				);
				wp_send_json_error( $message );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Access denied.', 'tripzzy' ),
					)
				);
			}

			$payload      = Request::get_payload();
			$post_types   = PostTypeBase::get_post_types();
			$post_types[] = 'attachment';

			$current_index = ! empty( $payload['current'] ) ? absint( $payload['current'] ) : 0;
			$posttype      = isset( $post_types[ $current_index ] ) ? $post_types[ $current_index ] : false;

			if ( false === $posttype ) {

				$timestamp = time();

				wp_send_json_success(
					array(
						'status'   => 'done',
						'progress' => 100,
						'filename' => 'tripzzy-export-' . sanitize_title( str_replace( array( 'https://', 'http://' ), '', home_url() ) ) . '-' . $timestamp . '.json',
						'metadata' => array(
							'version'   => TRIPZZY_VERSION,
							'timestamp' => $timestamp,
						),
					)
				);
			}

			if ( ! function_exists( 'export_wp' ) ) {
				require_once ABSPATH . 'wp-admin/includes/export.php';
			}

			ob_start();
			\export_wp(
				array(
					'content' => $posttype,
				)
			);
			$content  = ob_get_clean();
			$progress = ( ( $current_index + 1 ) / count( $post_types ) ) * 100;

			wp_send_json_success(
				array(
					'status'   => 'in-progress',
					'progress' => $progress,
					'posttype' => $posttype,
					'content'  => base64_encode( $content ), // @phpcs:ignore
				)
			);
		}
	}
	ImportExportAjax::instance();
}
