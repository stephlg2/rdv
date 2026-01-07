<?php if ( !defined( 'ABSPATH' ) ) { exit; }

class TravelMap_Widget extends WP_Widget
{
	public function __construct()
	{
		parent::__construct(
			'travelmap_widget',
			_x('TravelMap', 'widget', 'travelmap-blog'),
			array(
				'description' => _x('Embed your TravelMap', 'widget', 'travelmap-blog')
			)
		);
	}

	public function widget($args, $instance)
	{
		$iframe_data = TravelMap::set_iframe_data($instance);

		echo $args['before_widget'];
		echo $args['before_title'];
		echo apply_filters('widget_title', $instance['title']);
		echo $args['after_title'];
		include( TRAVELMAP_PUBLIC_PARTIALS_DIR.'travelmap-iframe.php' );
		echo $args['after_widget'];
	}

	public function form($instance)
	{
		include( TRAVELMAP_ADMIN_PARTIALS_DIR.'travelmap-widget-form.php' );
	}
}