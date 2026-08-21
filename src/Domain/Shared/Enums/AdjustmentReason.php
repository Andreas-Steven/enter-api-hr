<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Reason category for an EmployeeLeave adjustment transaction.
 *
 * Stored alongside the adjustment record as `leave_type` to explain WHY the
 * adjustment was made.
 *
 * - REWARD:       Leave granted as a reward (e.g. event prize).
 * - COMPENSATORY: Leave granted in lieu of overtime/work on holiday.
 * - MANUAL:       Manual adjustment by an admin/HR.
 * - CORRECTION:   Correcting a previous data-entry mistake.
 * - EXPIRY:       Removing expired unused quota.
 * - CARRYOVER:    Quota carried over from a previous period.
 * - REVOCATION:   Revoking a previously granted adjustment.
 */
enum AdjustmentReason: string
{
    case REWARD = 'REWARD';
    case COMPENSATORY = 'COMPENSATORY';
    case MANUAL = 'MANUAL';
    case CORRECTION = 'CORRECTION';
    case EXPIRY = 'EXPIRY';
    case CARRYOVER = 'CARRYOVER';
    case REVOCATION = 'REVOCATION';

    public function label(): string
    {
        return match ($this) {
            self::REWARD => 'Reward',
            self::COMPENSATORY => 'Compensatory',
            self::MANUAL => 'Manual',
            self::CORRECTION => 'Correction',
            self::EXPIRY => 'Expiry',
            self::CARRYOVER => 'Carryover',
            self::REVOCATION => 'Revocation',
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
