<?php if ( !defined( 'ABSPATH' ) ) { exit; }
/*
Plugin Name: TravelMap Itinerary
Plugin URI: https://wordpress.org/support/plugin/travelmap-blog/
Description: Create and display an interactive travel map on your website. Choose your transport modes, update your itinerary using geolocation, etc.
Version: 1.0.4
Author: TravelMap
Author URI: https://travelmap.net
Text Domain: travelmap-blog
Domain Path: /languages
*/

define('TRAVELMAP_WP_URL', 'https://travelmap.net/itinerary');
define('TRAVELMAP_WP_USER_KEY', 'wp_user_id');
define('TRAVELMAP_WP_USER_LENGTH', 6);

define('TRAVELMAP_IFRAME_DEFAULT_WIDTH', '100%');
define('TRAVELMAP_IFRAME_DEFAULT_HEIGHT', '500');

class TravelMap
{
	public function __construct()
	{
		// i18n
		add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
		
		// Widgets
		add_action('widgets_init', array($this, 'register_widgets'));

		// Public
		include_once plugin_dir_path( __FILE__ ).'public/class-travelmap-public.php';
		
		// Admin
		if ( is_admin() ) {
			include_once plugin_dir_path( __FILE__ ).'admin/class-travelmap-admin.php';
		}
	}

	public function load_plugin_textdomain()
	{
		load_plugin_textdomain( 'travelmap-blog', false, basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	public function register_widgets()
	{
		include_once plugin_dir_path( __FILE__ ).'widgets/class-travelmap-widget.php';
		include_once plugin_dir_path( __FILE__ ).'widgets/class-travelmap-blog-widget.php';

		register_widget('TravelMap_Widget');
		register_widget('TravelMap_Blog_Widget');
	}
	
	public static function set_iframe_data($data)
	{
		//--dev
		// print '<pre>'; print_r($data); print '</pre>';

		// URL
		if ( isset($data['href']) ) {
			$data['url'] = $data['href'];
		}
		else if ( !isset($data['url']) ) {
			$wp_user_id = get_option( TRAVELMAP_WP_USER_KEY );
			$data['url'] = TRAVELMAP_WP_URL.'/'.$wp_user_id.'?map-only=1';
		}

		// Map only
		if ( isset($data['map-only']) && $data['map-only'] && strpos($data['url'], '?') === false ) {
			$data['url'] = $data['url'].'?map-only=1';
		}

		// Width
		if ( !isset($data['width']) ) {
			$data['width'] = TRAVELMAP_IFRAME_DEFAULT_WIDTH;
		}
		// Height
		if ( !isset($data['height']) ) {
			$data['height'] = TRAVELMAP_IFRAME_DEFAULT_HEIGHT;
		}

		return $data;
	}
}

new TravelMap();
