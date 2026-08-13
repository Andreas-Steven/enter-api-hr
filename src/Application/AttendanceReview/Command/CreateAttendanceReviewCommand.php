<?php

declare(strict_types=1);

namespace App\Application\AttendanceReview\Command;

use App\Domain\Shared\Enums\ReviewReason;
use App\Domain\Shared\Enums\ReviewSourceType;

/**
 * Command for manual review item creation.
 *
 * Typically review items are auto-generated via GenerateAttendanceReviewCommand,
 * but this command allows manual creation for edge cases (e.g. ad-hoc review
 * for a situation not covered by the automatic scan).
 */
final readonly class CreateAttendanceReviewCommand
{
    public function __construct(
        public string $name,
        public int $employeeId,
        public int $period,
        public ?int $attendanceSummaryId,
        public ReviewSourceType $sourceType,
        public array $sourceRef,
        public ReviewReason $reviewReason,
        public string $sourceTreatment,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $name,
        int $employeeId,
        int $period,
        ?int $attendanceSummaryId,
        ReviewSourceType $sourceType,
        array $sourceRef,
        ReviewReason $reviewReason,
        string $sourceTreatment,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            employeeId: $employeeId,
            period: $period,
            attendanceSummaryId: $attendanceSummaryId,
            sourceType: $sourceType,
            sourceRef: $sourceRef,
            reviewReason: $reviewReason,
            sourceTreatment: $sourceTreatment,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}
