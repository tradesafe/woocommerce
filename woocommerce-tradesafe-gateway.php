<?php
/**
 * Plugin Name: WooCommerce TradeSafe Gateway
 * Plugin URI: https://github.com/tradesafe/woocommerce-tradesafe-gateway
 * Description: Receive payments using the TradeSafe Escrow.
 * Author: TradeSafe
 * Author URI: http://www.tradesafe.co.za/
 * Version: 1.0.0
 * Requires at least: 5.0
 * Tested up to: 5.2
 * WC tested up to: 3.6
 * WC requires at least: 3.6
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants
define( 'TRADESAFE_VERSION', '1.0.0' );
define( 'TRADESAFE_DOMAIN', 'www.tradesafe.co.za' );
define( 'TRADESAFE_API_TEST_DOMAIN', 'sandbox.tradesafe.co.za' );
define( 'TRADESAFE_API_PROD_DOMAIN', 'www.tradesafe.co.za' );
define( 'TRADESAFE_PLUGIN_DIR', dirname( __FILE__ ) );
define( 'TRADESAFE_PLUGIN_FILE_PATH', __FILE__ );

// Debug options
$debug_domain  = isset( $_ENV['TRADESAFE_DEBUG_DOMAIN'] ) ? $_ENV['TRADESAFE_DEBUG_DOMAIN'] : '';
$debug_ca_path = isset( $_ENV['TRADESAFE_API_DEBUG_CA_PATH'] ) ? $_ENV['TRADESAFE_API_DEBUG_CA_PATH'] : '';
define( 'TRADESAFE_API_DEBUG_DOMAIN', $debug_domain );
define( 'TRADESAFE_API_DEBUG_CA_PATH', $debug_ca_path );

// Load classes
require_once( TRADESAFE_PLUGIN_DIR . '/includes/class-tradesafe.php' );
require_once( TRADESAFE_PLUGIN_DIR . '/includes/class-tradesafe-api-wrapper.php' );
require_once( TRADESAFE_PLUGIN_DIR . '/includes/class-tradesafe-profile.php' );
require_once( TRADESAFE_PLUGIN_DIR . '/includes/class-tradesafe-orders.php' );

// Init Plugin
add_action( 'init', [ 'TradeSafe', 'init' ] );
add_action( 'init', [ 'TradeSafeProfile', 'init' ] );
add_action( 'init', [ 'TradeSafeOrders', 'init' ] );

