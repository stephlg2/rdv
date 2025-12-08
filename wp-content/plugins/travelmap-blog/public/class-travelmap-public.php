<?php if ( !defined( 'ABSPATH' ) ) { exit; }

define('TRAVELMAP_PUBLIC_PARTIALS_DIR', plugin_dir_path( __FILE__ ).'partials/');

class TravelMap_Public
{
	public function __construct()
	{
		// Shortcodes
		$this->register_shortcodes();
	}

	public function register_shortcodes()
	{
		include_once plugin_dir_path( __FILE__ ).'class-travelmap-shortcode.php';
	}
}

new TravelMap_Public();