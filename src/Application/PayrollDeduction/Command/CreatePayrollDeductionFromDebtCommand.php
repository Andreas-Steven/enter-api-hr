<?php

declare(strict_types=1);

namespace App\Application\PayrollDeduction\Command;

final readonly class CreatePayrollDeductionFromDebtCommand
{
    public function __construct(
        public int $employeeDebtId,
        public int $period,
        public float $amount,
        public ?string $description,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        int $employeeDebtId,
        int $period,
        float $amount,
        int $status,
        ?string $description = null,
        ?array $detailInfo = null,
    ): self {
        return new self(
            employeeDebtId: $employeeDebtId,
            period: $period,
            amount: $amount,
            description: $description,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}
