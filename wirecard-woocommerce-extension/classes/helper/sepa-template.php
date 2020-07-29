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
$html = '
<table border="0" cellpadding="0" cellspacing="0" class="stretch">
	<tr>
		<td class="text11justify">
			<table border="0" width="100%">
				<tr>
					<td class="text11justify">
						<i>' . __( 'creditor', 'wirecard-woocommerce-extension' ) . '</i><br />' .
	$creditor_name . ' ' . $creditor_store_city . '<br />' .
	__( 'creditor_id_input', 'wirecard-woocommerce-extension' ) . ':' . $creditor_id . '<br />
					</td>
					<td width="10%">&nbsp;</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<table border="0" width="100%">
				<tr>
					<td class="text11">
						<i>' . __( 'debtor', 'wirecard-woocommerce-extension' ) . '</i><br />' .
	__( 'debtor_acc_owner', 'wirecard-woocommerce-extension' ) . ': <span class="first_last_name"></span><br />' .
	__( 'iban_input', 'wirecard-woocommerce-extension' ) . ': <span class="bank_iban"></span><br />';
if ( ( $this->get_option( 'enable_bic' ) === 'yes' ) ) {
	$html .= __( 'bic_input', 'wirecard-woocommerce-extension' ) . ':<span class="bank_bic"></span><br />';
}
$html .= '</td>
					<td width="10%">&nbsp;</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td class="text11justify">
			<table border="0" width="100%">
				<tr>
					<td class="text11justify">' .
	__( 'sepa_text_1', 'wirecard-woocommerce-extension' ) . ' ' .
	$creditor_name . ' ' .
	__( 'sepa_text_2', 'wirecard-woocommerce-extension' ) . ' ' .
	$creditor_name . ' ' . __( 'sepa_text_2b', 'wirecard-woocommerce-extension' ) . ' ' . $additional_text . '
					</td>
					<td width="10%">&nbsp;</td>
				</tr>
				<tr>
					<td class="text11justify">' .
	__( 'sepa_text_3', 'wirecard-woocommerce-extension' ) . '
					</td>
					<td width="10%">&nbsp;</td>
				</tr>
				<tr>
					<td class="text11justify">' .
	__( 'sepa_text_4', 'wirecard-woocommerce-extension' ) . ' ' .
	$creditor_name . ' ' .
	__( 'sepa_text_5', 'wirecard-woocommerce-extension' ) . '
					</td>
					<td width="10%">&nbsp;</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td class="text11justify">
			<table border="0" width="100%">
				<tr>
					<td class="text11justify">' .
	$creditor_store_city . ' ' . gmdate( 'd.m.Y' ) . ' <span class="first_last_name"></span>
					</td>
					<td width="10%">&nbsp;</td>
				</tr>
				<tr>
					<td>
						<input type="checkbox" id="sepa-check">&nbsp;<label for="sepa-check">' . __( 'sepa_text_6', 'wirecard-woocommerce-extension' ) . '</label>
					</td>
				</tr>
				<tr>
					<td style="text-align: right;"><button id="sepa-button"> ' . __( 'cancel', 'wirecard-woocommerce-extension' ) . '</button></td>
				</tr>
			</table>
		</td>
	</tr>
</table>';
