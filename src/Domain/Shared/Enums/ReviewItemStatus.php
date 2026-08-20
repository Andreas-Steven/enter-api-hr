<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Status of a single review item inside an AttendanceReview.
 *
 * - PENDING:  item has been generated and is awaiting a reviewer decision.
 * - APPROVED: reviewer approved the item (the resolved treatment is accepted).
 * - REJECTED: reviewer rejected the item (the resolved treatment is denied).
 * - RESOLVED: item has been resolved and its treatment has been applied back
 *             to the source AttendanceSummary.
 */
enum ReviewItemStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case RESOLVED = 'RESOLVED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::RESOLVED => 'Resolved',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Returns true when the item still awaits a reviewer decision.
     */
    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Returns true when the item has reached a terminal state (no further
     * reviewer action expected).
     */
    public function isTerminal(): bool
    {
        return $this === self::APPROVED
            || $this === self::REJECTED
            || $this === self::RESOLVED;
    }
}
