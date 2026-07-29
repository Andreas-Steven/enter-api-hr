<?php

declare(strict_types=1);

namespace App\Application\PayrollDeductionType\Dto;

use App\Domain\PayrollDeductionType\Entity\PayrollDeductionType;

final readonly class PayrollDeductionTypeResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $category,
        public int $creates_debt,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(PayrollDeductionType $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            category: $entity->getCategory(),
            creates_debt: $entity->getCreatesDebt(),
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