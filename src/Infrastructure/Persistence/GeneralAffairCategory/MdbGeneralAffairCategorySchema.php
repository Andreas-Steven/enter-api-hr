<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\GeneralAffairCategory;

// Domain Layer
use App\Domain\GeneralAffairCategory\Entity\GeneralAffairCategory;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbGeneralAffairCategorySchema
{
    public static function toArray(GeneralAffairCategory $entity): array
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