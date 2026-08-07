<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebt\Command;

final readonly class CreateEmployeeDebtCommand
{
    public function __construct(
        public int $employeeId,
        public int $deductionTypeId,
        public int $amount,
        public string $date,
        public ?string $note,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        int $employeeId,
        int $deductionTypeId,
        int $amount,
        string $date,
        ?string $note,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            employeeId: $employeeId,
            deductionTypeId: $deductionTypeId,
            amount: $amount,
            date: $date,
            note: $note,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}
