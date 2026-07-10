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
            attendance: array_map(
                static fn(array $item): array => self::sanitizeAttendanceItem($item),
                $entity->getAttendance()
            ),
            status: $entity->getStatus()->value(),
            detail_info: $entity->getDetailInfo()->toArray(),
            sync_mdb: $entity->getSyncMdbValue(),
            lock_version: $entity->getLockVersion()->value(),
        );
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private static function sanitizeAttendanceItem(array $item): array
    {
        unset($item['attendance_policies']);

        return $item;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}