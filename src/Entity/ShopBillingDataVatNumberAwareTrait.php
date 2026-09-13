<?php

declare(strict_types=1);

namespace Gewebe\SyliusVATPlugin\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gewebe\SyliusVATPlugin\Vat\Number\VatNumberNormalizer;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Trait that implements the shop billing data vat number functionality
 *
 * @see ShopBillingData
 */
trait ShopBillingDataVatNumberAwareTrait
{
    #[ORM\Column(name: 'vat_number', type: Types::STRING, nullable: true)]
    #[Groups(['admin:shop_billing_data:read'])]
    protected ?string $vatNumber = null;

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function setVatNumber(?string $vatNumber): void
    {
        if ($vatNumber !== null) {
            $vatNumber = VatNumberNormalizer::normalize($vatNumber);
        }

        $this->vatNumber = $vatNumber;
    }

    public function hasVatNumber(): bool
    {
        return null !== $this->vatNumber && '' !== $this->vatNumber;
    }
}
