<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Indicates whether a PayrollDeductionType creates an employee debt.
 *
 * - NO  (0): Direct salary deduction (potong gaji). Does NOT create debt.
 *            Examples: attendance fines (LATE, ABSENT, etc.), penalties.
 *            Suitable for PayrollRatePolicies deduction incidents.
 *
 * - YES (1): Debt-creating deduction (kasbon). Creates an EmployeeDebt record
 *            that must be repaid over time. Examples: cash advance, loan.
 *            NOT suitable for PayrollRatePolicies deduction incidents.
 */
enum CreatesDebt: int
{
    case NO = 0;
    case YES = 1;

    public function label(): string
    {
        return match ($this) {
            self::NO => 'Tidak Membuat Hutang',
            self::YES => 'Membuat Hutang',
        };
    }

    public function isYes(): bool
    {
        return $this === self::YES;
    }

    public function isNo(): bool
    {
        return $this === self::NO;
    }

    /**
     * @return list<int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
