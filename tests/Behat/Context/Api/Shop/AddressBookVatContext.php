<?php

declare(strict_types=1);

namespace Tests\Gewebe\SyliusVATPlugin\Behat\Context\Api\Shop;

use Behat\Behat\Context\Context;
use Behat\Step\Then;
use Behat\Step\When;
use Sylius\Behat\Client\ApiClientInterface;
use Sylius\Behat\Client\ResponseCheckerInterface;
use Sylius\Behat\Context\Api\Resources;
use Webmozart\Assert\Assert;

final readonly class AddressBookVatContext implements Context
{
    public function __construct(
        private ApiClientInterface $client,
        private ResponseCheckerInterface $responseChecker,
    ) {
    }

    #[When('/^I (?:specify the VAT number as|change the VAT number to) "([^"]+)"$/')]
    public function iSpecifyTheVatNumberAs(string $vatNumber): void
    {
        $this->client->addRequestData('vatNumber', $vatNumber);
    }

    #[When('I remove the VAT number')]
    public function iRemoveTheVatNumber(): void
    {
        $this->client->addRequestData('vatNumber', null);
    }

    #[Then('the returned address should contain the VAT number :vatNumber')]
    public function theReturnedAddressShouldContainTheVatNumber(string $vatNumber): void
    {
        Assert::true(
            $this->responseChecker->hasValue($this->client->getLastResponse(), 'vatNumber', $vatNumber),
        );
    }

    #[Then('the returned address VAT number should be marked as valid')]
    public function theReturnedAddressVatNumberShouldBeMarkedAsValid(): void
    {
        $response = $this->client->getLastResponse();

        Assert::true($this->responseChecker->hasValue($response, 'vatValid', true));
        Assert::notEmpty($this->responseChecker->getValue($response, 'vatValidatedAt'));
    }

    #[Then('the returned address should not contain a VAT number')]
    public function theReturnedAddressShouldNotContainAVatNumber(): void
    {
        Assert::true(
            $this->responseChecker->hasValue($this->client->getLastResponse(), 'vatNumber', null),
        );
    }

    #[Then('the returned address VAT validation should be reset')]
    public function theReturnedAddressVatValidationShouldBeReset(): void
    {
        $response = $this->client->getLastResponse();

        Assert::true($this->responseChecker->hasValue($response, 'vatValid', false));
        Assert::true($this->responseChecker->hasValue($response, 'vatValidatedAt', null));
    }

    #[Then('the address of :fullName should contain the valid VAT number :vatNumber')]
    public function theAddressOfShouldContainTheValidVatNumber(string $fullName, string $vatNumber): void
    {
        $address = $this->findAddress($fullName);

        Assert::keyExists($address, 'vatNumber');
        Assert::same($address['vatNumber'], $vatNumber);
        Assert::keyExists($address, 'vatValid');
        Assert::true($address['vatValid']);
        Assert::keyExists($address, 'vatValidatedAt');
        Assert::notEmpty($address['vatValidatedAt']);
    }

    #[Then('the address of :fullName should not contain a VAT number and its validation should be reset')]
    public function theAddressOfShouldNotContainAVatNumberAndItsValidationShouldBeReset(string $fullName): void
    {
        $address = $this->findAddress($fullName);

        Assert::keyExists($address, 'vatNumber');
        Assert::null($address['vatNumber']);
        Assert::keyExists($address, 'vatValid');
        Assert::false($address['vatValid']);
        Assert::keyExists($address, 'vatValidatedAt');
        Assert::null($address['vatValidatedAt']);
    }

    /** @return array<string, mixed> */
    private function findAddress(string $fullName): array
    {
        [$firstName, $lastName] = explode(' ', $fullName, 2);
        $addresses = $this->responseChecker->getCollection($this->client->index(Resources::ADDRESSES));

        foreach ($addresses as $address) {
            if ($address['firstName'] === $firstName && $address['lastName'] === $lastName) {
                return $address;
            }
        }

        throw new \InvalidArgumentException(sprintf('No address for "%s" was found.', $fullName));
    }
}
