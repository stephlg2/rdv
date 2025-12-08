<?php
/**
 * Helper class that provides required methods for handling meta keys and options data.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

use Tripzzy\Core\Traits\DataTrait;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\MetaHelpers' ) ) {

	/**
	 * Helper class that provides required methods for handling meta keys and options data.
	 *
	 * @since 1.0.0
	 */
	class MetaHelpers {

		use DataTrait;

		/**
		 * ==============================
		 * Methods related to post types.
		 * ==============================
		 */

		/**
		 * Wrapper method for `update_post_meta`.
		 *
		 * @param int    $post_id    Post ID.
		 * @param string $meta_key   Metadata key.
		 * @param mixed  $meta_value Metadata value.
		 *
		 * @return int|bool Meta ID if the key didn't exist, true on successful update,
		 *                  false on failure or if the value passed to the function
		 *                  is the same as the one that is already in the database.
		 */
		public static function update_post_meta( $post_id, $meta_key, $meta_value ) {

			$action_args = compact( 'post_id', 'meta_key', 'meta_value' );

			do_action( 'tripzzy_before_update_post_meta', $action_args );
			$json_value = self::data_to_json( $meta_value );
			// workaround. By adding one more level of \ escaping using function wp_slash (introduced in WP 3.6), you can compensate for the call to stripslashes().
			$json_value = wp_slash( $json_value );
			$update     = update_post_meta( $post_id, self::get_prefix( $meta_key ), $json_value );

			do_action( 'tripzzy_after_update_post_meta', $action_args );

			return $update;
		}

		/**
		 * Wrapper method for `get_post_meta`.
		 *
		 * @param int    $post_id Post ID.
		 * @param string $key     The meta key to retrieve. By default,
		 *                        returns data for all keys. Default empty.
		 * @param bool   $single  Whether to return a single value. Default true.
		 * @return mixed An array of values if `$single` is false.
		 *               The value of the meta field if `$single` is true.
		 *               False for an invalid `$post_id` (non-numeric, zero, or negative value).
		 *               An empty string if a valid but non-existing post ID is passed.
		 */
		public static function get_post_meta( $post_id, $key = '', $single = true ) {

			$action_args = compact( 'post_id', 'key' );

			do_action( 'tripzzy_before_get_post_meta', $action_args );

			$values = self::json_to_data( get_post_meta( $post_id, self::get_prefix( $key ), $single ) );

			$action_args['values'] = $values;

			do_action( 'tripzzy_after_get_post_meta', $action_args );

			return $values;
		}

		/**
		 * Wrapper method for `delete_post_meta`.
		 *
		 * @param int    $post_id    Post ID.
		 * @param string $meta_key   Metadata name.
		 * @param mixed  $meta_value Optional.
		 *
		 * @return bool True on success, false on failure.
		 */
		public static function delete_post_meta( $post_id, $meta_key, $meta_value = '' ) {

			$action_args = compact( 'post_id', 'meta_key', 'meta_value' );

			do_action( 'tripzzy_before_delete_post_meta', $action_args );

			$delete = delete_post_meta( $post_id, self::get_prefix( $meta_key ), self::data_to_json( $meta_value ) );

			do_action( 'tripzzy_after_delete_post_meta', $action_args );

			return $delete;
		}

		/**
		 * ===============================
		 * Methods related to comments.
		 * ===============================
		 */

		/**
		 * Wrapper method for `update_comment_meta`.
		 *
		 * @param int    $comment_id Comment ID.
		 * @param string $meta_key   Metadata key.
		 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
		 *
		 * @return int|bool Meta ID if the key didn't exist, true on successful update,
		 *                  false on failure or if the value passed to the function
		 *                  is the same as the one that is already in the database.
		 */
		public static function update_comment_meta( $comment_id, $meta_key, $meta_value ) {

			$action_args = compact( 'comment_id', 'meta_key', 'meta_value' );

			do_action( 'tripzzy_before_update_comment_meta', $action_args );

			$json_value = self::data_to_json( $meta_value );
			// workaround. By adding one more level of \ escaping using function wp_slash (introduced in WP 3.6), you can compensate for the call to stripslashes().
			$json_value = wp_slash( $json_value );
			$update     = update_comment_meta( $comment_id, self::get_prefix( $meta_key ), $json_value );

			do_action( 'tripzzy_after_update_comment_meta', $action_args );

			return $update;
		}


		/**
		 * Wrapper for `get_comment_meta`.
		 *
		 * @param int    $comment_id Comment ID.
		 * @param string $key        Optional. The meta key to retrieve. By default,
		 *                           returns data for all keys.
		 * @param bool   $single     Optional. Whether to return a single value.
		 *                           This parameter has no effect if `$key` is not specified.
		 *                           Default true.
		 * @return mixed An array of values if `$single` is false.
		 *               The value of meta data field if `$single` is true.
		 *               False for an invalid `$comment_id` (non-numeric, zero, or negative value).
		 *               An empty string if a valid but non-existing comment ID is passed.
		 */
		public static function get_comment_meta( $comment_id, $key = '', $single = true ) {

			$action_args = compact( 'comment_id', 'key' );

			do_action( 'tripzzy_before_get_comment_meta', $action_args );

			$values = self::json_to_data( get_comment_meta( $comment_id, self::get_prefix( $key ), $single ) );

			$action_args['values'] = $values;

			do_action( 'tripzzy_after_get_comment_meta', $action_args );

			return $values;
		}

		/**
		 * ===============================
		 * Methods related to options table.
		 * ===============================
		 */

		/**
		 * Wrapper method for `update_option`.
		 *
		 * @param string $option   Name of the option to update. Expected to not be SQL-escaped.
		 * @param mixed  $value    Option value.
		 *
		 * @return bool True if the value was updated, false otherwise.
		 */
		public static function update_option( $option, $value ) {

			$action_args = compact( 'option', 'value' );

			do_action( 'tripzzy_before_update_option', $action_args );
			// update_option doesn't need extra wp_slash like in update_post_meta.
			$update = update_option( self::get_prefix( $option ), self::data_to_json( $value ) );

			do_action( 'tripzzy_after_update_option', $action_args );

			return $update;
		}

		/**
		 * Wrapper for `get_option`.
		 *
		 * @param string $option  Name of the option to retrieve. Expected to not be SQL-escaped.
		 * @param mixed  $default_value Optional. Default value to return if the option does not exist.
		 * @return mixed Value of the option. A value of any type may be returned, including
		 *               scalar (string, boolean, float, integer), null, array, object.
		 *               Scalar and null values will be returned as strings as long as they originate
		 *               from a database stored option value. If there is no option in the database,
		 *               boolean `false` is returned.
		 */
		public static function get_option( $option, $default_value = false ) {

			$action_args = compact( 'option', 'default_value' );

			do_action( 'tripzzy_before_get_option', $action_args );

			$values = self::json_to_data( get_option( self::get_prefix( $option ), $default_value ) );

			if ( is_bool( $values ) ) {
				// Option doesn't support boolean value. json_to_data will treat string value for 'true', 'false' as boolean.
				$values = (string) $values;
			}

			$action_args['values'] = $values;

			do_action( 'tripzzy_after_get_option', $action_args );

			return $values;
		}

		/**
		 * Wrapper method for `delete_option`.
		 *
		 * @param string $option Name of the option to delete.
		 * @return bool True if the option was deleted, false otherwise.
		 */
		public static function delete_option( $option ) {

			do_action( 'tripzzy_before_delete_option', $option );

			$delete = delete_option( self::get_prefix( $option ) );

			do_action( 'tripzzy_after_delete_option', $option );

			return $delete;
		}

		/**
		 * ====================================
		 * Methods related to users meta table.
		 * ====================================
		 */


		/**
		 * Wrapper method for `update_user_meta`.
		 *
		 * @param int    $user_id    User ID.
		 * @param string $meta_key   Metadata key.
		 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
		 *
		 * @return int|bool Meta ID if the key didn't exist, true on successful update,
		 *                  false on failure or if the value passed to the function
		 *                  is the same as the one that is already in the database.
		 */
		public static function update_user_meta( $user_id, $meta_key, $meta_value ) {

			$action_args = compact( 'user_id', 'meta_key', 'meta_value' );

			do_action( 'tripzzy_before_update_user_meta', $action_args );
			$json_value = self::data_to_json( $meta_value );
			// workaround. By adding one more level of \ escaping using function wp_slash (introduced in WP 3.6), you can compensate for the call to stripslashes().
			$json_value = wp_slash( $json_value );
			$update     = update_user_meta( $user_id, self::get_prefix( $meta_key ), $json_value );

			do_action( 'tripzzy_after_update_user_meta', $action_args );

			return $update;
		}


		/**
		 * Wrapper method for `get_user_meta`.
		 *
		 * @param int    $user_id User ID.
		 * @param string $key     The meta key to retrieve. By default,
		 *                        returns data for all keys.
		 * @param bool   $single  Optional. Whether to return a single value.
		 *                        This parameter has no effect if `$key` is not specified.
		 *                        Default true.
		 * @return mixed An array of values if `$single` is false.
		 *               The value of meta data field if `$single` is true.
		 *               False for an invalid `$user_id` (non-numeric, zero, or negative value).
		 *               An empty string if a valid but non-existing user ID is passed.
		 */
		public static function get_user_meta( $user_id, $key, $single = true ) {

			$action_args = compact( 'user_id', 'key' );

			do_action( 'tripzzy_before_get_user_meta', $action_args );

			$values = self::json_to_data( get_user_meta( $user_id, self::get_prefix( $key ), $single ) );

			$action_args['values'] = $values;

			do_action( 'tripzzy_after_get_user_meta', $action_args );

			return $values;
		}

		/**
		 * Wrapper method for `delete_user_meta`.
		 *
		 * @param int    $user_id    User ID.
		 * @param string $meta_key   Metadata name.
		 * @param mixed  $meta_value Optional.
		 *
		 * @return bool True on success, false on failure.
		 */
		public static function delete_user_meta( $user_id, $meta_key, $meta_value = '' ) {

			$action_args = compact( 'user_id', 'meta_key', 'meta_value' );

			do_action( 'tripzzy_before_delete_user_meta', $action_args );

			$delete = delete_user_meta( $user_id, self::get_prefix( $meta_key ), self::data_to_json( $meta_value ) );

			do_action( 'tripzzy_after_delete_user_meta', $action_args );

			return $delete;
		}

		/**
		 * ====================================
		 * Methods related to term meta table.
		 * ====================================
		 */


		/**
		 * Wrapper method for `update_term_meta`.
		 *
		 * @param int    $term_id    Term ID.
		 * @param string $meta_key   Metadata key.
		 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
		 *
		 * @return int|bool Meta ID if the key didn't exist, true on successful update,
		 *                  false on failure or if the value passed to the function
		 *                  is the same as the one that is already in the database.
		 */
		public static function update_term_meta( $term_id, $meta_key, $meta_value ) {

			$action_args = compact( 'term_id', 'meta_key', 'meta_value' );

			do_action( 'tripzzy_before_update_term_meta', $action_args );
			$json_value = self::data_to_json( $meta_value );
			// workaround. By adding one more level of \ escaping using function wp_slash (introduced in WP 3.6), you can compensate for the call to stripslashes().
			$json_value = wp_slash( $json_value );
			$update     = update_term_meta( $term_id, self::get_prefix( $meta_key ), $json_value );

			do_action( 'tripzzy_after_update_term_meta', $action_args );

			return $update;
		}


		/**
		 * Wrapper method for `get_term_meta`.
		 *
		 * @param int    $term_id Term ID.
		 * @param string $key     The meta key to retrieve. By default,
		 *                        returns data for all keys.
		 * @param bool   $single  Optional. Whether to return a single value.
		 *                        This parameter has no effect if `$key` is not specified.
		 *                        Default true.
		 * @return mixed An array of values if `$single` is false.
		 *               The value of meta data field if `$single` is true.
		 *               False for an invalid `$term_id` (non-numeric, zero, or negative value).
		 *               An empty string if a valid but non-existing Term ID is passed.
		 */
		public static function get_term_meta( $term_id, $key, $single = true ) {

			$action_args = compact( 'term_id', 'key' );

			do_action( 'tripzzy_before_get_term_meta', $action_args );

			$values = self::json_to_data( get_term_meta( $term_id, self::get_prefix( $key ), $single ) );

			$action_args['values'] = $values;

			do_action( 'tripzzy_after_get_term_meta', $action_args );

			return $values;
		}

		/**
		 * Wrapper method for `delete_term_meta`.
		 *
		 * @param int    $term_id    Term ID.
		 * @param string $meta_key   Metadata name.
		 * @param mixed  $meta_value Optional.
		 *
		 * @return bool True on success, false on failure.
		 */
		public static function delete_term_meta( $term_id, $meta_key, $meta_value = '' ) {

			$action_args = compact( 'term_id', 'meta_key', 'meta_value' );

			do_action( 'tripzzy_before_delete_term_meta', $action_args );

			$delete = delete_term_meta( $term_id, self::get_prefix( $meta_key ), self::data_to_json( $meta_value ) );

			do_action( 'tripzzy_after_delete_term_meta', $action_args );

			return $delete;
		}
	}
}
