<?php

declare(strict_types=1);

namespace App\Application\AttendanceCalendar\Command;

final readonly class UpdateAttendanceCalendarCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $date,
        public ?string $holidayType,
        public ?array $scope,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
        public ?string $note,
        public ?int $dayOff,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?string $date = null,
        ?string $holidayType = null,
        ?array $scope = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
        ?string $note = null,
        ?int $dayOff = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            date: $date,
            holidayType: $holidayType,
            scope: $scope,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
            note: $note,
            dayOff: $dayOff,
        );
    }
}