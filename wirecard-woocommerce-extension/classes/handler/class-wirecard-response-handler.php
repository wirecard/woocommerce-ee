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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/handler/class-wirecard-handler.php' );

use Wirecard\PaymentSdk\Exception\MalformedResponseException;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;

/**
 * Class Wirecard_Response_Handler
 *
 * Handles responses recieved via Wirecard Payment Gateway
 *
 * @since 1.0.0
 */
class Wirecard_Response_Handler extends Wirecard_Handler {

	/**
	 * Handle response via transaction service
	 *
	 * @param array $request
	 *
	 * @throws \InvalidArgumentException
	 * @throws MalformedResponseException
	 *
	 * @return SuccessResponse|bool
	 *
	 * @since 1.0.0
	 */
	public function handle_response( $request ) {
		/** @var WC_Wirecard_Payment_Gateway $payment */
		$payment = $this->get_payment_method( $request['payment-method'] );
		$config  = $payment->create_payment_config();
		try {
			$transaction_service = new TransactionService( $config, $this->logger );
			/** @var Response $result */
			$result = $transaction_service->handleResponse( $request );
			if ( $result instanceof SuccessResponse ) {
				unset( WC()->session->ratepay_device_ident );
				return $result;
			}
		} catch ( \InvalidArgumentException $exception ) {
			$this->logger->error( __METHOD__ . ':' . 'Invalid argument set: ' . $exception->getMessage() );
			throw $exception;
		} catch ( MalformedResponseException $exception ) {
			$this->logger->error( __METHOD__ . ':' . 'Response is malformed: ' . $exception->getMessage() );
			throw $exception;
		}
		return false;
	}
}
