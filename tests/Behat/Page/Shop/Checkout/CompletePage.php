<?php

declare(strict_types=1);

namespace Tests\Gewebe\SyliusVATPlugin\Behat\Page\Shop\Checkout;

use Sylius\Behat\Page\Shop\Checkout\CompletePage as BaseCompletePage;

class CompletePage extends BaseCompletePage implements CompletePageInterface
{
    public function hasVatNumberValidationStatus(string $type, string $status): bool
    {
        return null !== $this->getDocument()->find(
            'css',
            sprintf(
                '[data-test-%s-address] [data-test-vat-validation-status="%s"]',
                $type,
                $status,
            ),
        );
    }
}
