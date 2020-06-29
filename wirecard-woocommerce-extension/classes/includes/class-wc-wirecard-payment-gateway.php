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

require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/handler/class-wirecard-response-handler.php' );
require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/handler/class-wirecard-notification-handler.php' );
require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/handler/class-wirecard-callback.php' );
require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/admin/class-wirecard-transaction-factory.php' );
require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/helper/class-logger.php' );
require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/helper/class-additional-information.php' );
require_once( WIRECARD_EXTENSION_HELPER_DIR . 'class-credentials-loader.php' );

use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Redirect;
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
	const CHECK_PAYER_RESPONSE = 'check-payer-response';
	const PAYMENT_ACTIONS      = array(
		'pay'     => 'purchase',
		'reserve' => 'authorization',
	);

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
	 * Payment method type
	 *
	 * @since 1.1.0
	 * @access protected
	 * @var string
	 */
	protected $type;

	/**
	 * Specific transaction per payment method
	 *
	 * @since 1.1.0
	 * @access protected
	 * @var Wirecard\PaymentSdk\Transaction\Transaction
	 */
	protected $transaction;

	/**
	 * Initial payment action for payment method
	 *
	 * @since 1.1.0
	 * @access protected
	 * @var string
	 */
	protected $payment_action;

	/**
	 * Payment action for refund
	 *
	 * @since 1.1.0
	 * @access protected
	 * @var string
	 */
	protected $refund_action;

	/**
	 * Additional helper for basket and risk management
	 *
	 * @since  1.1.0
	 * @access protected
	 * @var Additional_Information
	 */
	protected $additional_helper;

	/**
	 * @var \Credentials\Config\DefaultConfig|\Credentials\Config\CreditCardConfig
	 *
	 * @since 3.1.1
	 */
	protected $credential_config;

	/**
	 * Payment method config
	 *
	 * @since 1.1.0
	 * @access protected
	 * @var Config
	 */
	protected $config;

	/**
	 * Fraud protection fingerprint id
	 *
	 * @since 1.5.0
	 * @access protected
	 * @var string
	 */
	protected $fps_session_id;

	/**
	 * Define config data
	 *
	 * @throws \Credentials\Exception\InvalidPaymentMethodException
	 * @since 3.1.1
	 */
	public function init_form_fields() {
		$this->credential_config = Credentials_Loader::get_instance()->get_credentials_config( $this->type );
	}

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
		add_action(
			'woocommerce_api_test_payment_method_config',
			array(
				$this,
				'test_payment_config',
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
	public function return_request( $response = null ) {
		$redirect_url = $this->get_return_url();
		if ( ! array_key_exists( 'order-id', $_REQUEST ) ) {
			header( 'Location:' . $redirect_url );
			die();
		}
		$payment_method = $_REQUEST['payment-method'];
		$order_id       = $_REQUEST['order-id'];
		$order          = new WC_Order( $order_id );

		if ( 'cancel' === $_REQUEST['payment-state'] ) {
			wc_add_notice( __( 'canceled_payment_process', 'wirecard-woocommerce-extension' ), 'notice' );
			$order->update_status( 'cancelled', __( 'order_status_gateway_update', 'wirecard-woocommerce-extension' ) );
			header( 'Location:' . $order->get_cancel_endpoint() );
			die();
		}

		$response_handler = new Wirecard_Response_Handler();
		try {
			$transaction_factory = new Wirecard_Transaction_Factory();
			$response            = $response_handler->handle_response( $_REQUEST );

			if ( ! $response instanceof Response ) {
				wc_add_notice( __( 'order_error', 'wirecard-woocommerce-extension' ), 'error' );
				$redirect_url = $order->get_cancel_endpoint();
			} else {
				if ( 'wiretransfer' === $response->getPaymentMethod() ) {
					$response_data = $response->getData();
					add_post_meta( $order->get_id(), 'pia-iban', $response_data['merchant-bank-account.0.iban'] );
					add_post_meta( $order->get_id(), 'pia-bic', $response_data['merchant-bank-account.0.bic'] );
					add_post_meta( $order->get_id(), 'pia-reference-id', $response_data['provider-transaction-reference-id'] );
				}
				if ( ! $transaction_factory->get_transaction( $response->getTransactionId() ) ) {
					$this->payment_on_hold( $order );
					$this->update_payment_transaction( $order, $response, 'awaiting', $payment_method );
				}
				$redirect_url = $this->get_return_url( $order );
			}
		} catch ( Exception $exception ) {
			wc_add_notice( __( 'order_error', 'wirecard-woocommerce-extension' ), 'error' );
			header( 'Location:' . $order->get_cancel_endpoint() );
			die();
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
			/** @var SuccessResponse $response */
			$response = $notification_handler->handle_notification( $payment_method, $notification );
			if ( $response ) {

				if ( self::CHECK_PAYER_RESPONSE === $response->getTransactionType() ) {
					return;
				}

				$this->save_response_data( $order, $response );
				$this->update_payment_transaction( $order, $response, 'success', $payment_method );
				$order = $this->update_order_state( $order, $response );
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
	 * @param string $payment_state
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function create_redirect_url( $order, $payment_state, $payment_method ) {
		return add_query_arg(
			array(
				'wc-api'         => 'WC_Wirecard_Payment_Gateway_Redirect',
				'order-id'       => $order->get_id(),
				'payment-state'  => $payment_state,
				'payment-method' => $payment_method,
			),
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);
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
	 * @param \Wirecard\PaymentSdk\Config\Config $config
	 * @param string $operation
	 * @param WC_Order $order
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function execute_transaction( $transaction, $config, $operation, $order, $request_values = null ) {
		$logger              = new Logger();
		$transaction_service = new TransactionService( $config, $logger );

		try {
			/** @var Response $response */
			$process_credit_card_response = $request_values;
			if ( isset( $process_credit_card_response ) ) {
				$redirect = $this->create_redirect_url( $order, 'success', $this->type );
				$response = $transaction_service->processJsResponse( $request_values, $redirect );
			} else {
				$response = $transaction_service->process( $transaction, $operation );
			}
		} catch ( \Exception $exception ) {
			$logger->error( __METHOD__ . ': ' . get_class( $exception ) . ': ' . $exception->getMessage() . ' - ' . $operation );

			wc_add_notice( __( 'order_error', 'wirecard-woocommerce-extension' ), 'error' );

			return array(
				'result'   => 'error',
				'redirect' => '',
			);
		}

		$page_url = '';
		if ( $response instanceof SuccessResponse ) {
			$page_url            = $this->get_return_url( $order );
			$payment_method      = $response->getPaymentMethod();
			$transaction_factory = new Wirecard_Transaction_Factory();

			if ( ! $transaction_factory->get_transaction( $response->getTransactionId() ) ) {
				$this->payment_on_hold( $order );
				$this->update_payment_transaction( $order, $response, 'awaiting', $payment_method );
			}
		}
		if ( $response instanceof InteractionResponse ) {
			$page_url = $response->getRedirectUrl();
		} elseif ( $response instanceof FormInteractionResponse ) {
			$data['url']         = $response->getUrl();
			$data['method']      = $response->getMethod();
			$data['form_fields'] = $response->getFormFields();

			WC()->session->set( 'wirecard_post_data', $data );

			$page_url = add_query_arg(
				array( 'wc-api' => 'checkout_form_submit' ),
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
	 * @param Config $config
	 * @param WC_Order $order
	 * @param string $operation
	 *
	 * @return string|WP_Error
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function execute_refund( $transaction, $config, $order, $operation = 'cancel' ) {
		$logger              = new Logger();
		$transaction_service = new TransactionService( $config, $logger );
		try {
			/** @var Response $response */
			$response = $transaction_service->process( $transaction, $operation );
		} catch ( \Exception $exception ) {
			$logger->error( __METHOD__ . ':' . $exception->getMessage() );

			return new WP_Error( 'error', __( 'refund_processing_error', 'wirecard-woocommerce-extension' ) );
		}
		if ( $response instanceof SuccessResponse ) {
			$this->update_payment_transaction( $order, $response, 'awaiting', $transaction::NAME );
			$order->set_transaction_id( $response->getTransactionId() );

			return $response;
		}
		if ( $response instanceof FailureResponse ) {
			return new WP_Error( 'error', __( 'refund_gateway_error', 'wirecard-woocommerce-extension' ) );
		}

		return new WP_Error( 'error', __( 'refund_gateway_error', 'wirecard-woocommerce-extension' ) );
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
	 * @since 2.0.0 Update shop version to include the WordPress version
	 *              Move plugin name to a global var
	 * @since 1.0.0
	 */
	public function create_payment_config( $base_url = null, $http_user = null, $http_pass = null ) {
		global $wp_version;
		$config       = new Config( $base_url, $http_user, $http_pass );
		$shop_version = sprintf( '%s+WordPress+%s', WC()->version, $wp_version );

		$config->setShopInfo( 'WooCommerce', $shop_version );
		$config->setPluginInfo(
			WIRECARD_EXTENSION_HEADER_PLUGIN_NAME,
			WIRECARD_EXTENSION_VERSION
		);

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
			add_post_meta( $order->get_id(), 'response_data', wp_json_encode( $response_data ) );
		}
	}

	/**
	 * Update payment
	 *
	 * @param WC_Order $order
	 * @param SuccessResponse $response
	 * @param string $transaction_state
	 * @param string $payment_method
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function update_payment_transaction( $order, $response, $transaction_state, $payment_method ) {
		$transaction_factory = new Wirecard_Transaction_Factory();

		// Normally you would use WooCommerce's get_option here but it seems to return values from
		// different payment methods. This loads the options based on the passed payment method name.
		$payment_method_option_name = sprintf( 'woocommerce_wirecard_ee_%s_settings', $payment_method );
		$payment_method_options     = get_option( $payment_method_option_name );

		//create table entry
		$result = $transaction_factory->create_transaction( $order, $response, $payment_method_options['base_url'], $transaction_state, $payment_method );
		if ( ! $result ) {
			$logger = new WC_Logger();
			$logger->debug( __METHOD__ . 'Transaction could not be saved in transaction table' );
		}
	}

	/**
	 * Update order with specific order state
	 *
	 * @param WC_Order $order
	 * @param SuccessResponse $response
	 *
	 * @return WC_Order
	 *
	 * @since 1.0.0
	 */
	public function update_order_state( $order, $response ) {
		$transaction_amount = $response->getData()['requested-amount'];
		switch ( $response->getTransactionType() ) {
			case 'capture-authorization':
			case 'debit':
			case 'purchase':
			case 'deposit':
				$state = 'processing';
				break;
			case 'void-authorization':
				$state = 'cancelled';
				break;
			case 'refund-capture':
			case 'refund-debit':
			case 'refund-purchase':
			case 'credit':
			case 'void-capture':
			case 'void-purchase':
				if ( ( $order->get_total() > $transaction_amount ) && ( $order->get_remaining_refund_amount() !== '0.00' ) ) {
					$state = 'processing';
				} else {
					$state = 'refunded';
				}
				break;
			case 'authorization':
			default:
				$state = 'authorization';
				break;
		}
		$order->update_status( $state, __( 'order_status_gateway_update', 'wirecard-woocommerce-extension' ) );

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
		if ( in_array( $type, $this->capture, true ) ) {
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
		if ( in_array( $type, $this->cancel, true ) ) {
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
		if ( in_array( $type, $this->refund, true ) ) {
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
	 * Process payment gateway transactions
	 *
	 * @param int $order_id
	 *
	 * @since 1.1.0
	 */
	public function process_payment( $order_id ) {
		$order         = wc_get_order( $order_id );
		$redirect_urls = new Redirect(
			$this->create_redirect_url( $order, 'success', $this->type ),
			$this->create_redirect_url( $order, 'cancel', $this->type ),
			$this->create_redirect_url( $order, 'failure', $this->type )
		);

		$this->config = $this->create_payment_config();

		$formatted_total = number_format( $order->get_total(), wc_get_price_decimals(), '.', '' );
		$amount          = new Amount( floatval( $formatted_total ), $order->get_currency() );

		$this->transaction->setNotificationUrl( $this->create_notification_url( $order, $this->type ) );
		$this->transaction->setRedirect( $redirect_urls );
		$this->transaction->setAmount( $amount );

		$custom_fields = new CustomFieldCollection();
		$custom_fields->add( new CustomField( 'orderId', $order_id ) );
		$custom_fields->add( new CustomField( 'multisite', is_multisite() ? 'multisite' : '' ) );
		$custom_fields->add( new CustomField( 'phpVersion', phpversion() ) );
		$this->transaction->setCustomFields( $custom_fields );
		$this->transaction->setOrderNumber( $order->get_order_number() );
		if ( $this->get_option( 'descriptor' ) === 'yes' ) {
			$this->transaction->setDescriptor( $this->additional_helper->create_descriptor( $order ) );
		}

		if ( $this->get_option( 'send_additional' ) === 'yes' ) {
			$this->transaction = $this->additional_helper->set_additional_information( $order, $this->transaction );
		}
	}

	/**
	 * @param int $order_id
	 * @param float|null $amount
	 * @param string $reason
	 *
	 * @return bool|WP_Error
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		if ( ! $this->can_refund_order( $order ) ) {
			return new WP_Error( 'error', __( 'refund_online_error', 'wirecard-woocommerce-extension' ) );
		}
		$this->config = $this->create_payment_config();
		$this->transaction->setParentTransactionId( $order->get_transaction_id() );
		if ( ! is_null( $amount ) ) {
			$this->transaction->setAmount( new Amount( floatval( $amount ), $order->get_currency() ) );
		}

		return $this->execute_refund( $this->transaction, $this->config, $order, $this->refund_action );
	}

	/**
	 * Submit a form with the data from the response
	 *
	 */
	public function callback() {
		$callback = new Wirecard_Callback();
		$callback->post_form();
	}

	/**
	 * Return true if the payment method is available
	 *
	 * @return bool
	 * @since 1.1.0
	 */
	public function is_available() {
		if ( $this->get_option( 'enabled' ) === 'yes' ) {
			return true;
		}

		return false;
	}

	/**
	 * Test payment configuration
	 *
	 * @since 1.1.0
	 */
	public function test_payment_config() {
		if ( wp_verify_nonce( $_POST['admin_nonce'] ) ) {
			$base_url  = $_POST['base_url'];
			$http_user = $_POST['http_user'];
			$http_pass = $_POST['http_pass'];

			$test_config         = new Config( wp_unslash( $base_url ), wp_unslash( $http_user ), wp_unslash( $http_pass ) );
			$transaction_service = new TransactionService( $test_config, new Logger() );

			if ( $transaction_service->checkCredentials() ) {
				wp_send_json_success( __( 'success_credentials', 'wirecard-woocommerce-extension' ) );
			} else {
				wp_send_json_error( __( 'error_credentials', 'wirecard-woocommerce-extension' ) );
			}
		}
		die();
	}

	/**
	 * Get current WordPress version and WooCommerce version
	 *
	 * @return string
	 *
	 * @since 1.1.0
	 */
	private function get_shop_version() {
		global $wp_version;

		$shop         = 'WordPress ';
		$shop        .= 'v' . $wp_version;
		$woocommerce  = ' WooCommerce ';
		$woocommerce .= 'v' . WC()->version;

		return $shop . $woocommerce;
	}

	/**
	 * Update order status for awaiting payments
	 *
	 * @param WC_Order $order
	 *
	 * @since 1.3.0
	 */
	private function payment_on_hold( $order ) {
		if ( ! $order->is_paid() && ( 'authorization' !== $order->get_status() ) && ( 'processing' !== $order->get_status() ) ) {
			$order->update_status( 'on-hold', __( 'payment_awaiting', 'wirecard-woocommerce-extension' ) );
		}
	}

	/**
	 * Generates the fraud protection fingerprint id
	 *
	 * @param string $maid_option_name the key to access the merchant account id, 'merchant_account_id' in most payment methods
	 *
	 * @return string the generated fingerprint id for this session
	 * @since 1.5.0
	 */
	public function generate_fps_session_id( $maid_option_name ) {
		$maid   = $this->get_option( $maid_option_name );
		$random = md5( uniqId() . '_' . microtime() );

		return $maid . '_' . $random;
	}

	/**
	 * Return payment method type
	 *
	 * @return string
	 *
	 * @since 3.3.0
	 */
	public function get_type() {
		return $this->type;
	}
}
