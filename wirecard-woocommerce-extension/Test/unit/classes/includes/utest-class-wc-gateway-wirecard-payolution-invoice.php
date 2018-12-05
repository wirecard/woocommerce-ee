<?php

class  WC_Gateway_Wirecard_Payolution_Invoice_Utest extends PHPUnit_Framework_TestCase {

	private $payment;

	public function setUp() {
		$this->payment = new WC_Gateway_Wirecard_Payolution_Invoice();
	}

	public function test_init_form_fields() {

		// prepare
		unset( $this->payment->form_fields ); // prevent any setups done by constructor

		// perform
		$this->payment->init_form_fields();

		// validate
		$this->assertTrue( is_array( $this->payment->form_fields ) );
		$this->assertContains( 'billing_shipping_same', array_keys( $this->payment->form_fields ) );
		$this->assertEquals( 'checkbox', $this->payment->form_fields['billing_shipping_same']['type'] );
		$this->assertContains( 'billing_countries', array_keys( $this->payment->form_fields ) );
		$this->assertEquals( 'multiselect', $this->payment->form_fields['billing_countries']['type'] );
		$this->assertContains( 'send_additional', array_keys( $this->payment->form_fields ) );
		$this->assertEquals( 'checkbox', $this->payment->form_fields['send_additional']['type'] );
	}

	public function test_amount_too_low() {

		// prepare
		$low_amount = 10;
		$this->assertFalse( $this->payment->get_option( 'min_amount' ) <= $low_amount );
		$this->assertTrue( $this->payment->get_option( 'max_amount' ) >= $low_amount );

		// perform + validate
		$this->assertFalse( $this->payment->validate_cart_amounts( $low_amount ) );
	}

	public function test_check_amount_too_high() {

		// prepare
		$high_amount = 10000;
		$this->assertTrue( $this->payment->get_option( 'min_amount' ) <= $high_amount );
		$this->assertFalse( $this->payment->get_option( 'max_amount' ) >= $high_amount );

		// perform + validate
		$this->assertFalse( $this->payment->validate_cart_amounts( $high_amount ) );
	}

	public function test_minamount_itself_notallowed() {

		$min_amount = $this->payment->get_option( 'min_amount' );
		$this->assertFalse( $this->payment->validate_cart_amounts( $min_amount ) );

	}

	public function test_maxamount_itself_notallowed() {
		$max_amount = $this->payment->get_option( 'max_amount' );
		$this->assertFalse( $this->payment->validate_cart_amounts( $max_amount ) );
	}

	public function test_amount_ok() {

		// prepare
		$ok_amount = 100;
		$this->assertTrue( $this->payment->get_option( 'min_amount' ) <= $ok_amount );
		$this->assertTrue( $this->payment->get_option( 'max_amount' ) >= $ok_amount );

		// perform + validate
		$this->assertTrue( $this->payment->validate_cart_amounts( $ok_amount ) );
	}

	public function test_refuse_birthdate_under18() {

		$dt_obj         = new DateTime();
		$too_young_date = $dt_obj->sub( new DateInterval( 'P17Y364D' ) )->format( 'd.m.Y' );

		$this->assertFalse( $this->payment->validate_date_of_birth( $too_young_date ) );

		$last_error_msg = get_last_mocked_notice();
		$this->assertNotNull( $last_error_msg );
		$this->assertEquals( 'You need to be older then 18 to order.', $last_error_msg );
	}

	public function test_refuse_invalid_birthdate() {

		$invalid_date = 'invalid date';

		$this->assertFalse( $this->payment->validate_date_of_birth( $invalid_date ) );

		$last_error_msg = get_last_mocked_notice();
		$this->assertNotNull( $last_error_msg );
		$this->assertEquals( 'You need to enter a valid date as birthdate.', $last_error_msg );
	}

	public function test_refuse_empty_birthdate() {

		$this->assertFalse( $this->payment->validate_date_of_birth( '' ) );

		$last_error_msg = get_last_mocked_notice();
		$this->assertNotNull( $last_error_msg );
		$this->assertEquals( 'You need to enter your birthdate to proceed.', $last_error_msg );
	}

	public function test_allowed_birthdate_over18() {

		$dt_obj  = new DateTime();
		$ok_date = $dt_obj->sub( new DateInterval( 'P18Y1D' ) )->format( 'd.m.Y' );

		$this->assertTrue( $this->payment->validate_date_of_birth( $ok_date ) );
	}

	public function test_process_payment_fails_without_birthdate() {
		$order_id                          = 12;
		$_POST                             = $this->prepare_post_parameter_for_pay();
		$_POST['payolution_date_of_birth'] = '';

		$result = $this->payment->process_payment( $order_id );

		$this->assertFalse( $result );
	}

	public function test_process_payment_failes_without_gdpr_agreement() {
		$order_id                          = 12;
		$_POST                             = $this->prepare_post_parameter_for_pay();
		$_POST['payolution_date_of_birth'] = '';

		$result = $this->payment->process_payment( $order_id );

		$this->assertFalse( $result );
	}

	public function test_process_payment() {
		$order_id = 12;
		$_POST    = $this->prepare_post_parameter_for_pay();

		$result = $this->payment->process_payment( $order_id );

		$this->assertTrue( is_array( $result ) );
	}

	// NOTE: it's not a good test because the result is a WordPress error instead a success
	public function test_process_refund() {
		$order_id = 12;

		$result = $this->payment->process_refund( $order_id );

		$this->assertNotNull( $result );
	}

	public function test_process_cancel() {

		$order_id = 12;
		$amount   = 100;

		$expected = new \Wirecard\PaymentSdk\Transaction\PayolutionInvoiceTransaction();
		$expected->setParentTransactionId( 'transaction_id' );
		$expected->setAmount( new \Wirecard\PaymentSdk\Entity\Amount( $amount, 'EUR' ) );

		$this->assertEquals( $expected, $this->payment->process_cancel( $order_id, $amount ) );
	}

	public function test_process_capture() {

		$order_id = 12;
		$amount   = 37.12;

		$expected = new \Wirecard\PaymentSdk\Transaction\PayolutionInvoiceTransaction();
		$expected->setParentTransactionId( 'transaction_id' );
		$expected->setAmount( new \Wirecard\PaymentSdk\Entity\Amount( $amount, 'EUR' ) );

		$this->assertEquals( $expected, $this->payment->process_capture( $order_id, $amount ) );
	}

	private function prepare_post_parameter_for_pay() {
		return array(
			'payolution_date_of_birth'  => '30.10.1999',
			'payolution_gpdr_agreement' => '1',
		);
	}
}
