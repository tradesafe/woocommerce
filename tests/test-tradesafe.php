<?php
/**
 * Class TradeSafeTest
 *
 * @package Woocommerce_Tradesafe_Gateway
 */

/**
 * TradeSafe Class Test
 */
class TradeSafeTest extends WP_UnitTestCase {
	private $class_instance;

	/**
	 * Setup for testing
	 */
	public function setUp() {
		parent::setUp();

		$this->class_instance = new TradeSafe();
	}

	/**
	 * Are plugin link paths correct
	 */
	public function test_plugin_links() {
		$plugin_links = $this->class_instance->plugin_links( [] );
		$expected     = [
			'<a href="http://example.org/wp-admin/options-general.php?page=tradesafe">Settings</a>',
			'<a href="https://www.tradesafe.co.za/page/contact">Support</a>',
			'<a href="https://www.tradesafe.co.za/page/API">Docs</a>'
		];

		$this->assertEquals( $expected, $plugin_links );
	}

	/**
	 * Are $payment methods added
	 */
	public function test_add_payment_methods() {
		$payment_methods = $this->class_instance->add_payment_methods( [] );
		$expected     = [
			'WC_Gateway_TradeSafe_Ecentric',
			'WC_Gateway_TradeSafe_EftSecure',
			'WC_Gateway_TradeSafe_ManualEft'
		];

		$this->assertEquals( $expected, $payment_methods );
	}
}
