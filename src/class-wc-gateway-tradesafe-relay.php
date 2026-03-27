<?php
/**
 * TradeSafe Gateway for WooCommerce.
 *
 * @package TradeSafe Payment Gateway
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_Gateway_TradeSafe Implantation of WC_Payment_Gateway
 */
class WC_Gateway_TradeSafe_Relay extends WC_Gateway_TradeSafe {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'tradesafe-relay';
		$this->method_title       = __( 'Relay', 'tradesafe-payment-gateway' );
		$this->method_description = __( 'Fast, simple and secure payments powered by Relay', 'tradesafe-payment-gateway' );
		$this->icon               = TRADESAFE_PAYMENT_GATEWAY_BASE_DIR . '/assets/images/icon-relay.png';

		$this->client = new \TradeSafe\Helpers\TradeSafeApiClient( $this->id );

		$this->version              = WC_GATEWAY_TRADESAFE_VERSION;
		$this->available_countries  = array( 'ZA' );
		$this->available_currencies = (array) apply_filters( 'woocommerce_gateway_tradesafe_available_currencies', array( 'ZAR' ) );

		// Supported functionality.
		$this->supports = array(
			'products',
			'refunds',
			'tokenization',
		);

		// Setup default merchant data.
		$this->has_fields  = true;
		$this->enabled     = $this->is_valid_for_use() ? 'yes' : 'no'; // Check if the base currency supports this gateway.
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'tradesafe_payment_gateway_admin_order_data_after_order_details' ) );
		add_action( 'woocommerce_receipt_tradesafe', array( $this, 'receipt_page' ) );
		add_action( 'post_action_tradesafe_deliver', array( $this, 'tradesafe_payment_gateway_admin_post_action_deliver' ) );
		add_action( 'admin_notices', array( $this, 'tradesafe_payment_gateway_admin_notice' ), 1 );

		if ( is_admin() ) {
			wp_enqueue_script( 'tradesafe-payment-gateway-settings', TRADESAFE_PAYMENT_GATEWAY_BASE_DIR . '/assets/js/settings.js', array( 'jquery' ), WC_GATEWAY_TRADESAFE_VERSION, true );
			wp_enqueue_style( 'tradesafe-payment-gateway-settings', TRADESAFE_PAYMENT_GATEWAY_BASE_DIR . '/assets/css/style.css', array(), WC_GATEWAY_TRADESAFE_VERSION );

			if ( isset( $_GET['page'] ) && isset( $_GET['tab'] ) && isset( $_GET['section'] ) && $_GET['page'] === 'wc-settings' && $_GET['tab'] === 'checkout' && $_GET['section'] === 'tradesafe-relay' ) {
				$this->init_form_fields();
				$this->init_settings();
			}
		}
	}

	/**
	 * Get title function.
	 *
	 * @return string
	 */
	public function get_title() {
		$title = strip_tags( $this->title );
		$title = apply_filters( 'woocommerce_gateway_title', $title, $this->id );

		// show the title with an icon on the checkout page alone
		$logo_url = plugins_url( '../assets/images/logos.png', __FILE__ );
		$img      = '<img src="' . $logo_url . '" style="height: 1.4em;margin-left: 0px;margin-right: 0.3em;display: inline;float: none;" class="' . $this->id . '-payment-method-title-icon" alt="Relay logo" />';
		$title    = '<span style="display: inline-flex;flex-direction: column;align-items: start;vertical-align: middle;"><span>Pay with Visa, Mastercard, Ozow, PayJustNow, SnapScan, or RCS</span><span>' . $img . '</span></span>';
		return apply_filters( 'woocommerce_gateway_title', $title, $this->id );
	}
}
