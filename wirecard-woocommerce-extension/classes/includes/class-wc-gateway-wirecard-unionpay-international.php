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
use Wirecard\PaymentSdk\Transaction\UpiTransaction;
use Wirecard\PaymentSdk\TransactionService;

/**
 * Class WC_Gateway_Wirecard_Unionpay_International
 *
 * @extends WC_Wirecard_Payment_Gateway
 *
 * @since   1.1.0
 */
class WC_Gateway_Wirecard_Unionpay_International extends WC_Wirecard_Payment_Gateway {

	/**
	 * WC_Gateway_Wirecard_Unionpay_International constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->type               = 'unionpayinternational';
		$this->id                 = 'wirecard_ee_unionpayinternational';
		$this->icon               = WIRECARD_EXTENSION_URL . 'assets/images/unionpayinternational.png';
		$this->method_title       = __( 'heading_title_upi', 'wirecard-woocommerce-extension' );
		$this->method_name        = __( 'upi', 'wirecard-woocommerce-extension' );
		$this->method_description = __( 'upi_desc', 'wirecard-woocommerce-extension' );
		$this->has_fields         = true;

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
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ), 999 );

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
				'title'       => __( 'text_enable_disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'description' => __( 'config_status_desc_upi', 'wirecard-woocommerce-extension' ),
				'label'       => __( 'enable_heading_title_upi', 'wirecard-woocommerce-extension' ),
				'default'     => 'no',
			),
			'title'               => array(
				'title'       => __( 'config_title', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_title_desc', 'wirecard-woocommerce-extension' ),
				'default'     => __( 'heading_title_upi', 'wirecard-woocommerce-extension' ),
			),
			'merchant_account_id' => array(
				'title'       => __( 'config_merchant_account_id', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_merchant_account_id_desc', 'wirecard-woocommerce-extension' ),
				'default'     => 'c6e9331c-5c1f-4fc6-8a08-ef65ce09ddb0',
			),
			'secret'              => array(
				'title'       => __( 'config_merchant_secret', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_merchant_secret_desc', 'wirecard-woocommerce-extension' ),
				'default'     => '16d85b73-79e2-4c33-932a-7da99fb04a9c',
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
				'default'     => 'https://api-test.wirecard.com',
			),
			'http_user'           => array(
				'title'       => __( 'config_http_user', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_http_user_desc', 'wirecard-woocommerce-extension' ),
				'default'     => '70000-APILUHN-CARD',
			),
			'http_pass'           => array(
				'title'       => __( 'config_http_password', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_http_password_desc', 'wirecard-woocommerce-extension' ),
				'default'     => '8mhwavKVb91T',
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
			'payment_action'      => array(
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
	 * Load basic scripts
	 *
	 * @since 1.1.5
	 */
	public function payment_scripts() {
		$base_url    = $this->get_option( 'base_url' );
		$gateway_url = WIRECARD_EXTENSION_URL;

		wp_register_style( 'jquery_ui_style', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.css', array(), null, false );
		wp_register_script( 'jquery_ui', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.js', array(), null, false );
		wp_register_script( 'page_loader', $base_url . '/engine/hpp/paymentPageLoader.js', array(), null, true );
		wp_register_script( 'upi_js', $gateway_url . 'assets/js/unionpayinternational.js', array( 'jquery', 'page_loader' ), null, true );
	}

	/**
	 * Add payment fields to payment method
	 *
	 * @since 1.1.0
	 */
	public function payment_fields() {
		$page_url = add_query_arg(
			[ 'wc-api' => 'get_upi_request_data' ],
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);

		$args = array(
			'ajax_url' => $page_url,
		);

		wp_enqueue_script( 'jquery_ui' );
		wp_enqueue_style( 'jquery_ui_style' );
		wp_enqueue_script( 'page_loader' );
		wp_enqueue_script( 'upi_js' );
		wp_localize_script( 'upi_js', 'upi_vars', $args );

		$html = <<<HTML
            <div id='wc_payment_method_wirecard_unionpayinternational_form'></div>
HTML;

		echo $html;
		return true;
	}

	/**
	 * Return request data for the unionpay international form
	 *
	 * @since 1.1.0
	 */
	public function get_request_data_upi() {
		$config              = $this->create_payment_config();
		$transaction_service = new TransactionService( $config );
		$lang                = 'en';
		try {
			$supported_lang = json_decode( file_get_contents( $this->get_option( 'base_url' ) . '/engine/includes/i18n/languages/hpplanguages.json' ) );
			if ( key_exists( substr( get_locale(), 0, 2 ), $supported_lang ) ) {
				$lang = substr( get_locale(), 0, 2 );
			} elseif ( key_exists( get_locale(), $supported_lang ) ) {
				$lang = get_locale();
			}
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
		wp_send_json_success( $transaction_service->getDataForUpiUi( $lang, new Amount( 0, get_woocommerce_currency() ) ) );
		wp_die();
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

		$this->payment_action = $this->get_option( 'payment_action' );
		$token                = sanitize_text_field( $_POST['tokenId'] );

		$this->transaction = new UpiTransaction();
		parent::process_payment( $order_id );

		$this->transaction->setTokenId( $token );
		$this->transaction->setTermUrl( $this->create_redirect_url( $order, 'success', $this->type ) );

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

		$transaction = new UpiTransaction();
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
	 * @return UpiTransaction
	 *
	 * @since 1.0.0
	 */
	public function process_capture( $order_id, $amount = null ) {
		$order = wc_get_order( $order_id );

		$transaction = new UpiTransaction();
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
		$this->transaction = new UpiTransaction();

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
			UpiTransaction::NAME,
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
