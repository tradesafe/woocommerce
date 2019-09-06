<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Email', false ) ) {
	return;
}

/**
 * Class TradeSafeEmail
 */
class TradeSafe_Email {
	// Define Variables
	private static $initiated = false;

	/**
	 * Init
	 */
	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	// Initializes WordPress hooks
	private static function init_hooks() {
		self::$initiated = true;

		// Actions

		// Filters
		add_filter( 'woocommerce_email_classes', [ 'TradeSafe_Email', 'register_email' ], 90, 1 );
	}

	/**
	 * @param $emails
	 *
	 * @return mixed
	 */
	public function register_email( $emails ) {
		$emails['WC_Email_Payment_Processed_Buyer'] = include TRADESAFE_PLUGIN_DIR . '/includes/emails/class-wc-email-payment-processed-buyer.php';

		if ( 'marketplace' === get_option( 'tradesafe_site_role', 'seller' ) ) {
			$emails['WC_Email_Payment_Processed_Seller'] = include TRADESAFE_PLUGIN_DIR . '/includes/emails/class-wc-email-payment-processed-seller.php';
		}

		return $emails;
	}
}
