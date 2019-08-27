<?php

/**
 * Class WC_Gateway_TradeSafe_Eftsecure
 */
class WC_Gateway_TradeSafe_Eftsecure extends WC_Gateway_TradeSafe_Base {
	/**
	 * WC_Gateway_TradeSafe_Eftsecure constructor.
	 */
	public function __construct() {
		$this->id                 = 'tradesafe_eftsecure';
		$this->icon               = WP_PLUGIN_URL . '/' . plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/assets/images/icon.png';
		$this->has_fields         = true;
		$this->method_title       = __( 'TradeSafe Escrow', TRADESAFE_PLUGIN_NAME );
		$this->method_description = __( 'Redirects user to EftSecure to make a Online Payment', TRADESAFE_PLUGIN_NAME );
		$this->order_button_text  = __( 'Proceed to EftSecure', TRADESAFE_PLUGIN_NAME );
		$this->supports           = array(
			'products',
//			'refunds',
		);

		// Supported Countries
		$this->countries = [
			'ZA'
		];

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->enabled     = $this->is_available() ? 'yes' : 'no';

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );
	}

	/**
	 * Config Form
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'     => array(
				'title'   => __( 'Enable/Disable', TRADESAFE_PLUGIN_NAME ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Instant EFT Payment', TRADESAFE_PLUGIN_NAME ),
				'default' => 'yes'
			),
			'title'       => array(
				'title'       => __( 'Title', TRADESAFE_PLUGIN_NAME ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', TRADESAFE_PLUGIN_NAME ),
				'default'     => __( 'Instant EFT', TRADESAFE_PLUGIN_NAME ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', TRADESAFE_PLUGIN_NAME ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', TRADESAFE_PLUGIN_NAME ),
				'default'     => __( 'Make payment using EFT through EftSecure.', TRADESAFE_PLUGIN_NAME ),
				'desc_tip'    => true,
			)
		);
	}
}