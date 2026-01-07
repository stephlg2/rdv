<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.boltonstudios.com/read-more-wp/
 * @since      1.0.0
 *
 * @package    Read_More_Wp
 * @subpackage Read_More_Wp/admin/partials
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

$tabs = $this->settings->rmwp_get_tabs();
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<?php
    // Check user capabilities
     if ( ! current_user_can( 'manage_options' ) ) {
        return;
     }
?>
<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
             
    <?php
        // If the "tab" index of the $_GET array is not empty...
        if( isset( $_GET[ 'tab' ] ) ){

            // ...assign its escaped value to the $active_tab variable.
            $active_tab = esc_attr( sanitize_text_field( $_GET[ 'tab' ] ) );
         } else{

            // Otherwise, assign an escaped default value to the variable.
            $active_tab = esc_attr( 'rmwp_general' );
        }
    ?>
     
    <h2 class="nav-tab-wrapper">
    <?php
        $i = 0;
        foreach( $tabs as $tab){
            $option_display_name = $tab[0];
            $option_group = $tab[1];
            $option_name = $tab[2];
            $active_tab_class = ($active_tab == $option_group) ? 'nav-tab-active' : '';
            echo '<a href="?page=read-more-wp&tab='. esc_attr( $option_group ) .'" id="rmwp-nav-tab-'. esc_attr( $i ) .'" class="nav-tab '. esc_attr( $active_tab_class ) .'">'. esc_attr( $option_display_name ) .'</a>';
            $i++;
        }
    ?>
    </h2>
    <form action="../../wp-admin/options.php" method="post">
    <?php
    
        // Output settings
        settings_fields( esc_attr( $active_tab ) );
        do_settings_sections( esc_attr( $active_tab ) );

        // Output save settings button
        submit_button( 'Save Settings' );
    ?>
    </form>
</div>