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

namespace Page;

use Facebook\WebDriver\Exception\NoSuchElementException;

class PayPalReview extends Base {

	// include url of current page
	/**
	 * @var string
	 * @since 1.4.4
	 */
	public $URL = 'checkout';

	/**
	 * @var string
	 * @since 3.1.0
	 */
	public $pageSpecific = 'checkout';

	/**
	 * @var array
	 * @since 1.4.4
	 */
	public $elements = array(
		'Pay Now' => "//*[@id='confirmButtonTop']",
		'Accept Cookies' => "//*[@id='acceptAllButton']",
		'Continue' => "//*[@class='btn full confirmButton continueButton']"
	);

	/**
	 * Method acceptCookies
	 *
	 * @since 3.1.0
	 */
	public function acceptCookies()
	{
		$I = $this->tester;

		try {
			$I->waitForElement($this->getElement('Accept Cookies'), 15);
			$I->waitForElementVisible($this->getElement('Accept Cookies'), 15);
			$I->waitForElementClickable($this->getElement('Accept Cookies'), 60);
			$I->click($this->getElement('Accept Cookies'));
		} catch (NoSuchElementException $e) {
			$I->seeInCurrentUrl($this->getPageSpecific());
		}
	}

	/**
	 * Method payNow
	 *
	 * @since 3.1.0
	 */
	public function payNow()
	{
		$I = $this->tester;

		$I->wait(1);
		try {
			$I->waitForElementVisible($this->getElement('Pay Now'), 60);
			$I->waitForElementClickable($this->getElement('Pay Now'), 60);
			$I->click($this->getElement('Pay Now'));
		} catch (NoSuchElementException $e) {
			$I->seeInCurrentUrl($this->getPageSpecific());
		}
	}
}
