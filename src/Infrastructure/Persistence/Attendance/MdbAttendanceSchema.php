<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Attendance;

// Domain Layer
use App\Domain\Attendance\Entity\Attendance;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbAttendanceSchema
{
    public static function toArray(Attendance $entity): array
    {
        return [
            'date' => $entity->getDate(),
            'period' => $entity->getPeriod(),
            'attendance' => $entity->getAttendance(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}