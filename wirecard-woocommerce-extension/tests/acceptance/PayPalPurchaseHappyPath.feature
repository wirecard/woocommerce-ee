Feature: PayPalPurchaseHappyPath
  As a guest user
  I want to make a purchase with a Pay Pal
  And to see that transaction was successful

  Background:
	Given I activate "paypal" payment action "pay" in configuration
	And I prepare checkout
    When I am on "Checkout" page
    Then I fill fields with "Customer data"
	And I click "Wirecard PayPal"

  @patch @minor @major
  Scenario: purchase
    Given I click "Place order"
    And I am redirected to "Pay Pal Log In" page
    And I login to Paypal
	When I am redirected to "Pay Pal Review" page
	And I click "Continue"
	And I click "Pay Now"
	Then I am redirected to "Order Received" page
    And I see "Order received"
	And I see "paypal" "purchase" in transaction table
