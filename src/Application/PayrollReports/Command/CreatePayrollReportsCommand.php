<?php

declare(strict_types=1);

namespace App\Application\PayrollReports\Command;

final readonly class CreatePayrollReportsCommand
{
    public function __construct(
        public string $name,
        public int $attendanceSummaryId,
        public int $status,
        public ?array $payrollSummary = null,
        public ?string $notes = null,
        public ?array $detailInfo = null,
    ) {}

    public static function create(
        string $name,
        int $attendanceSummaryId,
        int $status,
        ?array $payrollSummary = null,
        ?string $notes = null,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            attendanceSummaryId: $attendanceSummaryId,
            status: $status,
            payrollSummary: $payrollSummary,
            notes: $notes,
            detailInfo: $detailInfo,
        );
    }
}