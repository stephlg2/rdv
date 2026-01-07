<?php if ( !defined( 'ABSPATH' ) ) { exit; }

define('TRAVELMAP_ADMIN_PARTIALS_DIR', plugin_dir_path( __FILE__ ).'partials/');

class TravelMap_Admin
{
	public function __construct()
	{
		// Scripts
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

		// Menu
		add_action('admin_menu', array($this, 'add_admin_menu'), 20);
	}

	public function enqueue_admin_scripts()
	{
		wp_enqueue_style(
			'travelmap', // handle
			plugin_dir_url( __FILE__ ).'css/travelmap-admin.css', // src
			null, // dependencies
			false, // version
			'all' // media query
		);
	}

	public function add_admin_menu()
	{
		if ( !current_user_can('manage_options') ) {
			return;
		}
		add_menu_page(
			'TravelMap', // Page Title
			'TravelMap', // Menu Title
			'manage_options', // Capability
			'travelmap', // Menu Slug
			array($this, 'admin_page_html'), // Display function,
			$this->get_admin_svg_icon(), // Icon
			39 // Menu Position
		);
	}

	public function get_admin_svg_icon()
	{
		$svg = file_get_contents(plugin_dir_path( __FILE__ ).'images/travelmap.svg');

		return 'data:image/svg+xml;base64,' . base64_encode($svg);
	}

	public function admin_page_html()
	{
		// Remove footer text
		add_filter( 'admin_footer_text', array($this, 'clear_admin_footer') );

		// Iframe URL
		$iframe_url = $this->get_admin_page_url();

		// Template
		include( TRAVELMAP_ADMIN_PARTIALS_DIR.'travelmap-admin-page.php' );
	}

	public function clear_admin_footer()
	{
		return '';
	}

	public function get_admin_page_url()
	{
		// Clear user id
		// if ( isset($_REQUEST['clear-account']) && $_REQUEST['clear-account'] === '1' ) {
		// 	$this->clear_user_id();

		// 	die('Account cleared.');
		// }

		// User id
		$wp_user_id = $this->get_user_id();

		// Locale
		$locale = get_locale();
		if ( $locale ) {
			$locale = substr($locale, 0, 2);
		}

		// URL
		return TRAVELMAP_WP_URL.'?'.TRAVELMAP_WP_USER_KEY.'='.$wp_user_id.'&locale='.$locale;
	}

	public function get_user_id()
	{
		$wp_user_id = get_option( TRAVELMAP_WP_USER_KEY );

		if ( !$wp_user_id ) {
			$wp_user_id = $_SERVER['HTTP_HOST'].'-'.$this->str_random( TRAVELMAP_WP_USER_LENGTH );
			add_option( TRAVELMAP_WP_USER_KEY, $wp_user_id );
		}

		return $wp_user_id;
	}
	
	// Deprecated
	public function clear_user_id()
	{
		delete_option( TRAVELMAP_WP_USER_KEY );
	}
	
	public function str_random($length = 16)
	{
		$string = '';

		while (($len = strlen($string)) < $length) {
			$size = $length - $len;

			$bytes = random_bytes($size);

			$string .= substr(str_replace(array('/', '+', '='), '', base64_encode($bytes)), 0, $size);
		}

		return $string;
	}

}

new TravelMap_Admin();
