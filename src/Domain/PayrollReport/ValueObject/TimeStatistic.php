<?php

declare(strict_types=1);

namespace App\Domain\PayrollReport\ValueObject;

/**
 * Time-related statistics (the `payroll_summary.time` block).
 *
 * Simplified version of the AttendanceSummary time statistic: only exposes
 * the counts (no minutes) since payroll deductions are based on occurrence
 * counts, not duration.
 */
final readonly class TimeStatistic
{
    private function __construct(
        private int $lateCount,
        private int $earlyOutCount,
        private int $incompleteCount,
    ) {}

    public static function empty(): self
    {
        return new self(0, 0, 0);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['late_count'] ?? 0),
            (int) ($data['early_out_count'] ?? 0),
            (int) ($data['incomplete_count'] ?? 0),
        );
    }

    public static function fromValues(
        int $lateCount,
        int $earlyOutCount,
        int $incompleteCount,
    ): self {
        return new self($lateCount, $earlyOutCount, $incompleteCount);
    }

    public function getLateCount(): int
    {
        return $this->lateCount;
    }

    public function getEarlyOutCount(): int
    {
        return $this->earlyOutCount;
    }

    public function getIncompleteCount(): int
    {
        return $this->incompleteCount;
    }

    /** @return array<string, int> */
    public function toArray(): array
    {
        return [
            'late_count' => $this->lateCount,
            'early_out_count' => $this->earlyOutCount,
            'incomplete_count' => $this->incompleteCount,
        ];
    }
}
