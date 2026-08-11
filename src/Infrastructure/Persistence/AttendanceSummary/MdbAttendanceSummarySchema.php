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
            'employee_id' => $entity->getEmployeeId(),
            'branch_id' => $entity->getBranchId(),
            'division_id' => $entity->getDivisionId(),
            'position_id' => $entity->getPositionId(),
            'employee_type_id' => $entity->getEmployeeTypeId(),
            'work_pattern_id' => $entity->getWorkPatternId(),
            'period' => $entity->getPeriod(),
            'attendance_detail' => $entity->getAttendanceDetail(),
            'period_summary' => $entity->getPeriodSummary(),
            'time_summary' => $entity->getTimeSummary(),
            'notes' => $entity->getNotes(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}