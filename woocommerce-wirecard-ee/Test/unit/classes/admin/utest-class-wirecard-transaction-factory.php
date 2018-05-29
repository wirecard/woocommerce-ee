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

require_once __DIR__ . '/../../../../classes/admin/class-wirecard-transaction-factory.php';

class WC_Gateway_Wirecard_Transaction_Factory_Utest extends \PHPUnit_Framework_TestCase {
	private $transaction_factory;

	private $order;

	private $response;

	public function setUp() {
		/** @var Wirecard_Transaction_Factory transaction_factory */
		$this->transaction_factory = new Wirecard_Transaction_Factory();

		$this->order = $this->getMockBuilder( WC_Order::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get_total', 'get_currency', 'get_id', 'set_transaction_id' ] )
			->getMock();

		$this->order->method( 'get_total' )->willReturn( 20 );
		$this->order->method( 'get_currency' )->willReturn( 'EUR' );
		$this->order->method( 'get_id' )->willReturn( 1 );
		$this->order->method( 'set_transaction_id' );

		$this->response = $this->getMockBuilder( \Wirecard\PaymentSdk\Response\SuccessResponse::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getParentTransactionId', 'getTransactionId', 'findElement', 'getData' ] )
			->getMock();

		$this->response->method( 'getTransactionId' )->willReturn( '123' );
		$this->response->method( 'getParentTransactionId' )->willReturn( '123' );
		$this->response->method( 'findElement' )->with( 'merchant-account-id' )->willReturn( '1234' );

		$response_data = array(
			'requested-amount' => 20,
		);
		$this->response->method( 'getData' )->willReturn( $response_data );
	}

	public function test_update_create_transaction() {
		global $wpdb;

		$parent                    = new stdClass();
		$parent->amount            = 20;
		$parent->transaction_state = 'success';

		$transaction                    = new stdClass();
		$transaction->amount            = 20;
		$transaction->transaction_state = 'awaiting';
		$transactions                   = array( '1' => $transaction );

		$mocked_wpdb = $this->getMockBuilder( WPDB::class )
			->setMethods( [ 'get_results', 'get_row' ] )
			->getMock();
		$mocked_wpdb->method( 'get_row' )->willReturn( $parent );
		$mocked_wpdb->method( 'get_results' )->willReturn( $transactions );
		$wpdb = $mocked_wpdb;

		$this->assertNull( $this->transaction_factory->create_transaction( $this->order, $this->response, 'www.my-url.com', 'closed', 'paypal' ) );
	}

	public function test_insert_create_transaction() {
		$response = $this->getMockBuilder( \Wirecard\PaymentSdk\Response\SuccessResponse::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getParentTransactionId', 'getTransactionId', 'findElement', 'getData' ] )
			->getMock();
		$wpdb     = $this->getMockBuilder( WPDB::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get_row', 'get_results' ] )
			->getMock();

		$response->method( 'getParentTransactionId' )->willReturn( '1234' );
		$response->method( 'getTransactionId' )->willReturn( '1234' );
		$response->method( 'findElement' )->with( 'merchant-account-id' )->willReturn( '1234' );
		$response_data = array(
			'requested-amount' => 20,
		);
		$response->method( 'getData' )->willReturn( $response_data );
		$this->assertNotNull( $this->transaction_factory->create_transaction( $this->order, $response, 'www.my-url.com', 'closed', 'paypal' ) );
	}
}
