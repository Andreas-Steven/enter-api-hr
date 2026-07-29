<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebtTransaction\Command;

final readonly class UpdateEmployeeDebtTransactionCommand
{
    public function __construct(
        public int $id,
        public ?int $employeeDebtId,
        public ?int $employeeDebtInstallmentId,
        public ?int $payrollDeductionId,
        public ?string $type,
        public ?float $amount,
        public ?string $transactionDate,
        public ?string $note,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?int $employeeDebtId = null,
        ?int $employeeDebtInstallmentId = null,
        ?int $payrollDeductionId = null,
        ?string $type = null,
        ?float $amount = null,
        ?string $transactionDate = null,
        ?string $note = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            employeeDebtId: $employeeDebtId,
            employeeDebtInstallmentId: $employeeDebtInstallmentId,
            payrollDeductionId: $payrollDeductionId,
            type: $type,
            amount: $amount,
            transactionDate: $transactionDate,
            note: $note,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}