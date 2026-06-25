<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\AttendanceCalendar;

// Domain Layer
use App\Domain\AttendanceCalendar\Entity\AttendanceCalendar;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbAttendanceCalendarSchema
{
    public static function toArray(AttendanceCalendar $entity): array
    {
        return [
            'name' => $entity->getName(),
            'period' => $entity->getPeriod()->toInt(),
            'date' => $entity->getDate(),
            'holiday_type' => $entity->getHolidayType(),
            'scope' => $entity->getScope()->toArray(),
            'day_off' => $entity->getDayOff(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}