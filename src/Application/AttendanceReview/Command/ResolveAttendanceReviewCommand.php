<?php

declare(strict_types=1);

namespace App\Application\AttendanceReview\Command;

use App\Domain\Shared\Enums\ApprovalStatus;

/**
 * Command to resolve (approve/reject) a review item.
 *
 * When approving, resolvedTreatment is the alternative treatment the
 * resolver has chosen (e.g. TREAT_AS_ABSENT for a deficit that was
 * originally MANUAL_REVIEW).
 *
 * When rejecting, resolvedTreatment should be null — the original
 * source treatment stands and the item is "held" (not counted).
 */
final readonly class ResolveAttendanceReviewCommand
{
    public function __construct(
        public int $id,
        public ApprovalStatus $decision,
        public ?string $resolvedTreatment,
        public ?string $approvedNote,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ApprovalStatus $decision,
        ?string $resolvedTreatment = null,
        ?string $approvedNote = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            decision: $decision,
            resolvedTreatment: $resolvedTreatment,
            approvedNote: $approvedNote,
            lockVersion: $lockVersion,
        );
    }
}
