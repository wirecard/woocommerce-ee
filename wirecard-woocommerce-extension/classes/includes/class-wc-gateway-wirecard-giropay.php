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

require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-wirecard-payment-gateway.php' );
require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-sepa-credit-transfer.php' );

use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\BankAccount;
use Wirecard\PaymentSdk\Entity\Device;
use Wirecard\PaymentSdk\Transaction\GiropayTransaction;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;

/**
 * Class WC_Gateway_Wirecard_Giropay
 *
 * @extends WC_Wirecard_Payment_Gateway
 *
 * @since 2.2.0
 */
class WC_Gateway_Wirecard_Giropay extends WC_Wirecard_Payment_Gateway {


	/**
	 * WC_Gateway_Wirecard_Giropay constructor.
	 *
	 * @since 2.2.0
	 */
	public function __construct() {
		$this->type               = 'giropay';
		$this->id                 = 'wirecard_ee_giropay';
		$this->icon               = WIRECARD_EXTENSION_URL . 'assets/images/giropay.png';
		$this->method_title       = __( 'heading_title_giropay', 'wirecard-woocommerce-extension' );
		$this->method_name        = __( 'giropay', 'wirecard-woocommerce-extension' );
		$this->method_description = __( 'giropay_desc', 'wirecard-woocommerce-extension' );
		$this->has_fields         = true;
		$this->supports           = array( 'products' );
		$this->payment_action     = 'pay';

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
	 * @since 2.2.0
	 */
	public function init_form_fields() {
		parent::init_form_fields();
		$this->form_fields = array(
			'enabled'             => array(
				'title'       => __( 'text_enable_disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'description' => __( 'config_status_desc_giropay', 'wirecard-woocommerce-extension' ),
				'label'       => __( 'enable_heading_title_giropay', 'wirecard-woocommerce-extension' ),
				'default'     => 'no',
			),
			'title'               => array(
				'title'       => __( 'config_title', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_title_desc', 'wirecard-woocommerce-extension' ),
				'default'     => __( 'heading_title_giropay', 'wirecard-woocommerce-extension' ),
			),
			'merchant_account_id' => array(
				'title'       => __( 'config_merchant_account_id', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_merchant_account_id_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getMerchantAccountId(),
			),
			'secret'              => array(
				'title'       => __( 'config_merchant_secret', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_merchant_secret_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getSecret(),
			),
			'credentials'         => array(
				'title'       => __( 'text_credentials', 'wirecard-woocommerce-extension' ),
				'type'        => 'title',
				'description' => __( 'text_credentials_desc', 'wirecard-woocommerce-extension' ),
			),
			'base_url'            => array(
				'title'       => __( 'config_base_url', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_base_url_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getBaseUrl(),
			),
			'http_user'           => array(
				'title'       => __( 'config_http_user', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_http_user_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getHttpUser(),
			),
			'http_pass'           => array(
				'title'       => __( 'config_http_password', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_http_password_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getHttpPassword(),
			),
			'test_button'         => array(
				'title'   => __( 'test_config', 'wirecard-woocommerce-extension' ),
				'type'    => 'button',
				'class'   => 'wc_wirecard_test_credentials_button button-primary',
				'default' => __( 'test_credentials', 'wirecard-woocommerce-extension' ),
			),
			'advanced'            => array(
				'title'       => __( 'text_advanced', 'wirecard-woocommerce-extension' ),
				'type'        => 'title',
				'description' => '',
			),
			'descriptor'          => array(
				'title'       => __( 'text_enable_disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'description' => __( 'config_descriptor_desc', 'wirecard-woocommerce-extension' ),
				'label'       => __( 'config_descriptor', 'wirecard-woocommerce-extension' ),
				'default'     => 'no',
			),
			'send_additional'     => array(
				'title'       => __( 'text_enable_disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'description' => __( 'config_additional_info_desc', 'wirecard-woocommerce-extension' ),
				'label'       => __( 'config_additional_info', 'wirecard-woocommerce-extension' ),
				'default'     => 'yes',
			),
		);
	}

	/**
	 * Add payment fields to payment method
	 *
	 * @return bool
	 *
	 * @since 2.2.0
	 */
	public function payment_fields() {
		$html  = '';
		$html .= '<input type="hidden" name="giropay_nonce" value="' . wp_create_nonce() . '" />
			<p class="form-row form-row-wide">
				<label for="giropay_bic">' . __( 'bic', 'wirecard-woocommerce-extension' ) . '</label>
				<input id="giropay_bic" class="input-text" type="text" name="giropay_bank_bic">
			</p>';

		echo $html;
		return true;
	}

	/**
	 * Process payment gateway transactions
	 *
	 * @param int $order_id
	 *
	 * @return array|bool
	 *
	 * @since 2.2.0
	 */
	public function process_payment( $order_id ) {
		if ( ! wp_verify_nonce( $_POST['giropay_nonce'] ) ) {
			return false;
		}
		/** @var WC_Order $order */
		$order = wc_get_order( $order_id );

		$this->transaction = new GiropayTransaction();
		parent::process_payment( $order_id );
		if ( isset( $_POST['giropay_bank_bic'] ) && strlen( $_POST['giropay_bank_bic'] ) ) {
			$bank_account = new BankAccount();
			$bank_account->setBic( sanitize_text_field( $_POST['giropay_bank_bic'] ) );
			$this->transaction->setBankAccount( $bank_account );
		}

		return $this->execute_transaction( $this->transaction, $this->config, $this->payment_action, $order );
	}

	/**
	 * Create payment method configuration
	 *
	 * @param string|null $base_url
	 * @param string|null $http_user
	 * @param string|null $http_pass
	 *
	 * @return Config
	 *
	 * @since 2.2.0
	 */
	public function create_payment_config( $base_url = null, $http_user = null, $http_pass = null ) {
		if ( is_null( $base_url ) ) {
			$base_url  = $this->get_option( 'base_url' );
			$http_user = $this->get_option( 'http_user' );
			$http_pass = $this->get_option( 'http_pass' );
		}

		$config         = parent::create_payment_config( $base_url, $http_user, $http_pass );
		$payment_config = new PaymentMethodConfig( GiropayTransaction::NAME, $this->get_option( 'merchant_account_id' ), $this->get_option( 'secret' ) );
		$config->add( $payment_config );

		return $config;
	}

	/**
	 * @param WC_Order $order
	 * @return bool
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 * @since 3.0.0
	 */
	public function can_refund_order( $order ) {
		return false;
	}
}
