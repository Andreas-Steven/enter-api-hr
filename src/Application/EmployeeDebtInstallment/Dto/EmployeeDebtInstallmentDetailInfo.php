<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebtInstallment\Dto;

/**
 * DTO shape untuk kolom detail_info JSON pada employee_debt_installment.
 *
 * Struktur konsisten dengan default migration:
 * {
 *   "employee_debt": { "id": int, "name": string } | null,
 *   "payroll_deduction": { "id": int, "name": string, "period": string } | null,
 *   "change_log": { ... }
 * }
 */
final readonly class EmployeeDebtInstallmentDetailInfo
{
    public function __construct(
        public ?array $employee_debt = null,
        public ?array $payroll_deduction = null,
    ) {}

    public function toArray(): array
    {
        return [
            'employee_debt' => $this->employee_debt,
            'payroll_deduction' => $this->payroll_deduction,
        ];
    }

    public function getEmployeeDebt(): ?array
    {
        return $this->employee_debt;
    }

    public function getPayrollDeduction(): ?array
    {
        return $this->payroll_deduction;
    }
}
