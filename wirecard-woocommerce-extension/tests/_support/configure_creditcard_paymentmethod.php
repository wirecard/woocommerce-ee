<?php
$gateway = getenv('GATEWAY');

$gatewayConfig = function ($key) use ($gateway) {
    // if no gateway was defined in the environment, use the api-test.wirecard.com
    if (!$gateway) {
        $gateway = 'API-TEST';
    }
    $dataArray = [
        'NOVA' => [
            'base_url' => 'https://payments-test.wirecard.com',
            'http_user' => 'NovaTeam',
            'http_pass' => 'kCopTTMkpw',
            'threed_maid' => 'fd83dbfa-8790-4492-8391-3f3938908b28',
            'threed_secret' => '38424ae8-2dc5-45be-af4c-6e0fee0fea3e',
            'non_threed_maid' => 'fd83dbfa-8790-4492-8391-3f3938908b28',
            'non_threed_secret' => '38424ae8-2dc5-45be-af4c-6e0fee0fea3e',
        ],
        'API-WDCEE-TEST' => [
            'base_url' => 'https://api-wdcee-test.wirecard.com',
            'http_user' => 'pink-test',
            'http_pass' => '8f5y2h0s',
            'threed_maid' => '49ee1355-cdd3-4205-920f-85391bb3865d',
            'threed_secret' => '518c3be1-4aa2-4294-a081-eb7edf20f9d7',
            'non_threed_maid' => '589651ab-bffe-4f45-9a41-c5466aa8cbc8',
            'non_threed_secret' => 'cf8be86b-a671-4da4-b870-80af5c3eedb1'
        ],
        'API-TEST' => [
            'base_url' => 'https://api-test.wirecard.com',
            'http_user' => '70000-APITEST-AP',
            'http_pass' => 'qD2wzQ_hrc!8',
            'threed_maid' => '508b8896-b37d-4614-845c-26bf8bf2c948',
            'threed_secret' => 'dbc5a498-9a66-43b9-bf1d-a618dd399684',
            'non_threed_maid' => '53f2895a-e4de-4e82-a813-0d87a10e55e6',
            'non_threed_secret' => 'dbc5a498-9a66-43b9-bf1d-a618dd399684'
        ],
        'SECURE-TEST-SG' => [
            'base_url' => 'https://secure-test.wirecard.com.sg',
            'http_user' => 'uatwd_ecom',
            'http_pass' => 'Tomcat123',
            'threed_maid' => 'd7855010-64c1-4e66-9ab3-d98b309a3d8c',
            'threed_secret' => '543d957b-dcc9-46cd-8258-0f49ed97fa8e',
            'non_threed_maid' => 'd7855010-64c1-4e66-9ab3-d98b309a3d8c',
            'non_threed_secret' => '543d957b-dcc9-46cd-8258-0f49ed97fa8e'
        ],
        'TEST-SG' => [
            'base_url' => 'https://test.wirecard.com.sg',
            'http_user' => 'wirecarduser3d',
            'http_pass' => 'Tomcat123',
            'threed_maid' => '961c567b-d9da-41f6-9801-ba21cb228a00',
            'threed_secret' => '03365d5f-1a12-4f16-9351-7ee59ddc9d3f',
            'non_threed_maid' => '961c567b-d9da-41f6-9801-ba21cb228a00',
            'non_threed_secret' => '03365d5f-1a12-4f16-9351-7ee59ddc9d3f'
        ]
    ];

    return $dataArray[$gateway][$key];
};

// test setup
$maid = $gatewayConfig('non_threed_maid');
$secret = $gatewayConfig('non_threed_secret');
$threeDMaid = $gatewayConfig('threed_maid');
$threeDSecret = $gatewayConfig('threed_secret');
$baseUrl = $gatewayConfig('base_url');
$httpUser = $gatewayConfig('http_user');
$httpPass = $gatewayConfig('http_pass');
$sslMaxLimit = 100.0;
$threeDMinLimit = 50.0;

updateCreditCartConfig($maid, $secret, $threeDMaid, $threeDSecret, $sslMaxLimit, $threeDMinLimit, $baseUrl, $httpUser,
    $httpPass);

function updateCreditCartConfig(
    $maid,
    $secret,
    $threeDMaid,
    $threeDSecret,
    $sslMaxLimit,
    $threeDMinLimit,
    $baseUrl,
    $httpUser,
    $httpPass
) {
    //DB setup
    $dbHost = 'mysql';
    $dbName = 'wordpress';
    $dbUser = 'root';
    $dbPass = getenv('WOOCOMMERCE_DB_PASSWORD');
    $dbPort = getenv('WOOCOMMERCE_DB_PORT');

    // table name
    $tableName = 'wp_options';
    $creditCardSettingKey = 'woocommerce_wirecard_ee_creditcard_settings';

    $config = [
        'enabled' => 'yes',
        'title' => 'Wirecard Credit Card',
        'merchant_account_id' => $maid,
        'secret' => $secret,
        'three_d_merchant_account_id' => $threeDMaid,
        'three_d_secret' => $threeDSecret,
        'ssl_max_limit' => (string)$sslMaxLimit,
        'three_d_min_limit' => (string)$threeDMinLimit,
        'credentials' => '',
        'base_url' => $baseUrl,
        'http_user' => $httpUser,
        'http_pass' => $httpPass,
        'test_button' => 'Test',
        'advanced' => '',
        'payment_action' => 'pay',
        'descriptor' => 'no',
        'send_additional' => 'yes',
        'cc_vault_enabled' => 'no',
    ];
    $serializedConfig = serialize($config);

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