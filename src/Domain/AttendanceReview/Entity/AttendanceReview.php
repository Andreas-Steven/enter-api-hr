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

use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;
use App\Domain\Shared\Enums\ApprovalStatus;
use App\Domain\Shared\Enums\ReviewReason;
use App\Domain\Shared\Enums\ReviewSourceType;
use App\Domain\AttendanceReview\ValueObject\SourceRef;

final class AttendanceReview
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'AttendanceReview';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private int $employeeId,
        private int $period,
        private ?int $attendanceSummaryId,
        private ReviewSourceType $sourceType,
        private SourceRef $sourceRef,
        private string $stableKey,
        private ReviewReason $reviewReason,
        private string $sourceTreatment,
        private ?string $resolvedTreatment,
        private ApprovalStatus $approvalStatus,
        private ?int $approvedBy,
        private ?string $approvedNote,
        private ?string $approvedAt,
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

    public static function create(
        string $name,
        int $employeeId,
        int $period,
        ?int $attendanceSummaryId,
        ReviewSourceType $sourceType,
        SourceRef $sourceRef,
        ReviewReason $reviewReason,
        string $sourceTreatment,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
    ): self {
        self::guardInitialStatus(
            status: $status,
            resource: self::RESOURCE
        );

        return new self(
            id: null,
            name: $name,
            employeeId: $employeeId,
            period: $period,
            attendanceSummaryId: $attendanceSummaryId,
            sourceType: $sourceType,
            sourceRef: $sourceRef,
            stableKey: $sourceRef->stableHash(),
            reviewReason: $reviewReason,
            sourceTreatment: $sourceTreatment,
            resolvedTreatment: null,
            approvalStatus: ApprovalStatus::PENDING,
            approvedBy: null,
            approvedNote: null,
            approvedAt: null,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            lockVersion: LockVersion::create(),
        );
    }

    public static function reconstitute(
        int $id,
        string $name,
        int $employeeId,
        int $period,
        ?int $attendanceSummaryId,
        ReviewSourceType $sourceType,
        SourceRef $sourceRef,
        string $stableKey,
        ReviewReason $reviewReason,
        string $sourceTreatment,
        ?string $resolvedTreatment,
        ApprovalStatus $approvalStatus,
        ?int $approvedBy,
        ?string $approvedNote,
        ?string $approvedAt,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            employeeId: $employeeId,
            period: $period,
            attendanceSummaryId: $attendanceSummaryId,
            sourceType: $sourceType,
            sourceRef: $sourceRef,
            stableKey: $stableKey,
            reviewReason: $reviewReason,
            sourceTreatment: $sourceTreatment,
            resolvedTreatment: $resolvedTreatment,
            approvalStatus: $approvalStatus,
            approvedBy: $approvedBy,
            approvedNote: $approvedNote,
            approvedAt: $approvedAt,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            lockVersion: $lockVersion ?? LockVersion::create(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'employee_id' => $this->employeeId,
            'period' => $this->period,
            'attendance_summary_id' => $this->attendanceSummaryId,
            'source_type' => $this->sourceType->value,
            'source_ref' => $this->sourceRef->toArray(),
            'stable_key' => $this->stableKey,
            'review_reason' => $this->reviewReason->value,
            'source_treatment' => $this->sourceTreatment,
            'resolved_treatment' => $this->resolvedTreatment,
            'approval_status' => $this->approvalStatus->value,
            'approved_by' => $this->approvedBy,
            'approved_note' => $this->approvedNote,
            'approved_at' => $this->approvedAt,
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

    // --- Review-specific business logic ---

    /**
     * Apply a manual resolution decision to this review item.
     *
     * When APPROVED, a resolvedTreatment MUST be provided — this is the
     * alternative treatment the resolver has chosen (e.g. TREAT_AS_ABSENT
     * for a deficit that was originally MANUAL_REVIEW).
     *
     * When REJECTED, resolvedTreatment is cleared — the original source
     * treatment stands and the deficit/excess/missing event is treated as
     * "held" (not counted toward any summary bucket).
     */
    public function applyResolution(
        ApprovalStatus $decision,
        ?string $resolvedTreatment,
        ?int $approvedBy,
        ?string $approvedNote,
        ?string $approvedAt,
    ): void {
        $currentStatus = $this->approvalStatus;

        if (!$decision->canTransitionFrom($currentStatus)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'attendance_review.invalid_resolution_transition',
                    params: [
                        'from' => $currentStatus->getLabel(),
                        'to' => $decision->getLabel(),
                    ],
                    domain: 'app',
                )
            );
        }

        if ($decision === ApprovalStatus::APPROVED) {
            if ($resolvedTreatment === null || $resolvedTreatment === '') {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'attendance_review.resolved_treatment_required',
                        params: [],
                        domain: 'app',
                    )
                );
            }
            $this->resolvedTreatment = $resolvedTreatment;
        } else {
            // REJECTED: clear any prior override; the source treatment stands.
            $this->resolvedTreatment = null;
        }

        $this->approvalStatus = $decision;
        $this->approvedBy = $approvedBy;
        $this->approvedNote = $approvedNote;
        $this->approvedAt = $approvedAt;
    }

    public function isPending(): bool
    {
        return $this->approvalStatus === ApprovalStatus::PENDING;
    }

    public function isResolved(): bool
    {
        return $this->approvalStatus !== ApprovalStatus::PENDING;
    }

    // --- Getters ---

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function getAttendanceSummaryId(): ?int
    {
        return $this->attendanceSummaryId;
    }

    public function setAttendanceSummaryId(?int $attendanceSummaryId): void
    {
        $this->attendanceSummaryId = $attendanceSummaryId;
    }

    public function getSourceType(): ReviewSourceType
    {
        return $this->sourceType;
    }

    public function getSourceRef(): SourceRef
    {
        return $this->sourceRef;
    }

    public function getStableKey(): string
    {
        return $this->stableKey;
    }

    public function getReviewReason(): ReviewReason
    {
        return $this->reviewReason;
    }

    public function getSourceTreatment(): string
    {
        return $this->sourceTreatment;
    }

    public function getResolvedTreatment(): ?string
    {
        return $this->resolvedTreatment;
    }

    public function getApprovalStatus(): ApprovalStatus
    {
        return $this->approvalStatus;
    }

    public function getApprovedBy(): ?int
    {
        return $this->approvedBy;
    }

    public function getApprovedNote(): ?string
    {
        return $this->approvedNote;
    }

    public function getApprovedAt(): ?string
    {
        return $this->approvedAt;
    }
}
