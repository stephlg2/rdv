<?php if ( !defined( 'ABSPATH' ) ) { exit; }

class TravelMap_Blog_Widget extends WP_Widget
{
	public function __construct()
	{
		parent::__construct(
			'travelmap_iframe',
			_x('TravelMap Blog', 'widget', 'travelmap-blog'),
			array(
				'description' => _x('Embed a TravelMap blog', 'widget', 'travelmap-blog')
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
		include( TRAVELMAP_ADMIN_PARTIALS_DIR.'travelmap-blog-widget-form.php' );
	}

	public function update($new_instance, $instance)
	{
		$new_instance['map-only'] = isset($new_instance['map-only']) && $new_instance['map-only'] === 'on';
		
		return $new_instance;
	}
}