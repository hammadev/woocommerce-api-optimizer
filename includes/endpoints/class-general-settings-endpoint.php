<?php
namespace ShopMobi\ApiOptimizer\Endpoints;

defined( 'ABSPATH' ) || exit;

class General_Settings_Endpoint {

    public function register_routes() {
        // Public endpoint — returns only data visible to any store visitor
        \register_rest_route( 'shopmobi/v1', '/general-settings', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_settings' ],
            'permission_callback' => '__return_true',
        ] );

        // Authenticated endpoint — returns store address (requires login)
        \register_rest_route( 'shopmobi/v1', '/store-location', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_store_location_response' ],
            'permission_callback' => [ $this, 'is_logged_in' ],
        ] );
    }

    public function is_logged_in(): bool {
        return \current_user_can( 'read' );
    }

    public function get_settings( \WP_REST_Request $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
        return \rest_ensure_response( [
            'country'  => $this->get_country(),
            'currency' => $this->get_currency(),
            'gateways' => $this->get_active_gateways(),
        ] );
    }

    public function get_store_location_response( \WP_REST_Request $request ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
        return \rest_ensure_response( $this->get_store_location() );
    }

    private function get_country(): array {
        $raw   = \get_option( 'woocommerce_default_country', '' );
        $parts = explode( ':', $raw );
        $code  = $parts[0] ?? '';
        $state = $parts[1] ?? '';

        $response = [
            'code'   => $code,
            'name'   => \WC()->countries->countries[ $code ] ?? '',
            'state'  => $state,
            'states' => [],
        ];

        if ( $code ) {
            $states = \WC()->countries->get_states( $code ) ?: [];
            foreach ( $states as $state_code => $state_name ) {
                $response['states'][] = [ 'label' => $state_name, 'value' => $state_code ];
            }
        }

        return $response;
    }

    private function get_currency(): array {
        return [
            'code'               => \get_woocommerce_currency(),
            'symbol'             => \get_woocommerce_currency_symbol(),
            'position'           => \get_option( 'woocommerce_currency_pos' ),
            'decimal_separator'  => \wc_get_price_decimal_separator(),
            'thousand_separator' => \wc_get_price_thousand_separator(),
            'decimals'           => \wc_get_price_decimals(),
        ];
    }

    private function get_store_location(): array {
        return [
            'address'  => \get_option( 'woocommerce_store_address' ),
            'city'     => \get_option( 'woocommerce_store_city' ),
            'postcode' => \get_option( 'woocommerce_store_postcode' ),
            'country'  => \get_option( 'woocommerce_default_country' ),
        ];
    }

    private function get_active_gateways(): array {
        $gateways = \WC_Payment_Gateways::instance()->get_available_payment_gateways();
        $active   = [];

        foreach ( $gateways as $id => $gateway ) {
            if ( ! $gateway->is_available() ) continue;
            $active[] = [
                'id'           => $id,
                'title'        => $gateway->get_title(),
                'description'  => $gateway->get_description(),
                'instructions' => $gateway->get_option( 'instructions' ),
                'order'        => $gateway->get_option( 'order' ),
            ];
        }

        usort( $active, fn( $a, $b ) => $a['order'] <=> $b['order'] );
        return $active;
    }
}
