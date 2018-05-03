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

class WC_Order {

	public function get_id() {
		return 12;
	}

	public function get_total() {
		return 20.56;
	}

	public function get_currency() {
		return 'EUR';
	}

	public function get_transaction_id() {
		return 'transaction_id';
	}

	public function is_paid() {
		return true;
	}

	public function get_order_number() {
		return 12;
	}

	public function get_billing_country() {
		return 'AUT';
	}

	public function get_billing_city() {
		return 'City';
	}

	public function get_billing_address_1() {
		return 'street1';
	}

	public function get_billing_address_2() {
		return false;
	}

	public function get_billing_postcode() {
		return '1234';
	}

	public function get_billing_email() {
		return 'test@email.com';
	}

	public function get_billing_first_name() {
		return 'first-name';
	}

	public function get_billing_last_name() {
		return 'last-name';
	}

	public function get_billing_phone() {
		return '123123123';
	}

	public function get_items() {
		return array(
			'1' =>  new WC_Product(),
		);
	}

	public function get_shipping_total() {
		return 20.0;
	}

	public function get_shipping_tax() {
		return 2;
	}

	public function get_shipping_country() {
		return 'AUT';
	}

	public function get_shipping_city() {
		return 'City';
	}

	public function get_shipping_address_1() {
		return 'street1';
	}

	public function get_shipping_postcode() {
		return '1234';
	}

	public function get_shipping_first_name() {
		return 'first-name';
	}

	public function get_shipping_last_name() {
		return 'last-name';
	}

	public function get_customer_ip_address() {
		return '123.123.123';
	}

	public function get_customer_id() {
		return 1;
	}

	public function get_product_id() {
		return 1;
	}
}
