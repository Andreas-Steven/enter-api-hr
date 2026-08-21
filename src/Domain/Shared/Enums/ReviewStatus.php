<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Aggregate-level status of an AttendanceReview.
 *
 * Stored as an integer in the database:
 * - 1 (OPEN):  review has been generated and is awaiting a reviewer decision.
 * - 2 (CLOSE): review has been resolved (the evidence case is closed).
 */
enum ReviewStatus: int
{
    case OPEN = 1;
    case CLOSE = 2;

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::CLOSE => 'Close',
        };
    }

    /**
     * @return list<int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromInt(int $value): ?self
    {
        return self::tryFrom($value);
    }
}
