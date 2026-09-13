<?php

declare(strict_types=1);

namespace Tests\Gewebe\SyliusVATPlugin\Behat\Context\Api\Shop;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Sylius\Behat\Client\ApiClientInterface;
use Sylius\Behat\Client\ResponseCheckerInterface;
use Sylius\Behat\Context\Api\Resources;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Webmozart\Assert\Assert;

final class CheckoutVatContext implements Context
{
    /** @var array{billingAddress?: array<string, string|null>} */
    private array $content = [];

    public function __construct(
        private readonly ApiClientInterface $client,
        private readonly SharedStorageInterface $sharedStorage,
        private readonly ResponseCheckerInterface $responseChecker,
    ) {
    }

    #[Given('I am at the checkout addressing step')]
    public function iAmAtTheCheckoutAddressingStep(): void
    {
    }

    #[When('/^I specify the billing (address as "([^"]+)", "([^"]+)", "([^"]+)", "([^"]+)" for "([^"]+)")$/')]
    public function iSpecifyTheBillingAddressAs(AddressInterface $address): void
    {
        $this->content['billingAddress'] = [
            'city' => $address->getCity() ?? '',
            'street' => $address->getStreet() ?? '',
            'postcode' => $address->getPostcode() ?? '',
            'countryCode' => $address->getCountryCode() ?? '',
            'firstName' => $address->getFirstName() ?? '',
            'lastName' => $address->getLastName() ?? '',
            'provinceName' => $address->getProvinceName(),
        ];
    }

    #[When('/^I specify the billing company as "([^"]+)"$/')]
    public function iSpecifyTheBillingCompanyAs(string $company): void
    {
        $this->content['billingAddress']['company'] = $company;
    }

    #[When('/^I specify the billing VAT number as "([^"]+)"$/')]
    public function iSpecifyTheBillingVatNumberAs(string $vatNumber): void
    {
        $this->content['billingAddress']['vatNumber'] = $vatNumber;
    }

    #[When('/^I (?:try to )?complete the addressing step$/')]
    public function iCompleteTheAddressingStep(): void
    {
        $this->client
            ->buildUpdateRequest(Resources::ORDERS, $this->getCartToken())
            ->setRequestData($this->content)
            ->update()
        ;

        $this->content = [];
    }

    #[Then('/^I should be notified that the VAT number in (shipping|billing) is required$/')]
    public function iShouldBeNotifiedThatTheVatNumberIsRequired(string $type): void
    {
        $this->assertViolation('Please enter a VAT number.', sprintf('%sAddress.vatNumber', $type));
    }

    #[Then('/^I should be notified that the company VAT number in (shipping|billing) is required$/')]
    public function iShouldBeNotifiedThatTheCompanyVatNumberIsRequired(string $type): void
    {
        $this->assertViolation('Please enter the company VAT number.', sprintf('%sAddress.vatNumber', $type));
    }

    #[Then('/^I should be notified that the VAT number in (shipping|billing) is not valid$/')]
    public function iShouldBeNotifiedThatTheVatNumberIsNotValid(string $type): void
    {
        $this->assertViolation('Please enter a valid VAT number.', sprintf('%sAddress.vatNumber', $type));
    }

    #[Then('the cart billing address VAT number should be :vatNumber')]
    public function theCartBillingAddressVatNumberShouldBe(string $vatNumber): void
    {
        $response = $this->client->show(Resources::ORDERS, $this->getCartToken());
        $billingAddress = $this->responseChecker->getValue($response, 'billingAddress');

        Assert::isArray($billingAddress);
        Assert::same($billingAddress['vatNumber'] ?? null, $vatNumber);
    }

    private function assertViolation(string $message, string $propertyPath): void
    {
        Assert::true(
            $this->responseChecker->hasViolationWithMessage(
                $this->client->getLastResponse(),
                $message,
                $propertyPath,
            ),
        );
    }

    private function getCartToken(): string
    {
        if ($this->sharedStorage->has('cart_token')) {
            return (string) $this->sharedStorage->get('cart_token');
        }

        return (string) $this->sharedStorage->get('previous_cart_token');
    }
}
