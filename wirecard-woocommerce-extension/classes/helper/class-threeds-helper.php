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

require_once WIRECARD_EXTENSION_HELPER_DIR . 'class-user-data-helper.php';
require_once WIRECARD_EXTENSION_BASEDIR . 'classes/helper/class-additional-information.php';

use Wirecard\PaymentSdk\Constant\AuthMethod;
use Wirecard\PaymentSdk\Entity\AuthenticationInfo;
use Wirecard\PaymentSdk\Transaction\Transaction;
use Wirecard\PaymentSdk\Entity\ThreeDSRequestor;

/**
 * Class Three_DS_Helper
 *
 * Handles creation of 3DS parameters
 *
 * @since   2.1.0
 */
class Three_DS_Helper {
	
	private $order;

	/**
	 * @var Transaction
	 */
	private $transaction;

	/**
	 * Three_DS_Helper constructor.
	 * @param WC_Order $order
	 * @param Transaction $transaction
	 * @since 2.1.0
	 */
	public function __construct( $order, $transaction )
	{
		$this->order = $order;
		$this->transaction = $transaction;
	}

	/**
	 * Init 3DS fields and return filled transaction
	 * 
	 * @return Transaction
	 * @since 2.1.0
	 */
	public function get_three_ds_transaction() {
		$this->add_three_ds_parameters();
		$this->add_card_holder_data();
		
		return $this->transaction;
	}

	/**
	 * Determines if user is logged in or if guest checkout is used
	 * Maps AuthMethod values for user checkout and guest checkout
	 * 
	 * @return string
	 * @since 2.1.0
	 */
	private function get_authentication_method() {
		if ( is_user_logged_in() ) {
			return AuthMethod::USER_CHECKOUT;
		}
		
		return AuthMethod::GUEST_CHECKOUT;
	}

	/**
	 * Add authentication info and card holder data to transaction
	 *
	 * @since 2.1.0
	 */
	private function add_three_ds_parameters() {
		$requestor = new ThreeDSRequestor();
		$authentication_info = new AuthenticationInfo();
		$authentication_info->setAuthMethod($this->get_authentication_method());
		// no login timestamp available per default
		$authentication_info->setAuthTimestamp(null);
		$requestor->setAuthenticationInfo( $authentication_info );
		//TODO: challengeInd for requestor

		$this->transaction->setThreeDSRequestor( $requestor );

		if ( is_user_logged_in() ) {
			$user_data_helper = new User_Data_Helper( wp_get_current_user(), $this->order );
			$card_holder = $user_data_helper->get_card_holder_data();

			$this->transaction->setCardHolderAccount($card_holder);
		}
	}

	/**
	 * Add account holder data to transaction
	 * 
	 * @since 2.1.0
	 */
	private function add_card_holder_data() {
		$additional_helper = new Additional_Information();
		$account_holder = $additional_helper->create_account_holder( $this->order, Additional_Information::BILLING );
		$shipping_account = $additional_helper->create_account_holder( $this->order, Additional_Information::SHIPPING );
		
		$this->transaction->setAccountHolder( $account_holder);
		$this->transaction->setShipping( $shipping_account );
	}
}
