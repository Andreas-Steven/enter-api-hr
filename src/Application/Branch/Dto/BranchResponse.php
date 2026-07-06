<?php

declare(strict_types=1);

namespace App\Application\Branch\Dto;

use App\Domain\Branch\Entity\Branch;

final readonly class BranchResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $code,
        public string $address,
        public ?int $attendance_policies_id,
        public ?int $work_pattern_id,
        public int $status,
        public ?int $branch_type,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(Branch $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            code: $entity->getCode(),
            address: $entity->getAddress(),
            attendance_policies_id: $entity->getAttendancePoliciesId(),
            work_pattern_id: $entity->getWorkPatternId(),
            status: $entity->getStatus()->value(),
            branch_type: $entity->getType(),
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