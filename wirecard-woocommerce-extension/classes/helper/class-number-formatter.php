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

class Number_Formatter
{
	/** @var string */
	const DEFAULT_DECIMAL_SEPARATOR = ".";
	/** @var string */
	const DEFAULT_THOUSANDS_SEPARATOR = "";
	/** @var int */
	const DEFAULT_DECIMAL_POINT_NUMBER = 2;

	/** @var int */
	private $decimal_point_number;
	/**
	 * Number_Formatter constructor.
	 * 
	 * @param int $decimal_point_number
	 */
	public function __construct( $decimal_point_number = self::DEFAULT_DECIMAL_POINT_NUMBER )
	{
		$this->set_decimal_point_number( $decimal_point_number );
	}

	/**
	 * @return int
	 * 
	 * @since 3.1.0
	 */
	public function get_decimal_point_number()
	{
		return $this->decimal_point_number;
	}

	/**
	 * @param int $decimal_point_number
	 * @return $this
	 * 
	 * @since 3.1.0
	 */
	public function set_decimal_point_number( $decimal_point_number )
	{
		$this->decimal_point_number = $decimal_point_number;
		return $this;
	}

	/**
	 * Number format
	 * 
	 * @param float $amount
	 * @return float
	 */
	public function format_wc( $amount ) {
		return (float)number_format(
			$amount,
			$this->get_decimal_point_number(),
			self::DEFAULT_DECIMAL_SEPARATOR,
			self::DEFAULT_THOUSANDS_SEPARATOR
		);
	}
}
