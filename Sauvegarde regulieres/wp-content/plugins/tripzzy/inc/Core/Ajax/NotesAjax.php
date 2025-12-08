<?php
/**
 * Notes ajax class.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Ajax;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Bookings;
use Tripzzy\Core\Helpers\Strings;

if ( ! class_exists( 'Tripzzy\Core\Ajax\NotesAjax' ) ) {
	/**
	 * Notes Ajax Class.
	 *
	 * @since 1.1.8
	 */
	class NotesAjax {
		use SingletonTrait;

		/**
		 * All available strings.
		 *
		 * @var array
		 */
		private $strings;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->strings = Strings::messages();
			add_action( 'wp_ajax_tripzzy_add_booking_note', array( $this, 'add_booking_note' ) );
			add_action( 'wp_ajax_nopriv_tripzzy_add_booking_note', array( $this, 'add_booking_note' ) );

			add_action( 'wp_ajax_tripzzy_delete_booking_note', array( $this, 'delete_booking_note' ) );
			add_action( 'wp_ajax_nopriv_tripzzy_delete_booking_note', array( $this, 'delete_booking_note' ) );
		}

		/**
		 * Ajax Callback to add new note.
		 *
		 * @since 1.1.8
		 */
		public function add_booking_note() {
			if ( ! Nonce::verify() ) {
				$message = array(
					'message' => $this->strings['nonce_verification_failed'],
				);
				wp_send_json_error( $message );
			}
			$data              = Request::sanitize_input( 'INPUT_PAYLOAD' );
			$booking_id        = $data['booking_id'] ?? 0;
			$booking_note      = $data['booking_note'] ?? '';
			$booking_note_type = $data['booking_note_type'] ?? '';
			$is_guest_note     = 'guest' === $booking_note_type ? 1 : 0;

			$comment_id   = Bookings::add_note( $booking_id, $booking_note, $is_guest_note, true );
			$note         = Bookings::get_note( $comment_id );
			$note_class   = $note->guest_note ? 'tripzzy-guest-note' : '';
			$placeholder  = 'tripzzy-guest-note' === $note_class ? __( 'Guest Note', 'tripzzy' ) : __( 'Private Note', 'tripzzy' );
			$date_created = $note->date_created;
			$comment_date = $date_created->format( \tripzzy_date_format() );
			$comment_time = $date_created->format( \tripzzy_time_format() );
			ob_start();

			?>
			<li rel="<?php echo absint( $note->id ); ?>" class="tripzzy-note <?php echo esc_attr( $note_class ); ?>" title="<?php echo esc_attr( $placeholder ); ?>">
				<div class="tripzzy-note-content">
					<?php echo wp_kses_post( wpautop( wptexturize( make_clickable( $note->content ) ) ) ); ?>
				</div>
				<p class="tripzzy-note-meta">
					<abbr class="exact-date" title="<?php echo esc_attr( $comment_date ); ?>">
						<?php
						/* translators: $1: Date created, $2 Time created */
						printf( esc_html__( 'added on %1$s at %2$s', 'tripzzy' ), esc_html( $comment_date ), esc_html( $comment_time ) );
						?>
					</abbr>
					<?php
					if ( 'system' !== $note->added_by ) :
						/* translators: %s: note author */
						printf( ' ' . esc_html__( 'by %s ', 'tripzzy' ), esc_html( $note->added_by ) );
					endif;
					?>
					<a href="#" class="tripzzy-delete-note" role="button"><?php esc_html_e( 'Delete note', 'tripzzy' ); ?></a>
				</p>
			</li>
			<?php

			$note = ob_get_clean();

			$response = array(
				'note' => Strings::trim_nl( $note ),
			);
			wp_send_json_success( $response );
		}

		/**
		 * Ajax Callback to delete note.
		 *
		 * @since 1.1.8
		 */
		public function delete_booking_note() {
			if ( ! Nonce::verify() ) {
				$message = array(
					'message' => $this->strings['nonce_verification_failed'],
				);
				wp_send_json_error( $message );
			}
			$data = Request::sanitize_input( 'INPUT_PAYLOAD' );

			$note_id = $data['note_id'] ?? 0;

			Bookings::delete_note( $note_id );

			$response = array(
				'note_id' => $note_id,
			);
			wp_send_json_success( $response );
		}
	}

	NotesAjax::instance();
}
