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
            'period' => $entity->getPeriod(),
            'employee_id' => $entity->getEmployeeId(),
            'branch_id' => $entity->getBranchId(),
            'division_id' => $entity->getDivisionId(),
            'position_id' => $entity->getPositionId(),
            'employee_type_id' => $entity->getEmployeeTypeId(),
            'work_pattern_id' => $entity->getWorkPatternId(),
            'summary' => $entity->getSummary()->toArray(),
            'report_type' => $entity->getReportType(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}