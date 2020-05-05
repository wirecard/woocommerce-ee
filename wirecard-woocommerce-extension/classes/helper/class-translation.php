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

/**
 * Class Transaction_Type_Translation
 *
 * @since 3.3.0
 */
class Translation {

	/**
	 * Gets list of all transaction type translations
	 *
	 * @return array
	 *
	 * @since 3.3.0
	 */
	public function get_transaction_type_list() {
		return array(
			'check-enrollment'		=> array(
				'title' => __( 'tx_type_check_enrollment', 'wirecard-woocommerce-extension' ),
			),
			'check-payer-response'  => array(
				'title' => __( 'tx_type_check_payer_response', 'wirecard-woocommerce-extension' ),
			),
			'authorization'         => array(
				'title' => __( 'tx_type_authorization', 'wirecard-woocommerce-extension' ),
			),
			'capture-authorization' => array(
				'title' => __( 'tx_type_capture_authorization', 'wirecard-woocommerce-extension' ),
			),
			'refund-capture'        => array(
				'title' => __( 'tx_type_refund_capture', 'wirecard-woocommerce-extension' ),
			),
			'void-authorization'    => array(
				'title' => __( 'tx_type_void_authorization', 'wirecard-woocommerce-extension' ),
			),
			'void-capture'          => array(
				'title' => __( 'tx_type_void_capture', 'wirecard-woocommerce-extension' ),
			),
			'deposit'              	=> array(
				'title' => __( 'tx_type_deposit', 'wirecard-woocommerce-extension' ),
			),
			'purchase'              => array(
				'title' => __( 'tx_type_purchase', 'wirecard-woocommerce-extension' ),
			),
			'debit'              	=> array(
				'title' => __( 'tx_type_debit', 'wirecard-woocommerce-extension' ),
			),
			'refund-purchase'       => array(
				'title' => __( 'tx_type_refund_purchase', 'wirecard-woocommerce-extension' ),
			),
			'refund-debit'          => array(
				'title' => __( 'tx_type_refund_debit', 'wirecard-woocommerce-extension' ),
			),
			'debit-return'          => array(
				'title' => __( 'tx_type_debit_return', 'wirecard-woocommerce-extension' ),
			),
			'void-purchase'         => array(
				'title' => __( 'tx_type_void_purchase', 'wirecard-woocommerce-extension' ),
			),
			'pending-debit'         => array(
				'title' => __( 'tx_type_pending_debit', 'wirecard-woocommerce-extension' ),
			),
			'void-pending-debit'    => array(
				'title' => __( 'tx_type_void_pending_debit', 'wirecard-woocommerce-extension' ),
			),
			'pending-credit'        => array(
				'title' => __( 'tx_type_pending_credit', 'wirecard-woocommerce-extension' ),
			),
			'void-pending-credit'   => array(
				'title' => __( 'tx_type_void_pending_credit', 'wirecard-woocommerce-extension' ),
			),
			'credit'              	=> array(
				'title' => __( 'tx_type_credit', 'wirecard-woocommerce-extension' ),
			),
		);
	}
	
	/**
	 * Gets list of all transaction state translations
	 *
	 * @return array
	 *
	 * @since 3.3.0
	 */
	public function get_transaction_state_list() {
		return array(
			'closed'    => array(
				'title' => __( 'state_closed', 'wirecard-woocommerce-extension' ),
			),
			'open'      => array(
				'title' => __( 'state_open', 'wirecard-woocommerce-extension' ),
			),
			'success'   => array(
				'title' => __( 'state_success', 'wirecard-woocommerce-extension' ),
			),
			'awaiting'	=> array(
				'title' => __( 'state_awaiting', 'wirecard-woocommerce-extension' ),
			),
		);
	}
}
