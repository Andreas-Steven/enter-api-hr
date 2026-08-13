<?php

declare(strict_types=1);

namespace App\Application\AttendanceFineRate\Command;

final readonly class CreateAttendanceFineRateCommand
{
    public function __construct(
        public string $name,
        public string $incidentType,
        public int $deductionTypeId,
        public int $amount,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $name,
        string $incidentType,
        int $deductionTypeId,
        int $amount,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            incidentType: $incidentType,
            deductionTypeId: $deductionTypeId,
            amount: $amount,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}
