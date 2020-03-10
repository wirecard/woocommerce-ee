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

	/**
	 * Get credentials from the xml using Credentials class methods
	 *
	 * @param $payment_type
	 *
	 * @return array
	 *
	 * @since 3.1.1
	 */
	public function get_credentials( $payment_type ) {
		$credential_file_path = dirname( dirname( __DIR__ ) ) . '/credentials_config.xml';
		$credentials          = [];
		try {
			$module  = new Credentials( $credential_file_path );
			$payment = $module->getCredentialsByPaymentMethod( $payment_type );
			if ( $payment ) {
				$credentials['merchant_account_id'] = $payment->getMerchantAccountId();
				$credentials['secret'] = $payment->getSecret();
				$credentials['http_user'] = $payment->getHttpUser();
				$credentials['http_pass'] = $payment->getHttpPassword();
				$credentials['base_url'] = $payment->getBaseUrl();
			}
			if ( self::CREDIT_CARD_ID === $payment_type ) {
				$credentials['three_d_merchant_account_id'] = $payment->getThreeDMerchantAccountId();
				$credentials['three_d_secret']              = $payment->getThreeDSecret();
				$credentials['wpp_url']                     = $payment->getWppUrl();
			}
		} catch ( InvalidPaymentMethodException $e ) {
			$credentials = [];
		} catch ( InvalidXMLFormatException $e ) {
			$credentials = [];
		} catch ( MissedCredentialsException $e ) {
			$credentials = [];
		}
		return $credentials;
	}

	/**
	 * Create structure of data for the credentials fields
	 *
	 * @param $payment_method
	 *
	 * @return array
	 *
	 * @since 3.1.1
	 */
	public function get_credentials_config( $payment_method ) {
		$credentials           = $this->get_credentials( $payment_method );
		$credentials_config_cc = [];
		$credentials_config    = array(
			'merchant_account_id' => array( 
				'title'       => __( 'config_merchant_account_id', 'wirecard-woocommerce-extension' ),
			    'type'        => 'text',
			    'description' => __( 'config_merchant_account_id_desc', 'wirecard-woocommerce-extension' ),
			    'default'     => $credentials['merchant_account_id'],
			),
			'secret'              => array(
                'title'       => __( 'config_merchant_secret', 'wirecard-woocommerce-extension' ),
                'type'        => 'text',
                'description' => __( 'config_merchant_secret_desc', 'wirecard-woocommerce-extension' ),
                'default'     => $credentials['secret'],
	        ),
	        'credentials'         => array(
	            'title'       => __( 'text_credentials', 'wirecard-woocommerce-extension' ),
	            'type'        => 'title',
	            'description' => __( 'text_credentials_desc', 'wirecard-woocommerce-extension' ),
	        ),
	        'base_url'            => array(
	            'title'       => __( 'config_base_url', 'wirecard-woocommerce-extension' ),
	            'type'        => 'text',
	            'description' => __( 'config_base_url_desc', 'wirecard-woocommerce-extension' ),
	            'default'     => $credentials['base_url'],
	        ), 
			'http_user'           => array(
	            'title'       => __( 'config_http_user', 'wirecard-woocommerce-extension' ),
	            'type'        => 'text',
	            'description' => __( 'config_http_user_desc', 'wirecard-woocommerce-extension' ),
	            'default'     => $credentials['http_user'],
	        ),
	        'http_pass'           => array(
	            'title'       => __( 'config_http_password', 'wirecard-woocommerce-extension' ),
	            'type'        => 'text',
	            'description' => __( 'config_http_password_desc', 'wirecard-woocommerce-extension' ),
	            'default'     => $credentials['http_pass'],
	        ),
		);
		if ( $payment_method === self::CREDIT_CARD_ID ) {
			$credentials_config_cc = array(
				'three_d_merchant_account_id' => array(
					'title'       => __( 'config_three_d_merchant_account_id', 'wirecard-woocommerce-extension' ),
					'type'        => 'text',
					'description' => __( 'config_three_d_merchant_account_id_desc', 'wirecard-woocommerce-extension' ),
					'default'     => $credentials['three_d_merchant_account_id'],
				),
				'three_d_secret'              => array(
					'title'       => __( 'config_three_d_merchant_secret', 'wirecard-woocommerce-extension' ),
					'type'        => 'text',
					'description' => __( 'config_three_d_merchant_secret_desc', 'wirecard-woocommerce-extension' ),
					'default'     => $credentials['three_d_secret'],
				),
				'wpp_url'                     => array(
					'title'       => __( 'config_wpp_url', 'wirecard-woocommerce-extension' ),
					'type'        => 'text',
					'description' => __( 'config_wpp_url_desc', 'wirecard-woocommerce-extension' ),
					'default'     => $credentials['wpp_url'],
				)
			);
		}
		return array_merge( $credentials_config,$credentials_config_cc );
	}
}
