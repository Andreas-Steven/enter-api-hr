<?php

declare(strict_types=1);

namespace App\Application\PayrollReports\Dto;

use App\Domain\PayrollReports\Entity\PayrollReports;

final readonly class PayrollReportsResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int $attendance_summary_id,
        public array $payroll_summary,
        public ?string $notes,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(PayrollReports $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            attendance_summary_id: $entity->getAttendanceSummaryId(),
            payroll_summary: $entity->getPayrollSummary(),
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