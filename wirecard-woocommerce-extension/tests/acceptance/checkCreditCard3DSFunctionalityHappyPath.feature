Feature: checkCreditCard3DSFunctionalityHappyPath
  As a guest  user
  I want to make a purchase with a Credit Card 3DS
  And to see that transaction was successful

  Background:
    Given I prepare checkout
    And I am on "Checkout" page
    And I fill fields with "Customer data"
    Then I see "Wirecard Credit Card"
#	And I click "Place order"
  
  Scenario: try purchaseCheck
    Given I fill fields with "Valid Credit Card Data"
    When I click "Pay now"
    And I am redirected to "Verified" page
    And I enter "wirecard" in field "Password"
    And I click "Continue"
    Then I am redirected to "Order Received" page
    And I see "Order received"
