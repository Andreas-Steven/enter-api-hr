<?php

declare(strict_types=1);

namespace App\Application\AttendanceReview\Dto;

use App\Domain\AttendanceReview\Entity\AttendanceReview;

final readonly class AttendanceReviewResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public ?int $period,
        public array $employee,
        public array $attendance_summary,
        public array $evidence,
        public int $review_status,
        public ?string $resolved_treatment,
        public ?string $notes,
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
            period: $entity->getPeriod(),
            employee: $entity->getEmployee()->toArray(),
            attendance_summary: $entity->getAttendanceSummary()->toArray(),
            evidence: $entity->getEvidence()->toArray(),
            review_status: $entity->getReviewStatus()->value,
            resolved_treatment: $entity->getResolvedTreatment(),
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
