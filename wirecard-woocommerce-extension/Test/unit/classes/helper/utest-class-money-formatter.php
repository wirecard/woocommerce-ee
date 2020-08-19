<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

require_once WIRECARD_EXTENSION_HELPER_DIR . 'class-money-formatter.php';

class WC_Gateway_Wirecard_Money_Formatter_Utest extends \PHPUnit_Framework_TestCase {

	private $class_under_test;

	public function setUp() {
		$this->class_under_test = new Money_Formatter();
	}

	public function test_integer() {
		$this->assertEquals( 124, $this->class_under_test->to_float( 124 ) );
	}

	public function test_double() {
		$this->assertEquals( 123.4567, $this->class_under_test->to_float( 123.4567 ) );
	}

	public function test_negative_double() {
		$this->assertEquals( -0.1, $this->class_under_test->to_float( -0.1 ) );
	}

	public function test_float_as_string() {
		$this->assertEquals( 2.34, $this->class_under_test->to_float( '2.34' ) );
	}

	public function test_negative_float_as_string() {
		$this->assertEquals( -1.32, $this->class_under_test->to_float( '-1.32' ) );
	}

	public function test_whitespaces() {
		$this->assertEquals( 10.11, $this->class_under_test->to_float( '   10.11   ' ) );
	}
}
