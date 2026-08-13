<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Identifies the type of attendance incident that triggers a fine or deduction.
 *
 * Used by AttendanceFineRate to map incident types to deduction amounts,
 * and by PayrollReportDomainService to classify incidents when auto-generating
 * PayrollDeduction records from Attendance data.
 */
enum IncidentType: string
{
    case LATE = 'LATE';
    case EARLY_OUT = 'EARLY_OUT';
    case MISSING_IN = 'MISSING_IN';
    case MISSING_OUT = 'MISSING_OUT';
    case ABSENT = 'ABSENT';

    /**
     * Returns all valid incident type values.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }

    /**
     * Whether this incident type maps to ATTENDANCE_FINE category.
     */
    public function isFine(): bool
    {
        return match ($this) {
            self::LATE,
            self::EARLY_OUT,
            self::MISSING_IN,
            self::MISSING_OUT => true,
            self::ABSENT => false,
        };
    }

    /**
     * Whether this incident type maps to ATTENDANCE_DEDUCTION category.
     */
    public function isDeduction(): bool
    {
        return $this === self::ABSENT;
    }

    /**
     * The DeductionType category constant for this incident type.
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
        };
    }
}
