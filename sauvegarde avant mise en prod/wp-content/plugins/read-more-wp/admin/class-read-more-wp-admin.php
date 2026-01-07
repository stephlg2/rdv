<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.boltonstudios.com/read-more-wp/
 * @since      1.0.0
 *
 * @package    Read_More_Wp
 * @subpackage Read_More_Wp/admin
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Read_More_Wp
 * @subpackage Read_More_Wp/admin
 * @author     Aaron Bolton <aaron@boltonstudios.com>
 */
class Read_More_Wp_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_slug    The ID of this plugin.
     */
    private $plugin_slug;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

    /**
     * The tabs that separate plugin options in the admin interface.
     *
     * @since    1.0.0
     * @access   private
     * @var      object  
     */
    private $tabs;

    /**
     * The object that contains methods used to build the plugin settings.
     *
     * @since    1.1.0
     * @access   private
     * @var      Read_More_Wp_Settings 
     */
    private $settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $plugin_slug, $version, $settings ) {

		$this->plugin_name = $plugin_name;
        $this->plugin_slug = $plugin_slug;
        $this->version = $version;
        $this->settings = $settings;
        $this->tabs = $settings->rmwp_get_tabs();

	}

    // Methods
    /**
     * Add plugin admin options page
     *
     * @since    1.0.0
     */
    public function rmwp_add_options_page(){
        
        // Add a new Sub-menu to WordPress Administration.
        add_submenu_page(
            'options-general.php', // string $parent_slug
            $this->plugin_name, // string $page_title
            $this->plugin_name, // string $menu_title
            'manage_options', // string $capability
            $this->plugin_slug, // string $menu_slug
            array( $this, 'rmwp_render_settings_page' ) // callable $function = ''
        );
    }

    /**
     * Add action links to the plugin in the Plugins list table
     *
     * @since    1.1.0
     */
    public function rmwp_admin_plugin_listing_actions( $links ) {
        
        $action_links = [];
        $action_links = array(
            'settings' => '<a href="' . admin_url( 'admin.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', 'domain' ) . '</a>',
        );
        return array_merge( $action_links, $links );
    }

    /**
     * Register the plugin settings
     *
     * @since    1.1.0
     */
    public function rmwp_init_settings(){

        // Register sections in the settings page
        register_setting(
            'rmwp_plus', // option group
            'rmwp_plus_options' // option name
        );
    
        // Tabs
        foreach($this->tabs as $tab){
            register_setting(
                $tab[1], // option group
                $tab[2] // option name
            );
        }
        // Sections
        foreach($this->settings->rmwp_get_sections() as $section){
            add_settings_section(
                $section['id'], // id
                $section['title'], // title
                $section['id'] . '_cb', // callback
                $section['page'] // page
            );
        }
        // Fields
        foreach($this->settings->rmwp_get_settings() as $setting){
            add_settings_field(
                $setting['id'],
                $setting['title'],
                $setting['callback'],
                $setting['tab'],
                $setting['section'], 
                [
                    'label_for' => $setting['id'],
                    'class' => 'rmwp_row'
                ]
            );
        }

        /*
        *
        *  Free Plugin Version Callbacks
        * 
        */

        // Section Callbacks
        // Section callbacks accept an $args parameter, which is an array.
        // $args have the following keys defined: id, title, callback.
        // the values are defined at the add_settings_section() function.

        //
        function rmwp_section_for_defaults_cb( $args ) {
            ?>
            <p id="<?php echo esc_attr( $args['id'] ); ?>">
                Please find the default plugin settings below. You may override the default settings using the shortcode options.
            </p>
            <hr />
            <p id="<?php echo esc_attr( $args['id'] ); ?>-2">
                <strong style="font-size: 14px">Shortcode</strong><br/>
                [start-read-more][end-read-more]
            </p>
            <p id="<?php echo esc_attr( $args['id'] ); ?>-3">
                Example overrides:<br />[start-read-more more="Show More" less="Show Less" inline=true ellipsis=false][end-read-more]

            </p>
            <hr />
            <?php
        }
        function rmwp_section_for_support_cb( $args ) {
            
            ?>
            <hr />
            <p>
                <strong style="font-size: 14px">Get Help</strong><br/>
                Have a question? Choose a Support option below:
                <ul>
                    <li>
                    <span class="dashicons dashicons-arrow-right-alt" style="font-size: 14px;"></span> 
                    Send a message using the <a href="/wp-admin/options-general.php?page=read-more-wp-contact">Contact Form</a>.
                    </li>
                    <li>
                    <span class="dashicons dashicons-arrow-right-alt" style="font-size: 14px;"></span> 
                        Open a ticket on the <a href="https://wordpress.org/support/plugin/read-more-wp/" target="_blank">Support Forum</a>. <span class="dashicons dashicons-external" style="font-size: 14px;"></span>
                    </li>
                    <li>
                    <span class="dashicons dashicons-arrow-right-alt" style="font-size: 14px;"></span> 
                        Upgrade or downgrade on the <a href="/wp-admin/options-general.php?page=read-more-wp-pricing">Plans and Pricing Page</a>.
                    </li>
                </ul>
            </p>
            <p>
                <strong style="font-size: 14px">Debugging Information</strong><br/>
                Your PHP version is <?php echo esc_html( PHP_VERSION ); ?>.
            </p>
            <hr />
            <?php
        }
        // Field Callbacks
        // Field callbacks can accept an $args parameter, which is an array.
        // $args is defined at the add_settings_field() function.
        // wordpress has magic interaction with the following keys: label_for, class.
        // the "label_for" key value is used for the "for" attribute of the <label>.
        // the "class" key value is used for the "class" attribute of the <tr> containing the field.
        // you can add custom key value pairs to be used inside your callbacks.

        // Read More Default Text callback
        function rmwp_more_button_label_field_cb( $args ) {
            
            // Get the value of the setting we've registered with register_setting()
            $options = get_option('rmwp_general_options');
            
            $setting = ''; // More Button label
            if( isset( $options[$args['label_for']] ) ){
                $setting = $options[$args['label_for']];
            };
            ?>

            <label for="<?php echo esc_attr( $args['label_for'] ); ?>" class="screen-reader-text">"Read More" Button Label</label>
            <input type="text" id="<?php echo esc_attr( $args['label_for'] ); ?>" class="rmwp-setting" name="rmwp_general_options[<?php echo esc_attr( $args['label_for'] ); ?>]" value="<?php echo esc_attr( $setting ); ?>" />

            <?php
        }

        // Read Less Default Text callback
        function rmwp_less_button_label_field_cb( $args ) {
            
            // Get the value of the setting we've registered with register_setting()
            $options = get_option('rmwp_general_options');
            
            $setting = ''; // More Button label
            if( isset( $options[$args['label_for']] ) ){
                $setting = $options[$args['label_for']];
            };
            ?>

            <label for="<?php echo esc_attr( $args['label_for'] ); ?>" class="screen-reader-text">"Read Less" Button Label</label>
            <input type="text" id="<?php echo esc_attr( $args['label_for'] ); ?>" class="rmwp-setting" name="rmwp_general_options[<?php echo esc_attr( $args['label_for'] ); ?>]" value="<?php echo esc_attr( $setting ) ?>" />

            <?php
        }

        // Show Ellipsis callback
        function rmwp_ellipsis_toggle_checkbox_field_cb( $args ) {
            
            // Get the value of the setting we've registered with register_setting()
            $options = get_option('rmwp_general_options');
            
            $setting = ''; // Hide Ellipsis checkbox
            if( isset( $options[$args['label_for']] ) ){
                $setting = $options[$args['label_for']];
            };
            ?>

            <label for="<?php echo esc_attr( $args['label_for'] ); ?>" class="screen-reader-text">Show Ellipsis</label>
            <input name="rmwp_general_options[<?php echo esc_attr( $args['label_for'] ); ?>]" type="checkbox" id="<?php echo esc_attr( $args['label_for'] ); ?>" value="1" <?php esc_attr( checked('1', $setting) ); ?> />
        <?php
        }
    }

    /**
     * Include the HTML code to display the settings page tabs and more.
     *
     * @since    1.1.0
     */
    public function rmwp_render_settings_page() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/read-more-wp-admin-display.php';
    }

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function rmwp_enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Read_More_Wp_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Read_More_Wp_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/read-more-wp-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function rmwp_enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Read_More_Wp_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Read_More_Wp_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/read-more-wp-admin.js', array( 'jquery' ), $this->version, false );

	}
}
