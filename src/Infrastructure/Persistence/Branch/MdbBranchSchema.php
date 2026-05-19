<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Branch;

// Domain Layer
use App\Domain\Branch\Entity\Branch;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbBranchSchema
{
    public static function toArray(Branch $entity): array
    {
        return [
            'name' => $entity->getName(),
            'code' => $entity->getCode(),
            'address' => $entity->getAddress(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}