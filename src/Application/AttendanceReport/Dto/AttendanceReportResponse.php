<?php

declare(strict_types=1);

namespace App\Application\AttendanceReport\Dto;

use App\Domain\AttendanceReport\Entity\AttendanceReport;

final readonly class AttendanceReportResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int $period,
        public int $employee_id,
        public int $branch_id,
        public int $division_id,
        public int $position_id,
        public int $employee_type_id,
        public int $work_pattern_id,
        public array $summary,
        public string $report_type,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(AttendanceReport $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            period: $entity->getPeriod(),
            employee_id: $entity->getEmployeeId(),
            branch_id: $entity->getBranchId(),
            division_id: $entity->getDivisionId(),
            position_id: $entity->getPositionId(),
            employee_type_id: $entity->getEmployeeTypeId(),
            work_pattern_id: $entity->getWorkPatternId(),
            summary: $entity->getSummary()->toArray(),
            report_type: $entity->getReportType(),
            status: $entity->getStatus()->value(),
            detail_info: AttendanceReportDetailInfo::fromArray(
                $entity->getDetailInfo()->toArray()
            )->toArray(),
            sync_mdb: $entity->getSyncMdbValue(),
            lock_version: $entity->getLockVersion()->value(),
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}