<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\AttendanceReview;

// Domain Layer
use App\Domain\AttendanceReview\Entity\AttendanceReview;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbAttendanceReviewSchema
{
    public static function toArray(AttendanceReview $entity): array
    {
        return [
            'name' => $entity->getName(),
            'employee_id' => $entity->getEmployeeId(),
            'period' => $entity->getPeriod(),
            'attendance_summary_id' => $entity->getAttendanceSummaryId(),
            'source_type' => $entity->getSourceType()->value,
            'source_ref' => $entity->getSourceRef()->toArray(),
            'stable_key' => $entity->getStableKey(),
            'review_reason' => $entity->getReviewReason()->value,
            'source_treatment' => $entity->getSourceTreatment(),
            'resolved_treatment' => $entity->getResolvedTreatment(),
            'approval_status' => $entity->getApprovalStatus()->value,
            'approved_by' => $entity->getApprovedBy(),
            'approved_note' => $entity->getApprovedNote(),
            'approved_at' => $entity->getApprovedAt(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}
