<?php
/**
 * Base Class For Tripzzy.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Bases;

use Tripzzy\Core\Traits\LocaleTrait;
use Tripzzy\Admin\Permalinks;
use Tripzzy\Admin\NavMenuMetaboxes;
use Tripzzy\Admin\Pointers;
use Tripzzy\Admin\InPluginUpdate;
use Tripzzy\Admin\AdminMenu;
use Tripzzy\Admin\Hooks;

use Tripzzy\Core\Assets;
use Tripzzy\Core\Template;
use Tripzzy\Core\PostTypes;
use Tripzzy\Core\PostMeta;
use Tripzzy\Core\TermMeta;
use Tripzzy\Core\Taxonomies;
use Tripzzy\Core\Ajax;
use Tripzzy\Core\Shortcodes;
use Tripzzy\Core\Cart;
use Tripzzy\Core\SessionHandler;
use Tripzzy\Core\Bookings;
use Tripzzy\Core\SendEmails;
use Tripzzy\Core\Payment;
use Tripzzy\Core\ThemeCompatibility;
use Tripzzy\Core\RestApis;
use Tripzzy\Core\Blocks;
use Tripzzy\Core\IgnorePageCache;

use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Forms\Input;

use Tripzzy\Core\Helpers\User;
use Tripzzy\Core\Helpers\Customer;
use Tripzzy\Core\Helpers\TemplateHooks;
use Tripzzy\Core\Helpers\Reviews;
use Tripzzy\Core\Helpers\Schema;
use Tripzzy\Core\Helpers\Cron;

use Tripzzy\Core\BlockTemplates;

use Tripzzy\Core\Activation\Activator;
use Tripzzy\Core\Activation\Deactivator;
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Base Class For Tripzzy.
 *
 * @since 1.0.0
 */
class Base {
	use LocaleTrait;


	/**
	 * Initialize necessary things.
	 *
	 * @return void
	 */
	public function pre_start() {
		$this->init_session();
		$this->init_cart();
	}
	/**
	 * Initialize Tripzzy.
	 */
	public static function start() {
		// Start plugin if compatible.
		do_action( 'tripzzy_before_init' );
		self::include_files();
		self::init_freemius();
		self::init_hooks();
		do_action( 'tripzzy_after_init' );
	}

	/**
	 * Init Freemius.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function init_freemius() {
		global $tripzzy_fs;

		if ( ! $tripzzy_fs ) {
			// Include Freemius SDK.
			include_once sprintf( '%1$sinc/Lib/freemius/start.php', TRIPZZY_ABSPATH );
			$fs_args    = array(
				'id'             => '14142',
				'slug'           => 'tripzzy',
				'type'           => 'plugin',
				'public_key'     => 'pk_7899768e91bd33b18627f5a8b116c',
				'is_premium'     => false,
				'has_addons'     => false,
				'has_paid_plans' => false,
				'menu'           => array(
					'slug'    => 'tripzzy-homepage',
					'account' => false,
					'contact' => false,
					'support' => false,
					'parent'  => array(
						'slug' => 'edit.php?post_type=tripzzy_booking',
					),
				),
			);
			$tripzzy_fs = fs_dynamic_init( $fs_args );
		}
		do_action( 'tripzzy_fs_loaded' );
	}

	/**
	 * Hooks initialization.
	 *
	 * @return void
	 * @since 1.0.0
	 * @since 1.2.3 Added Schema instance method call.
	 * @since 1.2.7 Added IgnorePageCache instance.
	 */
	private static function init_hooks() {
		// Activation.
		register_activation_hook( TRIPZZY_PLUGIN_FILE, array( 'Tripzzy\Core\Activation\Activator', 'init' ) );
		register_deactivation_hook( TRIPZZY_PLUGIN_FILE, array( 'Tripzzy\Core\Activation\Deactivator', 'init' ) );

		add_action( 'after_setup_theme', array( 'Tripzzy\Core\Image', 'add_image_sizes' ) );
		add_filter( 'image_size_names_choose', array( 'Tripzzy\Core\Image', 'list_image_sizes' ) );

		Bookings::init();
		Template::instance(); // Tripzzy Template [archive, single page].
		TemplateHooks::instance();
		Blocks::instance();
		BlockTemplates::instance();
		ThemeCompatibility::instance();
		Reviews::init();
		Customer::init();
		User::init();
		Cron::init();
		AdminMenu::instance();
		Schema::instance();

		/**
		 * Init autoloads.
		 *
		 * @since 1.2.0
		 */
		add_action( 'init', array( __CLASS__, 'init_autoloads' ), 0 );

		// Register Post Type.
		add_action( 'init', array( 'Tripzzy\Core\Bases\PostTypeBase', 'init' ) );
		// Shortcode.
		add_action( 'init', array( 'Tripzzy\Core\Bases\ShortcodeBase', 'init' ) );

		// Add Meta boxes.
		add_action( 'do_meta_boxes', array( 'Tripzzy\Core\Bases\PostTypeBase', 'init_meta_box' ), 10, 3 );

		// Register Taxonomy.
		add_action( 'init', array( 'Tripzzy\Core\Bases\TaxonomyBase', 'init' ) );
		add_action( 'init', array( 'Tripzzy\Core\Bases\TaxonomyBase', 'init_term_meta' ) );

		// Add Filters in settings.
		add_filter( 'tripzzy_filter_default_settings', array( 'Tripzzy\Core\Helpers\TripFilter', 'default_settings_keys' ) );
		if ( Request::is( 'admin' ) ) {
			add_action( 'current_screen', array( 'Tripzzy\Core\Bases\Base', 'admin_init' ) );
			Pointers::instance();
			InPluginUpdate::instance();
			IgnorePageCache::instance();
		}

		// Rest API.
		add_action( 'rest_api_init', array( 'Tripzzy\Core\Bases\RestApiBase', 'init' ) );

		// Blocks.
		if ( function_exists( 'register_block_type' ) ) {
			add_action( 'init', array( 'Tripzzy\Core\Bases\BlocksBase', 'init' ) );
		}
	}

	/**
	 * Init Autoload classes.
	 *
	 * @return void
	 * @since 1.2.0
	 */
	public static function init_autoloads() {
		Ajax::instance();
		PostTypes::instance();
		PostMeta::instance();
		Taxonomies::instance();
		Shortcodes::instance();
		Input::init();
		Assets::instance();
		SendEmails::init();
		Payment::instance();
		RestApis::instance();
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private static function include_files() {
		foreach ( glob( sprintf( '%1$sinc/Admin/Views/Layouts/*.php', TRIPZZY_ABSPATH ) ) as $filename ) {
			include_once $filename;
		}
		require_once sprintf( '%1$svendor/autoload.php', TRIPZZY_ABSPATH );
	}

	/**
	 * Conditional includes for admin pages.
	 * Action Callback : current_screen
	 *
	 * @since 1.0.0
	 */
	public static function admin_init() {
		if ( ! get_current_screen() ) {
			return;
		}

		// Additional hooks.
		Hooks::instance();

		$screen = get_current_screen();
		switch ( $screen->id ) {
			case 'options-permalink':
				new Permalinks();
				return;
			case 'nav-menus':
				new NavMenuMetaboxes();
				return;
		}
	}

	/**
	 * Load Tripzzy Text domain for translation support.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public static function load_plugin_textdomain() {
		$locale = self::get_locale();
		unload_textdomain( 'tripzzy' );
		load_textdomain( 'tripzzy', WP_LANG_DIR . '/tripzzy/tripzzy-' . $locale . '.mo' );
		load_plugin_textdomain( 'tripzzy', false, TRIPZZY_PLUGIN_DIR . '/languages' );
	}

	/**
	 * Initialize the session class.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init_session() {
		/**
		* Filter to overwrite the session class that handles session data for users.
		*/
		if ( is_null( $this->session ) || ! $this->session instanceof SessionHandler ) {
			$this->session = new SessionHandler();
			$this->session->init();
		}
	}

	/**
	 * Iniitializes Cart.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init_cart() {
		if ( is_null( $this->cart ) ) {
			$this->cart = new Cart();
		}
	}
}
