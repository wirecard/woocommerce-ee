Feature: CreditCard3DSPurchaseHappyPath
  As a guest  user
  I want to make a purchase with a Credit Card 3DS
  And to see that transaction was successful

  Background:
	Given I activate "creditcard" payment action "pay" in configuration
	And I prepare credit card checkout "3DS"
    When I am on "Checkout" page
    And I fill fields with "Customer data"
    Then I see "Wirecard Credit Card"
	And I click "Place order"

  @patch @minor @major
  Scenario: purchase
    Given I fill fields with "Valid Credit Card Data"
    When I click "Pay now"
    And I am redirected to "Verified" page
    And I enter "wirecard" in field "Password"
    And I click "Continue"
    Then I am redirected to "Order Received" page
    And I see "Order received"
	And I see "creditcard" "purchase" in transaction table
