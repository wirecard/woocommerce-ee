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

require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/helper/class-logger.php' );

/**
 * Class Wirecard_Handler
 *
 * Basic Wirecard handler for payment gateway payments
 *
 * @since 1.0.0
 */
class Wirecard_Handler {

	/**
	 * Array of payment methods
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array
	 */
	private $payment_methods;

	/**
	 * Logger
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var Logger
	 */
	protected $logger;

	/**
	 * Wirecard_Handler constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->payment_methods = array(
			'paypal'          => new WC_Gateway_Wirecard_Paypal(),
			'creditcard'      => new WC_Gateway_Wirecard_Creditcard(),
			'sepadirectdebit' => new WC_Gateway_Wirecard_Sepa_Direct_Debit(),
			'sepacredit'      => new WC_Gateway_Wirecard_Sepa_Credit_Transfer(),
			'ideal'           => new WC_Gateway_Wirecard_Ideal(),
			'eps'             => new WC_Gateway_Wirecard_Eps(),
			'sofortbanking'   => new WC_Gateway_Wirecard_Sofort(),
			'wiretransfer'    => new WC_Gateway_Wirecard_Poipia(),
			'ratepay-invoice' => new WC_Gateway_Wirecard_Guaranteed_Invoice_Ratepay(),
			'alipay-xborder'  => new WC_Gateway_Wirecard_Alipay_Crossborder(),
			'zapp'            => new WC_Gateway_Wirecard_Pay_By_Bank_App(),
			'giropay'         => new WC_Gateway_Wirecard_Giropay(),
		);

		$this->logger = new Logger();

	}

	/**
	 * Getter for payment gateway object for specific payment
	 *
	 * @param string $method_name
	 *
	 * @return WC_Wirecard_Payment_Gateway | null
	 *
	 * @since 1.0.0
	 */
	public function get_payment_method( $method_name ) {
		return isset( $this->payment_methods[ $method_name ] ) ? $this->payment_methods[ $method_name ] : null;
	}
}
