<?php

declare(strict_types=1);

namespace App\Application\AttendancePermission\Command;

final readonly class RejectAttendancePermissionCommand
{
    public function __construct(
        public int $id,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            lockVersion: $lockVersion,
        );
    }
}