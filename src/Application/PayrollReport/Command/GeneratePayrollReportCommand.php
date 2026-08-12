<?php

declare(strict_types=1);

namespace App\Application\PayrollReport\Command;

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
