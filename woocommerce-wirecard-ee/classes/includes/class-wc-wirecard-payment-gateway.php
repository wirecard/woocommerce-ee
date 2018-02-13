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

use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\TransactionService;

/**
 * Class WC_Wirecard_Payment_Gateway
 */
abstract class WC_Wirecard_Payment_Gateway extends WC_Payment_Gateway {

	/**
	 * Add global wirecard payment gateway actions
	 *
	 * @since 1.0.0
	 */
	public function add_payment_gateway_actions() {
		add_action(
			'woocommerce_api_wc_wirecard_payment_gateway',
			array(
				$this,
				'notify'
			)
		);
		add_action(
			'woocommerce_api_wc_wirecard_payment_gateway_redirect',
			array(
				$this,
				'return_request'
			)
		);
	}

	/**
	 * Handle redirects
	 *
	 * @since 1.0.0
	 */
	public function return_request() {
		$order_id = $_REQUEST['order-id'];
		$order    = new WC_Order( $order_id );

		$redirect_url = $this->get_return_url( $order );
		header( 'Location: ' . $redirect_url );
		die();
	}

	/**
	 * Handle notifications
	 *
	 * @since 1.0.0
	 */
	public function notify() {
		echo "notify";
	}

	/**
	 * @param $order
	 * @param $payment_state
	 *
	 * @return string
	 */
	public function create_redirect_url( $order, $payment_state ) {
		$return_url = add_query_arg(
			array(
				'wc-api'       => 'WC_Wirecard_Payment_Gateway_Redirect',
				'order-id'     => $order->get_id(),
				'paymentState' => $payment_state
			),
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);

		return $return_url;
	}

	/**
	 * Create notification url
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function create_notification_url() {
		return add_query_arg(
			'wc-api', 'WC_Wirecard_Payment_Gateway',
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);
	}

	/**
	 * Execute transactions via wirecard payment gateway
	 *
	 * @param $transaction
	 * @param $config
	 * @param $operation
	 * @param $order
	 * @param $order_id
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function execute_transaction( $transaction, $config, $operation, $order, $order_id ) {
		$logger              = new WC_Logger();
		$transaction_service = new TransactionService( $config );
		try {
			/** @var $response Response */
			$response = $transaction_service->process( $transaction, $operation );
			$logger->error( print_r( $response, true ) );
		}
		catch ( \Exception $exception ) {
			$logger->error( print_r( $exception, true ) );
		}

		$page_url = $order->get_checkout_payment_url( true );
		$page_url = add_query_arg( 'key', $order->get_order_key(), $page_url );
		$page_url = add_query_arg( 'order-pay', $order_id, $page_url );

		if ( $response instanceof InteractionResponse ) {
			$page_url = $response->getRedirectUrl();
		}

		// FailureResponse, redirect should be implemented
		if ( $response instanceof FailureResponse ) {
			$errors = "";
			foreach ( $response->getStatusCollection()->getIterator() as $item ) {
				/** @var Status $item */
				$errors .= $item->getDescription() . "<br>\n";
			}
			throw new InvalidArgumentException( $errors );
		}

		return array(
			'result'   => 'success',
			'redirect' => $page_url
		);
	}
}
