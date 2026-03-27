<?php
/**
 * TradeSafe Gateway for WooCommerce.
 *
 * @package TradeSafe Payment Gateway
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Gateway_TradeSafe_Relay_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'tradesafe-relay';

	public function initialize() {
		$this->settings = get_option( 'woocommerce_' . $this->name . '_settings', array() );
	}

	public function is_active() {
		$payment_gateways_class = WC()->payment_gateways();
		$payment_gateways       = $payment_gateways_class->payment_gateways();

		return $payment_gateways[ $this->name ]->is_available();
	}

	public function get_payment_method_script_handles() {
		wp_register_script(
			$this->name . '-payment-gateway-blocks-integration',
			plugin_dir_url( __FILE__ ) . '../assets/js/checkout-relay.js',
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
			wp_set_script_translations( $this->name . '-payment-gateway-blocks-integration', 'tradesafe_payment_gateway' );
		}

		return array( $this->name . '-payment-gateway-blocks-integration' );
	}

	public function get_payment_method_data() {
		return array(
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => $this->get_supported_features(),
			'logo_urls'   => array(
				TRADESAFE_PAYMENT_GATEWAY_BASE_DIR . '/assets/images/logos.png',
			),
		);
	}

	public function get_supported_features() {
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		return $payment_gateways[ $this->name ]->supports;
	}
}
