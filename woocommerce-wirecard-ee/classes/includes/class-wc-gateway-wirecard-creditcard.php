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
require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/helper/class-credit-card-vault.php' );

use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\CreditCardConfig;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\TransactionService;

/**
 * Class WC_Gateway_Wirecard_CreditCard
 *
 * @extends WC_Wirecard_Payment_Gateway
 *
 * @since   1.0.0
 */
class WC_Gateway_Wirecard_Creditcard extends WC_Wirecard_Payment_Gateway {

	private $vault;

	/**
	 * WC_Gateway_Wirecard_Creditcard constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->type               = 'creditcard';
		$this->id                 = 'wirecard_ee_creditcard';
		$this->icon               = WOOCOMMERCE_GATEWAY_WIRECARD_URL . 'assets/images/creditcard.png';
		$this->method_title       = __( 'Wirecard Credit Card', 'wooocommerce-gateway-wirecard' );
		$this->method_name        = __( 'Credit Card', 'wooocommerce-gateway-wirecard' );
		$this->method_description = __( 'Credit Card transactions via Wirecard Payment Processing Gateway', 'woocommerce-gateway-wirecard' );
		$this->has_fields         = true;
		$this->vault              = new Credit_Card_Vault();

		$this->supports = array(
			'products',
			'refunds',
		);

		$this->cancel        = array( 'authorization' );
		$this->capture       = array( 'authorization' );
		$this->refund        = array( 'purchase', 'capture-authorization' );
		$this->refund_action = 'refund';

		$this->init_form_fields();
		$this->init_settings();

		$this->title   = $this->get_option( 'title' );
		$this->enabled = $this->get_option( 'enabled' );

		$this->additional_helper = new Additional_Information();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_api_get_credit_card_request_data', array( $this, 'get_request_data_credit_card' ) );
		add_action( 'woocommerce_api_save_cc_to_vault', array( $this, 'save_to_vault' ) );
		add_action( 'woocommerce_api_get_cc_from_vault', array( $this, 'get_cc_from_vault' ) );
		add_action( 'woocommerce_api_remove_cc_from_vault', array( $this, 'remove_cc_from_vault' ) );

		parent::add_payment_gateway_actions();
	}

	/**
	 * Load form fields for configuration
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'                     => array(
				'title'       => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Wirecard Credit Card', 'woocommerce-gateway-wirecard' ),
				'description' => __( 'Activate payment method Credit Card', 'woocommerce-gateway-wirecard' ),
				'default'     => 'no',
			),
			'title'                       => array(
				'title'       => __( 'Title', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the consumer sees during checkout.', 'woocommerce-gateway-wirecard' ),
				'default'     => __( 'Wirecard Credit Card', 'woocommerce-gateway-wirecard' ),
			),
			'merchant_account_id'         => array(
				'title'       => __( 'Merchant Account ID', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'The unique identifier assigned for your Merchant Account.', 'woocommerce-gateway-wirecard' ),
				'default'     => '53f2895a-e4de-4e82-a813-0d87a10e55e6',
			),
			'secret'                      => array(
				'title'       => __( 'Secret Key', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'Secret key is mandatory to calculate the Digital Signature for the payment.', 'woocommerce-gateway-wirecard' ),
				'default'     => 'dbc5a498-9a66-43b9-bf1d-a618dd399684',
			),
			'three_d_merchant_account_id' => array(
				'title'       => __( '3-D Secure Merchant Account ID', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'The unique identifier assigned for your 3D Merchant Account.', 'woocommerce-gateway-wirecard' ),
				'default'     => '508b8896-b37d-4614-845c-26bf8bf2c948',
			),
			'three_d_secret'              => array(
				'title'       => __( '3-D Secure Secret Key', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'Secret key is mandatory to calculate the Digital Signature for the 3D payment.', 'woocommerce-gateway-wirecard' ),
				'default'     => 'dbc5a498-9a66-43b9-bf1d-a618dd399684',
			),
			'ssl_max_limit'               => array(
				'title'       => __( 'Non 3-D Secure Max. Limit', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'Amount in default shop currency', 'woocommerce-gateway-wirecard' ),
				'default'     => '100.0',
			),
			'three_d_min_limit'           => array(
				'title'       => __( '3-D Secure Min. Limit', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'Amount in default shop currency', 'woocommerce-gateway-wirecard' ),
				'default'     => '50.0',
			),
			'credentials'                 => array(
				'title'       => __( 'Credentials', 'woocommerce-gateway-wirecard' ),
				'type'        => 'title',
				'description' => __( 'Enter your Wirecard credentials.', 'woocommerce-gateway-wirecard' ),
			),
			'base_url'                    => array(
				'title'       => __( 'Base URL', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'The Wirecard base URL. (e.g. https://api.wirecard.com)', 'woocomerce-gateway-wirecard' ),
				'default'     => 'https://api-test.wirecard.com',
			),
			'http_user'                   => array(
				'title'       => __( 'HTTP User', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'The http user provided in your Wirecard contract', 'woocommerce-gateway-wirecard' ),
				'default'     => '70000-APITEST-AP',
			),
			'http_pass'                   => array(
				'title'       => __( 'HTTP Password', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'The http password provided in your Wirecard contract', 'woocommerce-gateway-wirecard' ),
				'default'     => 'qD2wzQ_hrc!8',
			),
			'test_button'                 => array(
				'title'   => __( 'Test configuration', 'woocommerce-gateway-wirecard' ),
				'type'    => 'button',
				'class'   => 'wc_wirecard_test_credentials_button button-primary',
				'default' => __( 'Test', 'woocommerce-gateway-wirecard' ),
			),
			'advanced'                    => array(
				'title'       => __( 'Advanced Options', 'woocommerce-gateway-wirecard' ),
				'type'        => 'title',
				'description' => '',
			),
			'payment_action'              => array(
				'title'       => __( 'Payment Action', 'woocommerce-gateway-wirecard' ),
				'type'        => 'select',
				'description' => __( 'Select between "Capture" to capture / invoice your order automatically or "Authorization" to manually capture / invoice. ', 'woocommerce-gateway-wirecard' ),
				'default'     => 'Capture',
				'label'       => __( 'Payment Action', 'woocommerce-gateway-wirecard' ),
				'options'     => array(
					'reserve' => 'Authorization',
					'pay'     => 'Capture',
				),
			),
			'descriptor'                  => array(
				'title'       => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'        => 'checkbox',
				'description' => __( 'Send text which is displayed on the bank statement issued to your consumer by the financial service provider', 'woocommerce-gateway-wirecard' ),
				'label'       => __( 'Descriptor', 'woocommerce-gateway-wirecard' ),
				'default'     => 'no',
			),
			'send_additional'             => array(
				'title'       => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'        => 'checkbox',
				'description' => __( 'Additional data will be sent for the purpose of fraud protection. This additional data includes billing / shipping address, shopping basket and descriptor.', 'woocommerce-gateway-wirecard' ),
				'label'       => __( 'Send additional information', 'woocommerce-gateway-wirecard' ),
				'default'     => 'yes',
			),
			'cc_vault_enabled'            => array(
				'title'       => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'        => 'checkbox',
				'description' => __( 'Credit Cards can be saved and used for later purpose without', 'woocommerce-gateway-wirecard' ),
				'label'       => __( 'Enable Recurring Payment', 'woocommerce-gateway-wirecard' ),
				'default'     => 'no',
			),
		);
	}

	/**
	 * Create payment method Configuration
	 *
	 * @return Config
	 *
	 * @since 1.0.0
	 */
	public function create_payment_config( $base_url = null, $http_user = null, $http_pass = null ) {
		if ( is_null( $base_url ) ) {
			$base_url  = $this->get_option( 'base_url' );
			$http_user = $this->get_option( 'http_user' );
			$http_pass = $this->get_option( 'http_pass' );
		}

		$config         = parent::create_payment_config( $base_url, $http_user, $http_pass );
		$payment_config = new CreditCardConfig(
			$this->get_option( 'merchant_account_id' ),
			$this->get_option( 'secret' )
		);

		if ( $this->get_option( 'three_d_merchant_account_id' ) !== '' ) {
			$payment_config->setThreeDCredentials(
				$this->get_option( 'three_d_merchant_account_id' ),
				$this->get_option( 'three_d_secret' )
			);
		}

		if ( $this->get_option( 'ssl_max_limit' ) !== '' ) {
			$payment_config->addSslMaxLimit(
				new Amount(
					$this->get_option( 'ssl_max_limit' ),
					$this->get_option( 'woocommerce_currency' )
				)
			);
		}

		if ( $this->get_option( 'three_d_min_limit' ) !== '' ) {
			$payment_config->addThreeDMinLimit(
				new Amount(
					$this->get_option( 'three_d_min_limit' ),
					$this->get_option( 'woocommerce_currency' )
				)
			);
		}

		$config->add( $payment_config );

		return $config;
	}

	/**
	 * Add payment fields to payment method
	 *
	 * @since 1.0.0
	 */
	public function payment_fields() {
		$base_url         = $this->get_option( 'base_url' );
		$gateway_url      = WOOCOMMERCE_GATEWAY_WIRECARD_URL;
		$page_url         = add_query_arg(
			[ 'wc-api' => 'get_credit_card_request_data' ],
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);
		$vault_save_url   = add_query_arg(
			[ 'wc-api' => 'save_cc_to_vault' ],
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);
		$vault_get_url    = add_query_arg(
			[ 'wc-api' => 'get_cc_from_vault' ],
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);
		$vault_delete_url = add_query_arg(
			[ 'wc-api' => 'remove_cc_from_vault' ],
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);

		$html = <<<HTML
			<script src='$base_url/engine/hpp/paymentPageLoader.js' type='text/javascript'></script>
			<link href='$gateway_url/assets/styles/frontend.css' type="text/css" rel="stylesheet" />
            <link href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.css" type="text/css" rel="stylesheet" />
			<script type='application/javascript' src='$gateway_url/assets/js/creditcard.js'></script>
			<script type="application/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.js"></script>
            <script>
                var ajax_url  = "$page_url";
                var vault_url = "$vault_save_url";
                var vault_get_url = "$vault_get_url";
                var vault_delete_url = "$vault_delete_url";
            </script>
            
HTML;
		if ( $this->get_option( 'cc_vault_enabled' ) == 'yes' && $this->has_cc_in_vault() ) {
			$html .= '<div id="open-vault-popup"><span class="dashicons dashicons-arrow-up"></span>' . __( 'Use saved Credit Cards', 'woocommerce-gateway-wirecard' ) . '</div>
            <div id="wc_payment_method_wirecard_creditcard_vault"><div class="show-spinner"><div class="spinner"></div></div><div class="cards"></div></div><br>
            <div id="open-new-card"><span class="dashicons dashicons-arrow-down"></span>' . __( 'Use new Credit Card', 'woocommerce-gateway-wirecard' ) . '</div>
            <div id="wc_payment_method_wirecard_new_credit_card">';
		}

		$html .= '<div class="show-spinner"><div class="spinner"></div></div><div id="wc_payment_method_wirecard_creditcard_form"></div>';

		if ( $this->get_option( 'cc_vault_enabled' ) == 'yes' ) {
			$html .= '<div class="save-later"><label for="wirecard-store-card">
			<input type="checkbox" id="wirecard-store-card" /> ' .
				__( 'Save for later use.', 'woocommerce-gateway-wirecard' ) . '</label></div>';
			if ( $this->has_cc_in_vault() ) {
				$html .= '</div>';
			}
		}

		echo $html;
	}

	/**
	 * Process payment gateway transactions
	 *
	 * @param int $order_id
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		$this->payment_action = $this->get_option( 'payment_action' );
		$token                = $_POST['tokenId'];

		$this->transaction = new CreditCardTransaction();
		parent::process_payment( $order_id );

		$this->transaction->setTokenId( $token );
		$this->transaction->setTermUrl( $this->create_redirect_url( $order, 'success', $this->type ) );

		return $this->execute_transaction( $this->transaction, $this->config, $this->payment_action, $order );
	}

	/**
	 * Return request data for the credit card form
	 *
	 * @since 1.0.0
	 */
	public function get_request_data_credit_card() {
		$config              = $this->create_payment_config();
		$transaction_service = new TransactionService( $config );
		wp_send_json_success( $transaction_service->getDataForCreditCardUi() );
		die();
	}

	/**
	 * Create transaction for cancel
	 *
	 * @param int        $order_id
	 * @param float|null $amount
	 *
	 * @return CreditCardTransaction
	 *
	 * @since 1.0.0
	 */
	public function process_cancel( $order_id, $amount = null ) {
		$order = wc_get_order( $order_id );

		$transaction = new CreditCardTransaction();
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
	 * @return CreditCardTransaction
	 *
	 * @since 1.0.0
	 */
	public function process_capture( $order_id, $amount = null ) {
		$order = wc_get_order( $order_id );

		$transaction = new CreditCardTransaction();
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
	 * @return bool|CreditCardTransaction|WP_Error
	 *
	 * @since 1.0.0
	 * @throws Exception
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$this->transaction = new CreditCardTransaction();

		return parent::process_refund( $order_id, $amount, '' );
	}

	/**
	 *  Save Credit card data to Vault
	 *
	 * @since 1.1.0
	 */
	public function save_to_vault() {
		$token    = $_POST['token'];
		$mask_pan = $_POST['mask_pan'];
		/** @var WP_User $user */
		$user = wp_get_current_user();

		if ( $this->vault->save_card( $user->ID, $token, $mask_pan ) ) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
		die();
	}

	/**
	 * Get Credit Cards from Vault
	 *
	 * @since 1.1.0
	 */
	public function get_cc_from_vault() {
		if ( $this->get_option( 'cc_vault_enabled' ) != 'yes' ) {
			wp_send_json_success( false );
		}

		/** @var WP_User $user */
		$user = wp_get_current_user();

		wp_send_json_success( $this->vault->get_cards_for_user( $user->ID ) );
		die();
	}

	/**
	 * Remove Credit Card from Vault
	 *
	 * @since 1.1.0
	 */
	public function remove_cc_from_vault() {
		$vault_id = $_POST['vault_id'];

		if ( isset( $vault_id ) && $this->vault->delete_credit_card( $vault_id ) > 0 ) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
		die();
	}

	/**
	 * Check if the user has Credit Cards in Vault
	 *
	 * @return true|false
	 * @since 1.1.0
	 */
	private function has_cc_in_vault() {
		/** @var WP_User $user */
		$user = wp_get_current_user();

		if ( $this->vault->get_cards_for_user( $user->ID ) ) {
			return true;
		}
		return false;
	}
}
