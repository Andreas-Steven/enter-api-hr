<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EmployeeLeave;

// Domain Layer
use App\Domain\EmployeeLeave\Entity\EmployeeLeave;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbEmployeeLeaveSchema
{
    public static function toArray(EmployeeLeave $entity): array
    {
        return [
            'name' => $entity->getName(),
            'leave_type' => $entity->getLeaveType(),
            'employee_id' => $entity->getEmployeeId(),
            'period_start_date' => $entity->getPeriodStartDate(),
            'period_end_date' => $entity->getPeriodEndDate(),
            'initial_amount' => $entity->getInitialAmount(),
            'adjustment_amount' => $entity->getAdjustmentAmount(),
            'used_amount' => $entity->getUsedAmount(),
            'leave_records' => $entity->getLeaveRecords(),
            'adjustment_records' => $entity->getAdjustmentRecords(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}