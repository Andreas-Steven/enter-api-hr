<?php

declare(strict_types=1);

namespace App\Application\PayrollReport\Command;

/**
 * Command for the auto-generation use case.
 *
 * The only input that drives generation is the period (YYYYMM). Payroll reports
 * are generated for every active employee from their AttendanceSummary records
 * and the configured AttendanceFineRate deduction rates. An optional
 * `attendanceSummaryId` restricts generation to a single summary (used by
 * the console command / override flow).
 */
final readonly class GeneratePayrollReportCommand
{
    public function __construct(
        public int $period,
        public int $status,
        public ?int $attendanceSummaryId = null,
        public ?string $notes = null,
        public ?array $detailInfo = null,
    ) {}

    public static function create(
        int $period,
        int $status,
        ?int $attendanceSummaryId = null,
        ?string $notes = null,
        ?array $detailInfo = null,
    ): self {
        return new self(
            period: $period,
            status: $status,
            attendanceSummaryId: $attendanceSummaryId,
            notes: $notes,
            detailInfo: $detailInfo,
        );
    }
}
