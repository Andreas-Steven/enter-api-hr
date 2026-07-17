<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

final class DeductionType
{
    // Status Codes
    public const ATTENDANCE_FINE = 'attendance_fine';
    public const ATTENDANCE_DEDUCTION = 'attendance_deduction';
    public const DAMAGE_CHARGE = 'damage_charge';
    public const LOSS_CHARGE = 'loss_charge';
    public const CASH_ADVANCE = 'cash_advance';
    public const MANUAL_ADJUSTMENT = 'manual_adjustment';

    public static function values(): array
    {
        return [
            self::ATTENDANCE_FINE,
            self::ATTENDANCE_DEDUCTION,
            self::DAMAGE_CHARGE,
            self::LOSS_CHARGE,
            self::CASH_ADVANCE,
            self::MANUAL_ADJUSTMENT,
        ];
    }
}