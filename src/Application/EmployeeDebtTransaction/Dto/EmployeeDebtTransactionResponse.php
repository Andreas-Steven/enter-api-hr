<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebtTransaction\Dto;

use App\Domain\EmployeeDebtTransaction\Entity\EmployeeDebtTransaction;

final readonly class EmployeeDebtTransactionResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int $employee_debt_id,
        public ?int $employee_debt_installment_id,
        public ?int $payroll_deduction_id,
        public string $type,
        public float $amount,
        public string $transaction_date,
        public ?string $note,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(EmployeeDebtTransaction $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            employee_debt_id: $entity->getEmployeeDebtId(),
            employee_debt_installment_id: $entity->getEmployeeDebtInstallmentId(),
            payroll_deduction_id: $entity->getPayrollDeductionId(),
            type: $entity->getType(),
            amount: $entity->getAmount(),
            transaction_date: $entity->getTransactionDate(),
            note: $entity->getNote(),
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