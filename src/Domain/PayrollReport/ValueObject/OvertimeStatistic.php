<?php

declare(strict_types=1);

namespace App\Domain\PayrollReport\ValueObject;

/**
 * Overtime statistics (the `payroll_summary.overtime` block).
 *
 * Overtime days and total overtime minutes for the period.
 */
final readonly class OvertimeStatistic
{
    private function __construct(
        private int $days,
        private int $minutes,
    ) {}

    public static function empty(): self
    {
        return new self(0, 0);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['days'] ?? 0),
            (int) ($data['minutes'] ?? 0),
        );
    }

    public static function fromValues(int $days, int $minutes): self
    {
        return new self($days, $minutes);
    }

    public function getDays(): int
    {
        return $this->days;
    }

    public function getMinutes(): int
    {
        return $this->minutes;
    }

    /** @return array<string, int> */
    public function toArray(): array
    {
        return [
            'days' => $this->days,
            'minutes' => $this->minutes,
        ];
    }
}
