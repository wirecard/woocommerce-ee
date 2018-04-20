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

/**
 * Class Credit_Card_Vault
 *
 * @since   1.1.0
 */
class Credit_Card_Vault {

	/**
	 * Vault table name in database
	 *
	 * @since  1.1.0
	 * @access private
	 * @var string
	 */
	private $table_name;

	/**
	 * Credit_Card_Vault constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'wirecard_payment_gateway_vault';

	}

	/**
	 * Save credit card data to db
	 *
	 * @param $user_id
	 * @param $token
	 * @param $pan
	 * @return int
	 * @since 1.1.0
	 */
	public function save_card( $user_id, $token, $pan ) {
		global $wpdb;
		$wpdb->insert(
			$this->table_name,
			array(
				'user_id'        => $user_id,
				'token'          => $token,
				'masked_pan'     => $pan,
			),
			array('%d', '%s', '%s')
		);

		return $wpdb->insert_id;
	}

	/**
	 * Get all credit cards for a user
	 *
	 * @param int $user_id
	 * @return array|bool|null|object
	 * @since 1.1.0
	 */
	public function get_cards_for_user( $user_id ) {
		$cards = $this->get_cards_from_db( $user_id );
		if ( false != $cards ) {
			return $this->fetch_template_data( $cards );
		}

		return false;
	}

	public function delete_credit_card( $vault_id ) {
		global $wpdb;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wirecard_payment_gateway_vault WHERE vault_id = %d", $vault_id ) );
	}

	private function get_cards_from_db( $user_id ) {
		global $wpdb;

		$cards = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wirecard_payment_gateway_vault WHERE user_id = %s", $user_id ) );

		if ( empty( $cards ) ) {
			return false;
		}

		return $cards;
	}

	private function fetch_template_data( $cards ) {
		$html = '<table id="vault-table">
		<tr>
			<th></th>
			<th>' . __( 'Account number', 'woocommerce-gateway-wirecard' ) . '</th>
			<th>' . __( 'Delete Card', 'woocommerce-gateway-wirecard' ) . '</th>
		</tr>';
		foreach ($cards as $card) {
			$html .= '<tr>
				<td><input class="token" name="token" onclick="javascript:setToken()" type="radio" data-token="' . $card->token . '" /></td>
				<td>' . $card->masked_pan . '</td>
				<td><div class="delete-from-vault" onclick="javascript:deleteCard(' . $card->vault_id . ')">' . __( 'Delete', 'woocommerce-gateway-wirecard' ) . '</div></td>
			</tr>';
		}
		$html .= '</table>';
		return $html;
	}
}