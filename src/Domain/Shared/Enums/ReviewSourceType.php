<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Identifies whether a review item originates from a rule-level aggregate
 * (deficit/excess across a period for a single rule) or from a day-level
 * event (missing check-in/out on a specific date).
 */
enum ReviewSourceType: string
{
    case RULE_LEVEL = 'RULE_LEVEL';
    case DAY_LEVEL = 'DAY_LEVEL';

    public function label(): string
    {
        return match ($this) {
            self::RULE_LEVEL => 'Rule Level',
            self::DAY_LEVEL => 'Day Level',
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
