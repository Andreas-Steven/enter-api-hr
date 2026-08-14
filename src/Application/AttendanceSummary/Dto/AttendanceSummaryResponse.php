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
        public int $status,
        public array $employee,
        public array $work_schedule,
        public array $attendance,
        public array $permissions,
        public array $summary,
        public array $policy,
        public array $detail_info,
        public ?string $notes,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(AttendanceSummary $entity): self
    {
        $data = AttendanceSummaryResponseMapper::fromEntity($entity);

        return new self(
            id: $data['id'],
            name: $data['name'],
            period: $data['period'],
            status: $data['status'],
            employee: $data['employee'],
            work_schedule: $data['work_schedule'],
            attendance: $data['attendance'],
            permissions: $data['permissions'],
            summary: $data['summary'],
            policy: $data['policy'],
            detail_info: $data['detail_info'],
            notes: $data['notes'],
            sync_mdb: $data['sync_mdb'],
            lock_version: $data['lock_version'],
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
