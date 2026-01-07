<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.boltonstudios.com/read-more-wp/
 * @since      1.0.0
 *
 * @package    Read_More_Wp
 * @subpackage Read_More_Wp/public
 */
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Read_More_Wp
 * @subpackage Read_More_Wp/public
 * @author     Aaron Bolton <aaron@boltonstudios.com>
 */
class Read_More_Wp_Public {
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The user's settings from the General admin tab.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $general_options    The user's settings from the General admin tab.
     */
    private $general_options;

    /**
     * A variable to keep track of whether the user selected inline or block element breaks.
     *
     * @since    1.0.0
     * @access   private
     * @var      boolean
     */
    private $inline;

    /**
     * A variable to provide an additional closing element for a wrapper if needed.
     *
     * @since    1.1.0
     * @access   private
     * @var      string
     */
    private $close_wrapper;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        // Initialize instance variables.
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->inline = false;
        $this->close_wrapper = "";
        // Get user settings.
        $this->general_options = get_option( 'rmwp_general_options' );
        // Run the init() function once the activated plugins have loaded.
        add_action( 'plugins_loaded', array($this, 'rmwp_init') );
    }

    /**
     * Define additional methods.
     * 
     * @since    1.0.0
     */
    // Define a helper function to initialize the class object.
    function rmwp_init() {
        // Add a new shortcode with the 'start-read-more' tag using the 'construct_start_read_more' callback function.
        add_shortcode( 'start-read-more', array($this, 'rmwp_construct_start_read_more') );
        // Add a new shortcode with the 'end-read-more' tag using the 'construct_end_read_more' callback function.
        add_shortcode( 'end-read-more', array($this, 'rmwp_construct_end_read_more') );
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
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
        wp_enqueue_style(
            $this->plugin_name . '-public-css',
            plugin_dir_url( __FILE__ ) . 'css/read-more-wp-public.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
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
        wp_enqueue_script(
            $this->plugin_name . '-public-js',
            plugin_dir_url( __FILE__ ) . 'js/read-more-wp-public.js',
            array('jquery'),
            $this->version,
            false
        );
    }

    /**
     * [start-read-more]
     *
     * @since    1.0.0
     */
    function rmwp_construct_start_read_more( $user_attributes ) {
        // Initialize variables with default values.
        $rmwp_id = rand();
        // Generate a random number to identify this read-more toggle.
        $inline = false;
        $ellipsis = '...';
        $hide_ellipsis = ( isset( $this->rmwp_get_general_options()['rmwp_ellipsis_toggle'] ) ? $this->rmwp_get_general_options()['rmwp_ellipsis_toggle'] : false );
        $default_more_label = 'Read More';
        $default_less_label = 'Read Less';
        $more_label = ( isset( $this->rmwp_get_general_options()['rmwp_more_button_label'] ) ? $this->rmwp_get_general_options()['rmwp_more_button_label'] : $default_more_label );
        $less_label = ( isset( $this->rmwp_get_general_options()['rmwp_less_button_label'] ) ? $this->rmwp_get_general_options()['rmwp_less_button_label'] : $default_less_label );
        $toggle_break = '';
        $classes = '';
        $animation = null;
        $animation_speed = null;
        $element_type = 'div';
        // Handle attributes.
        if ( isset( $user_attributes ) ) {
            // Set list of supported attributes and their default values.
            $supported_attributes = array(
                'inline'    => $inline,
                'ellipsis'  => $ellipsis,
                'more'      => $more_label,
                'less'      => $less_label,
                'animation' => $animation,
                'speed'     => $animation_speed,
            );
            // Combine user attributes with known attributes and fill in defaults when needed.
            $attributes = shortcode_atts( $supported_attributes, $user_attributes );
            // Assign attribute values to the corresponding local variables.
            $inline = htmlspecialchars( esc_attr( $attributes['inline'] ), ENT_QUOTES );
            $more_label = htmlspecialchars( esc_html( $attributes['more'] ), ENT_QUOTES );
            $less_label = htmlspecialchars( esc_html( $attributes['less'] ), ENT_QUOTES );
            $animation = htmlspecialchars( esc_html( $attributes['animation'] ), ENT_QUOTES );
            $animation_speed = htmlspecialchars( esc_html( $attributes['speed'] ), ENT_QUOTES );
            $user_ellipsis_value = htmlspecialchars( esc_attr( $attributes['ellipsis'] ), ENT_QUOTES );
            // If the user specified ellipsis=false in the shortcode attributes...
            if ( $user_ellipsis_value == false || $user_ellipsis_value == 'false' || $user_ellipsis_value == 'hide' || $user_ellipsis_value == 'off' ) {
                // Set the hide_ellipsis flag to true.
                $hide_ellipsis = true;
            }
        }
        // If the More or Less button labels were set to empty strings, use the defaults.
        $more_label = ( $more_label == '' ? $default_more_label : $more_label );
        $less_label = ( $less_label == '' ? $default_less_label : $less_label );
        // Initialize more variables using updated attributes.
        $btn_args = "'{$rmwp_id}', '{$more_label}', '{$less_label}'";
        $btn_action = "rmwpButtonAction( {$btn_args} )";
        // Assign the current inline variable value to the class instance variable.
        // We want to keep track of the inline value to ensure the closing element matches
        // the opening element, whether a span or a div.
        $this->inline = $inline;
        // If the current inline variable is true, change from the opening element from div to span.
        $element_type = ( $inline ? 'span' : $element_type );
        // Construct the HTML for the element to wrap the hidden, togglable content.
        $toggle_break .= '<' . $element_type . ' class="rmwp-toggle ' . $classes . '" id="rmwp-toggle-' . $rmwp_id . '" style="display: none">';
        // If the $hide_ellipsis flag is true...
        if ( $hide_ellipsis == true ) {
            // Add styles to hide the ellipsis.
            // The ellipsis partly serves as the insertion point for the Read More button,
            // so we do not want to omit it from the DOM; only the rendered HTML.
            $ellipsis = '<span class="ellipsis" id="ellipsis-' . $rmwp_id . '" style="opacity: 0; position: absolute; z-index: -1;">...</span>';
        } else {
            // Construct the default ellipsis HTML.
            $ellipsis = '<span class="ellipsis" id="ellipsis-' . $rmwp_id . '">...</span>';
        }
        // Construct the output elements.
        $button = '<button name="read more" type="button" onclick="' . $btn_action . '">';
        $button .= $more_label;
        $button .= '</button>';
        $read_more = '<span class="rmwp-button-wrap" id="rmwp-button-wrap-' . $rmwp_id . '" style="display: none;">';
        $read_more .= $button;
        $read_more .= '</span>';
        // Assemble the output.
        $output = $ellipsis;
        $output .= $read_more;
        $output .= $toggle_break;
        // Return the output.
        return $output;
    }

    /**
     * [end-read-more]
     *
     * @since    1.0.0
     */
    function rmwp_construct_end_read_more() {
        // If the current inline variable is true, change from the opening element from div to span.
        $element_type = ( $this->inline ? 'span' : 'div' );
        // Set the closing element.
        $output = "</{$element_type}>{$this->close_wrapper}<{$element_type} class='rmwp-toggle-end'></{$element_type}>";
        // Return the output.
        return $output;
    }

    /**
     * Get the value of general_options
     *
     * @since    1.0.0
     */
    public function rmwp_get_general_options() {
        return $this->general_options;
    }

}
