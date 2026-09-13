<?php

declare(strict_types=1);

namespace Gewebe\SyliusVATPlugin\Twig;

use Gewebe\SyliusVATPlugin\CustomerAccount\VatNumberValidationStatusResolver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class VatNumberValidationStatusExtension extends AbstractExtension
{
    public function __construct(
        private readonly VatNumberValidationStatusResolver $statusResolver,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('gewebe_sylius_vat_validation_status', $this->statusResolver->resolve(...)),
        ];
    }
}
