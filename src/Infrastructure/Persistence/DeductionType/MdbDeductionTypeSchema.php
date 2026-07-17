<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\DeductionType;

// Domain Layer
use App\Domain\DeductionType\Entity\DeductionType;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbDeductionTypeSchema
{
    public static function toArray(DeductionType $entity): array
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