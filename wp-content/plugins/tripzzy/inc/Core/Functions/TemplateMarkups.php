<?php
/**
 * All Markups related to templates.
 *
 * @package tripzzy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Helpers\TripFilter;
use Tripzzy\Core\Helpers\Loading;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Image;

if ( ! function_exists( 'tripzzy_render_archive_toolbar' ) ) {
	/**
	 * Render archive toolbar.
	 *
	 * @since 1.0.0
	 * @since 1.2.2 Added filter by option.
	 * @since 1.2.5 Removed view mode and sort by text.
	 * @return mixed
	 */
	function tripzzy_render_archive_toolbar() {
		$tripzzy_view_mode = TripFilter::get_view_mode();
		$labels            = Strings::get()['labels'];
		?>
		<?php if ( is_tax() ) : 
		    $term = get_queried_object(); 
	    $allowed_tags = array(
	        'h1' => array(),
	        'h2' => array(),
	        'h3' => array(),
	        'h4' => array(),
	        'h5' => array(),
	        'h6' => array(),
	        'p'  => array(),
			'br'  => array(),
	        'a'  => array(
	            'href'   => array(),
	            'title'  => array(),
	            'target' => array(),
	            'rel'    => array(),
	            'class'  => array(),
	        ),
	        'strong' => array(),
	        'em' => array(),
	        'b' => array(),
	        'i' => array(),
	    );
		    ?>
		    <h1 class="tripzzy-archive-title"><?php echo wp_kses( $term->name, $allowed_tags ); ?></h1>
		    <?php if ( ! empty( $term->description ) ) : ?>
		        <div class="tripzzy-archive-description"><?php echo wp_kses( wpautop( $term->description ), $allowed_tags ); ?></div>
		    <?php endif; ?>
		<?php endif; ?>
		<div class="tz-toolbar">
			<div class="tz-toolbar-left">
				<div class="tz-toolbar-title" id="tripzzy-filter-found-posts"></div><!-- ID: tripzzy-filter-found-posts is required-->
				<?php Loading::render( array( 'id' => 'tripzzy-archive-loader' ) ); ?>
			</div>
			<div class="tz-toolbar-right">
				<div class="tz-toolbar-sort-by-wrapper" id="tz-toolbar-sort-by-wrapper">
					<select class="tripzzy-input tripzzy-multiselect tz-toolbar-sort-by" id="tz-toolbar-sort-by" multiple data-placeholder="<?php esc_attr_e( 'Sort by', 'tripzzy' ); ?>" style="display: none;">
						<option value="" selected><?php esc_html_e( 'Sort by', 'tripzzy' ); ?></option>
						<option value="price_high_to_low"><?php esc_html_e( 'Price high to low', 'tripzzy' ); ?></option>
						<option value="price_low_to_high"><?php esc_html_e( 'Price low to high', 'tripzzy' ); ?></option>
						<option value="name_asc"><?php esc_html_e( 'Name asc', 'tripzzy' ); ?></option>
						<option value="name_desc"><?php esc_html_e( 'Name desc', 'tripzzy' ); ?></option>
					</select>
				</div>
				<div class="tz-view-mode" id="tz-view-mode">
					<ul class="tz-view-mode-lists" aria-label="View Mode">
						<li class="<?php echo 'grid' === $tripzzy_view_mode ? esc_attr( 'current-mode' ) : ''; ?>" ><a href="#" data-view="grid"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="grid-2" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="svg-inline--fa fa-grid-2 fa-lg"><path fill="currentColor" d="M192 80c0-26.5-21.5-48-48-48H48C21.5 32 0 53.5 0 80v96c0 26.5 21.5 48 48 48h96c26.5 0 48-21.5 48-48V80zm0 256c0-26.5-21.5-48-48-48H48c-26.5 0-48 21.5-48 48v96c0 26.5 21.5 48 48 48h96c26.5 0 48-21.5 48-48V336zM256 80v96c0 26.5 21.5 48 48 48h96c26.5 0 48-21.5 48-48V80c0-26.5-21.5-48-48-48H304c-26.5 0-48 21.5-48 48zM448 336c0-26.5-21.5-48-48-48H304c-26.5 0-48 21.5-48 48v96c0 26.5 21.5 48 48 48h96c26.5 0 48-21.5 48-48V336z" class=""></path></svg></a></li>
						<li class="<?php echo 'list' === $tripzzy_view_mode ? esc_attr( 'current-mode' ) : ''; ?>"><a href="#" data-view="list"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="list-ul" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="svg-inline--fa fa-list-ul fa-lg"><path fill="currentColor" d="M64 144c26.5 0 48-21.5 48-48s-21.5-48-48-48S16 69.5 16 96s21.5 48 48 48zM192 64c-17.7 0-32 14.3-32 32s14.3 32 32 32H480c17.7 0 32-14.3 32-32s-14.3-32-32-32H192zm0 160c-17.7 0-32 14.3-32 32s14.3 32 32 32H480c17.7 0 32-14.3 32-32s-14.3-32-32-32H192zm0 160c-17.7 0-32 14.3-32 32s14.3 32 32 32H480c17.7 0 32-14.3 32-32s-14.3-32-32-32H192zM64 464c26.5 0 48-21.5 48-48s-21.5-48-48-48s-48 21.5-48 48s21.5 48 48 48zm48-208c0-26.5-21.5-48-48-48s-48 21.5-48 48s21.5 48 48 48s48-21.5 48-48z" class=""></path></svg></a></li>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'tripzzy_render_archive_list_item_template' ) ) {
	/**
	 * Render archive list item template.
	 *
	 * @since 1.0.0
	 * @since 1.0.6 Content loading markup added by adding tz-template-markup class.
	 * @return mixed
	 */
	function tripzzy_render_archive_list_item_template() {
		$images_url = sprintf( '%sassets/images', esc_url( TRIPZZY_PLUGIN_DIR_URL ) );

		?>
		<script type="text/html" id="tmpl-tripzzy-archive-list-item">
			<div class="tz-col tz-template-markup">
				<article>
					<div class="tripzzy-trip">
						<div class="tz-row">
							<div class="tz-col tz-cols-5-md">
								<div class="tripzzy-thumbnail">
									<a href="#" class="tz-template-markup-loading"><?php Image::default_thumbnail(); ?></a>
									<div class="tripzzy-wishlist">
										<button class="tripzzy-wishlist-button" ><i class="fa-regular fa-heart"></i></button>
									</div>
									<div class="tm-ribbon-group vertical"></div>
								</div>
							</div>
							<div class="tz-col tz-cols-7-md">
								<div class="tripzzy-content-wrapper">
									<div class="tripzzy-trip-price">
										<div class="tripzzy-price-wrapper tz-template-markup-loading" style="width:40%">
											<div class="tripzzy-price ">$100</div>
										</div>
									</div>
									<h3 class="tripzzy-trip-title tz-template-markup-loading" style="width:70%"><a href="#" >Trip title</a></h3>
									<div class="tripzzy-after-title">
										<div class="tripzzy-review-wrapper tz-template-markup-loading" >
											<div class="tripzzy-average-rating no-reviews" title="Rated 0 out of 5">
												<div class="tripzzy-average-rating-value">
													<span style="width:0%"> Rated <strong class="rating">0</strong> out of <span>5</span></span>
												</div>
											</div>
											<!-- For Grid View only -->
											<div class="tripzzy-average-review no-reviews " title=" (0 Reviews)"></div>
										</div>
										<div class="tripzzy-meta-container tz-template-markup-loading" style="width:50%">
											<div class="tripzzy-meta-wrapper">
												<div class="tripzzy-meta-item">
													<span class="tripzzy-meta destination" title="Destination">
														<svg class="icon">
															<use xlink:href="<?php echo esc_url( $images_url ); ?>/sprite.svg#Pin_light"></use>
														</svg>
														<span>N/A</span>
													</span>
												</div>
											</div>
										</div>
									</div>
									<hr class="tripzzy-divider">
									<div class="tripzzy-before-content tz-template-markup-loading" style="width:100%">
										<div class="tripzzy-meta-container">
											<div class="tripzzy-meta-wrapper">
												<div class="tripzzy-meta-item ">
													<span class="tripzzy-meta duration">
														<svg class="icon">
															<use xlink:href="<?php echo esc_url( $images_url ); ?>/sprite.svg#Alarmclock_light">
															</use>
														</svg>
														<span>N/A</span>
													</span>
												</div>
												<div class="tripzzy-meta-item ">
													<span class="tripzzy-meta difficulty">
														<svg class="icon">
															<use xlink:href="<?php echo esc_url( $images_url ); ?>/sprite.svg#Waterfall_light">
															</use>
														</svg>
														<span>N/A</span>
													</span>
												</div>
											</div>
										</div>
									</div>
									<div class="tripzzy-trip-content"></div>
									<div class="tripzzy-trip-button-wrapper">
										<div class="tz-template-markup-loading" style=""><a href="#" class="tz-btn tz-btn-outline">View Details</a></div>
										<div class="tz-template-markup-loading" style=""><a href="#" class="tz-btn tz-btn-solid tm-book-now-btn">Book Now</a></div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</article><!-- /article -->
			</div>		
		</script>
		<?php
	}
}

if ( ! function_exists( 'tripzzy_booking_category_template_markup' ) ) {
	/**
	 * Markup to display package category in trip detail page.
	 *
	 * @since 1.2.9
	 * @return void
	 */
	function tripzzy_booking_category_template_markup() {
		$labels             = Strings::get()['labels'] ?? array();
		$trip               = new Trip( get_the_ID() );
		$packages           = $trip->packages(); // all Packages.
		$default_package_id = $packages->default_package_id;
		$default_package    = $packages->get_package();
		$price_per_key      = $trip->price_per;

		?>
			<script type="text/html" id="tmpl-tripzzy-booking-categories-content">
				<div class='tripzzy-packages-title'><?php echo esc_html( $labels['packages'] ?? '' ); ?></div>	
				<div class='tripzzy-packages-content'>
					<div class='tripzzy-packages-list-wrapper'>
					<ul class='tripzzy-packages-list' >
	
					<?php
					foreach ( $packages as $package ) {
						$categories = $package->get_categories();
						if ( ! count( $categories ) ) {
							continue;
						}
						$package_info = array(
							'trip_id'    => get_the_ID(),
							'package_id' => (int) $package->get_id(),
							'start_date' => '{{{data.StartDate}}}',
						);
						?>
						<li data-package="<?php echo esc_attr( wp_json_encode( $package_info ) ); ?>" class="tripzzy__package-name <?php echo esc_attr( (int) $package->get_id() === (int) $default_package_id ? 'selected-package' : '' ); ?>" ><?php echo esc_html( $package->get_title() ); ?></li>
						<?php
					}
					?>
					</ul>
					<?php do_action( 'tripzzy_date_availability_after_packages', $trip ); ?>
					</div>
					<?php do_action( 'tripzzy_date_availability_before_category_items', $trip ); ?>
					<div class="tripzzy__category-items">
						<?php
						foreach ( $default_package as $package_category ) {
							$package_category_id = $package_category->get_id();
							if ( ! get_term( $package_category_id ) ) {
								continue;
							}
							?>
							<div class="tripzzy__category-item" style="display:flex;justify-content:space-between;">
								<div class="tripzzy__category-title">
									<span><?php echo esc_html( $package_category->get_title() ); ?></span>
									<?php
									/**
									 * Hook to add text besides category title.
									 *
									 * @since 1.2.5
									 */
									do_action( 'tripzzy_after_package_category_title', $package_category, $trip );
									$min_people = apply_filters( 'tripzzy_filter_min_people', 0, $package_category, $trip );
									?>
								</div>
								
								<?php if ( 'person' === $price_per_key ) : ?>
									<div class="tripzzy__category-price">
										<?php
										if ( $package_category->has_sale() ) {
											?>
											<del><?php echo esc_html( \Tripzzy\Core\Helpers\Amount::display( $package_category->get_regular_price() ) ); ?></del>
											<?php
										}
										echo esc_html( \Tripzzy\Core\Helpers\Amount::display( $package_category->get_price() ) );
										?>
									</div>
								<?php endif; ?>
								<div class="tripzzy__qty-counter tripzzy__category-counter">
									<div class="tripzzy__qty-counter-input tripzzy__category-counter-input">
										<button class="tripzzy-counter-btn tripzzy-counter-btn-minus" type="button" aria-label="<?php esc_attr_e( 'Decrease', 'tripzzy' ); ?>"><i class="dashicons dashicons-minus"></i></button>
										<input class="tripzzy-counter-input" min="<?php echo esc_attr( $min_people ); ?>" type="number" data-category-counter="<?php echo absint( $package_category_id ); ?>"/>
										<button class="tripzzy-counter-btn tripzzy-counter-btn-plus" type="button" aria-label="<?php esc_attr_e( 'Increase', 'tripzzy' ); ?>"><i class="dashicons dashicons-plus-alt2"></i></button>
									</div>
								</div>
							</div>
							<?php
						}
						?>
					</div>
					<?php do_action( 'tripzzy_date_availability_after_category_items', $trip ); ?>
				</div>
				<div class='tripzzy-checkout-button-wrapper'>
					<div class='tripzzy-error'></div>
					<div class="tripzzy-checkout-button-loader-wrapper">
						<?php Loading::render(); ?>
						<button class="tripzzy-checkout-button tz-btn tz-btn-solid" role="button" data-action-checkout>
							<?php echo esc_html( $labels['checkout'] ?? '' ); ?>
						</button>
					</div>
				</div>
			</script>
			<?php
	}
}
