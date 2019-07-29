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

use Wirecard\PaymentSdk\Entity\AuthenticationInfo;
use Wirecard\PaymentSdk\Entity\CardHolderAccount;

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
	 * User_Data_Helper constructor.
	 * @param WP_User $user
	 * @param WC_Order $order
	 *
	 * @since 2.1.0
	 */
	public function __construct( $user, $order ) {
		$this->user          = $user;
		$this->current_order = $order;
	}

	/**
	 * Get ThreeDS CardHolder Data
	 *
	 * @return CardHolderAccount
	 * @since 2.1.0
	 */
	public function get_card_holder_data() {
		$card_holder = new CardHolderAccount();
		$card_holder->setCreationDate( $this->get_account_creation_date() );
		$card_holder->setUpdateDate( $this->get_account_update_date() );
		$card_holder->setShippingAddressFirstUse( $this->get_shipping_address_first_use() );
		$card_holder->setAmountPurchasesLastSixMonths( $this->get_successful_orders_last_six_months() );
		$card_holder->setMerchantCrmId( $this->user->ID );

		return $card_holder;
	}
	
	public function get_card_information() {
		$vault = new Credit_Card_Vault();
		$cards = $vault->get_cards_for_user( $this->user->ID );
		//creation date does not exist
	}

	/**
	 * Get card holder account creation date - user registration date
	 *
	 * @return DateTime
	 * @since 2.1.0
	 */
	private function get_account_creation_date() {
		$date_time = new DateTime( $this->user->user_registered );

		return $date_time;
	}

	/**
	 * Get card holder account update date
	 *
	 * @return DateTime
	 * @since 2.1.0
	 */
	private function get_account_update_date() {
		$update_date = get_user_meta( $this->user->ID, 'last_update', true );
		$date_time   = $this->convert_timestamp_to_date_time( $update_date );

		return $date_time;
	}

	/**
	 * Converts timestamp to DateTime formatted with 'Y-m-d\TH:i:s\Z'
	 *
	 * @param string $timestamp
	 * @return DateTime
	 * @since 2.1.0
	 */
	private function convert_timestamp_to_date_time( $timestamp ) {
		$date_time = new DateTime();
		$date_time->format( AuthenticationInfo::DATE_FORMAT );
		$date_time->setTimestamp( $timestamp );

		return $date_time;
	}

	/**
	 * Get DateTime for first shipping address usage
	 *
	 * @return NULL|WC_DateTime
	 * @since 2.1.0
	 */
	private function get_shipping_address_first_use() {
		$arguments = array(
			'customer'           => $this->user->ID,
			'limit'              => 1,
			'orderby'            => 'date',
			'order'              => 'ASC',
			'shipping_address_1' => $this->current_order->get_shipping_address_1(),
		);

		/** @var array $orders */
		$orders = $this->get_order_array_with_args( $arguments );

		if ( empty( $orders ) ) {
			return null;
		}
		/** @var WC_Order $first_order */
		$first_order = $orders[0];

		return $first_order->get_date_created();
	}

	/**
	 * Get successful purchased orders within last six months
	 *
	 * @return int
	 * @since 2.1.0
	 */
	private function get_successful_orders_last_six_months() {
		$arguments = array(
			'customer'   => $this->user->ID,
			'limit'      => self::UNLIMITED,
			'status'     => 'processing',
			'date_after' => '6 months ago',
		);

		return count( $this->get_order_array_with_args( $arguments ) );
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
