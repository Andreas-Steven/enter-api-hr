<?php

declare(strict_types=1);

namespace App\Application\AttendanceReview\Command;

/**
 * Command for resolving a single AttendanceReview case.
 *
 * The reviewer chooses a treatment from the evidence's `treatment_options`.
 * The entity records the resolved_treatment, sets review_status to CLOSE (2)
 * and transitions the resource status to COMPLETED (3). The reviewer and
 * timestamp are recorded in detail_info.change_log via
 * DetailInfoFactory::withApproved().
 */
final readonly class ResolveAttendanceReviewItemCommand
{
    public function __construct(
        public int $reviewId,
        public string $resolvedTreatment,
        public ?string $note = null,
        public ?int $lockVersion = null,
    ) {}

    public static function create(
        int $reviewId,
        string $resolvedTreatment,
        ?string $note = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            reviewId: $reviewId,
            resolvedTreatment: $resolvedTreatment,
            note: $note,
            lockVersion: $lockVersion,
        );
    }
}
