<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\AttendanceSummary;

// Domain Layer
use App\Domain\AttendanceSummary\Entity\AttendanceSummary;
// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbAttendanceSummarySchema
{
    public static function toArray(AttendanceSummary $entity): array
    {
        return [
            'name' => $entity->getName(),
            'period' => $entity->getPeriod(),
            'employee' => $entity->getEmployee()->toArray(),
            'work_schedule' => $entity->getWorkSchedule()->toArray(),
            'attendance' => $entity->getAttendance()->toArray(),
            'permissions' => $entity->getPermissions()->toArray(),
            'summary' => $entity->getSummary()->toArray(),
            'policy' => $entity->getPolicy()->toArray(),
            'notes' => $entity->getNotes(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}
