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

require_once __DIR__ . '/wpdb.php';

global $wpdb;
$wpdb = new WPDB();

global $woocommerce;
$woocommerce = new stdClass();

function __( $text, $domain = 'default' ) {
	return $text;
}

function wc_get_base_location() {
	return array('country' => 'Austria');
}

function load_plugin_textdomain() {
	return true;
}

function register_activation_hook( $file, $function ) {
	$file = plugin_basename( $file );
	add_action( 'activate_' . $file, $function );
}

function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
	return;
}

function wc_get_order() {
	return new WC_Order();
}

function add_query_arg( $arguments ) {
	$url = 'my-base-url.com';
	foreach ( $arguments as $key => $value ) {
		$url .= '&' . $key . '=' . $value;
	}
	return $url;
}

function site_url() {
	return;
}

function is_ssl() {
	return false;
}

function wc_add_notice( $message, $type ) {

}

function get_bloginfo() {
	return 'name';
}

function get_woocommerce_currencies() {
	return array();
}

function wc_get_price_including_tax( $product ) {
	return 20.0;
}

function wc_get_price_decimals() {
	return 2;
}

function wc_get_price_excluding_tax( $product ) {
	return 10.0;
}

function wc_round_tax_total( $amount ) {
	return number_format( $amount, 2 );
}

function get_woocommerce_currency() {
	return 'EUR';
}

function WC() {
	return new WC();
}

function wp_json_encode() {
	return 'json';
}

function is_multisite() {
	return false;
}

function wp_unslash( $string ) {
	return $string;
}

function wp_enqueue_script( $string ) {
	return;
}

function wp_dequeue_script( $string ) {
	return;
}

function wp_enqueue_style( $string ) {
	return;
}

function admin_url() {
	return;
}

function wp_localize_script( $name, $var_name, $var ) {
	return;
}

function sanitize_text_field( $string ) {
	return $string;
}

function is_user_logged_in() {
	return true;
}

function wp_verify_nonce() {
	return true;
}

function wp_create_nonce() {
	return 'nonce';
}

function wc_reduce_stock_levels( $order ) {
	return $order;
}

function wp_strip_all_tags( $string ) {
	return $string;
}

function get_option( $option ) {
	return $option;
}

function apply_filters( $string, $parameter, $option ) {
	return $string;
}

function is_wp_error() {
	return;
}

function wp_send_json_success($input) {
	echo json_encode($input);
}

function wp_send_json_error($input) {
	echo json_encode($input);
}

function wp_die() {
	return;
}

function _wp_filter_build_unique_id( $tag, $function, $priority ) {
	return $tag . rand();
}

function esc_attr( $str ) {
	return $str;
}

function esc_html ( $str ) {
	return $str;
}
