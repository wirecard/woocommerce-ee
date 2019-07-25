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
	
	public function __construct( $user, $order )
	{
		$this->user = $user;
		$this->current_order = $order;
	}
	
	public function get_card_holder_data() {
		$card_holder = new CardHolderAccount();
		$card_holder->setCreationDate( $this->get_account_creation_date() );
		$card_holder->setUpdateDate( $this->get_account_update_date() );
		$card_holder->setShippingAddressFirstUse( $this->get_shipping_address_first_use() );
		$card_holder->setAmountPurchasesLastSixMonths( $this->get_successful_orders_last_six_months() );
		$card_holder->setMerchantCrmId( $this->user->ID );
		
		return $card_holder;
	}

	private function get_account_creation_date() {
		$date_time = new DateTime( $this->user->user_registered );
		
		return $date_time;
	}

	private function get_account_update_date() {
		$update_date = get_user_meta( $this->user->ID, 'last_update', true );
		$date_time = $this->convertTimestampToDateTime( $update_date );
		
		return $date_time;
	}
	
	private function convertTimestampToDateTime( $timestamp ) {
		$date_time = new DateTime();
		$date_time->format( AuthenticationInfo::DATE_FORMAT );
		$date_time->setTimestamp( $timestamp );
		
		return $date_time;
	}
	
	private function get_shipping_address_first_use() {
		$arguments = array(
			'customer' => $this->user->ID,
			'limit'    => 1,
			'orderby'  => 'date',
			'order'    => 'ASC',
			'shipping_address_1' => $this->current_order->get_shipping_address_1()
		);

		/** @var array $orders */
		$orders = $this->get_orders_with_args( $arguments );
		
		if ( empty( $orders ) ) {
			return null;
		}
		/** @var WC_Order $first_order */
		$first_order = $orders[0];
		
		return $first_order->get_date_created();
	}
	
	private function get_successful_orders_last_six_months() {
		$arguments = array(
			'customer' => $this->user->ID,
			'limit'		=> self::UNLIMITED,
			'status'	=> 'processing',
			'date_after' => '6 months ago'
		);
		
		return count( $this->get_orders_with_args( $arguments ) );
	}
	
	private function get_orders_with_args( $args ) {
		$orders = wc_get_orders( $args );
		
		return $orders;
	}
}
