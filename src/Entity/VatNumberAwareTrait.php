<?php

declare(strict_types=1);

namespace Gewebe\SyliusVATPlugin\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gewebe\SyliusVATPlugin\Vat\Number\VatNumberNormalizer;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Trait that implements the vat number functionality
 *
 * @see Address
 */
trait VatNumberAwareTrait
{
    #[ORM\Column(name: 'vat_number', type: Types::STRING, nullable: true)]
    #[Gedmo\Versioned]
    #[Groups([
        'shop:address:read',
        'shop:address:create',
        'shop:address:update',
        'sylius:admin:address:index',
        'sylius:admin:address:show',
        'sylius:admin:address:update',
        'sylius:admin:order:index',
        'sylius:admin:order:show',
        'sylius:shop:address:create',
        'sylius:shop:address:index',
        'sylius:shop:address:show',
        'sylius:shop:address:update',
        'sylius:shop:cart:update',
        'sylius:shop:cart:show',
        'sylius:shop:order:account:show',
    ])]
    protected ?string $vatNumber = null;

    #[ORM\Column(name: 'vat_valid', type: Types::BOOLEAN)]
    #[Gedmo\Versioned]
    #[Groups([
        'shop:address:read',
        'sylius:admin:address:index',
        'sylius:admin:address:show',
        'sylius:admin:order:index',
        'sylius:admin:order:show',
        'sylius:shop:address:index',
        'sylius:shop:address:show',
        'sylius:shop:cart:show',
        'sylius:shop:order:account:show',
    ])]
    protected bool $vatValid = false;

    #[ORM\Column(name: 'vat_validated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Versioned]
    #[Groups([
        'shop:address:read',
        'sylius:admin:address:index',
        'sylius:admin:address:show',
        'sylius:admin:order:index',
        'sylius:admin:order:show',
        'sylius:shop:address:index',
        'sylius:shop:address:show',
        'sylius:shop:cart:show',
        'sylius:shop:order:account:show',
    ])]
    protected ?DateTime $vatValidatedAt = null;

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function setVatNumber(?string $vatNumber): void
    {
        if ($vatNumber !== null) {
            $vatNumber = VatNumberNormalizer::normalize($vatNumber);
        }

        if ($this->vatNumber !== $vatNumber) {
            $this->vatValid = false;
            $this->vatValidatedAt = null;
        }

        $this->vatNumber = $vatNumber;
    }

    public function hasVatNumber(): bool
    {
        return null !== $this->vatNumber && '' !== $this->vatNumber;
    }

    /**
     * @deprecated since 2.3, use isVatValid() instead.
     */
    public function hasValidVatNumber(): bool
    {
        return $this->isVatValid();
    }

    public function isVatValid(): bool
    {
        return $this->hasVatNumber() && $this->vatValid;
    }

    public function setVatValid(bool $valid, ?DateTime $validatedAt = null): void
    {
        $this->vatValid = $valid;
        $this->vatValidatedAt = $validatedAt ?? new DateTime();
    }

    public function getVatValidatedAt(): ?DateTime
    {
        return $this->vatValidatedAt;
    }
}
