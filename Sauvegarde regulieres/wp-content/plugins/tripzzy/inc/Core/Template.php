<?php
/**
 * Tripzzy Template.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\Page;


if ( ! class_exists( 'Tripzzy\Core\Template' ) ) {
	/**
	 * Tripzzy Templates.
	 *
	 * @since 1.0.0
	 */
	class Template {
		use SingletonTrait;

		/**
		 * All available strings.
		 *
		 * @var array
		 */
		private $strings;

		/**
		 * Template path for themes.
		 *
		 * @var array
		 */
		private static $template_path;

		/**
		 * Default Template path for the plugin.
		 *
		 * @var array
		 */
		private static $default_path;

		/**
		 * Constructor.
		 */
		public function __construct() {
			self::$template_path = apply_filters( 'tripzzy_filter_template_path', 'tripzzy/' );
			self::$default_path  = sprintf( '%1$stemplates/', TRIPZZY_ABSPATH );

			add_filter( 'template_include', array( __CLASS__, 'template_include' ) );
			/**
			 * Include Page Template hierarchy for fse.
			 *
			 * @since 1.0.6
			 */
			add_filter( 'page_template_hierarchy', array( __CLASS__, 'page_template_hierarchy' ) );
			/**
			 * Include Taxonomy Template hierarchy for fse.
			 *
			 * @since 1.0.6
			 */
			add_filter( 'index_template_hierarchy', array( __CLASS__, 'taxonomy_template_hierarchy' ) );
			add_filter( 'archive_template_hierarchy', array( __CLASS__, 'taxonomy_template_hierarchy' ) );
			add_filter( 'taxonomy_template_hierarchy', array( __CLASS__, 'taxonomy_template_hierarchy' ) );
		}

		/**
		 * Tripzzy Templates.
		 *
		 * @param string $template Template file full path.
		 * @since 1.0.0
		 * @since 1.0.6 return default template if fse theme is activated.
		 */
		public static function template_include( $template ) {
			if ( tripzzy_is_fse_theme() ) {
				return $template;
			}
			// Load a template for a single trip.
			if ( Page::is( 'trip' ) ) {
				$page_template = self::get_template_file( 'single-tripzzy.php' );
				if ( $page_template ) {
					return $page_template;
				}
			}
			// Load a template for a archive trip.
			if ( Page::is( 'trips' ) ) {
				$page_template = self::get_template_file( 'archive-tripzzy.php' );
				if ( $page_template ) {
					return $page_template;
				}
			}
			// Load a template for a checkout trip.
			if ( Page::is( 'checkout' ) ) {
				$page_template = self::get_template_file( 'page-tripzzy-checkout.php' );
				if ( $page_template ) {
					return $page_template;
				}
			}
			// Load a template for thankyou page.
			if ( Page::is( 'thankyou' ) ) {
				$page_template = self::get_template_file( 'thankyou.php' );
				if ( $page_template ) {
					return $page_template;
				}
			}
			// Load a template for search result page.
			if ( Page::is( 'search-result' ) ) {
				$page_template = self::get_template_file( 'search-result.php' );
				if ( $page_template ) {
					return $page_template;
				}
			}

			// Load a template for search result page.  @since 1.1.1.
			if ( Page::is( 'dashboard' ) ) {
				$page_template = self::get_template_file( 'page-tripzzy-dashboard.php' );
				if ( $page_template ) {
					return $page_template;
				}
			}
			return $template;
		}

		/**
		 * Add Page Template Hierarchy for block templates.
		 *
		 * @since 1.0.6
		 * @param array $templates Template list.
		 * @return array
		 */
		public static function page_template_hierarchy( $templates ) {
			if ( Page::is( 'checkout' ) ) {
				array_unshift( $templates, 'page-tripzzy-checkout.php' );
				return $templates;
			}
			if ( Page::is( 'thankyou' ) ) {
				array_unshift( $templates, 'thankyou.php' );
				return $templates;
			}
			if ( Page::is( 'search-result' ) ) {
				array_unshift( $templates, 'search-result.php' );
				return $templates;
			}
			// @since 1.1.1
			if ( Page::is( 'dashboard' ) ) {
				array_unshift( $templates, 'page-tripzzy-dashboard.php' );
				return $templates;
			}

			return $templates;
		}

		/**
		 * Add Taxonomy Template Hierarchy for block templates.
		 *
		 * @since 1.0.6
		 * @param array $templates Template list.
		 * @return array
		 */
		public static function taxonomy_template_hierarchy( $templates ) {
			// To support taxonomies page as well.
			if ( Page::is( 'trips' ) ) {
				array_unshift( $templates, 'archive-tripzzy.php' );
				return $templates;
			}
			return $templates;
		}

		/**
		 * Get Template.
		 *
		 * @param string $template_name Name of file / template.
		 * @param array  $args Additional argument/variables for the template.
		 * @since 1.0.0
		 */
		public static function get_template( $template_name, $args = array() ) {
			$template = '';
			if ( $template_name ) {
				$file_name = "{$template_name}.php";
				$template  = self::get_template_file( $file_name ); // Just returned template.
			}
			if ( $template ) {
				load_template( $template, false, $args ); // $args since WordPress 5.5
			}
		}

		/**
		 * Get Template Part.
		 *
		 * @param  String $slug Name of slug.
		 * @param  string $name Name of file / template.
		 * @param  array  $args Additional argument/variables for the template.
		 */
		public static function get_template_part( $slug, $name = '', $args = array() ) {
			$template_name = ( $name ) ? "{$slug}-{$name}" : "{$slug}";
			self::get_template( $template_name, $args );
		}

		/**
		 * Get the name of Template file. Do not use this method directly to call template. This will just return template file name if exists.
		 *
		 * @param string $template_name Path of template.
		 * @param array  $args Additional argument/variables for the template.
		 */
		public static function get_template_file( $template_name, $args = array() ) {
			$template_path = self::$template_path;
			$default_path  = self::$default_path;

			// Look templates in theme first.
			$template = locate_template(
				array(
					trailingslashit( $template_path ) . $template_name,
					$template_name,
				),
				false,
				true,
				$args
			);

			if ( ! $template ) { // Load from the plugin if the file is not in the theme.
				$template = $default_path . $template_name;
			}
			if ( file_exists( $template ) ) {
				return $template;
			}
			return false;
		}
	}
}
