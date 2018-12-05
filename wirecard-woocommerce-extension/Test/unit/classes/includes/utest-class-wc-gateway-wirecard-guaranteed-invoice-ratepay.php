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

require_once __DIR__ . '/../../../../classes/includes/class-wc-gateway-wirecard-guaranteed-invoice-ratepay.php';

class WC_Gateway_Wirecard_Guaranteed_Invoice_Ratepay_Utest extends \PHPUnit_Framework_TestCase {

	/** @var WC_Gateway_Wirecard_Guaranteed_Invoice_Ratepay */
	private $payment;
	private $basket;
	private $mock_additional_helper;

	public function setUp() {
		global $woocommerce;

		$woocommerce->cart     = new WC_Cart();
		$woocommerce->customer = new WC_Customer();

		$this->payment = new WC_Gateway_Wirecard_Guaranteed_Invoice_Ratepay();

		$version = new \Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction();
		$version->setParentTransactionId( 'transaction_id' );
		$version->setAmount( new \Wirecard\PaymentSdk\Entity\Amount( 50, 'EUR' ) );

		$this->basket = new \Wirecard\PaymentSdk\Entity\Basket();
		$item         = new \Wirecard\PaymentSdk\Entity\Item(
			'nemo',
			new \Wirecard\PaymentSdk\Entity\Amount( 20, 'EUR' ),
			1
		);
		$item->setDescription( 'short description' );
		$item->setArticleNumber( '1' );
		$item->setTaxRate( 0.0 );
		$item->setTaxAmount( new \Wirecard\PaymentSdk\Entity\Amount( 10, 'EUR' ) );
		$this->basket->add( $item );

		$item = new \Wirecard\PaymentSdk\Entity\Item(
			'Shipping',
			new \Wirecard\PaymentSdk\Entity\Amount( 22, 'EUR' ),
			1
		);
		$item->setDescription( 'Shipping' );
		$item->setArticleNumber( 'Shipping' );
		$item->setTaxRate( 10.0 );
		$this->basket->add( $item );

		$this->basket->setVersion( $version );

		$this->mock_additional_helper = $this->getMockBuilder( Additional_Information::class )
			->setMethods( [ 'create_basket_from_parent_transaction' ] )
			->getMock();

		$this->mock_additional_helper->method( 'create_basket_from_parent_transaction' )
			->willReturn( $this->basket );

		$reflection = new \ReflectionClass( $this->payment );
		$property   = $reflection->getProperty( 'additional_helper' );

		$property->setAccessible( true );
		$property->setValue( $this->payment, $this->mock_additional_helper );
	}

	public function test_init_form_fields() {
		$this->payment->init_form_fields();
		$this->assertTrue( is_array( $this->payment->form_fields ) );
	}

	public function test_process_payment_invalid_date_of_birth() {
		$_POST['invoice_date_of_birth']   = '12.12.2021';
		$_POST['ratepay_nonce']           = 'test';
		$_POST['invoice_data_protection'] = 'on';
		$this->assertFalse( is_array( $this->payment->process_payment( 12 ) ) );
	}

	public function test_process_payment_invalid_consent() {
        $_POST['invoice_date_of_birth']   = '12.12.2021';
        $_POST['ratepay_nonce']           = 'test';
        $_POST['invoice_data_protection'] = false;
        $this->assertFalse( is_array( $this->payment->process_payment( 12 ) ) );
    }

	public function test_process_payment() {
		$_POST['invoice_date_of_birth'] = '12.12.1990';
        $_POST['ratepay_nonce']           = 'test';
        $_POST['invoice_data_protection'] = 'on';
		$this->assertTrue( is_array( $this->payment->process_payment( 12 ) ) );
	}

	public function test_process_cancel() {
		$expected = new \Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction();
		$expected->setParentTransactionId( 'transaction_id' );
		$expected->setAmount( new \Wirecard\PaymentSdk\Entity\Amount( 50, 'EUR' ) );
		$expected->setBasket( $this->basket );
		$this->assertEquals( $expected, $this->payment->process_cancel( 12, 50 ) );
	}

	public function test_process_capture() {
		$expected = new \Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction();
		$expected->setParentTransactionId( 'transaction_id' );
		$expected->setAmount( new \Wirecard\PaymentSdk\Entity\Amount( 50, 'EUR' ) );
		$expected->setBasket( $this->basket );
		$this->assertEquals( $expected, $this->payment->process_capture( 12, 50 ) );
	}

	public function test_process_refund() {
		$this->assertNotNull( $this->payment->process_refund( 12, 50 ) );
	}

	public function test_is_available() {
		$this->assertTrue( $this->payment->is_available() );
	}
}
