<?php

declare(strict_types=1);

namespace App\Domain\EmployeeDebt\Service;

// Domain Layer
use App\Domain\EmployeeDebt\Entity\EmployeeDebt;
use App\Domain\EmployeeDebt\Repository\EmployeeDebtRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;

// Application Layer
use App\Application\EmployeeDebt\Dto\EmployeeDebtDetailInfo;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;

/**
 * EmployeeDebt Domain Service
 *
 * Pure business logic and domain rules.
 *
 * EmployeeDebt is a standalone module that only records debts for
 * PayrollDeductionType with creates_debt = 1. Payment tracking is manual
 * (paid_amount / remaining_amount / is_settled). There is no installment
 * system and no auto-generation of PayrollDeduction records.
 */
final class EmployeeDebtDomainService
{
    use DomainValidator;

    public function __construct(
        private EmployeeDebtRepositoryInterface $repository,
    ) {}

    /**
     * Guard: EmployeeDebt hanya boleh dibuat dengan PayrollDeductionType
     * yang memiliki creates_debt = 1. Tolak jika tidak.
     */
    public function guardCreatesDebt(EmployeeDebtDetailInfo $debtInfo): void
    {
        if (!$debtInfo->createsDebt()) {
            $deductionType = $debtInfo->getDeductionType();

            throw new BadRequestException(
                translate: Message::create(
                    key: 'employee_debt.deduction_type_not_creates_debt',
                    params: [
                        'id' => $deductionType['id'] ?? null,
                        'name' => $deductionType['name'] ?? null,
                    ],
                    domain: 'validation'
                )
            );
        }
    }
}
