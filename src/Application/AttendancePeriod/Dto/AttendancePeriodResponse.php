<?php

declare(strict_types=1);

namespace App\Application\AttendancePeriod\Dto;

use App\Domain\AttendancePeriod\Entity\AttendancePeriod;

final readonly class AttendancePeriodResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int $period,
        public ?string $start_date,
        public ?string $end_date,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(AttendancePeriod $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            period: $entity->getPeriod(),
            start_date: $entity->getStartDate()?->toString(),
            end_date: $entity->getEndDate()?->toString(),
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