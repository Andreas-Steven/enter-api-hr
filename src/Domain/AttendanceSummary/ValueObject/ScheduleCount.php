<?php

declare(strict_types=1);

namespace App\Domain\AttendanceSummary\ValueObject;

/**
 * Schedule-based day counters (the `summary.schedule` block).
 *
 * Derived purely from the work schedule + holidays (NOT from attendance
 * records). Separates HEK (Hari Efektif Kerja = REQUIRED_WEEKDAY days minus
 * holidays) from Obligation days (PERIODIC_REQUIRED_DAY up to target_count,
 * minus holidays). Also exposes the total Saturday and Sunday counts in the
 * period for auditability.
 */
final readonly class ScheduleCount
{
    private function __construct(
        private int $hekDays,
        private int $obligationDays,
        private int $saturdayDays,
        private int $sundayDays,
    ) {}

    public static function empty(): self
    {
        return new self(0, 0, 0, 0);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['hek_days'] ?? 0),
            (int) ($data['obligation_days'] ?? 0),
            (int) ($data['saturday_days'] ?? 0),
            (int) ($data['sunday_days'] ?? 0),
        );
    }

    public static function fromValues(
        int $hekDays,
        int $obligationDays,
        int $saturdayDays = 0,
        int $sundayDays = 0,
    ): self {
        return new self($hekDays, $obligationDays, $saturdayDays, $sundayDays);
    }

    public function getHekDays(): int
    {
        return $this->hekDays;
    }

    public function getObligationDays(): int
    {
        return $this->obligationDays;
    }

    public function getSaturdayDays(): int
    {
        return $this->saturdayDays;
    }

    public function getSundayDays(): int
    {
        return $this->sundayDays;
    }

    public function getTotalWorkingDays(): int
    {
        return $this->hekDays + $this->obligationDays;
    }

    /** @return array<string, int> */
    public function toArray(): array
    {
        return [
            'hek_days' => $this->hekDays,
            'obligation_days' => $this->obligationDays,
            'saturday_days' => $this->saturdayDays,
            'sunday_days' => $this->sundayDays,
            'total_working_days' => $this->getTotalWorkingDays(),
        ];
    }
}
