<?php

declare(strict_types=1);

namespace App\Application\AttendanceSummary\Command;

/**
 * Command for the auto-generation use case.
 *
 * The only input that drives generation is the period (YYYYMM). Summaries are
 * generated for every active employee from their attendance records and
 * employee rules. An optional `employeeId` restricts generation to a single
 * employee (used by the console command).
 */
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
