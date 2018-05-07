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

require_once __DIR__ . '/../../../../classes/helper/class-additional-information.php';

class WC_Gateway_Wirecard_Additional_Information_Utest extends \PHPUnit_Framework_TestCase {
	/** @var Additional_Information*/
	private $additional_information;

	private $transaction;

	private $order;

	public function setUp() {
		global $woocommerce;
		$woocommerce->cart            = new WC_Cart();
		$woocommerce->customer        = new WC_Customer();
		$this->additional_information = new Additional_Information();
		$this->transaction            = new \Wirecard\PaymentSdk\Transaction\CreditCardTransaction();
		$this->order                  = new WC_Order();
	}

	public function test_set_additional_information() {
		$expected = new \Wirecard\PaymentSdk\Transaction\CreditCardTransaction();
		$expected->setConsumerId( 1 );
		$expected->setIpAddress( '123.123.123' );
		$expected->setOrderNumber( 12 );
		$expected->setDescriptor( 'name 12' );
		$account_holder = new \Wirecard\PaymentSdk\Entity\AccountHolder();
		$account_holder->setLastName( 'last-name' );
		$account_holder->setFirstName( 'first-name' );
		$account_holder->setEmail( 'test@email.com' );
		$account_holder->setPhone( '123123123' );
		$address = new \Wirecard\PaymentSdk\Entity\Address( 'AUT', 'City', 'street1' );
		$address->setPostalCode( '1234' );
		$account_holder->setAddress( $address );
		$expected->setAccountHolder( $account_holder );
		$shipping = new \Wirecard\PaymentSdk\Entity\AccountHolder();
		$shipping->setLastName( 'last-name' );
		$shipping->setFirstName( 'first-name' );
		$shipping->setAddress( $address );
		$expected->setShipping( $shipping );

		$basket = new \Wirecard\PaymentSdk\Entity\Basket();
		$item   = new \Wirecard\PaymentSdk\Entity\Item(
			'nemo x1',
			new \Wirecard\PaymentSdk\Entity\Amount( 20, 'EUR' ),
			1
		);
		$item->setDescription( 'short description' );
		$item->setArticleNumber( '1' );
		$item->setTaxRate( 0.0 );
		$basket->add( $item );

		$item = new \Wirecard\PaymentSdk\Entity\Item(
			'Shipping',
			new \Wirecard\PaymentSdk\Entity\Amount( 6, 'EUR' ),
			1
		);
		$item->setDescription( 'Shipping' );
		$item->setArticleNumber( 'Shipping' );
		$item->setTaxRate( 20 );
		$basket->add( $item );

		$item = new \Wirecard\PaymentSdk\Entity\Item(
			'Rounding',
			new \Wirecard\PaymentSdk\Entity\Amount( 24, 'EUR' ),
			1
		);
		$item->setDescription( 'Rounding' );
		$item->setArticleNumber( 'Rounding' );
		$item->setTaxRate( 20.0 );
		$basket->add( $item );
		$basket->setVersion( $expected );

		$expected->setBasket( $basket );

		$this->assertEquals(
			$expected,
			$this->additional_information->set_additional_information(
				$this->order,
				$this->transaction,
				50
			)
		);
	}
}
