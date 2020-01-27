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

require_once WIRECARD_EXTENSION_HELPER_DIR . 'class-additional-information.php';

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
		$this->order                  = $this->getMockBuilder( WC_Order::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get_total' ] )
			->getMock();
	}

	public function test_set_additional_information() {
		global $woocommerce;

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
		$address = new \Wirecard\PaymentSdk\Entity\Address( 'AT', 'City', 'street1' );
		$address->setPostalCode( '1234' );
		$address->setStreet2( 'street2' );
		$address->setState( 'OR' );
		$account_holder->setAddress( $address );
		$expected->setAccountHolder( $account_holder );
		$shipping = new \Wirecard\PaymentSdk\Entity\AccountHolder();
		$shipping->setLastName( 'last-name' );
		$shipping->setFirstName( 'first-name' );
		$address = new \Wirecard\PaymentSdk\Entity\Address( 'AT', 'City', 'street1' );
		$address->setPostalCode( '1234' );
		$address->setState( 'OR' );
		$shipping->setAddress( $address );
		$expected->setShipping( $shipping );

		$basket = new \Wirecard\PaymentSdk\Entity\Basket();
		$item   = new \Wirecard\PaymentSdk\Entity\Item(
			'Testproduct',
			new \Wirecard\PaymentSdk\Entity\Amount( 20, 'EUR' ),
			1
		);
		$item->setDescription( 'Testdescription' );
		$item->setArticleNumber( '1' );
		$item->setTaxRate( 12 );
		$item->setTaxAmount( new \Wirecard\PaymentSdk\Entity\Amount( 10, 'EUR' ) );
		$basket->add( $item );
		$item = new \Wirecard\PaymentSdk\Entity\Item(
			'Voucher',
			new \Wirecard\PaymentSdk\Entity\Amount( -14, 'EUR' ),
			1
		);
		$item->setDescription( 'Voucher' );
		$item->setArticleNumber( 'Voucher' );
		$item->setTaxRate(2.0);
		$basket->add( $item );
		$basket->setVersion( $expected );

		$expected->setBasket( $basket );

		$mocked_product = $this->getMockBuilder( WC_Product::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get_id', 'get_short_description', 'is_taxable', 'get_name', 'get_quantity', 'is_downloadable', 'is_virtual', 'get_product_id' ] )
			->getMock();
		$mocked_product->method( 'get_id' )->willReturn( '1' );
		$mocked_product->method( 'get_short_description' )->willReturn( 'Testdescription' );
		$mocked_product->method( 'is_taxable' )->willReturn( 0 );
		$mocked_product->method( 'get_name' )->willReturn( 'Testproduct' );

		$mocked_cart   = $this->getMockBuilder( WC_Cart::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get_cart', 'get_shipping_total', 'get_total', 'get_shipping_tax' ] )
			->getMock();
		$cart_contents = array(
			'1' => array(
				'data'       => $mocked_product,
				'quantity'   => 1,
				'product_id' => 1,
			),
		);
		$mocked_cart->method( 'get_cart' )->willReturn( $cart_contents );
		$woocommerce->cart = $mocked_cart;

		$this->assertEquals(
			$expected,
			$this->additional_information->set_additional_information(
				$this->order,
				$this->transaction,
				50
			)
		);
	}

	public function test_create_address_data() {
		$order = $this->getMockBuilder( WC_Order::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get_billing_country', 'get_billing_city', 'get_billing_address_1', 'get_billing_postcode', 'get_billing_address_2', 'get_billing_state' ] )
			->getMock();
		$order->method( 'get_billing_country' )->willReturn( 'AT' );
		$order->method( 'get_billing_state' )->willReturn( 'OR' );
		$order->method( 'get_billing_city' )->willReturn( 'City' );
		$order->method( 'get_billing_address_1' )->willReturn( 'Street1' );
		$order->method( 'get_billing_postcode' )->willReturn( '0000' );
		$order->method( 'get_billing_address_2' )->willReturn( 'Street2' );

		$expected = new \Wirecard\PaymentSdk\Entity\Address( 'AT', 'City', 'Street1' );
		$expected->setPostalCode( '0000' );
		$expected->setStreet2( 'Street2' );
		$expected->setState( 'OR' );

		$actual = $this->additional_information->create_address_data( $order, 'BILLING' );

		$this->assertEquals( $expected, $actual );
	}
}
