<?php
/**
 * Base Class For Tripzzy Post Type.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Bases;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Forms\Form;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Helpers\ArrayHelper;
use Tripzzy\Core\Helpers\EscapeHelper;

if ( ! class_exists( 'Tripzzy\Core\Bases\PostTypeBase' ) ) {
	/**
	 * Base Class For  Post Type Post Type.
	 *
	 * @since 1.0.0
	 */
	class PostTypeBase {
		use SingletonTrait;

		/**
		 * An array of post type arguments to register the custom post type with array key being post type slug and value being $args.
		 *
		 * @var array
		 * @since 1.0.0
		 */
		private static $post_type_args = array();

		/**
		 * An array of meta box arguments to register the custom meta box with array key being meta box slug and value being $args.
		 *
		 * @var array
		 * @since 1.0.0
		 */
		private static $meta_box_args = array();

		/**
		 * Initialize Post Types.
		 *
		 * @since 1.0.0
		 */
		public static function init() {
			// Register Post Types.
			$post_type_args       = apply_filters( 'tripzzy_filter_post_type_args', self::$post_type_args );
			self::$post_type_args = ArrayHelper::sort_by_priority( $post_type_args );

			if ( is_array( self::$post_type_args ) && ! empty( self::$post_type_args ) ) {
				foreach ( self::$post_type_args as $post_type => $args ) {
					if ( ! post_type_exists( $post_type ) ) {
						register_post_type( $post_type, $args );
					}
				}
			}
		}

		/**
		 * Function to make dynamic save_post hook as per Tripzzy Post Type arguments form PostMeta class.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public static function get_post_types() {
			return array_keys( self::$post_type_args );
		}

		/**
		 * Initialize Meta Boxes.
		 *
		 * @param string                 $screen Current screen id.
		 * @param string                 $context Context.
		 * @param \WP_Post|object|string $data Post obj data or mixed data.
		 * @since 1.0.0
		 */
		public static function init_meta_box( $screen, $context, $data ) {
			if ( ! $screen ) {
				return;
			}

			if ( ! isset( $data->ID ) ) {
				return;
			}

			// Register Meta Boxes.
			$post_id = $data->ID; // $data is mixed variable.

			$meta_box_args = apply_filters( 'tripzzy_filter_meta_box_args', self::$meta_box_args, $post_id );
			foreach ( $meta_box_args as $post_type => $post_type_meta_box ) {
				if ( ! empty( $post_type_meta_box ) ) {
					foreach ( $post_type_meta_box as $meta_box_id => $meta_box ) {
						$meta_box_id = sprintf( '%s__%s', $post_type, $meta_box_id );
						$callback    = isset( $meta_box['callback'] ) ? $meta_box['callback'] : array( 'Tripzzy\Core\Bases\PostTypeBase', 'meta_box_render_fields' );
						$context     = isset( $meta_box['context'] ) ? $meta_box['context'] : 'advanced';
						$priority    = isset( $meta_box['priority'] ) ? $meta_box['priority'] : 'high';

						// Callback args.
						$callback_args = array();
						if ( isset( $meta_box['markups'] ) ) {
							$callback_args['markups'] = $meta_box['markups'];
						}
						if ( isset( $meta_box['fields'] ) ) {
							$callback_args['fields'] = $meta_box['fields'];
						}
						add_meta_box( $meta_box_id, esc_html( $meta_box['title'] ), $callback, $post_type, $context, $priority, $callback_args );
					}
				}
			}
		}

		/**
		 * Render the default callback funtion if callback is not provided in arguments.
		 * Need field arguments to display fields.
		 *
		 * @param object $post Post object.
		 * @param array  $additional Meta box arguments [on going].
		 * @since 1.0.0
		 */
		public static function meta_box_render_fields( $post, $additional = array() ) {
			if ( ! $post ) {
				return;
			}
			if ( isset( $additional['args'] ) && is_array( $additional['args'] ) && count( $additional['args'] ) > 0 ) {
				$args = $additional['args'];
				foreach ( $args as $key => $meta_box_field ) { // to sort element as per value added in array.
					?>
					<div class="inside-content">
					<?php
					if ( 'fields' === $key ) {
						$form_args = array(
							'fields' => $args['fields'],
						);
						Form::render( $form_args );
					}
					if ( 'markups' === $key ) {
						$allowed_html = EscapeHelper::get_allowed_html();
						echo wp_kses( $args['markups'], $allowed_html );
					}
					?>
					</div>
					<?php
				}
			}
		}

		/**
		 * Get the Post Type Key defined in the child class.
		 *
		 * @since 1.0.0
		 * @return string
		 */
		public static function get_key() {
			return static::$post_type;
		}

		/**
		 * Add Post Type arguments to create post type.
		 *
		 * @param array $post_type_args Array arguments.
		 *
		 * @since 1.0.0
		 */
		public function init_args( $post_type_args ) {
			$post_type_args[ static::$post_type ] = static::post_type_args();
			return $post_type_args;
		}

		/**
		 * Arguments to add Meta box.
		 *
		 * @param array $meta_box_args Array arguments.
		 * @param int   $post_id Post id.
		 *
		 * @since 1.0.0
		 */
		public function init_meta_box_args( $meta_box_args, $post_id = null ) {
			$meta_box_args[ static::$post_type ] = static::meta_box_args( $post_id );
			return $meta_box_args;
		}
	}
}
