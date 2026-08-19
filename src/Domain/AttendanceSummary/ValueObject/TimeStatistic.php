<?php

declare(strict_types=1);

namespace App\Domain\AttendanceSummary\ValueObject;

/**
 * Time-related statistics (the `summary.time` block).
 *
 * Aggregates lateness and early-leave events plus incomplete attendance
 * (missing check-in or check-out) across the period.
 */
final readonly class TimeStatistic
{
    private function __construct(
        private int $lateCount,
        private int $lateMinutes,
        private int $earlyOutCount,
        private int $earlyOutMinutes,
        private int $incomplete,
    ) {}

    public static function empty(): self
    {
        return new self(0, 0, 0, 0, 0);
    }

    public static function fromArray(array $data): self
    {
        $late = $data['late'] ?? [];

        return new self(
            (int) ($late['count'] ?? 0),
            (int) ($late['minutes'] ?? 0),
            (int) ($data['early_out']['count'] ?? 0),
            (int) ($data['early_out']['minutes'] ?? 0),
            (int) ($data['incomplete'] ?? 0),
        );
    }

    public static function fromValues(
        int $lateCount,
        int $lateMinutes,
        int $earlyOutCount,
        int $earlyOutMinutes,
        int $incomplete,
    ): self {
        return new self($lateCount, $lateMinutes, $earlyOutCount, $earlyOutMinutes, $incomplete);
    }

    public function getLateCount(): int
    {
        return $this->lateCount;
    }

    public function getLateMinutes(): int
    {
        return $this->lateMinutes;
    }

    public function getEarlyOutCount(): int
    {
        return $this->earlyOutCount;
    }

    public function getEarlyOutMinutes(): int
    {
        return $this->earlyOutMinutes;
    }

    public function getIncomplete(): int
    {
        return $this->incomplete;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'late' => [
                'count' => $this->lateCount,
                'minutes' => $this->lateMinutes,
            ],
            'early_out' => [
                'count' => $this->earlyOutCount,
                'minutes' => $this->earlyOutMinutes,
            ],
            'incomplete' => $this->incomplete,
        ];
    }
}
