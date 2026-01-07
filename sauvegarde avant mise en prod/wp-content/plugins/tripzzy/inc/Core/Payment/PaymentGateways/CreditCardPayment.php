<?php
namespace Tripzzy\Core\Payment\PaymentGateways;

use Tripzzy\Core\Helpers\MetaHelpers;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\CreditCardPayment' ) ) {

    class CreditCardPayment {

        public function __construct() {
            // Ajouter cette passerelle aux options disponibles
            add_filter( 'tripzzy_filter_payment_gateways', array( $this, 'register_gateway' ) );

            // Traiter le paiement lors de la réservation
            add_action( 'tripzzy_after_booking', array( $this, 'process_payment' ), 10, 2 );
        }

        public function register_gateway( $gateways ) {
            $gateways['credit_card'] = array(
                'title' => __( 'Credit Card', 'tripzzy' ),
                'description' => __( 'Pay securely using your credit card.', 'tripzzy' ),
            );
            return $gateways;
        }

        public function process_payment( $booking_id, $data ) {
            if ( isset( $data['payment_mode'] ) && $data['payment_mode'] === 'credit_card' ) {
                // Ici, on simule un paiement CB accepté
                $payment_id = wp_insert_post( array(
                    'post_title'   => 'Credit Card Payment',
                    'post_content' => '',
                    'post_status'  => 'publish',
                    'post_type'    => 'tripzzy_payment',
                ), true );

                // Enregistrer les métadonnées
                MetaHelpers::update_post_meta( $payment_id, 'payment_details', $data['payment_details'] ?? 'Paid via Credit Card' );
                MetaHelpers::update_post_meta( $payment_id, 'payment_mode', 'credit_card' );
                MetaHelpers::update_post_meta( $payment_id, 'payment_amount', $data['payment_amount'] ?? 0 );

                // Mettre à jour la réservation
                MetaHelpers::update_post_meta( $booking_id, 'payment_status', 'paid' );
                MetaHelpers::update_post_meta( $booking_id, 'booking_status', 'booked' );

                // Ajouter le payment_id dans la réservation
                $payment_ids = MetaHelpers::get_post_meta( $booking_id, 'payment_ids' ) ?: array();
                $payment_ids[] = $payment_id;
                MetaHelpers::update_post_meta( $booking_id, 'payment_ids', $payment_ids );
            }
        }
    }
}