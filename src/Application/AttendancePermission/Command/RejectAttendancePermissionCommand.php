<?php

declare(strict_types=1);

namespace App\Application\AttendancePermission\Command;

final readonly class RejectAttendancePermissionCommand
{
    public function __construct(
        public int $id,
        public ?string $approvedNote,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?string $approvedNote = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            approvedNote: $approvedNote,
            lockVersion: $lockVersion,
        );
    }
}