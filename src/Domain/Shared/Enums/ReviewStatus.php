<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Aggregate-level status of an AttendanceReview.
 *
 * - OPEN:        review has been generated and contains pending items awaiting
 *                reviewer decisions.
 * - IN_PROGRESS: at least one item has been resolved but others remain pending.
 * - COMPLETED:   every item has been resolved (or the review had no items at
 *                all, meaning no review-triggering conditions were found).
 */
enum ReviewStatus: string
{
    case OPEN = 'OPEN';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
