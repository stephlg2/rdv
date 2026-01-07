<?php
/**
 * Notice Class
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\Notice' ) ) {
	/**
	 * Class For Page
	 *
	 * @since 1.0.0
	 */
	class Notice {

		/**
		 * Get Notice Markup.
		 *
		 * @param mixed  $notices Either Notice string or array.
		 * @param string $type Notice type.
		 */
		public static function render( $notices = '', $type = 'info' ) {
			if ( empty( $notices ) ) {
				return;
			} ?>
			<div class="tripzzy-notices-wrapper">
				<?php
				if ( is_array( $notices ) ) :
					foreach ( $notices as $notice ) :
						?>
						<div class="tripzzy-<?php echo esc_attr( $type ); ?>"><?php echo wp_kses_post( $notice ); ?></div>
						<?php
					endforeach;
				else :
					?>
					<div class="tripzzy-<?php echo esc_attr( $type ); ?>"><?php echo wp_kses_post( $notices ); ?></div>
					<?php
				endif;
				?>
			</div>
			<?php
		}
	}
}
