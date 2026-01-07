<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.boltonstudios.com/read-more-wp/
 * @since      1.0.0
 *
 * @package    Read_More_Wp
 * @subpackage Read_More_Wp/includes
 * @author     Aaron Bolton <aaron@boltonstudios.com>
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Read_More_Wp
 * @subpackage Read_More_Wp/includes
 * @author     Aaron Bolton <aaron@boltonstudios.com>
 */
class Read_More_Wp {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Read_More_Wp_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

    /**
     * The basename of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_basename    
     */
    protected $plugin_basename;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_slug    The string used to uniquely identify this plugin.
     */
    protected $plugin_slug;

    /**
     * 
     *
     * @since    1.0.0
     * @access   private
     * @var      Read_More_Wp_Settings    $plugin_settings
     */
    private $plugin_settings;

    /**
     *
     *
     * @since    1.0.0
     * @access   private
     * @var      Read_More_Wp_Admin    $plugin_admin
     */
    private $plugin_admin;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_basename ) {
		if ( defined( 'READ_MORE_WP_VERSION' ) ) {
			$this->version = READ_MORE_WP_VERSION;
		} else {
			$this->version = '1.0.0';
		}
        $this->plugin_name = 'Read More WP';
        $this->plugin_slug = 'read-more-wp';
        $this->plugin_basename = $plugin_basename;

		$this->rmwp_load_dependencies();
		$this->rmwp_set_locale();
        $this->rmwp_init();
		$this->rmwp_define_admin_hooks();
		$this->rmwp_define_public_hooks();

	}
        
    // Methods
    /**
     * 
     *
     * @since    1.0.0
     */
    public function rmwp_init() {

        $this->plugin_settings = new Read_More_Wp_Settings(
            $this->rmwp_get_plugin_name(),
            $this->rmwp_get_plugin_slug(),
            $this->rmwp_get_version()
        );
        
        $this->plugin_admin = new Read_More_Wp_Admin(
            $this->rmwp_get_plugin_name(),
            $this->rmwp_get_plugin_slug(),
            $this->rmwp_get_version(),
            apply_filters('read-more-wp-settings-override', $this->rmwp_get_plugin_settings())
        );
    }

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Read_More_Wp_Loader. Orchestrates the hooks of the plugin.
	 * - Read_More_Wp_i18n. Defines internationalization functionality.
	 * - Read_More_Wp_Admin. Defines all hooks for the admin area.
	 * - Read_More_Wp_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function rmwp_load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-read-more-wp-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-read-more-wp-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-read-more-wp-admin.php';

        /**
		 * The class responsible for defining all the settings in the plugin admin menu.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-read-more-wp-settings.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-read-more-wp-public.php';

		$this->loader = new Read_More_Wp_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Read_More_Wp_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function rmwp_set_locale() {

		$plugin_i18n = new Read_More_Wp_i18n();

		$this->loader->rmwp_add_action( 'plugins_loaded', $plugin_i18n, 'rmwp_load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function rmwp_define_admin_hooks() {

		$plugin_admin = $this->plugin_admin;

		$this->loader->rmwp_add_action( 'admin_enqueue_scripts', $plugin_admin, 'rmwp_enqueue_styles' );
		$this->loader->rmwp_add_action( 'admin_enqueue_scripts', $plugin_admin, 'rmwp_enqueue_scripts' );
        $this->loader->rmwp_add_action( 'admin_init', $plugin_admin, 'rmwp_init_settings' );
        $this->loader->rmwp_add_action( 'admin_menu', $plugin_admin, 'rmwp_add_options_page' );
        $this->loader->rmwp_add_filter( 'plugin_action_links_' . $this->plugin_basename, $this->plugin_admin, 'rmwp_admin_plugin_listing_actions');

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function rmwp_define_public_hooks() {

		$plugin_public = new Read_More_Wp_Public( $this->rmwp_get_plugin_name(), $this->rmwp_get_version() );

		$this->loader->rmwp_add_action( 'wp_enqueue_scripts', $plugin_public, 'rmwp_enqueue_styles' );
		$this->loader->rmwp_add_action( 'wp_enqueue_scripts', $plugin_public, 'rmwp_enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function rmwp_run() {
		$this->loader->rmwp_run();
	}

    // Getters & Setters
	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function rmwp_get_plugin_name() {
		return $this->plugin_name;
	}

    /**
     * Retreive the basename of the plugin
     *
     * @since     1.0.0
     * @return    string    The basename of the plugin.
     */
    public function rmwp_get_plugin_basename() {
        return $this->plugin_basename;
    }

    /**
     * Set the basename of the plugin
     *
     * @since     1.0.0
     * @return    string    The basename of the plugin.
     */
    public function rmwp_set_plugin_basename( $plugin_basename ) {
        $this->plugin_basename = $plugin_basename;
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function rmwp_get_plugin_slug() {
        return $this->plugin_slug;
    }

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Read_More_Wp_Loader    Orchestrates the hooks of the plugin.
	 */
	public function rmwp_get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function rmwp_get_version() {
		return $this->version;
	}

    /**
     *
     *
     * @return  Read_More_Wp_Settings
     */ 
    public function rmwp_get_plugin_settings()
    {
        return $this->plugin_settings;
    }

    /**
     *
     *
     * @param  Read_More_Wp_Settings  $plugin_settings  $instance
     *
     * @return  self
     */ 
    public function rmwp_set_plugin_settings(Read_More_Wp_Settings $plugin_settings)
    {
        $this->plugin_settings = $plugin_settings;

        return $this;
    }

    /**
     * 
     *
     * @return  Read_More_Wp_Admin
     */ 
    public function rmwp_get_plugin_admin()
    {
        return $this->plugin_admin;
    }

    /**
     *
     *
     * @param  Read_More_Wp_Admin  $plugin_admin  $instance
     *
     * @return  self
     */ 
    public function rmwp_set_plugin_admin(Read_More_Wp_Admin  $plugin_admin)
    {
        $this->plugin_admin = $plugin_admin;

        return $this;
    }

}
