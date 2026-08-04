<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebtTransaction\Dto;

/**
 * DTO shape untuk kolom detail_info JSON pada employee_debt_transaction.
 *
 * {
 *   "employee": { "id": int, "name": string } | null,
 *   "employee_debt": { "id": int, "name": string, "amount": float, "remaining_amount": float } | null,
 *   "payroll_deduction": { "id": int, "name": string, "period": int, "amount": float } | null,
 *   "change_log": { ... }
 * }
 */
final readonly class EmployeeDebtTransactionDetailInfo
{
    public function __construct(
        public ?array $employee = null,
        public ?array $employee_debt = null,
        public ?array $payroll_deduction = null,
    ) {}

    public function toArray(): array
    {
        return [
            'employee' => $this->employee,
            'employee_debt' => $this->employee_debt,
            'payroll_deduction' => $this->payroll_deduction,
        ];
    }

    public function getEmployee(): ?array
    {
        return $this->employee;
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
