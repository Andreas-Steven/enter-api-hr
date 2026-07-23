<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebt\Dto;

use App\Domain\EmployeeDebt\Entity\EmployeeDebt;

final readonly class EmployeeDebtResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public ?float $amount,
        public ?string $date,
        public ?string $note,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(EmployeeDebt $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            amount: $entity->getAmount(),
            date: $entity->getDate(),
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