<?php
/**
 * Plugin Name: Wirecard Payment Processing Gateway
 * Plugin URI: https://github.com/wirecard/woocommerce-ee
 * Description: Wirecard Payment Processing Gateway Plugin for WooCommerce
 * Version: 0.0.1
 * Author: Wirecard
 * Author URI: https://www.wirecard.at/
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

register_activation_hook( __FILE__, 'install_wirecard_payment_gateway' );

add_action( 'plugins_loaded', 'init_wirecard_payment_gateway' );

function init_wirecard_payment_gateway() {
	if ( ! class_exists( 'WC_PAYMENT_GATEWAY' ) ) {
		return;
	}

	require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'includes/class-wc-gateway-wirecard-payment-gateway.php' );
	require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'vendor/autoload.php' );

	add_filter( 'woocommerce_payment_gateways', 'add_wirecard_payment_gateway' );
}

function add_wirecard_payment_gateway() {
	$methods[] = 'WC_Gateway_Wirecard_Payment_Gateway';

	return $methods;
}

/**
 * Default method for installation process
 *
 * @since 0.0.1
 */
function install_wirecard_payment_gateway() {
	global $wpdb;
}
