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
 * Class Address_Data
 * Address data helper
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @since 3.4.4
 */
class Address_Data {

	/** @var string */
	private $address_1;
	/** @var string */
	private $city;
	/** @var string */
	private $postal_code;
	/** @var string */
	private $country;
	/** @var string */
	private $hash;

	/**
	 * Address_Data constructor.
	 * @param string $address_1
	 * @param string $city
	 * @param string $postal_code
	 * @param string $country
	 * @since 3.4.4
	 */
	public function __construct( $address_1, $city, $postal_code, $country ) {
		$this->address_1   = $address_1;
		$this->city        = $city;
		$this->postal_code = $postal_code;
		$this->country     = $country;
		$this->generate_hash();
	}

	/**
	 * @return string
	 */
	public function get_hash() {
		return $this->hash;
	}

	/**
	 * Equals with other Address_Data
	 * @param string $hash
	 * @return bool
	 * @since 3.4.4
	 */
	public function equals( $hash ) {
		return $this->hash === $hash;
	}

	/**
	 * Create instance of billing address from WC_Order
	 * @param WC_Order $order
	 * @return static
	 * @since 3.4.4
	 */
	public static function from_wc_order( WC_Order $order ) {
		return new static(
			$order->get_billing_address_1(),
			$order->get_billing_city(),
			$order->get_billing_postcode(),
			$order->get_billing_country()
		);
	}

	/**
	 * Generate hash in format MD5(address1_city_country_postcode)
	 * @return self
	 * @since 3.4.4
	 */
	private function generate_hash() {
		$data       = array( $this->address_1, $this->city, $this->country, $this->postal_code );
		$this->hash = md5( implode( '_', $data ) );
		return $this;
	}
}
