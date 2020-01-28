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

require_once( WIRECARD_EXTENSION_HELPER_DIR . 'class-logger.php' );

/**
 * Class Upgrade_Helper
 * Helper for plugin upgrades
 *
 * @since 2.0.0
 */
class Upgrade_Helper {

	/** @var string GENERAL_INFORMATION_COLUMN */
	const GENERAL_INFORMATION_COLUMN = 'general_information';
	/** @var string PREVIOUS_VERSION_KEY */
	const PREVIOUS_VERSION_KEY = 'previous_version';
	/** @var string CURRENT_VERSION_KEY */
	const CURRENT_VERSION_KEY = 'current_version';
	/** @var WPDB $wpdb */
	public $wpdb;
	/** @var string general_information_table */
	public $general_information_table;
	/** @var Logger $logger */
	public $logger;
	/** @var string $general_information_table_id */
	public $general_information_table_id;
	/** @var $collation */
	public $collation;

	/**
	 * Upgrade_Helper constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		global $wpdb;

		$this->logger                       = new Logger();
		$this->wpdb                         = $wpdb;
		$this->general_information_table    = $this->wpdb->prefix . 'wirecard_payment_general_information';
		$this->general_information_table_id = $this->general_information_table . '_id';
		if ( $this->wpdb->has_cap( 'collation' ) ) {
			$this->collation = $this->wpdb->get_charset_collate();
		}
	}

	/**
	 * Update the extension version stored in the database
	 *
	 * @since 2.0.0
	 */
	public function update_extension_version() {
		$general_information        = null;
		$previous_extension_version = null;

		$this->general_information_init();

		$general_information = $this->get_extension_general_information();

		if ( ! array_key_exists( self::CURRENT_VERSION_KEY, $general_information ) ) {
			$general_information[ self::CURRENT_VERSION_KEY ] = WIRECARD_EXTENSION_VERSION;
		}

		$general_information = array(
			self::PREVIOUS_VERSION_KEY => $general_information[ self::CURRENT_VERSION_KEY ],
			self::CURRENT_VERSION_KEY  => WIRECARD_EXTENSION_VERSION,
		);

		$this->set_extension_general_information( $general_information );
	}

	/**
	 * Get the current extension version
	 *
	 * @return array|mixed|null|object
	 *
	 * @since 2.0.0
	 */
	public function get_current_extension_version() {
		$current_version = $this->get_extension_general_information( self::CURRENT_VERSION_KEY );

		return $current_version;
	}

	/**
	 * Get the previous extension version
	 *
	 * @return array|mixed|null|object
	 *
	 * @since 2.0.0
	 */
	public function get_previous_extension_version() {
		$previous_version = $this->get_extension_general_information( self::PREVIOUS_VERSION_KEY );

		return $previous_version;
	}

	/**
	 * Get extension general information
	 * and return as array
	 * or string if $type was set
	 * or null if an error occurs
	 *
	 * @param null $type
	 * @return array|mixed|object
	 *
	 * @since 2.0.0
	 */
	protected function get_extension_general_information( $type = null ) {
		$general_information = array();
		$column              = self::GENERAL_INFORMATION_COLUMN;
		// Get latest entry in the extensions general information table
		$general_information_query = $this->wpdb->prepare(
			"SELECT `$column` FROM `$this->general_information_table` ORDER BY `$this->general_information_table_id` DESC LIMIT 1",
			array()
		);

		// If table and column do not exist return null
		if ( ! $this->general_information_conditions_met() ) {
			return $general_information;
		}

		// Returns result as string or null
		$general_information_result = $this->wpdb->get_var( $general_information_query );

		// If entry doesn't exist return null
		if ( is_null( $general_information_result ) ) {
			return $general_information;
		}

		// Json decode as array
		$general_information = json_decode( $general_information_result, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->logger->error(
				__METHOD__ . ':' . 'general_information could not be decoded:'
				. json_last_error_msg()
			);
			return array();
		}

		// If no type is set return whole array
		if ( is_null( $type ) ) {
			return $general_information;
		}

		// If type is set return specific value
		return $general_information[ $type ];
	}

	/**
	 * Adds a new entry to the general_information table
	 * Equals to updating since on get always the latest entry is returned
	 * To update only one key, use get_extension_general_information
	 * update the key and call this method
	 *
	 * @param array $general_information
	 *
	 * @since 2.0.0
	 */
	protected function set_extension_general_information( $general_information ) {
		$encoded_general_information = json_encode( $general_information );
		$result                      = $this->wpdb->insert(
			$this->general_information_table,
			array(
				self::GENERAL_INFORMATION_COLUMN => $encoded_general_information,
			)
		);

		if ( false === $result ) {
			$this->logger->error( 'Wirecard extension general information could not be set!' );
			wp_die();
		}
	}

	/**
	 * Check if table and column exist
	 *
	 * @return bool
	 *
	 * @since 2.0.0
	 */
	public function general_information_conditions_met() {
		if ( ! $this->general_information_table_exists() ) {
			return false;
		}

		if ( ! $this->general_information_column_exists() ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if table $this->general_information_table exists
	 *
	 * @return bool
	 *
	 * @since 2.0.0
	 */
	protected function general_information_table_exists() {
		$table_query = $this->wpdb->prepare(
			'SHOW TABLES LIKE %s',
			array(
				$this->general_information_table,
			)
		);

		// If table does not exist return false
		if ( $this->wpdb->query( $table_query ) === 0 ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if column general_information exists
	 *
	 * @return bool
	 *
	 * @since 2.0.0
	 */
	protected function general_information_column_exists() {
		$column_query = $this->wpdb->prepare(
			"SHOW COLUMNS FROM `$this->general_information_table` LIKE %s",
			array(
				self::GENERAL_INFORMATION_COLUMN,
			)
		);

		$column_query_result = $this->wpdb->query( $column_query );

		if ( empty( $column_query_result ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Create table and column necessary for general_information to be stored
	 *
	 * @since 2.0.0
	 */
	protected function general_information_init() {
		$this->create_general_information_table();
		$this->create_general_information_column();
	}

	/**
	 * Create general_information_table
	 *
	 * @since 2.0.0
	 */
	protected function create_general_information_table() {
		$create_table_query = $this->wpdb->prepare(
			"CREATE TABLE `$this->general_information_table` (`$this->general_information_table_id` int UNSIGNED AUTO_INCREMENT PRIMARY KEY)$this->collation",
			array()
		);

		if ( $this->general_information_table_exists() ) {
			return;
		}

		$this->wpdb->query( $create_table_query );
	}

	/**
	 * Create general_information_column in table
	 * if it doesn't exist
	 *
	 * @since 2.0.0
	 */
	protected function create_general_information_column() {
		$column              = self::GENERAL_INFORMATION_COLUMN;
		$create_column_query = $this->wpdb->prepare(
			"ALTER TABLE `$this->general_information_table` ADD `$column` VARCHAR(255) NOT NULL AFTER `$this->general_information_table_id`",
			array()
		);

		if ( $this->general_information_column_exists() ) {
			return;
		}

		$this->wpdb->query( $create_column_query );
	}
}
