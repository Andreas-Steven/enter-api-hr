<?php

declare(strict_types=1);

namespace App\Domain\PayrollDeduction\Service;

// Domain Layer
use App\Domain\PayrollDeduction\Repository\PayrollDeductionRepositoryInterface;
use App\Domain\PayrollDeduction\Result\DebtContext;
use App\Domain\EmployeeDebt\Repository\EmployeeDebtRepositoryInterface;
use App\Domain\EmployeeDebtTransaction\Service\EmployeeDebtTransactionDomainService;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\Enums\TransactionType;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\NotFoundException;

/**
 * PayrollDeduction Domain Service
 *
 * Pure business logic and domain rules untuk baris potongan di slip gaji.
 *
 * Aturan utama:
 * - period format YYYY-MM (string)
 * - amount > 0
 * - jika employee_debt_id > 0, derive employee_id & deduction_type_id dari EmployeeDebt
 */
final class PayrollDeductionDomainService
{
    use DomainValidator;

    public function __construct(
        private PayrollDeductionRepositoryInterface $repository,
        private EmployeeDebtRepositoryInterface $employeeDebtRepository,
        private EmployeeDebtTransactionDomainService $debtTransactionDomainService,
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

    /**
     * Resolve context untuk create PayrollDeduction.
     *
     * Business rule:
     * - Jika employeeDebtId > 0: fetch EmployeeDebt, verifikasi employee_id
     *   dari input harus cocok, guard debt Approved & Active, lalu derive
     *   employee_id & deduction_type_id dari EmployeeDebt (input diabaikan).
     * - Jika employeeDebtId <= 0: gunakan employee_id & deduction_type_id
     *   dari input (potongan gaji biasa).
     */
    public function resolveDebtContext(
        int $employeeDebtId,
        int $employeeId,
        int $deductionTypeId,
    ): DebtContext {
        if ($employeeDebtId <= 0) {
            return DebtContext::create(
                employeeId: $employeeId,
                deductionTypeId: $deductionTypeId,
                employeeDebtId: null,
            );
        }

        $debt = $this->employeeDebtRepository->findById(
            id: $employeeDebtId,
            status: null
        );

        if ($debt === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => 'EmployeeDebt',
                        'field' => 'id',
                        'value' => $employeeDebtId
                    ]
                )
            );
        }

        if ($debt->getEmployeeId() !== $employeeId) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'payroll_deduction.employee_id_mismatch',
                    domain: 'app',
                )
            );
        }

        $this->debtTransactionDomainService->guardDebtApprovedAndActive(
            debt: $debt,
            transactionType: TransactionType::PAYMENT,
        );

        return DebtContext::create(
            employeeId: $debt->getEmployeeId(),
            deductionTypeId: $debt->getDeductionTypeId(),
            employeeDebtId: $employeeDebtId,
        );
    }
}
