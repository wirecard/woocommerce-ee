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
		$this->method_title       = __( 'Wirecard SEPA Direct Debit', 'wooocommerce-gateway-wirecard' );
		$this->method_name        = __( 'SEPA Direct Debit', 'wooocommerce-gateway-wirecard' );
		$this->method_description = __( 'SEPA Direct Debit transactions via Wirecard Payment Processing Gateway', 'wirecard-woocommerce-extension' );
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
		$this->form_fields = array(
			'enabled'                => array(
				'title'       => __( 'Enable/Disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'description' => __( 'Activate payment method SEPA Direct Debit', 'wirecard-woocommerce-extension' ),
				'label'       => __( 'Enable Wirecard SEPA Direct Debit', 'wirecard-woocommerce-extension' ),
				'default'     => 'no',
			),
			'title'                  => array(
				'title'       => __( 'Title', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __(
					'This controls the title which the consumer sees during checkout.',
					'wirecard-woocommerce-extension'
				),
				'default'     => __( 'Wirecard SEPA Direct Debit', 'wirecard-woocommerce-extension' ),
			),
			'merchant_account_id'    => array(
				'title'       => __( 'Merchant Account ID', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'The unique identifier assigned for your Merchant Account.', 'wirecard-woocommerce-extension' ),
				'default'     => '933ad170-88f0-4c3d-a862-cff315ecfbc0',
			),
			'secret'                 => array(
				'title'       => __( 'Secret Key', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'Secret key is mandatory to calculate the Digital Signature for the payment.', 'wirecard-woocommerce-extension' ),
				'default'     => '5caf2ed9-5f79-4e65-98cb-0b70d6f569aa',
			),
			'credentials'            => array(
				'title'       => __( 'Credentials', 'wirecard-woocommerce-extension' ),
				'type'        => 'title',
				'description' => __(
					'Enter your Wirecard credentials.',
					'wirecard-woocommerce-extension'
				),
			),
			'base_url'               => array(
				'title'       => __( 'Base URL', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'The Wirecard base URL. (e.g. https://api.wirecard.com)', 'wirecard-woocommerce-extension' ),
				'default'     => 'https://api-test.wirecard.com',
			),
			'http_user'              => array(
				'title'       => __( 'HTTP User', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'The http user provided in your Wirecard contract', 'wirecard-woocommerce-extension' ),
				'default'     => '16390-testing',
			),
			'http_pass'              => array(
				'title'       => __( 'HTTP Password', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'The http password provided in your Wirecard contract', 'wirecard-woocommerce-extension' ),
				'default'     => '3!3013=D3fD8X7',
			),
			'test_button'            => array(
				'title'   => __( 'Test configuration', 'wirecard-woocommerce-extension' ),
				'type'    => 'button',
				'class'   => 'wc_wirecard_test_credentials_button button-primary',
				'default' => __( 'Test', 'wirecard-woocommerce-extension' ),
			),
			'sepa_credentials'       => array(
				'title'       => __( 'SEPA Credentials', 'wirecard-woocommerce-extension' ),
				'type'        => 'title',
				'description' => __( 'Enter your SEPA credentials and SEPA Direct Debit Mandate settings.', 'wirecard-woocommerce-extension' ),
			),
			'creditor_id'            => array(
				'title'       => __( 'Creditor ID', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'SEPA requires Creditor ID to create SEPA Direct Debit Mandate. To get a Creditor ID apply at the responsible bank institute.', 'wirecard-woocommerce-extension' ),
				'default'     => 'DE98ZZZ09999999999',
			),
			'creditor_name'          => array(
				'title'       => __( 'Creditor Name', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'SEPA requires a Creditor Name for display on the SEPA Direct Debit Mandate page.', 'wirecard-woocommerce-extension' ),
				'default'     => '',
			),
			'creditor_city'          => array(
				'title'       => __( 'Creditor City', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'SEPA requires a Creditor City for display on the SEPA Direct Debit Mandate page.', 'wirecard-woocommerce-extension' ),
				'default'     => '',
			),
			'sepa_mandate_textextra' => array(
				'title'       => __( 'Additional Text', 'wirecard-woocommerce-extension' ),
				'type'        => 'textarea',
				'default'     => '',
				'description' => __( 'This text appears on the SEPA Direct Debit Mandate page at the end of the first paragraph.', 'wirecard-woocommerce-extension' ),
			),
			'advanced'               => array(
				'title'       => __( 'Advanced Options', 'wirecard-woocommerce-extension' ),
				'type'        => 'title',
				'description' => '',
			),
			'payment_action'         => array(
				'title'       => __( 'Payment Action', 'wirecard-woocommerce-extension' ),
				'type'        => 'select',
				'description' => __( 'Select between "Capture" to capture / invoice your order automatically or "Authorization" to manually capture / invoice.', 'wirecard-woocommerce-extension' ),
				'default'     => 'Purchase',
				'label'       => __( 'Payment Action', 'wirecard-woocommerce-extension' ),
				'options'     => array(
					'reserve' => 'Authorization',
					'pay'     => 'Purchase',
				),
			),
			'descriptor'             => array(
				'title'       => __( 'Enable/Disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'description' => __( 'Send text which is displayed on the bank statement issued to your consumer by the financial service provider', 'wirecard-woocommerce-extension' ),
				'label'       => __( 'Descriptor', 'wirecard-woocommerce-extension' ),
				'default'     => 'no',
			),
			'send_additional'        => array(
				'title'       => __( 'Enable/Disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'description' => __( 'Additional data will be sent for the purpose of fraud protection. This additional data includes billing / shipping address, shopping basket and descriptor.', 'wirecard-woocommerce-extension' ),
				'label'       => __( 'Send additional information', 'wirecard-woocommerce-extension' ),
				'default'     => 'yes',
			),
			'enable_bic'             => array(
				'title'       => __( 'Enable/Disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'description' => __( 'If BIC is activated, the consumer must enter a BIC in checkout.', 'woocommerce-gateway-wireced' ),
				'label'       => __( 'BIC enabled', 'wirecard-woocommerce-extension' ),
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
			[ 'wc-api' => 'get_sepa_mandate' ],
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);

		$args = array(
			'ajax_url'          => $page_url,
			'sepa_process_text' => __( 'Process', 'wirecard-woocommerce-extension' ),
			'sepa_cancel_text'  => __( 'Cancel', 'wirecard-woocommerce-extension' ),
		);

		wp_enqueue_style( 'jquery_ui' );
		wp_enqueue_script( 'jquery_ui' );
		wp_enqueue_script( 'sepa_js' );
		wp_localize_script( 'sepa_js', 'sepa_var', $args );

		$html = '
			<div id="dialog" title="SEPA"></div>
			<input type="hidden" name="sepa_nonce" value="' . wp_create_nonce() . '" />
			<p class="form-row form-row-wide validate-required">
				<label for="sepa_firstname">' . __( 'First name', 'wooocommerce-gateway-wirecard' ) . '</label>
				<input id="sepa_firstname" class="input-text wc-sepa-input" type="text" name="sepa_firstname">
			</p>
			<p class="form-row form-row-wide validate-required">
				<label for="sepa_lastname">' . __( 'Last name', 'wooocommerce-gateway-wirecard' ) . '</label>
				<input id="sepa_lastname" class="input-text wc-sepa-input" type="text" name="sepa_lastname">
			</p>
			<p class="form-row form-row-wide validate-required">
				<label for="sepa_iban">' . __( 'IBAN', 'wooocommerce-gateway-wirecard' ) . '</label>
				<input id="sepa_iban" class="input-text wc-sepa-input" type="text" name="sepa_iban">
			</p>';

		if ( $this->get_option( 'enable_bic' ) == 'yes' ) {
			$html .= '			
			<p class="form-row form-row-wide validate-required">
				<label for="sepa_bic">' . __( 'BIC', 'wooocommerce-gateway-wirecard' ) . '</label>
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
			|| ( $this->get_option( 'enable_bic' ) == 'yes' && ! $_POST['sepa_bic'] ) ) {
			wc_add_notice( __( 'Please fill in the SEPA fields and try again.', 'wirecard-woocommerce-extension' ), 'error' );

			return false;
		}
		$this->payment_action = $this->get_option( 'payment_action' );

		$account_holder = new AccountHolder();
		$account_holder->setFirstName( sanitize_text_field( $_POST['sepa_lastname'] ) );
		$account_holder->setLastName( sanitize_text_field( $_POST['sepa_firstname'] ) );

		$this->transaction = new SepaDirectDebitTransaction();
		parent::process_payment( $order_id );
		$this->transaction->setAccountHolder( $account_holder );
		$this->transaction->setIban( sanitize_text_field( $_POST['sepa_iban'] ) );

		if ( $this->get_option( 'enable_bic' ) == 'yes' ) {
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
	 * @param $order_id
	 *
	 * @return string
	 */
	private function generate_mandate_id( $order_id ) {
		return $this->get_option( 'creditor_id' ) . '-' . $order_id . '-' . strtotime( date( 'Y-m-d H:i:s' ) );
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
