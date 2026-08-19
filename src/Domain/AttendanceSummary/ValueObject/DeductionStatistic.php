<?php

declare(strict_types=1);

namespace App\Domain\AttendanceSummary\ValueObject;

/**
 * Deduction statistics (the `summary.deduction` block).
 *
 * Tracks the number of days that count against payroll (absences plus
 * incomplete attendance days). Used as an input by downstream payroll reports.
 */
final readonly class DeductionStatistic
{
    private function __construct(
        private int $days,
    ) {}

    public static function empty(): self
    {
        return new self(0);
    }

    public static function fromArray(array $data): self
    {
        return new self((int) ($data['days'] ?? 0));
    }

    public static function fromValues(int $days): self
    {
        return new self($days);
    }

    public function getDays(): int
    {
        return $this->days;
    }

    /** @return array<string, int> */
    public function toArray(): array
    {
        return ['days' => $this->days];
    }
}
