<?php

declare(strict_types=1);

namespace App\Application\AttendanceReview\Command;

final readonly class CreateAttendanceReviewCommand
{
    public function __construct(
        public string $name,
        public int $status,
        public ?int $period = null,
        public ?array $employee = null,
        public ?array $attendanceSummary = null,
        public ?array $items = null,
        public ?array $counts = null,
        public ?string $reviewStatus = null,
        public ?string $notes = null,
        public ?array $detailInfo = null,
    ) {}

    public static function create(
        string $name,
        int $status,
        ?int $period = null,
        ?array $employee = null,
        ?array $attendanceSummary = null,
        ?array $items = null,
        ?array $counts = null,
        ?string $reviewStatus = null,
        ?string $notes = null,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            status: $status,
            period: $period,
            employee: $employee,
            attendanceSummary: $attendanceSummary,
            items: $items,
            counts: $counts,
            reviewStatus: $reviewStatus,
            notes: $notes,
            detailInfo: $detailInfo,
        );
    }
}
