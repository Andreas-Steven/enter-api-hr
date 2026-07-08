<?php

declare(strict_types=1);

namespace App\Application\AttendancePeriod\Command;

final readonly class CreateAttendancePeriodCommand
{
    public function __construct(
        public int $year,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        int $year,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            year: $year,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}