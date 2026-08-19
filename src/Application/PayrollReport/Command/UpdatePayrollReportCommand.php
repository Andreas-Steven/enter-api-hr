<?php

declare(strict_types=1);

namespace App\Application\PayrollReport\Command;

final readonly class UpdatePayrollReportCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?int $status,
        public ?array $employee,
        public ?array $attendanceSummary,
        public ?array $payrollSummary,
        public ?string $notes,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?int $status = null,
        ?array $employee = null,
        ?array $attendanceSummary = null,
        ?array $payrollSummary = null,
        ?string $notes = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            status: $status,
            employee: $employee,
            attendanceSummary: $attendanceSummary,
            payrollSummary: $payrollSummary,
            notes: $notes,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}
