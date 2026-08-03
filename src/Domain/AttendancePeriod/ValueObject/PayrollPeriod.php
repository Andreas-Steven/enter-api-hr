<?php

declare(strict_types=1);

namespace App\Domain\AttendancePeriod\ValueObject;

use App\Domain\Shared\Enums\AttendancePeriodConfig;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Value object that derives payroll period boundaries from a period identifier.
 *
 * Period 202607 (or "2026-07") represents the July 2026 payroll cycle:
 *   - start_date:  2026-06-26  (26th of previous month)
 *   - end_date:    2026-07-25  (25th of period month)
 *   - payout_date: 2026-07-31  (last day of period month)
 *
 * All dates are derived purely from the period integer — no database lookup needed.
 */
final readonly class PayrollPeriod
{
    private function __construct(
        private int $year,
        private int $month,
    ) {}

    public static function fromInt(int $period): self
    {
        $year = intdiv($period, 100);
        $month = $period % 100;

        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException("Invalid period: {$period}");
        }

        return new self($year, $month);
    }

    public static function fromString(string $period): self
    {
        if (!preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $period, $m)) {
            throw new InvalidArgumentException("Invalid period format: {$period}. Expected YYYY-MM.");
        }

        return new self((int) $m[1], (int) $m[2]);
    }

    public static function fromDateTime(DateTimeImmutable $dateTime): self
    {
        $date = (int) $dateTime->format('d');

        if ($date > AttendancePeriodConfig::CUTOFF_DAY) {
            $dateTime = $dateTime->modify('+1 month');
        }

        return new self((int) $dateTime->format('Y'), (int) $dateTime->format('m'));
    }

    public function startDate(): DateTimeImmutable
    {
        return (new DateTimeImmutable(
            sprintf('%04d-%02d-%02d', $this->year, $this->month, AttendancePeriodConfig::START_DAY)
        ))->modify('-1 month');
    }

    public function endDate(): DateTimeImmutable
    {
        return new DateTimeImmutable(
            sprintf('%04d-%02d-%02d', $this->year, $this->month, AttendancePeriodConfig::CUTOFF_DAY)
        );
    }

    public function payoutDate(): DateTimeImmutable
    {
        return (new DateTimeImmutable(
            sprintf('%04d-%02d-01', $this->year, $this->month)
        ))->modify('last day of this month');
    }

    public function toInt(): int
    {
        return $this->year * 100 + $this->month;
    }

    public function toString(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }
}
