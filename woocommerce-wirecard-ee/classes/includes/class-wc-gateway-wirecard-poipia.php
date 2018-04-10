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
use Wirecard\PaymentSdk\Transaction\PoiPiaTransaction;

/**
 * Class WC_Gateway_Wirecard_Poipia
 *
 * @extends WC_Wirecard_Payment_Gateway
 *
 * @since   1.1.0
 */
class WC_Gateway_Wirecard_Poipia extends WC_Wirecard_Payment_Gateway {

	/**
	 * Additional helper for basket and risk management
	 *
	 * @since  1.1.0
	 * @access private
	 * @var Additional_Information
	 */
	private $additional_helper;

	/**
	 * WC_Gateway_Wirecard_Poipia constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->type               = 'wiretransfer';
		$this->id                 = 'wirecard_ee_poipia';
		$this->icon               = WOOCOMMERCE_GATEWAY_WIRECARD_URL . 'assets/images/poipia.png';
		$this->method_title       = __( 'Wirecard Payment on Invoice / Payment in Advance', 'wooocommerce-gateway-wirecard' );
		$this->method_name        = __( 'Payment on Invoice / Payment in Advance', 'wooocommerce-gateway-wirecard' );
		$this->method_description = __( 'Payment on Invoice / Payment in Advance transactions via Wirecard Payment Processing Gateway', 'woocommerce-gateway-wirecard' );

		$this->supports       = array(
			'products',
		);
		$this->cancel         = array( 'authorization' );
		$this->payment_action = 'reserve';

		$this->init_form_fields();
		$this->init_settings();

		$this->title   = $this->get_option( 'title' );
		$this->enabled = $this->get_option( 'enabled' );

		$this->additional_helper = new Additional_Information();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

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
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-wirecard' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Wirecard Payment on Invoice / Payment in Advance', 'woocommerce-gateway-wirecard' ),
				'default' => 'yes',
			),
			'title'               => array(
				'title'       => __( 'Title', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-wirecard' ),
				'default'     => __( 'Wirecard Payment on Invoice / Payment in Advance', 'woocommerce-gateway-wirecard' ),
				'desc_tip'    => true,
			),
			'merchant_account_id' => array(
				'title'   => __( 'Merchant Account ID', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => '105ab3e8-d16b-4fa0-9f1f-18dd9b390c94',
			),
			'secret'              => array(
				'title'   => __( 'Secret Key', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => '2d96596b-9d10-4c98-ac47-4d56e22fd878',
			),
			'credentials'         => array(
				'title'       => __( 'Credentials', 'woocommerce-gateway-wirecard' ),
				'type'        => 'title',
				'description' => __( 'Enter your Wirecard credentials.', 'woocommerce-gateway-wirecard' ),
			),
			'base_url'            => array(
				'title'       => __( 'Base URL', 'woocommerce-gateway-wirecard' ),
				'type'        => 'text',
				'description' => __( 'The Wirecard base URL. (e.g. https://api.wirecard.com)' ),
				'default'     => 'https://api-test.wirecard.com',
				'desc_tip'    => true,
			),
			'http_user'           => array(
				'title'   => __( 'HTTP User', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => '70000-APITEST-AP',
			),
			'http_pass'           => array(
				'title'   => __( 'HTTP Password', 'woocommerce-gateway-wirecard' ),
				'type'    => 'text',
				'default' => 'qD2wzQ_hrc!8',
			),
			'advanced'            => array(
				'title'       => __( 'Advanced Options', 'woocommerce-gateway-wirecard' ),
				'type'        => 'title',
				'description' => '',
			),
			'payment_type'        => array(
				'title'   => __( 'Payment Type', 'woocommerce-gateway-wirecard' ),
				'type'    => 'select',
				'default' => 'Payment in Advance',
				'label'   => __( 'Payment Type', 'woocommerce-gateway-wirecard' ),
				'options' => array(
					'poi' => 'Payment on Invoice',
					'pia' => 'Payment in Advance',
				),
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

		$this->transaction = new PoiPiaTransaction();
		$this->transaction->setAccountHolder( $this->additional_helper->create_account_holder( $order, 'billing' ) );

		return parent::process_payment( $order_id );
	}

	/**
	 * Process cancel transaction
	 *
	 * @param int     $order_id
	 * @param float|null $amount
	 *
	 * @return PoiPiaTransaction
	 *
	 * @since 1.1.0
	 */
	public function process_cancel( $order_id, $amount = null ) {
		$order = wc_get_order( $order_id );

		$transaction = new PoiPiaTransaction();
		$transaction->setParentTransactionId( $order->get_transaction_id() );
		if ( ! is_null( $amount ) ) {
			$transaction->setAmount( new Amount( $amount, $order->get_currency() ) );
		}

		return $transaction;
	}

	/**
	 * Create payment method configuration
	 *
	 * @param null $base_url
	 * @param null $http_user
	 * @param null $http_pass
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
		$payment_config = new PaymentMethodConfig( PoiPiaTransaction::NAME, $this->get_option( 'merchant_account_id' ), $this->get_option( 'secret' ) );
		$config->add( $payment_config );

		return $config;
	}

	/**
	 * Hook for thankyou page text
	 *
	 * @param $order_id
	 *
	 * @return string|void
	 *
	 * @since 1.1.0
	 */
	public function thankyou_page( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $this->get_option( 'payment_type' ) == 'pia' ) {
			$iban         = get_post_meta( $order_id, 'pia-iban', true );
			$bic          = get_post_meta( $order_id, 'pia-bic', true );
			$reference_id = get_post_meta( $order_id, 'pia-reference-id', true );
			$result       = '<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">';
			$result      .= '<thead><tr><th>';
			$result      .= __( 'Please transfer the amount using the following data:', 'woocommerce-gateway-wirecard' );
			$result      .= '</th></tr></thead>';
			$result      .= '<tr><td>' . __( 'Amount', 'woocommerce-gateway-wirecard' ) . '</td><td>' . $order->get_total() . '</td></tr>';
			$result      .= '<tr><td>' . __( 'IBAN', 'wooocommerce-gateway-wirecard' ) . '</td><td>' . $iban . '</td></tr>';
			$result      .= '<tr><td>' . __( 'BIC', 'woocommerce-gateway-wirecard' ) . '</td><td>' . $bic . '</td></tr>';
			$result      .= '<tr><td>' . __( 'Provider transaction reference id', 'woocommerce-gateway-wirecard' ) . '</td><td>' . $reference_id . '</td></tr>';
			echo $result;
		}
	}

}
