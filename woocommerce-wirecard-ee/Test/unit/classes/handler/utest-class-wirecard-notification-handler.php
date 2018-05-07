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

require_once __DIR__ . '/../../../../classes/handler/class-wirecard-notification-handler.php';

class WC_Gateway_Wirecard_Notification_Handler_Utest extends \PHPUnit_Framework_TestCase {
	/** @var  Wirecard_Notification_Handler */
	private $notification_handler;

	private $payload;

	public function setUp() {
		$this->notification_handler = new Wirecard_Notification_Handler();
		$this->payload              = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><payment><merchant-account-id>merchant-id</merchant-account-id><transaction-id>some_id</transaction-id><request-id>more_id</request-id><transaction-type>purchase</transaction-type><transaction-state>success</transaction-state><completion-time-stamp>2018-04-27T13:20:14.000Z</completion-time-stamp><statuses><status code="201.0000" description="3d-acquirer:The resource was successfully created." severity="information"/></statuses><requested-amount currency="EUR">26.11</requested-amount><payment-methods><payment-method name="paypal"/></payment-methods></payment>';
	}

	public function test_success_handle_notification() {
		$this->assertTrue(
			$this->notification_handler->handle_notification( 'paypal', $this->payload ) instanceof \Wirecard\PaymentSdk\Response\SuccessResponse
		);
	}

	public function test_failure_handle_notification() {
		$failure_payload = str_replace( 'success', 'failure', $this->payload );
		$this->assertFalse( $this->notification_handler->handle_notification( 'paypal', $failure_payload ) );
	}

	/**
	 * @expectedException \Wirecard\PaymentSdk\Exception\MalformedResponseException
	 */
	public function test_malformed_response_exception_handle_notification() {
		$invalid_payload = substr( $this->payload, 0, 20 );
		$this->notification_handler->handle_notification( 'paypal', $invalid_payload );
	}
}
