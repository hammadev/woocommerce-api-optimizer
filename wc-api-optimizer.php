<?php
/**
 * Plugin Name:       WC API Optimizer — by WooLaunch
 * Description:       Your WooCommerce store deserves a better API. Stop receiving 50+ fields when your app needs 3. GraphQL-like field filtering over REST, plus login and Stripe payments out of the box.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            WooLaunch
 * Author URI:        https://woolaunch.com
 * WC requires at least: 6.0
 * WC tested up to:   9.0
 */

defined( 'ABSPATH' ) || exit;

define( 'WC_API_OPTZ_VERSION', '1.0.0' );
define( 'WC_API_OPTZ_FILE',    __FILE__ );
define( 'WC_API_OPTZ_PATH',    plugin_dir_path( __FILE__ ) );
define( 'WC_API_OPTZ_URL',     plugin_dir_url( __FILE__ ) );

if ( file_exists( WC_API_OPTZ_PATH . 'vendor/autoload.php' ) ) {
    require_once WC_API_OPTZ_PATH . 'vendor/autoload.php';
}

require_once WC_API_OPTZ_PATH . 'includes/class-plugin.php';

add_action( 'plugins_loaded', [ 'WC_API_Optz\Plugin', 'instance' ] );
