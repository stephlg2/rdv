<?php
/**
 * Call the init method when plugin is activated.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Activation;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Bases\PostTypeBase;
use Tripzzy\Core\Bases\TaxonomyBase;
use Tripzzy\Core\PostTypes;
use Tripzzy\Core\PostMeta;
use Tripzzy\Core\Taxonomies;
use Tripzzy\Core\Payment;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\User;
use Tripzzy\Core\Helpers\Cron;

/**
 * Activator class.
 */
class Activator {

	/**
	 * The "Late Static Binding" class name
	 *
	 * @since 1.2.7
	 * @var string
	 */
	private static $called_class = '';

	/**
	 * Init Activator Hook.
	 *
	 * @note While resetting settings, do not reset some settings value assigned from thixs activation hook. like Tripzzy pages, Form fields etc.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 Added PostTypes::instance, PostMeta::instance, Taxonomies::instance, Payment::instance.
	 */
	public static function init() {
		self::$called_class = get_called_class();

		self::update_db_version();
		PostTypes::instance(); // Initialize post type.
		PostMeta::instance();
		Taxonomies::instance();
		Payment::instance();
		PostTypeBase::init(); // Just register post type before creating custom post. So it will not throw any warning related to post type not registered.
		TaxonomyBase::init();
		User::add_roles();
		// Database Migrations.
		self::migrations_init();
		self::data_upgrade_init();
		// Seeder.
		self::seeder_init();

		// Cronjob.
		Cron::create();
		// Flush Rewrite rule.
		flush_rewrite_rules();
		do_action( 'tripzzy_after_activation', self::$called_class );
	}

	/**
	 * Update DB Version.
	 *
	 * @since 1.0.0
	 */
	public static function update_db_version() {
		$db_version = MetaHelpers::get_option( 'version' );
		MetaHelpers::update_option( 'initial_activation', 'false' );
		if ( TRIPZZY_VERSION !== $db_version ) {
			if ( empty( $db_version ) ) {
				MetaHelpers::update_option( 'used_since', TRIPZZY_VERSION );
			}
			MetaHelpers::update_option( 'version', TRIPZZY_VERSION );
		}
	}

	/**
	 * Initialize Seeder.
	 *
	 * @since 1.0.0
	 */
	public static function seeder_init() {
		foreach ( glob( sprintf( '%1$sinc/Core/Seeder/*.php', TRIPZZY_ABSPATH ) ) as $filename ) {
			$namespace  = 'Tripzzy\Core\Seeder';
			$class_name = basename( $filename, '.php' );
			if ( class_exists( $namespace . '\\' . $class_name ) && method_exists( $namespace . '\\' . $class_name, 'init' ) ) {
				call_user_func( $namespace . '\\' . $class_name . '::init' );
			}
		}
	}

	/**
	 * Initialize Migrations.
	 *
	 * @since 1.0.0
	 */
	public static function migrations_init() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( glob( sprintf( '%1$sinc/Core/Migrations/*.php', TRIPZZY_ABSPATH ) ) as $filename ) {
			$namespace  = 'Tripzzy\Core\Migrations';
			$class_name = basename( $filename, '.php' );
			if ( class_exists( $namespace . '\\' . $class_name ) && method_exists( $namespace . '\\' . $class_name, 'init' ) ) {
				call_user_func( $namespace . '\\' . $class_name . '::init' );
			}
		}
	}

	/**
	 * Data Upgrade.
	 *
	 * @since 1.1.3
	 */
	public static function data_upgrade_init() {
		foreach ( glob( sprintf( '%1$sinc/Core/DataUpgrade/*.php', TRIPZZY_ABSPATH ) ) as $filename ) {
			$namespace  = 'Tripzzy\Core\DataUpgrade';
			$class_name = basename( $filename, '.php' );
			if ( class_exists( $namespace . '\\' . $class_name ) && method_exists( $namespace . '\\' . $class_name, 'init' ) ) {
				call_user_func( $namespace . '\\' . $class_name . '::init' );
			}
		}
	}
}
