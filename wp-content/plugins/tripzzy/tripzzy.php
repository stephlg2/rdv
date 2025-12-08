<?php
/**
 * Plugin Name: Tripzzy
 * Plugin URI: https://wptripzzy.com
 * Description: Tripzzy is a free travel booking WordPress plugin for creating travel and tour packages for tour operators and agencies quickly and easily.
 * Version: 1.3.0
 * Author: Refresh Themes
 * Author URI: https://refreshthemes.com
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Tested up to: 6.8
 *
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Text Domain: tripzzy
 * Domain Path: /languages/
 *
 * @package tripzzy
 * @author  Refresh Themes
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

define( 'TRIPZZY_VERSION', '1.3.0' );
define( 'TRIPZZY_PLUGIN_FILE', __FILE__ );

if ( ! class_exists( 'Tripzzy' ) ) {
	require_once 'inc/class-tripzzy.php';
}

/**
 * Tripzzy Main Class.
 *
 * @return object
 */
function tripzzy() {
	return Tripzzy::instance();
}
tripzzy();
