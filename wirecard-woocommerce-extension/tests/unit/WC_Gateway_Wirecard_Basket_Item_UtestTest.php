<?php

use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Item;

class WC_Gateway_Wirecard_Basket_Item_Unit_Test extends \Codeception\Test\Unit
{
	/**
	 * @var UnitTester
	 */
	protected $tester;
	private $basket_helper;

	protected function _before()
	{
		$this->basket_helper = new Basket_Item_Helper();
	}

	protected function _after()
	{
	}

	public function test_build_basket_item_with_tax_rate() {

		$name = 'Testproduct1';
		$amount = 34;
		$quantity = 2;
		$description = 'This is a testproduct.';
		$article_number = '3';
		$tax_rate = 15;
		$tax_amount = null;
		$currency = 'EUR';

		$expected = new Item(
			$name,
			new Amount($amount, $currency),
			$quantity
		);
		$expected->setDescription($description);
		$expected->setArticleNumber($article_number);
		$expected->setTaxRate($tax_rate);

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

	public function test_build_basket_item_with_tax_amount() {
		$name = 'Testproduct with taxamount';
		$amount = 36;
		$quantity = 3;
		$description = 'This is a testproduct with tax amount.';
		$article_number = '6';
		$tax_rate = 12;
		$tax_amount = 5;
		$currency = 'EUR';

		$expected = new Item(
			$name,
			new Amount($amount, $currency),
			$quantity
		);
		$expected->setDescription($description);
		$expected->setArticleNumber($article_number);
		$expected->setTaxRate($tax_rate);
		$expected->setTaxAmount(new Amount($tax_amount, $currency));

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

	public function test_build_basket_item_with_string_amount() {
		$name = 'Testproduct with amount not float';
		$amount = '42.50';
		$quantity = 1;
		$description = 'This is a testproduct with tax amount and not float values.';
		$article_number = '6';
		$tax_rate = null;
		$tax_amount = '3.40';
		$currency = 'EUR';

		$expected = new Item(
			$name,
			new Amount((float)$amount, $currency),
			$quantity
		);
		$expected->setDescription($description);
		$expected->setArticleNumber($article_number);
		$expected->setTaxAmount(new Amount((float)$tax_amount, $currency));

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

	public function test_build_basket_without_currency() {
		$name = 'Testproduct without currency';
		$amount = 36.2;
		$quantity = 1;
		$description = 'This is a testproduct with tax rate and without currency set.';
		$article_number = '8';
		$tax_rate = 16;
		$tax_amount = null;

		$expected = new Item(
			$name,
			new Amount($amount, get_woocommerce_currency()),
			$quantity
		);
		$expected->setDescription($description);
		$expected->setArticleNumber($article_number);
		$expected->setTaxRate($tax_rate);

		$this->assertEquals($expected, $this->basket_helper->build_basket_item(
			$name,
			$amount,
			$quantity,
			$description,
			$article_number,
			$tax_rate,
			$tax_amount
		));
	}
}
