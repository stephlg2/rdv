<?php
/**
 * Views: Tripzzy Settings.
 *
 * @package tripzzy
 */

namespace Tripzzy\Admin\Views;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Helpers\Loading;

if ( ! class_exists( 'Tripzzy\Admin\Views\FormFieldsView' ) ) {
	/**
	 * FormFieldsView Class.
	 *
	 * @since 1.0.0
	 */
	class FormFieldsView {

		/**
		 * Form Fields page html.
		 *
		 * @param object $form Post object.
		 * @since 1.0.0
		 */
		public static function render( $form ) {
			$form_id = $form->ID;
			?>
			<div class="tripzzy-form-fields-page-wrapper">
				<div id="tripzzy-form-fields-page" class="tripzzy-page tripzzy-form-fields-page" data-form-id="<?php echo esc_attr( $form_id ); ?>" >
					<?php Loading::render(); ?>
				</div>
			</div>
			<?php
		}
	}
}
