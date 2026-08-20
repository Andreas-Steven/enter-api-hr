<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Indicates whether the payroll net result is positive (earning) or
 * negative (deduction).
 *
 * - POSITIVE: overtime amount > deduction amount → sisa ditambah ke gaji
 * - NEGATIVE: deduction amount > overtime amount → gaji dipotong
 */
enum PayrollNetType: string
{
    case POSITIVE = 'positive';
    case NEGATIVE = 'negative';

    public function label(): string
    {
        return match ($this) {
            self::POSITIVE => 'Positif (Penambah Gaji)',
            self::NEGATIVE => 'Negatif (Potong Gaji)',
        };
    }

    /**
     * Resolve the type from the net amount sign.
     */
    public static function fromAmount(int $amount): self
    {
        return $amount >= 0 ? self::POSITIVE : self::NEGATIVE;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
