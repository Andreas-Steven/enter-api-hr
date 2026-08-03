<?php

declare(strict_types=1);

namespace App\Domain\PayrollDeduction\Service;

// Domain Layer
use App\Domain\EmployeeDebtInstallment\Entity\EmployeeDebtInstallment;
use App\Domain\PayrollDeduction\Entity\PayrollDeduction;
use App\Domain\PayrollDeduction\Repository\PayrollDeductionRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ConflictException;

/**
 * PayrollDeduction Domain Service
 *
 * Pure business logic and domain rules untuk baris potongan di slip gaji.
 *
 * Aturan utama:
 * - period format YYYYMM (int)
 * - amount > 0
 * - Untuk potongan yang berasal dari installment: installment harus pending,
 *   dan belum pernah dibuat payroll_deduction untuk installment tersebut
 *   (cek via existsForInstallment).
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

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 9999) {
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
        float $amount,
        ?int $employeeDebtInstallmentId = null,
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

        if ($employeeDebtInstallmentId !== null) {
            if ($this->repository->existsForInstallment($employeeDebtInstallmentId)) {
                throw new ConflictException(
                    translate: Message::create(
                        key: 'payroll_deduction.installment_already_generated',
                        domain: 'validation',
                        params: ['installment_id' => $employeeDebtInstallmentId]
                    )
                );
            }
        }
    }

    /**
     * Validasi installment eligible untuk generate payroll_deduction otomatis:
     * - Masih pending (payment_status = 0)
     * - Belum pernah dibuat payroll_deduction untuk installment ini
     */
    public function guardInstallmentEligibleForGeneration(EmployeeDebtInstallment $installment): void
    {
        if ($installment->isPaid()) {
            throw new ConflictException(
                translate: Message::create(
                    key: 'payroll_deduction.installment_already_paid',
                    domain: 'validation',
                    params: ['installment_id' => $installment->getId()]
                )
            );
        }

        if ($this->repository->existsForInstallment($installment->getId())) {
            throw new ConflictException(
                translate: Message::create(
                    key: 'payroll_deduction.installment_already_generated',
                    domain: 'validation',
                    params: ['installment_id' => $installment->getId()]
                )
            );
        }
    }
}
