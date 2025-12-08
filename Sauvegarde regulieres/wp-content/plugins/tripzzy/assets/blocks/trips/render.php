<?php
/**
 * Tripzzy Trips.
 *
 * @package tripzzy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\TaxonomyBase;
use Tripzzy\Core\Image;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\Amount;
use Tripzzy\Core\Helpers\Reviews;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\FilterPlus;
use Tripzzy\Core\Template;

$data = $attributes['query'] ?? array();
$args = array(
	'post_type'      => 'tripzzy',
	'paged'          => 1,
	'orderby'        => $data['orderBy'] ?? 'title',
	'order'          => $data['order'] ?? 'asc',
	'posts_per_page' => $data['numberOfItems'] ?? 3,
);

if ( $attributes ) {
	$args['tax_query']['relation'] = 'AND';

	$taxonomies = TaxonomyBase::get_args();
	foreach ( $taxonomies as $tax_name => $tax_name_args ) {
		if ( isset( $data[ $tax_name ] ) && ! empty( $data[ $tax_name ] ) ) {
			if ( is_array( $data[ $tax_name ] ) ) {
				// Filter empty value from the taxonomy list to perform tax query.
				$terms = array_filter(
					$data[ $tax_name ],
					function ( $value ) {
						return ! empty( $value );
					}
				);
			} else {
				$terms = array( $data[ $tax_name ] );
			}
			if ( ! empty( $terms ) ) {
				$args['tax_query'][] = array(
					'taxonomy' => $tax_name,
					'field'    => 'term_id',
					'terms'    => $terms,
				);
			}
		}
	}

	$custom_taxonomies = FilterPlus::get();
	if ( is_array( $custom_taxonomies ) && count( $custom_taxonomies ) > 0 ) {
		foreach ( $custom_taxonomies as $slug => $custom_taxonomy ) {
			if ( isset( $data[ $slug ] ) && ! empty( $data[ $slug ] ) ) {
				$args['tax_query'][] = array(
					'taxonomy' => $slug,
					'field'    => 'slug',
					'terms'    => is_array( $data[ $slug ] ) ? $data[ $slug ] : array( $data[ $slug ] ),
				);
			}
		}
	}

	// Meta Query.
	$args['meta_query'] = array(); // @phpcs:ignore
	$featured           = $data['featured'] ?? false;

	if ( $featured ) {
		$args['meta_query'][] = array(
			'key'     => 'tripzzy_featured',
			'value'   => '1',
			'compare' => '=',
		);
	}
}

$query             = new \WP_Query( $args );
$tripzzy_view_mode = $attributes['view_mode'] ?? 'grid_view';

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'tripzzy-trips-block',
	)
);

?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<div class="tripzzy-trips <?php echo esc_attr( $tripzzy_view_mode ); ?>-view">
		<div class="tz-row tripzzy-trip-listings">
			<?php
			$has_post_class = true;
			while ( $query->have_posts() ) {
				$query->the_post();
				Template::get_template_part( 'content', 'archive-tripzzy', compact( 'has_post_class' ) );
			}
			wp_reset_postdata();
			?>
		</div><!-- /.tripzzy-trip-listings -->
	</div><!-- /.tripzzy-trips -->
</div>
