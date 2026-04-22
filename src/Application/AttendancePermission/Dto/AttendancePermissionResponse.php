<?php

declare(strict_types=1);

namespace App\Application\AttendancePermission\Dto;

use App\Domain\AttendancePermission\Entity\AttendancePermission;

final readonly class AttendancePermissionResponse
{
    public function __construct(
        public int $id,
        // public string $name,
        public int $employeeId,
        public int $permissionType,
        public string $date,
        public string $note,
        public ?int $approved,
        public ?string $approvedNote,
        public ?array $revision,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(AttendancePermission $entity): self
    {
        return new self(
            id: $entity->getId(),
            // name: $entity->getName(),
            employeeId: $entity->getEmployeeId(),
            permissionType: $entity->getPermissionType(),
            date: $entity->getDate(),
            note: $entity->getNote(),
            approved: $entity->getApproved(),
            approvedNote: $entity->getApprovedNote(),
            revision: $entity->getRevision()?->toArray() ?? [],
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