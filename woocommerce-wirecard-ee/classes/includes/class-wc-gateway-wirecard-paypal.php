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
require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/helper/class-additional-information.php' );

use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use Wirecard\PaymentSdk\TransactionService;

/**
 * Class WC_Gateway_Wirecard_Paypal
 *
 * @extends WC_Wirecard_Payment_Gateway
 *
 * @since   1.0.0
 */
class WC_Gateway_Wirecard_Paypal extends WC_Wirecard_Payment_Gateway {

	private $additional_helper;

	public function __construct() {
		$this->id                 = 'woocommerce_wirecard_paypal';
		$this->icon               = WOOCOMMERCE_GATEWAY_WIRECARD_URL . 'assets/images/paypal.png';
		$this->method_title       = __( 'Wirecard Payment Processing Gateway PayPal', 'wooocommerce-gateway-wirecard' );
		$this->method_description = __( 'PayPal transactions via Wirecard Payment Processing Gateway', 'woocommerce-gateway-wirecard' );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		$this->title   = $this->get_option( 'title' );
		$this->enabled = $this->get_option( 'enabled' );

		$this->additional_helper = new Additional_Information();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'wp_ajax_test_credentials' . $this->id, array( $this, 'test_credentials' ) );

		parent::add_payment_gateway_actions();
	}

	/**
	 * Load form fields for configuration
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'             => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Wirecard Payment Processing Gateway PayPal', 'woocommerce-gateway-wirecard' ),
				'default' => 'yes',
			),
			'title'               => array(
				'title'       => __( 'Title', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-wirecard' ),
				'default'     => __( 'Wirecard Payment Processing Gateway PayPal', 'woocommerce-gateway-wirecard' ),
				'desc_tip'    => true,
			),
			'merchant_account_id' => array(
				'title'   => __( 'Merchant Account ID', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => '2a0e9351-24ed-4110-9a1b-fd0fee6bec26',
			),
			'secret'              => array(
				'title'   => __( 'Secret Key', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => 'dbc5a498-9a66-43b9-bf1d-a618dd399684',
			),
			'credentials'         => array(
				'title'       => __( 'Credentials', 'woocommerce-gateway-wirecard' ),
				'type'        => 'title',
				'description' => __( 'Enter your Wirecard Processing Payment Gateway credentials and test it.', 'woocommerce-gateway-wirecard' ),
			),
			'base_url'            => array(
				'title'       => __( 'Base Url', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'The elastic engine base url. (e.g. https://api.wirecard.com)' ),
				'default'     => 'https://api-test.wirecard.com',
				'desc_tip'    => true,
			),
			'http_user'           => array(
				'title'   => __( 'Http User', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => '70000-APITEST-AP',
			),
			'http_pass'           => array(
				'title'   => __( 'Http Password', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => 'qD2wzQ_hrc!8',
			),
			'credentials_button'  => array(
				'type' => 'credentials_button',
			),
			'advanced'            => array(
				'title'       => __( 'Advanced options', 'woocommerce-gateway-wirecard' ),
				'type'        => 'title',
				'description' => '',
			),
			'payment_action'      => array(
				'title'   => __( 'Payment Action', 'woocommerce-gateway-wirecard' ),
				'type'    => 'select',
				'default' => 'Authorization',
				'label'   => __( 'Payment Action', 'woocommerce-gateway-wirecard' ),
				'options' => array(
					'reserve' => 'Authorization',
					'pay'     => 'Capture',
				),
			),
			'shopping_basket'     => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'    => 'checkbox',
				'label'   => __( 'Shopping Basket', 'woocommerce-gateway-wirecard' ),
				'default' => 'no',
			),
			'descriptor'          => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'    => 'checkbox',
				'label'   => __( 'Descriptor', 'woocommerce-gateway-wirecard' ),
				'default' => 'no',
			),
			'send_additional'     => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'    => 'checkbox',
				'label'   => __( 'Send additional information', 'woocommerce-gateway-wirecard' ),
				'default' => 'yes',
			),
		);
	}

	/**
	 * Create test credentials button (in progress)
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function generate_credentials_button_html() {
		ob_start();
		?>
		<a class="test button" href="#" onclick="testCredentials();return false;"><?php _e( 'Test credentials', 'woocommerce-wirecard-gateway' ); ?></a>
		<script type="text/javascript">
			function testCredentials() {
				var baseUrl = jQuery('#woocommerce_woocommerce_wirecard_paypal_base_url').val();
				var httpUser = jQuery('#woocommerce_woocommerce_wirecard_paypal_http_user').val();
				var httpPass = jQuery('#woocommerce_woocommerce_wirecard_paypal_http_pass').val();
				var data = {
					'base_url' : baseUrl,
					'http_user': httpUser,
					'http_pass': httpPass
				};
				jQuery.ajax({
					type   : 'post',
					action : 'test_credentials',
					data   : {
						'action': 'test_credentials',
						'data'  : data
					},
					success: function (response) {
						alert(response);
					}
				});
			}
		</script>
		<?php
		return ob_get_clean();
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

		$redirect_urls = new Redirect(
			$this->create_redirect_url( $order, 'success' ),
			$this->create_redirect_url( $order, 'cancel' )
		);

		$config    = $this->create_payment_config();
		$amount    = new Amount( $order->get_total(), 'EUR' );
		$operation = $this->get_option( 'payment_action' );

		$transaction = new PayPalTransaction();
		$transaction->setNotificationUrl( $this->create_notification_url() );
		$transaction->setRedirect( $redirect_urls );
		$transaction->setAmount( $amount );

		if ( $this->get_option( 'shopping_basket' ) == 'yes' ) {
			$basket = $this->additional_helper->create_shopping_basket( $order, $transaction );
			$transaction->setBasket( $basket );
		}

		if ( $this->get_option( 'descriptor' ) == 'yes' ) {
			$transaction->setDescriptor( $this->additional_helper->create_descriptor( $order ) );
		}

		if ( $this->get_option( 'send_additional' ) == 'yes' ) {
			$this->additional_helper->set_additional_information( $order, $transaction );
		}

		return $this->execute_transaction( $transaction, $config, $operation, $order, $order_id );
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
		if ( $base_url != null ) {
			$base_url      = $this->get_option( 'base_url' );
			$http_user     = $this->get_option( 'http_user' );
			$http_password = $this->get_option( 'http_pass' );
		}

		$config         = new Config( $base_url, $http_user, $http_password, 'EUR' );
		$payment_config = new PaymentMethodConfig( PayPalTransaction::NAME, $this->get_option( 'merchant_account_id' ), $this->get_option( 'secret' ) );
		$config->add( $payment_config );

		return $config;
	}

	/**
	 * Test current configuration before save
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function test_credentials() {
		$logger = new WC_Logger();
		$logger->error( 'test credentials' );
		if ( isset( $_POST['base_url'] ) ) {
			$config              = $this->create_payment_config( $_POST['base_url'], $_POST['http_user'], $_POST['http_pass'] );
			$transaction_service = new TransactionService( $config );
			$check               = $transaction_service->checkCredentials();
			return $check;
		}
		return false;
	}
}
