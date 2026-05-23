<?php
namespace WC_API_Optz\Endpoints;

defined( 'ABSPATH' ) || exit;

class Stripe_Payment_Endpoint {

    public function register_routes() {
        register_rest_route( 'wp/v2', '/stripe-payment', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'create_payment_intent' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function create_payment_intent( \WP_REST_Request $request ) {
        $secret_key = get_option( 'wc_api_optz_stripe_secret_key', '' );

        if ( empty( $secret_key ) ) {
            return new \WP_Error(
                'stripe_not_configured',
                'Stripe is not configured. Please add your API keys under WooCommerce > API Optimizer.',
                [ 'status' => 500 ]
            );
        }

        if ( ! class_exists( '\Stripe\StripeClient' ) ) {
            return new \WP_Error(
                'stripe_sdk_missing',
                'Stripe SDK not found. Run composer install in the plugin directory.',
                [ 'status' => 500 ]
            );
        }

        $order_amount = absint( $request['order_amount'] ?? 0 );
        $user_id      = absint( $request['user_id'] ?? 0 );

        $stripe      = new \Stripe\StripeClient( $secret_key );
        $customer_id = $this->get_or_create_customer( $stripe, $user_id );

        $ephemeral_key = $stripe->ephemeralKeys->create(
            [ 'customer' => $customer_id ],
            [ 'stripe_version' => '2022-08-01' ]
        );

        $intent = $stripe->paymentIntents->create( [
            'amount'                    => $order_amount * 100,
            'currency'                  => strtolower( get_woocommerce_currency() ),
            'customer'                  => $customer_id,
            'automatic_payment_methods' => [ 'enabled' => true ],
        ] );

        return [
            'paymentIntent'  => $intent->client_secret,
            'ephemeralKey'   => $ephemeral_key->secret,
            'customer'       => $customer_id,
            'publishableKey' => get_option( 'wc_api_optz_stripe_public_key', '' ),
        ];
    }

    private function get_or_create_customer( \Stripe\StripeClient $stripe, int $user_id ): string {
        $existing = get_user_meta( $user_id, 'stripe_cust_id', true );
        if ( $existing ) return $existing;

        $customer = $stripe->customers->create();
        update_user_meta( $user_id, 'stripe_cust_id', $customer->id );
        return $customer->id;
    }
}
