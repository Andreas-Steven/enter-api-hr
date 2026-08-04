<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebt\Command;

final readonly class UpdateEmployeeDebtCommand
{
    public function __construct(
        public int $id,
        public ?int $employeeId,
        public ?int $deductionTypeId,
        public ?float $amount,
        public ?string $date,
        public ?string $note,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?int $employeeId = null,
        ?int $deductionTypeId = null,
        ?float $amount = null,
        ?string $date = null,
        ?string $note = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            employeeId: $employeeId,
            deductionTypeId: $deductionTypeId,
            amount: $amount,
            date: $date,
            note: $note,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}
