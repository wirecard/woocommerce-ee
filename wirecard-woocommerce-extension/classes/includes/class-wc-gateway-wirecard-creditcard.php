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
require_once( WIRECARD_EXTENSION_HELPER_DIR . 'class-address-data.php' );
require_once( WIRECARD_EXTENSION_HELPER_DIR . 'class-vault-data.php' );
require_once( WIRECARD_EXTENSION_HELPER_DIR . 'class-credit-card-vault.php' );
require_once( WIRECARD_EXTENSION_HELPER_DIR . 'class-template-helper.php' );
require_once( WIRECARD_EXTENSION_HELPER_DIR . 'class-logger.php' );
require_once( WIRECARD_EXTENSION_HELPER_DIR . 'class-admin-message.php' );
require_once( WIRECARD_EXTENSION_HELPER_DIR . 'class-action-helper.php' );
require_once( WIRECARD_EXTENSION_HELPER_DIR . 'class-three-ds-helper.php' );

use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\CreditCardConfig;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\TransactionService;
use Wirecard\Converter\WppVTwoConverter;
use Wirecard\PaymentSdk\Constant\ChallengeInd;

/**
 * Class WC_Gateway_Wirecard_CreditCard
 *
 * @extends WC_Wirecard_Payment_Gateway
 *
 * @since   1.0.0
 */
class WC_Gateway_Wirecard_Creditcard extends WC_Wirecard_Payment_Gateway {

	/** @var Credit_Card_Vault $vault */
	private $vault;

	/**
	 * @var bool $force_three_d
	 * @since 2.1.0
	 */
	private $force_three_d;

	/**
	 * @var Template_Helper $template_helper
	 *
	 * @since 2.0.0
	 */
	protected $template_helper;

	/**
	 * @var Logger $logger
	 *
	 * @since 2.0.0
	 */
	protected $logger;

	/**
	 * @var Address_Data
	 */
	private $current_address_data;

	/**
	 * WC_Gateway_Wirecard_Creditcard constructor.
	 *
	 * @since 2.0.0 Update constructor so it can be shared
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->type              = 'creditcard';
		$this->logger            = new Logger();
		$this->additional_helper = new Additional_Information();
		$this->template_helper   = new Template_Helper();
		$this->supports          = array( 'products', 'refunds' );
		$this->cancel            = array( 'authorization' );
		$this->capture           = array( 'authorization' );
		$this->refund            = array( 'purchase', 'capture-authorization' );

		$this->init();

		$this->init_form_fields();
		$this->init_settings();

		$this->title   = $this->get_option( 'title' );
		$this->enabled = $this->get_option( 'enabled' );

		$woocommerce_update_options = 'woocommerce_update_options_payment_gateways_' . $this->id;
		$action_helper              = new Action_Helper();

		add_action( $woocommerce_update_options, array( $this, 'process_admin_options' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ), 999 );
		$action_helper->add_action_once( $woocommerce_update_options, array( $this, 'validate_url_configuration' ) );

		$this->add_payment_gateway_actions();
	}

	/**
	 * Called in constructor
	 * Initializes the class
	 *
	 * @since 2.0.0
	 */
	protected function init() {
		$this->id                 = 'wirecard_ee_creditcard';
		$this->icon               = WIRECARD_EXTENSION_URL . 'assets/images/creditcard.png';
		$this->method_title       = __( 'heading_title_creditcard', 'wirecard-woocommerce-extension' );
		$this->method_name        = __( 'creditcard', 'wirecard-woocommerce-extension' );
		$this->method_description = __( 'creditcard_desc', 'wirecard-woocommerce-extension' );
		$this->vault              = new Credit_Card_Vault();

		$this->refund_action = 'refund';

		add_action( 'woocommerce_api_get_credit_card_request_data', array( $this, 'get_request_data_credit_card' ) );
		add_action( 'woocommerce_api_save_cc_to_vault', array( $this, 'save_to_vault' ) );
		add_action( 'woocommerce_api_get_cc_from_vault', array( $this, 'get_cc_from_vault' ) );
		add_action( 'woocommerce_api_remove_cc_from_vault', array( $this, 'remove_cc_from_vault' ) );
	}

	/**
	 * @since 1.7.0
	 */
	public function add_payment_gateway_actions() {
		parent::add_payment_gateway_actions();

		add_action(
			'woocommerce_api_submit_creditcard_response',
			array(
				$this,
				'execute_payment',
			)
		);
	}

	/**
	 * Load form fields for configuration
	 *
	 * @since 2.1.0 challenge_indicator config field
	 * @since 1.0.0
	 */
	public function init_form_fields() {
		parent::init_form_fields();
		$challenge_indicators = $this->get_challenge_indicator_options();

		$this->form_fields = array(
			'enabled'                     => array(
				'title'       => __( 'text_enable_disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'label'       => __( 'enable_heading_title_creditcard', 'wirecard-woocommerce-extension' ),
				'description' => __( 'config_status_desc_creditcard', 'wirecard-woocommerce-extension' ),
				'default'     => 'no',
			),
			'psd_two'                     => array(
				'title'       => __( 'config_PSD2_information', 'wirecard-woocommerce-extension' ),
				'type'        => 'hidden',
				'description' => __( 'config_PSD2_information_desc_woocommerce', 'wirecard-woocommerce-extension' ),
			),
			'title'                       => array(
				'title'       => __( 'config_title', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_title_desc', 'wirecard-woocommerce-extension' ),
				'default'     => __( 'heading_title_creditcard', 'wirecard-woocommerce-extension' ),
			),
			'merchant_account_id'         => array(
				'title'       => __( 'config_merchant_account_id', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_merchant_account_id_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getMerchantAccountId(),
			),
			'secret'                      => array(
				'title'       => __( 'config_merchant_secret', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_merchant_secret_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getSecret(),
			),
			'three_d_merchant_account_id' => array(
				'title'       => __( 'config_three_d_merchant_account_id', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_three_d_merchant_account_id_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getThreeDMerchantAccountId(),
			),
			'three_d_secret'              => array(
				'title'       => __( 'config_three_d_merchant_secret', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_three_d_merchant_secret_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getThreeDSecret(),
			),
			'ssl_max_limit'               => array(
				'title'       => __( 'config_ssl_max_limit', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_limit_desc', 'wirecard-woocommerce-extension' ),
				'default'     => '100.0',
			),
			'three_d_min_limit'           => array(
				'title'       => __( 'config_three_d_min_limit', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_limit_desc', 'wirecard-woocommerce-extension' ),
				'default'     => '50.0',
			),
			'credentials'                 => array(
				'title'       => __( 'text_credentials', 'wirecard-woocommerce-extension' ),
				'type'        => 'title',
				'description' => __( 'text_credentials_desc', 'wirecard-woocommerce-extension' ),
			),
			'base_url'                    => array(
				'title'       => __( 'config_base_url', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_base_url_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getBaseUrl(),
			),
			'wpp_url'                     => array(
				'title'       => __( 'config_wpp_url', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_wpp_url_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getWppUrl(),
			),
			'http_user'                   => array(
				'title'       => __( 'config_http_user', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_http_user_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getHttpUser(),
			),
			'http_pass'                   => array(
				'title'       => __( 'config_http_password', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_http_password_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getHttpPassword(),
			),
			'test_button'                 => array(
				'title'   => __( 'test_config', 'wirecard-woocommerce-extension' ),
				'type'    => 'button',
				'class'   => 'wc_wirecard_test_credentials_button button-primary',
				'default' => __( 'test_credentials', 'wirecard-woocommerce-extension' ),
			),
			'advanced'                    => array(
				'title'       => __( 'text_advanced', 'wirecard-woocommerce-extension' ),
				'type'        => 'title',
				'description' => '',
			),
			'payment_action'              => array(
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
			'challenge_indicator'         => array(
				'title'          => __( 'config_challenge_indicator', 'wirecard-woocommerce-extension' ),
				'type'           => 'select',
				'description'    => __( 'config_challenge_indicator_desc', 'wirecard-woocommerce-extension' ),
				'options'        => $challenge_indicators,
				'default'        => ChallengeInd::NO_PREFERENCE,
				'multiple'       => true,
				'select_buttons' => true,
			),
			'descriptor'                  => array(
				'title'       => __( 'text_enable_disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'description' => __( 'config_descriptor_desc', 'wirecard-woocommerce-extension' ),
				'label'       => __( 'config_descriptor', 'wirecard-woocommerce-extension' ),
				'default'     => 'no',
			),
			'send_additional'             => array(
				'title'       => __( 'text_enable_disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'description' => __( 'config_additional_info_desc', 'wirecard-woocommerce-extension' ),
				'label'       => __( 'config_additional_info', 'wirecard-woocommerce-extension' ),
				'default'     => 'yes',
			),
			'cc_vault_enabled'            => array(
				'title'       => __( 'text_enable_disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'description' => __( 'config_vault_desc', 'wirecard-woocommerce-extension' ),
				'label'       => __( 'enable_vault', 'wirecard-woocommerce-extension' ),
				'default'     => 'no',
			),
		);
	}

	/**
	 * Creates challenge indicator options for admin configuration
	 *
	 * @return array
	 * @since 2.1.0
	 */
	private function get_challenge_indicator_options() {
		return array(
			ChallengeInd::NO_PREFERENCE    => __( 'config_challenge_no_preference', 'wirecard-woocommerce-extension' ),
			ChallengeInd::NO_CHALLENGE     => __( 'config_challenge_no_challenge', 'wirecard-woocommerce-extension' ),
			ChallengeInd::CHALLENGE_THREED => __( 'config_challenge_challenge_threed', 'wirecard-woocommerce-extension' ),
		);
	}

	/**
	 * Create payment method Configuration
	 * @param string|null $base_url
	 * @param string|null $http_user
	 * @param string|null $http_pass
	 *
	 * @return Config
	 *
	 * @since 2.1.1 Add forced three d check
	 * @since 1.0.0
	 */
	public function create_payment_config( $base_url = null, $http_user = null, $http_pass = null ) {
		$config                      = $this->initialize_config( $base_url, $http_user, $http_pass );
		$payment_config              = new CreditCardConfig();
		$merchant_account_id         = $this->get_option( 'merchant_account_id' );
		$secret                      = $this->get_option( 'secret' );
		$three_d_merchant_account_id = $this->get_option( 'three_d_merchant_account_id' );
		$three_d_secret              = $this->get_option( 'three_d_secret' );

		$this->set_payment_config_maids(
			$payment_config,
			$merchant_account_id,
			$secret,
			$three_d_merchant_account_id,
			$three_d_secret
		);

		if ( ! empty( $merchant_account_id ) && ! empty( $three_d_merchant_account_id ) ) {
			$this->set_payment_config_three_d_limits( $payment_config );
		}

		$this->set_force_three_d( $merchant_account_id, $three_d_merchant_account_id );
		$config->add( $payment_config );

		return $config;
	}

	/**
	 * @param string $base_url
	 * @param string $http_user
	 * @param string $http_pass
	 *
	 * @return Config
	 *
	 * @since 2.1.1
	 */
	protected function initialize_config( $base_url, $http_user, $http_pass ) {
		if ( empty( $base_url ) || empty( $http_user ) || empty( $http_pass ) ) {
			$base_url  = $this->get_option( 'base_url' );
			$http_user = $this->get_option( 'http_user' );
			$http_pass = $this->get_option( 'http_pass' );
		}

		return parent::create_payment_config( $base_url, $http_user, $http_pass );
	}

	/**
	 * @param CreditCardConfig $payment_config
	 * @param $merchant_account_id
	 * @param $secret
	 * @param $three_d_merchant_account_id
	 * @param $three_d_secret
	 *
	 * @since 2.1.1
	 */
	protected function set_payment_config_maids(
		$payment_config,
		$merchant_account_id,
		$secret,
		$three_d_merchant_account_id,
		$three_d_secret
	) {
		if ( ! empty( $merchant_account_id ) && ! empty( $secret ) ) {
			$payment_config->setSSLCredentials( $merchant_account_id, $secret );
		}

		if ( ! empty( $three_d_merchant_account_id ) && ! empty( $three_d_secret ) ) {
			$payment_config->setThreeDCredentials( $three_d_merchant_account_id, $three_d_secret );
		}
	}

	/**
	 * @param CreditCardConfig $payment_config
	 *
	 * @since 2.1.1
	 */
	protected function set_payment_config_three_d_limits( $payment_config ) {
		$ssl_max_limit        = $this->get_option( 'ssl_max_limit' );
		$three_d_min_limit    = $this->get_option( 'three_d_min_limit' );
		$woocommerce_currency = $this->get_option( 'woocommerce_currency' );
		if ( ! strlen( $woocommerce_currency ) ) {
			$woocommerce_currency = get_woocommerce_currency();
		}

		if ( ! empty( $ssl_max_limit ) ) {
			$payment_config->addSslMaxLimit(
				new Amount(
					floatval( $ssl_max_limit ),
					$woocommerce_currency
				)
			);
		}

		if ( ! empty( $three_d_min_limit ) ) {
			$payment_config->addThreeDMinLimit(
				new Amount(
					floatval( $this->get_option( 'three_d_min_limit' ) ),
					$woocommerce_currency
				)
			);
		}
	}

	/**
	 * @param string $merchant_account_id
	 * @param string $three_d_merchant_account_id
	 *
	 * @since 2.1.1
	 */
	protected function set_force_three_d( $merchant_account_id, $three_d_merchant_account_id ) {
		$force_three_d = false;
		if ( empty( $merchant_account_id ) && ! empty( $three_d_merchant_account_id ) ) {
			$force_three_d = true;
		}

		$this->force_three_d = $force_three_d;
	}

	/**
	 * Load basic scripts
	 *
	 * @since 1.1.5
	 */
	public function payment_scripts() {
		$wpp_url     = $this->get_option( 'wpp_url' );
		$gateway_url = WIRECARD_EXTENSION_URL;

		wp_register_style( 'basic_style', $gateway_url . '/assets/styles/frontend.css', array(), null, false );
		wp_register_style( 'jquery_ui_style', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.css', array(), null, false );
		wp_register_script( 'jquery_ui', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.js', array(), null, false );
		wp_register_script( 'page_loader', $wpp_url . '/loader/paymentPage.js', array(), null, true );
		wp_register_script( 'credit_card_js', $gateway_url . 'assets/js/creditcard.js', array( 'jquery', 'page_loader' ), null, true );
	}

	/**
	 * Load variables for credit card javascript
	 *
	 * @return array
	 * @since 1.1.8
	 */
	public function load_variables() {
		$base_url         = site_url( '/', is_ssl() ? 'https' : 'http' );
		$page_url         = add_query_arg(
			array( 'wc-api' => 'get_credit_card_request_data' ),
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);
		$submit_url       = add_query_arg(
			array( 'wc-api' => 'submit_creditcard_response' ),
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);
		$token_url        = add_query_arg(
			array( 'wc-api' => 'submit_token_response' ),
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);
		$vault_save_url   = add_query_arg(
			array( 'wc-api' => 'save_cc_to_vault' ),
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);
		$vault_get_url    = add_query_arg(
			array( 'wc-api' => 'get_cc_from_vault' ),
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);
		$vault_delete_url = add_query_arg(
			array( 'wc-api' => 'remove_cc_from_vault' ),
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);

		return array(
			'ajax_url'         => $page_url,
			'submit_url'       => $submit_url,
			'token_url'        => $token_url,
			'base_url'         => $base_url,
			'vault_url'        => $vault_save_url,
			'vault_get_url'    => $vault_get_url,
			'vault_delete_url' => $vault_delete_url,
			'spinner'          => $this->get_spinner(),
		);
	}

	/**
	 * Load html for the template
	 *
	 * @return string
	 * @since 1.1.8
	 */
	public function load_cc_template() {
		$html = '<h2 class="credit-card-heading">' . __( 'heading_creditcard_payment_form', 'wirecard-woocommerce-extension' ) . '</h2>';

		if ( is_user_logged_in()
			&& $this->get_option( 'cc_vault_enabled' ) === 'yes'
			&& $this->has_cc_in_vault()
		) {
			$html .= $this->get_vault_html();
		}

		$html .= $this->get_creditcard_form_html();

		if (
			is_user_logged_in()
			&& $this->get_option( 'cc_vault_enabled' ) === 'yes'
		) {
			$html .= $this->get_save_for_later_html();
		}

		$html .= $this->get_creditcard_submit_html();

		return $html;
	}

	/**
	 * Add payment fields to payment method
	 *
	 * @since 1.0.0
	 */
	public function render_form() {
		$this->enqueue_scripts();

		wp_enqueue_script( 'credit_card_js' );
		wp_localize_script( 'credit_card_js', 'phpVars', $this->load_variables() );

		echo $this->load_cc_template();
	}

	/**
	 * Return request data for the credit card form
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 * @since 1.0.0
	 */
	public function get_request_data_credit_card() {
		$vault_token         = $_POST['vault_token'];
		$token_id            = sanitize_text_field( $vault_token );
		$order_id            = WC()->session->get( 'wirecard_order_id' );
		$config              = $this->create_payment_config();
		$transaction_service = new TransactionService( $config );
		$lang                = $this->determine_user_language();
		$order               = wc_get_order( $order_id );

		$this->payment_action = $this->get_option( 'payment_action' );
		$this->transaction    = new CreditCardTransaction();

		parent::process_payment( $order_id );

		// Only set ThreeD here if no Non-3D maid is provided to keep maid logic from sdk
		if ( $this->force_three_d ) {
			$this->transaction->setThreeD( $this->force_three_d );
		}
		$this->transaction->setConfig( $config->get( CreditCardTransaction::NAME ) );

		// Add token_id if oneclick vaulted card used
		if ( $token_id ) {
			$this->transaction->setTokenId( $token_id );
		}

		$this->set_three_ds_transaction_fields( $order, $token_id );

		wp_send_json_success(
			$transaction_service->getCreditCardUiWithData(
				$this->transaction,
				self::PAYMENT_ACTIONS[ $this->payment_action ],
				$lang
			)
		);

		wp_die();
	}

	/**
	 * Set 3DS fields for transaction
	 *
	 * @param WC_Order $order
	 * @param string $token_id
	 * @since 2.1.0
	 */
	private function set_three_ds_transaction_fields( $order, $token_id = null ) {
		$challenge_ind = $this->get_option( 'challenge_indicator' );

		// Set 3DS fields within transaction
		$three_ds_helper   = new Three_DS_Helper( $order, $this->transaction, $challenge_ind, $token_id );
		$this->transaction = $three_ds_helper->get_three_ds_transaction();
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
		WC()->session->set( 'wirecard_order_id', $order_id );
		$order = wc_get_order( $order_id );

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}

	/**
	 * @return void
	 * @since 1.0.0
	 */
	public function execute_payment() {
		if ( wp_verify_nonce( $_POST['cc_nonce'] ) ) {
			$config   = $this->create_payment_config();
			$order_id = WC()->session->get( 'wirecard_order_id' );
			$order    = wc_get_order( $order_id );

			$this->payment_action = $this->get_option( 'payment_action' );

			wp_send_json_success( $this->execute_transaction( $this->transaction, $config, $this->payment_action, $order, $_POST ) );
			wp_die();
		}
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
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
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
		$token    = sanitize_text_field( $_POST['token'] );
		$mask_pan = sanitize_text_field( $_POST['mask_pan'] );
		/** @var WP_User $user */
		$user         = wp_get_current_user();
		$address_data = $this->get_address_data_from_current_order();
		$vault_data   = new Vault_Data( $user->ID, $mask_pan, $token, $address_data->get_hash() );
		if ( $this->vault->save_card( $vault_data ) ) {
			wp_send_json_success();
			wp_die();
		}
		wp_send_json_error();
		wp_die();
	}

	/**
	 * Get Credit Cards from Vault
	 *
	 * @since 1.1.0
	 */
	public function get_cc_from_vault() {
		if ( $this->get_option( 'cc_vault_enabled' ) !== 'yes' ) {
			wp_send_json_success( false );
		}

		/** @var WP_User $user */
		$user = wp_get_current_user();
		$this->render_card_template_by_user( $user->ID );
	}

	/**
	 * Render vault table for specific user
	 * @param int $user_id
	 *
	 * @since 3.3.4
	 */
	private function render_card_template_by_user( $user_id ) {
		wp_send_json_success(
			$this->vault->get_cards_for_user(
				(int) $user_id,
				$this->get_address_data_from_current_order()
			)
		);
		wp_die();
	}

	/**
	 * Remove Credit Card from Vault
	 *
	 * @since 1.1.0
	 */
	public function remove_cc_from_vault() {
		$vault_id = sanitize_text_field( $_POST['vault_id'] );

		if ( ! empty( $vault_id ) && $this->vault->delete_credit_card( $vault_id ) > 0 ) {
			/** @var WP_User $user */
			$user = wp_get_current_user();
			$this->render_card_template_by_user( $user->ID );
		}
		wp_send_json_error();
		wp_die();
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

		if ( $this->vault->has_cards_for_user_address( $user->ID, $this->get_address_data_from_current_order() ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Determines the language to use for the seamless credit card form.
	 *
	 * @return string
	 *
	 * @since 2.0.1 Only take first set of language code to avoid issues with de_DE_formal
	 * @since 2.0.0 Exchange hpp with wpp languages
	 * @since 1.7.0
	 */
	protected function determine_user_language() {
		$locale    = explode( '_', get_locale() );
		$locale    = reset( $locale );
		$language  = 'en';
		$converter = new WppVTwoConverter();

		try {
			$converter->init();
			$language = $converter->convert( $locale );
		} catch ( Exception $e ) {
			$this->logger->error( $e->getMessage() );
			// Return fallback in errorcase
			return $language;
		}

		return $language;
	}

	/**
	 * Gets a displayable spinner for the frontend
	 *
	 * @return string
	 *
	 * @since 2.0.0 Move template out of class
	 * @since 1.7.0
	 */
	protected function get_spinner() {
		return $this->template_helper->get_template_as_string( 'spinner.php' );
	}

	/**
	 * Gets the HTML required to display the vault functionality.
	 *
	 * @return string
	 *
	 * @since 2.0.0 Move template out of class
	 * @since 1.7.0
	 */
	protected function get_vault_html() {
		return $this->template_helper->get_template_as_string( 'credit-card-vault.php' );
	}

	/**
	 * Gets the HTML required to display the seamless credit card form.
	 *
	 * @return string
	 *
	 * @since 2.0.0 Move template out of class
	 * @since 1.7.0
	 */
	protected function get_creditcard_form_html() {
		return $this->template_helper->get_template_as_string( 'credit-card-form.php' );
	}

	/**
	 * Gets the submit button for the seamless credit card form.
	 *
	 * @return string
	 *
	 * @since 2.0.0 Move template out of class
	 * @since 1.7.0
	 */
	protected function get_creditcard_submit_html() {
		return $this->template_helper->get_template_as_string( 'credit-card-submit.php' );
	}

	/**
	 * @return string
	 *
	 * @since 2.0.0 Move template out of class
	 * @since 1.7.0
	 */
	protected function get_save_for_later_html() {
		return $this->template_helper->get_template_as_string( 'credit-card-save-for-later.php' );
	}

	/**
	 * Loads all required scripts for the form rendering.
	 *
	 * @since 1.7.0
	 */
	protected function enqueue_scripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_style( 'basic_style' );
		wp_enqueue_script( 'jquery_ui' );
		wp_enqueue_style( 'jquery_ui_style' );
		wp_enqueue_script( 'page_loader' );
	}

	/**
	 * If the url configuration is mixed
	 * add a notification for the admin
	 *
	 * @since 2.0.0
	 */
	public function validate_url_configuration() {
		$admin_notifications = new Admin_Message();
		$message             = __( 'warning_credit_card_url_mismatch', 'wirecard-woocommerce-extension' );

		if ( ! $this->is_url_configuration_valid() ) {
			$admin_notifications->add_gateway_admin_notice__warning( $message );
		}

		return;
	}

	/**
	 * Scenarios checked in this method
	 * base_url and wpp_url both contain "test"        = valid
	 * base_url and wpp_url both do not contain "test" = valid
	 * only base_url or wpp_url contains "test"        = invalid
	 *
	 * The information is used to check the possibility
	 * of a mixed configuration (production and test)
	 *
	 * @return bool
	 *
	 * @since 2.0.0
	 */
	protected function is_url_configuration_valid() {
		$base_url = (string) $this->get_option( 'base_url' );
		$wpp_url  = (string) $this->get_option( 'wpp_url' );
		$needle   = 'test';

		/** @var bool $base_url_contains_test */
		$base_url_contains_test = $this->string_contains_substring( $base_url, $needle );
		/** @var bool $wpp_url_contains_test */
		$wpp_url_contains_test = $this->string_contains_substring( $wpp_url, $needle );

		if ( $base_url_contains_test === $wpp_url_contains_test ) {
			return true;
		}

		return false;
	}

	/**
	 * @param string $string
	 * @param string $needle
	 *
	 * @return bool
	 *
	 * @since 2.0.0
	 */
	protected function string_contains_substring( $string, $needle ) {
		if ( stripos( $string, $needle ) === false ) {
			return false;
		}

		return true;
	}

	/**
	 * Get address data generated from WC_Order
	 * @return Address_Data
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 * @since 3.3.4
	 */
	public function get_address_data_from_current_order() {
		if ( null === $this->current_address_data ) {
			$this->current_address_data = Address_Data::from_wc_order(
				wc_get_order(
					WC()->session->get( 'wirecard_order_id' )
				)
			);
		}
		return $this->current_address_data;
	}
}
