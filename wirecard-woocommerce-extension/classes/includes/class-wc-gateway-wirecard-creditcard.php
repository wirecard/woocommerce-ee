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
require_once( WIRECARD_EXTENSION_HELPER_DIR . 'class-credit-card-vault.php' );
require_once( WIRECARD_EXTENSION_HELPER_DIR . 'class-template-helper.php' );

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
	 * @var Template_Helper $template_helper
	 */
	protected $template_helper;

	/**
	 * WC_Gateway_Wirecard_Creditcard constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->additional_helper = new Additional_Information();
		$this->template_helper   = new Template_Helper();
		$this->supports          = array( 'products', 'refunds' );
		$this->cancel            = array( 'authorization' );
		$this->capture           = array( 'authorization' );
		$this->refund            = array( 'purchase', 'capture-authorization' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title   = $this->get_option( 'title' );
		$this->enabled = $this->get_option( 'enabled' );

		$this->init();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ), 999 );

		$this->add_payment_gateway_actions();
	}

	/**
	 * Called in constructor
	 * Initializes the class
	 *
	 * @since 2.0.0
	 */
	protected function init() {
		$this->type               = 'creditcard';
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

		add_action(
			'woocommerce_api_submit_token_response',
			array(
				$this,
				'execute_token_payment',
			)
		);
	}

	/**
	 * Load form fields for configuration
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'                     => array(
				'title'       => __( 'text_enable_disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'label'       => __( 'enable_heading_title_creditcard', 'wirecard-woocommerce-extension' ),
				'description' => __( 'config_status_desc_creditcard', 'wirecard-woocommerce-extension' ),
				'default'     => 'no',
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
				'default'     => '53f2895a-e4de-4e82-a813-0d87a10e55e6',
			),
			'secret'                      => array(
				'title'       => __( 'config_merchant_secret', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_merchant_secret_desc', 'wirecard-woocommerce-extension' ),
				'default'     => 'dbc5a498-9a66-43b9-bf1d-a618dd399684',
			),
			'three_d_merchant_account_id' => array(
				'title'       => __( 'config_three_d_merchant_account_id', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_three_d_merchant_account_id_desc', 'wirecard-woocommerce-extension' ),
				'default'     => '508b8896-b37d-4614-845c-26bf8bf2c948',
			),
			'three_d_secret'              => array(
				'title'       => __( 'config_three_d_merchant_secret', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_three_d_merchant_secret_desc', 'wirecard-woocommerce-extension' ),
				'default'     => 'dbc5a498-9a66-43b9-bf1d-a618dd399684',
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
				'default'     => 'https://api-test.wirecard.com',
			),
			'wpp_url'                     => array(
				'title'       => __( 'config_wpp_url', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_wpp_url_desc', 'wirecard-woocommerce-extension' ),
				'default'     => 'https://wpp-test.wirecard.com',
			),
			'http_user'                   => array(
				'title'       => __( 'config_http_user', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_http_user_desc', 'wirecard-woocommerce-extension' ),
				'default'     => '70000-APITEST-AP',
			),
			'http_pass'                   => array(
				'title'       => __( 'config_http_password', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_http_password_desc', 'wirecard-woocommerce-extension' ),
				'default'     => 'qD2wzQ_hrc!8',
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
	 * Create payment method Configuration
	 * @param string|null $base_url
	 * @param string|null $http_user
	 * @param string|null $http_pass
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

		$config              = parent::create_payment_config( $base_url, $http_user, $http_pass );
		$merchant_account_id = $this->get_option( 'merchant_account_id' );
		$secret              = $this->get_option( 'secret' );

		if ( '' === $merchant_account_id ) {
			$merchant_account_id = $this->get_option( 'three_d_merchant_account_id' );
			$secret              = $this->get_option( 'three_d_secret' );
		}
		$payment_config = new CreditCardConfig( $merchant_account_id, $secret );

		if ( $this->get_option( 'three_d_merchant_account_id' ) !== '' ) {
			$payment_config->setThreeDCredentials(
				$this->get_option( 'three_d_merchant_account_id' ),
				$this->get_option( 'three_d_secret' )
			);
		}

		$woocommerce_currency = $this->get_option( 'woocommerce_currency' );
		if ( ! strlen( $woocommerce_currency ) ) {
			$woocommerce_currency = get_woocommerce_currency();
		}

		if ( $this->get_option( 'ssl_max_limit' ) !== '' ) {
			$payment_config->addSslMaxLimit(
				new Amount(
					floatval( $this->get_option( 'ssl_max_limit' ) ),
					$woocommerce_currency
				)
			);
		}

		if ( $this->get_option( 'three_d_min_limit' ) !== '' ) {
			$payment_config->addThreeDMinLimit(
				new Amount(
					floatval( $this->get_option( 'three_d_min_limit' ) ),
					$woocommerce_currency
				)
			);
		}

		$config->add( $payment_config );

		return $config;
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
			[ 'wc-api' => 'get_credit_card_request_data' ],
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);
		$submit_url       = add_query_arg(
			[ 'wc-api' => 'submit_creditcard_response' ],
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);
		$token_url        = add_query_arg(
			[ 'wc-api' => 'submit_token_response' ],
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
		$html = '<h2 class="credit-card-heading">' . __( 'heading_creditcard_form', 'wirecard-woocommerce-extension' ) . '</h2>';

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
		wp_localize_script( 'credit_card_js', 'php_vars', $this->load_variables() );

		echo $this->load_cc_template();
	}

	/**
	 * Return request data for the credit card form
	 *
	 * @since 1.0.0
	 */
	public function get_request_data_credit_card() {
		$order_id            = WC()->session->get( 'wirecard_order_id' );
		$config              = $this->create_payment_config();
		$transaction_service = new TransactionService( $config );
		$lang                = $this->determine_user_language();

		$this->payment_action = $this->get_option( 'payment_action' );
		$this->transaction    = new CreditCardTransaction();

		parent::process_payment( $order_id );

		$this->transaction->setTermUrl( $this->create_redirect_url( wc_get_order( $order_id ), 'success', $this->type ) );
		$this->transaction->setConfig( $config->get( CreditCardTransaction::NAME ) );

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
	 * @return void
	 * @since 1.7.0
	 */
	public function execute_token_payment() {
		if ( wp_verify_nonce( $_POST['cc_nonce'] ) ) {
			$config   = $this->create_payment_config();
			$order_id = WC()->session->get( 'wirecard_order_id' );
			$order    = wc_get_order( $order_id );
			$token_id = sanitize_text_field( $_POST['vault_token'] );

			$this->payment_action = $this->get_option( 'payment_action' );

			if ( $token_id ) {
				$this->transaction = new CreditCardTransaction();

				parent::process_payment( $order_id );

				$this->transaction->setTokenId( $token_id );
				$this->transaction->setTermUrl( $this->create_redirect_url( $order, 'success', $this->type ) );
				$this->transaction->setConfig( $config->get( CreditCardTransaction::NAME ) );

				wp_send_json_success( $this->execute_transaction( $this->transaction, $config, $this->payment_action, $order ) );
				wp_die();
			}
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
		$user = wp_get_current_user();

		if ( $this->vault->save_card( $user->ID, $token, $mask_pan ) ) {
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

		wp_send_json_success( $this->vault->get_cards_for_user( $user->ID ) );
		wp_die();
	}

	/**
	 * Remove Credit Card from Vault
	 *
	 * @since 1.1.0
	 */
	public function remove_cc_from_vault() {
		$vault_id = sanitize_text_field( $_POST['vault_id'] );

		if ( isset( $vault_id ) && $this->vault->delete_credit_card( $vault_id ) > 0 ) {
			$user = wp_get_current_user();
			wp_send_json_success( $this->vault->get_cards_for_user( $user->ID ) );
			wp_die();
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

		if ( $this->vault->get_cards_for_user( $user->ID ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Determines the best language to use for the seamless credit card form.
	 *
	 * @return string
	 * @since 2.0.0 Exchange hpp with wpp languages
	 * @since 1.7.0
	 */
	protected function determine_user_language() {
		$language = null;
		//try {
		//$converter = new WppVTwoConverter();
		//$language = $converter->convert( get_locale() );
		//} catch ( Exception $e ) {
		//wp_send_json_error( $e->getMessage() );
		//wp_die();
		//}
		//return $language;

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
}
