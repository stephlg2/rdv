<?php
/**
 * Tripzzy Post type.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\PostTypes;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\PostTypeBase;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\Icon;
use Tripzzy\Admin\Permalinks;

if ( ! class_exists( 'Tripzzy\Core\PostTypes\TripzzyPostType' ) ) {
	/**
	 * Tripzzy Post Type Class.
	 *
	 * @since 1.0.0
	 */
	class TripzzyPostType extends PostTypeBase {
		/**
		 * Post Type Key to register post type.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $post_type = 'tripzzy';

		/**
		 * Permalinks.
		 *
		 * @since 1.0.0
		 * @var array
		 */
		protected static $slugs;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$post_type   = self::$post_type;
			self::$slugs = Permalinks::get();
			add_filter( 'tripzzy_filter_post_type_args', array( $this, 'init_args' ) );
			add_filter( 'tripzzy_filter_meta_box_args', array( $this, 'init_meta_box_args' ), 10, 2 );

			// Additional Hooks.
			add_action( 'do_meta_boxes', array( $this, 'remove_metaboxes' ) );

			// Always open Forms Metabox.
			$screen_id = $post_type;
			$box_id    = sprintf( '%s__trip_options', $screen_id );
			add_filter( "postbox_classes_{$screen_id}_{$box_id}", '__return_empty_array' );

			/*
			* Filter Hook : Admin Column - Headings.
			*/
			add_filter( 'manage_edit-' . $post_type . '_columns', array( $this, 'admin_column_headings' ) );

			/*
			* Action Hook : Admin Column - Content.
			*/
			add_action( 'manage_' . $post_type . '_posts_custom_column', array( $this, 'admin_column_contents' ), 10, 2 );
		}

		/**
		 * Post Type arguments.
		 *
		 * @since 1.0.0
		 */
		protected static function post_type_args() {
			$labels = array(
				'add_new'            => _x( 'New Trip', 'tripzzy', 'tripzzy' ),
				'add_new_item'       => __( 'Add New Trip', 'tripzzy' ),
				'all_items'          => __( 'All Trips', 'tripzzy' ),
				'edit_item'          => __( 'Edit Trip', 'tripzzy' ),
				'menu_name'          => _x( 'Trips', 'admin menu', 'tripzzy' ),
				'name'               => _x( 'Trips', 'post type general name', 'tripzzy' ),
				'name_admin_bar'     => _x( 'Trip', 'add new on admin bar', 'tripzzy' ),
				'new_item'           => __( 'New Trip', 'tripzzy' ),
				'not_found'          => __( 'No Trips found.', 'tripzzy' ),
				'not_found_in_trash' => __( 'No Trips found in Trash.', 'tripzzy' ),
				'parent_item_colon'  => __( 'Parent trips:', 'tripzzy' ),
				'search_items'       => __( 'Search trips', 'tripzzy' ),
				'singular_name'      => _x( 'Trips', 'post type singular name', 'tripzzy' ),
				'view_item'          => __( 'View Trip', 'tripzzy' ),
			);

			$args = array(
				'labels'             => $labels,
				'description'        => __( 'Description.', 'tripzzy' ),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'rewrite'            => array(
					'slug'       => self::$slugs['tripzzy_base'],
					'with_front' => true,
				),
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => 30,
				'supports'           => array( 'title', 'comments', 'excerpt', 'editor', 'thumbnail' ),
				'menu_icon'          => Icon::get_svg_icon_base_64( 'trip' ),
				'show_in_rest'       => true,
				'rest_base'          => 'tripzzy',
				'priority'           => 10,
			);
			return $args;
		}

		/**
		 * Meta Box arguments.
		 * Required Method to register Metabox if filter `tripzzy_filter_meta_box_args` is used.
		 *
		 * @param int $trip_id Trip id.
		 * @since 1.0.0
		 */
		protected static function meta_box_args( $trip_id ) {
			if ( get_post_type( $trip_id ) !== self::$post_type ) {
				return array();
			}
			$args = array(
				'trip_options' => array(  // Meta Box ID.
					'title'    => __( 'Tripzzy Trip Options', 'tripzzy' ), // Required.
					'callback' => array( 'Tripzzy\Admin\Views\TripsView', 'render' ),
				),
				'hebergement' => array(
					'title'    => __( 'Hébergement', 'tripzzy' ),
					'callback' => function( $post ) {
						$value = get_post_meta( $post->ID, '_hebergement_info', true );
						?>
						<label for="hebergement_info"><?php esc_html_e( 'Infos Hébergement', 'tripzzy' ); ?></label>
						<textarea name="hebergement_info" id="hebergement_info" rows="5" style="width:100%;"><?php echo esc_textarea( $value ); ?></textarea>
						<?php
					},
				),
			);
			return $args;
		}

		/**
		 * Add/remove remove metaboxes.
		 *
		 * @since 1.0.0
		 */
		public function remove_metaboxes() {
			// Remove Default.
			remove_meta_box( 'submitdiv', self::$post_type, 'side' );
			remove_meta_box( 'commentsdiv', self::$post_type, 'normal' );
			remove_meta_box( 'commentstatusdiv', self::$post_type, 'normal' );
		}

		/**
		 * Admin Column Heading
		 *
		 * @param array $columns List of column heading.
		 * @return array
		 */
		public function admin_column_headings( $columns ) {
			$date = $columns['date'];
			unset( $columns['date'] );
			unset( $columns['comments'] );

			$columns['date']     = __( 'Date', 'tripzzy' );
			$columns['featured'] = __( 'Featured', 'tripzzy' );

			return $columns;
		}

		/**
		 * Admin Column Heading
		 *
		 * @param string $column_name Name of the column.
		 * @param string $trip_id Trip id.
		 * @return void
		 */
		public function admin_column_contents( $column_name, $trip_id ) {
			switch ( $column_name ) {
				case 'featured':
					$trip       = new Trip( $trip_id );
					$icon_class = ' dashicons-star-empty ';
					if ( $trip->is_featured() ) {
						$icon_class = ' dashicons-star-filled ';
					}
					printf( wp_kses_post( '<a href="#" class="tripzzy-featured-trip dashicons %s" data-trip-id="%d"></a>' ), esc_attr( $icon_class ), esc_attr( $trip_id ) );
					break;
				default:
					break;
			} // end switch
		}
	}
}