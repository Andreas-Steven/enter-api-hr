<?php

declare(strict_types=1);

namespace App\Application\AttendanceSummary\Command;

final readonly class CreateAttendanceSummaryCommand
{
    public function __construct(
        public string $name,
        public int $employeeId,
        public int $branchId,
        public int $divisionId,
        public int $positionId,
        public int $employeeTypeId,
        public int $status,
        public ?int $workPatternId = null,
        public ?int $period = null,
        public ?array $attendanceDetail = null,
        public ?array $periodSummary = null,
        public ?array $timeSummary = null,
        public ?string $notes = null,
        public ?array $detailInfo = null,
    ) {}

    public static function create(
        string $name,
        int $employeeId,
        int $branchId,
        int $divisionId,
        int $positionId,
        int $employeeTypeId,
        int $status,
        ?int $workPatternId = null,
        ?int $period = null,
        ?array $attendanceDetail = null,
        ?array $periodSummary = null,
        ?array $timeSummary = null,
        ?string $notes = null,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            employeeId: $employeeId,
            branchId: $branchId,
            divisionId: $divisionId,
            positionId: $positionId,
            employeeTypeId: $employeeTypeId,
            status: $status,
            workPatternId: $workPatternId,
            period: $period,
            attendanceDetail: $attendanceDetail,
            periodSummary: $periodSummary,
            timeSummary: $timeSummary,
            notes: $notes,
            detailInfo: $detailInfo,
        );
    }
}