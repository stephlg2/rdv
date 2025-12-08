<?php
/**
 * Search Form Rest API.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\RestApis;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\RestApiBase;
use Tripzzy\Core\Helpers\SearchForm;
use Tripzzy\Admin\Permalinks;

if ( ! class_exists( 'Tripzzy\Core\RestApis\SearchFormRestApi' ) ) {
	/**
	 * Search Form Rest API.
	 *
	 * @since 1.0.0
	 */
	class SearchFormRestApi extends RestApiBase {
		/**
		 * API Name space
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $api_namespace = 'tripzzy-search-form/v1';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'tripzzy_filter_rest_api_args', array( $this, 'init_args' ) );
		}

		/**
		 * Rest API arguments.
		 *
		 * $args = array( 'route_name' => $route_args );
		 *
		 * @since 1.0.0
		 */
		protected static function api_args() {
			$args = array(
				// For default fields.
				'/get_fields/' => array(
					'methods'             => 'GET',
					'callback'            => array( 'Tripzzy\Core\RestApis\SearchFormRestApi', 'get_fields' ),
					'permission_callback' => '__return_true',
				),
			);
			return $args;
		}

		// Callbacks.
		/**
		 * Search form fields
		 *
		 * @return array
		 */
		public static function get_fields() {
			return SearchForm::get_fields();
		}
	}
}
