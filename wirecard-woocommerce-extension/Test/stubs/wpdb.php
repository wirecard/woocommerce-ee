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

class WPDB {
	public $prefix;

	public $insert_id;

	public $base_prefix;

	public function __construct() {
		$this->prefix      = 'prefix_';
		$this->base_prefix = 'base_prefix_';
	}

	public function insert( $table_name, $data, $type = null ) {
		$this->insert_id = 1;
		return;
	}

	/**
	 * @param $table_name
	 * @param $data
	 * @param null $type
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function replace( $table_name, $data, $type = null ) {
		$this->insert_id = 1;
		return;
	}

	public function prepare( $query, $id ) {
		if ( $id == 1 || $id == '123' ) {
			return true;
		} else {
			return false;
		}
	}

	public function get_results( $query ) {
		if ( $query ) {
			$card             = new stdClass();
			$card->token      = '123123123';
			$card->masked_pan = '123*****123';
			$card->vault_id   = '1';
			$card->user_id    = '123';
			$card->address_hash   = '1231qwerqwerqwerqwerqwerqwerqwer';

			return array(
				'1' => $card,
			);
		} else {
			return array();
		}
	}

	public function query( $query ) {
		return 1;
	}

	public function get_row( $id ) {
		$transaction = new stdClass();
		if ( $id ) {
			return $transaction;
		} else {
			return;
		}
	}

	public function update() {
		return;
	}
}
