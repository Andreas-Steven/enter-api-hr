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
            'payment_term' => $entity->getPaymentTerm(),
            'payment_method' => $entity->getPaymentMethod(),
            'installment_count' => $entity->getInstallmentCount(),
            'installment_amount' => $entity->getInstallmentAmount(),
            'remaining_amount' => $entity->getRemainingAmount(),
            'paid_amount' => $entity->getPaidAmount(),
            'first_payment_date' => $entity->getFirstPaymentDate(),
            'is_settled' => $entity->isSettled(),
            'settled_at' => $entity->getSettledAt(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}