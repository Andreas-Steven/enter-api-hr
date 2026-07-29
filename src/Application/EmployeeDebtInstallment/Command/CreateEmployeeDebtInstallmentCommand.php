<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebtInstallment\Command;

final readonly class CreateEmployeeDebtInstallmentCommand
{
    public function __construct(
        public int $employeeDebtId,
        public ?int $payrollDeductionId,
        public int $sequence,
        public string $dueDate,
        public float $amount,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        int $employeeDebtId,
        string $dueDate,
        float $amount,
        int $status,
        ?int $payrollDeductionId = null,
        int $sequence = 1,
        ?array $detailInfo = null,
    ): self {
        return new self(
            employeeDebtId: $employeeDebtId,
            payrollDeductionId: $payrollDeductionId,
            sequence: $sequence,
            dueDate: $dueDate,
            amount: $amount,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}