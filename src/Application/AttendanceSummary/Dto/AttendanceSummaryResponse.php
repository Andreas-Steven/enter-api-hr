<?php

declare(strict_types=1);

namespace App\Application\AttendanceSummary\Dto;

use App\Domain\AttendanceSummary\Entity\AttendanceSummary;

final readonly class AttendanceSummaryResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public ?int $period,
        public array $employee,
        public array $work_schedule,
        public array $attendance,
        public array $permissions,
        public array $summary,
        public array $policy,
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
            period: $entity->getPeriod(),
            employee: $entity->getEmployee()->toArray(),
            work_schedule: $entity->getWorkSchedule()->toArray(),
            attendance: $entity->getAttendance()->toArray(),
            permissions: $entity->getPermissions()->toArray(),
            summary: $entity->getSummary()->toArray(),
            policy: $entity->getPolicy()->toArray(),
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
