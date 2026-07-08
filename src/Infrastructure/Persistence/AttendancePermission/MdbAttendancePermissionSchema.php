<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\AttendancePermission;

// Domain Layer
use App\Domain\AttendancePermission\Entity\AttendancePermission;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbAttendancePermissionSchema
{
    public static function toArray(AttendancePermission $entity): array
    {
        return [
            // 'name' => $entity->getName(),
            'employee_id' => $entity->getEmployeeId(),
            'permission_type' => $entity->getPermissionType(),
            'start_date' => $entity->getStartDate(),
            'end_date' => $entity->getEndDate(),
            'note' => $entity->getNote(),
            'approved' => $entity->getApproved(),
            'approved_note' => $entity->getApprovedNote(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'period' => $entity->getPeriod(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}