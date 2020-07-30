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
 * @SuppressWarnings(PHPMD.StaticAccess)
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
	 * @param Vault_Data $card
	 * @return int
	 * @since 1.1.0
	 */
	public function save_card( Vault_Data $card ) {
		global $wpdb;

		$saved_card = $this->get_vault_by_token( $card->get_user_id(), $card->get_token() );
		if ( null !== $saved_card && $saved_card->get_address_hash() === $card->get_address_hash() ) {
			return 0;
		}

		$data = array(
			'user_id'      => $card->get_user_id(),
			'token'        => $card->get_token(),
			'masked_pan'   => $card->get_masked_pan(),
			'address_hash' => $card->get_address_hash(),
		);

		$data_format = array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' );

		if ( $saved_card ) {
			$data          = array_merge( $data, array( 'vault_id' => $saved_card->get_vault_id() ) );
			$data_format[] = '%d';
		}

		$wpdb->replace(
			$this->table_name,
			$data,
			$data_format
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
	 * @param Address_Data|null $address_data
	 * @return array|bool|null|object
	 * @since 1.1.0
	 */
	public function get_cards_for_user( $user_id, Address_Data $address_data ) {
		$cards = $this->get_cards_from_db( $user_id, $address_data->get_hash() );

		if ( $cards ) {
			return $this->fetch_template_data( $cards );
		}

		return false;
	}

	/**
	 * @param int $user_id
	 * @param Address_Data $address_data
	 * @return bool
	 * @since 3.4.4
	 */
	public function has_cards_for_user_address( $user_id, Address_Data $address_data ) {
		$cards = $this->get_cards_from_db( $user_id, $address_data->get_hash() );
		return count( $cards ) > 0;
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
	 * @param int $user_id
	 * @param int $token_id
	 * @return Vault_Data|null
	 * @since 3.4.4
	 */
	public function get_vault_by_token( $user_id, $token_id ) {
		global $wpdb;
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wirecard_payment_gateway_vault WHERE user_id = %s AND token = %s",
				(int) $user_id,
				$token_id
			)
		);

		if ( $result ) {
			return Vault_Data::from_db( $result );
		}

		return null;
	}

	/**
	 * Get credit cards from vault
	 *
	 * @param int $user_id
	 * @param string $address_hash
	 * @return array|Vault_Data[]
	 * @since 1.1.0
	 */
	private function get_cards_from_db( $user_id, $address_hash ) {
		global $wpdb;

		$cards     = array();
		$statement = $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wirecard_payment_gateway_vault WHERE user_id = %s AND address_hash = %s",
			(int) $user_id,
			$address_hash
		);
		$db_cards  = $wpdb->get_results( $statement );
		foreach ( $db_cards as $db_card ) {
			$cards[] = Vault_Data::from_db( $db_card );
		}

		return $cards;
	}

	/**
	 * Return html for the ajax
	 *
	 * @param array|Vault_Data[] $cards
	 * @return string
	 * @since 1.1.0
	 */
	private function fetch_template_data( $cards ) {
		$html = '<table id="vault-table">';
		foreach ( $cards as $card ) {
			$html .= '<tr>
				<td class="wd-card-selector"><input class="token" name="token" type="radio" data-token="' . $card->get_token() . '" /></td>
				<td class="wd-card-number">' . $card->get_masked_pan() . '</td>
				<td class="wd-card-delete" id="wd-token-' . $card->get_token() . '" data-vault-id="' . $card->get_vault_id() . '"><div class="delete-from-vault">' . __( 'text_delete', 'wirecard-woocommerce-extension' ) . '</div></td>
			</tr>';
		}
		$html .= '</table>';
		return $html;
	}
}
