<?php

declare(strict_types=1);

namespace App\Application\AttendanceCalendar\Command;

final readonly class CreateAttendanceCalendarCommand
{
    public function __construct(
        public string $name,
        public string $date,
        public string $holidayType,
        public array $scope,
        public int $status,
        public ?array $detailInfo,
        public ?string $note,
        public ?int $dayOff = 0,
    ) {}

    public static function create(
        string $name,
        string $date,
        string $holidayType,
        array $scope,
        int $status,
        ?array $detailInfo = null,
        ?string $note = null,
        int $dayOff = 0,
    ): self {
        return new self(
            name: $name,
            date: $date,
            holidayType: $holidayType,
            scope: $scope,
            status: $status,
            detailInfo: $detailInfo,
            note: $note,
            dayOff: $dayOff,
        );
    }
}