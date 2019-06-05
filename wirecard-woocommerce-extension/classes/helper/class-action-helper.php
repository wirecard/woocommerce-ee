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

/**
 * Class Action_Helper
 *
 * @since 2.0.0
 */
class Action_Helper {

	/**
	 * Performs an add_action only once. Helpful for constructors where an action only
	 * needs to be added once.
	 *
	 * @param string   $tag             The name of the action to hook the $function_to_add callback to.
	 * @param callback $function_to_add The callback to be run when the filter is applied.
	 * @param int      $priority        Optional. Used to specify the order in which the functions
	 *                                  associated with a particular action are executed. Default 10.
	 *                                  Lower numbers correspond with earlier execution,
	 *                                  and functions with the same priority are executed
	 *                                  in the order in which they were added to the action.
	 * @param int      $accepted_args   Optional. The number of arguments the function accepts. Default 1.
	 *
	 * @return true
	 *
	 * @since 2.0.0
	 */
	function add_action_once( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		global $_gateway_actions_extended;

		if ( ! isset( $_gateway_actions_extended ) ) {
			$_gateway_actions_extended = array();
		}

		$idx_func = $function_to_add;
		if ( is_array( $function_to_add ) && ! empty( $function_to_add ) ) {
			$idx_func[0] = get_class( $function_to_add[0] );
		}
		$idx = _wp_filter_build_unique_id( $tag, $idx_func, $priority );

		if ( ! in_array( $idx, $_gateway_actions_extended, true ) ) {
			add_action( $tag, $function_to_add, $priority, $accepted_args );
		}

		$_gateway_actions_extended[] = $idx;

		return true;
	}
}
