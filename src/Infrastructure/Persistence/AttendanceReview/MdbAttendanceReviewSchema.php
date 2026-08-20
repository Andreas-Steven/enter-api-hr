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
            'period' => $entity->getPeriod(),
            'employee' => $entity->getEmployee()->toArray(),
            'attendance_summary' => $entity->getAttendanceSummary()->toArray(),
            'items' => $entity->getItems()->toArray(),
            'counts' => $entity->getCounts()->toArray(),
            'review_status' => $entity->getReviewStatus()->value,
            'notes' => $entity->getNotes(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}
