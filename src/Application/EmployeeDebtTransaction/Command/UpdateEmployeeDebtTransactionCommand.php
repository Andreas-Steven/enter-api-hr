<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebtTransaction\Command;

final readonly class UpdateEmployeeDebtTransactionCommand
{
    public function __construct(
        public int $id,
        public ?int $employeeDebtId,
        public ?int $employeeId,
        public ?string $transactionType,
        public ?string $date,
        public ?int $amount,
        public ?string $paymentSource,
        public ?int $payrollDeductionId,
        public ?int $payrollPeriod,
        public ?string $note,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?int $employeeDebtId = null,
        ?int $employeeId = null,
        ?string $transactionType = null,
        ?string $date = null,
        ?int $amount = null,
        ?string $paymentSource = null,
        ?int $payrollDeductionId = null,
        ?int $payrollPeriod = null,
        ?string $note = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            employeeDebtId: $employeeDebtId,
            employeeId: $employeeId,
            transactionType: $transactionType,
            date: $date,
            amount: $amount,
            paymentSource: $paymentSource,
            payrollDeductionId: $payrollDeductionId,
            payrollPeriod: $payrollPeriod,
            note: $note,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}
