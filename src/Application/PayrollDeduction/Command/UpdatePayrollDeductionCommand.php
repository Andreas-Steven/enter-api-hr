<?php

declare(strict_types=1);

namespace App\Application\PayrollDeduction\Command;

final readonly class UpdatePayrollDeductionCommand
{
    public function __construct(
        public int $id,
        public ?int $employeeId,
        public ?int $deductionTypeId,
        public ?int $employeeDebtId,
        public ?int $period,
        public ?int $amount,
        public ?string $description,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?int $employeeId = null,
        ?int $deductionTypeId = null,
        ?int $employeeDebtId = null,
        ?int $period = null,
        ?int $amount = null,
        ?string $description = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            employeeId: $employeeId,
            deductionTypeId: $deductionTypeId,
            employeeDebtId: $employeeDebtId,
            period: $period,
            amount: $amount,
            description: $description,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}
