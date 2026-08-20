<?php

declare(strict_types=1);

namespace App\Application\AttendanceReview\Command;

/**
 * Command for resolving a single review item.
 *
 * The reviewer chooses a treatment from the item's `treatment_options` list.
 * The domain service applies the decision to the item, recomputes the review
 * counts/status, and (when wired) mutates the source AttendanceSummary to
 * reflect the resolution.
 */
final readonly class ResolveAttendanceReviewItemCommand
{
    public function __construct(
        public int $reviewId,
        public string $itemId,
        public string $resolvedTreatment,
        public ?int $resolvedBy = null,
        public ?string $note = null,
        public ?int $lockVersion = null,
    ) {}

    public static function create(
        int $reviewId,
        string $itemId,
        string $resolvedTreatment,
        ?int $resolvedBy = null,
        ?string $note = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            reviewId: $reviewId,
            itemId: $itemId,
            resolvedTreatment: $resolvedTreatment,
            resolvedBy: $resolvedBy,
            note: $note,
            lockVersion: $lockVersion,
        );
    }
}
