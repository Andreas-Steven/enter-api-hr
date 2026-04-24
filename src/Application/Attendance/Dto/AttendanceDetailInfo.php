<?php

declare(strict_types=1);

namespace App\Application\Attendance\Dto;

final readonly class AttendanceDetailInfo
{
    public function __construct(
        public ?bool $dayoff,
        public ?array $blacklist,
    ) {}

    public function toArray(): array
    {
        return [
            'day_off' => $this->dayoff,
            'blacklist' => $this->blacklist,
        ];
    }

    public function getDayoff(): ?bool
    {
        return $this->dayoff;
    }

    public function getBlacklist(): ?array
    {
        return $this->blacklist;
    }
}