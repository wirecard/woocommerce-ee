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
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\Device;
use Wirecard\PaymentSdk\Transaction\PayByBankAppTransaction;
use Wirecard\PaymentSdk\TransactionService;

/**
 * Class WC_Gateway_Wirecard_Pay_By_Bank_App
 *
 * @extends WC_Wirecard_Payment_Gateway
 *
 * @since   1.1.0
 */
class WC_Gateway_Wirecard_Pay_By_Bank_App extends WC_Wirecard_Payment_Gateway {

	/**
	 * WC_Gateway_Wirecard_Pay_By_Bank_App constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->type               = 'zapp';
		$this->id                 = 'wirecard_ee_pbba';
		$this->icon               = WIRECARD_EXTENSION_URL . 'assets/images/pbba.png';
		$this->method_title       = __( 'Wirecard Pay By Bank app', 'wirecard-woocommerce-extension' );
		$this->method_name        = __( 'Pay By Bank app', 'wirecard-woocommerce-extension' );
		$this->method_description = __( 'Pay By Bank app transactions via Wirecard Payment Processing Gateway', 'wirecard-woocommerce-extension' );

		$this->supports = array(
			'products',
			'refunds',
		);

		$this->cancel        = array( 'authorization' );
		$this->capture       = array( 'authorization' );
		$this->refund        = array( 'purchase', 'capture-authorization' );
		$this->refund_action = 'cancel';

		$this->init_form_fields();
		$this->init_settings();

		$this->title   = $this->get_option( 'title' );
		$this->enabled = $this->get_option( 'enabled' );

		$this->additional_helper = new Additional_Information();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_api_get_upi_request_data', array( $this, 'get_request_data_upi' ) );

		parent::add_payment_gateway_actions();
	}

	/**
	 * Load form fields for configuration
	 *
	 * @since 1.1.0
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'                => array(
				'title'       => __( 'text_enable_disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'description' => __( 'enable_heading_title_pbba', 'wirecard-woocommerce-extension' ),
				'label'       => __( 'config_status_desc_pbba', 'wirecard-woocommerce-extension' ),
				'default'     => 'no',
			),
			'title'                  => array(
				'title'       => __( 'config_title', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_title_desc', 'wirecard-woocommerce-extension' ),
				'default'     => __( 'heading_title_pbba', 'wirecard-woocommerce-extension' ),
			),
			'merchant_account_id'    => array(
				'title'       => __( 'config_merchant_account_id', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_merchant_account_id_desc', 'wirecard-woocommerce-extension' ),
				'default'     => '70055b24-38f1-4500-a3a8-afac4b1e3249',
			),
			'secret'                 => array(
				'title'       => __( 'config_merchant_secret', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_merchant_secret_desc', 'wirecard-woocommerce-extension' ),
				'default'     => '4a4396df-f78c-44b9-b8a0-b72b108ac465',
			),
			'credentials'            => array(
				'title'       => __( 'text_credentials', 'wirecard-woocommerce-extension' ),
				'type'        => 'title',
				'description' => __( 'text_credentials_desc', 'wirecard-woocommerce-extension' ),
			),
			'base_url'               => array(
				'title'       => __( 'config_base_url', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_base_url_desc', 'woocomerce-gateway-wirecard' ),
				'default'     => 'https://api-test.wirecard.com',
			),
			'http_user'              => array(
				'title'       => __( 'config_http_user', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'The http user provided in your Wirecard contract', 'wirecard-woocommerce-extension' ),
				'default'     => '70000-APITEST-AP',
			),
			'http_pass'              => array(
				'title'       => __( 'config_http_password', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_http_password_desc', 'wirecard-woocommerce-extension' ),
				'default'     => 'qD2wzQ_hrc!8',
			),
			'test_button'            => array(
				'title'   => __( 'test_config', 'wirecard-woocommerce-extension' ),
				'type'    => 'button',
				'class'   => 'wc_wirecard_test_credentials_button button-primary',
				'default' => __( 'test_credentials', 'wirecard-woocommerce-extension' ),
			),
			'advanced'               => array(
				'title'       => __( 'text_advanced', 'wirecard-woocommerce-extension' ),
				'type'        => 'title',
				'description' => '',
			),
			'merchant_return_string' => array(
				'title'       => __( 'Merchant return string', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'Merchant Return String to redirect the Consumer from the Mobile Banking App to the Merchant’s browser or App.', 'wirecard-woocommerce-extension' ),
				'default'     => 'PayByBankApp',
			),
		);
	}

	/**
	 *
	 * @param string $key
	 * @param string value
	 *
	 * @return CustomField
	 *
	 */
	function createCustomField( $key, $value ) {
		$custom_field = new CustomField( $key, $value );
		$custom_field->setPrefix( '' );
		return $custom_field;
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

		$this->payment_action = 'pay';

		$this->transaction = new PayByBankAppTransaction();

		$device = new Device();
		$device->setType( 'pc' );
		$device->setOperatingSystem( 'windows' );
		$this->transaction->setDevice( $device );

		parent::process_payment( $order_id );

		$custom_fields          = $this->transaction->getCustomFields();
		$merchant_return_string = $this->get_option( 'merchant_return_string' );
		if ( ! isset( $merchant_return_string ) || trim( $merchant_return_string ) === '' ) {
			$merchant_return_string = 'pbba';
		}
		$custom_fields->add( $this->createCustomField( 'zapp.in.MerchantRtnStrng', $merchant_return_string ) );
		$custom_fields->add( $this->createCustomField( 'zapp.in.TxType', 'PAYMT' ) );
		$custom_fields->add( $this->createCustomField( 'zapp.in.DeliveryType', 'DELTAD' ) );

		return $this->execute_transaction( $this->transaction, $this->config, $this->payment_action, $order );
	}

	/**
	 * Create transaction for cancel
	 *
	 * @param int        $order_id
	 * @param float|null $amount
	 *
	 * @return UpiTransaction
	 *
	 * @since 1.1.0
	 */
	public function process_cancel( $order_id, $amount = null ) {
		$order = wc_get_order( $order_id );

		$transaction = new PayByBankAppTransaction();
		$transaction->setParentTransactionId( $order->get_transaction_id() );
		if ( ! is_null( $amount ) ) {
			$transaction->setAmount( new Amount( $amount, $order->get_currency() ) );
		}

		return $transaction;
	}

	/**
	 * Create transaction for refund
	 *
	 * @param int        $order_id
	 * @param float|null $amount
	 * @param string     $reason
	 *
	 * @return bool|UpiTransaction|WP_Error
	 *
	 * @since 1.1.0
	 * @throws Exception
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$this->transaction = new PayByBankAppTransaction();

		$this->transaction->setRefundReasonType( 'LATECONFIRMATION' );
		$this->transaction->setRefundMethod( 'BACS' );

		return parent::process_refund( $order_id, $amount, '' );
	}

	/**
	 * Create payment method Configuration
	 *
	 * @return Config
	 *
	 * @since 1.1.0
	 */
	public function create_payment_config( $base_url = null, $http_user = null, $http_pass = null ) {
		if ( is_null( $base_url ) ) {
			$base_url  = $this->get_option( 'base_url' );
			$http_user = $this->get_option( 'http_user' );
			$http_pass = $this->get_option( 'http_pass' );
		}

		$config         = parent::create_payment_config( $base_url, $http_user, $http_pass );
		$payment_config = new PaymentMethodConfig(
			PayByBankAppTransaction::NAME,
			$this->get_option( 'merchant_account_id' ),
			$this->get_option( 'secret' )
		);

		$config->add( $payment_config );

		return $config;
	}

	/**
	 * Submit a form with the data from the response
	 *
	 * @since 1.1.0
	 */
	public function callback() {
		$callback = new Wirecard_Callback();
		$callback->post_upi_form();
	}
}
