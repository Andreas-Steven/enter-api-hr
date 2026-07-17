<?php

declare(strict_types=1);

namespace App\Application\DeductionType\Dto;

use App\Domain\DeductionType\Entity\DeductionType;

final readonly class DeductionTypeResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $category,
        public int $createsLoan,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(DeductionType $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            category: $entity->getCategory(),
            createsLoan: $entity->getCreatesLoan(),
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