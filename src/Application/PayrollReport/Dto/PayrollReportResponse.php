<?php

declare(strict_types=1);

namespace App\Application\PayrollReport\Dto;

use App\Domain\PayrollReport\Entity\PayrollReport;

final readonly class PayrollReportResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public ?int $period,
        public array $employee,
        public array $attendance_summary,
        public array $payroll_summary,
        public array $overtime_summary,
        public array $payroll_net,
        public ?string $notes,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(PayrollReport $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            period: $entity->getPeriod(),
            employee: $entity->getEmployee()->toArray(),
            attendance_summary: $entity->getAttendanceSummary()->toArray(),
            payroll_summary: $entity->getPayrollSummary()->toArray(),
            overtime_summary: $entity->getOvertimeSummary()->toArray(),
            payroll_net: $entity->getPayrollNet()->toArray(),
            notes: $entity->getNotes(),
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
