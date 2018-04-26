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

require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/includes/class-wc-wirecard-payment-gateway.php' );
require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-sepa.php' );

use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Transaction\SofortTransaction;
use Wirecard\PaymentSdk\Transaction\SepaTransaction;

/**
 * Class WC_Gateway_Wirecard_Sofort
 *
 * @extends WC_Wirecard_Payment_Gateway
 *
 * @since   1.1.0
 */
class WC_Gateway_Wirecard_Sofort extends WC_Wirecard_Payment_Gateway {

	/**
	 * WC_Gateway_Wirecard_Sofort constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->type               = 'sofortbanking';
		$this->id                 = 'wirecard_ee_sofortbanking';
		$this->icon               = WOOCOMMERCE_GATEWAY_WIRECARD_URL . 'assets/images/sofortbanking.png';
		$this->method_title       = __( 'Wirecard Sofort.', 'wooocommerce-gateway-wirecard' );
		$this->method_name        = __( 'Sofort.', 'wooocommerce-gateway-wirecard' );
		$this->method_description = __( 'Sofort. transactions via Wirecard Payment Processing Gateway', 'woocommerce-gateway-wirecard' );

		$this->supports = array(
			'products',
			'refunds',
		);

		$this->refund         = array( 'debit' );
		$this->payment_action = 'pay';
		$this->refund_action  = 'credit';

		$this->init_form_fields();
		$this->init_settings();

		$this->title   = $this->get_option( 'title' );
		$this->enabled = $this->get_option( 'enabled' );

		$this->additional_helper = new Additional_Information();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		parent::add_payment_gateway_actions();
	}

	/**
	 * Load form fields for configuration
	 *
	 * @since 1.1.0
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'             => array(
				'title'       => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'        => 'checkbox',
				'description' => __( 'Activate payment method Sofort.', 'woocommerce-gateway-wirecard' ),
				'label'       => __( 'Enable Wirecard Sofort.', 'woocommerce-gateway-wirecard' ),
				'default'     => 'no',
			),
			'title'               => array(
				'title'       => __( 'Title', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the consumer sees during checkout.', 'woocommerce-gateway-wirecard' ),
				'default'     => __( 'Wirecard Sofort.', 'woocommerce-gateway-wirecard' ),
			),
			'merchant_account_id' => array(
				'title'       => __( 'Merchant Account ID', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'The unique identifier assigned for your Merchant Account.', 'woocommerce-gateway-wirecard' ),
				'default'     => 'c021a23a-49a5-4987-aa39-e8e858d29bad',
			),
			'secret'              => array(
				'title'       => __( 'Secret Key', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'Secret key is mandatory to calculate the Digital Signature for the payment.', 'woocommerce-gateway-wirecard' ),
				'default'     => 'dbc5a498-9a66-43b9-bf1d-a618dd399684',
			),
			'credentials'         => array(
				'title'       => __( 'Credentials', 'woocommerce-gateway-wirecard' ),
				'type'        => 'title',
				'description' => __( 'Enter your Wirecard credentials.', 'woocommerce-gateway-wirecard' ),
			),
			'base_url'            => array(
				'title'       => __( 'Base URL', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'The Wirecard base URL. (e.g. https://api.wirecard.com)', 'woocommerce-gateway-wirecard' ),
				'default'     => 'https://api-test.wirecard.com',
			),
			'http_user'           => array(
				'title'       => __( 'HTTP User', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'The http user provided in your Wirecard contract', 'woocommerce-gateway-wirecard' ),
				'default'     => '70000-APITEST-AP',
			),
			'http_pass'           => array(
				'title'       => __( 'HTTP Password', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'The http password provided in your Wirecard contract', 'woocommerce-gateway-wirecard' ),
				'default'     => 'qD2wzQ_hrc!8',
			),
			'test_button'         => array(
				'title'   => __( 'Test configuration', 'woocommerce-gateway-wirecard' ),
				'type'    => 'button',
				'class'   => 'wc_wirecard_test_credentials_button button-primary',
				'default' => __( 'Test', 'woocommerce-gateway-wirecard' ),
			),
			'advanced'            => array(
				'title'       => __( 'Advanced Options', 'woocommerce-gateway-wirecard' ),
				'type'        => 'title',
				'description' => '',
			),
			'send_additional'     => array(
				'title'       => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'        => 'checkbox',

				'description' => __( 'Additional data will be sent for the purpose of fraud protection. This additional data includes billing / shipping address, shopping basket and descriptor.', 'woocommerce-gateway-wirecard' ),
				'label'       => __( 'Send additional information', 'woocommerce-gateway-wirecard' ),
				'default'     => 'yes',
			),
		);
	}

	/**
	 * Process payment gateway transactions
	 *
	 * @param int $order_id
	 *
	 * @return array
	 *
	 * @since 1.1.0
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		$this->transaction = new SofortTransaction();
		parent::process_payment( $order_id );
		$this->transaction->setDescriptor( $this->additional_helper->create_descriptor( $order ) );

		return $this->execute_transaction( $this->transaction, $this->config, $this->payment_action, $order );
	}

	/**
	 * Create transaction for refund
	 *
	 * @param int        $order_id
	 * @param float|null $amount
	 * @param string     $reason
	 *
	 * @return bool|SepaTransaction|WP_Error
	 *
	 * @since 1.1.0
	 * @throws Exception
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$sepa_payment = new WC_Gateway_Wirecard_Sepa();

		return $sepa_payment->process_refund( $order_id, $amount, $reason );
	}

	/**
	 * Create payment method configuration
	 *
	 * @param null $base_url
	 * @param null $http_user
	 * @param null $http_pass
	 * @since 1.1.0
	 * @return Config
	 */
	public function create_payment_config( $base_url = null, $http_user = null, $http_pass = null ) {
		if ( is_null( $base_url ) ) {
			$base_url  = $this->get_option( 'base_url' );
			$http_user = $this->get_option( 'http_user' );
			$http_pass = $this->get_option( 'http_pass' );
		}

		$config         = parent::create_payment_config( $base_url, $http_user, $http_pass );
		$payment_config = new PaymentMethodConfig( SofortTransaction::NAME, $this->get_option( 'merchant_account_id' ), $this->get_option( 'secret' ) );
		$config->add( $payment_config );

		return $config;
	}
}
