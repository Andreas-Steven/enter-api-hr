<?php

declare(strict_types=1);

namespace App\Domain\PayrollReport\ValueObject;

/**
 * Schedule-based day counters (the `payroll_summary.schedule` block).
 *
 * Derived from the source AttendanceSummary's schedule counts. Only exposes
 * the fields relevant to payroll: HEK days, obligation days, and the total.
 */
final readonly class ScheduleCount
{
    private function __construct(
        private int $hekDays,
        private int $obligationDays,
    ) {}

    public static function empty(): self
    {
        return new self(0, 0);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['hek_days'] ?? 0),
            (int) ($data['obligation_days'] ?? 0),
        );
    }

    public static function fromValues(int $hekDays, int $obligationDays): self
    {
        return new self($hekDays, $obligationDays);
    }

    public function getHekDays(): int
    {
        return $this->hekDays;
    }

    public function getObligationDays(): int
    {
        return $this->obligationDays;
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
            'total_working_days' => $this->getTotalWorkingDays(),
        ];
    }
}
