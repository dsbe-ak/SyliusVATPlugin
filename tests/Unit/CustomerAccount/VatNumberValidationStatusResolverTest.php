<?php

declare(strict_types=1);

namespace Tests\Gewebe\SyliusVATPlugin\Unit\CustomerAccount;

use DateTime;
use Gewebe\SyliusVATPlugin\CustomerAccount\VatNumberValidationStatusResolver;
use Gewebe\SyliusVATPlugin\Entity\VatNumberAddressInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Addressing\Model\AddressInterface;

final class VatNumberValidationStatusResolverTest extends TestCase
{
    public function testItReturnsNothingWhenTheDisplayIsDisabled(): void
    {
        $resolver = new VatNumberValidationStatusResolver();

        self::assertNull($resolver->resolve($this->getAddress()));
    }

    public function testItReturnsNothingForAnAddressWithoutVatSupport(): void
    {
        $resolver = new VatNumberValidationStatusResolver(enabled: true);

        self::assertNull($resolver->resolve($this->createMock(AddressInterface::class)));
    }

    public function testItReturnsNothingForAnAddressWithoutVatNumber(): void
    {
        $resolver = new VatNumberValidationStatusResolver(enabled: true);

        self::assertNull($resolver->resolve($this->getAddress(hasVatNumber: false)));
    }

    public function testItReturnsNotValidatedWithoutValidationDate(): void
    {
        $resolver = new VatNumberValidationStatusResolver(enabled: true);

        self::assertSame(
            VatNumberValidationStatusResolver::NOT_VALIDATED,
            $resolver->resolve($this->getAddress()),
        );
    }

    public function testItReturnsInvalidForAnUnsuccessfulValidation(): void
    {
        $resolver = new VatNumberValidationStatusResolver(enabled: true);

        self::assertSame(
            VatNumberValidationStatusResolver::INVALID,
            $resolver->resolve($this->getAddress(validatedAt: new DateTime(), valid: false)),
        );
    }

    public function testItReturnsExpiredForAnOutdatedValidation(): void
    {
        $resolver = new VatNumberValidationStatusResolver(enabled: true, expirationDays: 30);

        self::assertSame(
            VatNumberValidationStatusResolver::EXPIRED,
            $resolver->resolve($this->getAddress(validatedAt: new DateTime('-31 days'), valid: true)),
        );
    }

    public function testItReturnsValidForACurrentSuccessfulValidation(): void
    {
        $resolver = new VatNumberValidationStatusResolver(enabled: true, expirationDays: 30);

        self::assertSame(
            VatNumberValidationStatusResolver::VALID,
            $resolver->resolve($this->getAddress(validatedAt: new DateTime(), valid: true)),
        );
    }

    public function testItDoesNotExpireAHistoricalValidationWhenExpirationIsDisabled(): void
    {
        $resolver = new VatNumberValidationStatusResolver(enabled: true, expirationDays: 30);

        self::assertSame(
            VatNumberValidationStatusResolver::VALID,
            $resolver->resolve(
                $this->getAddress(validatedAt: new DateTime('2020-01-01'), valid: true),
                checkExpiration: false,
            ),
        );
    }

    private function getAddress(
        bool $hasVatNumber = true,
        ?DateTime $validatedAt = null,
        bool $valid = false,
    ): VatNumberAddressInterface {
        $address = $this->createMock(VatNumberAddressInterface::class);
        $address->method('hasVatNumber')->willReturn($hasVatNumber);
        $address->method('getVatValidatedAt')->willReturn($validatedAt);
        $address->method('isVatValid')->willReturn($valid);

        return $address;
    }
}
