<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\WorkTime;

// Domain Layer
use App\Domain\WorkTime\Entity\WorkTime;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbWorkTimeSchema
{
    public static function toArray(WorkTime $entity): array
    {
        return [
            'name' => $entity->getName(),
            'working_days' => $entity->getWorkingDays()->toArray(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}