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

require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-wirecard-payment-gateway.php' );

use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Address;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\Item;
use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * Class Additional_Information
 *
 * Handles basket creation and risk management parameter creation
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
		$cart   = $woocommerce->cart;
		$basket = new Basket();
		$basket->setVersion( $transaction );
		//depending on the backend woocommerce_tax_based_on setting in WC (shipping, billing, shop) we get the tax rate
		$tax_country = $this->get_correct_country_for_tax_rate();
		$sum         = 0;

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			/** @var $product WC_Product */
			$product   = $cart_item['data'];
			$tax_class = apply_filters( 'woocommerce_cart_item_tax', $product->get_tax_class(), $cart_item, $cart_item_key );
			$basket    = $this->set_basket_item(
				$basket,
				$product,
				$cart_item['quantity'],
				wc_get_price_excluding_tax( $product ),
				( wc_get_price_including_tax( $product ) - wc_get_price_excluding_tax( $product ) ),
				$this->get_tax_rate_from_tax_class_depending_on_country( $tax_country, $tax_class )
			);
			$sum      += number_format( wc_get_price_including_tax( $product ), wc_get_price_decimals() ) * $cart_item['quantity'];
		}
		//Check if there is a rounding difference and if so add the difference to shipping
		$shipping           = $cart->get_shipping_total();
		$wc_tax             = new WC_Tax();
		$shipping_tax_class = $wc_tax->get_shipping_tax_rates();
		$shipping_tax_rate  = $this->get_tax_rate_from_tax_class_depending_on_country( $tax_country, $shipping_tax_class );
		$sum               += $shipping + $cart->get_shipping_tax();

		if ( $cart->get_total( 'total' ) - $sum > 0 ) {
			$shipping += number_format( ( $cart->get_total( 'total' ) - $sum ), wc_get_price_decimals() );
		}
		if ( $shipping > 0 ) {
			$basket = $this->set_shipping_item( $basket, $shipping, $cart->get_shipping_tax(), $shipping_tax_rate );
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
	 * @param DateTime $date_of_birth
	 *
	 * @return AccountHolder
	 *
	 * @since 1.0.0
	 */
	public function create_account_holder( $order, $type, $date_of_birth = null ) {
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
			if ( null != $date_of_birth ) {
				$account_holder->setDateOfBirth( $date_of_birth );
			}
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
			if ( strlen( $order->get_shipping_state() ) ) {
				$address->setState(
					$this->map_state_to_iso_code( $order->get_shipping_country(), $order->get_shipping_state() )
				);
			}
		} else {
			$address = new Address( $order->get_billing_country(), $order->get_billing_city(), $order->get_billing_address_1() );
			$address->setPostalCode( $order->get_billing_postcode() );
			if ( strlen( $order->get_billing_address_2() ) ) {
				$address->setStreet2( $order->get_billing_address_2() );
			}
			if ( strlen( $order->get_billing_state() ) ) {
				$address->setState(
					$this->map_state_to_iso_code( $order->get_billing_country(), $order->get_billing_state() )
				);
			}
		}

		return $address;
	}

	/**
	 * Set an Item to basket
	 *
	 * @param Basket $basket
	 * @param WC_Product $product
	 * @param int $quantity
	 * @param float $total
	 * @param float $tax
	 * @param float $tax_rate
	 * @return Basket
	 * @since 1.4.0
	 */
	private function set_basket_item( $basket, $product, $quantity, $total, $tax, $tax_rate ) {
		$item_unit_gross_amount = $total + $tax;

		$article_nr  = $product->get_id();
		$description = wp_strip_all_tags( html_entity_decode( $product->get_short_description() ), true );
		$amount      = new Amount( number_format( $item_unit_gross_amount, wc_get_price_decimals() ), get_woocommerce_currency() );

		$item = new Item(
			wp_strip_all_tags( html_entity_decode( $product->get_name() ), true ),
			$amount,
			$quantity
		);
		$item->setDescription( $description );
		$item->setArticleNumber( $article_nr );
		$item->setTaxRate( floatval( number_format( $tax_rate, wc_get_price_decimals() ) ) );
		$item->setTaxAmount( new Amount( wc_round_tax_total( $tax ), get_woocommerce_currency() ) );
		$basket->add( $item );

		return $basket;
	}

	/**
	 * Set the shipping item
	 *
	 * @param Basket $basket
	 * @param float $shipping_total
	 * @param float $shipping_tax
	 * @param float $tax_rate
	 * @return Basket
	 * @since 1.4.0
	 */
	private function set_shipping_item( $basket, $shipping_total, $shipping_tax, $tax_rate ) {
		$amount = floatval( number_format( $shipping_total + $shipping_tax, wc_get_price_decimals() ) );

		$amount = new Amount( $amount, get_woocommerce_currency() );
		$item   = new Item( 'Shipping', $amount, 1 );
		$item->setDescription( 'Shipping' );
		$item->setArticleNumber( 'Shipping' );
		$item->setTaxRate( floatval( number_format( $tax_rate, wc_get_price_decimals() ) ) );
		$basket->add( $item );

		return $basket;
	}

	/**
	 * Maps WooCommerce state codes to ISO where necessary.
	 *
	 * @param $country
	 * @param $state
	 * @return string
	 * @since 1.2.0
	 */
	public function map_state_to_iso_code( $country, $state ) {
		$mapping = file_get_contents( WIRECARD_EXTENSION_BASEDIR . '/assets/stateMapping.json' );
		$mapping = json_decode( $mapping, true );

		if ( array_key_exists( $country, $mapping ) && array_key_exists( $state, $mapping[ $country ] ) ) {
			return $mapping[ $country ][ $state ];
		}

		return $state;
	}

	/**
	 * Creates a basket that is equivalent to the parent transaction
	 *
	 * @param WC_Order $order
	 * @param \Wirecard\PaymentSdk\Config\Config $config
	 * @param $payment_method
	 * @param $refund_basket
	 * @param $refunding_amount
	 * @return Basket|WP_Error
	 * @since 1.3.2
	 */
	public function create_basket_from_parent_transaction( $order, $config, $payment_method, $refund_basket = [], $refunding_amount = 0 ) {
		$basket              = new Basket();
		$transaction_service = new \Wirecard\PaymentSdk\TransactionService( $config );
		$parent_transaction  = $transaction_service->getTransactionByTransactionId( $order->get_transaction_id(), $payment_method );
		$items_total         = 0;
		$shipping            = 0;

		foreach ( $parent_transaction['payment']['order-items']['order-item'] as $item ) {
			if ( 'Shipping' === $item['name'] ) {
				$shipping = $item;
			}
			if ( ! empty( $refund_basket ) ) {
				foreach ( $refund_basket as $refund_item ) {
					if ( $refund_item['product']->get_id() == $item['article-number'] ) {
						$items_total += $item['amount']['value'] * $refund_item['qty'];
						$basket       = $this->set_item_from_response(
							$basket,
							new Amount( $item['amount']['value'], $item['amount']['currency'] ),
							$item['name'],
							$refund_item['qty'],
							$item['description'],
							$item['article-number'],
							$item['tax-rate']
						);
					}
				}
			} elseif ( 0 === $refunding_amount ) {
				$basket = $this->set_item_from_response(
					$basket,
					new Amount( $item['amount']['value'], $item['amount']['currency'] ),
					$item['name'],
					$item['quantity'],
					$item['description'],
					$item['article-number'],
					$item['tax-rate']
				);
			}
		}

		if ( ( ! empty( $refund_basket ) || $refunding_amount > 0 ) && $refunding_amount - $items_total > 0 ) {
			if ( 0 == $refunding_amount - $items_total - $shipping['amount']['value'] ) {
				$basket = $this->set_item_from_response(
					$basket,
					new Amount( $shipping['amount']['value'], $shipping['amount']['currency'] ),
					$shipping['name'],
					$shipping['quantity'],
					$shipping['description'],
					$shipping['article-number'],
					$shipping['tax-rate']
				);
			} else {
				return new WP_Error( 'error', __( 'refund_partial_shipping_error', 'wirecard-woocommerce-extension' ) );
			}
		}
		return $basket;
	}

	/**
	 * Return country code
	 *
	 * @return string
	 * @since 1.4.0
	 */
	private function get_correct_country_for_tax_rate() {
		$tax_setting = get_option( 'woocommerce_tax_based_on' );

		switch ( $tax_setting ) {
			case 'billing':
				return WC()->customer->get_billing_country();
				break;
			case 'shipping':
				return WC()->customer->get_shipping_country();
				break;
			case 'base':
				return wc_get_base_location()['country'];
				break;
		}
	}

	/**
	 * @param $country
	 * @param $tax_classes
	 *
	 * @return float
	 * @since 1.4.0
	 */
	private function get_tax_rate_from_tax_class_depending_on_country( $country, $tax_classes ) {
		$wc_tax    = new WC_Tax();
		$tax_rates = $wc_tax->find_rates(
			array(
				'country'   => $country,
				'tax_class' => $tax_classes,
			)
		);
		return array_column( $tax_rates, 'rate' )[0];
	}

	/**
	 * @param Basket $basket
	 * @param Amount $amount
	 * @param string $name
	 * @param int $quantity
	 * @param string $description
	 * @param string $item_number
	 * @param float $tax_rate
	 *
	 * @return Basket
	 * @since 1.4.0
	 */
	private function set_item_from_response( $basket, $amount, $name, $quantity, $description, $item_number, $tax_rate ) {
		$basket_item = new Item( $name, $amount, $quantity );
		$basket_item->setDescription( $description );
		$basket_item->setArticleNumber( $item_number );
		$basket_item->setTaxRate( $tax_rate );

		return $basket->add( $basket_item );
	}
}
