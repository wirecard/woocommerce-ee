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

require_once WIRECARD_EXTENSION_HELPER_DIR . 'class-number-formatter.php';

/**
 * Class WC_Gateway_Wirecard_Number_Formatter_Utest
 * @coversDefaultClass Number_Formatter
 * 
 * @since 3.1.0
 */
class WC_Gateway_Wirecard_Number_Formatter_Utest extends \PHPUnit_Framework_TestCase {
	
	/** 
	 * @var Number_Formatter
	 */
	protected $number_formatter;
	
	protected function setUp()
	{
		$this->number_formatter = new Number_Formatter();
	}

	/**
	 * @return array
	 * 
	 * @since 3.1.0
	 */
	public function format_wc_data_provider() {
		$dataAssertionNumberList = [];
		$dataAssertionNumberList["test_format_to_two_decimal"]       = [1234.5678, 2, 1234.57];
		$dataAssertionNumberList["test_format_to_one_decimal"]       = [1234.5699, 1, 1234.6];
		$dataAssertionNumberList["test_format_and_convert_to_float"] = ['1234.56', 2, 1234.56];
		$dataAssertionNumberList["test_format_to_three_decimal"]     = [1234.56131313, 3, 1234.561];
		$dataAssertionNumberList["test_format_null"]				 = [null, 2, 0.0];
		$dataAssertionNumberList["test_format_bool"]				 = [false, 2, 0.0];
		return $dataAssertionNumberList;
	}

	/**
	 * Test sanitize_wc function
	 *
	 * @group unit
	 * @small 
	 * @dataProvider format_wc_data_provider
	 * @covers ::format_wc
	 * 
	 * @param string $value
	 * @param int $decimal_point_number
	 * @param string $expected
	 * 
	 * @since 3.1.0
	 */
	public function test_format_wc( $value, $decimal_point_number, $expected ) {
		$this->number_formatter->set_decimal_point_number( $decimal_point_number );
		$this->assertEquals( $expected, $this->number_formatter->format_wc( $value ) );
	}
}
 
