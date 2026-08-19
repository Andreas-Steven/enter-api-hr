<?php

declare(strict_types=1);

namespace App\Application\PayrollReport\Command;

final readonly class CreatePayrollReportCommand
{
    public function __construct(
        public string $name,
        public int $status,
        public ?int $period = null,
        public ?array $employee = null,
        public ?array $attendanceSummary = null,
        public ?array $payrollSummary = null,
        public ?string $notes = null,
        public ?array $detailInfo = null,
    ) {}

    public static function create(
        string $name,
        int $status,
        ?int $period = null,
        ?array $employee = null,
        ?array $attendanceSummary = null,
        ?array $payrollSummary = null,
        ?string $notes = null,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            status: $status,
            period: $period,
            employee: $employee,
            attendanceSummary: $attendanceSummary,
            payrollSummary: $payrollSummary,
            notes: $notes,
            detailInfo: $detailInfo,
        );
    }
}
