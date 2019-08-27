<?php

/**
 * WooCommerce TradeSafe Gateway Uninstall
 *
 * Uninstalling deletes the plugin options.
 *
 * @package woocommerce-tradesafe-gateway\Uninstaller
 * @version 1.0.0
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'tradesafe_api_token' );
delete_option( 'tradesafe_api_production' );
delete_option( 'tradesafe_site_industry' );
delete_option( 'tradesafe_site_role' );
delete_option( 'tradesafe_site_fee' );
delete_option( 'tradesafe_site_fee_allocation' );
delete_option( 'tradesafe_api_debugging' );
