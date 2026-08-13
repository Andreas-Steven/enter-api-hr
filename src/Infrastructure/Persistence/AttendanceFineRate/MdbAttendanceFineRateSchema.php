<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\AttendanceFineRate;

// Domain Layer
use App\Domain\AttendanceFineRate\Entity\AttendanceFineRate;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbAttendanceFineRateSchema
{
    public static function toArray(AttendanceFineRate $entity): array
    {
        return [
            'name' => $entity->getName(),
            'incident_type' => $entity->getIncidentType()->value,
            'deduction_type_id' => $entity->getDeductionTypeId(),
            'amount' => $entity->getAmount(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}
