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
        public int $employee_id,
        public string $transaction_type,
        public string $date,
        public int $amount,
        public ?string $payment_source,
        public ?int $payroll_deduction_id,
        public ?int $payroll_period,
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
            employee_id: $entity->getEmployeeId(),
            transaction_type: $entity->getTransactionType(),
            date: $entity->getDate(),
            amount: $entity->getAmount(),
            payment_source: $entity->getPaymentSource(),
            payroll_deduction_id: $entity->getPayrollDeductionId(),
            payroll_period: $entity->getPayrollPeriod(),
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
