<?php

declare(strict_types=1);

namespace App\Application\AttendanceReview\Command;

/**
 * Command for updating review item metadata (notes, detailInfo, status).
 *
 * Resolution decisions (approve/reject) use ResolveAttendanceReviewCommand,
 * not this command.
 */
final readonly class UpdateAttendanceReviewCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}
