<?php

declare(strict_types=1);

namespace Tests\Gewebe\SyliusVATPlugin\Unit\Api\Mapper;

use Gewebe\SyliusVATPlugin\Api\Mapper\AddressMapper;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Mapper\AddressMapperInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Tests\Gewebe\SyliusVATPlugin\Entity\Addressing\Address;

final class AddressMapperTest extends TestCase
{
    public function testItMapsVatNumberWhenUpdatingAnAddress(): void
    {
        $currentAddress = new Address();
        $currentAddress->setVatNumber('DE123456789');
        $currentAddress->setVatValid(true);

        $targetAddress = new Address();
        $targetAddress->setVatNumber('DE123123123');

        $decoratedMapper = $this->createMock(AddressMapperInterface::class);
        $decoratedMapper
            ->expects(static::once())
            ->method('mapExisting')
            ->with($currentAddress, $targetAddress)
            ->willReturn($currentAddress)
        ;

        $result = (new AddressMapper($decoratedMapper))->mapExisting($currentAddress, $targetAddress);

        static::assertSame($currentAddress, $result);
        static::assertSame('DE123123123', $currentAddress->getVatNumber());
        static::assertFalse($currentAddress->hasValidVatNumber());
        static::assertNull($currentAddress->getVatValidatedAt());
    }

    public function testItCanRemoveVatNumberWhenUpdatingAnAddress(): void
    {
        $currentAddress = new Address();
        $currentAddress->setVatNumber('DE123456789');

        $targetAddress = new Address();

        $decoratedMapper = $this->createStub(AddressMapperInterface::class);
        $decoratedMapper->method('mapExisting')->willReturn($currentAddress);

        (new AddressMapper($decoratedMapper))->mapExisting($currentAddress, $targetAddress);

        static::assertNull($currentAddress->getVatNumber());
    }

    public function testItRequiresVatNumberAwareAddresses(): void
    {
        $currentAddress = $this->createStub(AddressInterface::class);
        $targetAddress = $this->createStub(AddressInterface::class);

        $decoratedMapper = $this->createStub(AddressMapperInterface::class);
        $decoratedMapper->method('mapExisting')->willReturn($currentAddress);

        $this->expectException(\InvalidArgumentException::class);

        (new AddressMapper($decoratedMapper))->mapExisting($currentAddress, $targetAddress);
    }
}
