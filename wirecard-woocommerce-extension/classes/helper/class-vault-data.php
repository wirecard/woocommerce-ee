<?php

/**
 * Class Vault_Data
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @since 3.3.4
 */
class Vault_Data {

	/**
	 * @var int
	 */
	private $vault_id;
	/**
	 * @var int
	 */
	private $user_id;
	/**
	 * @var string
	 */
	private $token;
	/**
	 * @var string
	 */
	private $masked_pan;

	/**
	 * @var Address_Data
	 */
	private $address_data;

	/**
	 * Vault_Data constructor.
	 * @param int $user_id
	 * @param string $masked_pan
	 * @param string $token
	 * @param Address_Data|null $address_data
	 * @param null|int $vault_id
	 * @since 3.3.4
	 */
	public function __construct( $user_id, $masked_pan, $token, Address_Data $address_data = null, $vault_id = null ) {
		$this->user_id      = intval( $user_id );
		$this->masked_pan   = $masked_pan;
		$this->token        = $token;
		$this->address_data = $address_data;
		$this->vault_id     = $vault_id;
	}

	/**
	 * @return int
	 * @since 3.3.4
	 */
	public function get_vault_id() {
		return $this->vault_id;
	}

	/**
	 * @return int
	 * @since 3.3.4
	 */
	public function get_user_id() {
		return $this->user_id;
	}

	/**
	 * @return string
	 * @since 3.3.4
	 */
	public function get_masked_pan() {
		return $this->masked_pan;
	}

	/**
	 * @return string
	 * @since 3.3.4
	 */
	public function get_token() {
		return $this->token;
	}

	/**
	 * @return Address_Data
	 * @since 3.3.4
	 */
	public function get_address_data() {
		return $this->address_data;
	}

	/**
	 * @param stdClass $obj
	 * @return static
	 * @since 3.3.4
	 */
	public static function from_db( stdClass $obj ) {

		return new static(
			$obj->user_id,
			$obj->masked_pan,
			$obj->token,
			new Address_Data( $obj->address_1, $obj->city, $obj->postcode, $obj->country ),
			$obj->vault_id
		);
	}
	
	
}
