<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\WorkPattern;

// Domain Layer
use App\Domain\WorkPattern\Entity\WorkPattern;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbWorkPatternSchema
{
    public static function toArray(WorkPattern $entity): array
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
