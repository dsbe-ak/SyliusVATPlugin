<?php

declare(strict_types=1);

namespace Gewebe\SyliusVATPlugin\Api\Mapper;

use Gewebe\SyliusVATPlugin\Entity\VatNumberAddressInterface;
use Sylius\Bundle\ApiBundle\Mapper\AddressMapperInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Webmozart\Assert\Assert;

final readonly class AddressMapper implements AddressMapperInterface
{
    public function __construct(private AddressMapperInterface $decoratedMapper)
    {
    }

    public function mapExisting(AddressInterface $currentAddress, AddressInterface $targetAddress): AddressInterface
    {
        $address = $this->decoratedMapper->mapExisting($currentAddress, $targetAddress);

        Assert::isInstanceOf($address, VatNumberAddressInterface::class);
        Assert::isInstanceOf($targetAddress, VatNumberAddressInterface::class);

        $address->setVatNumber($targetAddress->getVatNumber());

        return $address;
    }
}
