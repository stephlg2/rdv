<?php
/**
 * Prevent theme hijack — force Avada Child.
 */
add_filter('pre_option_template', function () { return 'Avada'; });
add_filter('pre_option_stylesheet', function () { return 'Avada-Child-Theme'; });
add_action('admin_init', function () {
    if (get_option('stylesheet') !== 'Avada-Child-Theme' || get_option('template') !== 'Avada') {
        update_option('template', 'Avada');
        update_option('stylesheet', 'Avada-Child-Theme');
    }
});
