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

function plugin_dir_path()
{
	return __DIR__ . '/../../';
}

function plugin_dir_url()
{
	return __DIR__ . '/../';
}

function plugin_basename()
{
	return 'wirecard-woocommerce-extension';
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

function wp_localize_script( $name, $var_name, $var ) {
	return;
}

function wp_verify_nonce() {
	return true;
}

function wp_create_nonce() {
	return 'nonce';
}

function wp_strip_all_tags( $string ) {
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
