<?php

declare(strict_types=1);

namespace App\Domain\PayrollDeduction\Service;

// Domain Layer
use App\Domain\PayrollDeduction\Repository\PayrollDeductionRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;

/**
 * PayrollDeduction Domain Service
 *
 * Pure business logic and domain rules untuk baris potongan di slip gaji.
 *
 * Aturan utama:
 * - period format YYYY-MM (string)
 * - amount > 0
 */
final class PayrollDeductionDomainService
{
    use DomainValidator;

    public function __construct(
        private PayrollDeductionRepositoryInterface $repository
    ) {}

    /**
     * Validasi format periode YYYYMM (integer).
     */
    public function guardPeriod(int $period): void
    {
        $year = intdiv($period, 100);
        $month = $period % 100;

        if ($year < 1900 || $year > 2100 || $month < 1 || $month > 12) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'payroll_deduction.invalid_period',
                    domain: 'validation',
                    params: ['period' => $period]
                )
            );
        }
    }

    /**
     * Validasi saat create payroll_deduction manual.
     */
    public function guardCreate(
        int $employeeId,
        int $deductionTypeId,
        int $period,
        int $amount,
    ): void {
        if ($employeeId < 1) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'validation.min_value',
                    domain: 'validation',
                    params: ['field' => 'employee_id', 'min' => 1]
                )
            );
        }

        if ($deductionTypeId < 1) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'validation.min_value',
                    domain: 'validation',
                    params: ['field' => 'deduction_type_id', 'min' => 1]
                )
            );
        }

        $this->guardPeriod($period);

        if ($amount <= 0) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'validation.min_value',
                    domain: 'validation',
                    params: ['field' => 'amount', 'min' => 0]
                )
            );
        }
    }
}
