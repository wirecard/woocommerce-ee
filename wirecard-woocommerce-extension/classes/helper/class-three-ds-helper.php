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
require_once WIRECARD_EXTENSION_HELPER_DIR . 'class-additional-information.php';
require_once WIRECARD_EXTENSION_HELPER_DIR . 'class-credit-card-vault.php';

use Wirecard\PaymentSdk\Constant\AuthMethod;
use Wirecard\PaymentSdk\Entity\AccountInfo;
use Wirecard\PaymentSdk\Transaction\Transaction;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\RiskInfo;
use Wirecard\PaymentSdk\Constant\IsoTransactionType;

/**
 * Class Three_DS_Helper
 *
 * Handles creation of 3DS parameters
 *
 * @since   2.1.0
 */
class Three_DS_Helper {

	/**
	 * @var WC_Order
	 */
	private $order;

	/**
	 * @var Transaction
	 */
	private $transaction;

	/**
	 * @var string
	 */
	private $challenge_ind;

	/**
	 * @var string|null
	 */
	private $token_id;

	/**
	 * @var Additional_Information
	 */
	private $additional_helper;

	/**
	 * @var User_Data_Helper
	 */
	private $user_data_helper;

	/**
	 * Three_DS_Helper constructor.
	 * @param WC_Order $order
	 * @param Transaction $transaction
	 * @param string $challenge_ind
	 * @param string|null $token_id
	 * @since 2.1.0
	 */
	public function __construct( $order, $transaction, $challenge_ind, $token_id ) {
		$this->order         = $order;
		$this->transaction   = $transaction;
		$this->challenge_ind = $challenge_ind;
		$this->token_id      = $token_id;

		$this->init();
	}

	/**
	 * Initialize helpers for credit card fields
	 *
	 * @since 2.1.0
	 */
	public function init() {
		$this->additional_helper = new Additional_Information();
		$this->user_data_helper  = new User_Data_Helper( wp_get_current_user(), $this->order, $this->token_id );
	}

	/**
	 * Init 3DS fields and return filled transaction
	 * includes AccountHolder with AccountInfo, Shipping, IsoTransactionType and RiskInfo
	 *
	 * @return Transaction
	 * @since 2.1.0
	 */
	public function get_three_ds_transaction() {
		$shipping_account = $this->get_shipping_account();
		$account_holder   = $this->get_card_holder_account();
		$account_info     = $this->get_account_info();
		$risk_info        = $this->get_risk_info();

		$account_holder->setAccountInfo( $account_info );
		$account_holder->setCrmId( $this->get_merchant_crm_id() );

		$this->transaction->setAccountHolder( $account_holder );
		$this->transaction->setShipping( $shipping_account );
		$this->transaction->setRiskInfo( $risk_info );
		$this->transaction->setIsoTransactionType( IsoTransactionType::GOODS_SERVICE_PURCHASE );

		return $this->transaction;
	}

	/**
	 * Get Shipping with pre-filled shipping data
	 *
	 * @return AccountHolder
	 * @since 2.1.0
	 */
	private function get_shipping_account() {
		$shipping_account = $this->additional_helper->create_account_holder( $this->order, Additional_Information::SHIPPING );

		return $shipping_account;
	}

	/**
	 * Get AccountHolder with pre-filled billing data
	 *
	 * @return AccountHolder
	 * @since 2.1.0
	 */
	private function get_card_holder_account() {
		$card_holder_account = $this->additional_helper->create_account_holder( $this->order, Additional_Information::BILLING );

		return $card_holder_account;
	}

	/**
	 * Create AccountInfo with all available data
	 *
	 * @return AccountInfo
	 * @since 2.1.0
	 */
	private function get_account_info() {
		$account_info = new AccountInfo();
		$account_info->setAuthMethod( $this->get_authentication_method() );
		// No login timestamp available per default (only date - therefor usage of NOW)
		$account_info->setAuthTimestamp( null );
		$account_info->setChallengeInd( $this->challenge_ind );
		// Add specific AccountInfo data for authenticated user
		$account_info = $this->add_authenticated_user_data( $account_info );

		return $account_info;
	}

	/**
	 * Create RiskInfo with all available data
	 *
	 * @return RiskInfo
	 * @since 2.1.0
	 */
	private function get_risk_info() {
		$risk_info = new RiskInfo();

		$risk_info->setDeliveryEmailAddress( $this->user_data_helper->get_delivery_mail() );
		// get reordered info via user_data_helper

		return $risk_info;
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
	 * Add AccountInfo data for logged-in user
	 *
	 * @param AccountInfo $account_info
	 * @return AccountInfo
	 * @since 2.1.0
	 */
	private function add_authenticated_user_data( $account_info ) {
		if ( is_user_logged_in() ) {
			// challenge indicator can not be set to mandate due to missing save for later information in init
			$account_info->setChallengeInd( $this->challenge_ind );
			$account_info->setCreationDate( $this->user_data_helper->get_account_creation_date() );
			$account_info->setUpdateDate( $this->user_data_helper->get_account_update_date() );
			$account_info->setShippingAddressFirstUse( $this->user_data_helper->get_shipping_address_first_use() );
			$account_info->setCardCreationDate( $this->user_data_helper->get_card_creation_date() );
			$account_info->setAmountPurchasesLastSixMonths( $this->user_data_helper->get_successful_orders_last_six_months() );
		}

		return $account_info;
	}

	/**
	 * Get merchant crm id from user id
	 *
	 * @return string | null
	 * @since 2.1.0
	 */
	private function get_merchant_crm_id() {
		$merchant_crm_id = null;
		if ( is_user_logged_in() ) {
			$merchant_crm_id = (string) $this->user_data_helper->get_user_id();
		}

		return $merchant_crm_id;
	}
}
