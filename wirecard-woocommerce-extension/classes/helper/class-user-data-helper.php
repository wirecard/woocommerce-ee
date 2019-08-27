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

require_once WIRECARD_EXTENSION_HELPER_DIR . 'class-credit-card-vault.php';

use Wirecard\PaymentSdk\Entity\AccountInfo;
use Wirecard\PaymentSdk\Constant\RiskInfoReorder;
use Wirecard\PaymentSdk\Constant\ChallengeInd;

/**
 * Class User_Data_Helper
 *
 * Retrieves user data
 *
 * @since   2.1.0
 */
class User_Data_Helper {

	const UNLIMITED = -1;

	const SUCCESS_ORDER_STATES = array( 'processing', 'completed', 'refunded', 'cancelled', 'authorization' );

	/**
	 * @var WP_User
	 */
	private $user;

	/**
	 * @var WC_Order
	 */
	private $current_order;

	/**
	 * @var string|null
	 */
	private $token_id;

	/**
	 * User_Data_Helper constructor.
	 * @param WP_User $user
	 * @param WC_Order $order
	 * @param string|null $token_id
	 *
	 * @since 2.1.0
	 */
	public function __construct( $user, $order, $token_id ) {
		$this->user          = $user;
		$this->current_order = $order;
		$this->token_id      = $token_id;
	}

	/**
	 * Get card creation date for token usage or now for non-one-click-checkout
	 *
	 * @return DateTime
	 * @since 2.1.0
	 */
	public function get_card_creation_date() {
		$creation_date = new DateTime();
		if ( null !== $this->token_id ) {
			$creation_date = $this->get_token_creation_date();
		}

		return $creation_date;
	}

	/**
	 * Get token creation date for user
	 *
	 * @return bool|DateTime
	 * @since 2.1.0
	 */
	private function get_token_creation_date() {
		$vault              = new Credit_Card_Vault();
		$card_creation_date = $vault->get_card_creation_for_user( $this->user->ID, $this->token_id );
		if ( ! $card_creation_date instanceof DateTime ) {
			$card_creation_date = new DateTime();
		}

		return $card_creation_date;
	}

	/**
	 * Get card holder account creation date - user registration date
	 *
	 * @return DateTime
	 * @since 2.1.0
	 */
	public function get_account_creation_date() {
		$date_time = new DateTime( $this->user->user_registered );

		return $date_time;
	}

	/**
	 * Get card holder account update date
	 *
	 * @return DateTime
	 * @since 2.1.0
	 */
	public function get_account_update_date() {
		$update_date = get_user_meta( $this->user->ID, 'last_update', true );
		$date_time   = $this->convert_timestamp_to_date_time( $update_date );

		return $date_time;
	}

	/**
	 * Converts timestamp to DateTime formatted with 'Y-m-d'
	 *
	 * @param string $timestamp
	 * @return DateTime
	 * @since 2.1.0
	 */
	private function convert_timestamp_to_date_time( $timestamp ) {
		$date_time = new DateTime( '@' . $timestamp );
		$date_time->format( AccountInfo::DATE_FORMAT );

		return $date_time;
	}

	/**
	 * Get DateTime for first shipping address usage
	 *
	 * @return WC_DateTime|DateTime
	 * @since 2.1.0
	 */
	public function get_shipping_address_first_use() {
		$arguments = array(
			'customer'           => $this->user->ID,
			'limit'              => 1,
			'orderby'            => 'date',
			'order'              => 'ASC',
			'shipping_address_1' => $this->current_order->get_shipping_address_1(),
		);

		/** @var array $orders */
		$orders = $this->get_order_array_with_args( $arguments );
		/** @var WC_Order $first_order */
		$first_order    = reset( $orders );
		$first_use_date = new DateTime();
		if ( $first_order ) {
			$first_use_date = $first_order->get_date_created();
		}

		return $first_use_date;
	}

	/**
	 * Get successful orders within last six months
	 * Successful order status includes:
	 *  - processing (purchase/debit) - standard WC Order State
	 *  - completed - standard WC Order State for shipped orders
	 *  - refunded - standard WC Order State for refunded orders (still successful)
	 *  - cancelled - standard WC Order State for cancelled order (still successful)
	 *  - authorization - Wirecard Order State for authorized/reserved payments
	 *
	 * @return int
	 * @since 2.1.0
	 */
	public function get_successful_orders_last_six_months() {
		$order_count = 0;
		foreach ( self::SUCCESS_ORDER_STATES as $status ) {
			$arguments    = array(
				'customer'   => $this->user->ID,
				'limit'      => self::UNLIMITED,
				'status'     => $status,
				'date_after' => '6 months ago',
			);
			$orders       = $this->get_order_array_with_args( $arguments );
			$order_count += count( $orders );
		}

		return $order_count;
	}

	/**
	 * Get user ID
	 *
	 * @return int
	 * @since 2.1.0
	 */
	public function get_user_id() {
		return $this->user->ID;
	}

	/**
	 * Get delivery mail address
	 * temp send billing mail address because there is no dedicated mail address for electronic/virtual goods
	 *
	 * @return string | null
	 * @since 2.1.0
	 */
	public function get_delivery_mail() {
		$delivery_mail = null;
		if ( ! empty( $this->current_order->get_billing_email() ) ) {
			$delivery_mail = $this->current_order->get_billing_email();
		}

		return $delivery_mail;
	}

	/**
	 * Returns correct risk reorder information for current order products
	 * TODO: clarify if full order or only product has to be reordered
	 *
	 * @return string
	 * @since 2.1.0
	 */
	public function get_reordered_info() {
		$reordered = RiskInfoReorder::FIRST_TIME_ORDERED;
		/** @var WC_Order_Item[] $products */
		$order_items = $this->current_order->get_items();
		/** @var WC_Order_Item_Product $item */
		foreach ( $order_items as $item ) {
			if ( $item->is_type( 'line_item' )
				&& wc_customer_bought_product( $this->user->user_email, $this->user->ID, $item->get_product_id() )
			) {
				$reordered = RiskInfoReorder::REORDERED;
			}
		}

		return $reordered;
	}

	/**
	 * Get array with wc order data according to arguments
	 * Override paginate = false to avoid stdClass
	 *
	 * @param $args
	 * @return array
	 * @since 2.1.0
	 */
	private function get_order_array_with_args( $args ) {
		$no_paginate = array(
			'paginate' => false,
		);
		$args        = array_merge( $no_paginate, $args );
		$orders      = wc_get_orders( $args );

		return $orders;
	}
}
