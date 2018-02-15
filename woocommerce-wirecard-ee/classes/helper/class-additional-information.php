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

require_once( WOOCOMMERCE_GATEWAY_WIRECARD_BASEDIR . 'classes/includes/class-wc-wirecard-payment-gateway.php' );

use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Address;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\Item;
use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * Class Additional_Information
 *
 * @since   1.0.0
 */
class Additional_Information {

	const SHIPPING = 'shipping';

	/**
	 * Create basket items and shipping item
	 *
	 * @param Transaction $transaction
	 *
	 * @return Basket
	 *
	 * @since 1.0.0
	 */
	public function create_shopping_basket( $transaction ) {
		global $woocommerce;

		/** @var $cart WC_Cart */
		$cart = $woocommerce->cart;

		$basket = new Basket();
		$basket->setVersion( $transaction );

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			/** @var $product WC_Product */
			$product       = $cart_item['data'];
			$name          = $product->get_name();
			$item_quantity = $cart_item['quantity'];

			$item_unit_net_amount   = $cart_item['line_total'] / $item_quantity;
			$item_unit_tax_amount   = $cart_item['line_tax'] / $item_quantity;
			$item_unit_gross_amount = wc_format_decimal( $item_unit_net_amount + $item_unit_tax_amount, wc_get_price_decimals() );
			$item_tax_rate          = $item_unit_tax_amount / $item_unit_gross_amount;

			$article_nr  = $product->get_sku();
			$description = $product->get_short_description();
			$amount      = new Amount( $item_unit_gross_amount, get_woocommerce_currency() );

			$item = new Item( $name, $amount, $item_quantity );
			$item->setDescription( $description );
			$item->setArticleNumber( $article_nr );
			if ( $product->is_taxable() ) {
				$item->setTaxRate( number_format( $item_tax_rate * 100, 2 ) );
			} else {
				$item->setTaxRate( 0 );
			}
			$basket->add( $item );
		}

		if ( $cart->get_shipping_total() > 0 ) {
			$amount        = wc_format_decimal( $cart->get_shipping_total() + $cart->get_shipping_tax(), wc_get_price_decimals() );
			$unit_tax_rate = $cart->get_shipping_tax() / $cart->get_shipping_total();

			$amount = new Amount( $amount, get_woocommerce_currency() );
			$item   = new Item( 'Shipping', $amount, 1 );
			$item->setDescription( 'Shipping' );
			$item->setArticleNumber( 'Shipping' );
			$item->setTaxRate( number_format( $unit_tax_rate * 100, 2 ) );
			$basket->add( $item );
		}

		return $basket;
	}

	/**
	 * Create descriptor including shopname and ordernumber
	 *
	 * @param WC_Order $order
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function create_descriptor( $order ) {
		return sprintf(
			'%s %s',
			substr( get_bloginfo( 'name' ), 0, 9 ),
			$order->get_order_number()
		);
	}

	/**
	 * Set additional information
	 *
	 * @param WC_Order    $order
	 * @param Transaction $transaction
	 *
	 * @return Transaction
	 *
	 * @since 1.0.0
	 */
	public function set_additional_information( $order, $transaction ) {
		$transaction->setDescriptor( $this->create_descriptor( $order ) );
		$transaction->setAccountHolder( $this->create_account_holder( $order, 'billing' ) );
		$transaction->setShipping( $this->create_account_holder( $order, 'shipping' ) );
		$transaction->setOrderNumber( $order->get_order_number() );
		$transaction->setBasket( $this->create_shopping_basket( $transaction ) );
		$transaction->setIpAddress( $order->get_customer_ip_address() );
		$transaction->setConsumerId( $order->get_customer_id() );

		return $transaction;
	}

	/**
	 * Create accountholder with specific address data
	 *
	 * @param WC_Order $order
	 * @param string   $type
	 *
	 * @return AccountHolder
	 *
	 * @since 1.0.0
	 */
	public function create_account_holder( $order, $type ) {
		$account_holder = new AccountHolder();
		if ( self::SHIPPING == $type ) {
			$account_holder->setAddress( $this->create_address_data( $order, $type ) );
			$account_holder->setFirstName( $order->get_shipping_first_name() );
			$account_holder->setLastName( $order->get_shipping_last_name() );
		} else {
			$account_holder->setAddress( $this->create_address_data( $order, $type ) );
			$account_holder->setEmail( $order->get_billing_email() );
			$account_holder->setFirstName( $order->get_billing_first_name() );
			$account_holder->setLastName( $order->get_billing_last_name() );
			$account_holder->setPhone( $order->get_billing_phone() );
			//$account_holder->setDateOfBirth();
		}

		return $account_holder;
	}

	/**
	 * Create address data
	 *
	 * @param WC_Order $order
	 * @param string   $type
	 *
	 * @return Address
	 *
	 * @since 1.0.0
	 */
	public function create_address_data( $order, $type ) {
		if ( self::SHIPPING == $type ) {
			$address = new Address( $order->get_shipping_country(), $order->get_shipping_city(), $order->get_shipping_address_1() );
			$address->setPostalCode( $order->get_shipping_postcode() );
		} else {
			$address = new Address( $order->get_billing_country(), $order->get_billing_city(), $order->get_billing_address_1() );
			$address->setPostalCode( $order->get_billing_postcode() );
			if ( strlen( $order->get_billing_address_2() ) ) {
				$address->setStreet2( $order->get_billing_address_2() );
			}
		}

		return $address;
	}
}
