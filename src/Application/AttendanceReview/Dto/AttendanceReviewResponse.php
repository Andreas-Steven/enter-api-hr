<?php

declare(strict_types=1);

namespace App\Application\AttendanceReview\Dto;

use App\Domain\AttendanceReview\Entity\AttendanceReview;

final readonly class AttendanceReviewResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int $employee_id,
        public int $period,
        public ?int $attendance_summary_id,
        public string $source_type,
        public array $source_ref,
        public string $stable_key,
        public string $review_reason,
        public string $source_treatment,
        public ?string $resolved_treatment,
        public int $approval_status,
        public ?int $approved_by,
        public ?string $approved_note,
        public ?string $approved_at,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(AttendanceReview $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            employee_id: $entity->getEmployeeId(),
            period: $entity->getPeriod(),
            attendance_summary_id: $entity->getAttendanceSummaryId(),
            source_type: $entity->getSourceType()->value,
            source_ref: $entity->getSourceRef()->toArray(),
            stable_key: $entity->getStableKey(),
            review_reason: $entity->getReviewReason()->value,
            source_treatment: $entity->getSourceTreatment(),
            resolved_treatment: $entity->getResolvedTreatment(),
            approval_status: $entity->getApprovalStatus()->value,
            approved_by: $entity->getApprovedBy(),
            approved_note: $entity->getApprovedNote(),
            approved_at: $entity->getApprovedAt(),
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
