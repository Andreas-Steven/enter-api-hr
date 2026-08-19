<?php

declare(strict_types=1);

namespace App\Domain\AttendanceSummary\ValueObject;

// Domain Layer
use App\Domain\Shared\Enums\AttendancePeriodConfig;
// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

/**
 * AttendanceSummary Period Value Object.
 *
 * A period is an 8-digit integer YYYYMM. Because the attendance cut-off is
 * configured (CUTOFF_DAY = 25 / START_DAY = 26), a period actually spans from
 * the START_DAY of the previous month up to the CUTOFF_DAY of the period month,
 * e.g. period 202608 => 2026-07-26 .. 2026-08-25.
 */
final readonly class Period
{
    private ?int $value;

    private function __construct(?int $value)
    {
        $this->value = $value;
    }

    public static function fromNullable(?int $value): self
    {
        if ($value === null) {
            return new self(null);
        }

        $year = intdiv($value, 100);
        $month = $value % 100;

        if ($month < 1 || $month > 12 || $year < 1900 || $year > 9999) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'attendance_summary.period.invalid',
                    domain: 'app',
                    params: ['value' => $value],
                ),
            );
        }

        return new self($value);
    }

    public function toInt(): ?int
    {
        return $this->value;
    }

    public function isNull(): bool
    {
        return $this->value === null;
    }

    /**
     * Returns the inclusive date range [start, end] that this period covers,
     * based on the configured cut-off/start days.
     *
     * @return array{start: ?string, end: ?string}
     */
    public function getDateRange(): array
    {
        if ($this->value === null) {
            return ['start' => null, 'end' => null];
        }

        $year = intdiv($this->value, 100);
        $month = $this->value % 100;

        // Start day lives in the previous month.
        $startMonth = $month - 1;
        $startYear = $year;
        if ($startMonth < 1) {
            $startMonth = 12;
            $startYear--;
        }

        $start = sprintf(
            '%04d-%02d-%02d',
            $startYear,
            $startMonth,
            AttendancePeriodConfig::START_DAY,
        );

        $end = sprintf(
            '%04d-%02d-%02d',
            $year,
            $month,
            AttendancePeriodConfig::CUTOFF_DAY,
        );

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Iterates every calendar day inside the period date range.
     *
     * @return \Generator<int, string, void, void> Yields each day as "Y-m-d".
     */
    public function iterateDays(): \Generator
    {
        $range = $this->getDateRange();

        if ($range['start'] === null || $range['end'] === null) {
            return;
        }

        $cursor = new \DateTimeImmutable($range['start']);
        $end = new \DateTimeImmutable($range['end']);

        while ($cursor <= $end) {
            yield $cursor->format('Y-m-d');
            $cursor = $cursor->modify('+1 day');
        }
    }
}
