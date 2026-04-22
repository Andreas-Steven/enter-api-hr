<?php

declare(strict_types=1);

namespace App\Application\AttendancePermission\Command;

final readonly class CreateAttendancePermissionCommand
{
    public function __construct(
        // public string $name,
        public int $employeeId,
        public int $permissionType,
        public string $date,
        public string $note,
        public array $revision,
        public ?bool $approved,
        public ?string $approvedNote,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        // string $name,
        int $employeeId,
        int $permissionType,
        string $date,
        string $note,
        int $status,
        array $revision,
        ?bool $approved = null,
        ?string $approvedNote = null,
        ?array $detailInfo = null,
    ): self {
        return new self(
            // name: $name,
            employeeId: $employeeId,
            permissionType: $permissionType,
            date: $date,
            note: $note,
            revision: $revision,
            approved: $approved,
            approvedNote: $approvedNote,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}