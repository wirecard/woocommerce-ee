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

require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/handler/class-wirecard-response-handler.php' );
require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/handler/class-wirecard-notification-handler.php' );
require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/handler/class-wirecard-callback.php' );
require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/admin/class-wirecard-transaction-factory.php' );
require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/helper/class-logger.php' );


use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;

/**
 * Class WC_Wirecard_Payment_Gateway
 *
 * @extends WC_Payment_Gateway
 *
 * @since   1.0.0
 */
abstract class WC_Wirecard_Payment_Gateway extends WC_Payment_Gateway {

	/**
	 * Parent transaction types which support cancel operation
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var array
	 */
	protected $cancel = array( 'authorization' );

	/**
	 * Parent transaction types which support refund/cancel(refund) operation
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var array
	 */
	protected $refund = array( 'capture-authorization' );

	/**
	 * Parent transaction types which support pay(capture) operation
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var array
	 */
	protected $capture = array( 'authorization' );

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
				'notify',
			)
		);
		add_action(
			'woocommerce_api_wc_wirecard_payment_gateway_redirect',
			array(
				$this,
				'return_request',
			)
		);
		add_action(
			'woocommerce_api_checkout_form_submit',
			array(
				$this,
				'callback',
			)
		);
	}

	/**
	 * Handle redirects
	 *
	 * @throws \Wirecard\PaymentSdk\Exception\MalformedResponseException
	 *
	 * @since 1.0.0
	 */
	public function return_request() {
		$redirect_url = $this->get_return_url();
		if ( ! array_key_exists( 'order-id', $_REQUEST ) ) {
			header( 'Location:' . $redirect_url );
			die();
		}
		$order_id = $_REQUEST['order-id'];
		$order    = new WC_Order( $order_id );

		if ( 'cancel' == $_REQUEST['payment-state'] ) {
			wc_add_notice( __( 'You have canceled the payment process.', 'woocommerce-gateway-wirecard' ), 'notice' );
			header( 'Location:' . $order->get_cancel_endpoint() );
			die();
		}

		$response_handler = new Wirecard_Response_Handler();
		try {
			$status = $response_handler->handle_response( $_REQUEST );
		} catch ( Exception $exception ) {
			wc_add_notice( __( 'An error occurred during the payment process. Please try again.', 'woocommerce-gateway-wirecard' ), 'error' );
			header( 'Location:' . $order->get_cancel_endpoint() );
			die();
		}

		if ( ! $status ) {
			wc_add_notice( __( 'An error occurred during the payment process. Please try again.', 'woocommerce-gateway-wirecard' ), 'error' );
			$redirect_url = $order->get_cancel_endpoint();
		} else {
			if ( ! $order->is_paid() && ( 'authorization' != $order->get_status() ) ) {
				$order->update_status( 'on-hold', __( 'Awaiting payment from Wirecard', 'woocommerce-gateway-wirecard' ) );
			}
			$redirect_url = $this->get_return_url( $order );
		}
		header( 'Location: ' . $redirect_url );
		die();
	}

	/**
	 * Handle notifications
	 *
	 * @since 1.0.0
	 */
	public function notify() {
		if ( ! isset( $_REQUEST['payment-method'] ) ) {
			return;
		}
		$payment_method       = $_REQUEST['payment-method'];
		$order_id             = $_REQUEST['order-id'];
		$order                = new WC_Order( $order_id );
		$notification         = file_get_contents( 'php://input' );
		$notification_handler = new Wirecard_Notification_Handler();
		try {
			/** @var Response $response */
			$response = $notification_handler->handle_notification( $payment_method, $notification );
			if ( $response ) {
				$this->save_response_data( $order, $response );
				$this->update_payment_transaction( $order, $response );
				$order = $this->update_order_state( $order, $response->getTransactionType() );
			}
		} catch ( Exception $exception ) {
			if ( ! $order->is_paid() ) {
				$logger = new Logger();
				$logger->debug( __METHOD__ . $exception->getMessage() );
			}
			die();
		}
		die();
	}

	/**
	 * Create redirect url including orderinformation
	 *
	 * @param WC_Order $order
	 * @param string   $payment_state
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function create_redirect_url( $order, $payment_state, $payment_method ) {
		$return_url = add_query_arg(
			array(
				'wc-api'         => 'WC_Wirecard_Payment_Gateway_Redirect',
				'order-id'       => $order->get_id(),
				'payment-state'  => $payment_state,
				'payment-method' => $payment_method,
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
	public function create_notification_url( $order, $payment_method ) {
		return add_query_arg(
			array(
				'wc-api'         => 'WC_Wirecard_Payment_Gateway',
				'payment-method' => $payment_method,
				'order-id'       => $order->get_id(),
			),
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);
	}

	/**
	 * Execute transactions via wirecard payment gateway
	 *
	 * @param \Wirecard\PaymentSdk\Transaction\Transaction $transaction
	 * @param \Wirecard\PaymentSdk\Config\Config           $config
	 * @param string                                       $operation
	 * @param WC_Order                                     $order
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function execute_transaction( $transaction, $config, $operation, $order ) {
		$logger              = new Logger();
		$transaction_service = new TransactionService( $config, $logger );
		try {
			/** @var $response Response */
			$response = $transaction_service->process( $transaction, $operation );
		} catch ( \Exception $exception ) {
			$logger->error( __METHOD__ . ':' . $exception->getMessage() );

			wc_add_notice( __( 'An error occurred during the payment process. Please try again.', 'woocommerce-gateway-wirecard' ), 'error' );

			return array(
				'result'   => 'error',
				'redirect' => '',
			);
		}

		$page_url = $order->get_checkout_payment_url( true );
		$page_url = add_query_arg( 'key', $order->get_order_key(), $page_url );
		$page_url = add_query_arg( 'order-pay', $order->get_order_number(), $page_url );

		if ( $response instanceof InteractionResponse ) {
			$page_url = $response->getRedirectUrl();
		} elseif ( $response instanceof FormInteractionResponse ) {
			$data['url']         = $response->getUrl();
			$data['method']      = $response->getMethod();
			$data['form_fields'] = $response->getFormFields();
			WC()->session->set( 'wirecard_post_data', $data );
			$page_url = add_query_arg(
				[ 'wc-api' => 'checkout_form_submit' ],
				site_url( '/', is_ssl() ? 'https' : 'http' )
			);
		}

		if ( $response instanceof FailureResponse ) {
			$errors = '';
			foreach ( $response->getStatusCollection()->getIterator() as $item ) {
				/** @var Status $item */
				$errors .= $item->getDescription() . "<br>\n";
			}

			wc_add_notice( $errors, 'error' );

			return array(
				'result'   => 'error',
				'redirect' => '',
			);
		}

		return array(
			'result'   => 'success',
			'redirect' => $page_url,
		);
	}

	/**
	 * Execute refund transaction
	 *
	 * @param \Wirecard\PaymentSdk\Transaction\Transaction $transaction
	 * @param Config                                       $config
	 * @param WC_Order                                     $order
	 *
	 * @throws Exception
	 *
	 * @return string|WP_Error
	 *
	 * @since 1.0.0
	 */
	public function execute_refund( $transaction, $config, $order ) {
		$logger              = new Logger();
		$transaction_service = new TransactionService( $config, $logger );
		try {
			if ( $transaction instanceof \Wirecard\PaymentSdk\Transaction\CreditCardTransaction ) {
				/** @var $response Response */
				$response = $transaction_service->process( $transaction, 'refund' );
			} else {
				/** @var $response Response */
				$response = $transaction_service->process( $transaction, 'cancel' );
			}
		} catch ( \Exception $exception ) {
			$logger->error( __METHOD__ . ':' . $exception->getMessage() );

			return new WP_Error( 'error', __( 'Processing refund failed.', 'woocommerce-gateway-wirecard' ) );
		}
		if ( $response instanceof SuccessResponse ) {
			$order->set_transaction_id( $response->getTransactionId() );

			return '/admin.php?page=wirecardpayment&id=' . $response->getTransactionId();
		}
		if ( $response instanceof FailureResponse ) {
			return new WP_Error( 'error', __( 'Refund via Wirecard Payment Processing Gateway failed.', 'woocommerce-gateway-wirecard' ) );
		}

		return new WP_Error( 'error', __( 'Refund via Wirecard Payment Processing Gateway failed.', 'woocommerce-gateway-wirecard' ) );
	}

	/**
	 * Create default payment method configuration
	 *
	 * @param null $base_url
	 * @param null $http_user
	 * @param null $http_pass
	 *
	 * @return Config
	 *
	 * @since 1.0.0
	 */
	public function create_payment_config( $base_url = null, $http_user = null, $http_pass = null ) {
		$config = new Config( $base_url, $http_user, $http_pass );

		return $config;
	}

	/**
	 * Save response data in order
	 *
	 * @param WC_Order $order
	 * @param Response $response
	 *
	 * @since 1.0.0
	 */
	public function save_response_data( $order, $response ) {
		$response_data = $response->getData();
		if ( ! empty( $response_data ) ) {
			/*foreach ( $response_data as $key => $value ) {
				add_post_meta( $order->get_id(), $key, $value );
			}*/
			add_post_meta( $order->get_id(), 'response_data', wp_json_encode( $response_data ) );
		}
	}

	/**
	 * Update payment
	 *
	 * @param WC_Order        $order
	 * @param SuccessResponse $response
	 *
	 * @since 1.0.0
	 */
	public function update_payment_transaction( $order, $response ) {
		$order->set_transaction_id( $response->getTransactionId() );
		//create table entry
		$transaction_factory = new Wirecard_Transaction_Factory();
		$result              = $transaction_factory->create_transaction( $order, $response, $this->get_option( 'base_url' ) );
		if ( ! $result ) {
			$logger = new WC_Logger();
			$logger->debug( __METHOD__ . 'Transaction could not be saved in transaction table' );
		}
	}

	/**
	 * Update order with specific order state
	 *
	 * @param WC_Order $order
	 * @param string   $transaction_type
	 *
	 * @return WC_Order
	 *
	 * @since 1.0.0
	 */
	public function update_order_state( $order, $transaction_type ) {
		switch ( $transaction_type ) {
			case 'capture-authorization':
			case 'debit':
			case 'purchase':
				$state = 'processing';
				break;
			case 'void-authorization':
				$state = 'cancelled';
				break;
			case 'refund-capture':
			case 'refund-debit':
			case 'refund-purchase':
				$state = 'refunded';
				break;
			case 'authorization':
			default:
				$state = 'authorization';
				break;
		}
		$order->update_status( $state, __( 'Update order status via Wirecard Payment Processing Gateway.', 'woocommerce-gateway-wirecard' ) );

		return $order;
	}

	/**
	 * Check if payment method can use capture
	 *
	 * @return boolean
	 *
	 * @since 1.0.0
	 */
	public function can_capture( $type ) {
		if ( in_array( $type, $this->capture ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if payment method can use cancel
	 *
	 * @return boolean
	 *
	 * @since 1.0.0
	 */
	public function can_cancel( $type ) {
		if ( in_array( $type, $this->cancel ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if payment method can use refund
	 *
	 * @return boolean
	 *
	 * @since 1.0.0
	 */
	public function can_refund( $type ) {
		if ( in_array( $type, $this->refund ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function can_refund_order( $order ) {
		return $order && $order->is_paid();
	}

	/**
	 * @param int    $order_id
	 * @param null   $amount
	 * @param string $reason
	 *
	 * @return bool|WP_Error
	 *
	 * @since 1.0.0
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		if ( ! $this->can_refund_order( $order ) ) {
			return new WP_Error( 'error', __( 'No online refund possible at this time.', 'woocommerce-gateway-wirecard' ) );
		}
	}

	/**
	 * Submit a form with the data from the response
	 *
	 */
	public function callback() {
		$callback = new Wirecard_Callback();
		$callback->post_form();
	}
}
