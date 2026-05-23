<?php
namespace WC_API_Optz;

defined( 'ABSPATH' ) || exit;

class Plugin {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', [ $this, 'missing_wc_notice' ] );
            return;
        }

        $this->load();
        $this->init_hooks();
    }

    private function load() {
        require_once WC_API_OPTZ_PATH . 'includes/endpoints/class-auth-endpoints.php';
        require_once WC_API_OPTZ_PATH . 'includes/endpoints/class-password-reset-endpoints.php';
        require_once WC_API_OPTZ_PATH . 'includes/endpoints/class-general-settings-endpoint.php';
        require_once WC_API_OPTZ_PATH . 'includes/endpoints/class-stripe-payment-endpoint.php';
        require_once WC_API_OPTZ_PATH . 'includes/endpoints/class-payment-gateways-endpoint.php';
        require_once WC_API_OPTZ_PATH . 'includes/filters/class-field-optimizer.php';
        require_once WC_API_OPTZ_PATH . 'includes/filters/class-product-response-filter.php';
        require_once WC_API_OPTZ_PATH . 'includes/admin/class-settings-page.php';
    }

    private function init_hooks() {
        // REST endpoints
        add_action( 'rest_api_init', [ new Endpoints\Auth_Endpoints(), 'register_routes' ] );
        add_action( 'rest_api_init', [ new Endpoints\Password_Reset_Endpoints(), 'register_routes' ] );
        add_action( 'rest_api_init', [ new Endpoints\General_Settings_Endpoint(), 'register_routes' ] );
        add_action( 'rest_api_init', [ new Endpoints\Stripe_Payment_Endpoint(), 'register_routes' ] );
        add_action( 'rest_api_init', [ new Endpoints\Payment_Gateways_Endpoint(), 'register_routes' ] );

        // Field optimizer — applies to all major WC endpoints
        $optimizer = new Filters\Field_Optimizer();
        $wc_filters = [
            'woocommerce_rest_prepare_product_object',
            'woocommerce_rest_prepare_product_variation_object',
            'woocommerce_rest_prepare_shop_order_object',
            'woocommerce_rest_prepare_shop_order_refund_object',
            'woocommerce_rest_prepare_customer',
        ];
        foreach ( $wc_filters as $hook ) {
            add_filter( $hook, [ $optimizer, 'filter_fields' ], 20, 3 );
        }

        // Product variation enhancer runs before optimizer (priority 10 vs 20)
        add_filter(
            'woocommerce_rest_prepare_product_object',
            [ new Filters\Product_Response_Filter(), 'enhance_variations' ],
            10, 3
        );

        // Admin settings
        $settings = new Admin\Settings_Page();
        add_action( 'admin_menu',  [ $settings, 'add_page' ] );
        add_action( 'admin_init',  [ $settings, 'register_settings' ] );
    }

    public function missing_wc_notice() {
        printf(
            '<div class="error"><p><strong>WC API Optimizer</strong> requires WooCommerce. Please install and activate it.</p></div>'
        );
    }
}
