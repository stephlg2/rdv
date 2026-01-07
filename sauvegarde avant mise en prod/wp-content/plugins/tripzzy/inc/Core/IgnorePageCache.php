<?php
/**
 * Ignore page being cached.
 *
 * @since 1.2.7
 * @package tripzzy
 */

namespace Tripzzy\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
// Traits.
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Helpers\Page;
use Tripzzy\Core\Helpers\MetaHelpers;

if ( ! class_exists( 'Tripzzy\Core\IgnorePageCache' ) ) {
	/**
	 * IgnorePageCache class.
	 *
	 * @since 1.2.7
	 */
	class IgnorePageCache {
		use SingletonTrait;

		/**
		 * Active Plugins.
		 *
		 * @since 1.2.7
		 * @var array
		 */
		protected $active_plugins;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->active_plugins = get_option( 'active_plugins' );
			// Third Party Activation.
			add_action( 'rocket_after_activation', array( $this, 'wp_rocket_ignore_cache' ) );

			// Tripzzy Activation.
			add_action( 'tripzzy_after_activation', array( $this, 'wp_rocket_ignore_cache' ) );
		}

		/**
		 * Ignore Tripzzy Page cache for WP Rocket. like checkout page.
		 *
		 * @param string $called_class Called class.
		 * @since 1.2.7
		 * @return void
		 */
		public function wp_rocket_ignore_cache( $called_class = null ) {
			if ( $called_class ) {
				$active_plugins = $this->active_plugins;
				switch ( $called_class ) {
					case 'Tripzzy\Core\Activation\Activator':
						if ( ! in_array( 'wp-rocket/wp-rocket.php', $active_plugins, true ) ) {
							return;
						}
						break;
				}
			}
			$updated = MetaHelpers::get_option( 'wp_rocket_cache_updated' );
			if ( $updated ) {
				return;
			}
			$options       = get_option( 'wp_rocket_settings' );
			$checkout_slug = Page::get_slug( 'checkout' );

			$pages_to_skip = array(
				'/' . $checkout_slug . '/',
			);
			foreach ( $pages_to_skip as $page ) {
				if ( ! $options ) {
					$options['cache_reject_uri']   = array();
					$options['cache_reject_uri'][] = $page;
				} elseif ( isset( $options['cache_reject_uri'] ) && is_array( $options['cache_reject_uri'] ) ) {
					if ( ! in_array( $page, $options['cache_reject_uri'], true ) ) {
						$options['cache_reject_uri'][] = $page;
					}
				}
			}
			update_option( 'wp_rocket_settings', $options );
			MetaHelpers::update_option( 'wp_rocket_cache_updated', true );
		}
	}
}
