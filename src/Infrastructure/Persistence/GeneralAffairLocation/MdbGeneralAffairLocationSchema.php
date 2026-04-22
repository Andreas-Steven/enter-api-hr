<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\GeneralAffairLocation;

// Domain Layer
use App\Domain\GeneralAffairLocation\Entity\GeneralAffairLocation;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbGeneralAffairLocationSchema
{
    public static function toArray(GeneralAffairLocation $entity): array
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