<?php

declare(strict_types=1);

namespace App\Application\Attendance\Command;

final readonly class CreateAttendanceCommand
{
    public function __construct(
        public string $date,
        public array $attendance,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $date,
        array $attendance,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            date: $date,
            attendance: $attendance,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}