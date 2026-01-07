<?php
/**
 * Template part for the review section of Tripzzy post type single template.
 *
 * @package tripzzy
 * @since 1.0.0
 */

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! comments_open() ) {
	return;
}
?>
<div class="tripzzy-section" id="tripzzy-reviews-section">

	<?php

	if ( have_comments() ) {

		?>
		<h2 class="comments-title">
		<?php
		printf(
			/* translators: %s: Review count number. */
			esc_html( _nx( '%s review', '%s reviews', get_comments_number(), 'Reviews title', 'tripzzy' ) ),
			esc_html( number_format_i18n( get_comments_number() ) )
		);
		?>
		</h2>

		<ol class="comment-list">
			<?php wp_list_comments(); ?>
		</ol><!-- .comment-list -->

		<?php

		the_comments_pagination();
	}

	comment_form();
	?>
</div>
