<?php

declare(strict_types=1);

namespace App\Application\Attendance\Dto;

use App\Domain\Attendance\Entity\Attendance;

final readonly class AttendanceResponse
{
    public function __construct(
        public int $id,
        public string $date,
        public int $period,
        public array $attendance,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(Attendance $entity): self
    {
        return new self(
            id: $entity->getId(),
            date: $entity->getDate(),
            period: $entity->getPeriod(),
            attendance: $entity->getAttendance(),
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