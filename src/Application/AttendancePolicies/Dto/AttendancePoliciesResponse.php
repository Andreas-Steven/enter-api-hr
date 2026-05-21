<?php

declare(strict_types=1);

namespace App\Application\AttendancePolicies\Dto;

use App\Domain\AttendancePolicies\Entity\AttendancePolicies;

final readonly class AttendancePoliciesResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public array $policy_detail,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(AttendancePolicies $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            policy_detail: $entity->getPolicyDetail()->toArray(),
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