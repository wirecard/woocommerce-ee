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
						<i>' . __( 'Creditor', 'wooocommerce-gateway-wirecard' ) . '</i><br />' .
						$creditor_name . ' ' . $creditor_store_city . '<br />' .
						__( 'Creditor ID:', 'wooocommerce-gateway-wirecard' ) . $creditor_id . '<br />
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
						<i>' . __( 'Debtor', 'wooocommerce-gateway-wirecard' ) . '</i><br />' .
						__( 'Account owner:', 'wooocommerce-gateway-wirecard' ) . ' <span class="first_last_name"></span><br />' .
						__( 'IBAN:', 'wooocommerce-gateway-wirecard' ) . ' <span class="bank_iban"></span><br />';
if ( ( $this->get_option( 'enable_bic' ) == 'yes' ) ) {
	$html .= __( 'BIC:', 'wooocommerce-gateway-wirecard' ) . '<span class="bank_bic"></span><br />';
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
						__( 'I authorize the creditor ', 'wooocommerce-gateway-wirecard' ) .
						$creditor_name .
						__( ' to send instructions to my bank to collect one single direct debit from my account. At the same time I instruct my bank to debit my account in accordance with the instructions from the creditor ', 'wooocommerce-gateway-wirecard' ) .
							$creditor_name . ' ' . $additional_text . '
					</td>
					<td width="10%">&nbsp;</td>
				</tr>
				<tr>
					<td class="text11justify">' .
						__( 'Note: As part of my rights, I am entitled to a refund under the terms and conditions of my agreement with my bank. A refund must be claimed within 8 weeks starting from the date on which my account was debited.', 'wooocommerce-gateway-wirecard' ) . '
					</td>
					<td width="10%">&nbsp;</td>
				</tr>
				<tr>
					<td class="text11justify">' .
						__( 'I irrevocably agree that, in the event that the direct debit is not honored, or objection against the direct debit exists, my bank will disclose to the creditor ', 'wooocommerce-gateway-wirecard' ) .
							$creditor_name .
							__( ' my full name, address and date of birth.', 'wooocommerce-gateway-wirecard' ) . '
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
						$creditor_store_city . ' ' . date( 'd.m.Y' ) . ' <span class="first_last_name"></span>
					</td>
					<td width="10%">&nbsp;</td>
				</tr>
				<tr>
					<td>
						<input type="checkbox" id="sepa-check">&nbsp;<label for="sepa-check">' . __( 'I have read and accepted the SEPA Direct Debit Mandate information.', 'wooocommerce-gateway-wirecard' ) . '</label>
					</td>
				</tr>
				<tr>
					<td style="text-align: right;"><button id="sepa-button"> ' . __( 'Cancel', 'wooocommerce-gateway-wirecard' ) . '</button></td>
				</tr>
			</table>
		</td>
	</tr>
</table>';
