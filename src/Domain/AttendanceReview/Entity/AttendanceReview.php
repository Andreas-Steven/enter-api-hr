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
use App\Domain\Shared\Enums\ReviewItemStatus;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;

use App\Domain\AttendanceReview\ValueObject\AttendanceSummaryRef;
use App\Domain\AttendanceReview\ValueObject\EmployeeSnapshot;
use App\Domain\AttendanceReview\ValueObject\Notes;
use App\Domain\AttendanceReview\ValueObject\ReviewCounts;
use App\Domain\AttendanceReview\ValueObject\ReviewItemCollection;

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
        private ReviewItemCollection $items,
        private ReviewCounts $counts,
        private ReviewStatus $reviewStatus,
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
     * Creates a new auto-generated AttendanceReview.
     *
     * The name, employee snapshot, attendance summary reference, items and
     * counts are produced by the generation flow from the source
     * AttendanceSummary.
     */
    public static function create(
        string $name,
        ?int $period,
        EmployeeSnapshot $employee,
        AttendanceSummaryRef $attendanceSummary,
        ReviewItemCollection $items,
        ReviewCounts $counts,
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
            items: $items,
            counts: $counts,
            reviewStatus: $reviewStatus,
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
        array $items,
        array $counts,
        string $reviewStatus,
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
            items: ReviewItemCollection::fromArray($items),
            counts: ReviewCounts::fromArray($counts),
            reviewStatus: ReviewStatus::tryFrom($reviewStatus) ?? ReviewStatus::OPEN,
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
            'items' => $this->items->toArray(),
            'counts' => $this->counts->toArray(),
            'review_status' => $this->reviewStatus->value,
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

    public function getItems(): ReviewItemCollection
    {
        return $this->items;
    }

    public function getCounts(): ReviewCounts
    {
        return $this->counts;
    }

    public function getReviewStatus(): ReviewStatus
    {
        return $this->reviewStatus;
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
     * Resolves a single review item by applying the reviewer's decision.
     *
     * Updates the item's status to RESOLVED, records the decision, rebuilds
     * the items collection, recomputes the counts and derives the new
     * aggregate review status.
     */
    public function resolveItem(
        string $itemId,
        string $resolvedTreatment,
        ?int $resolvedBy,
        ?string $note,
    ): void {
        $rawItems = $this->items->toArray();
        $found = false;

        foreach ($rawItems as $i => $rawItem) {
            if ($rawItem['item_id'] !== $itemId) {
                continue;
            }

            // Validate treatment option.
            $allowed = $rawItem['treatment_options'];
            if (!in_array($resolvedTreatment, $allowed, true)) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'attendance_review.invalid_treatment',
                        domain: 'app',
                        params: [
                            'item_id' => $itemId,
                            'treatment' => $resolvedTreatment,
                            'allowed' => implode(', ', $allowed),
                        ],
                    ),
                );
            }

            // Only pending items can be resolved.
            if ($rawItem['status'] !== ReviewItemStatus::PENDING->value) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'attendance_review.item_not_pending',
                        domain: 'app',
                        params: [
                            'item_id' => $itemId,
                            'status' => $rawItem['status'],
                        ],
                    ),
                );
            }

            $rawItem['status'] = ReviewItemStatus::RESOLVED->value;
            $rawItem['decision'] = [
                'resolved_treatment' => $resolvedTreatment,
                'resolved_by' => $resolvedBy,
                'resolved_at' => date('c'),
                'note' => $note,
            ];

            $rawItems[$i] = $rawItem;
            $found = true;
            break;
        }

        if (!$found) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'attendance_review.item_not_found',
                    domain: 'app',
                    params: ['item_id' => $itemId],
                ),
            );
        }

        $this->items = ReviewItemCollection::fromArray($rawItems);
        $this->counts = ReviewCounts::fromItemCollection($this->items);
        $this->reviewStatus = $this->counts->deriveReviewStatus();
    }
}
