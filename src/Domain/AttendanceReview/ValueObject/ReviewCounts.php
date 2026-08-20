<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReview\ValueObject;

// Domain Layer
use App\Domain\Shared\Enums\ReviewItemStatus;
use App\Domain\Shared\Enums\ReviewStatus;

/**
 * Denormalized counters for an AttendanceReview (the `counts` JSONB column).
 *
 * Mirrors the per-status breakdown of the item collection so that list/filter
 * queries can avoid loading and iterating the full items array.
 */
final readonly class ReviewCounts
{
    private function __construct(
        private int $total,
        private int $pending,
        private int $approved,
        private int $rejected,
        private int $resolved,
    ) {}

    public static function empty(): self
    {
        return new self(0, 0, 0, 0, 0);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['total'] ?? 0),
            (int) ($data['pending'] ?? 0),
            (int) ($data['approved'] ?? 0),
            (int) ($data['rejected'] ?? 0),
            (int) ($data['resolved'] ?? 0),
        );
    }

    public static function fromValues(
        int $total,
        int $pending,
        int $approved,
        int $rejected,
        int $resolved,
    ): self {
        return new self($total, $pending, $approved, $rejected, $resolved);
    }

    /**
     * Derives counts from a collection of items.
     */
    public static function fromItemCollection(ReviewItemCollection $collection): self
    {
        return new self(
            total: $collection->count(),
            pending: $collection->countByStatus(ReviewItemStatus::PENDING),
            approved: $collection->countByStatus(ReviewItemStatus::APPROVED),
            rejected: $collection->countByStatus(ReviewItemStatus::REJECTED),
            resolved: $collection->countByStatus(ReviewItemStatus::RESOLVED),
        );
    }

    /**
     * Derives the aggregate review status from the counts.
     *
     * - No items at all → COMPLETED (nothing to review).
     * - All items resolved/approved/rejected → COMPLETED.
     * - Some items pending, none resolved yet → OPEN.
     * - Mix of pending and resolved → IN_PROGRESS.
     */
    public function deriveReviewStatus(): ReviewStatus
    {
        if ($this->total === 0) {
            return ReviewStatus::COMPLETED;
        }

        if ($this->pending === 0) {
            return ReviewStatus::COMPLETED;
        }

        if ($this->resolved > 0 || $this->approved > 0 || $this->rejected > 0) {
            return ReviewStatus::IN_PROGRESS;
        }

        return ReviewStatus::OPEN;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPending(): int
    {
        return $this->pending;
    }

    public function getApproved(): int
    {
        return $this->approved;
    }

    public function getRejected(): int
    {
        return $this->rejected;
    }

    public function getResolved(): int
    {
        return $this->resolved;
    }

    /** @return array<string, int> */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'pending' => $this->pending,
            'approved' => $this->approved,
            'rejected' => $this->rejected,
            'resolved' => $this->resolved,
        ];
    }
}
