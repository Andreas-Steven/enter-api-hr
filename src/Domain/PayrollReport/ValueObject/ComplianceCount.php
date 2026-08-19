<?php

declare(strict_types=1);

namespace App\Domain\PayrollReport\ValueObject;

/**
 * Compliance counters (the `payroll_summary.compliance` block).
 *
 * - fulfilled:       obligation days fully met (present + full-day).
 * - unfulfilled:     obligation days not fully met (absent / incomplete / half).
 * - deficit:         days where the employee worked but did not meet the
 *                    required shift duration (partial day).
 * - excess:          days where the employee worked beyond the required
 *                    shift duration (overtime on a working day).
 * - pending_review:  days flagged for manual review (missing in/out).
 */
final readonly class ComplianceCount
{
    private function __construct(
        private int $fulfilled,
        private int $unfulfilled,
        private int $deficit,
        private int $excess,
        private int $pendingReview,
    ) {}

    public static function empty(): self
    {
        return new self(0, 0, 0, 0, 0);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['fulfilled_days'] ?? 0),
            (int) ($data['unfulfilled_days'] ?? 0),
            (int) ($data['deficit_days'] ?? 0),
            (int) ($data['excess_days'] ?? 0),
            (int) ($data['pending_review_days'] ?? 0),
        );
    }

    public static function fromValues(
        int $fulfilled,
        int $unfulfilled,
        int $deficit,
        int $excess,
        int $pendingReview,
    ): self {
        return new self($fulfilled, $unfulfilled, $deficit, $excess, $pendingReview);
    }

    public function getFulfilledDays(): int
    {
        return $this->fulfilled;
    }

    public function getUnfulfilledDays(): int
    {
        return $this->unfulfilled;
    }

    public function getDeficitDays(): int
    {
        return $this->deficit;
    }

    public function getExcessDays(): int
    {
        return $this->excess;
    }

    public function getPendingReviewDays(): int
    {
        return $this->pendingReview;
    }

    /** @return array<string, int> */
    public function toArray(): array
    {
        return [
            'fulfilled_days' => $this->fulfilled,
            'unfulfilled_days' => $this->unfulfilled,
            'deficit_days' => $this->deficit,
            'excess_days' => $this->excess,
            'pending_review_days' => $this->pendingReview,
        ];
    }
}
