<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\GeneralAffair;

// Domain Layer
use App\Domain\GeneralAffair\Entity\GeneralAffair;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbGeneralAffairSchema
{
    public static function toArray(GeneralAffair $entity): array
    {
        return [
            /* 'name' => $entity->getName(), */
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}