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

require_once WIRECARD_EXTENSION_HELPER_DIR . 'class-credit-card-vault.php';

class WC_Gateway_Wirecard_Credit_Card_Vault_Utest extends \PHPUnit_Framework_TestCase {
	private $credit_card_vault;

	public function setUp() {
		$this->credit_card_vault = new Credit_Card_Vault();
	}

	public function test_save_card() {
		$this->assertNotNull( $this->credit_card_vault->save_card( 1, 12, '123*****123' ) );
	}

	public function test_get_cards_for_user() {
		$this->assertNotNull( $this->credit_card_vault->get_cards_for_user( 1 ) );
	}

	public function test_failed_get_cards_for_user() {
		$this->assertFalse( $this->credit_card_vault->get_cards_for_user( 2 ) );
	}

	public function test_delete_credit_card() {
		$this->assertEquals( 1, $this->credit_card_vault->delete_credit_card( 3 ) );
	}
}
