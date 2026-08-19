<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PayrollReport;

// Domain Layer
use App\Domain\PayrollReport\Entity\PayrollReport;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbPayrollReportSchema
{
    public static function toArray(PayrollReport $entity): array
    {
        return [
            'name' => $entity->getName(),
            'period' => $entity->getPeriod(),
            'employee' => $entity->getEmployee()->toArray(),
            'attendance_summary' => $entity->getAttendanceSummary()->toArray(),
            'payroll_summary' => $entity->getPayrollSummary()->toArray(),
            'notes' => $entity->getNotes(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}
