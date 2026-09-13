<?php

declare(strict_types=1);

namespace Tests\Gewebe\SyliusVATPlugin\Unit\Vat\Number;

use Gewebe\SyliusVATPlugin\Vat\Number\VatNumberNormalizer;
use PHPUnit\Framework\TestCase;

final class VatNumberNormalizerTest extends TestCase
{
    public function testItNormalizesVatNumber(): void
    {
        self::assertSame('DE123456789', VatNumberNormalizer::normalize(" de\u{00A0}123.456-789 "));
    }
}
