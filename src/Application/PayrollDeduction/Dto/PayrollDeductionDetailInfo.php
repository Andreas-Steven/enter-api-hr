<?php

declare(strict_types=1);

namespace App\Application\PayrollDeduction\Dto;

/**
 * DTO shape untuk kolom detail_info JSON pada payroll_deduction.
 *
 * Struktur konsisten dengan default migration:
 * {
 *   "employee": { "id": int, "name": string } | null,
 *   "payroll_deduction_type": { "id": int, "name": string, "category": string, "creates_debt": int } | null,
 *   "employee_debt": { "id": int, "name": string } | null,
 *   "change_log": { ... }
 * }
 */
final readonly class PayrollDeductionDetailInfo
{
    public function __construct(
        public ?array $employee = null,
        public ?array $payroll_deduction_type = null,
        public ?array $employee_debt = null,
    ) {}

    public function toArray(): array
    {
        return [
            'employee' => $this->employee,
            'payroll_deduction_type' => $this->payroll_deduction_type,
            'employee_debt' => $this->employee_debt,
        ];
    }

    public function getEmployee(): ?array
    {
        return $this->employee;
    }

    public function getPayrollDeductionType(): ?array
    {
        return $this->payroll_deduction_type;
    }

    public function getEmployeeDebt(): ?array
    {
        return $this->employee_debt;
    }
}
