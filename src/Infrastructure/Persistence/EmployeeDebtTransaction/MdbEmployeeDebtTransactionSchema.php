<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EmployeeDebtTransaction;

// Domain Layer
use App\Domain\EmployeeDebtTransaction\Entity\EmployeeDebtTransaction;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbEmployeeDebtTransactionSchema
{
    public static function toArray(EmployeeDebtTransaction $entity): array
    {
        return [
            'name' => $entity->getName(),
            'employee_debt_id' => $entity->getEmployeeDebtId(),
            'employee_id' => $entity->getEmployeeId(),
            'transaction_type' => $entity->getTransactionType(),
            'date' => $entity->getDate(),
            'amount' => $entity->getAmount(),
            'payment_source' => $entity->getPaymentSource(),
            'payroll_deduction_id' => $entity->getPayrollDeductionId(),
            'payroll_period' => $entity->getPayrollPeriod(),
            'note' => $entity->getNote(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}
