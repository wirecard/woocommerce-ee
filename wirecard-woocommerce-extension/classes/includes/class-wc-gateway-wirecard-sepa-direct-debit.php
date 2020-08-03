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

use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\SepaConfig;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Mandate;
use Wirecard\PaymentSdk\Transaction\SepaDirectDebitTransaction;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;

/**
 * Class WC_Gateway_Wirecard_Sepa_Direct_Debit
 *
 * @extends WC_Wirecard_Payment_Gateway
 *
 * @since   1.0.0
 */
class WC_Gateway_Wirecard_Sepa_Direct_Debit extends WC_Wirecard_Payment_Gateway {

	public function __construct() {
		$this->type               = 'sepadirectdebit';
		$this->id                 = 'wirecard_ee_sepadirectdebit';
		$this->icon               = WIRECARD_EXTENSION_URL . 'assets/images/sepa.png';
		$this->method_title       = __( 'heading_title_sepadd', 'wirecard-woocommerce-extension' );
		$this->method_name        = __( 'sepadd', 'wirecard-woocommerce-extension' );
		$this->method_description = __( 'sepadd_desc', 'wirecard-woocommerce-extension' );
		$this->has_fields         = true;

		$this->supports = array(
			'products',
			'refunds',
		);

		$this->cancel        = array( 'pending-debit' );
		$this->capture       = array( 'authorization' );
		$this->refund        = array( 'debit' );
		$this->refund_action = 'credit';

		$this->init_form_fields();
		$this->init_settings();

		$this->title   = $this->get_option( 'title' );
		$this->enabled = $this->get_option( 'enabled' );

		$this->additional_helper = new Additional_Information();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_api_get_sepa_mandate', array( $this, 'sepa_mandate' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ), 999 );

		parent::add_payment_gateway_actions();
	}

	/**
	 * Load form fields for configuration
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields() {
		parent::init_form_fields();
		$this->form_fields = array(
			'enabled'                => array(
				'title'       => __( 'text_enable_disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'description' => __( 'config_status_desc_sepadd', 'wirecard-woocommerce-extension' ),
				'label'       => __( 'enable_heading_title_sepadd', 'wirecard-woocommerce-extension' ),
				'default'     => 'no',
			),
			'title'                  => array(
				'title'       => __( 'config_title', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_title_desc', 'wirecard-woocommerce-extension' ),
				'default'     => __( 'heading_title_sepadd', 'wirecard-woocommerce-extension' ),
			),
			'merchant_account_id'    => array(
				'title'       => __( 'config_merchant_account_id', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_merchant_account_id_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getMerchantAccountId(),
			),
			'secret'                 => array(
				'title'       => __( 'config_merchant_secret', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_merchant_secret_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getSecret(),
			),
			'credentials'            => array(
				'title'       => __( 'text_credentials', 'wirecard-woocommerce-extension' ),
				'type'        => 'title',
				'description' => __( 'text_credentials_desc', 'wirecard-woocommerce-extension' ),
			),
			'base_url'               => array(
				'title'       => __( 'config_base_url', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_base_url_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getBaseUrl(),
			),
			'http_user'              => array(
				'title'       => __( 'config_http_user', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_http_user_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getHttpUser(),
			),
			'http_pass'              => array(
				'title'       => __( 'config_http_password', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_http_password_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getHttpPassword(),
			),
			'test_button'            => array(
				'title'   => __( 'test_config', 'wirecard-woocommerce-extension' ),
				'type'    => 'button',
				'class'   => 'wc_wirecard_test_credentials_button button-primary',
				'default' => __( 'test_credentials', 'wirecard-woocommerce-extension' ),
			),
			'sepa_credentials'       => array(
				'title'       => __( 'text_sepa_config', 'wirecard-woocommerce-extension' ),
				'type'        => 'title',
				'description' => __( 'text_sepadd_config_desc', 'wirecard-woocommerce-extension' ),
			),
			'creditor_id'            => array(
				'title'       => __( 'config_creditor_id', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_creditor_id_desc', 'wirecard-woocommerce-extension' ),
				'default'     => 'DE98ZZZ09999999999',
			),
			'creditor_name'          => array(
				'title'       => __( 'config_creditor_name', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_creditor_name_desc', 'wirecard-woocommerce-extension' ),
				'default'     => '',
			),
			'creditor_city'          => array(
				'title'       => __( 'config_creditor_city', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_creditor_city_desc', 'wirecard-woocommerce-extension' ),
				'default'     => '',
			),
			'sepa_mandate_textextra' => array(
				'title'       => __( 'config_mandate_text', 'wirecard-woocommerce-extension' ),
				'type'        => 'textarea',
				'default'     => '',
				'description' => __( 'config_mandate_text_desc', 'wirecard-woocommerce-extension' ),
			),
			'advanced'               => array(
				'title'       => __( 'text_advanced', 'wirecard-woocommerce-extension' ),
				'type'        => 'title',
				'description' => '',
			),
			'payment_action'         => array(
				'title'       => __( 'config_payment_action', 'wirecard-woocommerce-extension' ),
				'type'        => 'select',
				'description' => __( 'config_payment_action_desc', 'wirecard-woocommerce-extension' ),
				'default'     => 'pay',
				'label'       => __( 'config_payment_action', 'wirecard-woocommerce-extension' ),
				'options'     => array(
					'reserve' => __( 'text_payment_action_reserve', 'wirecard-woocommerce-extension' ),
					'pay'     => __( 'text_payment_action_pay', 'wirecard-woocommerce-extension' ),
				),
			),
			'descriptor'             => array(
				'title'       => __( 'text_enable_disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'description' => __( 'config_descriptor_desc', 'wirecard-woocommerce-extension' ),
				'label'       => __( 'config_descriptor', 'wirecard-woocommerce-extension' ),
				'default'     => 'no',
			),
			'send_additional'        => array(
				'title'       => __( 'text_enable_disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'description' => __( 'config_additional_info_desc', 'wirecard-woocommerce-extension' ),
				'label'       => __( 'config_additional_info', 'wirecard-woocommerce-extension' ),
				'default'     => 'yes',
			),
			'enable_bic'             => array(
				'title'       => __( 'text_enable_disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'description' => __( 'config_enable_bic_desc', 'wirecard-woocommerce-extension' ),
				'label'       => __( 'config_enable_bic', 'wirecard-woocommerce-extension' ),
				'default'     => 'no',
			),
		);
	}

	/**
	 * Load basic scripts
	 *
	 * @since 1.1.5
	 */
	public function payment_scripts() {
		$gateway_url = WIRECARD_EXTENSION_URL;
		wp_register_style( 'basic_style', $gateway_url . '/assets/styles/frontend.css', array(), null, false );
		wp_register_style( 'jquery_ui_style', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.css', array(), null, false );
		wp_register_script( 'jquery_ui', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.js', array(), null, false );
		wp_register_script( 'sepa_js', $gateway_url . 'assets/js/sepa.js', array( 'jquery' ), null, true );
	}

	/**
	 * Add payment fields to payment method
	 *
	 * @since 1.0.0
	 */
	public function payment_fields() {
		$page_url = add_query_arg(
			array( 'wc-api' => 'get_sepa_mandate' ),
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);

		$args = array(
			'ajax_url'          => $page_url,
			'sepa_process_text' => __( 'text_confirm', 'wirecard-woocommerce-extension' ),
			'sepa_cancel_text'  => __( 'sepa_cancel', 'wirecard-woocommerce-extension' ),
		);

		wp_enqueue_style( 'basic_style' );
		wp_enqueue_style( 'jquery_ui_style' );
		wp_enqueue_script( 'jquery_ui' );
		wp_enqueue_script( 'sepa_js' );
		wp_localize_script( 'sepa_js', 'sepa_var', $args );

		$html = '
			<div id="dialog" title="SEPA"></div>
			<input type="hidden" name="sepa_nonce" value="' . wp_create_nonce() . '" />
			<p class="form-row form-row-wide validate-required">
				<label for="sepa_firstname">' . __( 'first-name', 'wirecard-woocommerce-extension' ) . '</label>
				<input id="sepa_firstname" class="input-text wc-sepa-input" type="text" name="sepa_firstname">
			</p>
			<p class="form-row form-row-wide validate-required">
				<label for="sepa_lastname">' . __( 'last-name', 'wirecard-woocommerce-extension' ) . '</label>
				<input id="sepa_lastname" class="input-text wc-sepa-input" type="text" name="sepa_lastname">
			</p>
			<p class="form-row form-row-wide validate-required">
				<label for="sepa_iban">' . __( 'iban', 'wirecard-woocommerce-extension' ) . '</label>
				<input id="sepa_iban" class="input-text wc-sepa-input" type="text" name="sepa_iban">
			</p>';

		if ( $this->get_option( 'enable_bic' ) === 'yes' ) {
			$html .= '
			<p class="form-row form-row-wide validate-required">
				<label for="sepa_bic">' . __( 'bic', 'wirecard-woocommerce-extension' ) . '</label>
				<input id="sepa_bic" class="input-text wc-sepa-input" type="text" name="sepa_bic">
			</p>';
		}

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
	 * @since 1.0.0
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! wp_verify_nonce( $_POST['sepa_nonce'] ) || ! isset( $_POST['sepa_firstname'] ) || ! isset( $_POST['sepa_lastname'] ) || ! isset( $_POST['sepa_iban'] )
			|| ( $this->get_option( 'enable_bic' ) === 'yes' && ! $_POST['sepa_bic'] ) ) {
			wc_add_notice( __( 'sepa_fields_error', 'wirecard-woocommerce-extension' ), 'error' );

			return false;
		}
		$this->payment_action = $this->get_option( 'payment_action' );

		$additional_information = new Additional_Information();
		$account_holder         = $additional_information->create_account_holder( $order, 'billing' );
		$account_holder->setFirstName( sanitize_text_field( $_POST['sepa_firstname'] ) );
		$account_holder->setLastName( sanitize_text_field( $_POST['sepa_lastname'] ) );

		$this->transaction = new SepaDirectDebitTransaction();
		parent::process_payment( $order_id );
		$this->transaction->setAccountHolder( $account_holder );
		$this->transaction->setIban( sanitize_text_field( $_POST['sepa_iban'] ) );

		if ( $this->get_option( 'enable_bic' ) === 'yes' ) {
			$this->transaction->setBic( sanitize_text_field( $_POST['sepa_bic'] ) );
		}

		$mandate = new Mandate( $this->generate_mandate_id( $order_id ) );
		$this->transaction->setMandate( $mandate );

		return $this->execute_transaction( $this->transaction, $this->config, $this->payment_action, $order );
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
		$payment_config = new SepaConfig( SepaDirectDebitTransaction::NAME, $this->get_option( 'merchant_account_id' ), $this->get_option( 'secret' ) );
		$payment_config->setCreditorId( $this->get_option( 'creditor_id' ) );
		$config->add( $payment_config );

		return $config;
	}

	/**
	 * @param int $order_id
	 *
	 * @return string
	 */
	private function generate_mandate_id( $order_id ) {
		$creditor_id = $this->get_option( 'creditor_id' );
		$appendix    = '-' . strval( $order_id ) . '-' . strtotime( gmdate( 'Y-m-d H:i:s' ) );

		return substr( $creditor_id, 0, 35 - strlen( $appendix ) ) . $appendix;
	}

	public function sepa_mandate() {
		$creditor_name       = $this->get_option( 'creditor_name' );
		$creditor_store_city = $this->get_option( 'creditor_city' );
		$creditor_id         = $this->get_option( 'creditor_id' );
		$additional_text     = $this->get_option( 'sepa_mandate_textextra' );

		$html = '';
		require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/helper/sepa-template.php' );

		wp_send_json_success( $html );
	}

	/**
	 * Create transaction for cancel
	 *
	 * @param int        $order_id
	 * @param float|null $amount
	 *
	 * @return SepaDirectDebitTransaction
	 *
	 * @since 1.0.0
	 */
	public function process_cancel( $order_id, $amount = null ) {
		$order = wc_get_order( $order_id );

		$transaction = new SepaDirectDebitTransaction();
		$transaction->setParentTransactionId( $order->get_transaction_id() );
		if ( ! is_null( $amount ) ) {
			$transaction->setAmount( new Amount( $amount, $order->get_currency() ) );
		}

		return $transaction;
	}

	/**
	 * Create transaction for capture
	 *
	 * @param int        $order_id
	 * @param float|null $amount
	 *
	 * @return SepaDirectDebitTransaction
	 *
	 * @since 1.0.0
	 */
	public function process_capture( $order_id, $amount = null ) {
		$order = wc_get_order( $order_id );

		$transaction = new SepaDirectDebitTransaction();
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
	 * @return bool|SepaCreditTransferTransaction|WP_Error
	 *
	 * @since 1.0.0
	 * @throws Exception
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$sepa_payment = new WC_Gateway_Wirecard_Sepa_Credit_Transfer();

		return $sepa_payment->process_refund( $order_id, $amount, $reason );
	}
}
