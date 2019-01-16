<?php
/**
 * Plugin Name: WooCommerce TradeSafe Gateway
 * Plugin URI: https://github.com/tradesafe-plugins/woocommerce-tradesafe-gateway
 * Description: Receive payments using the TradeSafe API.
 * Author: TradeSafe
 * Author URI: http://www.tradesafe.co.za/
 * Version: 1.0.0
 * Requires at least: 5.0
 * Tested up to: 5.0
 * WC tested up to: 3.5
 * WC requires at least: 3.5
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize the gateway.
 * @since 1.0.0
 */
function woocommerce_tradesafe_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	define( 'WC_GATEWAY_TRADESAFE_VERSION', '1.0.0' );

	require_once( plugin_basename( 'includes/class-wc-gateway-tradesafe.php' ) );
	require_once( plugin_basename( 'includes/class-wc-gateway-tradesafe-privacy.php' ) );
	load_plugin_textdomain( 'woocommerce-gateway-tradesafe', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) );
	add_filter( 'woocommerce_payment_gateways', 'woocommerce_tradesafe_add_gateway' );
}
add_action( 'plugins_loaded', 'woocommerce_tradesafe_init', 0 );

function woocommerce_tradesafe_plugin_links( $links ) {
	$settings_url = add_query_arg(
		array(
			'page' => 'wc-settings',
			'tab' => 'checkout',
			'section' => 'wc_gateway_tradesafe',
		),
		admin_url( 'admin.php' )
	);

	$plugin_links = array(
		'<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'woocommerce-gateway-tradesafe' ) . '</a>',
		'<a href="https://www.tradesafe.co.za/page/contact">' . __( 'Support', 'woocommerce-gateway-tradesafe' ) . '</a>',
		'<a href="https://www.tradesafe.co.za/page/API">' . __( 'Docs', 'woocommerce-gateway-tradesafe' ) . '</a>',
	);

	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'woocommerce_tradesafe_plugin_links' );


/**
 * Add the gateway to WooCommerce
 * @since 1.0.0
 */
function woocommerce_tradesafe_add_gateway( $methods ) {
	$methods[] = 'WC_Gateway_TradeSafe';
	return $methods;
}
