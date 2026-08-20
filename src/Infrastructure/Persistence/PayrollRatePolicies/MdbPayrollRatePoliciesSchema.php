<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PayrollRatePolicies;

// Domain Layer
use App\Domain\PayrollRatePolicies\Entity\PayrollRatePolicies;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbPayrollRatePoliciesSchema
{
    public static function toArray(PayrollRatePolicies $entity): array
    {
        return [
            'name' => $entity->getName(),
            'rate_type' => $entity->getRateType()->value,
            'deduction_type_id' => $entity->getDeductionTypeId(),
            'amount' => $entity->getAmount(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}
