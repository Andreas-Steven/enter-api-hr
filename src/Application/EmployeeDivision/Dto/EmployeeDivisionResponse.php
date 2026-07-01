<?php

declare(strict_types=1);

namespace App\Application\EmployeeDivision\Dto;

use App\Domain\EmployeeDivision\Entity\EmployeeDivision;

final readonly class EmployeeDivisionResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $code,
        public ?int $work_pattern_id,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(EmployeeDivision $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            code: $entity->getCode(),
            work_pattern_id: $entity->getWorkPatternId(),
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