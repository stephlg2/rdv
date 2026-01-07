<?php
/**
 * Cronjob Class
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Traits\DataTrait;

if ( ! class_exists( 'Tripzzy\Core\Helpers\Cron' ) ) {
	/**
	 * Class For Cronjob.
	 *
	 * @since 1.0.0
	 */
	class Cron {

		/**
		 * Initialize cronjob.
		 *
		 * @return void
		 */
		public static function init() {
			add_action( 'cron_schedules', array( __CLASS__, 'cron_schedules' ) ); // Register Schedule.
		}

		/**
		 * Add Tripzzy cron schedules.
		 *
		 * @param array $schedules List of WP scheduled cron jobs.
		 *
		 * @return array
		 */
		public static function cron_schedules( $schedules ) {
			$schedules['tz_monthly']     = array(
				'interval' => 2635200,
				'display'  => __( 'Monthly', 'tripzzy' ),
			);
			$schedules['tz_fifteendays'] = array(
				'interval' => 1296000,
				'display'  => __( 'Every 15 Days', 'tripzzy' ),
			);
			$schedules['tz_min']         = array(
				'interval' => MINUTE_IN_SECONDS,
				'display'  => __( 'Every minute', 'tripzzy' ),
			);
			return $schedules;
		}

		/**
		 * Create cron jobs (clear them first).
		 */
		public static function create() {
			// Clear cronjob.
			self::clear();

			// Create cronjob.
			if ( ! wp_next_scheduled( 'tripzzy_cleanup_sessions' ) ) {
				wp_schedule_event( time(), 'twicedaily', 'tripzzy_cleanup_sessions' );
			}
			if ( ! wp_next_scheduled( 'tripzzy_minutely_check' ) ) {
				wp_schedule_event( time(), 'tz_min', 'tripzzy_minutely_check' );
			}
		}

		/**
		 * Clear Cronjob on Deactivation and activation.
		 */
		public static function clear() {
			wp_clear_scheduled_hook( 'tripzzy_cleanup_sessions' );
			wp_clear_scheduled_hook( 'tripzzy_minutely_check' );
		}
	}
}
