<?php

declare(strict_types=1);

namespace App\Domain\PayrollRatePolicies\Service;

// Domain Layer
use App\Domain\PayrollRatePolicies\Entity\PayrollRatePolicies;
use App\Domain\PayrollRatePolicies\Repository\PayrollRatePoliciesRepositoryInterface;
use App\Domain\PayrollDeductionType\Repository\PayrollDeductionTypeRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\Enums\PayrollRateType;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

/**
 * PayrollRatePolicies Domain Service
 *
 * Pure business logic and domain rules
 */
final class PayrollRatePoliciesDomainService
{
    use DomainValidator;

    public function __construct(
        private PayrollRatePoliciesRepositoryInterface $repository,
        private PayrollDeductionTypeRepositoryInterface $deductionTypeRepository,
    ) {}

    /**
     * Guards that the deduction_type_id is consistent with the incident type.
     *
     * - Earning incidents (OVERTIME): deduction_type_id MUST be null.
     * - Deduction incidents (LATE, EARLY_OUT, MISSING_IN, MISSING_OUT,
     *   ABSENT): deduction_type_id MUST be set and point to a
     *   PayrollDeductionType with creates_debt = 0.
     *
     * @throws BadRequestException if the combination is invalid.
     */
    public function guardRateConsistency(
        PayrollRateType $rateType,
        ?int $deductionTypeId,
    ): void {
        if ($rateType->isEarning()) {
            // OVERTIME: deduction_type_id must be null.
            if ($deductionTypeId !== null) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'payroll_rate_policy.earning_must_not_have_deduction_type',
                        params: [
                            'rate_type' => $rateType->value,
                        ]
                    )
                );
            }

            return;
        }

        // Deduction incidents: deduction_type_id is required.
        if ($deductionTypeId === null) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'payroll_rate_policy.deduction_requires_deduction_type',
                    params: [
                        'rate_type' => $rateType->value,
                    ]
                )
            );
        }

        $this->guardDeductionTypeNotDebtCreating($deductionTypeId);
    }

    /**
     * Guards that the given deduction type does not create debt.
     *
     * PayrollRatePolicies maps attendance incidents to deduction amounts. These
     * are direct salary deductions (potong gaji), NOT debt-creating deductions
     * (kasbon). Therefore the referenced PayrollDeductionType MUST have
     * creates_debt = 0.
     *
     * @throws BadRequestException if the deduction type does not exist or
     *         has creates_debt = 1.
     */
    public function guardDeductionTypeNotDebtCreating(int $deductionTypeId): void
    {
        $deductionType = $this->deductionTypeRepository->findById($deductionTypeId);

        if ($deductionType === null) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => 'PayrollDeductionType',
                        'field' => 'id',
                        'value' => $deductionTypeId,
                    ]
                )
            );
        }

        if ($deductionType->getCreatesDebt()->isYes()) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'payroll_rate_policy.invalid_debt_creating_type',
                    params: [
                        'deduction_type' => $deductionType->getName(),
                    ]
                )
            );
        }
    }
}
