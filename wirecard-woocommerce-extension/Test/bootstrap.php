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

define( 'ABSPATH', true );
define( 'WIRECARD_EXTENSION_BASEDIR', __DIR__ . '/../' );
define( 'WIRECARD_EXTENSION_VERSION', '1.0.0' );
define( 'WIRECARD_EXTENSION_URL', __DIR__ . '/../' );

require_once __DIR__ . '/../vendor/autoload.php';

//stub objects
require_once __DIR__ . '/stubs/wc-payment-gateway.php';
require_once __DIR__ . '/stubs/functions.php';
require_once __DIR__ . '/stubs/wc-settings-api.php';
require_once __DIR__ . '/stubs/wc-order.php';
require_once __DIR__ . '/stubs/wc-log-levels.php';
require_once __DIR__ . '/stubs/wp-error.php';
require_once __DIR__ . '/stubs/wc-logger.php';
require_once __DIR__ . '/stubs/wc-countries.php';
require_once __DIR__ . '/stubs/wc-cart.php';
require_once __DIR__ . '/stubs/wc-product.php';
require_once __DIR__ . '/stubs/wc.php';
require_once __DIR__ . '/stubs/wc-session.php';
require_once __DIR__ . '/stubs/wc-customer.php';
require_once __DIR__ . '/stubs/wc-tax.php';

//payment methods
require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-paypal.php' );
require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-sepa-credit-transfer.php' );
require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-sepa-direct-debit.php' );
require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-creditcard.php' );
require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-ideal.php' );
require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-eps.php' );
require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-sofort.php' );
require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-poipia.php' );
require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-guaranteed-invoice-ratepay.php' );
require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-alipay-crossborder.php' );
require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-unionpay-international.php' );
require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-masterpass.php' );
require_once( WIRECARD_EXTENSION_BASEDIR . 'classes/includes/class-wc-gateway-wirecard-pay-by-bank-app.php' );
