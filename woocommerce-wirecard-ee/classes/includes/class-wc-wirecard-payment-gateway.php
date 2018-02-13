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

use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\Item;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\TransactionService;

/**
 * Class WC_Wirecard_Payment_Gateway
 */
abstract class WC_Wirecard_Payment_Gateway extends WC_Payment_Gateway {

	/**
	 * Add global wirecard payment gateway actions
	 *
	 * @since 1.0.0
	 */
	public function add_payment_gateway_actions() {
		add_action(
			'woocommerce_api_wc_wirecard_payment_gateway',
			array(
				$this,
				'notify'
			)
		);
		add_action(
			'woocommerce_api_wc_wirecard_payment_gateway_redirect',
			array(
				$this,
				'return_request'
			)
		);
	}

	/**
	 * Handle redirects
	 *
	 * @since 1.0.0
	 */
	public function return_request() {
		$order_id = $_REQUEST['order-id'];
		$order    = new WC_Order( $order_id );

		$redirect_url = $this->get_return_url( $order );
		header( 'Location: ' . $redirect_url );
		die();
	}

	/**
	 * Handle notifications
	 *
	 * @since 1.0.0
	 */
	public function notify() {
		echo "notify";
	}

	/**
	 * Create redirect url including orderinformation
	 *
	 * @param WC_Order $order
	 * @param string $payment_state
	 *
	 * @return string
	 */
	public function create_redirect_url( $order, $payment_state ) {
		$return_url = add_query_arg(
			array(
				'wc-api'       => 'WC_Wirecard_Payment_Gateway_Redirect',
				'order-id'     => $order->get_id(),
				'paymentState' => $payment_state
			),
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);

		return $return_url;
	}

	/**
	 * Create notification url
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function create_notification_url() {
		return add_query_arg(
			'wc-api', 'WC_Wirecard_Payment_Gateway',
			site_url( '/', is_ssl() ? 'https' : 'http' )
		);
	}

	/**
	 * Execute transactions via wirecard payment gateway
	 *
	 * @param \Wirecard\PaymentSdk\Transaction\Transaction $transaction
	 * @param \Wirecard\PaymentSdk\Config\Config $config
	 * @param string $operation
	 * @param WC_Order $order
	 * @param int $order_id
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function execute_transaction( $transaction, $config, $operation, $order, $order_id ) {
		$logger              = new WC_Logger();
		$transaction_service = new TransactionService( $config );
		try {
			/** @var $response Response */
			$response = $transaction_service->process( $transaction, $operation );
			$logger->error( print_r( $response, true ) );
		}
		catch ( \Exception $exception ) {
			$logger->error( print_r( $exception, true ) );
		}

		$page_url = $order->get_checkout_payment_url( true );
		$page_url = add_query_arg( 'key', $order->get_order_key(), $page_url );
		$page_url = add_query_arg( 'order-pay', $order_id, $page_url );

		if ( $response instanceof InteractionResponse ) {
			$page_url = $response->getRedirectUrl();
		}

		// FailureResponse, redirect should be implemented
		if ( $response instanceof FailureResponse ) {
			$errors = "";
			foreach ( $response->getStatusCollection()->getIterator() as $item ) {
				/** @var Status $item */
				$errors .= $item->getDescription() . "<br>\n";
			}
			throw new InvalidArgumentException( $errors );
		}

		return array(
			'result'   => 'success',
			'redirect' => $page_url
		);
	}

	/**
	 * Create basket items and shipping item
	 *
	 * @param Wirecard\PaymentSdk\Transaction\Transaction $transaction
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

			$amount = new Amount( $item_unit_gross_amount, get_woocommerce_currency() );
			$item   = new Item( $name, $amount, $item_quantity );

			$article_nr  = $product->get_sku();
			$description = $product->get_short_description();

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
}
