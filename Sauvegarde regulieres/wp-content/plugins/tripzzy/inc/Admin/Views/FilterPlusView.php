<?php
/**
 * Views: Tripzzy Custom Filters.
 *
 * @package tripzzy
 */

namespace Tripzzy\Admin\Views;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\PostTypes\TripzzyPostType;
use Tripzzy\Core\PostTypes\BookingPostType;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Helpers\FilterPlus;
use Tripzzy\Core\Helpers\Strings;

if ( ! class_exists( 'Tripzzy\Admin\Views\FilterPlusView' ) ) {
	/**
	 * FilterPlusView Class.
	 *
	 * @since 1.0.0
	 */
	class FilterPlusView {

		/**
		 * Custom Filters page html.
		 *
		 * @since 1.0.0
		 */
		public static function render() {
			$update_filter = isset( $_GET['update_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['update_filter'] ) ) : '';  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$labels        = Strings::get()['labels'];

			$mode         = 'add';
			$label        = '';
			$slug         = '';
			$hierarchical = true;
			$show         = true;

			$filter_title = $labels['add_new_filter'];
			$filter_type  = 'add_filter';
			$filters      = FilterPlus::get();

			if ( ! empty( $update_filter ) ) {
				$filter = $filters[ $update_filter ] ?? array();

				$mode         = 'edit';
				$label        = $filter['label'] ?? '';
				$slug         = $filter['slug'] ?? '';
				$hierarchical = $filter['hierarchical'] ?? '';
				$show         = $filter['show'] ?? '';

				$filter_title = $labels['update_filter'] ?? '';
				$filter_type  = 'update_filter';
			}

			$read_only = 'update_filter' === $filter_type ? 'readonly' : '';
			?>
			<div class="wrap nosubsub">
				<hr class="wp-header-end">
				<div class="tripzzy-page-wrapper">
					<div id="col-container" class="wp-clearfix">
						<div id="col-left">
							<div class="col-wrap">
								<div class="form-wrap">
									<h2><?php echo esc_html( $filter_title ); ?></h2>
									<form action="" method="post">
										<input type="hidden" name="tripzzy_filter" value="<?php echo esc_attr( $filter_type ); ?>">
										<input type="hidden" name="object_type" value="<?php echo esc_attr( TripzzyPostType::get_key() ); ?>">
										<?php Nonce::create_field(); ?>

										<!-- Custom filter form fields -->
										<div class="form-field">
											<label for="label"><?php echo esc_html( $labels['filter_label'] ?? '' ); ?></label>
											<input type="text" name="filter_label" id="fliter-label" value="<?php echo esc_attr( $label ); ?>" />
											<p><?php esc_html_e( 'General name for the filter, usually plural.', 'tripzzy' ); ?></p>
										</div>
										<div class="form-field">
											<label for="label"><?php echo esc_html( $labels['filter_slug'] ?? '' ); ?></label>
											<input <?php echo esc_attr( $read_only ); ?>  type="text" name="filter_slug" id="filter-slug" value="<?php echo esc_attr( $slug ); ?>">
											<p><?php esc_html_e( 'Slug for filter or Category.', 'tripzzy' ); ?></p>
										</div>
										<div class="form-field">
											<label for="label"><?php echo esc_html( $labels['hierarchical'] ?? '' ); ?></label>
											<input type="checkbox" name="filter_is_hierarchical" id="label" value="yes" <?php checked( true, $hierarchical ); ?> />
											<p><?php esc_html_e( 'If checked the new filter will be treated as WordPress deafult categories else as tags.', 'tripzzy' ); ?></p>
										</div>
										<div class="form-field">
											<label for="label"><?php echo esc_html( $labels['show_in_filters'] ?? '' ); ?></label>
											<input type="checkbox" name="show_in_filters" id="label" value="yes" <?php checked( true, $show ); ?> />
											<p><?php esc_html_e( 'If checked the filter will be available as filter by option.', 'tripzzy' ); ?></p>
										</div>
										<p class="submit">
											<input class="button button-primary" type="submit" name="submit" value="<?php echo esc_attr( $filter_title ); ?>">
										</p>
									</form>
								</div>
							</div>
						</div>
						<div id="col-right">
							<div class="col-wrap">
								<form action="">
									<table class="wp-list-table widefat fixed striped table-view-list">
										<thead>
											<tr>
												<th><?php echo esc_html( $labels['label'] ?? '' ); ?></th>
												<th><?php echo esc_html( $labels['slug'] ?? '' ); ?></th>
												<th><?php echo esc_html( $labels['hierarchical'] ?? '' ); ?></th>
											</tr>
										</thead>
										<tbody>
											<?php
											$nonce = Nonce::create();
											if ( count( $filters ) > 0 ) :
												foreach ( $filters as $_filter ) :

													$edit_filter_url = add_query_arg(
														array(
															'taxonomy' => $_filter['slug'],
															'post_type' => TripzzyPostType::get_key(),
														),
														admin_url( 'edit-tags.php' )
													);
													$edit_tax_url    = add_query_arg(
														array(
															'post_type' => BookingPostType::get_key(),
															'page' => 'tripzzy-custom-categories',
															'update_filter' => $_filter['slug'],
															'tripzzy_nonce' => $nonce,
														),
														admin_url( 'edit.php' )
													);
													$delete_tax_url  = add_query_arg(
														array(
															'post_type' => BookingPostType::get_key(),
															'page' => 'tripzzy-custom-categories',
															'remove_filter' => $_filter['slug'],
															'tripzzy_filter' => 'remove_filter',
															'tripzzy_nonce' => $nonce,
														),
														admin_url( 'edit.php' )
													);

													$hierarchical = $_filter['hierarchical'] ? __( 'Yes', 'tripzzy' ) : __( 'No', 'tripzzy' );
													?>
													<tr>
														<td>
															<strong>
																<a href="<?php echo esc_url( $edit_filter_url ); ?>"><?php echo esc_html( $_filter['label'] ); ?></a>
															</strong>
															<div class="row-actions">
																<span class="edit">
																	<a href="<?php echo esc_url( $edit_tax_url ); ?>"><?php esc_html_e( 'Edit', 'tripzzy' ); ?></a>
																</span>|
																<span class="delete">
																	<a href="<?php echo esc_url( $delete_tax_url ); ?> "><?php esc_html_e( 'Delete', 'tripzzy' ); ?></a>
																</span>
															</div>
														</td>
														<td><?php echo esc_html( $_filter['slug'] ); ?></td>
														<td><?php echo esc_html( $hierarchical ); ?></td>
													</tr>
													<?php
												endforeach;
											else :
												?>
												<tr class="no-items">
													<td class="colspanchange" colspan="3"><?php esc_html_e( 'No filters found.', 'tripzzy' ); ?></td>
												</tr>
												<?php
											endif;
											?>
										</tbody>
									</table>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
	}
}
