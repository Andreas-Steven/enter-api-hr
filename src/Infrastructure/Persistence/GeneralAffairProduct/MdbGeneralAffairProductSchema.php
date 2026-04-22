<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\GeneralAffairProduct;

// Domain Layer
use App\Domain\GeneralAffairProduct\Entity\GeneralAffairProduct;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbGeneralAffairProductSchema
{
    public static function toArray(GeneralAffairProduct $entity): array
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