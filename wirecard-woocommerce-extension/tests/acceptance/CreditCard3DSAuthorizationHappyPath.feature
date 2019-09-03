Feature: CreditCard3DSAuthorizationHappyPath
  As a guest  user
  I want to make am authorization with a Credit Card 3DS
  And to see that authorization was successful

  Background:
	Given I activate "creditcard" payment action "reserve" in configuration
	And I prepare credit card checkout "3DS"
    When I am on "Checkout" page
    And I fill fields with "Customer data"
    Then I see "Wirecard Credit Card"
	And I click "Place order"

  @API-TEST @API-WDCEE-TEST
  Scenario: authorize
    Given I fill fields with "Valid Credit Card Data"
    When I click "Pay now"
    And I am redirected to "Verified" page
    And I enter "wirecard" in field "Password"
    And I click "Continue"
    Then I am redirected to "Order Received" page
    And I see "Order received"
	And I see "creditcard" "authorization" in transaction table
