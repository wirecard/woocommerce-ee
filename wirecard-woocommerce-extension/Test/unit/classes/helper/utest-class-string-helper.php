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

require_once WIRECARD_EXTENSION_HELPER_DIR . 'class-string-helper.php';

/**
 * Class WC_Gateway_Wirecard_String_Helper_Utest
 * @coversDefaultClass String_Helper
 */
class WC_Gateway_Wirecard_String_Helper_Utest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return array
	 */
	public function sanitize_wc_data_provider() {
		$dataAssertionStringList = [];
		$dataAssertionStringList["test_empty_string"] 			   = ["", ""];
		$dataAssertionStringList["test_normal_string"] 			   = ["test", "test"];
		$dataAssertionStringList["test_normal_string_with_break"]  = ["test ", "test"];
		$dataAssertionStringList["test_normal_string_with_break1"] = ["test\r\n\t", "test"];
		$dataAssertionStringList["test_string_with_decoded_tag"]   = ["&lt;script&gt;alert('test');&lt;/script&gt;", ""];
		$dataAssertionStringList["test_string_with_tags"] 		   = ["<script>something</script>", ""];
		$dataAssertionStringList["test_string_with_tags1"] 		   = ["something", "something"];
		$dataAssertionStringList["test_string_with_style"] 		   = ["<style>something</style>", ""];
		$dataAssertionStringList["test_number_as_string"] 		   = ["123", "123"];
		return $dataAssertionStringList;
	}
	
	/**
	 * Test sanitize_wc function
	 * 
	 * @group unit
	 * @small 
	 * @covers ::sanitize_wc
	 * @dataProvider sanitize_wc_data_provider
	 * 
	 * @param string $value
	 * @param string $expected
	 * 
	 * @since 3.1.0
	 */
	public function test_sanitize_wc( $value, $expected ) {
		$this->assertEquals( $expected, String_Helper::sanitize_wc( $value ) );
	}
}
