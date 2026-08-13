<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PayrollDeduction;

// Domain Layer
use App\Domain\PayrollDeduction\Entity\PayrollDeduction;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbPayrollDeductionSchema
{
    public static function toArray(PayrollDeduction $entity): array
    {
        return [
            'name' => $entity->getName(),
            'employee_id' => $entity->getEmployeeId(),
            'deduction_type_id' => $entity->getDeductionTypeId(),
            'employee_debt_id' => $entity->getEmployeeDebtId(),
            'period' => $entity->getPeriod(),
            'amount' => $entity->getAmount(),
            'note' => $entity->getNote(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}
