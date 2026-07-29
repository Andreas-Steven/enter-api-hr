<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebtInstallment\Dto;

use App\Domain\EmployeeDebtInstallment\Entity\EmployeeDebtInstallment;

final readonly class EmployeeDebtInstallmentResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int $employee_debt_id,
        public ?int $payroll_deduction_id,
        public int $sequence,
        public string $due_date,
        public float $amount,
        public int $payment_status,
        public ?string $paid_at,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(EmployeeDebtInstallment $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            employee_debt_id: $entity->getEmployeeDebtId(),
            payroll_deduction_id: $entity->getPayrollDeductionId(),
            sequence: $entity->getSequence(),
            due_date: $entity->getDueDate(),
            amount: $entity->getAmount(),
            payment_status: $entity->getPaymentStatus(),
            paid_at: $entity->getPaidAt(),
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