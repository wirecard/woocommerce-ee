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

global $woocommerce;
$woocommerce = new stdClass();

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function __( $text, $domain = 'default' ) {
	return $text;
}

function wc_get_base_location() {
	return array(
		'country' => 'Austria'
	);
}

function wc_get_order() {
	return new WC_Order();
}

/**
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function wc_add_notice( $message, $type ) {

}

/**
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function wc_get_price_including_tax( $product ) {
	if ($product->is_taxable()) {
		return 20.0;
	}
	return $product->get_price();
}

function wc_get_price_decimals() {
	return 2;
}

/**
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function wc_get_price_excluding_tax( $product ) {
	if ($product->is_taxable()) {
		return 10.0;
	}
	return $product->get_price();
}

function wc_round_tax_total( $amount ) {
	return number_format( $amount, 2 );
}

function wc_reduce_stock_levels( $order ) {
	return $order;
}

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
function WC() {
	return new WC();
}

function get_woocommerce_currencies() {
	return array();
}

function get_woocommerce_currency() {
	return 'EUR';
}
