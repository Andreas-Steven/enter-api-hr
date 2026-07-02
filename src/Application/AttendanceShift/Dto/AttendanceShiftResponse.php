<?php

declare(strict_types=1);

namespace App\Application\AttendanceShift\Dto;

use App\Domain\AttendanceShift\Entity\AttendanceShift;

final readonly class AttendanceShiftResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $start_time,
        public string $end_time,
        public int $break_minutes,
        public int $default_work_time,
        public int $cross_day,
        public ?string $note,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(AttendanceShift $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            start_time: $entity->getStartTime(),
            end_time: $entity->getEndTime(),
            break_minutes: $entity->getBreakMinutes(),
            default_work_time: $entity->getDefaultWorkTime()->value(),
            cross_day: $entity->getCrossDay(),
            note: $entity->getNote(),
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