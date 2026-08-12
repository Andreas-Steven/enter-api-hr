<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum WorkPatternRuleType: string
{
    case PERIODIC_REQUIRED_DAY = 'PERIODIC_REQUIRED_DAY';
    case REQUIRED_WEEKDAY = 'REQUIRED_WEEKDAY';
    case TARGET_PERIOD_ATTENDANCE = 'TARGET_PERIOD_ATTENDANCE';

    public function label(): string
    {
        return match ($this) {
            self::PERIODIC_REQUIRED_DAY => 'Periodic Required Day',
            self::REQUIRED_WEEKDAY => 'Required Weekday',
            self::TARGET_PERIOD_ATTENDANCE => 'Target Period Attendance',
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
