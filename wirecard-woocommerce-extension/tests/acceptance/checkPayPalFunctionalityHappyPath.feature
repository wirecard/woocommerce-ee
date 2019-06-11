Feature: checkPayPalFunctionalityHappyPath
  As a guest user
  I want to make a purchase with a Pay Pal
  And to see that transaction was successful

  Background:
    Given I prepare checkout
    And I am on "Checkout" page
    And I fill fields with "Customer data"
	And I click "Wirecard PayPal"  
  
  Scenario: try purchaseCheck
    Given I click "Place order"
    And I am redirected to "Pay Pal Log In" page
	And I enter "paypal.buyer2@wirecard.com" in field "Email"
	And I enter "Wirecardbuyer" in field "Password"
	And I click "Log In"
	When I am redirected to "Pay Pal Review" page
	And I click "Pay Now"
	Then I am redirected to "Order Received" page
    And I see "Order received"
