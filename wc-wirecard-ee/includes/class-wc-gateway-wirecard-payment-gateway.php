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
 * Class WC_Gateway_Wirecard_Payment_Gateway
 */
class WC_Gateway_Wirecard_Payment_Gateway extends WC_Payment_Gateway {

	public function __construct() {
		$this->id                 = 'woocommerce_wirecard_payment_gateway';
		$this->method_title       = 'Wirecard Payment Gateway';
		$this->method_description = 'Payment Gateway';
		$this->has_fields         = true;
		$this->init_form_fields();
		$this->init_settings();

		// if any of the payment types are enabled, set this to "yes", otherwise "no"
		$this->enabled = 'yes';
		$this->title   = 'Wirecard Payment Gateway';
	}
}
