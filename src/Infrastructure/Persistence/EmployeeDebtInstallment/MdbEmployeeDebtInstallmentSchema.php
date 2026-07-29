<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EmployeeDebtInstallment;

// Domain Layer
use App\Domain\EmployeeDebtInstallment\Entity\EmployeeDebtInstallment;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbEmployeeDebtInstallmentSchema
{
    public static function toArray(EmployeeDebtInstallment $entity): array
    {
        return [
            'name' => $entity->getName(),
            'employee_debt_id' => $entity->getEmployeeDebtId(),
            'payroll_deduction_id' => $entity->getPayrollDeductionId(),
            'sequence' => $entity->getSequence(),
            'due_date' => $entity->getDueDate(),
            'amount' => $entity->getAmount(),
            'payment_status' => $entity->getPaymentStatus(),
            'paid_at' => $entity->getPaidAt(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}