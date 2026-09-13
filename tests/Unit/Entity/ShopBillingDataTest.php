<?php

declare(strict_types=1);

namespace Tests\Gewebe\SyliusVATPlugin\Unit\Entity;

use Gewebe\SyliusVATPlugin\Entity\ShopBillingDataVatNumberInterface;
use PHPUnit\Framework\TestCase;
use Tests\Gewebe\SyliusVATPlugin\Entity\Channel\ShopBillingData;

final class ShopBillingDataTest extends TestCase
{
    public function testIsShopBillingDataVatNumber(): void
    {
        $billingData = new ShopBillingData();
        self::assertInstanceOf(ShopBillingDataVatNumberInterface::class, $billingData);
    }

    public function testHasVatNumber(): void
    {
        $billingData = new ShopBillingData();
        self::assertFalse($billingData->hasVatNumber());

        $billingData->setVatNumber(null);
        self::assertFalse($billingData->hasVatNumber());

        $billingData->setVatNumber('');
        self::assertFalse($billingData->hasVatNumber());

        $billingData->setVatNumber(' ');
        self::assertFalse($billingData->hasVatNumber());

        $billingData->setVatNumber('at u123.456-78');
        self::assertTrue($billingData->hasVatNumber());
        self::assertSame('ATU12345678', $billingData->getVatNumber());
    }
}
