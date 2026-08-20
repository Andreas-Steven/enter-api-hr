<?php

declare(strict_types=1);

namespace App\Application\AttendanceReview\Command;

final readonly class UpdateAttendanceReviewCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?int $status,
        public ?string $reviewStatus,
        public ?string $notes,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?int $status = null,
        ?string $reviewStatus = null,
        ?string $notes = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            status: $status,
            reviewStatus: $reviewStatus,
            notes: $notes,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}
