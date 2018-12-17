Feature: check_credit_card_3DS_functionality_happy_path
  As a guest  user
  I want to make a purchase with a Credit Card 3DS
  And to see that transaction was successful

  Background:
    Given I am on "Shop" page
    When I click "First Product in the Product List"
    And I am redirected to "Product" page
    And I click "Add to cart"
    And I am on "Cart" page
    And I enter "5" in field "Quantity"
    And I click "Update Cart"
    And I click "Proceed to checkout"
    And I am redirected to "Checkout" page
    And I fill fields with "Customer data"
    Then I see "Wirecard Credit Card"

  Scenario: try purchase_check
    Given I fill fields with "Valid Credit Card Data"
    When I click "Place order"
    And I am redirected to "Verified" page
    And I enter "wirecard" in field "Password"
    And I click "Continue"
    Then I am redirected to "Order Received" page
    And I see "Order received"