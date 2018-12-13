Feature: guest_checkout
  In order to checkout
  As a guest
  I need to go to checkout page
  And to fill in customer data

  Scenario: try guest_checkout
    Given I am on "Shop" page
    When I click "Album"
    And I am redirected to "Product" page
    And I click "Add to cart"
    And I am on "Cart" page
    And I click "Proceed to checkout"
    And I am redirected to "Checkout" page
    And I fill fields with "Customer data"
    Then I see "Wirecard Credit Card"
