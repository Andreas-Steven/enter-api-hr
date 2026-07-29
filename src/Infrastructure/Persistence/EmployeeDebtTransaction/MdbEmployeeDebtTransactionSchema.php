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
            'employee_debt_installment_id' => $entity->getEmployeeDebtInstallmentId(),
            'payroll_deduction_id' => $entity->getPayrollDeductionId(),
            'type' => $entity->getType(),
            'amount' => $entity->getAmount(),
            'transaction_date' => $entity->getTransactionDate(),
            'note' => $entity->getNote(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}