<?php
/**
 * Main Tripzzy Class
 *
 * @package tripzzy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Traits.
foreach ( glob( sprintf( '%1$s/Core/Traits/*.php', __DIR__ ) ) as $tripzzy_trait ) {
	require_once $tripzzy_trait;
}
require_once 'Core/Bases/Base.php';
require_once 'Core/Http/Nonce.php';
require_once 'Core/Http/Request.php';

use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Traits\LocaleTrait;

use Tripzzy\Core\Bases\Base;

/**
 * Main Class.
 *
 * @since 1.0.0
 */
final class Tripzzy extends Base {

	use SingletonTrait;

	/**
	 * Session class.
	 *
	 * @var \Tripzzy\Core\Session|null
	 */
	public $session = null;

	/**
	 * Cart.
	 *
	 * @var \Tripzzy\Core\Cart|null
	 */
	public $cart = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->constants();

		require_once sprintf( '%1$sinc/Admin/Notices/Notices.php', TRIPZZY_ABSPATH );
		require_once sprintf( '%1$sinc/Core/Helpers/MetaHelpers.php', TRIPZZY_ABSPATH );
		require_once sprintf( '%1$sinc/Admin/Compatibility/Compatibility.php', TRIPZZY_ABSPATH );
		require_once sprintf( '%1$sinc/Admin/Compatibility/Ajax.php', TRIPZZY_ABSPATH );
		// Set up localisation.
		add_action( 'plugins_loaded', array( 'Tripzzy\Core\Bases\Base', 'load_plugin_textdomain' ) );
		if ( ! tripzzy_compatibility() ) {

			add_action( 'admin_enqueue_scripts', 'tripzzy_compatibility_scripts' );
			// Plugin works if we set it true, but we do not recommend this approach.
			if ( ! tripzzy_use_forcefully() ) {
				return;
			}
		}
		add_action( 'init', array( $this, 'pre_start' ) );
		self::start();
	}

	/**
	 * Define Constants.
	 */
	private function constants() {

		define( 'TRIPZZY_ABSPATH', dirname( TRIPZZY_PLUGIN_FILE ) . '/' );
		define( 'TRIPZZY_PLUGIN_DIR', dirname( plugin_basename( TRIPZZY_PLUGIN_FILE ) ) );
		define( 'TRIPZZY_PLUGIN_DIR_URL', plugin_dir_url( TRIPZZY_PLUGIN_FILE ) );
		define( 'TRIPZZY_SESSION_CACHE_GROUP', 'tripzzy_session_id' );

		// For Compatibility.
		define( 'TRIPZZY_MIN_WP_VERSION', '5.8' );
		define( 'TRIPZZY_MIN_PHP_VERSION', '7.4' );
	}
}
