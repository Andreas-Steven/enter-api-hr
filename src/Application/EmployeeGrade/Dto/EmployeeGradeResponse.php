<?php

declare(strict_types=1);

namespace App\Application\EmployeeGrade\Dto;

use App\Domain\EmployeeGrade\Entity\EmployeeGrade;

final readonly class EmployeeGradeResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int $level,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(EmployeeGrade $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            level: $entity->getLevel(),
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