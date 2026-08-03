<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\AttendancePeriod;

// Domain Layer
use App\Domain\AttendancePeriod\Entity\AttendancePeriod;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbAttendancePeriodSchema
{
    public static function toArray(AttendancePeriod $entity): array
    {
        return [
            'name' => $entity->getName(),
            'period' => (int) $entity->getPeriod(),
            'start_date' => $entity->getStartDate()?->toDateTime(),
            'end_date' => $entity->getEndDate()?->toDateTime(),
            'payout_date' => $entity->getPayoutDate()?->toDateTime(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}