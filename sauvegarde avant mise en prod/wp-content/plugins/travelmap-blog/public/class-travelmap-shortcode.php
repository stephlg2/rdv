<?php if ( !defined( 'ABSPATH' ) ) { exit; }

class TravelMap_Shortcode
{
	public function __construct()
	{
		add_shortcode('travelmap', array($this, 'travelmap_html'));
	}

	public function travelmap_html($atts, $content)
	{
		$iframe_data = TravelMap::set_iframe_data($atts);

		ob_start();
		include( TRAVELMAP_PUBLIC_PARTIALS_DIR.'travelmap-iframe.php' );
		$output = ob_get_clean();
		return $output;
	}
}

new TravelMap_Shortcode();