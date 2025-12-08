<?php
/**
 * The template for displaying all content of single trip.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package tripzzy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Template;
?>
<div class="tripzzy-content">
	<?php
	do_action( 'tripzzy_single_before_main_content' );

	Template::get_template( 'layouts/default/layout-single-tripzzy' );

	do_action( 'tripzzy_single_after_main_content' ); // @todo need to add `enable` and `select sidebar` option
	?>
</div>
