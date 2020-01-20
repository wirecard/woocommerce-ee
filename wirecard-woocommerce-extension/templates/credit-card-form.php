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

/**
 * Html template : Credit Card Form
 *
 * @since 2.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$html = '
			<div id="wc_payment_method_wirecard_new_credit_card">
				<div id="wd-creditcard-messagecontainer" class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout" style="display: none;">
				<ul class="woocommerce-error" role="alert"><li id="wd-creditcard-error-message">' .
				__( 'error_malformed_creditcard_request', 'wirecard-woocommerce-extension' ) .
				'</li></ul></div>
				<div class="show-spinner">
					<div class="spinner" style="background: url(\'' . admin_url() . 'images/loading.gif\') no-repeat;"></div>
				</div>
				
				<form method="POST" id="wc_payment_method_wirecard_creditcard_response_form">
					<input type="hidden" name="cc_nonce" value="' . wp_create_nonce() . '" />
				</form>
				
				<div id="wc_payment_method_wirecard_creditcard_form"></div>
		';
