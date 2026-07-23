<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EmployeeDebt;

// Domain Layer
use App\Domain\EmployeeDebt\Entity\EmployeeDebt;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbEmployeeDebtSchema
{
    public static function toArray(EmployeeDebt $entity): array
    {
        return [
            'name' => $entity->getName(),
            'employee_id' => $entity->getEmployeeId(),
            'deduction_type_id' => $entity->getDeductionTypeId(),
            'amount' => $entity->getAmount(),
            'date' => $entity->getDate(),
            'note' => $entity->getNote(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}