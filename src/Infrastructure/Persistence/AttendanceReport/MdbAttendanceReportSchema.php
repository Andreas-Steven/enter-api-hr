<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\AttendanceReport;

// Domain Layer
use App\Domain\AttendanceReport\Entity\AttendanceReport;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbAttendanceReportSchema
{
    public static function toArray(AttendanceReport $entity): array
    {
        return [
            'name' => $entity->getName(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}