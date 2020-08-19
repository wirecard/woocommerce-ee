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

require_once __DIR__ . '/../../../../classes/includes/class-wc-gateway-wirecard-sepa-direct-debit.php';

/**
 * Class WC_Gateway_Wirecard_Sepa_Utest
 * @SuppressWarnings(PHPMD.Superglobals)
 */
class WC_Gateway_Wirecard_Sepa_Utest extends \PHPUnit_Framework_TestCase {

	/** @var WC_Gateway_Wirecard_Sepa_Direct_Debit */
	private $payment;

	public function setUp() {
		$this->payment = new WC_Gateway_Wirecard_Sepa_Direct_Debit();
	}

	public function test_init_form_fields() {
		$this->payment->init_form_fields();
		$this->assertTrue( is_array( $this->payment->form_fields ) );
	}

	public function test_process_payment_fail() {
		$_POST['sepa_nonce'] = 'test';
		$this->assertFalse( $this->payment->process_payment( 12 ) );
	}

	public function test_process_payment() {
		$_POST['sepa_firstname'] = 'firstname';
		$_POST['sepa_lastname']  = 'lastname';
		$_POST['sepa_iban']      = 'iban';
		$_POST['sepa_nonce']     = 'test';

		$this->assertTrue( is_array( $this->payment->process_payment( 12 ) ) );
	}

	public function test_process_refund() {
		$this->assertNotNull( $this->payment->process_refund( 12 ) );
	}

	public function test_process_cancel() {
		$expected = new \Wirecard\PaymentSdk\Transaction\SepaDirectDebitTransaction();
		$expected->setParentTransactionId( 'transaction_id' );
		$expected->setAmount( new \Wirecard\PaymentSdk\Entity\Amount( 20, 'EUR' ) );

		$this->assertEquals( $expected, $this->payment->process_cancel( 12, 20 ) );
	}

	public function test_process_capture() {
		$expected = new \Wirecard\PaymentSdk\Transaction\SepaDirectDebitTransaction();
		$expected->setParentTransactionId( 'transaction_id' );
		$expected->setAmount( new \Wirecard\PaymentSdk\Entity\Amount( 20, 'EUR' ) );

		$this->assertEquals( $expected, $this->payment->process_capture( 12, 20 ) );
	}

	public function test_payment_fields() {
		$this->assertTrue( $this->payment->payment_fields() );
	}
}
