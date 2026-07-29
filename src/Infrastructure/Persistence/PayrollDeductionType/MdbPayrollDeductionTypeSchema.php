<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PayrollDeductionType;

// Domain Layer
use App\Domain\PayrollDeductionType\Entity\PayrollDeductionType;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbPayrollDeductionTypeSchema
{
    public static function toArray(PayrollDeductionType $entity): array
    {
        return [
            'name' => $entity->getName(),
            'category' => $entity->getCategory(),
            'creates_debt' => $entity->getCreatesDebt(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}