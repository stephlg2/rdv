<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
	$title = isset($instance['title']) ? $instance['title'] : esc_html_x('TravelMap', 'widget form', 'travelmap-blog');
	$url = isset($instance['url']) ? $instance['url'] : '';
	$width = isset($instance['width']) ? $instance['width'] : TRAVELMAP_IFRAME_DEFAULT_WIDTH;
	$height = isset($instance['height']) ? $instance['height'] : TRAVELMAP_IFRAME_DEFAULT_HEIGHT;
	$map_only = isset($instance['map-only']) ? $instance['map-only'] : false;
?>
<p>
	<label for="<?php echo $this->get_field_id( 'title' ); ?>">
		<?php echo esc_html_x( 'Title:', 'widget form', 'travelmap-blog' ); ?>
	</label>
	<input type="text" name="<?php echo $this->get_field_name( 'title' ); ?>" id="<?php echo $this->get_field_id( 'title' ); ?>" class="widefat" value="<?php echo esc_attr($title); ?>" />
</p>
<p>
	<label for="<?php echo $this->get_field_id( 'url' ); ?>">
		<?php echo esc_html_x( 'TravelMap url*:', 'widget form', 'travelmap-blog' ); ?>
	</label>
	<input type="url" name="<?php echo $this->get_field_name( 'url' ); ?>" id="<?php echo $this->get_field_id( 'url' ); ?>" class="widefat" value="<?php echo esc_url($url); ?>" placeholder="<?php echo esc_attr_x( 'https://username.travelmap.net', 'widget form', 'travelmap-blog' ); ?>" required="required" />
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
<p>
	<input type="checkbox" name="<?php echo $this->get_field_name( 'map-only' ); ?>" id="<?php echo $this->get_field_id( 'map-only' ); ?>" class="checkbox" <?php checked( $map_only ); ?>>
	<label for="<?php echo $this->get_field_id( 'map-only' ); ?>">
		<?php echo esc_html_x( 'Map only (hide header and menu)', 'widget form', 'travelmap-blog' ); ?>
	</label>
</p>