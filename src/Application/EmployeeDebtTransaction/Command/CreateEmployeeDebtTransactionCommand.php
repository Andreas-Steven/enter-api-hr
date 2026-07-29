<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebtTransaction\Command;

final readonly class CreateEmployeeDebtTransactionCommand
{
    public function __construct(
        public int $employeeDebtId,
        public ?int $employeeDebtInstallmentId,
        public ?int $payrollDeductionId,
        public string $type,
        public float $amount,
        public string $transactionDate,
        public ?string $note,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        int $employeeDebtId,
        string $type,
        float $amount,
        string $transactionDate,
        int $status,
        ?int $employeeDebtInstallmentId = null,
        ?int $payrollDeductionId = null,
        ?string $note = null,
        ?array $detailInfo = null,
    ): self {
        return new self(
            employeeDebtId: $employeeDebtId,
            employeeDebtInstallmentId: $employeeDebtInstallmentId,
            payrollDeductionId: $payrollDeductionId,
            type: $type,
            amount: $amount,
            transactionDate: $transactionDate,
            note: $note,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}