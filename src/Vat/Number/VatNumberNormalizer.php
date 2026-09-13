<?php

declare(strict_types=1);

namespace Gewebe\SyliusVATPlugin\Vat\Number;

final class VatNumberNormalizer
{
    public static function normalize(string $vatNumber): string
    {
        return strtoupper((string) preg_replace('/[\s\p{Z}.\-]+/u', '', $vatNumber));
    }
}
