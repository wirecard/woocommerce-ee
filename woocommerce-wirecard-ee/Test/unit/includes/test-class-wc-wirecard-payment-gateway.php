<?php

require __DIR__ . '/../../../woocommerce-wirecard-payment-gateway.php';
require __DIR__ . '/../../../classes/includes/class-wc-gateway-wirecard-paypal.php';

/**
 * Class WC_Wirecard_Payment_Gateway_Test
 */
class WC_Wirecard_Payment_Gateway_Test extends \PHPUnit_Framework_TestCase {
	/**
	 * A single example test.
	 */
	public function test_sample() {
		// Replace this with some actual testing code.
		$paypal = new WC_Gateway_Wirecard_Paypal();
		$this->assertTrue( true );
	}

	public function test_add_wirecard_payment_gateway() {
		$actual = add_wirecard_payment_gateway();
		$expected[] = 'WC_Gateway_Wirecard_Paypal';

		$this->assertEquals($expected, $actual);
	}
}