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
require_once( WIRECARD_EXTENSION_HELPER_DIR . 'class-method-helper.php' );
require_once( WIRECARD_EXTENSION_HELPER_DIR . 'class-basket-item-helper.php' );

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

	const BILLING = 'billing';

	const BASE = 'base';

	protected $basket_item_helper;

	private $payment_method;

	public function __construct( $payment_method = null ) {
		$this->payment_method     = $payment_method;
		$this->basket_item_helper = new Basket_Item_Helper();
	}

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

		/** @var WC_Cart $cart */
		$cart   = $woocommerce->cart;
		$basket = new Basket();
		$basket->setVersion( $transaction );
		//depending on the backend woocommerce_tax_based_on setting in WC (shipping, billing, shop) we get the tax rate
		$tax_country = $this->get_correct_country_for_tax_rate();
		$sum         = 0;

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			/** @var WC_Product $product */
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
			$sum      += floatval( number_format( wc_get_price_including_tax( $product ), wc_get_price_decimals(), '.', '' ) ) * $cart_item['quantity'];
		}
		//Check if there is a rounding difference and if so add the difference to shipping
		$shipping           = $cart->get_shipping_total();
		$shipping_tax_class = get_option( 'woocommerce_shipping_tax_class' );
		$shipping_tax_rate  = $this->get_tax_rate_from_tax_class_depending_on_country( $tax_country, $shipping_tax_class );
		$sum               += $shipping + $cart->get_shipping_tax();

		//If coupons are applied
		if ( ! empty( $cart->get_applied_coupons() ) ) {
			$coupon_netto = 0;
			$coupon_tax   = 0;
			//for the netto amount
			foreach ( $cart->get_coupon_discount_totals() as $coupon_code => $coupon_amount ) {
				$coupon_netto += $coupon_amount;
			}
			//to add tax
			foreach ( $cart->get_coupon_discount_tax_totals() as $coupon_code => $coupon_tax ) {
				$coupon_tax += $coupon_tax;
			}
			$coupon_total = $coupon_netto + $coupon_tax;
			$sum         -= $coupon_total;
			$basket       = $this->set_voucher_item(
				$basket,
				$coupon_netto,
				$coupon_tax
			);
		}
		if ( $cart->get_total( 'total' ) - $sum > 0 ) {
			$shipping += floatval( number_format( ( $cart->get_total( 'total' ) - $sum ), wc_get_price_decimals(), '.', '' ) );
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
		$transaction->setAccountHolder( $this->create_account_holder( $order, self::BILLING ) );
		$transaction->setShipping( $this->create_account_holder( $order, self::SHIPPING ) );
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
		if ( self::SHIPPING === $type ) {
			$account_holder->setAddress( $this->create_address_data( $order, $type ) );
			$account_holder->setFirstName( $order->get_shipping_first_name() );
			$account_holder->setLastName( $order->get_shipping_last_name() );
		} else {
			$account_holder->setAddress( $this->create_address_data( $order, $type ) );
			$account_holder->setEmail( $order->get_billing_email() );
			$account_holder->setFirstName( $order->get_billing_first_name() );
			$account_holder->setLastName( $order->get_billing_last_name() );
			$account_holder->setPhone( $order->get_billing_phone() );
			if ( null !== $date_of_birth ) {
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
		if ( self::SHIPPING === $type ) {
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
	 * @param array $item
	 *
	 * @return Item
	 *
	 * @since 3.1.0
	 */
	private function build_basket_item_from_array( $item ) {
		return $this->basket_item_helper->build_basket_item(
			$item['name'],
			$item['amount']['value'],
			$item['quantity'],
			$item['description'],
			$item['article-number'],
			$item['tax-rate'],
			null,
			$item['amount']['currency']
		);
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

		$item = $this->basket_item_helper->build_basket_item(
			$product->get_name(),
			$item_unit_gross_amount,
			$quantity,
			$product->get_short_description(),
			$product->get_id(),
			$tax_rate,
			$tax
		);

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
		$shipping_key = 'Shipping';
		$amount       = $shipping_total + $shipping_tax;

		$item = $this->basket_item_helper->build_basket_item(
			$shipping_key,
			$amount,
			1,
			$shipping_key,
			$shipping_key,
			$tax_rate
		);

		$basket->add( $item );

		return $basket;
	}

	/**
	 * Maps WooCommerce state codes to ISO where necessary.
	 *
	 * @param string $country
	 * @param string $state
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
	 * @param string $payment_method
	 * @param array $refund_basket
	 * @param int $refunding_amount
	 * @return Basket|WP_Error
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @since 1.3.2
	 */
	public function create_basket_from_parent_transaction( $order, $config, $payment_method, $refund_basket = array(), $refunding_amount = 0 ) {
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
					if ( $refund_item['product']->get_id() === $item['article-number'] ) {
						$items_total     += $item['amount']['value'] * $refund_item['qty'];
						$item['quantity'] = $refund_item['qty'];
						$basket->add(
							$this->build_basket_item_from_array( $item )
						);
					}
				}
			} elseif ( 0 === $refunding_amount ) {
				$basket->add(
					$this->build_basket_item_from_array( $item )
				);
			}
		}

		if ( ( ! empty( $refund_basket ) || $refunding_amount > 0 ) && $refunding_amount - $items_total > 0 ) {
			if ( 0 === $refunding_amount - $items_total - $shipping['amount']['value'] ) {
				$basket->add(
					$this->build_basket_item_from_array( $shipping )
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
			case self::BILLING:
				return WC()->customer->get_billing_country();
			case self::SHIPPING:
				return WC()->customer->get_shipping_country();
			case self::BASE:
			default:
				return wc_get_base_location()['country'];
		}
	}

	/**
	 * @param string $country
	 * @param string $tax_classes
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

		if ( ! count( $tax_rates ) ) {
			return 0;
		}

		return array_column( $tax_rates, 'rate' )[0];
	}

	/**
	 * @param Basket $basket
	 * @param float $voucher_total
	 * @param float $voucher_tax
	 *
	 * @return Basket
	 * @since 1.6.2
	 */
	private function set_voucher_item( $basket, $voucher_total, $voucher_tax ) {
		$voucher_key = 'Voucher';
		$amount      = ( ( $voucher_total + $voucher_tax ) * -1 );
		$item        = $this->basket_item_helper->build_basket_item(
			$voucher_key,
			$amount,
			1,
			$voucher_key,
			$voucher_key,
			$voucher_tax
		);
		if ( null !== $this->payment_method ) {
			$item->setTaxRate( null );
		}
		$basket->add( $item );

		return $basket;
	}
}
