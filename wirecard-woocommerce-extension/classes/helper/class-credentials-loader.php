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

use Credentials\Credentials;
use Credentials\Exception\InvalidPaymentMethodException;
use Credentials\Exception\InvalidXMLFormatException;
use Credentials\Exception\MissedCredentialsException;

/**
 * Class Credentials_Loader
 *
 * Handles credentails data retrieval
 *
 * @since   3.1.1
 */
class Credentials_Loader {

	const CREDIT_CARD_ID = 'creditcard';
	
	public function getCredentials($payment_type) {
		$credentialFilePath = dirname(dirname(__DIR__)) . "/credentials_config.xml";
		$credentials = [];
		try {
			$module = new Credentials($credentialFilePath);
			$payment = $module->getCredentialsByPaymentMethod($payment_type);
			if($payment) {
				$credentials['merchant_account_id'] = $payment->getMerchantAccountId();
				$credentials['secret'] = $payment->getSecret();
				$credentials['http_user'] = $payment->getHttpUser();
				$credentials['http_pass'] = $payment->getHttpPassword();
				$credentials['base_url'] = $payment->getBaseUrl();
			}
			if($payment_type === self::CREDIT_CARD_ID) {
				$credentials['three_d_merchant_account_id'] = $payment->getThreeDMerchantAccountId();
				$credentials['three_d_secret'] = $payment->getThreeDSecret();
				$credentials['wpp_url'] = $payment->getWppUrl();
			}
		} catch (InvalidPaymentMethodException $e) {
			$credentials = [];
		} catch (InvalidXMLFormatException $e) {
			$credentials = [];
		} catch (MissedCredentialsException $e) {
			$credentials = [];
		}
		return $credentials;
	}
}
