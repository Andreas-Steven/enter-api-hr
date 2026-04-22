<?php

declare(strict_types=1);

namespace App\Application\AttendancePermission\Command;

final readonly class UpdateAttendancePermissionCommand
{
    public function __construct(
        public int $id,
        // public ?string $name,
        public ?int $employeeId,
        public ?int $permissionType,
        public ?string $date,
        public ?string $note,
        public ?bool $approved,
        public ?string $approvedNote,
        public ?array $revision,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        // ?string $name,
        ?int $employeeId = null,
        ?int $permissionType = null,
        ?string $date = null,
        ?string $note = null,
        ?bool $approved = null,
        ?string $approvedNote = null,
        ?array $revision = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            // name: $name,
            employeeId: $employeeId,
            permissionType: $permissionType,
            date: $date,
            note: $note,
            approved: $approved,
            approvedNote: $approvedNote,
            revision: $revision,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}