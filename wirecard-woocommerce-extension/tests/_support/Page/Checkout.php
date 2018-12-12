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

class Checkout extends Base
{
    // include url of current page
    public $URL = '/checkout';

    public $elements = array(
        'First Name' => "//*[@id='billing_first_name']",
        'Last Name' => "//*[@id='billing_last_name']",
        'Country' => "//*[@id='select2-billing_country-container']",
        'Country entry' => "//*[@class='select2-search__field']",
        'Street address' => "//*[@id='billing_address_1']",
        'Town/City' => "//*[@id='billing_city']",
        'Postcode' => "//*[@id='billing_postcode']",
        'Phone' => "//*[@id='billing_phone']",
        'Email address' => "//*[@id='billing_email']",
        'Place order' => "//*[@id='place_order']",
    );

    /**
     * Method fillBillingDetails
     */
    public function fillBillingDetails()
    {
        $I = $this->tester;
        $data_field_values = $I->getCustomerDataFromDataFile();
        $I->fillField($this->getElement("First Name"), $data_field_values->first_name);
        $I->fillField($this->getElement("Last Name"), $data_field_values->first_name);
        $I->click($this->getElement("Country"));
        $I->fillField($this->getElement("Country entry"), $data_field_values->country);
        $I->click($this->getElement("Street address"));
        $I->fillField($this->getElement("Street address"), $data_field_values->street_address);
        $I->fillField($this->getElement("Town/City"), $data_field_values->town);
        $I->fillField($this->getElement("Postcode"), $data_field_values->post_code);
        $I->fillField($this->getElement("Phone"), $data_field_values->phone);
        $I->fillField($this->getElement("Email address"), $data_field_values->email_address);

    }

    /**
     * Method fillCreditCardDetails
     */
    public function fillCreditCardDetails()
    {
        ;
    }
}