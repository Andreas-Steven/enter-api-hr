<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Identifies the type of payroll rate.
 *
 * Used by PayrollRatePolicies to map rate types to amounts:
 * - LATE, EARLY_OUT, MISSING_IN, MISSING_OUT, ABSENT → deduction (requires
 *   deduction_type_id pointing to a non-debt-creating PayrollDeductionType)
 * - OVERTIME → earning (deduction_type_id must be null)
 *
 * Also used by PayrollReportDomainService to classify incidents when
 * auto-generating PayrollDeduction records from Attendance data.
 */
enum PayrollRateType: string
{
    case LATE = 'LATE';
    case EARLY_OUT = 'EARLY_OUT';
    case MISSING_IN = 'MISSING_IN';
    case MISSING_OUT = 'MISSING_OUT';
    case ABSENT = 'ABSENT';
    case OVERTIME = 'OVERTIME';

    /**
     * Returns all valid rate type values.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }

    /**
     * Whether this rate type maps to ATTENDANCE_FINE category.
     */
    public function isFine(): bool
    {
        return match ($this) {
            self::LATE,
            self::EARLY_OUT,
            self::MISSING_IN,
            self::MISSING_OUT => true,
            self::ABSENT,
            self::OVERTIME => false,
        };
    }

    /**
     * Whether this rate type maps to ATTENDANCE_DEDUCTION category.
     */
    public function isDeduction(): bool
    {
        return $this === self::ABSENT;
    }

    /**
     * Whether this rate type is an earning (not a deduction).
     */
    public function isEarning(): bool
    {
        return $this === self::OVERTIME;
    }

    /**
     * Whether this rate type requires a deduction_type_id.
     *
     * Deduction rates (LATE, EARLY_OUT, MISSING_IN, MISSING_OUT, ABSENT)
     * must reference a PayrollDeductionType with creates_debt = 0.
     * Earning rates (OVERTIME) must NOT have a deduction_type_id.
     */
    public function requiresDeductionType(): bool
    {
        return !$this->isEarning();
    }

    /**
     * The DeductionType category constant for this rate type.
     */
    public function deductionCategory(): string
    {
        return $this->isFine()
            ? DeductionType::ATTENDANCE_FINE
            : DeductionType::ATTENDANCE_DEDUCTION;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::LATE => 'Terlambat',
            self::EARLY_OUT => 'Pulang Cepat',
            self::MISSING_IN => 'Tidak Check-In',
            self::MISSING_OUT => 'Tidak Check-Out',
            self::ABSENT => 'Tidak Masuk',
            self::OVERTIME => 'Lembur',
        };
    }
}
