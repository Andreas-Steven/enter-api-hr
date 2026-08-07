<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebtTransaction\Command;

final readonly class CreateEmployeeDebtTransactionCommand
{
    public function __construct(
        public int $employeeDebtId,
        public string $transactionType,
        public string $date,
        public int $amount,
        public int $status,
        public ?string $paymentSource,
        public ?int $payrollDeductionId,
        public ?int $payrollPeriod,
        public ?string $note,
        public ?array $detailInfo,
    ) {}

    public static function create(
        int $employeeDebtId,
        string $transactionType,
        string $date,
        int $amount,
        int $status,
        ?string $paymentSource = null,
        ?int $payrollDeductionId = null,
        ?int $payrollPeriod = null,
        ?string $note = null,
        ?array $detailInfo = null,
    ): self {
        return new self(
            employeeDebtId: $employeeDebtId,
            transactionType: $transactionType,
            date: $date,
            amount: $amount,
            status: $status,
            paymentSource: $paymentSource,
            payrollDeductionId: $payrollDeductionId,
            payrollPeriod: $payrollPeriod,
            note: $note,
            detailInfo: $detailInfo,
        );
    }
}
