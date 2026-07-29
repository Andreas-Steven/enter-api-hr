<?php

declare(strict_types=1);

namespace App\Application\PayrollDeduction\Dto;

use App\Domain\PayrollDeduction\Entity\PayrollDeduction;

final readonly class PayrollDeductionResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int $employee_id,
        public int $deduction_type_id,
        public ?int $attendance_report_id,
        public ?int $employee_debt_id,
        public ?int $employee_debt_installment_id,
        public string $period,
        public float $amount,
        public ?string $description,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(PayrollDeduction $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            employee_id: $entity->getEmployeeId(),
            deduction_type_id: $entity->getDeductionTypeId(),
            attendance_report_id: $entity->getAttendanceReportId(),
            employee_debt_id: $entity->getEmployeeDebtId(),
            employee_debt_installment_id: $entity->getEmployeeDebtInstallmentId(),
            period: $entity->getPeriod(),
            amount: $entity->getAmount(),
            description: $entity->getDescription(),
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