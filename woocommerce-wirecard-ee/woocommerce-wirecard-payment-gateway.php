<?php
/**
 * Plugin Name: Wirecard Payment Processing Gateway
 * Plugin URI: https://github.com/wirecard/woocommerce-ee
 * Description: Wirecard Payment Processing Gateway Plugin for WooCommerce
 * Version: 1.0.0
 * Author: Wirecard
 * Author URI: https://www.wirecard.com/
 * License: GPL3
 *
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
	// Exit if accessed directly
	exit;
}

define( 'WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR', plugin_dir_path( __FILE__ ) );
define( 'WOOCOMMERCE_GATEWAY_WIRECARD_URL', plugin_dir_url( __FILE__ ) );

load_plugin_textdomain(
	'woocommerce-gateway-wirecard', false, dirname( plugin_basename( __FILE__ ) ) . '/languages'
);

register_activation_hook( __FILE__, 'install_wirecard_payment_gateway' );

add_action( 'plugins_loaded', 'init_wirecard_payment_gateway' );
add_action( 'admin_menu', 'wirecard_gateway_options_page' );

/**
 * Initialize payment gateway
 *
 * @since 1.0.0
 */
function init_wirecard_payment_gateway() {
	if ( ! class_exists( 'WC_PAYMENT_GATEWAY' ) ) {
		return;
	}

	require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/includes/class-wc-wirecard-payment-gateway.php' );
	require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-paypal.php' );
	require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-sepa.php' );
	require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-creditcard.php' );
	require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-ideal.php' );
	require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-sofort.php' );
	require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-poipia.php' );
	require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-guaranteed-invoice-ratepay.php' );
	require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-alipay-crossborder.php' );
	require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-unionpay-international.php' );
	require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'vendor/autoload.php' );

	add_filter( 'woocommerce_payment_gateways', 'add_wirecard_payment_gateway', 0 );
	add_filter( 'wc_order_statuses', 'wirecard_wc_order_statuses' );
	add_action( 'woocommerce_settings_checkout', 'add_support_chat', 0 );

	register_post_status(
		'wc-authorization',
		array(
			'label'                     => _x( 'Authorized', 'Order status', 'woocommerce-gateway-wirecard' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			// translators:
			'label_count'               => _n_noop( 'Authorized <span class="count">(%s)</span>', 'Authorized<span class="count">(%s)</span>', 'woocommerce-gateway-wirecard' ),
		)
	);
}

/**
 * Add payment methods for wirecard payment gateway
 *
 * @param $methods
 *
 * @return array
 *
 * @since 1.0.0
 */
function add_wirecard_payment_gateway( $methods ) {
	foreach ( get_payments() as $key => $payment_method ) {
		if ( is_checkout() && $payment_method->is_available() ) {
			$methods[] = $key;
		} else {
			$methods[] = $key;
		}
	}

	return $methods;
}

/**
 * Return payment methods
 *
 * @return array
 *
 * @since 1.1.0
 */
function get_payments() {
	return array(
		'WC_Gateway_Wirecard_Creditcard'                 => new WC_Gateway_Wirecard_Creditcard(),
		'WC_Gateway_Wirecard_Alipay_Crossborder'         => new WC_Gateway_Wirecard_Alipay_Crossborder(),
		'WC_Gateway_Wirecard_Guaranteed_Invoice_Ratepay' => new WC_Gateway_Wirecard_Guaranteed_Invoice_Ratepay(),
		'WC_Gateway_Wirecard_Ideal'                      => new WC_Gateway_Wirecard_Ideal(),
		'WC_Gateway_Wirecard_Paypal'                     => new WC_Gateway_Wirecard_Paypal(),
		'WC_Gateway_Wirecard_Poipia'                     => new WC_Gateway_Wirecard_Poipia(),
		'WC_Gateway_Wirecard_Sepa'                       => new WC_Gateway_Wirecard_Sepa(),
		'WC_Gateway_Wirecard_Sofort'                     => new WC_Gateway_Wirecard_Sofort(),
		'WC_Gateway_Wirecard_Unionpay_International'     => new WC_Gateway_Wirecard_Unionpay_International(),
	);
}

/**
 * Add Wirecard Authorization order status
 *
 * @param array $order_statuses
 *
 * @return array
 *
 * @since 1.0.0
 */
function wirecard_wc_order_statuses( $order_statuses ) {
	$order_statuses['wc-authorization'] = _x( 'Authorized', 'Order status', 'woocommerce-gateway-wirecard' );

	return $order_statuses;
}

/**
 * Create transaction table in activation process
 *
 * @since 1.0.0
 */
function install_wirecard_payment_gateway() {
	global $wpdb;

	$table_name = $wpdb->base_prefix . 'wirecard_payment_gateway_tx';
	$collate    = '';
	if ( $wpdb->has_cap( 'collation' ) ) {
		$collate = $wpdb->get_charset_collate();
	}
	$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
		tx_id int(10) unsigned NOT NULL auto_increment,
		transaction_id varchar(128) default NULL,
		parent_transaction_id VARCHAR(128) default NULL,
		order_id int(10) NULL,
		cart_id int(10) unsigned NOT NULL,
		carthash varchar(255),
		payment_method varchar(32) NOT NULL,
		transaction_state varchar(32) NOT NULL,
		transaction_type varchar(32) NOT NULL,
		amount float NOT NULL,
		currency varchar(3) NOT NULL,
		response TEXT default NULL,
		transaction_link varchar(255) default NULL,
		closed tinyint(1) NOT NULL default '0',
		created DATETIME NOT NULL default CURRENT_TIMESTAMP,
		modified DATETIME NOT NULL default CURRENT_TIMESTAMP,
 		PRIMARY KEY (tx_id)
	)$collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

/**
 * Add Wirecard Payment Gateway options page and back-end pages
 *
 * @since 1.0.0
 */
function wirecard_gateway_options_page() {
	require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/admin/class-wirecard-settings.php' );

	$admin = new Wirecard_Settings();
	add_submenu_page(
		'woocommerce',
		'Wirecard Payment Gateway',
		'Wirecard Payment Gateway',
		'manage_options',
		'wirecardpayment',
		array( $admin, 'wirecard_payment_gateway_settings' )
	);
	add_submenu_page(
		null,
		__( 'Cancel transaction', 'woocommerce-gateway-wirecard' ),
		__( 'Cancel transaction', 'woocommerce-gateway-wirecard' ),
		'manage_options',
		'cancelpayment',
		array( $admin, 'cancel_transaction' )
	);
	add_submenu_page(
		null,
		__( 'Capture transaction', 'woocommerce-gateway-wirecard' ),
		__( 'Capture transaction', 'woocommerce-gateway-wirecard' ),
		'manage_options',
		'capturepayment',
		array( $admin, 'capture_transaction' )
	);
	add_submenu_page(
		null,
		__( 'Refund transaction', 'woocommerce-gateway-wirecard' ),
		__( 'Refund transaction', 'woocommerce-gateway-wirecard' ),
		'manage_options',
		'refundpayment',
		array( $admin, 'refund_transaction' )
	);

	/**
	 * Add support chat script
	 *
	 * @since 1.1.0
	 */
	function add_support_chat() {
		echo '<script
                type="text/javascript" 
				id="936f87cd4ce16e1e60bea40b45b0596a"
			    src="http://www.provusgroup.com/livezilla/script.php?id=936f87cd4ce16e1e60bea40b45b0596a">
        </script>';
	}
}
