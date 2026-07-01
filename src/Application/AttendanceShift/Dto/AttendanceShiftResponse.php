<?php

declare(strict_types=1);

namespace App\Application\AttendanceShift\Dto;

use App\Domain\AttendanceShift\Entity\AttendanceShift;

final readonly class AttendanceShiftResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $startTime,
        public string $endTime,
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
            startTime: $entity->getStartTime(),
            endTime: $entity->getEndTime(),
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