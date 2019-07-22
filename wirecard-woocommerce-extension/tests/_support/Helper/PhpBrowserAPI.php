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

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I
//

use Codeception\Module\PhpBrowser;

class PhpBrowserAPI extends \Codeception\Module {


	/**
	 * @var PhpBrowser
	 * @since 1.4.4
	 */
	private $phpBrowser;

	public function _initialize() {
		 // we initialize PhpBrowser here
		$this->phpBrowser = new PhpBrowser(
			$this->moduleContainer,
			[
				'url'  => $this->config['url'],
				'auth' => [ $this->config['user'], $this->config['password'] ],
			]
		);
		$this->phpBrowser->_initialize();
	}

	/**
	 * Method prepareCheckout
	 *
	 * @param PageObject $productPage
	 *
	 * @since   1.4.4
	 */
	public function prepareCheckout( $productPage ) {
		//go to product page
		$this->phpBrowser->amOnPage( $productPage->getURL() );
		//choose a product to the cart 5 times
		for ( $i = 0; $i <= 4; $i++ ) {
			$this->phpBrowser->click( $productPage->getElement( 'Add to cart' ) );
		}
	}

	/**
	 * Method syncCookies
	 * @since   1.4.4
	 */
	public function syncCookies() {
		 // open page in PhpBrowser
		$this->phpBrowser->amOnPage( '/' );
		$webdriver = $this->getModule( 'WebDriver' );
		// open page in WebDriver
		$webdriver->amOnPage( '/' );
		$cookieJar = $this->phpBrowser->client->getCookieJar();
		foreach ( $cookieJar->all() as $cookie ) {
			// copy cookies from PhpBrowser to WebDriver
			$webdriver->setCookie( $cookie->getName(), $cookie->getValue() );
		}
	}
}
