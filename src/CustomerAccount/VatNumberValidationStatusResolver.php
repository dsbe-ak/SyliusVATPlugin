<?php

declare(strict_types=1);

namespace Gewebe\SyliusVATPlugin\CustomerAccount;

use DateTimeImmutable;
use Gewebe\SyliusVATPlugin\Entity\VatNumberAddressInterface;
use Sylius\Component\Addressing\Model\AddressInterface;

final readonly class VatNumberValidationStatusResolver
{
    public const VALID = 'valid';

    public const INVALID = 'invalid';

    public const EXPIRED = 'expired';

    public const NOT_VALIDATED = 'not_validated';

    public function __construct(
        private bool $enabled = false,
        private int $expirationDays = 30,
    ) {
    }

    public function resolve(AddressInterface $address): ?string
    {
        if (!$this->enabled ||
            !$address instanceof VatNumberAddressInterface ||
            !$address->hasVatNumber()
        ) {
            return null;
        }

        $validatedAt = $address->getVatValidatedAt();
        if ($validatedAt === null) {
            return self::NOT_VALIDATED;
        }

        if (!$address->isVatValid()) {
            return self::INVALID;
        }

        $expiresAt = DateTimeImmutable::createFromInterface($validatedAt)
            ->modify(sprintf('+%d days', $this->expirationDays));

        if ($expiresAt <= new DateTimeImmutable()) {
            return self::EXPIRED;
        }

        return self::VALID;
    }
}
