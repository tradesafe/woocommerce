<?php

// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// Delete site options
delete_option( 'tradesafe_api_token' );
delete_option( 'tradesafe_api_production' );
delete_option( 'tradesafe_site_industry' );
delete_option( 'tradesafe_site_role' );
delete_option( 'tradesafe_site_fee' );
delete_option( 'tradesafe_site_fee_allocation' );
delete_option( 'tradesafe_api_debugging' );

// Delete multisite options
delete_site_option( 'tradesafe_api_token' );
delete_site_option( 'tradesafe_api_production' );
delete_site_option( 'tradesafe_site_industry' );
delete_site_option( 'tradesafe_site_role' );
delete_site_option( 'tradesafe_site_fee' );
delete_site_option( 'tradesafe_site_fee_allocation' );
delete_site_option( 'tradesafe_api_debugging' );

// Remove Post meta
delete_metadata('post', null, 'tradesafe_id', '', true);
delete_metadata('user', null, 'tradesafe_user_id', '', true);
