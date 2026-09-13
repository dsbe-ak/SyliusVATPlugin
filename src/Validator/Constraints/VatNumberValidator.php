<?php

declare(strict_types=1);

namespace Gewebe\SyliusVATPlugin\Validator\Constraints;

use DateTimeImmutable;
use Gewebe\SyliusVATPlugin\Config\VatNumberValidatorConfig;
use Gewebe\SyliusVATPlugin\Entity\VatNumberAddressInterface;
use Gewebe\SyliusVATPlugin\Vat\Number\ClientException;
use Gewebe\SyliusVATPlugin\Vat\Number\VatNumberValidatorInterface;
use Gewebe\SyliusVATPlugin\Vat\Number\VatNumberValidatorProviderInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Contracts\Service\ResetInterface;

class VatNumberValidator extends ConstraintValidator implements ResetInterface
{
    /** @var array<string, bool|null> */
    private array $registrationResults = [];

    public function __construct(
        private readonly VatNumberValidatorProviderInterface $validatorProvider,
        private readonly VatNumberValidatorConfig $validatorConfig,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof VatNumber) {
            throw new UnexpectedTypeException($constraint, VatNumber::class);
        }

        if (!$value instanceof VatNumberAddressInterface) {
            throw new UnexpectedValueException($value, VatNumberAddressInterface::class);
        }

        if ($this->hasVatNumberForCompany($value, $constraint) === false ||
            $this->hasVatNumberForCountry($value, $constraint) === false ||
            $this->validatorConfig->validateFormat === false ||
            $value->getVatNumber() === null ||
            $value->getVatNumber() === '' ||
            $value->getCountryCode() === null
        ) {
            return;
        }

        $vatNumberValidator = $this->validatorProvider->getValidator($value->getCountryCode());
        if (null === $vatNumberValidator) {
            return;
        }

        if ($vatNumberValidator->validateFormat($value->getVatNumber()) === false) {
            $this->addViolation($constraint->messageInvalidFormat, $constraint->vatNumberPath);

            return;
        }

        if ($this->validatorConfig->validateCountry &&
            $vatNumberValidator->validateCountry($value->getVatNumber(), $value->getCountryCode()) === false
        ) {
            $this->addViolation($constraint->messageInvalidCountry, $constraint->vatNumberPath);

            return;
        }

        if ($this->validatorConfig->validateRegistration && $this->hasCurrentValidation($value)) {
            $validVatNumber = $value->hasValidVatNumber();
            $this->registrationResults[$this->getCacheKey($value)] = $validVatNumber;

            if (!$validVatNumber) {
                $this->addViolation($constraint->messageInvalidRegistration, $constraint->vatNumberPath);
            }

            return;
        }

        if ($this->validatorConfig->validateRegistration) {
            $this->validateRegistration($value, $vatNumberValidator, $constraint);
        }
    }

    public function reset(): void
    {
        $this->registrationResults = [];
    }

    private function addViolation(string $message, string $path): void
    {
        $this->context->buildViolation($message)->atPath($path)->addViolation();
    }

    private function hasVatNumberForCompany(VatNumberAddressInterface $address, VatNumber $constraint): bool
    {
        if ($this->validatorConfig->isRequiredForCompany &&
            $address->getCompany() !== null &&
            $address->getCompany() !== '' &&
            $address->hasVatNumber() === false
        ) {
            $this->addViolation($constraint->messageRequiredForCompany, $constraint->vatNumberPath);

            return false;
        }

        return true;
    }

    private function hasVatNumberForCountry(VatNumberAddressInterface $address, VatNumber $constraint): bool
    {
        if (count($this->validatorConfig->requiredForCountries) > 0 &&
            in_array($address->getCountryCode(), $this->validatorConfig->requiredForCountries, true) &&
            $address->hasVatNumber() === false
        ) {
            $this->addViolation($constraint->messageRequired, $constraint->vatNumberPath);

            return false;
        }

        return true;
    }

    private function validateRegistration(
        VatNumberAddressInterface $address,
        VatNumberValidatorInterface $validator,
        VatNumber $constraint,
    ): void {
        $cacheKey = $this->getCacheKey($address);

        if (!array_key_exists($cacheKey, $this->registrationResults)) {
            try {
                $this->registrationResults[$cacheKey] = $validator->validate($address->getVatNumber() ?? '');
            } catch (ClientException) {
                $this->registrationResults[$cacheKey] = null;
            }
        }

        $validVatNumber = $this->registrationResults[$cacheKey];
        if ($validVatNumber === null) {
            if ($this->validatorConfig->validateOnServiceUnavailable === true) {
                $this->addViolation($constraint->messageServiceUnavailable, $constraint->vatNumberPath);
            }

            return;
        }

        $address->setVatValid($validVatNumber);

        if (false === $validVatNumber) {
            $this->addViolation($constraint->messageInvalidRegistration, $constraint->vatNumberPath);
        }
    }

    private function getCacheKey(VatNumberAddressInterface $address): string
    {
        return sprintf('%s:%s', $address->getCountryCode(), $address->getVatNumber());
    }

    private function hasCurrentValidation(VatNumberAddressInterface $address): bool
    {
        $validatedAt = $address->getVatValidatedAt();
        if ($validatedAt === null) {
            return false;
        }

        $expiresAt = DateTimeImmutable::createFromInterface($validatedAt)
            ->modify(sprintf('%d days', $this->validatorConfig->expirationDays));

        return $expiresAt > new DateTimeImmutable();
    }
}
