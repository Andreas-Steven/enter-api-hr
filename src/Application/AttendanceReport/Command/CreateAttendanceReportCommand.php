<?php

declare(strict_types=1);

namespace App\Application\AttendanceReport\Command;

final readonly class CreateAttendanceReportCommand
{
    public function __construct(
        public int $period,
    ) {}

    public static function create(
        int $period,
    ): self {
        return new self(
            period: $period,
        );
    }
}