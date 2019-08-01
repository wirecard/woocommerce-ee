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

		$this->table_name = $wpdb->prefix . 'wirecard_payment_gateway_vault';

	}

	/**
	 * Save credit card data to db
	 *
	 * @param int $user_id
	 * @param string $token
	 * @param string $pan
	 * @return int
	 * @since 1.1.0
	 */
	public function save_card( $user_id, $token, $pan ) {
		global $wpdb;

		$cards = $this->get_cards_from_db( $user_id );
		if ( ! empty( $cards ) ) {
			foreach ( $cards as $card ) {
				if ( $card->token === $token ) {
					return;
				}
			}
		}
		$wpdb->insert(
			$this->table_name,
			array(
				'user_id'    => intval( $user_id ),
				'token'      => $token,
				'masked_pan' => $pan,
			),
			array( '%d', '%s', '%s' )
		);

		return $wpdb->insert_id;
	}

	/**
	 * Get creation date of used card token
	 * 
	 * @param $user_id
	 * @param $token_id
	 * @return bool|DateTime
	 * @since 2.1.0
	 */
	public function get_card_creation_for_user( $user_id, $token_id ) {
		global $wpdb;
		
		$format = 'Y-m-d H:i:s';
		$logger = new Logger();
		$logger->error('before prepare statement');
		$creation_date = $wpdb->get_var( $wpdb->prepare( 
			"SELECT created FROM {$wpdb->prefix}wirecard_payment_gateway_vault WHERE user_id = %s AND token = %s", 
			$user_id, 
			$token_id 
		) );
		
		$date = DateTime::createFromFormat( $format, $creation_date );
		return $date;
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
		if ( false !== $cards ) {
			return $this->fetch_template_data( $cards );
		}

		return false;
	}

	/**
	 * Delete credit card from vault
	 *
	 * @param int $vault_id
	 * @return false|int
	 * @since 1.1.0
	 */
	public function delete_credit_card( $vault_id ) {
		global $wpdb;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wirecard_payment_gateway_vault WHERE vault_id = %d", $vault_id ) );
	}

	/**
	 * Get credit cards from vault
	 *
	 * @param int $user_id
	 * @return array|bool|null|object
	 * @since 1.1.0
	 */
	private function get_cards_from_db( $user_id ) {
		global $wpdb;

		$cards = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wirecard_payment_gateway_vault WHERE user_id = %s", $user_id ) );

		if ( empty( $cards ) ) {
			return false;
		}

		return $cards;
	}

	/**
	 * Return html for the ajax
	 *
	 * @param array $cards
	 * @return string
	 * @since 1.1.0
	 */
	private function fetch_template_data( $cards ) {
		$html = '<table id="vault-table">
		<tr>
			<th></th>
			<th>' . __( 'vault_account_number', 'wirecard-woocommerce-extension' ) . '</th>
			<th>' . __( 'vault_delete_card_text', 'wirecard-woocommerce-extension' ) . '</th>
		</tr>';
		foreach ( $cards as $card ) {
			$html .= '<tr>
				<td class="wd-card-selector"><input onclick="javascript:onTokenSelected(this)" class="token" name="token" type="radio" data-token="' . $card->token . '" /></td>
				<td class="wd-card-number">' . $card->masked_pan . '</td>
				<td class="wd-card-delete"><div class="delete-from-vault" onclick="javascript:deleteCreditCardFromVaultTab(this, ' . $card->vault_id . ')">' . __( 'text_delete', 'wirecard-woocommerce-extension' ) . '</div></td>
			</tr>';
		}
		$html .= '</table>';
		return $html;
	}
}
