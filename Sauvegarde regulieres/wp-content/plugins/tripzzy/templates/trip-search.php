<?php
/**
 * Trip Search Form Template.
 *
 * @package tripzzy
 * @since   1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Template;
use Tripzzy\Core\Helpers\SearchForm;
?>
<div class="tripzzy-trip-search">
	<div class="container">
		<?php SearchForm::render( $args ); ?>
	</div>
</div>
