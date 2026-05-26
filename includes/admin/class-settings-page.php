<?php
namespace ShopMobi\ApiOptimizer\Admin;

defined( 'ABSPATH' ) || exit;

class Settings_Page {

    public function add_page() {
        \add_submenu_page(
            'woocommerce',
            'API Optimizer for WooCommerce',
            'API Optimizer',
            'manage_options',
            'wc-api-optimizer',
            [ $this, 'render' ]
        );
    }

    public function register_settings() {
        \register_setting( 'shopmobi_ao_settings', 'shopmobi_ao_stripe_secret_key', [
            'sanitize_callback' => 'sanitize_text_field',
        ] );
        \register_setting( 'shopmobi_ao_settings', 'shopmobi_ao_stripe_public_key', [
            'sanitize_callback' => 'sanitize_text_field',
        ] );

        \add_settings_section(
            'shopmobi_ao_stripe',
            'Stripe Configuration',
            null,
            'wc-api-optimizer'
        );

        \add_settings_field(
            'stripe_secret_key',
            'Secret Key',
            [ $this, 'render_secret_key_field' ],
            'wc-api-optimizer',
            'shopmobi_ao_stripe'
        );

        \add_settings_field(
            'stripe_public_key',
            'Publishable Key',
            [ $this, 'render_public_key_field' ],
            'wc-api-optimizer',
            'shopmobi_ao_stripe'
        );
    }

    public function render() {
        if ( ! \current_user_can( 'manage_options' ) ) return;
        ?>
        <div class="wrap">
            <h1><?php \esc_html_e( 'API Optimizer for WooCommerce', 'wc-api-optimizer' ); ?></h1>

            <h2 class="nav-tab-wrapper">
                <span class="nav-tab nav-tab-active">Stripe</span>
            </h2>

            <div style="margin-top:16px; padding:12px 16px; background:#fff3cd; border-left:4px solid #ffc107;">
                <strong>Field Filtering</strong> is active on all WooCommerce REST API endpoints.
                Clients can pass <code>X-WC-Fields: id,name,price</code> (header) or
                <code>?fields=id,name,price</code> (query param) to limit response fields.
                Use <code>X-WC-Except</code> / <code>?except_fields</code> to exclude fields.
            </div>

            <form method="post" action="options.php" style="margin-top:20px;">
                <?php
                \settings_fields( 'shopmobi_ao_settings' );
                \do_settings_sections( 'wc-api-optimizer' );
                \submit_button( 'Save Settings' );
                ?>
            </form>
        </div>
        <?php
    }

    public function render_secret_key_field() {
        $value = \get_option( 'shopmobi_ao_stripe_secret_key', '' );
        \printf(
            '<input type="password" name="shopmobi_ao_stripe_secret_key" value="%s" class="regular-text" placeholder="sk_live_..." autocomplete="new-password">',
            \esc_attr( $value )
        );
        echo '<p class="description">Starts with <code>sk_live_</code> or <code>sk_test_</code></p>';
    }

    public function render_public_key_field() {
        $value = \get_option( 'shopmobi_ao_stripe_public_key', '' );
        \printf(
            '<input type="text" name="shopmobi_ao_stripe_public_key" value="%s" class="regular-text" placeholder="pk_live_...">',
            \esc_attr( $value )
        );
        echo '<p class="description">Starts with <code>pk_live_</code> or <code>pk_test_</code></p>';
    }
}
