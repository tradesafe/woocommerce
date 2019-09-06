<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( 'WC_Email' ) ) {
	return;
}

/**
 * Class WC_Customer_Cancel_Order
 */
class WC_Email_Payment_Processed_Buyer extends WC_Email {
	/**
	 * Create an instance of the class.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		// Email slug we can use to filter other data.
		$this->id             = 'wc_payment_processed_buyer';
		$this->title          = __( 'TradeSafe: Payment Processed (Buyer)', 'woocommerce-tradesafe-gateway' );
		$this->description    = __( 'An email sent once a payment has been processed by TradeSafe.', 'woocommerce-tradesafe-gateway' );
		$this->customer_email = true;

		// Template paths.
		$this->template_html  = 'emails/wc-payment-processed.php';
		$this->template_plain = 'emails/plain/wc-payment-processed.php';
		$this->template_base  = TRADESAFE_PLUGIN_DIR . '/templates/';

		// Action to which we hook onto to send the email.
		add_action( 'tradesafe_payment_processed', [ $this, 'trigger' ] );
		parent::__construct();
	}

	/**
	 * Get email subject.
	 *
	 * @return string
	 * @since  3.1.0
	 */
	public function get_default_subject() {
		return __( '[{site_title}]: Payment Processed for Order #{order_number}', 'woocommerce-tradesafe-gateway' );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 * @since  3.1.0
	 */
	public function get_default_heading() {
		return __( 'Payment Processed: #{order_number}', 'woocommerce-tradesafe-gateway' );
	}

	/**
	 * Trigger the sending of this email.
	 *
	 * @param WC_Order|false $order Order object.
	 */
	public function trigger( $order = false ) {
		$this->setup_locale();

		if ( is_a( $order, 'WC_Order' ) ) {
			$this->object                         = $order;
			$this->recipient                      = $this->object->get_billing_email();
			$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
			$this->placeholders['{order_number}'] = $this->object->get_order_number();
			$this->placeholders['{deposit}']      = $this->object->get_meta( 'tradesafe_buyer_deposit' );

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}
		}

		$this->restore_locale();
	}

	/**
	 * Get content html.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => true,
				'plain_text'         => false,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => true,
				'plain_text'         => true,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Default content to show below main email content.
	 *
	 * @return string
	 * @since 3.7.0
	 */
	public function get_default_additional_content() {
		return __( 'TradeSafe has received and cleared the funds deposited [{deposit}]. The seller is now required to deliver the Goods or Service.', 'woocommerce-tradesafe-gateway' );
	}

	/**
	 * Initialise settings form fields.
	 */
	public function init_form_fields() {
		/* translators: %s: list of placeholders */
		$placeholder_text  = sprintf( __( 'Available placeholders: %s', 'woocommerce-tradesafe-gateway' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' );
		$this->form_fields = array(
			'enabled'            => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-tradesafe-gateway' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'woocommerce-tradesafe-gateway' ),
				'default' => 'yes',
			),
			'subject'            => array(
				'title'       => __( 'Subject', 'woocommerce-tradesafe-gateway' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => $placeholder_text,
				'placeholder' => $this->get_default_subject(),
				'default'     => '',
			),
			'heading'            => array(
				'title'       => __( 'Email heading', 'woocommerce-tradesafe-gateway' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => $placeholder_text,
				'placeholder' => $this->get_default_heading(),
				'default'     => '',
			),
			'additional_content' => array(
				'title'       => __( 'Additional content', 'woocommerce-tradesafe-gateway' ),
				'description' => __( 'Text to appear below the main email content.', 'woocommerce-tradesafe-gateway' ) . ' ' . $placeholder_text,
				'css'         => 'width:400px; height: 75px;',
				'placeholder' => __( 'N/A', 'woocommerce-tradesafe-gateway' ),
				'type'        => 'textarea',
				'default'     => $this->get_default_additional_content(),
				'desc_tip'    => true,
			),
			'email_type'         => array(
				'title'       => __( 'Email type', 'woocommerce-tradesafe-gateway' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'woocommerce-tradesafe-gateway' ),
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_email_type_options(),
				'desc_tip'    => true,
			),
		);
	}
}

return new WC_Email_Payment_Processed_Buyer();
