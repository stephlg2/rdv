<?php
/**
 * Block Templates Class.
 *
 * @since 1.0.6
 * @package tripzzy
 */

namespace Tripzzy\Core;

use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Helpers\Page;

/**
 * Block Templates class.
 *
 * @internal
 */
class BlockTemplates {
	use SingletonTrait;

	/**
	 * Directory which contains all templates.
	 *
	 * @var string
	 */
	const TEMPLATES_ROOT_DIR = 'templates';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize block template.
	 *
	 * @since 1.0.6
	 */
	protected function init() {
		add_filter( 'get_block_templates', array( $this, 'add_block_templates' ), 10, 3 );
	}

	/**
	 * Add Block Templates for Tripzzy Pages.
	 *
	 * @param  array  $query_result  List of Template object.
	 * @param  array  $query         Query Arguments to retrive template.
	 * @param  string $template_type Template or template parts (wp_template or wp_template_part).
	 *
	 * @since 1.0.6
	 * @return array
	 */
	public function add_block_templates( $query_result, $query, $template_type ) {
		if ( ! BlockTemplateUtils::supports_block_templates( $template_type ) ) {
			return $query_result;
		}
		$post_type = isset( $query['post_type'] ) ? $query['post_type'] : '';
		$slugs     = isset( $query['slug__in'] ) ? $query['slug__in'] : array();

		$template_files = $this->get_block_templates( $slugs, $template_type );

		foreach ( $template_files as $template_file ) {
			if ( $post_type
			&& isset( $template_file->post_types )
			&& ! in_array( $post_type, $template_file->post_types, true )
			) {
				continue;
			}

			if ( 'custom' !== $template_file->source ) {
				$template = BlockTemplateUtils::build_template_result_from_file( $template_file, $template_type );
			} else {
				// Custom template which is saved and loaded from filesystem.
				$template_file->title       = BlockTemplateUtils::get_block_template_title( $template_file->slug );
				$template_file->description = BlockTemplateUtils::get_block_template_description( $template_file->slug );
				$query_result[]             = $template_file;
				continue;
			}

			$is_not_custom   = false === array_search(
				wp_get_theme()->get_stylesheet() . '//' . $template_file->slug,
				array_column( $query_result, 'id' ),
				true
			);
			$fits_slug_query =
			! isset( $query['slug__in'] ) || in_array( $template_file->slug, $query['slug__in'], true ) || Page::is( 'trip-taxonomies' );
			$fits_area_query =
			! isset( $query['area'] ) || ( property_exists( $template_file, 'area' ) && $template_file->area === $query['area'] );
			$should_include  = $is_not_custom && $fits_slug_query && $fits_area_query;
			if ( $should_include ) {
				$query_result[] = $template;
			}
		}

		return $query_result;
	}

	/**
	 * Get and build the block template objects from the block template files.
	 *
	 * @param array  $slugs         An array of slugs to retrieve templates for.
	 * @param string $template_type Template or template parts (wp_template or wp_template_part).
	 *
	 * @since 1.0.6
	 * @return array WP_Block_Template[] An array of block template objects.
	 */
	public function get_block_templates( $slugs = array(), $template_type = 'wp_template' ) {
		$templates_from_db      = BlockTemplateUtils::get_block_templates_from_db( $slugs, $template_type );
		$templates_from_tripzzy = $this->get_block_templates_from_tripzzy( $slugs, $templates_from_db, $template_type );
		$templates              = array_merge( $templates_from_db, $templates_from_tripzzy );
		return $templates;
	}

	/**
	 * Get Template from tripzzy skipping the template exists in the theme.
	 *
	 * @param string[] $slugs           An array of slugs to retrive respective templates.
	 * @param array    $custom_template Custom Template modified or saved and loaded from filesystem.
	 * @param string   $template_type   Template or template parts (wp_template or wp_template_part).
	 *
	 * @since 1.0.6
	 * @return array Return Tripzzy template from plugin.
	 */
	public function get_block_templates_from_tripzzy( $slugs, $custom_template, $template_type = 'wp_template' ) {
		$directory      = BlockTemplateUtils::get_templates_directory( $template_type );
		$template_files = BlockTemplateUtils::get_template_paths( $directory );
		$templates      = array();
		foreach ( $template_files as $template_file ) {
			$template_slug = BlockTemplateUtils::generate_template_slug_from_path( $template_file );
			// This template does not have a slug we're looking for. Skip it.
			if ( is_array( $slugs ) && count( $slugs ) > 0 && ! in_array( $template_slug, $slugs, true ) ) {
				continue;
			}
			// If the template exists in theme which we're looking for. skip it.
			if ( BlockTemplateUtils::theme_has_template( $template_slug )
				|| count(
					array_filter(
						$custom_template,
						function ( $template ) use ( $template_slug ) {
							$template_obj = (object) $template; //phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found
							return $template_obj->slug === $template_slug;
						}
					)
				) > 0
			) {
				continue;
			}
			// Add template if not exists.
			$templates[] = BlockTemplateUtils::create_new_block_template_object( $template_file, $template_type, $template_slug );
		}
		return $templates;
	}
}
