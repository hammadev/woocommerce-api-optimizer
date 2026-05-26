<?php
namespace WC_API_Optz\Endpoints;

defined( 'ABSPATH' ) || exit;

class Payment_Gateways_Endpoint {

    public function register_routes() {
        \register_rest_route( 'wp/v2', '/payment-gateways', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_gateways' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function get_gateways( \WP_REST_Request $request ) {
        $gateways = \WC_Payment_Gateways::instance()->get_available_payment_gateways();
        $active   = [];

        foreach ( $gateways as $id => $gateway ) {
            if ( ! $gateway->is_available() ) continue;
            $active[] = [
                'id'    => $id,
                'title' => $gateway->get_title(),
                'order' => $gateway->get_option( 'order' ),
            ];
        }

        usort( $active, fn( $a, $b ) => $a['order'] <=> $b['order'] );
        return \rest_ensure_response( $active );
    }
}
