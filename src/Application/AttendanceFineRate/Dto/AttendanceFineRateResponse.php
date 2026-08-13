<?php

declare(strict_types=1);

namespace App\Application\AttendanceFineRate\Dto;

use App\Domain\AttendanceFineRate\Entity\AttendanceFineRate;

final readonly class AttendanceFineRateResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $incident_type,
        public int $deduction_type_id,
        public int $amount,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(AttendanceFineRate $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            incident_type: $entity->getIncidentType()->value,
            deduction_type_id: $entity->getDeductionTypeId(),
            amount: $entity->getAmount(),
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
