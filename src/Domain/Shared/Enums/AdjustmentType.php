<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Direction of an EmployeeLeave adjustment transaction.
 *
 * - PLUS:  adds quota (e.g. reward, carryover)
 * - MINUS: removes quota (e.g. expiry, revocation, correction)
 */
enum AdjustmentType: string
{
    case PLUS = 'plus';
    case MINUS = 'minus';

    public function label(): string
    {
        return match ($this) {
            self::PLUS => 'Plus',
            self::MINUS => 'Minus',
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
