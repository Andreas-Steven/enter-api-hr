<?php

declare(strict_types=1);

namespace App\Application\AttendanceShift\Command;

final readonly class UpdateAttendanceShiftCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $startTime,
        public ?string $endTime,
        public ?int $breakMinutes,
        public ?int $crossDay,
        public ?string $note,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?string $startTime = null,
        ?string $endTime = null,
        ?int $breakMinutes = null,
        ?int $crossDay = null,
        ?string $note = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            startTime: $startTime,
            endTime: $endTime,
            breakMinutes: $breakMinutes,
            crossDay: $crossDay,
            note: $note,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}