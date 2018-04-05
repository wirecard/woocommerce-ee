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
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction;


/**
 * Class WC_Gateway_Wirecard_Guaranteed_Invoice_Ratepay
 *
 * @extends WC_Wirecard_Payment_Gateway
 *
 * @since   1.1.0
 */
class WC_Gateway_Wirecard_Guaranteed_Invoice_Ratepay extends WC_Wirecard_Payment_Gateway {

	/**
	 * Payment type
	 *
	 * @since  1.1.0
	 * @access private
	 * @var string
	 */
	private $type;

	/**
	 * Additional helper for basket and risk management
	 *
	 * @since  1.1.0
	 * @access private
	 * @var Additional_Information
	 */
	private $additional_helper;

	/**
	 * WC_Gateway_Wirecard_Paypal constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->type               = 'invoice';
		$this->id                 = 'wirecard_ee_invoice';
		$this->icon               = WOOCOMMERCE_GATEWAY_WIRECARD_URL . 'assets/images/invoice.png';
		$this->method_title       = __( 'Wirecard Guaranteed Invoice', 'wooocommerce-gateway-wirecard' );
		$this->method_name        = __( 'Guaranteed Invoice', 'wooocommerce-gateway-wirecard' );
		$this->method_description = __( 'Guaranteed Invoice transactions via Wirecard Payment Processing Gateway', 'woocommerce-gateway-wirecard' );

		$this->supports = array(
			'products',
			'refunds',
		);

		$this->cancel  = array( 'authorization' );
		$this->capture = array( 'authorization' );
		$this->refund  = array( 'capture-authorization' );

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
		$countries_obj = new WC_Countries();
		$countries     = $countries_obj->__get( 'countries' );

		$this->form_fields = array(
			'enabled'               => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Wirecard Guaranteed Invoice', 'woocommerce-gateway-wirecard' ),
				'default' => 'no',
			),
			'title'                 => array(
				'title'       => __( 'Title', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-wirecard' ),
				'default'     => __( 'Wirecard Guaranteed Invoice', 'woocommerce-gateway-wirecard' ),
				'desc_tip'    => true,
			),
			'merchant_account_id'   => array(
				'title'   => __( 'Merchant Account ID', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => 'fa02d1d4-f518-4e22-b42b-2abab5867a84',
			),
			'secret'                => array(
				'title'   => __( 'Secret Key', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => 'dbc5a498-9a66-43b9-bf1d-a618dd399684',
			),
			'credentials'           => array(
				'title'       => __( 'Credentials', 'woocommerce-gateway-wirecard' ),
				'type'        => 'title',
				'description' => __( 'Enter your Wirecard credentials.', 'woocommerce-gateway-wirecard' ),
			),
			'base_url'              => array(
				'title'       => __( 'Base URL', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'The Wirecard base URL. (e.g. https://api.wirecard.com)' ),
				'default'     => 'https://api-test.wirecard.com',
				'desc_tip'    => true,
			),
			'http_user'             => array(
				'title'   => __( 'HTTP User', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => '70000-APITEST-AP',
			),
			'http_pass'             => array(
				'title'   => __( 'HTTP Password', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => 'qD2wzQ_hrc!8',
			),
			'advanced'              => array(
				'title'       => __( 'Advanced Options', 'woocommerce-gateway-wirecard' ),
				'type'        => 'title',
				'description' => '',
			),
			'payment_action'        => array(
				'type'    => 'hidden',
				'default' => 'reserve',
			),
			'billing_shipping_same' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'    => 'checkbox',
				'label'   => __( 'Billing/Shipping address must be identical', 'woocommerce-gateway-wirecard' ),
				'default' => 'yes',
			),
			'billing_countries'     => array(
				'title'          => __( 'Allowed billing countries', 'woocommerce-gateway-wirecard' ),
				'type'           => 'multiselect',
				'options'        => $countries,
				'default'        => array( 'AT', 'DE' ),
				'multiple'       => true,
				'select_buttons' => true,
			),
			'shipping_countries'    => array(
				'title'          => __( 'Allowed shipping countries', 'woocommerce-gateway-wirecard' ),
				'type'           => 'multiselect',
				'options'        => $countries,
				'default'        => array( 'AT', 'DE' ),
				'multiple'       => true,
				'select_buttons' => true,
			),
			'allowed_currencies'    => array(
				'title'          => __( 'Allowed currencies', 'woocommerce-gateway-wirecard' ),
				'type'           => 'multiselect',
				'options'        => get_woocommerce_currencies(),
				'default'        => array( 'EUR' ),
				'multiple'       => true,
				'select_buttons' => true,
			),
			'min_amount'            => array(
				'title'   => __( 'Minimum Amount', 'woocommerce-gateway-wirecard' ),
				'default' => 20,
			),
			'max_amount'            => array(
				'title'   => __( 'Maximum Amount', 'woocommerce-gateway-wirecard' ),
				'default' => 3500,
			),
			'shopping_basket'       => array(
				'type'    => 'hidden',
				'default' => 'yes',
			),
			'descriptor'            => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'    => 'checkbox',
				'label'   => __( 'Descriptor', 'woocommerce-gateway-wirecard' ),
				'default' => 'no',
			),
			'send_additional'       => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'    => 'checkbox',
				'label'   => __( 'Send additional information', 'woocommerce-gateway-wirecard' ),
				'default' => 'yes',
			),
		);
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

		$redirect_urls = new Redirect(
			$this->create_redirect_url( $order, 'success', $this->type ),
			$this->create_redirect_url( $order, 'cancel', $this->type ),
			$this->create_redirect_url( $order, 'failure', $this->type )
		);

		$config    = $this->create_payment_config();
		$amount    = new Amount( $order->get_total(), 'EUR' );
		$operation = $this->get_option( 'payment_action' );

		$transaction = new RatepayInvoiceTransaction();
		$transaction->setNotificationUrl( $this->create_notification_url( $order, $this->type ) );
		$transaction->setRedirect( $redirect_urls );
		$transaction->setAmount( $amount );

		$custom_fields = new CustomFieldCollection();
		$custom_fields->add( new CustomField( 'orderId', $order_id ) );
		$transaction->setCustomFields( $custom_fields );

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
	 * @since 1.1.0
	 */
	public function create_payment_config( $base_url = null, $http_user = null, $http_pass = null ) {
		if ( is_null( $base_url ) ) {
			$base_url  = $this->get_option( 'base_url' );
			$http_user = $this->get_option( 'http_user' );
			$http_pass = $this->get_option( 'http_pass' );
		}

		$config         = parent::create_payment_config( $base_url, $http_user, $http_pass );
		$payment_config = new PaymentMethodConfig( RatepayInvoiceTransaction::NAME, $this->get_option( 'merchant_account_id' ), $this->get_option( 'secret' ) );
		$config->add( $payment_config );

		return $config;
	}

	/**
	 * Check if all cretireiars are fullfiled
	 *
	 * @return bool
	 * @since 1.1.0
	 */
	public function is_availible() {
		if ( is_checkout() ) {
			global $woocommerce;
			$customer = $woocommerce->customer;

			$cart = new WC_Cart();
			$cart->get_cart_from_session();

			if ( ! in_array( get_woocommerce_currency(), $this->get_option( 'allowed_currencies' ) ) &&
				! $this->validate_cart_amounts( $cart->total ) &&
				! $this->validate_cart_products( $cart ) &&
				! $this->validate_billing_shipping_address( $customer ) &&
				! $this->validate_countries( $customer ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check the cart for digital items
	 *
	 * @param $cart
	 * @return bool
	 * @since 1.1.0
	 */
	private function validate_cart_products( $cart ) {
		foreach ( $cart->cart_contents as $hash => $item ) {
			$product = new WC_Product( $item['product_id'] );
			if ( $product->is_downloadable() || $product->is_virtual() ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check for the allowed countries
	 *
	 * @param WC_Customer $customer
	 * @return bool
	 * @since 1.1.0
	 */
	private function validate_countries( $customer ) {
		if ( ! in_array( $customer->get_shipping_country(), $this->get_option( 'shipping_countries' ) ) &&
		! empty( $customer->get_shipping_country() ) ) {
			return false;
		}

		if ( ! in_array( $customer->get_billing_country(), $this->get_option( 'billing_countries' ) ) &&
		! empty( $customer->get_billing_country() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check that the shipping and billing adresses are equal
	 *
	 * @param WC_Customer $customer
	 * @return bool
	 * @since 1.1.0
	 */
	private function validate_billing_shipping_address( $customer ) {
		$fields = array(
			'first_name',
			'last_name',
			'address_1',
			'address_2',
			'city',
			'country',
			'postcode',
			'state',
		);
		foreach ( $fields as $field ) {
			$billing  = 'get_billing_' . $field;
			$shipping = 'get_shipping_' . $field;

			if ( call_user_func( array( $customer, $billing ) ) != call_user_func( array( $customer, $shipping ) ) &&
			! empty( call_user_func( array( $customer, $shipping ) ) ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Validate cart amounts
	 *
	 * @param float $total
	 * @return bool
	 * @since 1.1.0
	 */
	private function validate_cart_amounts( $total ) {
		if ( $total <= floatval( $this->get_option( 'min_amount' ) ) ) {
			return false;
		}

		if ( $total >= floatval( $this->get_option( 'max_amount' ) ) ) {
			return false;
		}

		return true;
	}
}
