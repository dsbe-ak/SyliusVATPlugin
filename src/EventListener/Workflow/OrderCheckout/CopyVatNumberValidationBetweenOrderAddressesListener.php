<?php

declare(strict_types=1);

namespace Gewebe\SyliusVATPlugin\EventListener\Workflow\OrderCheckout;

use Gewebe\SyliusVATPlugin\Entity\VatNumberAddressInterface;
use Sylius\Component\Addressing\Comparator\AddressComparatorInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Webmozart\Assert\Assert;

final readonly class CopyVatNumberValidationBetweenOrderAddressesListener
{
    public function __construct(private AddressComparatorInterface $addressComparator)
    {
    }

    public function __invoke(CompletedEvent $event): void
    {
        $order = $event->getSubject();
        Assert::isInstanceOf($order, OrderInterface::class);

        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();

        if (
            !$billingAddress instanceof VatNumberAddressInterface ||
            !$shippingAddress instanceof VatNumberAddressInterface ||
            !$billingAddress->hasVatNumber() ||
            $billingAddress->getVatNumber() !== $shippingAddress->getVatNumber() ||
            !$this->addressComparator->equal($billingAddress, $shippingAddress)
        ) {
            return;
        }

        if ($billingAddress->getVatValidatedAt() !== null && $shippingAddress->getVatValidatedAt() === null) {
            $this->copyValidation($billingAddress, $shippingAddress);

            return;
        }

        if ($shippingAddress->getVatValidatedAt() !== null && $billingAddress->getVatValidatedAt() === null) {
            $this->copyValidation($shippingAddress, $billingAddress);
        }
    }

    private function copyValidation(
        VatNumberAddressInterface $source,
        VatNumberAddressInterface $target,
    ): void {
        $validatedAt = $source->getVatValidatedAt();
        Assert::notNull($validatedAt);

        $target->setVatValid($source->hasValidVatNumber(), clone $validatedAt);
    }
}
