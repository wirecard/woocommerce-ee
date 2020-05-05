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

use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Item;

/**
 * Class Basket_Item_Helper
 * @since 3.2.1
 */
class WC_Gateway_Wirecard_Basket_Item_Unit_Test extends \Codeception\Test\Unit
{

	const NAME = "Test Product";
	const AMOUNT = 34;
	const AMOUNT_FLOAT = 34.50;
	const AMOUNT_FLOAT_STRING = '42.50';
	const QUANTITY = 2;
	const DESCRIPTION = "This is a test product.";
	const ARTICAL_NUMBER = "3";
	const TAX_RATE = 15.0;
	const TAX_RATE_NULL = null;
	const TAX_AMOUNT = 5;
	const TAX_AMOUNT_NULL = null;
	const TAX_AMOUNT_FLOAT = '3.40';
	const CURRENCY = "EUR";

	/**
	 * @var UnitTester
	 */
	protected $tester;

	/**
	 * @var Basket_Item_Helper
	 */
	private $basket_helper;
	
	protected function _before()
	{
		$this->basket_helper = new Basket_Item_Helper();
	}

	protected function _after()
	{
	}
	
	/**
	 * @param $name
	 * @param $amount
	 * @param $quantity
	 * @param $description
	 * @param $article_number
	 * @param $tax_rate
	 * @param $tax_amount
	 * @param $currency
	 * @return Item $expected
	 */
	private function itemData($name, $amount, $quantity, $description, $article_number, $tax_rate, $tax_amount, $currency)
	{
		$expected = new Item($name, new Amount($amount, $currency), $quantity);

		$expected->setDescription($description);
		$expected->setArticleNumber($article_number);

		$expected->setTaxRate($tax_rate);

		if ($tax_amount != null) {
			$expected->setTaxAmount(new Amount((float)$tax_amount, $currency));
		}

		return $expected;
	}
	
	/**
	* @return \Generator
	*/
	public function dataProvider()
	{
		yield "test_build_basket_item_with_tax_rate" => [
			self::NAME,
			self::AMOUNT,
			self::QUANTITY,
			self::DESCRIPTION,
			self::ARTICAL_NUMBER,
			self::TAX_RATE,
			self::TAX_AMOUNT_NULL,
			self::CURRENCY,
			$this->itemData(self::NAME, self::AMOUNT, self::QUANTITY,
				self::DESCRIPTION, self::ARTICAL_NUMBER,
				self::TAX_RATE, self::TAX_AMOUNT_NULL, self::CURRENCY)
		];
		yield "test_build_basket_item_with_tax_amount" => [
			self::NAME,
			self::AMOUNT,
			self::QUANTITY,
			self::DESCRIPTION,
			self::ARTICAL_NUMBER,
			self::TAX_RATE,
			self::TAX_AMOUNT,
			self::CURRENCY,
			$this->itemData(self::NAME, self::AMOUNT, self::QUANTITY,
				self::DESCRIPTION, self::ARTICAL_NUMBER,
				self::TAX_RATE, self::TAX_AMOUNT, self::CURRENCY)
		];
		yield "test_build_basket_item_with_string_amount" => [
			self::NAME,
			self::AMOUNT,
			self::QUANTITY,
			self::DESCRIPTION,
			self::ARTICAL_NUMBER,
			self::TAX_RATE_NULL,
			self::TAX_AMOUNT_FLOAT,
			self::CURRENCY,
			$this->itemData(self::NAME, self::AMOUNT, self::QUANTITY,
				self::DESCRIPTION, self::ARTICAL_NUMBER,
				self::TAX_RATE_NULL, self::TAX_AMOUNT_FLOAT, self::CURRENCY)
		];
		yield "test_build_basket_without_currency" => [
			self::NAME,
			self::AMOUNT_FLOAT,
			self::QUANTITY,
			self::DESCRIPTION,
			self::ARTICAL_NUMBER,
			self::TAX_RATE,
			self::TAX_AMOUNT_NULL,
			get_woocommerce_currency(),
			$this->itemData(self::NAME, self::AMOUNT_FLOAT, self::QUANTITY,
				self::DESCRIPTION, self::ARTICAL_NUMBER,
				self::TAX_RATE, self::TAX_AMOUNT_NULL, get_woocommerce_currency())
		];
	}

	/**
	 * @dataProvider dataProvider
	 *
	 * @param $name
	 * @param $amount
	 * @param $quantity
	 * @param $description
	 * @param $article_number
	 * @param $tax_rate
	 * @param $tax_amount
	 * @param $currency
	 * @param $expected
	 */
	public function test_build_basket($name, $amount, $quantity, $description, $article_number, $tax_rate, $tax_amount, $currency, $expected)
	{
		$this->assertEquals($expected, $this->basket_helper->build_basket_item(
			$name,
			$amount,
			$quantity,
			$description,
			$article_number,
			$tax_rate,
			$tax_amount,
			$currency
		));
	}
}
