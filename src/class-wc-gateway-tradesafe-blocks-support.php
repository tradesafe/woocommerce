<?php
/**
 * TradeSafe Gateway for WooCommerce.
 *
 * @package TradeSafe Payment Gateway
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Gateway_TradeSafe_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'tradesafe';

	public function initialize() {
		$this->settings = get_option( 'woocommerce_tradesafe_settings', array() );
	}

	public function is_active() {
		$payment_gateways_class = WC()->payment_gateways();
		$payment_gateways       = $payment_gateways_class->payment_gateways();

		return $payment_gateways['tradesafe']->is_available();
	}

	public function get_payment_method_script_handles() {
		wp_register_script(
			'tradesafe-payment-gateway-blocks-integration',
			plugin_dir_url( __FILE__ ) . '../assets/js/checkout.js',
			array(
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			),
			WC_GATEWAY_TRADESAFE_VERSION,
			array(
				'in_footer' => true,
			)
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'tradesafe-payment-gateway-blocks-integration', 'tradesafe-payment-gateway' );
		}

		return array( 'tradesafe-payment-gateway-blocks-integration' );
	}

	public function get_payment_method_data() {
		return array(
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => $this->get_supported_features(),
			'logo_urls'   => array(
				TRADESAFE_PAYMENT_GATEWAY_BASE_DIR . '/assets/images/icon.svg',
				TRADESAFE_PAYMENT_GATEWAY_BASE_DIR . '/assets/images/logos.svg',
			),
		);
	}

	public function get_supported_features() {
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		return $payment_gateways['tradesafe']->supports;
	}

}
