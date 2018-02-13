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
use Wirecard\PaymentSdk\Config\CreditCardConfig;
use Wirecard\PaymentSdk\Entity\Amount;

/**
 * Class WC_Gateway_Wirecard_CreditCard
 *
 * @extends WC_Wirecard_Payment_Gateway
 *
 * @since   1.0.0
 */
class WC_Gateway_Wirecard_Creditcard extends WC_Wirecard_Payment_Gateway {

	public function __construct() {
		$this->id                 = 'woocommerce_wirecard_creditcard';
		$this->method_title       = __( 'Wirecard Payment Processing Gateway Credit Card', 'wooocommerce-gateway-wirecard' );
		$this->method_description = __( 'Credit Card transactions via Wirecard Payment Processing Gateway', 'woocommerce-gateway-wirecard' );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		$this->title   = $this->get_option( 'title' );
		$this->enabled = $this->get_option( 'enabled' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Load form fields for configuration
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled'                     => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Wirecard Payment Processing Gateway Credit Card', 'woocommerce-gateway-wirecard' ),
				'default' => 'yes',
			),
			'title'                       => array(
				'title'       => __( 'Title', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-wirecard' ),
				'default'     => __( 'Wirecard Payment Processing Gateway Credit Card', 'woocommerce-gateway-wirecard' ),
				'desc_tip'    => true,
			),
			'description'                 => array(
				'title'   => __( 'Customer Message', 'woocommerce-gateway-wirecard' ),
				'type'    => 'textarea',
				'default' => '',
			),
			'base_url'                    => array(
				'title'       => __( 'Base Url', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'The elastic engine base url. (e.g. https://api.wirecard.com)' ),
				'default'     => 'https://api-test.wirecard.com',
				'desc_tip'    => true,
			),
			'http_user'                   => array(
				'title'   => __( 'Http User', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => '70000-APITEST-AP',
			),
			'http_pass'                   => array(
				'title'   => __( 'Http Password', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => 'qD2wzQ_hrc!8',
			),
			'merchant_account_id'         => array(
				'title'   => __( 'Merchant Account ID', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => '53f2895a-e4de-4e82-a813-0d87a10e55e6',
			),
			'secret'                      => array(
				'title'   => __( 'Secret Key', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => 'dbc5a498-9a66-43b9-bf1d-a618dd399684',
			),
			'three_d_merchant_account_id' => array(
				'title'   => __( '3-D Secure Merchant Account ID', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => '508b8896-b37d-4614-845c-26bf8bf2c948',
			),
			'three_d_secret'              => array(
				'title'   => __( '3-D Secure Secret Key', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => 'dbc5a498-9a66-43b9-bf1d-a618dd399684',
			),
			'ssl_max_limit'               => array(
				'title'   => __( 'Non 3-D Secure Max Limit', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => '100.0',
			),
			'three_d_min_limit'           => array(
				'title'   => __( '3-D Secure Min Limit', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => '50.0',
			),
			'payment_action'              => array(
				'title'   => __( 'Payment Action', 'woocommerce-gateway-wirecard' ),
				'type'    => 'select',
				'default' => 'Authorization',
				'label'   => __( 'Payment Action', 'woocommerce-gateway-wirecard' ),
				'options' => array(
					'authorization' => 'Authorization',
					'capture'       => 'Capture',
				),
			),
			'shopping_basket'             => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'    => 'checkbox',
				'label'   => __( 'Shopping Basket', 'woocommerce-gateway-wirecard' ),
				'default' => 'no',
			),
			'descriptor'                  => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'    => 'checkbox',
				'label'   => __( 'Descriptor', 'woocommerce-gateway-wirecard' ),
				'default' => 'no',
			),
			'send_additional'             => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'    => 'checkbox',
				'label'   => __( 'Send additional information', 'woocommerce-gateway-wirecard' ),
				'default' => 'yes',
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
	public function create_payment_config() {

		$config         = new Config(
			$this->get_option( 'base_url' ),
			$this->get_option( 'http_user' ),
			$this->get_option( 'http_pass' ),
			'EUR'
		);

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
			$payment_config->addSslMaxLimit(new Amount(
				$this->get_option( 'ssl_max_limit' ),
				'EUR'
			));
		}

		if ( $this->get_option( 'three_d_min_limit' ) !== '' ) {
			$payment_config->addSslMaxLimit(new Amount(
				$this->get_option( 'three_d_min_limit' ),
				'EUR'
			));
		}

		$config->add( $payment_config );

		return $config;
	}

}
