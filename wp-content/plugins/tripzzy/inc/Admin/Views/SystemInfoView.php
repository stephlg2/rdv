<?php
/**
 * Views: System Info.
 *
 * @package tripzzy
 */

namespace Tripzzy\Admin\Views;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Admin\Views\SystemInfoView' ) ) {
	/**
	 * SystemInfoView Class.
	 *
	 * @since 1.0.0
	 */
	class SystemInfoView {

		/**
		 * Recommended memory limit
		 *
		 * @var string
		 */
		protected static $recommended_memory_limit = '64M';

		/**
		 * System Info html.
		 *
		 * @since 1.0.0
		 */
		public static function render() {
			global $wpdb;

			?>
			<style>
			
				.tripzzy-system-info{
					margin-bottom:20px;
				}
				.tripzzy-system-info tbody td:first-child{
					width:33%;
				}
			</style>
			<div class="wrap">
				<hr class="wp-header-end">
				<div class="tripzzy-page-wrapper">
					<div id="tripzzy-system-info" class="tripzzy-page tripzzy-system-info">
						<div class="tripzzy-system-info-content">
							<?php self::render_wp_info(); ?>
							<?php self::render_server_info(); ?>
							<?php self::render_active_plugins_info(); ?>
							<?php self::render_current_theme_info(); ?>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Render WP information.
		 *
		 * @return void
		 */
		public static function render_wp_info() {
			?>

			<table class="widefat tripzzy-system-info tripzzy-wp-info" cellspacing="0">
				<thead>
					<tr>
						<th colspan="2" ><h2 style="margin:0; font-size:14px"><?php esc_html_e( 'WordPress environment', 'tripzzy' ); ?></h2></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td data-export-label="<?php esc_html_e( 'WP Version', 'tripzzy' ); ?>"><?php esc_html_e( 'WP Version', 'tripzzy' ); ?>:</td>
						<td><?php bloginfo( 'version' ); ?></td>
					</tr>
					<tr>
						<td data-export-label="<?php esc_html_e( 'Tripzzy Version', 'tripzzy' ); ?>"><?php esc_html_e( 'Tripzzy Version', 'tripzzy' ); ?>:</td>
						<td><?php echo esc_html( TRIPZZY_VERSION ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Home URL', 'tripzzy' ); ?>:</td>
						<td><?php form_option( 'home' ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Site URL', 'tripzzy' ); ?>:</td>
						<td><?php form_option( 'siteurl' ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'WP Memory Limit', 'tripzzy' ); ?>:</td>
						<td>
							<?php
							$recommended_memory_limit = self::convert_memory_size( self::$recommended_memory_limit );
							$wp_memory_limit          = self::convert_memory_size( WP_MEMORY_LIMIT );

							if ( function_exists( 'memory_get_usage' ) ) {
								$system_memory   = self::convert_memory_size( @ini_get( 'memory_limit' ) ); // @phpcs:ignore
								$wp_memory_limit = max( $wp_memory_limit, $system_memory );
							}

							if ( $wp_memory_limit < $recommended_memory_limit ) {
								?>
								<span class="warning"><span class="dashicons dashicons-warning"></span><?php echo esc_html( size_format( $wp_memory_limit ) ); ?> - For better performance, we recommend setting memory to at least <?php echo esc_html( self::$recommended_memory_limit ); ?>. See: <a href="https://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP" target="_blank">Increasing memory allocated to PHP</a></span>
								<?php
							} else {
								?>
								<span class="ok"><?php echo esc_html( size_format( $wp_memory_limit ) ); ?></span>
								<?php
							}
							?>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'WP Multisite', 'tripzzy' ); ?>:</td>
						<td><?php self::render_yes_no( is_multisite() ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'WP Debug Mode', 'tripzzy' ); ?>:</td>
						<td><?php self::render_yes_no( defined( 'WP_DEBUG' ) && WP_DEBUG ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'WP Cron', 'tripzzy' ); ?>:</td>
						<td><?php self::render_yes_no( ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ); ?></td>
					</tr>

					<tr>
						<td><?php esc_html_e( 'Language', 'tripzzy' ); ?>:</td>
						<td><?php echo esc_html( get_locale() ); ?></td>
					</tr>

					<tr>
						<td><?php esc_html_e( 'Upload Directory  Location', 'tripzzy' ); ?>:</td>
						<td>
							<?php
							$upload_dir = wp_upload_dir();
							echo esc_url( $upload_dir['baseurl'] ?? '' );
							?>
						</td>
					</tr>
				</tbody>
			</table>
			<?php
		}

		/**
		 * Render Server information.
		 *
		 * @return void
		 */
		public static function render_server_info() {
			global $wpdb;
			?>

			<table class="widefat tripzzy-system-info tripzzy-server-info" cellspacing="0">
				<thead>
					<tr>
						<th colspan="2" ><h2 style="margin:0; font-size:14px"><?php esc_html_e( 'Server environment', 'tripzzy' ); ?></h2></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php esc_html_e( 'Server Info', 'tripzzy' ); ?>:</td>
						<td>
							<?php
							$software = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
							echo esc_html( $software );
							?>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'PHP Version', 'tripzzy' ); ?>:</td>
						<td>
						<?php
							// Check if phpversion function exists.
						if ( function_exists( 'phpversion' ) ) {
							$php_version = phpversion();
							if ( version_compare( $php_version, TRIPZZY_MIN_PHP_VERSION, '<' ) ) {
								?>
								<span class="error">
									<span class="dashicons dashicons-warning"></span>
									<?php
									/* translators: 1: server php version 2: Minimum recommended php version */
									printf( esc_html__( '%1$s - Recommend  PHP version of %2$s. See:', 'tripzzy' ), esc_html( $php_version ), esc_html( TRIPZZY_MIN_PHP_VERSION ) );
									?>
									<a href="https://wordpress.org/about/requirements/" target="_blank"><?php esc_html_e( 'WordPress Requirements', 'tripzzy' ); ?></a>
								</span>
								<?php
							} else {
								?>
								<span class="yes"><?php echo esc_html( $php_version ); ?></span>
								<?php
							}
						} else {
							esc_html_e( "Couldn't determine PHP version because phpversion() doesn't exist.", 'tripzzy' );
						}
						?>
							</td>
					</tr>
						<?php if ( function_exists( 'ini_get' ) ) : ?>
						<tr>
							<td><?php esc_html_e( 'PHP Post Max Size', 'tripzzy' ); ?>:</td>
							<td><?php echo esc_html( size_format( self::convert_memory_size( ini_get( 'post_max_size' ) ) ) ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'PHP Time Limit', 'tripzzy' ); ?>:</td>
							<td><?php echo esc_html( ini_get( 'max_execution_time' ) ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'PHP Max Input Vars', 'tripzzy' ); ?>:</td>
							<td><?php echo esc_html( ini_get( 'max_input_vars' ) ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'cURL Version', 'tripzzy' ); ?>:</td>
							<td>
								<?php
								if ( function_exists( 'curl_version' ) ) {
									$curl_version = curl_version();
									echo esc_html( $curl_version['version'] ) . ', ' . esc_html( $curl_version['ssl_version'] );
								} else {
									esc_html_e( 'N/A', 'tripzzy' );
								}
								?>
							</td>
						</tr>
						<tr>
						<td>
							<?php esc_html_e( 'SUHOSIN Installed', 'tripzzy' ); ?>:</td>
							<td>
								<?php if ( extension_loaded( 'suhosin' ) ) : ?>
									<span class="dashicons dashicons-yes"></span>
								<?php else : ?>
									&ndash;
								<?php endif; ?>
							</td>
						</tr>
							<?php
					endif;

						if ( $wpdb->use_mysqli ) {
							$ver = mysqli_get_server_info( $wpdb->dbh ); // @phpcs:ignore
						} else {
							$ver = mysql_get_server_info(); // @phpcs:ignore
						}

						if ( ! empty( $wpdb->is_mysql ) && ! stristr( $ver, 'MariaDB' ) ) :
							?>
						<tr>
							<td><?php esc_html_e( 'MySQL Version', 'tripzzy' ); ?>:</td>

							<td>
								<?php
								$mysql_version = $wpdb->db_version();

								if ( version_compare( $mysql_version, '5.7', '<' ) ) {
									?>
									<span class="error">
										<span class="dashicons dashicons-warning"></span>
										<?php
										/* translators: 1: server mysql version */
										printf( esc_html__( '%1$s - We recommend a minimum MySQL version of 5.7. See:', 'tripzzy' ), esc_html( $mysql_version ) );
										?>
										<a href="https://wordpress.org/about/requirements/" target="_blank"><?php esc_html_e( 'WordPress Requirements', 'tripzzy' ); ?></a>
									</span>
									<?php
								} else {
									?>
									<span class="yes"><?php echo esc_html( $mysql_version ); ?></span>
									<?php
								}
								?>
							</td>
						</tr>
						<?php endif; ?>
					<tr>
						<td><?php esc_html_e( 'Max Upload Size', 'tripzzy' ); ?>:</td>

						<td><?php echo esc_html( size_format( wp_max_upload_size() ) ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Default Timezone is UTC', 'tripzzy' ); ?>:</td>

						<td>
						<?php
							$default_timezone = date_default_timezone_get();
						if ( 'UTC' !== $default_timezone ) {
							?>
							<span class="error">
								<span class="dashicons dashicons-warning"></span>
								<?php
								self::render_yes_no( false );
								/* translators: 1: Default Timezone */
								printf( esc_html__( 'Default timezone is %s - it should be UTC', 'tripzzy' ), esc_html( $default_timezone ) );
								?>
							</span>
							<?php
						} else {
							self::render_yes_no( true );
						}
						?>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'PHP Error Log File Location', 'tripzzy' ); ?>:</td>
						<td>
						<?php
							echo esc_html( ini_get( 'error_log' ) );
						?>
							</td>
					</tr>


						<?php
						$fields = array();

						// fsockopen/cURL.
						$fields['fsockopen_curl']['name'] = 'fsockopen/cURL';

						if ( function_exists( 'fsockopen' ) || function_exists( 'curl_init' ) ) {
							$fields['fsockopen_curl']['success'] = true;
						} else {
							$fields['fsockopen_curl']['success'] = false;
						}

						// SOAP.
						$fields['soap_client']['name'] = 'SoapClient';

						if ( class_exists( 'SoapClient' ) ) {
							$fields['soap_client']['success'] = true;
						} else {
							$fields['soap_client']['success'] = false;
							$fields['soap_client']['note']    = sprintf( __( 'Your server does not have the %s class enabled - some gateway plugins which use SOAP may not work as expected.', 'tripzzy' ), '<a href="https://php.net/manual/en/class.soapclient.php">SoapClient</a>' ); // @phpcs:ignore
						}

						// DOMDocument.
						$fields['dom_document']['name'] = 'DOMDocument';

						if ( class_exists( 'DOMDocument' ) ) {
							$fields['dom_document']['success'] = true;
						} else {
							$fields['dom_document']['success'] = false;
							$fields['dom_document']['note']    = sprintf( __( 'Your server does not have the %s class enabled - HTML/Multipart emails, and also some extensions, will not work without DOMDocument.', 'tripzzy' ), '<a href="https://php.net/manual/en/class.domdocument.php">DOMDocument</a>' ); // @phpcs:ignore
						}

						// GZIP.
						$fields['gzip']['name'] = 'GZip';

						if ( is_callable( 'gzopen' ) ) {
							$fields['gzip']['success'] = true;
						} else {
							$fields['gzip']['success'] = false;
							$fields['gzip']['note']    = sprintf( __( 'Your server does not support the %s function - this is required to use the GeoIP database from MaxMind.', 'tripzzy' ), '<a href="https://php.net/manual/en/zlib.installation.php">gzopen</a>' ); // @phpcs:ignore
						}

						// Multibyte String.
						$fields['mbstring']['name'] = 'Multibyte String';

						if ( extension_loaded( 'mbstring' ) ) {
							$fields['mbstring']['success'] = true;
						} else {
							$fields['mbstring']['success'] = false;
							$fields['mbstring']['note']    = sprintf( __( 'Your server does not support the %s functions - this is required for better character encoding. Some fallbacks will be used instead for it.', 'tripzzy' ), '<a href="https://php.net/manual/en/mbstring.installation.php">mbstring</a>' ); // @phpcs:ignore
						}

						// Remote Get.
						$fields['remote_get']['name'] = 'Remote Get Status';

						$response      = wp_remote_get(
							'https://www.paypal.com/cgi-bin/webscr',
							array(
								'timeout'     => 60,
								'user-agent'  => 'tripzzy/' . 1.0,
								'httpversion' => '1.1',
								'body'        => array(
									'cmd' => '_notify-validate',
								),
							)
						);
						$response_code = wp_remote_retrieve_response_code( $response );
						if ( 200 === absint( $response_code ) ) {
							$fields['remote_get']['success'] = true;
						} else {
							$fields['remote_get']['success'] = false;
						}

						foreach ( $fields as $field ) {
							$mark = ! empty( $field['success'] ) ? 'yes' : 'error';
							?>
							<tr>
								<td data-export-label="<?php echo esc_html( $field['name'] ); ?>"><?php echo esc_html( $field['name'] ); ?>:</td>
								<td>
									<?php self::render_yes_no( ! empty( $field['success'] ) ); ?>
									<?php if ( ! empty( $field['note'] ) ) : ?>
										<br/>
										<?php echo wp_kses_data( $field['note'] ); ?>
									<?php endif; ?>
								</td>
							</tr>
							<?php
						}
						?>
				</tbody>
			</table>
			<?php
		}

		/**
		 * Render Active Plugins information.
		 *
		 * @return void
		 */
		public static function render_active_plugins_info() {
			$plugins = (array) get_option( 'active_plugins', array() );
			?>

			<table class="widefat tripzzy-system-info tripzzy-active-plugin-info" cellspacing="0">
				<thead>
					<tr>
						<th colspan="2" ><h2 style="margin:0; font-size:14px"><?php esc_html_e( 'Active Plugins', 'tripzzy' ); ?> (<?php echo esc_html( count( $plugins ) ); ?>)</h2></th>
					</tr>
				</thead>
				<tbody>

					<?php

					if ( is_multisite() ) {
						$network_activated_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
						$plugins                   = array_merge( $plugins, $network_activated_plugins );
					}

					foreach ( $plugins as $plugin ) {

						$plugin_data    = @get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin ); // @phpcs:ignore
						$dirname        = dirname( $plugin );
						$version_string = '';
						$network_string = '';

						if ( ! empty( $plugin_data['Name'] ) ) {

							// Link the plugin name to the plugin url if available.
							$plugin_name = esc_html( $plugin_data['Name'] );

							if ( ! empty( $plugin_data['PluginURI'] ) ) {
								$plugin_name = '<a href="' . esc_url( $plugin_data['PluginURI'] ) . '" title="' . esc_attr__( 'Visit plugin homepage', 'tripzzy' ) . '" target="_blank">' . $plugin_name . '</a>';
							}
							?>
							<tr>
								<td><?php echo wp_kses_post( $plugin_name ); ?></td>
								<td>
									<?php
									/* translators: 1: Author markup  */
									$by_author = sprintf( _x( 'by %s', 'by author', 'tripzzy' ), $plugin_data['Author'] );
									/* translators: 1: Version 2: Version string 3: Netword string  */
									$plugin_version = sprintf( _x( ' %1$s %2$s %3$s', 'plugin version', 'tripzzy' ), $plugin_data['Version'], $version_string, $network_string );
									echo wp_kses_post( $by_author . ' &ndash; ' . $plugin_version );
									?>
								</td>
							</tr>
							<?php
						}
					}
					?>
				</tbody>
			</table>
			<?php
		}

		/**
		 * Render Current Theme information.
		 *
		 * @return void
		 */
		public static function render_current_theme_info() {
			?>

			<table class="widefat tripzzy-system-info tripzzy-theme-info" cellspacing="0">
				<thead>
					<tr>
						<th colspan="2" ><h2 style="margin:0; font-size:14px"><?php esc_html_e( 'Theme', 'tripzzy' ); ?></h2></th>
					</tr>
				</thead>
				<tbody>
					<?php
					require_once ABSPATH . 'wp-admin/includes/theme-install.php';
					$active_theme = wp_get_theme();
					?>
					<tr>
						<td><?php esc_html_e( 'Name', 'tripzzy' ); ?>:</td>
						<td><?php echo esc_html( $active_theme->Name ); // @phpcs:ignore ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Version', 'tripzzy' ); ?>:</td>
						<td><?php echo esc_html( $active_theme->Version ); // @phpcs:ignore ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Author URL', 'tripzzy' ); ?>:</td>
						<td><?php echo esc_url( $active_theme->{'Author URI'} ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Child Theme', 'tripzzy' ); ?>:</td>
						<td>
							<?php
							if ( is_child_theme() ) {
								self::render_yes_no( true );
							} else {
								self::render_yes_no( false, true );
								esc_html_e( 'If you\'re want to modifying a theme, it safe to create a child theme.  See:', 'tripzzy' );
								?>
								<a href="https://developer.wordpress.org/themes/advanced-topics/child-themes/" target="_blank"><?php esc_html_e( 'How to create a child theme', 'tripzzy' ); ?></a>
								<?php
							}
							?>
						</td>
					</tr>
					<?php
					if ( is_child_theme() ) :
						$parent_theme = wp_get_theme( $active_theme->Template ); // @phpcs:ignore
						?>
						<tr>
							<td><?php esc_html_e( 'Parent Theme Name', 'tripzzy' ); ?>:</td>
							<td><?php echo esc_html( $parent_theme->Name ); // @phpcs:ignore ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Parent Theme Version', 'tripzzy' ); ?>:</td>
							<td><?php echo esc_html( $parent_theme->Version ); // @phpcs:ignore ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Parent Theme Author URL', 'tripzzy' ); ?>:</td>
							<td><?php echo esc_url( $parent_theme->{'Author URI'} ); ?></td>
						</tr>
					<?php endif ?>
				</tbody>
			</table>
			<?php
		}


		/**
		 * Convert memory size into bytes.
		 *
		 * @param string $size Memory size.
		 * @return number
		 */
		public static function convert_memory_size( $size ) {
			$l   = substr( $size, -1 );
			$ret = substr( $size, 0, -1 );
			switch ( strtoupper( $l ) ) {
				case 'P': // @phpcs:ignore
					$ret *= 1024;
				case 'T': // @phpcs:ignore
					$ret *= 1024;
				case 'G': // @phpcs:ignore
					$ret *= 1024;
				case 'M': // @phpcs:ignore
					$ret *= 1024;
				case 'K':
					$ret *= 1024;
			}
			return $ret;
		}

		/**
		 * Render Markup for Yes or no value
		 *
		 * @param bool $value            true or false.
		 * @param bool $has_comma_at_end true or false.
		 * @return void
		 */
		public static function render_yes_no( $value, $has_comma_at_end = false ) {
			if ( $value ) :
				?>
				<span class="yes"><span class="dashicons dashicons-yes"></span>Yes<?php echo esc_html( $has_comma_at_end ? ', ' : '' ); ?></span>
			<?php else : ?>
				<span class="no"><span class="dashicons dashicons-no-alt"></span>No<?php echo esc_html( $has_comma_at_end ? ', ' : '' ); ?></span>
				<?php
			endif;
		}
	}
}
