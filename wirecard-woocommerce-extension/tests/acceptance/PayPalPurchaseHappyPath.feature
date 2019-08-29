Feature: PayPalPurchaseHappyPath
  As a guest user
  I want to make a purchase with a Pay Pal
  And to see that transaction was successful

  Background:
	Given I activate "pay pal" payment action "pay" in configuration
	And I prepare pay pal checkout
    And I am on "Checkout" page
    And I fill fields with "Customer data"
	And I click "Wirecard PayPal"
	
  @API-TEST
  Scenario: purchase
    Given I click "Place order"
    And I am redirected to "Pay Pal Log In" page
    And I login to Paypal
	When I am redirected to "Pay Pal Review" page
	And I click "Pay Now"
	Then I am redirected to "Order Received" page
    And I see "Order received"
	And I see "pay pal" "purchase" in transaction table
