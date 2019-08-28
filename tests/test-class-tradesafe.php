<?php

use WP_Mock\Tools\TestCase;

class TestClassTradesafe extends TestCase {
	public $class_instance;

	public function setUp(): void {
		WP_Mock::setUp();

		$this->class_instance = new TradeSafe();
	}

	public function tearDown(): void {
		WP_Mock::tearDown();
	}

	public function test_plugin_links() {
		$result   = $this->class_instance->plugin_links( [] );
		$expected = [
			'<a href="http://example.org/wp-admin/options-general.php?page=tradesafe">Settings</a>',
			'<a href="https://www.tradesafe.co.za/page/contact">Support</a>',
			'<a href="https://www.tradesafe.co.za/page/API">Docs</a>',
		];

		$this->assertEquals( $expected, $result );
	}

	public function test_add_payment_methods() {
		$result   = $this->class_instance->add_payment_methods( [] );
		$expected = [
			'WC_Gateway_TradeSafe_Ecentric',
			'WC_Gateway_TradeSafe_EftSecure',
			'WC_Gateway_TradeSafe_ManualEft',
		];

		$this->assertEquals( $expected, $result );
	}
}
