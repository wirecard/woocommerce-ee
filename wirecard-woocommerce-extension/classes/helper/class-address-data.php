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

if (!defined('ABSPATH')) {
	exit;
}


/**
 * Class Address_Data
 * Address data helper
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @since   3.4.4
 */
class Address_Data
{
	const TYPE_BILLING = "billing";
	
	// AddressData attributes
	const ATTRIBUTE_ADDRESS_1 = "address_1";
	const ATTRIBUTE_POSTAL_CODE = "postcode";
	const ATTRIBUTE_CITY = "city";
	const ATTRIBUTE_COUNTRY = "country";

	/** @var string */
	private $address_1;
	/** @var string */
	private $city;
	/** @var string */
	private $postal_code;
	/** @var string */
	private $country;
	/** @var string */
	private $type;

	/**
	 * Address_Data constructor.
	 * @param string $address_1
	 * @param string $city
	 * @param string $postal_code
	 * @param string $country
	 * @param string $type
	 */
	public function __construct($address_1, $city, $postal_code, $country, $type = self::TYPE_BILLING )
	{
		$this->address_1 = $address_1;
		$this->city = $city;
		$this->postal_code = $postal_code;
		$this->country = $country;
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getAddress1()
	{
		return $this->address_1;
	}

	/**
	 * @return string
	 */
	public function getPostalCode()
	{
		return $this->postal_code;
	}

	/**
	 * @return string
	 */
	public function getCountry()
	{
		return $this->country;
	}

	/**
	 * @return string
	 */
	public function getCity()
	{
		return $this->city;
	}
	

	/**
	 * Equals with other Address_Data
	 * @param Address_Data $address_data
	 * @return bool
	 */
	public function equals( Address_Data $address_data )
	{
		return $this->getCity() === $address_data->getCity() &&
			$this->getCountry() === $address_data->getCountry() &&
			$this->getPostalCode() === $address_data->getPostalCode() &&
			$this->getAddress1() === $address_data->getAddress1();
	}

	/**
	 * Representation of data in array format
	 * @return array
	 */
	public function toArray()
	{
		return [
			self::ATTRIBUTE_ADDRESS_1 => $this->address_1,
			self::ATTRIBUTE_COUNTRY => $this->country,
			self::ATTRIBUTE_CITY => $this->city,
			self::ATTRIBUTE_POSTAL_CODE => $this->postal_code,
		];
	}

//	/**
//	 * @return array
//	 */
//	public function generateWCArray()
//	{
//		$wc_list = [];
//		$generateKey = function ($attribute) {
//			return $this->type . "_" . $attribute;
//		};
//		$data = $this->toArray();
//		foreach ($data as $key => $value) {
//			$wc_list[$generateKey($key)] = $value;
//		}
//		
//		return $wc_list;
//	}

	/**
	 * Create instance of billing address from WC_Order
	 * @param WC_Order $order
	 * @return $this
	 */
	public static function fromWoocommerceOrder( WC_Order $order )
	{
		return new static(
			$order->get_billing_address_1(),
			$order->get_billing_city(),
			$order->get_billing_postcode(),
			$order->get_billing_country()
		);
	}

	/**
	 * @param stdClass $address
	 * @return $this
	 */
	public function fromInput( stdClass $address )
	{
		return new static(
			$address->address_1,
			$address->city,
			$address->postcode,
			$address->country
		);
	}
}
