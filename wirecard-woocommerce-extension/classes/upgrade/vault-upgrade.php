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
 * Update vault table with timestamps created and modified
 *
 * @since 2.1.0
 */
function vault_timestamp_upgrade() {
	global $wpdb;
	$vault_table_name = $wpdb->base_prefix . 'wirecard_payment_gateway_vault';
	
	if ( check_existing_column( 'created', $vault_table_name ) ) {
		$wpdb->query( "ALTER TABLE $vault_table_name ADD created DATETIME NOT NULL default CURRENT_TIMESTAMP" );
	}

	if ( check_existing_column( 'modified', $vault_table_name ) ) {
		$wpdb->query( "ALTER TABLE $vault_table_name ADD modified DATETIME NOT NULL default CURRENT_TIMESTAMP" );
	}
}

/**
 * Check if column already exist within given table
 * 
 * @param string $column_name
 * @param string $table_name
 * @return bool
 * 
 * @since 2.1.0
 */
function check_existing_column( $column_name, $table_name ) {
	global $wpdb;
	
	$prepared_statement = $wpdb->prepare( "SELECT %s FROM %s", $column_name, $table_name );
	$column_exists = $wpdb->get_col( $prepared_statement );
	
	return empty( $column_exists );
}
