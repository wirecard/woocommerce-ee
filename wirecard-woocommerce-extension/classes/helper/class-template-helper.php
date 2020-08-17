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
 * Class Template_Helper
 * Helper for templates
 *
 * @since 2.0.0
 */
class Template_Helper {

	/**
	 * Get template as string
	 *
	 * @param string $file_name
	 * @param array $arguments
	 * @return string
	 * @since 2.0.0
	 * @since 3.3.5 added param [arguments] - to load templates with arguments
	 */
	public function get_template_as_string( $file_name, $arguments = array() ) {
		return wc_get_template_html( $file_name, $arguments, '', WIRECARD_EXTENSION_TEMPLATE_DIR );
	}
}
