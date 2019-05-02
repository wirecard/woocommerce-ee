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
 * Class class-money-formatter
 *
 * The money formatter is used to format the transaction amount coming from
 * DB into a float value used by PaymentSDK.
 *
 * NOTE: The money amount is stored in the TX table of this plugin so the
 *       converting is NOT used the decimal point setting in WC - the plugin
 *       stores the TX amount as numeric data type into a string.
 *
 * @since   1.6.5
 */
class Money_Formatter {

	/**
	 * Convert a number/string to double to ensure handling a float
	 *
	 * @param  string|float $amount
	 * @return float
	 * @since  1.6.5
	 */
	public function to_float( $amount ) {
		$ret = $amount;
		if ( is_string( $amount ) ) {
			$ret = (float) $amount;
		}
		return $ret;
	}
}
