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
    ) {}

    public static function create(
        string $name,
        string $date,
        string $holidayType,
        array $scope,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            date: $date,
            holidayType: $holidayType,
            scope: $scope,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}