<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EmployeePosition;

// Domain Layer
use App\Domain\EmployeePosition\Entity\EmployeePosition;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbEmployeePositionSchema
{
    public static function toArray(EmployeePosition $entity): array
    {
        return [
            'name' => $entity->getName(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}