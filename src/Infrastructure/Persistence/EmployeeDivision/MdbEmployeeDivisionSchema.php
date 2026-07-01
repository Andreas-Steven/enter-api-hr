<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EmployeeDivision;

// Domain Layer
use App\Domain\EmployeeDivision\Entity\EmployeeDivision;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbEmployeeDivisionSchema
{
    public static function toArray(EmployeeDivision $entity): array
    {
        return [
            'name' => $entity->getName(),
            'code' => $entity->getCode(),
            'work_pattern_id' => $entity->getWorkPatternId(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}