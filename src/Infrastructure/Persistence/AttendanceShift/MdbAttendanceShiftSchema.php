<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\AttendanceShift;

// Domain Layer
use App\Domain\AttendanceShift\Entity\AttendanceShift;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbAttendanceShiftSchema
{
    public static function toArray(AttendanceShift $entity): array
    {
        return [
            'name' => $entity->getName(),
            'start_time' => $entity->getStartTime(),
            'end_time' => $entity->getEndTime(),
            'note' => $entity->getNote(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}