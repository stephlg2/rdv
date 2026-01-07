<?php
/**
 * Dashboard Page Template.
 *
 * @package tripzzy
 * @since   1.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
get_header(); ?>
<?php do_action( 'tripzzy_before_main_content' ); ?>
<div class="tripzzy-container"><!-- Main Wrapper element for Tripzzy -->
	<div class="tripzzy-content">
		<?php
		while ( have_posts() ) :
			the_post();
			the_content();
		endwhile; // end of the loop.
		?>
	</div>
</div>
<?php do_action( 'tripzzy_after_main_content' ); ?>
<?php
get_footer();
