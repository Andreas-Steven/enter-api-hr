<?php

declare(strict_types=1);

namespace App\Application\PayrollDeduction\Command;

final readonly class CreatePayrollDeductionCommand
{
    public function __construct(
        public int $employeeId,
        public int $deductionTypeId,
        public ?int $employeeDebtId,
        public int $period,
        public int $amount,
        public ?string $description,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        int $employeeId,
        int $deductionTypeId,
        int $period,
        int $amount,
        int $status,
        ?int $employeeDebtId = null,
        ?string $description = null,
        ?array $detailInfo = null,
    ): self {
        return new self(
            employeeId: $employeeId,
            deductionTypeId: $deductionTypeId,
            employeeDebtId: $employeeDebtId,
            period: $period,
            amount: $amount,
            description: $description,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}
