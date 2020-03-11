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
use Credentials\PaymentMethod;
use Credentials\Exception\InvalidPaymentMethodException;

/**
 * Class Credentials_Loader
 *
 * Handles credentials data retrieval
 *
 * @since   3.1.1
 */
class Credentials_Loader {


	const CREDENTIALS_CONFIG_FILE = 'credentials_config.xml';

	/**
	 * Credentials file path
	 *
	 * @since  3.1.1
	 * @access private
	 * @var string
	 */
	private $credential_file_path;

	/**
	 * Logger
	 *
	 * @since  3.1.1
	 * @access private
	 * @var string
	 */
	private $logger;

	/**
	 * @var Credentials_Loader
	 */
	private static $instance;
	
	/**
	 * Credentials_Loader constructor.
	 *
	 * @since 3.1.1
	 */
	private function __construct() {
		$this->credential_file_path = dirname( dirname( __DIR__ ) ) . '/' . self::CREDENTIALS_CONFIG_FILE;
		$this->logger               = new Logger();
	}

	/**
	 * @return Credentials_Loader
	 */
	public static function getInstance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Create structure of data for the credentials fields
	 *
	 * @param string $payment_method_name
	 *
	 * @return \Credentials\Config\CredentialsConfigInterface|\Credentials\Config\CredentialsCreditCardConfig
	 *
	 * @throws InvalidPaymentMethodException
	 * @since 3.1.1
	 */
	public function get_credentials_config( $payment_method_name ) {
		$credentials = null;

		try {
			$module      = new Credentials( $this->credential_file_path );
			$credentials = $module->getConfigByPaymentMethod( new PaymentMethod( $payment_method_name ) );
		} catch ( \Exception $exception ) {
			$this->logger->error( __METHOD__ . ':' . $exception->getMessage() );
		}

		return $credentials;
	}
}
