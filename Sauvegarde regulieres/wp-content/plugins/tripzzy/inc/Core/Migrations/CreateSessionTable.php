<?php
/**
 * Create Session table on activation if not exists.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Migrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Migrations\CreateSessionTable' ) ) {
	/**
	 * Create Session Table class.
	 *
	 * @since 1.0.0
	 */
	class CreateSessionTable {

		/**
		 * Post Type Key.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $table_name = 'tripzzy_sessions';

		/**
		 * Init create table.
		 *
		 * @since 1.0.0
		 */
		public static function init() {
			self::create();
		}


		/**
		 * Create table.
		 */
		public static function create() {
			global $wpdb;
			$charset_collate = '';
			if ( $wpdb->has_cap( 'collation' ) ) {
				$charset_collate = $wpdb->get_charset_collate();
			}
			$table_name = $wpdb->prefix . self::$table_name;

			// Create Table Query.
			$sql = "CREATE TABLE IF NOT EXISTS $table_name(
            session_id BIGINT UNSIGNED NOT null AUTO_INCREMENT,
			session_key char( 32 ) NOT null,
			session_value longtext NOT null,
			session_expiry BIGINT UNSIGNED NOT null,
			PRIMARY KEY( session_id ),
			UNIQUE KEY session_key( session_key )
			) $charset_collate;";
			dbDelta( $sql );
		}
	}
}
