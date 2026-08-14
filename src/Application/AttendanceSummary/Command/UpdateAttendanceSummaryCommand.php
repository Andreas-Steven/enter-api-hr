<?php

declare(strict_types=1);

namespace App\Application\AttendanceSummary\Command;

final readonly class UpdateAttendanceSummaryCommand
{
    public function __construct(
        public int $id,
        public ?string $name = null,
        public ?int $employeeId = null,
        public ?int $branchId = null,
        public ?int $divisionId = null,
        public ?int $positionId = null,
        public ?int $employeeTypeId = null,
        public ?int $workPatternId = null,
        public ?int $period = null,
        public ?array $attendanceDetail = null,
        public ?array $permissionDetail = null,
        public ?array $periodSummary = null,
        public ?array $timeSummary = null,
        public ?string $notes = null,
        public ?int $status = null,
        public ?array $detailInfo = null,
        public ?int $lockVersion = null,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?int $employeeId = null,
        ?int $branchId = null,
        ?int $divisionId = null,
        ?int $positionId = null,
        ?int $employeeTypeId = null,
        ?int $workPatternId = null,
        ?int $period = null,
        ?array $attendanceDetail = null,
        ?array $permissionDetail = null,
        ?array $periodSummary = null,
        ?array $timeSummary = null,
        ?string $notes = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            employeeId: $employeeId,
            branchId: $branchId,
            divisionId: $divisionId,
            positionId: $positionId,
            employeeTypeId: $employeeTypeId,
            workPatternId: $workPatternId,
            period: $period,
            attendanceDetail: $attendanceDetail,
            permissionDetail: $permissionDetail,
            periodSummary: $periodSummary,
            timeSummary: $timeSummary,
            notes: $notes,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}