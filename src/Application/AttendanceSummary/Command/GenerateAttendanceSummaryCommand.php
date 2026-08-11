<?php

declare(strict_types=1);

namespace App\Application\AttendanceSummary\Command;

final readonly class GenerateAttendanceSummaryCommand
{
    public function __construct(
        public int $period,
        public int $status,
        public ?int $employeeId = null,
        public ?string $notes = null,
        public ?array $detailInfo = null,
    ) {}

    public static function create(
        int $period,
        int $status,
        ?int $employeeId = null,
        ?string $notes = null,
        ?array $detailInfo = null,
    ): self {
        return new self(
            period: $period,
            status: $status,
            employeeId: $employeeId,
            notes: $notes,
            detailInfo: $detailInfo,
        );
    }
}
