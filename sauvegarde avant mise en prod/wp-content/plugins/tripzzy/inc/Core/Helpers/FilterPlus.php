<?php
/**
 * Custom Category Helper.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\PostTypes\TripzzyPostType;
use Tripzzy\Core\PostTypes\BookingPostType;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Helpers\Page;
/**
 * Custom Categories.
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'Tripzzy\Core\Helpers\FilterPlus' ) ) {
	/**
	 * Custom Categories Class.
	 */
	class FilterPlus {
		use SingletonTrait;

		/**
		 * Key to save data in option.
		 *
		 * @var string
		 */
		private static $option_key = 'custom_filters';

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'update' ) );
			add_action( 'init', array( $this, 'register_taxonomies' ), 20 );

			// Add admin page args to check custom taxonomy as admin page.
			add_filter(
				'tripzzy_filter_admin_page_ids',
				function ( $admin_pages ) {
					$filters = self::get();
					foreach ( $filters as $filter ) {
						$admin_pages[ 'trip_' . $filter['slug'] ] = array( 'edit-' . $filter['slug'] );
					}
					return $admin_pages;
				}
			);
		}

		/**
		 * Return Custom Taxonomy data.
		 *
		 * @since 1.0.0
		 */
		public static function get() {
			$data = MetaHelpers::get_option( self::$option_key, array() );
			return is_array( $data ) && count( $data ) > 0 ? $data : array();
		}

		/**
		 * Save Custom Taxonomy data.
		 *
		 * @since 1.0.0
		 */
		public function update() {
			if ( ! Nonce::verify() ) {
				return;
			}
			// Nonce already verified using Nonce::verify method.
			$filter_action = isset( $_REQUEST['tripzzy_filter'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['tripzzy_filter'] ) ) : ''; // @codingStandardsIgnoreLine
			if ( ! empty( $filter_action ) ) {
				$filter_actions = array( 'add_filter', 'update_filter', 'remove_filter' );
				if ( in_array( $filter_action, $filter_actions, true ) ) {
					$filters = self::get();
					if ( isset( $_REQUEST['remove_filter'] ) && ! empty( $_REQUEST['remove_filter'] ) ) { // @codingStandardsIgnoreLine
						$key = sanitize_text_field( wp_unslash( $_REQUEST['remove_filter'] ) ); // @codingStandardsIgnoreLine
						if ( isset( $filters[ $key ] ) ) {
							unset( $filters[ $key ] );
						}
					} else {
						$label           = isset( $_REQUEST['filter_label'] ) ? wp_kses( wp_unslash( $_REQUEST['filter_label'] ), array() ) : ''; // @codingStandardsIgnoreLine
						$is_hierarchical = isset( $_REQUEST['filter_is_hierarchical'] ) && 'yes' === $_REQUEST['filter_is_hierarchical']; // @codingStandardsIgnoreLine
						$show            = isset( $_REQUEST['show_in_filters'] ) && 'yes' === $_REQUEST['show_in_filters']; // @codingStandardsIgnoreLine
						$slug            = ! empty( $_REQUEST['filter_slug'] ) ? sanitize_title( wp_unslash( $_REQUEST['filter_slug'] ), '', 'save' ) : sanitize_title( $label, '', 'save' ); // @codingStandardsIgnoreLine
						if ( $slug ) {
							$filters[ $slug ] = array(
								'label'        => $label,
								'slug'         => $slug,
								'hierarchical' => $is_hierarchical,
								'show'         => $show,
							);
						}
					}
					MetaHelpers::update_option( self::$option_key, $filters, true );
					wp_safe_redirect(
						add_query_arg(
							array(
								'post_type' => BookingPostType::get_key(),
								'page'      => 'tripzzy-custom-categories',
							),
							admin_url( 'edit.php' )
						)
					);
					exit;
				}
			}
		}

		/**
		 * Register Taxaonomy as per saved data.
		 *
		 * @since 1.0.0
		 */
		public function register_taxonomies() {
			$filters = self::get();
			if ( is_array( $filters ) && count( $filters ) > 0 ) {
				foreach ( $filters as $filter ) {
					$args = array(
						'labels'       => array(
							'name' => $filter['label'],
						),
						'show_in_rest' => true,
						'hierarchical' => $filter['hierarchical'],
						'sort'         => true,
					);
					if ( $filter['show'] ) {
						$args['show_admin_column'] = true;
					}

					register_taxonomy(
						$filter['slug'],
						TripzzyPostType::get_key(),
						$args
					);
				}
			}
		}
	}
	FilterPlus::instance();
}
