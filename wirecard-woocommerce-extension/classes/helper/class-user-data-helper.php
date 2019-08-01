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

	/**
	 * @var WP_User
	 */
	private $user;

	/**
	 * @var WC_Order
	 */
	private $current_order;

	/**
	 * @var string
	 */
	private $challenge_ind;

	/**
	 * @var string|null
	 */
	private $token_id;

	/**
	 * User_Data_Helper constructor.
	 * @param WP_User $user
	 * @param WC_Order $order
	 * @param string $challenge_ind
	 * @param string|null $token_id
	 *
	 * @since 2.1.0
	 */
	public function __construct( $user, $order, $challenge_ind, $token_id ) {
		$this->user          = $user;
		$this->current_order = $order;
		$this->challenge_ind = $challenge_ind;
		$this->token_id      = $token_id;
	}

	/**
	 * @return DateTime|null|string
	 */
	public function get_card_creation_date() {
		$card_creation_date = null;

		if ( null !== $this->token_id ) {
			$vault              = new Credit_Card_Vault();
			$card_creation_date = $vault->get_card_creation_for_user( $this->user->ID, $this->token_id );
		}
		if ( $card_creation_date instanceof DateTime ) {
			return $card_creation_date;
		}

		return new DateTime();
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
		$date_time = new DateTime();
		$date_time->format( AccountInfo::DATE_FORMAT );
		$date_time->setTimestamp( $timestamp );

		return $date_time;
	}

	/**
	 * Get DateTime for first shipping address usage
	 *
	 * @return NULL|WC_DateTime
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
		$first_order = reset( $orders );
		if ( $first_order ) {

			return $first_order->get_date_created();
		}

		return null;
	}

	/**
	 * Get successful purchased orders within last six months
	 *
	 * @return int
	 * @since 2.1.0
	 */
	public function get_successful_orders_last_six_months() {
		$arguments = array(
			'customer'   => $this->user->ID,
			'limit'      => self::UNLIMITED,
			'status'     => 'processing',
			'date_after' => '6 months ago',
		);

		return count( $this->get_order_array_with_args( $arguments ) );
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
	 * Get challenge indicator depending on existing token
	 *   04  - for new one-click-checkout token
	 *   predefined indicator from settings for existing token and non-one-click-checkout
	 *
	 * @return string
	 * @since 2.1.0
	 */
	public function get_challenge_indicator() {
		if ( is_null( $this->token_id ) ) {
			return $this->challenge_ind;
		}

		$vault = new Credit_Card_Vault();
		if ( $vault->is_existing_token_for_user( $this->user->ID, $this->token_id ) ) {
			return $this->challenge_ind;
		}

		return ChallengeInd::CHALLENGE_MANDATE;
	}

	/**
	 * Get delivery mail address
	 * temp send billing mail address because there is no dedicated mail address for electronic/virtual goods
	 *
	 * @return string | null
	 * @since 2.1.0
	 */
	public function get_delivery_mail() {
		if ( ! empty( $this->current_order->get_billing_email() ) ) {
			return $this->current_order->get_billing_email();
		}

		return null;
	}

	/**
	 * Checks if one of the products within current order was bought before
	 *
	 * @return bool
	 * @since 2.1.0
	 */
	public function is_reordered_items() {
		/** @var WC_Order_Item[] $products */
		$order_items = $this->current_order->get_items();

		/** @var WC_Order_Item $item */
		foreach ( $order_items as $item ) {
			if ( 'line_item' !== $item->get_type() ) {
				continue;
			}
			/** @var WC_Order_Item_Product $item */
			$reordered = wc_customer_bought_product( $this->user->user_email, $this->user->ID, $item->get_product_id() );
			if ( $reordered ) {
				return RiskInfoReorder::REORDERED;
			}
		}

		return RiskInfoReorder::FIRST_TIME_ORDERED;
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
