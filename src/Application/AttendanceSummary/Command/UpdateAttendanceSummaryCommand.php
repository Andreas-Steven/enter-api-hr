<?php

declare(strict_types=1);

namespace App\Application\AttendanceSummary\Command;

/**
 * Command for manual adjustments on a generated AttendanceSummary.
 *
 * Generated fields (employee snapshot, work schedule, attendance data, summary,
 * policy) are not editable; only the name, status, notes and detail info can be
 * tweaked manually.
 */
final readonly class UpdateAttendanceSummaryCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?int $status,
        public ?string $notes,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?int $status = null,
        ?string $notes = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            status: $status,
            notes: $notes,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}
