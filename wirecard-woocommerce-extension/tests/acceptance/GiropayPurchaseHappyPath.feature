Feature: GiropayPurchaseHappyPath
  As a guest
  I want to make a purchase with a Giropay
  And to see that transaction was successful
	
  Background:
	Given I prepare checkout 
	And I am on "Checkout" page
	And I fill fields with "Customer data"
	And I click "Wirecard Giropay"
	And I fill BIC
	  
  Scenario: purchase
	Given I click "Place order"
	And I am redirected to "Giropay Payment" page
	And I fill fields with "Giropay Data"
	And I click "Absenden"
	Then I am redirected to "Order Received" page
	And I see "Order received"
