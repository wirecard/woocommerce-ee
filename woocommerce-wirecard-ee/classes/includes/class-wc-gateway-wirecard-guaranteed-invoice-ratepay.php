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

use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction;
use Wirecard\PaymentSdk\Entity\Basket;


/**
 * Class WC_Gateway_Wirecard_Guaranteed_Invoice_Ratepay
 *
 * @extends WC_Wirecard_Payment_Gateway
 *
 * @since   1.1.0
 */
class WC_Gateway_Wirecard_Guaranteed_Invoice_Ratepay extends WC_Wirecard_Payment_Gateway {

	/**
	 * WC_Gateway_Wirecard_Guaranteed_Invoice_Ratepay constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->type               = 'ratepay-invoice';
		$this->id                 = 'wirecard_ee_invoice';
		$this->icon               = WOOCOMMERCE_GATEWAY_WIRECARD_URL . 'assets/images/invoice.png';
		$this->method_title       = __( 'Wirecard Guaranteed Invoice', 'wooocommerce-gateway-wirecard' );
		$this->method_name        = __( 'Guaranteed Invoice', 'wooocommerce-gateway-wirecard' );
		$this->method_description = __( 'Guaranteed Invoice transactions via Wirecard Payment Processing Gateway', 'woocommerce-gateway-wirecard' );
		$this->has_fields         = true;

		$this->supports = array(
			'products',
			'refunds',
		);

		$this->cancel         = array( 'authorization' );
		$this->capture        = array( 'authorization' );
		$this->refund         = array( 'capture-authorization' );
		$this->payment_action = 'reserve';

		$this->init_form_fields();
		$this->init_settings();

		$this->title   = $this->get_option( 'title' );
		$this->enabled = $this->get_option( 'enabled' );

		$this->additional_helper = new Additional_Information();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_after_checkout_validation', 'validate', 10, 2 );

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
				'title'       => __( 'Minimum Amount', 'woocommerce-gateway-wirecard' ),
				'description' => __( 'Amount in default shop currency', 'woocommerce-gateway-wirecard' ),
				'default'     => 20,
			),
			'max_amount'            => array(
				'title'       => __( 'Maximum Amount', 'woocommerce-gateway-wirecard' ),
				'description' => __( 'Amount in default shop currency', 'woocommerce-gateway-wirecard' ),
				'default'     => 3500,
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

		if ( ! $this->validate_date_of_birth( $_POST['invoice_date_of_birth'] ) ) {
			return false;
		}
		$this->transaction = new RatepayInvoiceTransaction();
		parent::process_payment( $order_id );
		//var_dump($this->additional_helper->create_shopping_basket( $this->transaction, $order->get_total() ));die();

		$this->transaction->setOrderNumber( $order_id );
		$this->transaction->setBasket( $this->additional_helper->create_shopping_basket( $this->transaction, $order->get_total() ) );
		$this->transaction->setAccountHolder(
			$this->additional_helper->create_account_holder(
				$order,
				'billing',
				new \DateTime( $_POST['invoice_date_of_birth'] )
			)
		);

		$ident  = WC()->session->get( 'ratepay_device_ident' );
		$device = new \Wirecard\PaymentSdk\Entity\Device();
		$device->setFingerprint( $ident );
		$this->transaction->setDevice( $device );

		return $this->execute_transaction( $this->transaction, $this->config, $this->payment_action, $order );
	}

	/**
	 * Create transaction for cancel
	 *
	 * @param int        $order_id
	 * @param float|null $amount
	 *
	 * @return RatepayInvoiceTransaction
	 *
	 * @since 1.1.0
	 */
	public function process_cancel( $order_id, $amount = null ) {
		$order = wc_get_order( $order_id );

		$transaction = new RatepayInvoiceTransaction();
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
	 * @return RatepayInvoiceTransaction
	 *
	 * @since 1.1.0
	 */
	public function process_capture( $order_id, $amount = null ) {
		/** @var WC_Order $order */
		$order = wc_get_order( $order_id );

		$basket      = new Basket();
		$transaction = new RatepayInvoiceTransaction();

		$transaction->setParentTransactionId( $order->get_transaction_id() );
		$transaction->setAmount( new Amount( $amount, $order->get_currency() ) );

		$basket = $this->additional_helper->create_basket_from_order(
			$order->get_items(),
			$basket,
			$transaction,
			$order->get_shipping_total(),
			$order->get_shipping_tax(),
			$order->get_total()
		);
		$transaction->setBasket( $basket );

		return $transaction;
	}

	/**
	 * Create transaction for refund
	 *
	 * @param int        $order_id
	 * @param float|null $amount
	 * @param string $reason
	 *
	 * @return bool|RatepayInvoiceTransaction|WP_Error
	 *
	 * @since 1.1.0
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		/** @var WC_Order $order */
		$order = wc_get_order( $order_id );

		$basket            = new Basket();
		$this->transaction = new RatepayInvoiceTransaction();
		parent::process_refund( $order_id, $amount, '' );

		$this->transaction->setParentTransactionId( $order->get_transaction_id() );
		$this->transaction->setAmount( new Amount( $amount, $order->get_currency() ) );

		$basket = $this->additional_helper->create_basket_from_order(
			$order->get_items(),
			$basket,
			$this->transaction,
			$order->get_shipping_total(),
			$order->get_shipping_tax(),
			$order->get_total()
		);
		$this->transaction->setBasket( $basket );
		$config = $this->create_payment_config();
		return $this->execute_refund( $this->transaction, $config, $order );
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
	 * Check if all criteria are fulfilled
	 *
	 * @return bool
	 * @since 1.1.0
	 */
	public function is_available() {
		if ( parent::is_available() ) {
			global $woocommerce;
			$customer = $woocommerce->customer;

			$cart = new WC_Cart();
			$cart->get_cart_from_session();

			if ( ! in_array( get_woocommerce_currency(), $this->get_option( 'allowed_currencies' ) ) ||
				! $this->validate_cart_amounts( floatval( $cart->get_total( 'total' ) ) ) ||
				! $this->validate_cart_products( $cart ) ||
				! $this->validate_billing_shipping_address( $customer ) ||
				! $this->validate_countries( $customer ) ) {
				return false;
			}
			return true;
		}
		return false;
	}

	/**
	 * Add payment fields to payment method
	 *
	 * @since 1.1.0
	 */
	public function payment_fields() {
		$html  = $this->create_ratepay_script();
		$html .= '<p class="form-row form-row-wide validate-required">
		<label for="invoice_dateofbirth" class="">' . __( 'Date of birth', 'woocommerce-gateway-wirecard' ) . '
		<abbr class="required" title="required">*</abbr></label>
		<input class="input-text " name="invoice_date_of_birth" id="invoice_date_of_birth" placeholder="" type="date">
		</p>';

		echo $html;
	}

	/**
	 * Validate date of birth
	 *
	 * @param string $date
	 * @return bool
	 */
	public function validate_date_of_birth( $date ) {
		$birth_day  = new \DateTime( $date );
		$difference = $birth_day->diff( new \DateTime() );
		$age        = $difference->format( '%y' );
		if ( $age < 18 ) {
			wc_add_notice( __( 'You need to be older then 18 to order.', 'woocommerce-gateway-wirecard' ), 'error' );
			return false;
		}
		return true;
	}

	/**
	 * Check the cart for digital items
	 *
	 * @param WC_Cart $cart
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
		if ( $this->get_option( 'billing_shipping_same' ) == 'yes' ) {
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

	/**
	 * Create RatePay script
	 *
	 * @return string
	 * @since 1.1.0
	 */
	private function create_ratepay_script() {
		if ( null == WC()->session->get( 'ratepay_device_ident' ) ) {
			WC()->session->set( 'ratepay_device_ident', $this->create_device_ident() );
		}
		$device_ident = WC()->session->get( 'ratepay_device_ident' );

		return '<script language="JavaScript">
			var di = {t: "' . $device_ident . '", v: "WDWL", l: "Checkout"};
			</script>
				<script type="text/javascript" src="//d.ratepay.com/WDWL/di.js">
			</script>
			<noscript>
				<link rel="stylesheet" type="text/css" href="//d.ratepay.com/di.css?t=' . $device_ident . '&v=WDWL&l=Checkout" >
			</noscript>
			<object type="application/x-shockwave-flash" data="//d.ratepay.com/WDWL/c.swf" width="0" height="0">
			<param name="movie" value="//d.ratepay.com/WDWL/c.swf" />
			<param name="flashvars" value="t= ' . $device_ident . ' &v=WDWL"/>
			<param name="AllowScriptAccess" value="always"/>
			</object>';
	}

	/**
	 * Returns deviceIdentToken for ratepayscript
	 *
	 * @return string
	 * @since 1.1.0
	 */
	private function create_device_ident() {
		return md5( $this->get_option( 'merchant_account_id' ) . '_' . microtime() );
	}
}
