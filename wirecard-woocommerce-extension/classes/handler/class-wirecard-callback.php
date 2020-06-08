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

/**
 * Class Wirecard_Callback
 */
class Wirecard_Callback {
	/**
	 * Process 3ds redirect
	 *
	 * @since 1.0.0
	 */
	public function post_form() {
		$data = WC()->session->get( 'wirecard_post_data' );
		WC()->session->__unset( 'wirecard_post_data' );
		get_header();
		$html  = '';
		$html .= '
			<link rel="stylesheet" href="' . plugins_url( 'wirecard-woocommerce-extension/assets/styles/loader.css' ) . '">
			<div class="loader" style="display: flex; justify-content: center; font-size: 20px;"></div><div style="text-align: center;margin-bottom: 50px;">' .
			__( 'redirect_text', 'wirecard-woocommerce-extension' ) . '
			</div>';
		$html .= '<form id="credit_card_form" method="' . $data['method'] . '" action="' . $data['url'] . '">';
		foreach ( $data['form_fields'] as $key => $value ) {
			$html .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
		}
		$html .= '</form>';
		$html .= '<script>document.getElementById("credit_card_form").submit();</script>';

		echo $html;
		get_footer();
		wp_die( '', 200 );
	}
}
