<?php
/**
 * Plugin Name: Wirecard WooCommerce Extension
 * Plugin URI: https://github.com/wirecard/woocommerce-ee
 * Description: Payment Gateway for WooCommerce
 * Version: 1.1.8
 * Author: Wirecard AG
 * Author URI: https://www.wirecard.com/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wirecard-woocommerce-extension
 * Domain Path: /languages
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

define( 'WIRECARD_EXTENSION_NAME', 'Wirecard WooCommerce Extension' );
define( 'WIRECARD_EXTENSION_VERSION', '1.1.8' );
define( 'WIRECARD_EXTENSION_BASEDIR', plugin_dir_path( __FILE__ ) );
define( 'WIRECARD_EXTENSION_URL', plugin_dir_url( __FILE__ ) );

load_plugin_textdomain(
	'wirecard-woocommerce-extension', false, dirname( plugin_basename( __FILE__ ) ) . '/languages'
);

register_activation_hook( __FILE__, 'wirecard_install_payment_gateway' );

add_action( 'plugins_loaded', 'wirecard_init_payment_gateway' );

/**
 * Initialize payment gateway
 *
 * @since 1.0.0
 */
function wirecard_init_payment_gateway() {
	if ( ! class_exists( 'WC_PAYMENT_GATEWAY' ) ) {
		global $error;
		$error = new WP_Error( 'woocommerce', 'To use Wirecard WooCommerce Extension you need to install and activate the WooCommerce' );
		echo '<div class="error notice"><p>' . $error->get_error_message() . '</p></div>';
		return;
	}

	require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-wirecard-payment-gateway.php' );
	require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-paypal.php' );
	require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-sepa.php' );
	require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-creditcard.php' );
	require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-ideal.php' );
	require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-sofort.php' );
	require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-poipia.php' );
	require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-guaranteed-invoice-ratepay.php' );
	require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-alipay-crossborder.php' );
	require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-unionpay-international.php' );
	require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-masterpass.php' );
	require_once( WIRECARD_EXTENSION_BASEDIR . 'vendor/autoload.php' );

	add_filter( 'woocommerce_payment_gateways', 'wirecard_add_payment_gateway', 0 );
	add_filter( 'wc_order_statuses', 'wirecard_wc_order_statuses' );
	add_action( 'admin_enqueue_scripts', 'backend_scripts', 999 );
	add_action( 'woocommerce_settings_checkout', 'wirecard_add_support_chat', 0 );
	add_action( 'admin_menu', 'wirecard_gateway_options_page' );

	register_post_status(
		'wc-authorization',
		array(
			'label'                     => _x( 'Authorized', 'Order status', 'wirecard-woocommerce-extension' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			// translators:
			'label_count'               => _n_noop( 'Authorized <span class="count">(%s)</span>', 'Authorized<span class="count">(%s)</span>', 'wirecard-woocommerce-extension' ),
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
function wirecard_add_payment_gateway( $methods ) {
	foreach ( wirecard_get_payments() as $key => $payment_method ) {
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
function wirecard_get_payments() {
	return array(
		'WC_Gateway_Wirecard_Creditcard'                 => new WC_Gateway_Wirecard_Creditcard(),
		'WC_Gateway_Wirecard_Alipay_Crossborder'         => new WC_Gateway_Wirecard_Alipay_Crossborder(),
		'WC_Gateway_Wirecard_Guaranteed_Invoice_Ratepay' => new WC_Gateway_Wirecard_Guaranteed_Invoice_Ratepay(),
		'WC_Gateway_Wirecard_Ideal'                      => new WC_Gateway_Wirecard_Ideal(),
		'WC_Gateway_Wirecard_Masterpass'                 => new WC_Gateway_Wirecard_Masterpass(),
		'WC_Gateway_Wirecard_Poipia'                     => new WC_Gateway_Wirecard_Poipia(),
		'WC_Gateway_Wirecard_Paypal'                     => new WC_Gateway_Wirecard_Paypal(),
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
	$order_statuses['wc-authorization'] = _x( 'Authorized', 'Order status', 'wirecard-woocommerce-extension' );

	return $order_statuses;
}

/**
 * Create transaction table in activation process
 *
 * @since 1.0.0
 */
function wirecard_install_payment_gateway() {
	wirecard_check_if_woo_installed();
	global $wpdb;

	$table_name       = $wpdb->base_prefix . 'wirecard_payment_gateway_tx';
	$vault_table_name = $wpdb->base_prefix . 'wirecard_payment_gateway_vault';
	$collate          = '';
	if ( $wpdb->has_cap( 'collation' ) ) {
		$collate = $wpdb->get_charset_collate();
	}
	$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
		tx_id int(10) unsigned NOT NULL auto_increment,
		transaction_id varchar(128) default NULL UNIQUE,
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

	$sql2 = "CREATE TABLE IF NOT EXISTS {$vault_table_name} (
 		vault_id int(10) unsigned NOT NULL auto_increment,
 		user_id int(10) NOT NULL,
 		token varchar(20) NOT NULL,
 		masked_pan varchar(30) NOT NULL,
 		PRIMARY KEY (vault_id)
 		)$collate;";
	dbDelta( $sql2 );
}

/**
 * Add Wirecard Payment Gateway options page and back-end pages
 *
 * @since 1.0.0
 */
function wirecard_gateway_options_page() {
	require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/admin/class-wirecard-settings.php' );

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
		__( 'Cancel transaction', 'wirecard-woocommerce-extension' ),
		__( 'Cancel transaction', 'wirecard-woocommerce-extension' ),
		'manage_options',
		'cancelpayment',
		array( $admin, 'cancel_transaction' )
	);
	add_submenu_page(
		null,
		__( 'Capture transaction', 'wirecard-woocommerce-extension' ),
		__( 'Capture transaction', 'wirecard-woocommerce-extension' ),
		'manage_options',
		'capturepayment',
		array( $admin, 'capture_transaction' )
	);
	add_submenu_page(
		null,
		__( 'Refund transaction', 'wirecard-woocommerce-extension' ),
		__( 'Refund transaction', 'wirecard-woocommerce-extension' ),
		'manage_options',
		'refundpayment',
		array( $admin, 'refund_transaction' )
	);
	add_submenu_page(
		'wirecardpayment',
		'Wirecard Payment Gateway Support',
		'Wirecard Payment Gateway Support',
		'manage_options',
		'wirecardsupport',
		array( $admin, 'wirecard_payment_gateway_support' )
	);
	add_submenu_page(
		null,
		'Wirecard Payment Gateway Support',
		'Wirecard Payment Gateway Support',
		'manage_options',
		'wirecardsendsupport',
		array( $admin, 'send_email_to_support' )
	);
}

/**
 * Load basic scripts
 *
 * @since 1.1.5
 */
function backend_scripts() {
	wp_register_script( 'live_chat', 'http://www.provusgroup.com/livezilla/script.php?id=936f87cd4ce16e1e60bea40b45b0596a', array(), null, true );
	wp_register_script( 'plugin_admin_script', WIRECARD_EXTENSION_URL . 'assets/js/admin/plugin_admin.js', array(), null, false );
}

/**
 * Add support chat script
 *
 * @since 1.1.0
 */
function wirecard_add_support_chat() {
	$admin_url = add_query_arg(
		[ 'wc-api' => 'test_payment_method_config' ],
		site_url( '/', is_ssl() ? 'https' : 'http' )
	);

	$args = array(
		'admin_url'               => $admin_url,
		'test_credentials_button' => __( 'Test', 'wirecard-woocommerce-extension' ),
		'admin_nonce'             => wp_create_nonce(),
	);

	wp_enqueue_script( 'live_chat' );
	wp_enqueue_script( 'plugin_admin_script' );
	wp_localize_script( 'plugin_admin_script', 'admin_vars', $args );
}

/**
 * Check if the woocommerce plugin is installed else display error
 *
 * @since 1.1.0
 */
function wirecard_check_if_woo_installed() {
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )
		|| array_key_exists( 'woocommerce/woocommerce.php', get_site_option( 'active_sitewide_plugins' ) ) ) {
		return;
	}
	wp_die(
		__( 'Sorry, but this plugin requires WooCommerce Plugin to be installed and active.', 'wirecard-woocommerce-extension' ) .
		'<br><a href="' . admin_url( 'plugins.php' ) . '">' . __( 'Go to Plugins', 'wirecard-woocommerce-extension' ) . '</a>'
	);
}
