<?php

declare(strict_types=1);

namespace App\Application\WorkPattern\Dto;

use App\Domain\WorkPattern\Entity\WorkPattern;

final readonly class WorkPatternResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int $status,
        public array $working_days,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(WorkPattern $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            status: $entity->getStatus()->value(),
            working_days: $entity->getWorkingDays()->toArray(),
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