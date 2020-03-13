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
		$this->icon               = WIRECARD_EXTENSION_URL . 'assets/images/invoice.png';
		$this->method_title       = __( 'heading_title_ratepayinvoice', 'wirecard-woocommerce-extension' );
		$this->method_name        = __( 'ratepayinvoice', 'wirecard-woocommerce-extension' );
		$this->method_description = __( 'ratepayinvoice_desc', 'wirecard-woocommerce-extension' );
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

		parent::add_payment_gateway_actions();
	}

	/**
	 * Load form fields for configuration
	 *
	 * @since 1.1.0
	 */
	public function init_form_fields() {
		parent::init_form_fields();
		$countries_obj = new WC_Countries();
		$countries     = $countries_obj->__get( 'countries' );

		$this->form_fields = array(
			'enabled'               => array(
				'title'       => __( 'text_enable_disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'description' => __( 'config_status_desc_ratepayinvoice', 'wirecard-woocommerce-extension' ),
				'label'       => __( 'enable_heading_title_ratepayinvoice', 'wirecard-woocommerce-extension' ),
				'default'     => 'no',
			),
			'title'                 => array(
				'title'       => __( 'config_title', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_title_desc', 'wirecard-woocommerce-extension' ),
				'default'     => __( 'heading_title_ratepayinvoice', 'wirecard-woocommerce-extension' ),
			),
			'merchant_account_id'   => array(
				'title'       => __( 'config_merchant_account_id', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_merchant_account_id_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getMerchantAccountId(),
			),
			'secret'                => array(
				'title'       => __( 'config_merchant_secret', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_merchant_secret_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getSecret(),
			),
			'credentials'           => array(
				'title'       => __( 'text_credentials', 'wirecard-woocommerce-extension' ),
				'type'        => 'title',
				'description' => __( 'text_credentials_desc', 'wirecard-woocommerce-extension' ),
			),
			'base_url'              => array(
				'title'       => __( 'config_base_url', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_base_url_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getBaseUrl(),
			),
			'http_user'             => array(
				'title'       => __( 'config_http_user', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_http_user_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getHttpUser(),
			),
			'http_pass'             => array(
				'title'       => __( 'config_http_password', 'wirecard-woocommerce-extension' ),
				'type'        => 'text',
				'description' => __( 'config_http_password_desc', 'wirecard-woocommerce-extension' ),
				'default'     => $this->credential_config->getHttpPassword(),
			),
			'test_button'           => array(
				'title'   => __( 'test_config', 'wirecard-woocommerce-extension' ),
				'type'    => 'button',
				'class'   => 'wc_wirecard_test_credentials_button button-primary',
				'default' => __( 'test_credentials', 'wirecard-woocommerce-extension' ),
			),
			'advanced'              => array(
				'title'       => __( 'text_advanced', 'wirecard-woocommerce-extension' ),
				'type'        => 'title',
				'description' => '',
			),
			'billing_shipping_same' => array(
				'title'       => __( 'text_enable_disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'description' => __( 'config_billing_shipping_desc', 'wirecard-woocommerce-extension' ),
				'label'       => __( 'config_billing_shipping', 'wirecard-woocommerce-extension' ),
				'default'     => 'yes',
			),
			'billing_countries'     => array(
				'title'          => __( 'config_billing_countries', 'wirecard-woocommerce-extension' ),
				'type'           => 'multiselect',
				'description'    => __( 'config_billing_countries_desc', 'wirecard-woocommerce-extension' ),
				'options'        => $countries,
				'default'        => array( 'AT', 'DE' ),
				'multiple'       => true,
				'select_buttons' => true,
			),
			'shipping_countries'    => array(
				'title'          => __( 'config_shipping_countries', 'wirecard-woocommerce-extension' ),
				'type'           => 'multiselect',
				'description'    => __( 'config_shipping_countries_desc', 'wirecard-woocommerce-extension' ),
				'options'        => $countries,
				'default'        => array( 'AT', 'DE' ),
				'multiple'       => true,
				'select_buttons' => true,
			),
			'allowed_currencies'    => array(
				'title'          => __( 'config_allowed_currencies', 'wirecard-woocommerce-extension' ),
				'type'           => 'multiselect',
				'description'    => __( 'config_allowed_currencies_desc', 'wirecard-woocommerce-extension' ),
				'options'        => get_woocommerce_currencies(),
				'default'        => array( 'EUR' ),
				'multiple'       => true,
				'select_buttons' => true,
			),
			'min_amount'            => array(
				'title'       => __( 'config_basket_min', 'wirecard-woocommerce-extension' ),
				'description' => __( 'config_basket_min_desc', 'wirecard-woocommerce-extension' ),
				'default'     => 20,
			),
			'max_amount'            => array(
				'title'       => __( 'config_basket_max', 'wirecard-woocommerce-extension' ),
				'description' => __( 'config_basket_max_desc', 'wirecard-woocommerce-extension' ),
				'default'     => 3500,
			),
			'descriptor'            => array(
				'title'       => __( 'text_enable_disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'description' => __( 'config_descriptor_desc', 'wirecard-woocommerce-extension' ),
				'label'       => __( 'config_descriptor', 'wirecard-woocommerce-extension' ),
				'default'     => 'no',
			),
			'send_additional'       => array(
				'title'       => __( 'text_enable_disable', 'wirecard-woocommerce-extension' ),
				'type'        => 'checkbox',
				'description' => __( 'config_additional_info_desc', 'wirecard-woocommerce-extension' ),
				'label'       => __( 'config_additional_info', 'wirecard-woocommerce-extension' ),
				'default'     => 'yes',
			),
		);
	}

	/**
	 * Process payment gateway transactions
	 *
	 * @param int $order_id
	 *
	 * @return array|false
	 *
	 * @since 1.1.0
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! wp_verify_nonce( $_POST['ratepay_nonce'] ) ||
			! $this->validate_date_of_birth( $_POST['invoice_date_of_birth'] ) ||
			! $this->validate_consent( $_POST['invoice_data_protection'] ) ) {
			return false;
		}
		$this->transaction = new RatepayInvoiceTransaction();
		parent::process_payment( $order_id );

		$this->transaction->setOrderNumber( $order_id );
		$this->transaction->setBasket( $this->additional_helper->create_shopping_basket( $this->transaction ) );
		$this->transaction->setAccountHolder(
			$this->additional_helper->create_account_holder(
				$order,
				'billing',
				new \DateTime( sanitize_text_field( $_POST['invoice_date_of_birth'] ) )
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
		$order       = wc_get_order( $order_id );
		$config      = $this->create_payment_config();
		$transaction = new RatepayInvoiceTransaction();

		$basket = $this->additional_helper->create_basket_from_parent_transaction(
			$order,
			$config,
			RatepayInvoiceTransaction::NAME
		);

		$transaction->setParentTransactionId( $order->get_transaction_id() );
		$transaction->setBasket( $basket );

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
		$order       = wc_get_order( $order_id );
		$transaction = new RatepayInvoiceTransaction();
		$config      = $this->create_payment_config();

		$basket = $this->additional_helper->create_basket_from_parent_transaction(
			$order,
			$config,
			RatepayInvoiceTransaction::NAME
		);

		$transaction->setParentTransactionId( $order->get_transaction_id() );
		$transaction->setAmount( new Amount( $amount, $order->get_currency() ) );
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
	 * @since 1.1.0
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		/** @var WC_Order $order */
		$order         = wc_get_order( $order_id );
		$refund_basket = array();
		$config        = $this->create_payment_config();

		if ( $order->get_total() > $amount ) {
			$refund_items = json_decode( stripslashes( $_POST['line_item_qtys'] ) );
			$order_items  = $order->get_items();

			foreach ( $refund_items as $item_id => $quantity ) {
				if ( $quantity > 0 ) {
					$refund_basket[ $item_id ]['product'] = $order_items[ $item_id ]->get_product();
					$refund_basket[ $item_id ]['qty']     = $quantity;
				}
			}
		}
		$this->transaction = new RatepayInvoiceTransaction();
		$this->transaction->setParentTransactionId( $order->get_transaction_id() );
		$this->transaction->setAmount( new Amount( floatval( $amount ), $order->get_currency() ) );

		$basket = $this->additional_helper->create_basket_from_parent_transaction(
			$order,
			$config,
			RatepayInvoiceTransaction::NAME,
			$refund_basket,
			$amount
		);

		if ( is_wp_error( $basket ) ) {
			return $basket;
		}

		$this->transaction->setBasket( $basket );
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
			$cart     = $woocommerce->cart;

			if ( ! in_array( get_woocommerce_currency(), $this->get_option( 'allowed_currencies' ), true ) ||
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
		$html = $this->create_ratepay_script();

		$html .= '<p class="form-row form-row-wide validate-required">
		<label for="invoice_dateofbirth" class="">' . __( 'birthdate_input', 'wirecard-woocommerce-extension' ) . '
		<abbr class="required" title="required">*</abbr></label>
		<input class="input-text " name="invoice_date_of_birth" id="invoice_date_of_birth" placeholder="" type="date" />
		<input type="hidden" name="ratepay_nonce" value="' . wp_create_nonce() . '" />
		</p>';

		$html .= '
        <br />
        <p class="form-row form-row-wide validate-required">
        <div class="checkbox">
        <label for="invoice_data_protection">
        <input type="checkbox" name="invoice_data_protection" id="invoice_data_protection">&nbsp;'
		. __( 'text_terms_accept', 'wirecard-woocommerce-extension' ) .
		'<abbr class="required" title="required">*</abbr></label>
        </div>
        </p>
		';

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
			wc_add_notice( __( 'ratepayinvoice_fields_error', 'wirecard-woocommerce-extension' ), 'error' );
			return false;
		}
		return true;
	}

	/**
	 * Validates consent for sending data to Wirecard.
	 *
	 * @param string $consent
	 * @return bool
	 * @since 1.4.3
	 */
	public function validate_consent( $consent ) {
		if ( 'on' !== $consent ) {
			wc_add_notice( __( 'text_terms_notice', 'wirecard-woocommerce-extension' ), 'error' );
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
		if ( ! in_array( $customer->get_shipping_country(), $this->get_option( 'shipping_countries' ), true ) &&
		! empty( $customer->get_shipping_country() ) ) {
			return false;
		}

		if ( ! in_array( $customer->get_billing_country(), $this->get_option( 'billing_countries' ), true ) &&
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
		if ( $this->get_option( 'billing_shipping_same' ) === 'yes' ) {
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

				if ( call_user_func( array( $customer, $billing ) ) !== call_user_func( array( $customer, $shipping ) ) &&
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
	 * Create Ratepay script
	 *
	 * @return string
	 * @since 1.1.0
	 */
	private function create_ratepay_script() {
		if ( null === WC()->session->get( 'ratepay_device_ident' ) ) {
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
	 * Returns deviceIdentToken for Ratepay script
	 *
	 * @return string
	 * @since 1.1.0
	 */
	private function create_device_ident() {
		return md5( $this->get_option( 'merchant_account_id' ) . '_' . microtime() );
	}
}
