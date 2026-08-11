<?php

declare(strict_types=1);

namespace App\Application\AttendanceSummary\Dto;

use App\Domain\AttendanceSummary\Entity\AttendanceSummary;

final readonly class AttendanceSummaryResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int $employee_id,
        public int $branch_id,
        public int $division_id,
        public int $position_id,
        public int $employee_type_id,
        public ?int $work_pattern_id,
        public ?int $period,
        public array $attendance_detail,
        public array $period_summary,
        public array $time_summary,
        public ?string $notes,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(AttendanceSummary $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            employee_id: $entity->getEmployeeId(),
            branch_id: $entity->getBranchId(),
            division_id: $entity->getDivisionId(),
            position_id: $entity->getPositionId(),
            employee_type_id: $entity->getEmployeeTypeId(),
            work_pattern_id: $entity->getWorkPatternId(),
            period: $entity->getPeriod(),
            attendance_detail: $entity->getAttendanceDetail(),
            period_summary: $entity->getPeriodSummary(),
            time_summary: $entity->getTimeSummary(),
            notes: $entity->getNotes(),
            status: $entity->getStatus()->value(),
            detail_info: $entity->getDetailInfo()->toArray(),
            sync_mdb: $entity->getSyncMdbValue(),
            lock_version: $entity->getLockVersion()->value(),
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}