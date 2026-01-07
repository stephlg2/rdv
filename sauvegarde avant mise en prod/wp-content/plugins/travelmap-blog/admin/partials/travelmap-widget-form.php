<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
	$title = isset($instance['title']) ? $instance['title'] : esc_html_x('TravelMap', 'widget form', 'travelmap-blog');
	$width = isset($instance['width']) ? $instance['width'] : TRAVELMAP_IFRAME_DEFAULT_WIDTH;
	$height = isset($instance['height']) ? $instance['height'] : TRAVELMAP_IFRAME_DEFAULT_HEIGHT;
?>
<p>
	<label for="<?php echo $this->get_field_id( 'title' ); ?>">
		<?php echo esc_html_x( 'Title:', 'widget form', 'travelmap-blog' ); ?>
	</label>
	<input type="text" name="<?php echo $this->get_field_name( 'title' ); ?>" id="<?php echo $this->get_field_id( 'title' ); ?>" class="widefat" value="<?php echo esc_attr($title); ?>" />
</p>
<p>
	<label for="<?php echo $this->get_field_id( 'width' ); ?>">
		<?php echo esc_html_x( 'Map width:', 'widget form', 'travelmap-blog' ); ?>
	</label>
	<input type="text" name="<?php echo $this->get_field_name( 'width' ); ?>" id="<?php echo $this->get_field_id( 'width' ); ?>" class="widefat" value="<?php echo esc_attr($width); ?>" />
</p>
<p>
	<label for="<?php echo $this->get_field_id( 'height' ); ?>">
		<?php echo esc_html_x( 'Map height:', 'widget form', 'travelmap-blog' ); ?>
	</label>
	<input type="text" name="<?php echo $this->get_field_name( 'height' ); ?>" id="<?php echo $this->get_field_id( 'height' ); ?>" class="widefat" value="<?php echo esc_attr($height); ?>" />
</p>