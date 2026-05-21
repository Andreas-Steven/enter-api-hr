<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\AttendancePolicies;

// Domain Layer
use App\Domain\AttendancePolicies\Entity\AttendancePolicies;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbAttendancePoliciesSchema
{
    public static function toArray(AttendancePolicies $entity): array
    {
        return [
            'name' => $entity->getName(),
            'policy_detail' => $entity->getPolicyDetail()->toArray(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}