<?php if ( !defined( 'ABSPATH' ) ) { exit; } ?>
<?php if ( $iframe_data['url'] ): ?>
	<iframe src="<?php echo esc_url($iframe_data['url']); ?>" width="<?php echo esc_attr($iframe_data['width']); ?>" height="<?php echo esc_attr($iframe_data['height']); ?>" class="travelmap-iframe" allow="geolocation" scrolling="yes" frameborder="0" allowfullscreen style="box-shadow: 0 2px 2px 0 rgba(0, 0, 0, 0.14), 0 3px 1px -2px rgba(0, 0, 0, 0.2), 0 1px 5px 0 rgba(0, 0, 0, 0.12);"></iframe>
<?php endif; ?>