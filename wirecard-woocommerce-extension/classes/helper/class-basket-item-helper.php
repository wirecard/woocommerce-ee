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

require_once( WIRECARD_EXTENSION_HELPER_DIR . 'class-method-helper.php' );

use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Item;

/**
 * Class Basket_Item_Helper
 * Builds SDK Basket Items
 *
 * Handles basket
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @since   1.0.0
 */
class Basket_Item_Helper {

	/**
	 * @param string $name
	 * @param float $amount
	 * @param int $quantity
	 * @param string $description
	 * @param string $article_number
	 * @param float $tax_rate
	 * @param null|float $tax_amount
	 * @param null|string $currency
	 *
	 * @return Item
	 *
	 * @since 3.1.0
	 */
	public function build_basket_item( $name, $amount, $quantity, $description, $article_number, $tax_rate, $tax_amount = null, $currency = null ) {
		return $this->populate_basket_item(
			$this->create_basket_item( $name, $amount, $quantity ),
			$description,
			$article_number,
			$tax_rate,
			$tax_amount,
			$currency
		);
	}

	/**
	 * @param Item $item
	 * @param string $description
	 * @param string $article_nr
	 * @param float $tax_rate
	 * @param null|float $tax_amount
	 * @param null|string $currency
	 *
	 * @return Item
	 *
	 * @since 3.1.0
	 */
	private function populate_basket_item( $item, $description, $article_nr, $tax_rate, $tax_amount = null, $currency = null ) {
		$item->setDescription( Method_Helper::string_format_wc( $description ) );
		$item->setArticleNumber( $article_nr );
		$item->setTaxRate( Method_Helper::number_format_wc( $tax_rate ) );

		if ( $this->has_tax_amount( $tax_amount ) ) {
			$item->setTaxAmount(
				$this->create_formatted_amount( $tax_amount, $currency )
			);
		}

		return $item;
	}

	/**
	 * @param string $name
	 * @param float $amount
	 * @param int $quantity
	 * @param null|string $currency
	 *
	 * @return Item
	 *
	 * @since 3.1.0
	 */
	private function create_basket_item( $name, $amount, $quantity, $currency = null ) {
		return new Item(
			Method_Helper::string_format_wc( $name ),
			$this->create_formatted_amount( $amount, $currency ),
			$quantity
		);
	}

	/**
	 * @param float $amount
	 * @param null|string $currency
	 *
	 * @return Amount
	 *
	 * @since 3.1.0
	 */
	private function create_formatted_amount( $amount, $currency = null ) {
		if ( null === $currency ) {
			$currency = get_woocommerce_currency();
		}

		return new Amount(
			Method_Helper::number_format_wc( $amount ),
			$currency
		);
	}

	/**
	 * Check if tax amount is set
	 *
	 * @param $tax_amount
	 * @return bool
	 *
	 * @since 3.1.0
	 */
	private function has_tax_amount( $tax_amount ) {
		return null !== $tax_amount;
	}
}
