<?php

// the path for different config files, each named as <paymentmethod>.json
define('GATEWAY_CONFIG_PATH', 'gateway_configs');

$gateway = getenv('GATEWAY');
if (!$gateway) {
	$gateway = 'API-TEST';
}

// the default config defines valid keys for each payment method and is prefilled with API-TEST setup by default
$default_config = [
	'creditcard' => [
		'base_url' => 'https://api-test.wirecard.com',
		'http_user' => '70000-APITEST-AP',
		'http_pass' => 'qD2wzQ_hrc!8',
		'threed_maid' => '508b8896-b37d-4614-845c-26bf8bf2c948',
		'threed_secret' => 'dbc5a498-9a66-43b9-bf1d-a618dd399684',
		'merchant_account_id' => '53f2895a-e4de-4e82-a813-0d87a10e55e6',
		'secret' => 'dbc5a498-9a66-43b9-bf1d-a618dd399684',
		'ssl_max_limit' => 100,
		'three_d_min_limit' => 50,

		'enabled' => 'yes',
		'title' => 'Wirecard Credit Card',
		'credentials' => '',
		'test_button' => 'Test',
		'advanced' => '',
		'payment_action' => 'pay',
		'descriptor' => 'no',
		'send_additional' => 'yes',
		'cc_vault_enabled' => 'no',
	],
];

// main script - read payment method from command line, build the config and write it into database
if (count($argv) < 2) {
	$supported_payment_methods = implode("\n  ", array_keys($GLOBALS['default_config']));
	echo <<<END_USAGE
Usage: php configure_payment_method_db.php <paymentmethod>

Supported payment methods:
  $supported_payment_methods


END_USAGE;
	exit(1);
}
$payment_method = trim($argv[1]);

$db_config = build_config_by_payment_method($payment_method, $gateway);
if (empty($db_config)) {
	echo "Payment method $payment_method is not supported\n";
	exit(1);
}
update_woocommerce_ee_db_config($db_config, $payment_method);

function build_config_by_payment_method($payment_method, $gateway) {
	if (!array_key_exists($payment_method, $GLOBALS['default_config'])) {
		return null;
	}
	$config = $GLOBALS['default_config'][$payment_method];

	$jsonFile = GATEWAY_CONFIG_PATH . DIRECTORY_SEPARATOR . $payment_method . ".json";
	if (file_exists($jsonFile)) {
		$jsonData = json_decode(file_get_contents($jsonFile));
		if (!empty($jsonData) && !empty($jsonData->$gateway)) {
			foreach (get_object_vars($jsonData->$gateway) as $key => $data) {
				// only replace values from json if the key is defined in defaultDbValues
				if (array_key_exists($key, $config)) {
					$config[$key] = $data;
				}
			}
		}
	}
	return $config;
}

function update_woocommerce_ee_db_config($db_config, $payment_method) {

	//DB setup
    $dbHost = 'mysql';
    $dbName = 'wordpress';
    $dbUser = 'root';
    $dbPass = getenv('WOOCOMMERCE_DB_PASSWORD');
    $dbPort = getenv('WOOCOMMERCE_DB_PORT');

    // table name
    $tableName = 'wp_options';
    $creditCardSettingKey = 'woocommerce_wirecard_ee_' . $payment_method . '_settings';

    $serializedConfig = serialize($db_config);

    // create connection
    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
    if ($mysqli->connect_errno) {
        echo "Can't connect DB $dbName on host $dbHost as user $dbUser \n";
        return false;
    }

    // remove existing config if any exists - or do nothing
    $stmtDelete = $mysqli->prepare("DELETE FROM $tableName WHERE option_name = ?");
    $stmtDelete->bind_param("s", $creditCardSettingKey);
    $stmtDelete->execute();

    // insert the new config
    $stmtInsert = $mysqli->prepare("INSERT INTO $tableName (option_name, option_value, autoload) VALUES (?, ?, 'yes')");
    $stmtInsert->bind_param("ss", $creditCardSettingKey, $serializedConfig);
    $stmtInsert->execute();

    return true;
}
