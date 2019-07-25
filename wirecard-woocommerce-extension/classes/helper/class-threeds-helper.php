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

use Wirecard\PaymentSdk\Constant\AuthMethod;
use Wirecard\PaymentSdk\Entity\AuthenticationInfo;

/**
 * Class Three_DS_Helper
 *
 * Handles creation of 3DS parameters
 *
 * @since   2.1.0
 */
class Three_DS_Helper {
	
	private $order;
	
	public function __construct( $order )
	{
		$this->order = $order;
	}

	/**
	 * Not mandatory - temporary all mand.
	 */
	public function setMandatoryParams() {
		$authentication_info = new AuthenticationInfo();
		$authentication_info->setAuthMethod($this->get_authentication_method());
		// no login timestamp available per default
		$authentication_info->setAuthTimestamp(null);
		
		if ( is_user_logged_in() ) {
			$user_data_helper = new User_Data_Helper( wp_get_current_user(), $this->order );
			$card_holder = $user_data_helper->get_card_holder_data();
		}
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
}
