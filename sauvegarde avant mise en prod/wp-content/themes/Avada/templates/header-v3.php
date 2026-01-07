<?php

/**
 * Header-v3 template.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 */

// Do not allow directly accessing this file.
if (! defined('ABSPATH')) {
	exit('Direct script access denied.');
}
?>



<div class="fusion-header-sticky-height"></div>
<div class="fusion-header">
	<div class="fusion-row container-mega-menu">
		<div class="col-logo">
			<?php if ('flyout' === Avada()->settings->get('mobile_menu_design')) : ?>
				<div class="fusion-header-has-flyout-menu-content">
				<?php endif; ?>
				<?php avada_logo(); ?>
				</div>
				<div class="col-menu"> 
					<?php wp_nav_menu(array('theme_location' => 'main_navigation')); ?>
				</div>
				<?php avada_mobile_menu_search(); ?>
				<?php if ('flyout' === Avada()->settings->get('mobile_menu_design')) : ?>
		</div>
	<?php endif; ?>
	</div>
</div>