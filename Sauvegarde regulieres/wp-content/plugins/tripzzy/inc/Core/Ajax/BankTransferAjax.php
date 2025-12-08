<?php
/**
 * Bank Transfer ajax class.
 *
 * @since 1.1.8
 * @package tripzzy
 */

namespace Tripzzy\Core\Ajax;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Payment\PaymentGateways;

if ( ! class_exists( 'Tripzzy\Core\Ajax\BankTransferAjax' ) ) {
	/**
	 * Wishlist Ajax Class.
	 *
	 * @since 1.1.8
	 */
	class BankTransferAjax {
		use SingletonTrait;

		/**
		 * Constructor.
		 */
		public function __construct() {
			// Frontend side Ajax.
			add_action( 'wp_ajax_tripzzy_get_account_details', array( $this, 'render_accout_details' ) );
			add_action( 'wp_ajax_nopriv_tripzzy_get_account_details', array( $this, 'render_accout_details' ) );
		}

		/**
		 * Ajax Callback to render all trips with Markups. Need To move logic in helper file.
		 *
		 * @since 1.1.8
		 */
		public function render_accout_details() {
			$data     = Request::sanitize_input( 'INPUT_PAYLOAD' );
			$settings = Settings::get();

			$gateways        = (array) $settings['payment_gateways'];
			$payment_data    = $gateways['bank_transfer'] ?? array();
			$account_details = $payment_data['account_details'] ?? array();

			$all_gateways = PaymentGateways::get_all_fields();

			$bank_transfer_data = array_filter(
				$all_gateways,
				function ( $method ) {
					return 'bank_transfer' === $method['gateway'];
				}
			);

			// Reset array keys.
			$bank_transfer_data   = array_values( $bank_transfer_data );
			$bank_transfer_fields = $bank_transfer_data[0]['fields'] ?? array();

			$account_details_fields = array_filter(
				$bank_transfer_fields,
				function ( $field ) {
					return 'account_details' === $field['name'];
				}
			);
			$account_details_fields = array_values( $account_details_fields );
			$table_fields           = $account_details_fields[0]['table_fields'] ?? array();

			ob_start();
			if ( is_array( $table_fields ) && count( $table_fields ) > 0 ) : ?>
				<table class="tripzzy-account-details">
					<thead>
						<tr>
							<?php foreach ( $table_fields as $tk => $table_field ) : ?>
								<td><?php echo esc_html( $table_field['label'] ?? '' ); ?></td>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $account_details as $key => $account_detail ) : ?>
							<tr>
								<?php foreach ( $table_fields as $tk => $table_field ) : ?>
									<td>
										<?php
										$field_name = $table_field['name'] ?? '';
										if ( $field_name ) {
											$value = $account_detail[ $field_name ] ?? '';
											echo esc_html( $value );
										}
										?>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php
			endif;

			$account_details = ob_get_clean();

			$response = array(
				'account_details' => Strings::trim_nl( $account_details ),
			);
			wp_send_json_success( $response );
		}
	}

	BankTransferAjax::instance();
}
