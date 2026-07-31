<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebtTransaction\Dto;

/**
 * DTO shape untuk kolom detail_info JSON pada employee_debt_transaction.
 *
 * Struktur konsisten dengan default migration:
 * {
 *   "employee_debt": { "id": int, "name": string } | null,
 *   "employee_debt_installment": { "id": int, "name": string, "sequence": int } | null,
 *   "payroll_deduction": { "id": int, "name": string, "period": string } | null,
 *   "change_log": { ... }
 * }
 */
final readonly class EmployeeDebtTransactionDetailInfo
{
    public function __construct(
        public ?array $employee_debt = null,
        public ?array $employee_debt_installment = null,
        public ?array $payroll_deduction = null,
    ) {}

    public function toArray(): array
    {
        return [
            'employee_debt' => $this->employee_debt,
            'employee_debt_installment' => $this->employee_debt_installment,
            'payroll_deduction' => $this->payroll_deduction,
        ];
    }

    public function getEmployeeDebt(): ?array
    {
        return $this->employee_debt;
    }

    public function getEmployeeDebtInstallment(): ?array
    {
        return $this->employee_debt_installment;
    }

    public function getPayrollDeduction(): ?array
    {
        return $this->payroll_deduction;
    }
}
