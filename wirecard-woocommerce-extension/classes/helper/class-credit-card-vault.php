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
	 * @const string CREATED
	 */
	const CREATED = 'created';

	/**
	 * @const string VAULT_ID
	 */
	const VAULT_ID = 'vault_id';

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
	 * @param int $user_id
	 * @param string $token_id
	 * @return bool|DateTime
	 * @since 2.1.0
	 */
	public function get_card_creation_for_user( $user_id, $token_id ) {
		$format = 'Y-m-d H:i:s';

		$date          = new DateTime();
		$creation_date = $this->get_column_for_token_and_user( self::CREATED, $user_id, $token_id );
		if ( ! is_null( $creation_date ) ) {
			$date = DateTime::createFromFormat( $format, $creation_date );
		}

		return $date;
	}

	/**
	 * Check if token_id for user already exists
	 *
	 * @param int $user_id
	 * @param string $token_id
	 * @return bool
	 * @since 2.1.0
	 */
	public function is_existing_token_for_user( $user_id, $token_id ) {
		$is_existing  = true;
		$token_exists = $this->get_column_for_token_and_user( self::VAULT_ID, $user_id, $token_id );

		if ( is_null( $token_exists ) ) {
			$is_existing = false;
		}

		return $is_existing;
	}

	/**
	 * Get entry for specific column by token and user_id
	 *
	 * @param string $column
	 * @param int $user_id
	 * @param string $token_id
	 * @return null|string
	 * @since 2.1.0
	 */
	private function get_column_for_token_and_user( $column, $user_id, $token_id ) {
		global $wpdb;

		$token_var = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT {$column} FROM {$wpdb->prefix}wirecard_payment_gateway_vault WHERE user_id = %s AND token = %s",
				$user_id,
				$token_id
			)
		);

		return $token_var;
	}

	/**
	 * Get all credit cards for a user
	 *
	 * @param int $user_id
	 * @return array|bool|null|object
	 * @since 1.1.0
	 */
	public function get_cards_for_user( $user_id ) {
		$order_id            = WC()->session->get( 'wirecard_order_id' );
		$order               = wc_get_order( $order_id );
		echo "<pre>";
		print_r($order);
		print_r(Address_Data::fromWoocommerceOrder($order));
		echo "</pre>";
		die;
	
		//$address = new Address( $order->get_shipping_country(), $order->get_shipping_city(), $order->get_shipping_address_1() );
		//$address->setPostalCode( $order->get_shipping_postcode() );
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
		$html = '<table id="vault-table">';
		foreach ( $cards as $card ) {
			$html .= '<tr>
				<td class="wd-card-selector"><input class="token" name="token" type="radio" data-token="' . $card->token . '" /></td>
				<td class="wd-card-number">' . $card->masked_pan . '</td>
				<td class="wd-card-delete" id="wd-token-' . $card->token . '" data-vault-id="' . $card->vault_id . '"><div class="delete-from-vault">' . __( 'text_delete', 'wirecard-woocommerce-extension' ) . '</div></td>
			</tr>';
		}
		$html .= '</table>';
		return $html;
	}
}
