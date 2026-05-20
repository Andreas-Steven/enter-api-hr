<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EmployeeGrade;

// Domain Layer
use App\Domain\EmployeeGrade\Entity\EmployeeGrade;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbEmployeeGradeSchema
{
    public static function toArray(EmployeeGrade $entity): array
    {
        return [
            'name' => $entity->getName(),
            'level' => $entity->getLevel(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}