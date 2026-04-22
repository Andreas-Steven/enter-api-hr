<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

final class AttendanceStatus
{
    // Status Codes
    public const BLACKLIST = 'blacklist';
    public const WORK_HOLIDAY = 'work_holiday';
    public const LATE = 'late';
    public const LEAVE_EARLY = 'leave_early';
    public const ABSENT = 'absent';
    public const PRESENT = 'present';
    public const MACHINE_STATUS = 'machine_status';
    public const MISSED_CHECKIN = 'missed_checkin';

    // Config Constants (Business Rules)
    public const TIME_NOON = '12:00:00';
    public const TIME_AFTERNOON = '15:00:00';
    public const DEVICE_APP = 'APP';
}