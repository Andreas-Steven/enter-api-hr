<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebtInstallment\Command;

final readonly class UpdateEmployeeDebtInstallmentCommand
{
    public function __construct(
        public int $id,
        public ?int $employeeDebtId,
        public ?int $payrollDeductionId,
        public ?int $sequence,
        public ?string $dueDate,
        public ?float $amount,
        public ?int $paymentStatus,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?int $employeeDebtId = null,
        ?int $payrollDeductionId = null,
        ?int $sequence = null,
        ?string $dueDate = null,
        ?float $amount = null,
        ?int $paymentStatus = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            employeeDebtId: $employeeDebtId,
            payrollDeductionId: $payrollDeductionId,
            sequence: $sequence,
            dueDate: $dueDate,
            amount: $amount,
            paymentStatus: $paymentStatus,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}