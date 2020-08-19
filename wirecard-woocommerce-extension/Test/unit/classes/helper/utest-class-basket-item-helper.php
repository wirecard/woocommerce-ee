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

require_once WIRECARD_EXTENSION_HELPER_DIR . 'class-basket-item-helper.php';

use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Item;

class WC_Gateway_Wirecard_Basket_Item_Utest extends \PHPUnit_Framework_TestCase {

	private $basket_helper;

	public function setUp() {
		$this->basket_helper = new Basket_Item_Helper();
	}

	public function test_build_basket_item_with_tax_rate() {
		$name           = 'Testproduct1';
		$amount         = 34;
		$quantity       = 2;
		$description    = 'This is a testproduct.';
		$article_number = '3';
		$tax_rate       = 15;
		$tax_amount     = null;
		$currency       = 'EUR';

		$expected = new Item(
			$name,
			new Amount( $amount, $currency ),
			$quantity
		);
		$expected->setDescription( $description );
		$expected->setArticleNumber( $article_number );
		$expected->setTaxRate( $tax_rate );

		$this->assertEquals(
			$expected,
			$this->basket_helper->build_basket_item(
				$name,
				$amount,
				$quantity,
				$description,
				$article_number,
				$tax_rate,
				$tax_amount,
				$currency
			)
		);
	}

	public function test_build_basket_item_with_tax_amount() {
		$name           = 'Testproduct with taxamount';
		$amount         = 36;
		$quantity       = 3;
		$description    = 'This is a testproduct with tax amount.';
		$article_number = '6';
		$tax_rate       = 12;
		$tax_amount     = 5;
		$currency       = 'EUR';

		$expected = new Item(
			$name,
			new Amount( $amount, $currency ),
			$quantity
		);
		$expected->setDescription( $description );
		$expected->setArticleNumber( $article_number );
		$expected->setTaxRate( $tax_rate );
		$expected->setTaxAmount( new Amount( $tax_amount, $currency ) );

		$this->assertEquals(
			$expected,
			$this->basket_helper->build_basket_item(
				$name,
				$amount,
				$quantity,
				$description,
				$article_number,
				$tax_rate,
				$tax_amount,
				$currency
			)
		);
	}

	public function test_build_basket_item_with_string_amount() {
		$name           = 'Testproduct with amount not float';
		$amount         = '42.50';
		$quantity       = 1;
		$description    = 'This is a testproduct with tax amount and not float values.';
		$article_number = '6';
		$tax_rate       = null;
		$tax_amount     = '3.40';
		$currency       = 'EUR';

		$expected = new Item(
			$name,
			new Amount( (float) $amount, $currency ),
			$quantity
		);
		$expected->setDescription( $description );
		$expected->setArticleNumber( $article_number );
		$expected->setTaxAmount( new Amount( (float) $tax_amount, $currency ) );

		$this->assertEquals(
			$expected,
			$this->basket_helper->build_basket_item(
				$name,
				$amount,
				$quantity,
				$description,
				$article_number,
				$tax_rate,
				$tax_amount,
				$currency
			)
		);
	}

	public function test_build_basket_without_currency() {
		$name           = 'Testproduct without currency';
		$amount         = 36.2;
		$quantity       = 1;
		$description    = 'This is a testproduct with tax rate and without currency set.';
		$article_number = '8';
		$tax_rate       = 16;
		$tax_amount     = null;

		$expected = new Item(
			$name,
			new Amount( $amount, get_woocommerce_currency() ),
			$quantity
		);
		$expected->setDescription( $description );
		$expected->setArticleNumber( $article_number );
		$expected->setTaxRate( $tax_rate );

		$this->assertEquals(
			$expected,
			$this->basket_helper->build_basket_item(
				$name,
				$amount,
				$quantity,
				$description,
				$article_number,
				$tax_rate,
				$tax_amount
			)
		);
	}
}
