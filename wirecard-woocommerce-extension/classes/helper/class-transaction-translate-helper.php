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
 * Class Transaction_Translation
 *
 * @since 3.3.0
 */
class Transaction_Translate_Helper {

	/**
	 * Returns list of transaction header
	 *
	 * @return array
	 *
	 * @since 3.3.0
	 */
	public function get_table_header_translations() {
		return array(
			'tx_id'                 => __( 'panel_transaction', 'wirecard-woocommerce-extension' ),
			'order_id'              => __( 'panel_order_number', 'wirecard-woocommerce-extension' ),
			'transaction_id'        => __( 'panel_transcation_id', 'wirecard-woocommerce-extension' ),
			'parent_transaction_id' => __( 'panel_parent_transaction_id', 'wirecard-woocommerce-extension' ),
			'transaction_type'      => __( 'panel_action', 'wirecard-woocommerce-extension' ),
			'payment_method'        => __( 'panel_payment_method', 'wirecard-woocommerce-extension' ),
			'transaction_state'     => __( 'panel_transaction_state', 'wirecard-woocommerce-extension' ),
			'amount'                => __( 'panel_amount', 'wirecard-woocommerce-extension' ),
			'currency'              => __( 'panel_currency', 'wirecard-woocommerce-extension' ),
		);
	}

	/**
	 * Returns translated key for transaction table
	 *
	 * @param string $field_key
	 * @return string
	 *
	 * @since 3.3.0
	 */
	public function translate( $field_key ) {
		$translations = $this->get_translations();
		if ( array_key_exists( $field_key, $translations ) ) {
			return $translations[ $field_key ];
		}
		return $field_key;
	}

	/**
	 * Returns list of all transaction translations
	 *
	 * @return array
	 *
	 * @since 3.3.0
	 */
	private function get_translations() {
		return array(
			'check-enrollment'      => __( 'tx_type_check_enrollment', 'wirecard-woocommerce-extension' ),
			'check-payer-response'  => __( 'tx_type_check_payer_response', 'wirecard-woocommerce-extension' ),
			'authorization'         => __( 'tx_type_authorization', 'wirecard-woocommerce-extension' ),
			'capture-authorization' => __( 'tx_type_capture_authorization', 'wirecard-woocommerce-extension' ),
			'refund-capture'        => __( 'tx_type_refund_capture', 'wirecard-woocommerce-extension' ),
			'void-authorization'    => __( 'tx_type_void_authorization', 'wirecard-woocommerce-extension' ),
			'void-capture'          => __( 'tx_type_void_capture', 'wirecard-woocommerce-extension' ),
			'deposit'               => __( 'tx_type_deposit', 'wirecard-woocommerce-extension' ),
			'purchase'              => __( 'tx_type_purchase', 'wirecard-woocommerce-extension' ),
			'debit'                 => __( 'tx_type_debit', 'wirecard-woocommerce-extension' ),
			'refund-purchase'       => __( 'tx_type_refund_purchase', 'wirecard-woocommerce-extension' ),
			'refund-debit'          => __( 'tx_type_refund_debit', 'wirecard-woocommerce-extension' ),
			'debit-return'          => __( 'tx_type_debit_return', 'wirecard-woocommerce-extension' ),
			'void-purchase'         => __( 'tx_type_void_purchase', 'wirecard-woocommerce-extension' ),
			'pending-debit'         => __( 'tx_type_pending_debit', 'wirecard-woocommerce-extension' ),
			'void-pending-debit'    => __( 'tx_type_void_pending_debit', 'wirecard-woocommerce-extension' ),
			'pending-credit'        => __( 'tx_type_pending_credit', 'wirecard-woocommerce-extension' ),
			'void-pending-credit'   => __( 'tx_type_void_pending_credit', 'wirecard-woocommerce-extension' ),
			'credit'                => __( 'tx_type_credit', 'wirecard-woocommerce-extension' ),
			'closed'                => __( 'state_closed', 'wirecard-woocommerce-extension' ),
			'open'                  => __( 'state_open', 'wirecard-woocommerce-extension' ),
			'success'               => __( 'state_success', 'wirecard-woocommerce-extension' ),
			'awaiting'              => __( 'state_awaiting', 'wirecard-woocommerce-extension' ),
		);
	}
}
