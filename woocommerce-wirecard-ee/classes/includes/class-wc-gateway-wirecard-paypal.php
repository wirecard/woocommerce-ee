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

require_once __DIR__ . '/class-wc-wirecard-payment-gateway.php';

use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use Wirecard\PaymentSdk\TransactionService;

/**
 * Class WC_Gateway_Wirecard_Paypal
 *
 * @extends WC_Wirecard_Payment_Gateway
 *
 * @since   1.0.0
 */
class WC_Gateway_Wirecard_Paypal extends WC_Wirecard_Payment_Gateway {

	protected $_logger;

	public function __construct() {
		$this->id                 = 'woocommerce_wirecard_paypal';
		$this->icon               = WOOCOMMERCE_GATEWAY_WIRECARD_URL . 'assets/images/paypal.png';
		$this->method_title       = __( 'Wirecard Payment Processing Gateway PayPal', 'wooocommerce-gateway-wirecard' );
		$this->method_description = __( 'PayPal transactions via Wirecard Payment Processing Gateway', 'woocommerce-gateway-wirecard' );
		$this->_logger            = new WC_Logger();

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		$this->title   = $this->get_option( 'title' );
		$this->enabled = $this->get_option( 'enabled' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Load form fields for configuration
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'             => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Wirecard Payment Processing Gateway PayPal', 'woocommerce-gateway-wirecard' ),
				'default' => 'yes'
			),
			'title'               => array(
				'title'       => __( 'Title', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-wirecard' ),
				'default'     => __( 'Wirecard Payment Processing Gateway PayPal', 'woocommerce-gateway-wirecard' ),
				'desc_tip'    => true,
			),
			'description'         => array(
				'title'   => __( 'Customer Message', 'woocommerce-gateway-wirecard' ),
				'type'    => 'textarea',
				'default' => ''
			),
			'base_url'            => array(
				'title'       => __( 'Base Url', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'The elastic engine base url. (e.g. https://api.wirecard.com)' ),
				'default'     => 'https://api-test.wirecard.com',
				'desc_tip'    => true
			),
			'http_user'           => array(
				'title'   => __( 'Http User', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => '70000-APITEST-AP'
			),
			'http_pass'           => array(
				'title'   => __( 'Http Password', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => 'qD2wzQ_hrc!8'
			),
			'merchant_account_id' => array(
				'title'   => __( 'Merchant Account ID', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => '2a0e9351-24ed-4110-9a1b-fd0fee6bec26'
			),
			'secret'              => array(
				'title'   => __( 'Secret Key', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => 'dbc5a498-9a66-43b9-bf1d-a618dd399684'
			),
			'payment_action'      => array(
				'title'   => __( 'Payment Action', 'woocommerce-gateway-wirecard' ),
				'type'    => 'select',
				'default' => 'Authorization',
				'label'   => __( 'Payment Action', 'woocommerce-gateway-wirecard' ),
				'options' => array(
					'authorization' => 'Authorization',
					'capture'       => 'Capture'
				)
			),
			'shopping_basket'     => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'    => 'checkbox',
				'label'   => __( 'Shopping Basket', 'woocommerce-gateway-wirecard' ),
				'default' => 'no'
			),
			'descriptor'          => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'    => 'checkbox',
				'label'   => __( 'Descriptor', 'woocommerce-gateway-wirecard' ),
				'default' => 'no'
			),
			'send_additional'     => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'    => 'checkbox',
				'label'   => __( 'Send additional information', 'woocommerce-gateway-wirecard' ),
				'default' => 'yes'
			)
		);
	}

	public function process_payment( $order_id ) {
		global $woocommerce;

		$order = wc_get_order( $order_id );

		$redirect_urls     = new Redirect( $this->create_return_url( $order, 'SUCCESS' ), $this->create_return_url( $order, 'CANCEL' ) );
		$page_url         = $order->get_checkout_payment_url( true );
		$page_url         = add_query_arg( 'key', $order->get_order_key(), $page_url );
		$notification_url = add_query_arg( 'order-pay', $order_id, $page_url );


		$transaction = new PayPalTransaction();
		$transaction->setNotificationUrl( $notification_url );
		$transaction->setRedirect( $redirect_urls );
		$amount = new Amount( $order->get_total(), 'EUR' );
		$transaction->setAmount( $amount );
		$config = $this->create_payment_config();

		$transaction_service = new TransactionService( $config );
		try {
			$response = $transaction_service->process( $transaction, 'reserve' );
			$this->_logger->error( print_r( $response, true ) );
		}
		catch ( \Exception $exception ) {
			$this->_logger->error( print_r( $exception, true ) );
		}

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order )
		);

	}

	/**
	 * Create payment method configuration
	 *
	 * @return Config
	 *
	 * @since 1.0.0
	 */
	public function create_payment_config() {
		$base_url      = $this->get_option( 'base_url' );
		$http_user     = $this->get_option( 'http_user' );
		$http_password = $this->get_option( 'http_pass' );

		$config         = new Config( $base_url, $http_user, $http_password, 'EUR' );
		$payment_config = new PaymentMethodConfig( PayPalTransaction::NAME, $this->get_option( 'merchant_account_id' ), $this->get_option( 'secret' ) );
		$config->add( $payment_config );

		return $config;
	}

	public function create_return_url( $order, $payment_state ) {
		$return_url = add_query_arg(
			array(
				'wc-api'       => 'WC_Wirecard_Payment_Gateway_Return',
				'order-id'     => $order->get_id(),
				'paymentState' => $payment_state
			),
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);

		return $return_url;
	}
}
