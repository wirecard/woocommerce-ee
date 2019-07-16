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
require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/helper/class-money-formatter.php' );

use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\TransactionService;

/**
 * Class Wirecard_Transaction_Handler
 *
 * Handles/processes back-end operations via Wirecard Payment Gateway
 *
 * @since 1.0.0
 */
class Wirecard_Transaction_Handler extends Wirecard_Handler {

	/** @var Money_Formatter helper to format money amount from DB */
	private $money_formatter;

	/**
	 * Wirecard_Transaction_Handler constructor.
	 *
	 * @since 1.6.5
	 */
	public function __construct() {
		parent::__construct();
		$this->money_formatter = new Money_Formatter();
	}

	/**
	 * Cancel transaction via Payment Gateway
	 *
	 * @param stdClass $transaction_data
	 *
	 * @return string|SuccessResponse
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function cancel_transaction( $transaction_data ) {
		/** @var WC_Wirecard_Payment_Gateway $payment */
		$payment     = $this->get_payment_method( $transaction_data->payment_method );
		$config      = $payment->create_payment_config();
		$amount      = $this->money_formatter->to_float( $transaction_data->amount );
		$transaction = $payment->process_cancel( $transaction_data->order_id, $amount );

		$transaction_service = new TransactionService( $config, $this->logger );
		$response            = null;
		try {
			/** @var Response $response */
			$response = $transaction_service->process( $transaction, Operation::CANCEL );
		} catch ( \Exception $exception ) {
			$this->logger->error( __METHOD__ . ':' . $exception->getMessage() );
		}

		if ( $response instanceof SuccessResponse ) {
			$order = wc_get_order( $transaction_data->order_id );
			$order->set_transaction_id( $response->getTransactionId() );
			$this->restock_returned_items( $transaction_data->order_id );
			return $response;
		}
		if ( $response instanceof FailureResponse ) {
			return __( 'error_transaction_cancel', 'woocommercer-gateway-wirecard' );
		}
		return '';
	}

	/**
	 * Capture transaction via Payment Gateway
	 *
	 * @param stdClass $transaction_data
	 *
	 * @return string|SuccessResponse
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function capture_transaction( $transaction_data ) {
		/** @var WC_Wirecard_Payment_Gateway $payment */
		$payment     = $this->get_payment_method( $transaction_data->payment_method );
		$config      = $payment->create_payment_config();
		$amount      = $this->money_formatter->to_float( $transaction_data->amount );
		$transaction = $payment->process_capture( $transaction_data->order_id, $amount );

		$transaction_service = new TransactionService( $config, $this->logger );
		$response            = null;
		try {
			/** @var Response $response */
			$response = $transaction_service->process( $transaction, Operation::PAY );
		} catch ( \Exception $exception ) {
			$this->logger->error( __METHOD__ . ':' . $exception->getMessage() );
		}

		if ( $response instanceof SuccessResponse ) {
			$order = wc_get_order( $transaction_data->order_id );
			$order->set_transaction_id( $response->getTransactionId() );
			return $response;
		}
		if ( $response instanceof FailureResponse ) {
			return __( 'error_transaction_capture', 'woocommercer-gateway-wirecard' );
		}
		return '';
	}

	/**
	 * Refund transaction via Payment Gateway
	 *
	 * @param stdClass $transaction_data
	 *
	 * @return string|SuccessResponse
	 * @since 1.0.0
	 * @throws Exception
	 */
	public function refund_transaction( $transaction_data ) {
		/** @var WC_Wirecard_Payment_Gateway $payment */
		$payment = $this->get_payment_method( $transaction_data->payment_method );
		$amount  = $this->money_formatter->to_float( $transaction_data->amount );
		$return  = $payment->process_refund( $transaction_data->order_id, $amount );
		if ( is_wp_error( $return ) ) {
			return $return->get_error_message();
		} else {
			$this->restock_returned_items( $transaction_data->order_id );
			return $return;
		}
	}

	/**
	 * Restock returned items (refund, cancel via Transactiontable)
	 *
	 * @param int $order_id
	 *
	 * @since 1.3.1
	 */
	private function restock_returned_items( $order_id ) {
		$order      = wc_get_order( $order_id );
		$line_items = $order->get_items();

		foreach ( $line_items as $item_id => $item ) {
			$product = $item->get_product();
			if ( $product && $product->managing_stock() ) {
				$old_stock = $product->get_stock_quantity();
				$new_stock = wc_update_product_stock( $product, $item->get_quantity(), 'increase' );
				/* translators: 1: item name 2: old stock quantity 3: new stock quantity */
				$order->add_order_note( sprintf( __( 'text_item_stock_increased', 'woocommerce' ), $product->get_id(), $old_stock, $new_stock ) );
			}
		}
	}
}
