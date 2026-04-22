<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EmployeeType;

// Domain Layer
use App\Domain\EmployeeType\Entity\EmployeeType;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbEmployeeTypeSchema
{
    public static function toArray(EmployeeType $entity): array
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