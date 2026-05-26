<?php
/**
 * Plugin Name:       ShopMobi – API Optimizer for WooCommerce
 * Description:       Your store deserves a better API. Stop receiving 50+ fields when your app needs 3. GraphQL-like field filtering over REST, plus login and Stripe payments out of the box.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            ShopMobi
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       api-optimizer-for-woocommerce
 * Requires Plugins:  woocommerce
 * WC requires at least: 6.0
 * WC tested up to:   9.0
 */

defined( 'ABSPATH' ) || exit;

define( 'SHOPMOBI_AO_VERSION', '1.0.0' );
define( 'SHOPMOBI_AO_FILE',    __FILE__ );
define( 'SHOPMOBI_AO_PATH',    plugin_dir_path( __FILE__ ) );
define( 'SHOPMOBI_AO_URL',     plugin_dir_url( __FILE__ ) );

if ( file_exists( SHOPMOBI_AO_PATH . 'vendor/autoload.php' ) ) {
    require_once SHOPMOBI_AO_PATH . 'vendor/autoload.php';
}

require_once SHOPMOBI_AO_PATH . 'includes/class-plugin.php';

add_action( 'plugins_loaded', [ 'ShopMobi\ApiOptimizer\Plugin', 'instance' ] );
