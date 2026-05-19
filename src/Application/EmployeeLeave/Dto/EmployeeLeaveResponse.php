<?php

declare(strict_types=1);

namespace App\Application\EmployeeLeave\Dto;

use App\Domain\EmployeeLeave\Entity\EmployeeLeave;

final readonly class EmployeeLeaveResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $leave_type,
        public int $employee_id,
        public string $period_start_date,
        public string $period_end_date,
        public int $total_amount,
        public ?int $adjustment_amount,
        public ?int $used_amount,
        public array $leave_records,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(EmployeeLeave $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            leave_type: $entity->getLeaveType(),
            employee_id: $entity->getEmployeeId(),
            period_start_date: $entity->getPeriodStartDate(),
            period_end_date: $entity->getPeriodEndDate(),
            total_amount: $entity->getTotalAmount(),
            adjustment_amount: $entity->getAdjustmentAmount(),
            used_amount: $entity->getUsedAmount(),
            leave_records: $entity->getLeaveRecords(),
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