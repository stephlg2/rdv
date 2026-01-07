<?php
/**
 * Single Trip Page Template.
 *
 * @package tripzzy
 * @since   1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Template;

get_header(); ?>

<?php do_action( 'tripzzy_before_main_content' ); ?>

<div class="tripzzy-container"><!-- Main Wrapper element for Tripzzy -->
	<?php while ( have_posts() ) :
		the_post();

		Template::get_template_part( 'content', 'single-tripzzy' );


	endwhile; ?>
</div>

<?php do_action( 'tripzzy_after_main_content' ); ?>
<?php get_footer(); ?>