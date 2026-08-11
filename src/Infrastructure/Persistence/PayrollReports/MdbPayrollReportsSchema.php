<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PayrollReports;

// Domain Layer
use App\Domain\PayrollReports\Entity\PayrollReports;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbPayrollReportsSchema
{
    public static function toArray(PayrollReports $entity): array
    {
        return [
            'name' => $entity->getName(),
            'attendance_summary_id' => $entity->getAttendanceSummaryId(),
            'payroll_summary' => $entity->getPayrollSummary(),
            'notes' => $entity->getNotes(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}