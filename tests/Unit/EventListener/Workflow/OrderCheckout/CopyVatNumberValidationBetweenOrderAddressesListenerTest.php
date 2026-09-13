<?php

declare(strict_types=1);

namespace Tests\Gewebe\SyliusVATPlugin\Unit\EventListener\Workflow\OrderCheckout;

use DateTime;
use Gewebe\SyliusVATPlugin\EventListener\Workflow\OrderCheckout\CopyVatNumberValidationBetweenOrderAddressesListener;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Addressing\Comparator\AddressComparatorInterface;
use Sylius\Component\Core\Model\Order;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Marking;
use Tests\Gewebe\SyliusVATPlugin\Entity\Addressing\Address;

final class CopyVatNumberValidationBetweenOrderAddressesListenerTest extends TestCase
{
    public function testItCopiesBillingAddressValidationToAnIdenticalShippingAddress(): void
    {
        $validatedAt = new DateTime('2026-09-11 12:00:00');
        $billingAddress = $this->createAddress('DE123456789');
        $billingAddress->setVatValid(true, $validatedAt);
        $shippingAddress = $this->createAddress('DE123456789');

        $this->invokeListener($billingAddress, $shippingAddress, true);

        self::assertTrue($shippingAddress->isVatValid());
        self::assertEquals($validatedAt, $shippingAddress->getVatValidatedAt());
        self::assertNotSame($validatedAt, $shippingAddress->getVatValidatedAt());
    }

    public function testItCopiesShippingAddressValidationToAnIdenticalBillingAddress(): void
    {
        $billingAddress = $this->createAddress('DE123456789');
        $shippingAddress = $this->createAddress('DE123456789');
        $shippingAddress->setVatValid(true);

        $this->invokeListener($billingAddress, $shippingAddress, true);

        self::assertTrue($billingAddress->isVatValid());
        self::assertEquals($shippingAddress->getVatValidatedAt(), $billingAddress->getVatValidatedAt());
    }

    public function testItDoesNotCopyValidationBetweenDifferentAddresses(): void
    {
        $billingAddress = $this->createAddress('DE123456789');
        $billingAddress->setVatValid(true);
        $shippingAddress = $this->createAddress('DE123456789');

        $this->invokeListener($billingAddress, $shippingAddress, false);

        self::assertFalse($shippingAddress->isVatValid());
        self::assertNull($shippingAddress->getVatValidatedAt());
    }

    public function testItDoesNotCopyValidationBetweenDifferentVatNumbers(): void
    {
        $billingAddress = $this->createAddress('DE123456789');
        $billingAddress->setVatValid(true);
        $shippingAddress = $this->createAddress('DE987654321');

        $this->invokeListener($billingAddress, $shippingAddress, true, 0);

        self::assertFalse($shippingAddress->isVatValid());
        self::assertNull($shippingAddress->getVatValidatedAt());
    }

    public function testItDoesNotOverwriteExistingValidation(): void
    {
        $billingAddress = $this->createAddress('DE123456789');
        $billingAddress->setVatValid(true, new DateTime('2026-09-11 12:00:00'));
        $shippingAddress = $this->createAddress('DE123456789');
        $shippingAddress->setVatValid(false, $shippingValidatedAt = new DateTime('2026-09-11 13:00:00'));

        $this->invokeListener($billingAddress, $shippingAddress, true);

        self::assertFalse($shippingAddress->isVatValid());
        self::assertSame($shippingValidatedAt, $shippingAddress->getVatValidatedAt());
    }

    private function createAddress(string $vatNumber): Address
    {
        $address = new Address();
        $address->setVatNumber($vatNumber);

        return $address;
    }

    private function invokeListener(
        Address $billingAddress,
        Address $shippingAddress,
        bool $addressesAreEqual,
        int $expectedComparisonCount = 1,
    ): void {
        $addressComparator = $this->createMock(AddressComparatorInterface::class);
        $addressComparator
            ->expects(self::exactly($expectedComparisonCount))
            ->method('equal')
            ->with($billingAddress, $shippingAddress)
            ->willReturn($addressesAreEqual)
        ;

        $order = new Order();
        $order->setBillingAddress($billingAddress);
        $order->setShippingAddress($shippingAddress);

        $listener = new CopyVatNumberValidationBetweenOrderAddressesListener($addressComparator);
        $listener(new CompletedEvent($order, new Marking()));
    }
}
