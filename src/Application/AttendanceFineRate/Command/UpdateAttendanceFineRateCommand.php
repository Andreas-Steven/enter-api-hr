<?php

declare(strict_types=1);

namespace App\Application\AttendanceFineRate\Command;

final readonly class UpdateAttendanceFineRateCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $incidentType,
        public ?int $deductionTypeId,
        public ?int $amount,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?string $incidentType = null,
        ?int $deductionTypeId = null,
        ?int $amount = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            incidentType: $incidentType,
            deductionTypeId: $deductionTypeId,
            amount: $amount,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}
