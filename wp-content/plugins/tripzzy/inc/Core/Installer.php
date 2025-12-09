<?php
/**
 * Installer Class
 *
 * @since 1.3.4
 * @package tripzzy
 */

namespace Tripzzy\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Traits\DataTrait;
use Tripzzy\Core\Bases\PostTypeBase;
use Tripzzy\Core\Bases\TaxonomyBase;
use Tripzzy\Core\Payment;
use Tripzzy\Core\PostTypes;
use Tripzzy\Core\PostMeta;
use Tripzzy\Core\Taxonomies;
use Tripzzy\Core\Helpers\Cron;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\Transient;
use Tripzzy\Core\Helpers\User;
if ( ! class_exists( 'Tripzzy\Core\Installer' ) ) {
	/**
	 * Class For Installer.
	 *
	 * @since 1.3.4
	 */
	class Installer {
		use DataTrait;

		/**
		 * Hook in tabs.
		 *
		 * @since 1.3.4
		 */
		public static function init() {
			if ( ! empty( $GLOBALS['tz_uninstalling_plugin'] ) ) {
				return;
			}
			add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
		}

		/**
		 * Check Tripzzy version and run the updater is required.
		 *
		 * This check is done on all requests and runs if the versions do not match.
		 */
		public static function check_version() {

			$db_version      = MetaHelpers::get_option( 'version' );
			$requires_update = version_compare( $db_version, TRIPZZY_VERSION, '<' );
			if ( $requires_update ) {
				self::install();
				/**
				 * Run after Tripzzy has been updated.
				 *
				 * @since 1.3.4
				 */
				do_action( 'tripzzy_updated' );
			}
		}

		/**
		 * Core function that performs the Tripzzy install.
		 */
		private static function install_core() {
			if ( self::is_new_install() ) {
				MetaHelpers::update_option( 'initial_activation', 'yes' );
			}

			PostTypes::instance(); // Initialize post type.
			PostMeta::instance();
			Taxonomies::instance();
			Payment::instance();
			PostTypeBase::init(); // Just register post type before creating custom post. So it will not throw any warning related to post type not registered.
			TaxonomyBase::init();
			User::add_roles();
			// Cronjob.
			Cron::create();
			// Database Migrations.
			self::migrations_init();
			self::data_upgrade_init();
			// Seeder.
			self::seeder_init();

			self::update_db_version();
		}

		/**
		 * Install Tripzzy.
		 */
		public static function install() {
			if ( ! is_blog_installed() ) {
				return;
			}

			// Check if we are not already running this routine.
			if ( self::is_installing() ) {
				return;
			}

			Transient::set( 'installing', 'yes', MINUTE_IN_SECONDS * 10 );
			tripzzy_maybe_define_constant( 'TRIPZZY_INSTALLING', true );

			try {
				self::install_core();
			} finally {
				Transient::delete( 'installing' );
			}

			MetaHelpers::add_option( 'admin_install_timestamp', time() );

			// Force a flush of rewrite rules even if the corresponding hook isn't initialized yet.
			if ( ! has_action( 'tripzzy_flush_rewrite_rules' ) ) {
				flush_rewrite_rules();
			}

			/**
			 * Flush the rewrite rules after install or update.
			 *
			 * @since 1.3.4
			 */
			do_action( 'tripzzy_flush_rewrite_rules' );

			/**
			 * Run after tripzzy has been installed or updated.
			 *
			 * @since 1.3.4
			 */
			do_action( 'tripzzy_installed' );
		}

		/**
		 * Returns true if we're installing.
		 *
		 * @return bool
		 */
		private static function is_installing() {
			return 'yes' === Transient::get( 'installing' );
		}

		/**
		 * Is this a brand new Tripzzy install?
		 *
		 * A brand new install has no version yet. Also treat empty installs as 'new'.
		 *
		 * @since  1.3.4
		 * @return boolean
		 */
		public static function is_new_install() {
			return is_null( MetaHelpers::get_option( 'version', null ) );
		}

		/**
		 * Update DB Version.
		 *
		 * @since 1.3.4 Moved method from Activator to Installer class.
		 * @since 1.3.4
		 */
		public static function update_db_version() {
			$db_version = MetaHelpers::get_option( 'version' );
			if ( empty( $db_version ) ) {
				MetaHelpers::update_option( 'used_since', TRIPZZY_VERSION );
			}
			MetaHelpers::update_option( 'version', TRIPZZY_VERSION );
		}

		/**
		 * Initialize Seeder.
		 *
		 * @since 1.3.4 Moved method from Activator to Installer class.
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
		 * @since 1.3.4 Moved method from Activator to Installer class.
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
		 * @since 1.3.4 Moved method from Activator to Installer class.
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
}
