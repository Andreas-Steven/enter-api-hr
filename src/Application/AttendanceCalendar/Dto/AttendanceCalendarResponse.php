<?php

declare(strict_types=1);

namespace App\Application\AttendanceCalendar\Dto;

use App\Domain\AttendanceCalendar\Entity\AttendanceCalendar;

final readonly class AttendanceCalendarResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $date,
        public int $period,
        public string $holiday_type,
        public array $scope,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(AttendanceCalendar $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            date: $entity->getDate(),
            period: $entity->getPeriod()->toInt(),
            holiday_type: $entity->getHolidayType(),
            scope: $entity->getScope()->toArray(),
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