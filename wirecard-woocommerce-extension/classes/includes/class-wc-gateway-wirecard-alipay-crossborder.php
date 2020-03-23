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

require_once( __DIR__ . '/class-wc-wirecard-payment-gateway.php' );

use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\AlipayCrossborderTransaction;

/**
 * Class WC_Gateway_Wirecard_Alipay_Crossborder
 *
 * @extends WC_Wirecard_Payment_Gateway
 *
 * @since   1.1.0
 */
class WC_Gateway_Wirecard_Alipay_Crossborder extends WC_Wirecard_Payment_Gateway {

	/**
	 * WC_Gateway_Wirecard_Alipay_Crossborder constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->type               = 'alipay-xborder';
		$this->id                 = 'wirecard_ee_alipay-xborder';
		$this->icon               = WIRECARD_EXTENSION_URL . 'assets/images/alipay-xborder.png';
		$this->method_title       = __( 'heading_title_alipay_crossborder', 'wirecard-woocommerce-extension' );
		$this->method_name        = __( 'alipay_crossborder', 'wirecard-woocommerce-extension' );
		$this->method_description = __( 'alipay_crossborder_desc', 'wirecard-woocommerce-extension' );

		$this->supports = array(
			'products',
			'refunds',
		);

		$this->refund         = array( 'debit' );
		$this->payment_action = 'pay';
		$this->refund_action  = 'cancel';

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
		parent::init_form_fields();
		$this->form_fields = array(
			'enabled'             => array(
				'title'       => __( 'text_enable_disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'description' => __( 'config_status_desc_alipay_crossborder', 'wirecard-woocommerce-extension' ),
				'label'       => __( 'enable_heading_title_alipay_crossborder', 'wirecard-woocommerce-extension' ),
				'default'     => 'no',
			),
			'title'               => array(
				'title'       => __( 'config_title', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_title_desc', 'wirecard-woocommerce-extension' ),
				'default'     => __( 'heading_title_alipay_crossborder', 'wirecard-woocommerce-extension' ),
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
	 * Create payment method configuration
	 *
	 * @param null $base_url
	 * @param null $http_user
	 * @param null $http_pass
	 *
	 * @return Config
	 */
	public function create_payment_config( $base_url = null, $http_user = null, $http_pass = null ) {
		if ( is_null( $base_url ) ) {
			$base_url  = $this->get_option( 'base_url' );
			$http_user = $this->get_option( 'http_user' );
			$http_pass = $this->get_option( 'http_pass' );
		}

		$config         = parent::create_payment_config( $base_url, $http_user, $http_pass );
		$payment_config = new PaymentMethodConfig( AlipayCrossborderTransaction::NAME, $this->get_option( 'merchant_account_id' ), $this->get_option( 'secret' ) );
		$config->add( $payment_config );

		return $config;
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

		$this->transaction = new AlipayCrossborderTransaction();
		parent::process_payment( $order_id );
		$this->transaction->setAccountHolder( $this->additional_helper->create_account_holder( $order, 'billing' ) );

		return $this->execute_transaction( $this->transaction, $this->config, $this->payment_action, $order );
	}

	/**
	 * Create transaction for refund
	 *
	 * @param int        $order_id
	 * @param float|null $amount
	 * @param string     $reason
	 *
	 * @return bool|AlipayCrossborderTransaction|WP_Error
	 *
	 * @since 1.1.0
	 * @throws Exception
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$this->transaction = new AlipayCrossborderTransaction();

		return parent::process_refund( $order_id, $amount, '' );
	}
}
