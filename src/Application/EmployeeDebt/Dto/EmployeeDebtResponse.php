<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebt\Dto;

use App\Domain\EmployeeDebt\Entity\EmployeeDebt;

final readonly class EmployeeDebtResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int $employee_id,
        public int $deduction_type_id,
        public ?float $amount,
        public ?string $date,
        public ?string $note,
        public int $payment_term,
        public ?string $payment_method,
        public int $installment_count,
        public float $installment_amount,
        public float $remaining_amount,
        public float $paid_amount,
        public ?string $first_payment_date,
        public int $is_settled,
        public ?string $settled_at,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(EmployeeDebt $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            employee_id: $entity->getEmployeeId(),
            deduction_type_id: $entity->getDeductionTypeId(),
            amount: $entity->getAmount(),
            date: $entity->getDate(),
            note: $entity->getNote(),
            payment_term: $entity->getPaymentTerm(),
            payment_method: $entity->getPaymentMethod(),
            installment_count: $entity->getInstallmentCount(),
            installment_amount: $entity->getInstallmentAmount(),
            remaining_amount: $entity->getRemainingAmount(),
            paid_amount: $entity->getPaidAmount(),
            first_payment_date: $entity->getFirstPaymentDate(),
            is_settled: $entity->isSettled(),
            settled_at: $entity->getSettledAt(),
            status: $entity->getStatus()->value(),
            detail_info: $entity->getDetailInfo()->toArray(),
            sync_mdb: $entity->getSyncMdbValue(),
            lock_version: $entity->getLockVersion()->value(),
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}