<?php

declare(strict_types=1);

namespace App\Application\AttendanceShift\Command;

final readonly class CreateAttendanceShiftCommand
{
    public function __construct(
        public string $name,
        public string $startTime,
        public string $endTime,
        public ?string $note,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $name,
        string $startTime,
        string $endTime,
        ?string $note,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            startTime: $startTime,
            endTime: $endTime,
            note: $note,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}