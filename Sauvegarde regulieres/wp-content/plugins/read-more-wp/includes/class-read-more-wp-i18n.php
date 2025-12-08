<?php

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 * 
 * @link       https://www.boltonstudios.com/read-more-wp/
 * @since      1.0.0
 *
 * @package    Read_More_Wp
 * @subpackage Read_More_Wp/includes
 * @author     Aaron Bolton <aaron@boltonstudios.com>
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Define class.
class Read_More_Wp_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function rmwp_load_plugin_textdomain() {

		load_plugin_textdomain(
			'read-more-wp',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
