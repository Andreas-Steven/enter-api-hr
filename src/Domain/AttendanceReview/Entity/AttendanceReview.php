<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReview\Entity;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;

// Domain Layer
use App\Domain\Shared\Concerns\Entity\Stateful;
use App\Domain\Shared\Concerns\Entity\Identifiable;
use App\Domain\Shared\Concerns\Entity\Descriptive;

use App\Domain\Shared\Enums\ReviewStatus;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;

use App\Domain\AttendanceReview\ValueObject\AttendanceSummaryRef;
use App\Domain\AttendanceReview\ValueObject\EmployeeSnapshot;
use App\Domain\AttendanceReview\ValueObject\Notes;
use App\Domain\AttendanceReview\ValueObject\ReviewItemEvidence;

final class AttendanceReview
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'AttendanceReview';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private ?int $period,
        private readonly EmployeeSnapshot $employee,
        private readonly AttendanceSummaryRef $attendanceSummary,
        private ReviewItemEvidence $evidence,
        private ReviewStatus $reviewStatus,
        private ?string $resolvedTreatment,
        private ?string $notes,
        private ResourceStatus $status,
        private DetailInfo $detailInfo,
        private ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ) {
        $this->resource = self::RESOURCE;
        $this->lockVersion = $lockVersion ?? LockVersion::create();
    }

    public static function getResource(): string
    {
        return self::RESOURCE;
    }

    /**
     * Creates a new auto-generated AttendanceReview for a single evidence case.
     *
     * The name, employee snapshot, attendance summary reference and evidence
     * are produced by the generation flow from the source AttendanceSummary.
     * Each evidence case becomes its own review record.
     */
    public static function create(
        string $name,
        ?int $period,
        EmployeeSnapshot $employee,
        AttendanceSummaryRef $attendanceSummary,
        ReviewItemEvidence $evidence,
        ReviewStatus $reviewStatus,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?Notes $notes = null,
        ?SyncMdb $syncMdb = null,
    ): self {
        self::guardInitialStatus(
            status: $status,
            resource: self::RESOURCE
        );

        return new self(
            id: null,
            name: $name,
            period: $period,
            employee: $employee,
            attendanceSummary: $attendanceSummary,
            evidence: $evidence,
            reviewStatus: $reviewStatus,
            resolvedTreatment: null,
            notes: $notes?->toString(),
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            lockVersion: LockVersion::create()
        );
    }

    /**
     * Reconstitutes an AttendanceReview from persisted (raw) data.
     *
     * Composite columns are passed as arrays and wrapped in their Value Objects.
     */
    public static function reconstitute(
        int $id,
        string $name,
        ?int $period,
        array $employee,
        array $attendanceSummary,
        array $evidence,
        int $reviewStatus,
        ?string $resolvedTreatment,
        ?string $notes,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            period: $period,
            employee: EmployeeSnapshot::fromArray($employee),
            attendanceSummary: AttendanceSummaryRef::fromArray($attendanceSummary),
            evidence: ReviewItemEvidence::fromArray($evidence),
            reviewStatus: ReviewStatus::tryFrom($reviewStatus) ?? ReviewStatus::OPEN,
            resolvedTreatment: $resolvedTreatment,
            notes: $notes,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            lockVersion: $lockVersion ?? LockVersion::create()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'period' => $this->period,
            'employee' => $this->employee->toArray(),
            'attendance_summary' => $this->attendanceSummary->toArray(),
            'evidence' => $this->evidence->toArray(),
            'review_status' => $this->reviewStatus->value,
            'resolved_treatment' => $this->resolvedTreatment,
            'notes' => $this->notes,
            'status' => $this->status->value(),
            'detail_info' => $this->detailInfo->toArray(),
            SyncMdb::field() => $this->syncMdb?->value(),
            LockVersion::field() => $this->lockVersion->value(),
        ];
    }

    public function restore(): void
    {
        if (!$this->status->isDeleted()) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'resource.not_deleted',
                    params: ['id' => $this->id]
                )
            );
        }

        $this->status = ResourceStatus::restored();
    }

    /**
     * Auto-generated review fields.
     */
    public function getPeriod(): ?int
    {
        return $this->period;
    }

    public function getEmployee(): EmployeeSnapshot
    {
        return $this->employee;
    }

    public function getAttendanceSummary(): AttendanceSummaryRef
    {
        return $this->attendanceSummary;
    }

    public function getEvidence(): ReviewItemEvidence
    {
        return $this->evidence;
    }

    public function getReviewStatus(): ReviewStatus
    {
        return $this->reviewStatus;
    }

    public function getResolvedTreatment(): ?string
    {
        return $this->resolvedTreatment;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * Manual adjustments on a generated review.
     */
    public function updateNotes(?Notes $notes): void
    {
        if ($notes === null) {
            return;
        }

        $this->notes = $notes->toString();
    }

    public function updateReviewStatus(ReviewStatus $reviewStatus): void
    {
        $this->reviewStatus = $reviewStatus;
    }

    /**
     * Resolves this review case by applying the reviewer's decision.
     *
     * Validates the treatment against the evidence's treatment_options,
     * records the resolved_treatment, sets the review_status to CLOSE (2)
     * and transitions the resource status to COMPLETED (3). The reviewer
     * and timestamp are recorded in detail_info.change_log via
     * DetailInfoFactory::withApproved() by the application service.
     */
    public function resolve(
        string $resolvedTreatment,
        ?string $note,
    ): void {
        // Only open reviews can be resolved.
        if ($this->reviewStatus === ReviewStatus::CLOSE) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'attendance_review.already_closed',
                    domain: 'app',
                    params: [
                        'id' => $this->id,
                    ],
                ),
            );
        }

        // Validate treatment option.
        $allowed = $this->evidence->getTreatmentOptions();
        if (!in_array($resolvedTreatment, $allowed, true)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'attendance_review.invalid_treatment',
                    domain: 'app',
                    params: [
                        'treatment' => $resolvedTreatment,
                        'allowed' => implode(', ', $allowed),
                    ],
                ),
            );
        }

        $this->resolvedTreatment = $resolvedTreatment;

        if ($note !== null) {
            $this->notes = $note;
        }

        $this->reviewStatus = ReviewStatus::CLOSE;
        $this->status = ResourceStatus::completed();
    }
}
