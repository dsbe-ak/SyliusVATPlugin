@api_address_book_vat_number
Feature: Managing an address VAT number through the API
    In order to keep correct company billing data
    As a Customer
    I want to manage the VAT number in my address book through the API

    Background:
        # "ES12345678Z" and "ES87654321X" are registered with the fake VIES service
        Given the store operates on a single channel
        And the store operates in "Spain"
        And I am a logged in customer with name "Carmen García"

    @api
    Scenario: Adding, reading, updating and removing an address VAT number
        When I want to add a new address to my address book
        And I specify the address as "Carmen García", "Calle Gran Vía de Colón 1", "18001", "Granada", "Spain", "Granada"
        And I specify the VAT number as "ES12345678Z"
        And I add it
        Then I should be notified that the address has been successfully added
        And the returned address should contain the VAT number "ES12345678Z"
        And the returned address VAT number should be marked as valid
        And the address of "Carmen García" should contain the valid VAT number "ES12345678Z"
        When I am editing the address of "Carmen García"
        And I change the VAT number to "ES87654321X"
        And I save my changed address
        Then I should be notified that the address has been successfully updated
        And the returned address should contain the VAT number "ES87654321X"
        And the returned address VAT number should be marked as valid
        And the address of "Carmen García" should contain the valid VAT number "ES87654321X"
        When I am editing the address of "Carmen García"
        And I remove the VAT number
        And I save my changed address
        Then I should be notified that the address has been successfully updated
        And the returned address should not contain a VAT number
        And the returned address VAT validation should be reset
        And the address of "Carmen García" should not contain a VAT number and its validation should be reset
