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

require_once( WIRECARD_EXTENSION_HELPER_DIR . 'class-upgrade-helper.php' );
require_once( WIRECARD_EXTENSION_HELPER_DIR . 'class-logger.php' );

// Add required upgrades here
require_once 'wpp-v-two-upgrade.php';
require_once 'vault-upgrade.php';

/**
 * wirecard_extension_upgrade_completed
 * Checks if plugin was updated
 * or a new installation happened
 *
 * @param array $upgrader_object
 * @param array $options
 * @since 2.0.0
 */
function wirecard_extension_upgrade_completed( $upgrader_object, $options ) {
	// If an update has taken place and the updated type is plugins and the plugins element exists
	if ( 'update' === $options['action'] && 'plugin' === $options['type'] && isset( $options['plugins'] ) ) {
		// Iterate through the plugins being updated and check if ours is there
		foreach ( $options['plugins'] as $plugin ) {
			if ( WIRECARD_EXTENSION_MAIN_FILE === $plugin ) {
				// Call upgrade hook
				wirecard_extension_upgrade_hook();
			}
		}
	}
}

/**
 * wirecard_extension_upgrade_hook
 * Only called if this plugin was updated
 *
 * @since 2.1.0 vault_timestamp_upgrade()
 * @since 2.0.0
 */
function wirecard_extension_upgrade_hook() {
	$upgrade_helper = new Upgrade_Helper();

	// Since there was no general information stored
	// before the 2.0 release everything that has to be
	// added after an update from a version pre 2.0
	// must be called here
	if ( ! $upgrade_helper->general_information_conditions_met() ) {
		// Add wpp_url depending on the configured base_url
		// for credit card
		wpp_v_two_upgrade();
	}

	// Add address_hash field to vault table, if not already existent
	vault_address_fields_upgrade();
	// Add timestamps to vault table, if not already existent
	vault_timestamp_upgrade();

	// Create tables in sub sites, because before this pull request https://github.com/wirecard/woocommerce-ee/pull/311
	// the tables were created only in main blog. And we could not correctly match transactions with sites.
	wirecard_install_payment_gateway();

	// If other things should happen on upgrade
	// add the method calls here

	// Update extension version on every upgrade/update
	$upgrade_helper->update_extension_version();
}
