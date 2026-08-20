<?php

declare(strict_types=1);

namespace App\Application\GeneralAffair\Dto;

use App\Domain\GeneralAffair\Entity\GeneralAffair;

final readonly class GeneralAffairResponse
{
    public function __construct(
        public int $id,
        /* public string $name, */
        public int $productId,
        public int $categoryId,
        public int $locationId,
        public ?array $asset,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(GeneralAffair $entity): self
    {
        return new self(
            id: $entity->getId(),
            /* name: $entity->getName(), */
            productId: $entity->getProductId(),
            categoryId: $entity->getCategoryId(),
            locationId: $entity->getLocationId(),
            asset: $entity->getAsset()->toArray(),
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