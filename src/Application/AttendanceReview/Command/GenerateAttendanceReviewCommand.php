<?php

declare(strict_types=1);

namespace App\Application\AttendanceReview\Command;

/**
 * Command to auto-generate review items for all AttendanceSummaries
 * in a given period.
 *
 * The application service loads every AttendanceSummary for the period
 * and scans each one for review-triggering treatments. The missing-policy
 * config is resolved per-summary from the branch snapshot in detail_info,
 * unless explicitly provided here.
 */
final readonly class GenerateAttendanceReviewCommand
{
    public function __construct(
        public int $period,
        public ?array $missingPolicyConfig = null,
    ) {}

    public static function create(
        int $period,
        ?array $missingPolicyConfig = null,
    ): self {
        return new self(
            period: $period,
            missingPolicyConfig: $missingPolicyConfig,
        );
    }
}
