<?php
/**
 * Payment Gateway : Bank Transfer.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Payment\PaymentGateways;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Payment\PaymentGateways; // Base.
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Traits\GatewayTrait;
use Tripzzy\Core\Bookings;

if ( ! class_exists( 'Tripzzy\Core\Payment\PaymentGateways\BankTransfer' ) ) {
	/**
	 * Payment Gateway : Book Now Pay Later.
	 *
	 * @since 1.1.8
	 */
	class BankTransfer extends PaymentGateways {
		use SingletonTrait;
		use GatewayTrait;

		/**
		 * Payment Gateway type.
		 *
		 * @since 1.1.8
		 * @var string
		 */
		protected static $payment_gateway = 'bank_transfer'; // key/slug.

		/**
		 * Payment Gateway name.
		 *
		 * @since 1.1.8
		 * @var string
		 */
		protected static $payment_gateway_title; // name initialized from constructor.

		/**
		 * Settings
		 *
		 * @since 1.1.8
		 * @var array
		 */
		protected static $settings;

		/**
		 * Constructor.
		 */
		public function __construct() {
			self::$payment_gateway_title = __( 'Bank Transfer', 'tripzzy' );
			self::$settings              = Settings::get(); // for traits.
			add_filter( 'tripzzy_filter_payment_gateways_args', array( $this, 'init_args' ) );

			// Gateway Script.
			add_filter( 'tripzzy_filter_gateway_scripts', array( $this, 'init_gateway_scripts' ) );

			add_action( 'tripzzy_thankyou_page_after_booking_details', array( $this, 'render_account_details' ) );

			add_action( 'tripzzy_after_booking', array( $this, 'update_booking_status_to_on_hold' ), 50, 2 );
		}

		/**
		 * Payment gateway arguments.
		 *
		 * @since 1.1.8
		 */
		protected static function payment_gateway_args() {
			$args = array(
				'title'  => self::$payment_gateway_title,
				'name'   => self::$payment_gateway,
				'fields' => array(
					'enabled'         => array(
						'name'  => 'enabled',
						'label' => __( 'Enabled', 'tripzzy' ),
						'value' => false,
					),
					'description'     => array(
						'name'    => 'description',
						'label'   => __( 'Description', 'tripzzy' ),
						'type'    => 'textarea',
						'value'   => __( 'Make your payment directly into our bank account. Please use your Booking ID as the payment reference.', 'tripzzy' ),
						'tooltip' => __( 'Payment method description that the customer will see on your checkout.', 'tripzzy' ),

					),
					'instruction'     => array(
						'name'    => 'instruction',
						'label'   => __( 'Instruction', 'tripzzy' ),
						'type'    => 'textarea',
						'value'   => '',
						'tooltip' => __( 'Instruction that will be added to the thankyou page.', 'tripzzy' ),
					),

					'account_details' => array(
						'name'                => 'account_details',
						'label'               => __( 'Account Details', 'tripzzy' ),
						'type'                => 'table',
						'value'               => array(),
						'table_fields'        => array(
							array(
								'name'         => 'account_name',
								'label'        => __( 'Account Name', 'tripzzy' ),
								'column_width' => '22%',
							),
							array(
								'name'         => 'account_number',
								'label'        => __( 'Account Number', 'tripzzy' ),
								'column_width' => '22%',

							),
							array(
								'name'         => 'bank_name',
								'label'        => __( 'Bank Name', 'tripzzy' ),
								'column_width' => '20%',
							),
							array(
								'name'         => 'sort_code',
								'label'        => __( 'Sort Code', 'tripzzy' ),
								'column_width' => '12%',
							),
							array(
								'name'         => 'iban',
								'label'        => __( 'IBAN', 'tripzzy' ),
								'column_width' => '12%',
							),
							array(
								'name'         => 'swift',
								'label'        => __( 'BIC / Swift', 'tripzzy' ),
								'column_width' => '12%',
							),
						),
						'has_add_button'      => true,
						'has_remove_button'   => true,
						'has_sortable'        => true,
						'empty_label'         => __( 'No Accounts!!', 'tripzzy' ),
						'add_button_label'    => __( 'Add Account', 'tripzzy' ),
						'remove_button_label' => __( 'Remove Account', 'tripzzy' ),
					),
				),

			);
			return $args;
		}

		/**
		 * Gateway scripts arguments.
		 *
		 * @since 1.1.8
		 */
		protected static function gateway_scripts() {
			$data = self::geteway_data();
			$args = array();
			if ( ! empty( $data ) ) {

				$script_url = sprintf( '%sassets/dist/', TRIPZZY_PLUGIN_DIR_URL );

				$local_src = $script_url . 'bank-transfer.js?ver=' . TRIPZZY_VERSION;
				$args[]    = $local_src;
			}
			return $args;
		}

		/**
		 * Render Account detail for bank transfer.
		 *
		 * @param int $booking_id Booking id.
		 * @since 1.1.8
		 * @return void
		 */
		public function render_account_details( $booking_id ) {
			if ( ! $booking_id ) {
				return;
			}

			$checkout_info = MetaHelpers::get_post_meta( $booking_id, 'checkout_info' );
			$payment_mode  = $checkout_info['payment_mode'] ?? '';
			if ( 'bank_transfer' !== $payment_mode ) {
				return;
			}

			$settings = Settings::get();

			$gateways        = $settings['payment_gateways'];
			$payment_data    = $gateways['bank_transfer'] ?? array();
			$account_details = $payment_data['account_details'] ?? array();
			$instruction     = $payment_data['instruction'] ?? '';

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

			if ( $instruction ) {
				?>
				<div class="tripzzy-bank-transfer-instruction">
					<?php echo wp_kses_post( wpautop( $instruction ) ); ?>
				</div>
				<?php
			}
			if ( is_array( $table_fields ) && count( $table_fields ) > 0 ) :
				?>
				<label class="tripzzy-form-label tripzzy-form-label-wrapper"><?php esc_html_e( 'Our bank details', 'tripzzy' ); ?></label>
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
		}

		/**
		 * Update Booking Status to On Hold.
		 *
		 * @param int $booking_id Booking id.
		 * @since 1.1.8
		 * @return void
		 */
		public function update_booking_status_to_on_hold( $booking_id ) {
			if ( ! $booking_id ) {
				return;
			}
			$checkout_info = MetaHelpers::get_post_meta( $booking_id, 'checkout_info' );

			$payment_mode = $checkout_info['payment_mode'] ?? '';
			if ( 'bank_transfer' === $payment_mode ) {
				$old_status = MetaHelpers::get_post_meta( $booking_id, 'booking_status' );
				if ( 'on_hold' !== $old_status ) {

					$booking_status_options = Bookings::get_booking_status_options();
					MetaHelpers::update_post_meta( $booking_id, 'booking_status', 'on_hold' );

					$old_label = $booking_status_options[ $old_status ] ?? $old_status;
					$new_label = $booking_status_options['on_hold'] ?? 'on_hold';
					// Add Note.
					/* translators: 1: Old Booking Status, 2: New Booking status. */
					$booking_note = sprintf( __( 'Awaiting Bank Transfer payment. Booking status changed from "%1$s" to "%2$s".', 'tripzzy' ), $old_label, $new_label );
					Bookings::add_note( $booking_id, $booking_note, 0 );
				}
			}
		}
	}
}
