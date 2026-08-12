<?php

declare(strict_types=1);

namespace App\Application\PayrollReport\Command;

final readonly class UpdatePayrollReportCommand
{
    public function __construct(
        public int $id,
        public ?string $name = null,
        public ?int $attendanceSummaryId = null,
        public ?array $payrollSummary = null,
        public ?string $notes = null,
        public ?int $status = null,
        public ?array $detailInfo = null,
        public ?int $lockVersion = null,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?int $attendanceSummaryId = null,
        ?array $payrollSummary = null,
        ?string $notes = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            attendanceSummaryId: $attendanceSummaryId,
            payrollSummary: $payrollSummary,
            notes: $notes,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}