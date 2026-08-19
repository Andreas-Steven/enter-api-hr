<?php

declare(strict_types=1);

namespace App\Domain\AttendanceSummary\ValueObject;

// Domain Layer
use App\Domain\Attendance\ValueObject\AttendanceItem;

/**
 * Collection of attendance permissions relevant to a summary.
 *
 * Stored as JSON in the `permissions` column. Permissions (sick, leave,
 * business trip, etc.) are sourced from the AttendancePermission aggregate;
 * when that source is not available at generation time the collection stays
 * empty, and permission-derived counters in the summary resolve to zero.
 */
final readonly class PermissionCollection
{
    private function __construct(
        private array $items,
    ) {}

    public static function empty(): self
    {
        return new self([]);
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Permissions are not derivable from raw Attendance records alone, so the
     * generated collection is empty until an AttendancePermission source is
     * wired into the generation flow.
     *
     * @param array<string, AttendanceItem> $dailyItems
     */
    public static function fromDailyItems(array $dailyItems): self
    {
        return self::empty();
    }

    /** @return array<int, mixed> */
    public function toArray(): array
    {
        return $this->items;
    }
}
