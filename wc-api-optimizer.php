<?php
/**
 * Plugin Name:       WC API Optimizer
 * Description:       Optimizes WooCommerce REST API responses with field filtering and adds custom endpoints for auth, payments, and store settings.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Hammad Anwar
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
