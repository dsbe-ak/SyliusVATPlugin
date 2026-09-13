<?php

declare(strict_types=1);

namespace Tests\Gewebe\SyliusVATPlugin\Behat\Page\Shop\Checkout;

interface CompletePageInterface
{
    public function hasVatNumberValidationStatus(string $type, string $status): bool;
}
