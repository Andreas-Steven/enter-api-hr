<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Standard leave quota types tracked by the HR system.
 *
 * - ANNUAL:        Regular yearly paid leave quota.
 * - SICK:          Sick leave quota.
 * - MATERNITY:     Maternity leave quota.
 * - PATERNITY:     Paternity leave quota.
 * - UNPAID:        Unpaid leave quota (tracked but not paid).
 * - REWARD:        Leave granted as a reward (e.g. event prize).
 * - COMPENSATORY:  Compensatory leave (e.g. in lieu of overtime).
 */
enum LeaveType: string
{
    case ANNUAL = 'ANNUAL';
    case SICK = 'SICK';
    case MATERNITY = 'MATERNITY';
    case PATERNITY = 'PATERNITY';
    case UNPAID = 'UNPAID';
    case REWARD = 'REWARD';
    case COMPENSATORY = 'COMPENSATORY';

    public function label(): string
    {
        return match ($this) {
            self::ANNUAL => 'Annual Leave',
            self::SICK => 'Sick Leave',
            self::MATERNITY => 'Maternity Leave',
            self::PATERNITY => 'Paternity Leave',
            self::UNPAID => 'Unpaid Leave',
            self::REWARD => 'Reward Leave',
            self::COMPENSATORY => 'Compensatory Leave',
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
